<?php

namespace Module;

use Quantum\Tests\Unit\AppTestCase;
use Quantum\Module\ModuleLoader;
use Quantum\Router\Router;

class ModuleLoaderTest extends AppTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->setPrivateProperty(ModuleLoader::class, 'instance', null);

        $this->moduleLoader = ModuleLoader::getInstance();
    }

    public function tearDown(): void
    {
        parent::tearDown();
    }

    public function testGetInstance()
    {
        $this->assertInstanceOf(ModuleLoader::class, $this->moduleLoader);
    }

    public function testLoadModulesRoutesWithEnabledModules()
    {
        $modulesRoutes = $this->moduleLoader->loadModulesRoutes();

        Router::setRoutes($modulesRoutes);

        $this->assertNotEmpty(Router::getRoutes());

        $this->assertIsArray(Router::getRoutes());

        $this->assertCount(2, Router::getRoutes());
    }
}