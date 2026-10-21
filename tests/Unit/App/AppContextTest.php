<?php

namespace Quantum\Tests\Unit\App;

use Quantum\Environment\Environment;
use PHPUnit\Framework\TestCase;
use Quantum\App\Enums\ExitCode;
use Quantum\Di\DiContainer;
use Quantum\App\AppContext;
use Quantum\Config\Config;
use Mockery;

class AppContextTest extends TestCase
{
    public function testAppContextBaseDir(): void
    {
        $context = new AppContext('/my/base/dir', new DiContainer());

        $this->assertSame('/my/base/dir', $context->getBaseDir());
    }

    public function testAppContextContainer(): void
    {
        $container = new DiContainer();
        $context = new AppContext('/tmp', $container);

        $this->assertSame($container, $context->getContainer());
    }

    public function testAppContextGetEnvironment(): void
    {
        $container = new DiContainer();
        $environment = Mockery::mock(Environment::class);
        $container->set(Environment::class, $environment);

        $context = new AppContext('/tmp', $container);

        $this->assertSame($environment, $context->getEnvironment());
    }

    public function testAppContextGetConfig(): void
    {
        $container = new DiContainer();
        $config = Mockery::mock(Config::class);
        $container->set(Config::class, $config);

        $context = new AppContext('/tmp', $container);

        $this->assertSame($config, $context->getConfig());
    }

    public function testAppContextIsNotStoppedByDefault(): void
    {
        $context = new AppContext('/tmp', new DiContainer());

        $this->assertFalse($context->isStopped());
    }

    public function testAppContextStop(): void
    {
        $context = new AppContext('/tmp', new DiContainer());

        $context->stop();

        $this->assertTrue($context->isStopped());
    }

    public function testAppContextExitCodeDefaultsToSuccess(): void
    {
        $context = new AppContext('/tmp', new DiContainer());

        $this->assertSame(ExitCode::SUCCESS, $context->getExitCode());
    }

    public function testAppContextSetExitCode(): void
    {
        $context = new AppContext('/tmp', new DiContainer());

        $context->setExitCode(ExitCode::INVALID);

        $this->assertSame(ExitCode::INVALID, $context->getExitCode());
    }
}
