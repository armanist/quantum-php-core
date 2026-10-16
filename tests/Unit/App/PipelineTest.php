<?php

namespace Quantum\Tests\Unit\App;

use Quantum\App\Adapters\Console\ConsoleContext;
use Quantum\App\Stages\Boot\LoadHelpersStage;
use Quantum\App\Contracts\StageInterface;
use Quantum\App\Adapters\Web\WebContext;
use Quantum\Tests\Unit\AppTestCase;
use InvalidArgumentException;
use Quantum\App\Enums\ExitCode;
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

    public function testPipelineCarriesTheStoppedFlagOfAWebContextBetweenStages(): void
    {
        $stoppedSeenBySecondStage = null;

        (new Pipeline([
            $this->createStage(function (WebContext $context): void {
                $context->stop();
            }),
            $this->createStage(function (WebContext $context) use (&$stoppedSeenBySecondStage): void {
                $stoppedSeenBySecondStage = $context->isStopped();
            }),
        ]))->run(new WebContext($this->context));

        $this->assertTrue($stoppedSeenBySecondStage);
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
