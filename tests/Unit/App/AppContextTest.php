<?php

namespace Quantum\Tests\Unit\App;

use Quantum\Environment\Environment;
use PHPUnit\Framework\TestCase;
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
}
