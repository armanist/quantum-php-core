<?php

namespace Quantum\Tests\Unit\App\Adapters\Web;

use Quantum\App\Contracts\WebStageInterface;
use Quantum\App\Contracts\BootStageInterface;
use Quantum\App\Adapters\Web\WebPipeline;
use Quantum\Tests\Unit\AppTestCase;
use Quantum\App\Adapters\Web\WebContext;
use InvalidArgumentException;
use Quantum\App\AppContext;
use RuntimeException;

class WebPipelineTest extends AppTestCase
{
    private WebContext $webContext;

    public function setUp(): void
    {
        $this->webContext = new WebContext($this->createContext());
    }

    public function tearDown(): void
    {
        $this->clearAppContext();
    }

    public function testPipelineRunsStagesInOrder(): void
    {
        $log = [];

        $pipeline = new WebPipeline([
            $this->createStage(function () use (&$log): void {
                $log[] = 'first';
            }),
            $this->createStage(function () use (&$log): void {
                $log[] = 'second';
            }),
            $this->createStage(function () use (&$log): void {
                $log[] = 'third';
            }),
        ]);

        $pipeline->run($this->webContext);

        $this->assertSame(['first', 'second', 'third'], $log);
    }

    public function testEmptyPipelineRunsWithoutError(): void
    {
        $pipeline = new WebPipeline([]);

        $pipeline->run($this->webContext);

        $this->assertFalse($this->webContext->isStopped());
    }

    public function testPipelinePassesContextToStages(): void
    {
        $received = null;

        $pipeline = new WebPipeline([
            $this->createStage(function (WebContext $context) use (&$received): void {
                $received = $context;
            }),
        ]);

        $pipeline->run($this->webContext);

        $this->assertSame($this->webContext, $received);
    }

    public function testPipelinePropagatesException(): void
    {
        $pipeline = new WebPipeline([
            $this->createStage(function (): void {
                throw new RuntimeException('Stage failed');
            }),
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Stage failed');

        $pipeline->run($this->webContext);
    }

    public function testPipelineKeepsRunningStagesAfterContextIsStopped(): void
    {
        $log = [];

        $pipeline = new WebPipeline([
            $this->createStage(function (WebContext $context) use (&$log): void {
                $log[] = 'first';
                $context->stop();
            }),
            $this->createStage(function () use (&$log): void {
                $log[] = 'second';
            }),
        ]);

        $pipeline->run($this->webContext);

        $this->assertSame(['first', 'second'], $log);
        $this->assertTrue($this->webContext->isStopped());
    }

    public function testPipelineRejectsInvalidStage(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('All stages must implement');

        new WebPipeline([new \stdClass()]);
    }

    public function testPipelineRejectsBootStage(): void
    {
        $bootStage = new class () implements BootStageInterface {
            public function process(AppContext $context): void
            {
            }
        };

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('All stages must implement');

        new WebPipeline([$bootStage]);
    }

    private function createStage(callable $callback): WebStageInterface
    {
        return new class ($callback) implements WebStageInterface {
            private $callback;

            public function __construct(callable $callback)
            {
                $this->callback = $callback;
            }

            public function process(WebContext $context): void
            {
                ($this->callback)($context);
            }
        };
    }
}
