<?php

namespace Quantum\Tests\Unit\App;

use Quantum\Environment\Environment;
use PHPUnit\Framework\TestCase;
use Quantum\App\Enums\ExitCode;
use Quantum\App\Enums\AppType;
use Quantum\Di\DiContainer;
use Quantum\App\AppContext;
use Quantum\Config\Config;
use Mockery;

class AppContextTest extends TestCase
{
    public function testAppContextBaseDir(): void
    {
        $context = new AppContext('/my/base/dir', new DiContainer(), AppType::WEB);

        $this->assertSame('/my/base/dir', $context->getBaseDir());
    }

    public function testAppContextType(): void
    {
        $context = new AppContext('/tmp', new DiContainer(), AppType::CONSOLE);

        $this->assertSame(AppType::CONSOLE, $context->getType());
    }

    public function testAppContextContainer(): void
    {
        $container = new DiContainer();
        $context = new AppContext('/tmp', $container, AppType::WEB);

        $this->assertSame($container, $context->getContainer());
    }

    public function testAppContextGetEnvironment(): void
    {
        $container = new DiContainer();
        $environment = Mockery::mock(Environment::class);
        $container->set(Environment::class, $environment);

        $context = new AppContext('/tmp', $container, AppType::WEB);

        $this->assertSame($environment, $context->getEnvironment());
    }

    public function testAppContextGetConfig(): void
    {
        $container = new DiContainer();
        $config = Mockery::mock(Config::class);
        $container->set(Config::class, $config);

        $context = new AppContext('/tmp', $container, AppType::WEB);

        $this->assertSame($config, $context->getConfig());
    }

    public function testAppContextIsNotStoppedByDefault(): void
    {
        $context = new AppContext('/tmp', new DiContainer(), AppType::WEB);

        $this->assertFalse($context->isStopped());
    }

    public function testAppContextStop(): void
    {
        $context = new AppContext('/tmp', new DiContainer(), AppType::WEB);

        $context->stop();

        $this->assertTrue($context->isStopped());
    }

    public function testAppContextExitCodeDefaultsToSuccess(): void
    {
        $context = new AppContext('/tmp', new DiContainer(), AppType::WEB);

        $this->assertSame(ExitCode::SUCCESS, $context->getExitCode());
    }

    public function testAppContextSetExitCode(): void
    {
        $context = new AppContext('/tmp', new DiContainer(), AppType::WEB);

        $context->setExitCode(ExitCode::INVALID);

        $this->assertSame(ExitCode::INVALID, $context->getExitCode());
    }
}
