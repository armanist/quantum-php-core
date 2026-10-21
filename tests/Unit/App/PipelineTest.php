<?php

namespace Quantum\Tests\Unit\App;

use Quantum\App\Adapters\Console\Stages\Boot\SetupConsoleApplicationStage;
use Quantum\App\Adapters\Web\Stages\Runtime\HandleRouteNotFoundStage;
use Quantum\App\Adapters\Web\Stages\Runtime\HandlePreflightStage;
use Quantum\App\Adapters\Web\Stages\Runtime\DispatchRequestStage;
use Quantum\App\Adapters\Console\Stages\Runtime\RunCommandStage;
use Quantum\App\Adapters\Web\Stages\Runtime\PrepareRequestStage;
use Quantum\App\Adapters\Web\Stages\Runtime\SendResponseStage;
use Quantum\App\Adapters\Web\Stages\Runtime\ResolveRouteStage;
use Quantum\App\Adapters\Console\Stages\Boot\InitConsoleStage;
use Quantum\App\Adapters\Web\Stages\Boot\InitDebuggerStage;
use Quantum\App\Adapters\Web\Stages\Boot\LoadModulesStage;
use Quantum\App\Adapters\Web\Stages\Boot\InitHttpStage;
use Quantum\App\Stages\Boot\SetupErrorHandlerStage;
use Quantum\App\Stages\Boot\LoadEnvironmentStage;
use Quantum\App\Adapters\Console\ConsoleContext;
use Quantum\App\Stages\Boot\LoadAppConfigStage;
use Quantum\App\Stages\Boot\LoadHelpersStage;
use Quantum\App\Contracts\StageInterface;
use Quantum\App\Adapters\Web\WebContext;
use Quantum\Tests\Unit\AppTestCase;
use Quantum\App\Enums\ExitCode;
use InvalidArgumentException;
use Quantum\Di\DiContainer;
use Quantum\App\AppContext;
use Quantum\App\Pipeline;
use RuntimeException;

class PipelineTest extends AppTestCase
{
    public function setUp(): void
    {
        $this->context = $this->createContext();
    }

    public function tearDown(): void
    {
        $this->clearAppContext();
    }

    public function testPipelineRunsStagesInOrder(): void
    {
        $log = [];

        (new Pipeline([
            $this->createStage(function () use (&$log): void {
                $log[] = 'first';
            }),
            $this->createStage(function () use (&$log): void {
                $log[] = 'second';
            }),
            $this->createStage(function () use (&$log): void {
                $log[] = 'third';
            }),
        ]))->run(new AppContext('', new DiContainer()));

        $this->assertSame(['first', 'second', 'third'], $log);
    }

    public function testEmptyPipelineRunsWithoutError(): void
    {
        (new Pipeline([]))->run(new AppContext('', new DiContainer()));

        $this->assertTrue(true);
    }

    public function testPipelinePassesTheContextToEveryStage(): void
    {
        $context = new AppContext('/test/dir', new DiContainer());
        $received = [];

        $stage = $this->createStage(function (AppContext $context) use (&$received): void {
            $received[] = $context;
        });

        (new Pipeline([$stage, $stage]))->run($context);

        $this->assertSame([$context, $context], $received);
    }

    public function testPipelineRejectsAnythingThatIsNotAStage(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('All stages must implement ' . StageInterface::class);

        new Pipeline([new \stdClass()]);
    }

    public function testPipelineDispatchesEventsAroundAStageWithTheContext(): void
    {
        $this->loadHelpers();

        $log = [];

        foreach (['test.first.before', 'test.first.after'] as $name) {
            event()->listen($name, function (array $payload) use (&$log, $name): void {
                $log[] = [$name, $payload];
            });
        }

        (new Pipeline([$this->createFirstEventStage(function () use (&$log): void {
            $log[] = 'process';
        })]))->run($this->context);

        $this->assertSame([
            ['test.first.before', ['context' => $this->context]],
            'process',
            ['test.first.after', ['context' => $this->context]],
        ], $log);
    }

    public function testPipelineDispatchesEventsInStageOrder(): void
    {
        $this->loadHelpers();

        $log = [];

        foreach (['test.first.before', 'test.first.after', 'test.second.before', 'test.second.after'] as $name) {
            event()->listen($name, function () use (&$log, $name): void {
                $log[] = $name;
            });
        }

        (new Pipeline([
            $this->createFirstEventStage(function () use (&$log): void {
                $log[] = 'process first';
            }),
            $this->createSecondEventStage(function () use (&$log): void {
                $log[] = 'process second';
            }),
        ]))->run($this->context);

        $this->assertSame([
            'test.first.before',
            'process first',
            'test.first.after',
            'test.second.before',
            'process second',
            'test.second.after',
        ], $log);
    }

    public function testPipelineDispatchesOnlyTheEventAStageDeclares(): void
    {
        $this->loadHelpers();

        $log = [];

        event()->listen('test.after-only.after', function () use (&$log): void {
            $log[] = 'after';
        });

        (new Pipeline([$this->createAfterOnlyEventStage(function () use (&$log): void {
            $log[] = 'process';
        })]))->run($this->context);

        $this->assertSame(['process', 'after'], $log);
    }

    public function testPipelineKeepsListenersAfterStageEvents(): void
    {
        $this->loadHelpers();

        $count = 0;

        event()->listen('test.first.before', function () use (&$count): void {
            $count++;
        });

        $pipeline = new Pipeline([$this->createFirstEventStage(function (): void {
        })]);

        $pipeline->run($this->context);
        $pipeline->run($this->context);

        $this->assertSame(2, $count);
    }

    public function testPipelinePropagatesAnExceptionWithoutTheAfterEvent(): void
    {
        $this->loadHelpers();

        $after = false;

        event()->listen('test.first.after', function () use (&$after): void {
            $after = true;
        });

        try {
            (new Pipeline([$this->createFirstEventStage(function (): void {
                throw new RuntimeException('Stage failed');
            })]))->run($this->context);

            $this->fail('Expected the stage exception to propagate.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Stage failed', $exception->getMessage());
        }

        $this->assertFalse($after);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testPipelineSkipsStageEventsWhileEventHelperIsNotLoaded(): void
    {
        $this->assertFalse(function_exists('event'));

        $processed = false;

        (new Pipeline([$this->createFirstEventStage(function () use (&$processed): void {
            $processed = true;
        })]))->run($this->context);

        $this->assertTrue($processed);
    }

    public function testPipelineEndsTheRunWhenAStageStopsTheContext(): void
    {
        $log = [];

        (new Pipeline([
            $this->createStage(function (AppContext $context) use (&$log): void {
                $log[] = 'first';
                $context->stop();
            }),
            $this->createStage(function () use (&$log): void {
                $log[] = 'second';
            }),
        ]))->run(new WebContext($this->context));

        $this->assertSame(['first'], $log);
    }

    public function testPipelineDispatchesNoEventsOfTheStagesAfterAStop(): void
    {
        $this->loadHelpers();

        $log = [];

        foreach (['test.first.before', 'test.first.after', 'test.second.before', 'test.second.after'] as $name) {
            event()->listen($name, function () use (&$log, $name): void {
                $log[] = $name;
            });
        }

        (new Pipeline([
            $this->createFirstEventStage(function (AppContext $context): void {
                $context->stop();
            }),
            $this->createSecondEventStage(function (): void {
            }),
        ]))->run($this->context);

        $this->assertSame(['test.first.before', 'test.first.after'], $log);
    }

    public function testPipelineRunsTheClosingStageAfterTheStages(): void
    {
        $log = [];

        (new Pipeline(
            [
                $this->createStage(function () use (&$log): void {
                    $log[] = 'first';
                }),
                $this->createStage(function () use (&$log): void {
                    $log[] = 'second';
                }),
            ],
            $this->createStage(function () use (&$log): void {
                $log[] = 'closing';
            })
        ))->run($this->context);

        $this->assertSame(['first', 'second', 'closing'], $log);
    }

    public function testPipelineRunsTheClosingStageAfterAStop(): void
    {
        $log = [];

        (new Pipeline(
            [
                $this->createStage(function (AppContext $context) use (&$log): void {
                    $log[] = 'first';
                    $context->stop();
                }),
                $this->createStage(function () use (&$log): void {
                    $log[] = 'second';
                }),
            ],
            $this->createStage(function () use (&$log): void {
                $log[] = 'closing';
            })
        ))->run($this->context);

        $this->assertSame(['first', 'closing'], $log);
    }

    public function testPipelineDispatchesTheEventsOfTheClosingStageAfterAStop(): void
    {
        $this->loadHelpers();

        $log = [];

        foreach (['test.second.before', 'test.second.after'] as $name) {
            event()->listen($name, function () use (&$log, $name): void {
                $log[] = $name;
            });
        }

        (new Pipeline(
            [
                $this->createStage(function (AppContext $context): void {
                    $context->stop();
                }),
            ],
            $this->createSecondEventStage(function () use (&$log): void {
                $log[] = 'closing';
            })
        ))->run($this->context);

        $this->assertSame(['test.second.before', 'closing', 'test.second.after'], $log);
    }

    public function testPipelineSkipsTheClosingStageWhenAStageThrows(): void
    {
        $closed = false;

        try {
            (new Pipeline(
                [
                    $this->createStage(function (): void {
                        throw new RuntimeException('Stage failed');
                    }),
                ],
                $this->createStage(function () use (&$closed): void {
                    $closed = true;
                })
            ))->run($this->context);

            $this->fail('Expected the stage exception to propagate.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Stage failed', $exception->getMessage());
        }

        $this->assertFalse($closed);
    }

    public function testPipelineCarriesTheExitCodeOfAConsoleContextBetweenStages(): void
    {
        $exitCodeSeenBySecondStage = null;

        (new Pipeline([
            $this->createStage(function (ConsoleContext $context): void {
                $context->setExitCode(ExitCode::INVALID);
            }),
            $this->createStage(function (ConsoleContext $context) use (&$exitCodeSeenBySecondStage): void {
                $exitCodeSeenBySecondStage = $context->getExitCode();
            }),
        ]))->run(new ConsoleContext($this->context));

        $this->assertSame(ExitCode::INVALID, $exitCodeSeenBySecondStage);
    }

    public function testPipelineStagesDeclareBootEventNames(): void
    {
        $this->assertSame('boot.environment.before', LoadEnvironmentStage::BEFORE);
        $this->assertSame('boot.environment.after', LoadEnvironmentStage::AFTER);

        $this->assertSame('boot.config.before', LoadAppConfigStage::BEFORE);
        $this->assertSame('boot.config.after', LoadAppConfigStage::AFTER);

        $this->assertSame('boot.error_handler.before', SetupErrorHandlerStage::BEFORE);
        $this->assertSame('boot.error_handler.after', SetupErrorHandlerStage::AFTER);

        $this->assertSame('boot.http.before', InitHttpStage::BEFORE);
        $this->assertSame('boot.http.after', InitHttpStage::AFTER);

        $this->assertSame('boot.modules.before', LoadModulesStage::BEFORE);
        $this->assertSame('boot.modules.after', LoadModulesStage::AFTER);
    }

    public function testPipelineRuntimeStagesDeclareTheirEventNames(): void
    {
        $this->assertSame('web.preflight.before', HandlePreflightStage::BEFORE);
        $this->assertSame('web.preflight.after', HandlePreflightStage::AFTER);

        $this->assertSame('web.route.before', ResolveRouteStage::BEFORE);
        $this->assertSame('web.route.after', ResolveRouteStage::AFTER);

        $this->assertSame('web.not_found.before', HandleRouteNotFoundStage::BEFORE);
        $this->assertSame('web.not_found.after', HandleRouteNotFoundStage::AFTER);

        $this->assertSame('web.prepare.before', PrepareRequestStage::BEFORE);
        $this->assertSame('web.prepare.after', PrepareRequestStage::AFTER);

        $this->assertSame('web.dispatch.before', DispatchRequestStage::BEFORE);
        $this->assertSame('web.dispatch.after', DispatchRequestStage::AFTER);

        $this->assertSame('web.send.before', SendResponseStage::BEFORE);
        $this->assertSame('web.send.after', SendResponseStage::AFTER);

        $this->assertSame('console.command.before', RunCommandStage::BEFORE);
        $this->assertSame('console.command.after', RunCommandStage::AFTER);
    }

    public function testLoadHelpersInitConsoleInitDebuggerAndSetupConsoleApplicationStagesDeclareNoEvents(): void
    {
        foreach ([LoadHelpersStage::class, InitConsoleStage::class, InitDebuggerStage::class, SetupConsoleApplicationStage::class] as $stage) {
            $this->assertFalse(defined($stage . '::BEFORE'), $stage . ' must not declare BEFORE');
            $this->assertFalse(defined($stage . '::AFTER'), $stage . ' must not declare AFTER');
        }
    }

    private function loadHelpers(): void
    {
        (new LoadHelpersStage())->process($this->context);
    }

    private function createStage(callable $callback): StageInterface
    {
        return new class ($callback) implements StageInterface {
            private $callback;

            public function __construct(callable $callback)
            {
                $this->callback = $callback;
            }

            public function process(AppContext $context): void
            {
                ($this->callback)($context);
            }
        };
    }

    private function createFirstEventStage(callable $callback): StageInterface
    {
        return new class ($callback) implements StageInterface {
            public const BEFORE = 'test.first.before';
            public const AFTER = 'test.first.after';

            private $callback;

            public function __construct(callable $callback)
            {
                $this->callback = $callback;
            }

            public function process(AppContext $context): void
            {
                ($this->callback)($context);
            }
        };
    }

    private function createSecondEventStage(callable $callback): StageInterface
    {
        return new class ($callback) implements StageInterface {
            public const BEFORE = 'test.second.before';
            public const AFTER = 'test.second.after';

            private $callback;

            public function __construct(callable $callback)
            {
                $this->callback = $callback;
            }

            public function process(AppContext $context): void
            {
                ($this->callback)($context);
            }
        };
    }

    private function createAfterOnlyEventStage(callable $callback): StageInterface
    {
        return new class ($callback) implements StageInterface {
            public const AFTER = 'test.after-only.after';

            private $callback;

            public function __construct(callable $callback)
            {
                $this->callback = $callback;
            }

            public function process(AppContext $context): void
            {
                ($this->callback)($context);
            }
        };
    }
}
