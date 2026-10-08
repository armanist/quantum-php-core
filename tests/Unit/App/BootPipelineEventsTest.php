<?php

namespace Quantum\Tests\Unit\App;

use Quantum\App\Stages\SetupErrorHandlerStage;
use Quantum\App\Stages\LoadEnvironmentStage;
use Quantum\App\Contracts\BootStageInterface;
use Quantum\App\Stages\LoadAppConfigStage;
use Quantum\App\Stages\InitDebuggerStage;
use Quantum\App\Stages\LoadModulesStage;
use Quantum\App\Stages\LoadHelpersStage;
use Quantum\App\Stages\InitHttpStage;
use Quantum\Tests\Unit\AppTestCase;
use Quantum\App\BootPipeline;
use Quantum\Di\DiContainer;
use Quantum\App\AppContext;

class BootPipelineEventsTest extends AppTestCase
{
    public function testEventsAreDispatchedAroundStage(): void
    {
        $log = [];

        event()->listen('test.first.before', function () use (&$log): void {
            $log[] = 'before';
        });

        event()->listen('test.first.after', function () use (&$log): void {
            $log[] = 'after';
        });

        $pipeline = new BootPipeline([$this->firstStage(function () use (&$log): void {
            $log[] = 'process';
        })]);

        $pipeline->run($this->context());

        $this->assertSame(['before', 'process', 'after'], $log);
    }

    public function testEventsFollowStageOrder(): void
    {
        $log = [];

        foreach (['test.first.before', 'test.first.after', 'test.second.before', 'test.second.after'] as $name) {
            event()->listen($name, function () use (&$log, $name): void {
                $log[] = $name;
            });
        }

        $pipeline = new BootPipeline([
            $this->firstStage(function () use (&$log): void {
                $log[] = 'process first';
            }),
            $this->secondStage(function () use (&$log): void {
                $log[] = 'process second';
            }),
        ]);

        $pipeline->run($this->context());

        $this->assertSame([
            'test.first.before',
            'process first',
            'test.first.after',
            'test.second.before',
            'process second',
            'test.second.after',
        ], $log);
    }

    public function testEventPayloadCarriesContext(): void
    {
        $payloads = [];

        event()->listen('test.first.before', function (array $payload) use (&$payloads): void {
            $payloads[] = $payload;
        });

        event()->listen('test.first.after', function (array $payload) use (&$payloads): void {
            $payloads[] = $payload;
        });

        $context = $this->context();

        (new BootPipeline([$this->firstStage(function (): void {
        })]))->run($context);

        $this->assertCount(2, $payloads);
        $this->assertSame(['context'], array_keys($payloads[0]));
        $this->assertSame($context, $payloads[0]['context']);
        $this->assertSame($context, $payloads[1]['context']);
    }

    public function testStageDeclaringOnlyAfterDispatchesOnlyAfter(): void
    {
        $log = [];

        event()->listen('test.after-only.after', function () use (&$log): void {
            $log[] = 'after';
        });

        (new BootPipeline([$this->afterOnlyStage(function () use (&$log): void {
            $log[] = 'process';
        })]))->run($this->context());

        $this->assertSame(['process', 'after'], $log);
    }

    public function testListenersAreNotConsumedByStageEvents(): void
    {
        $count = 0;

        event()->listen('test.first.before', function () use (&$count): void {
            $count++;
        });

        $pipeline = new BootPipeline([$this->firstStage(function (): void {
        })]);

        $pipeline->run($this->context());
        $pipeline->run($this->context());

        $this->assertSame(2, $count);
    }

    public function testBootStagesDeclareEventNames(): void
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

    public function testHelpersAndDebuggerStagesDeclareNoEvents(): void
    {
        foreach ([LoadHelpersStage::class, InitDebuggerStage::class] as $stage) {
            $this->assertFalse(defined($stage . '::BEFORE'), $stage . ' must not declare BEFORE');
            $this->assertFalse(defined($stage . '::AFTER'), $stage . ' must not declare AFTER');
        }
    }

    private function context(): AppContext
    {
        return new AppContext(PROJECT_ROOT, new DiContainer());
    }

    private function firstStage(callable $callback): BootStageInterface
    {
        return new class ($callback) implements BootStageInterface {
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

    private function secondStage(callable $callback): BootStageInterface
    {
        return new class ($callback) implements BootStageInterface {
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

    private function afterOnlyStage(callable $callback): BootStageInterface
    {
        return new class ($callback) implements BootStageInterface {
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
