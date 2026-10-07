<?php

namespace Quantum\Tests\Unit\Config;

use Quantum\Tests\Unit\AppTestCase;
use Quantum\Config\Setup;

class SetupTest extends AppTestCase
{
    private Setup $setup;

    public function setUp(): void
    {
        parent::setUp();

        $this->setup = new Setup();
    }

    public function tearDown(): void
    {
        unset($this->setup);
    }

    public function testSetupConstructor(): void
    {
        $setup = new Setup('config', 'database');

        $this->assertEquals('config', $setup->getPathPrefix());

        $this->assertEquals('database', $setup->getFilename());

        $this->assertEquals(true, $setup->getHierarchy());

        $this->assertEquals('File `' . $setup->getPathPrefix() . DS . $setup->getFilename() . '` not found!', $setup->getExceptionMessage());
    }

    public function testSetupUsesCurrentRequestModuleWhenModuleIsOmitted(): void
    {
        $this->testRequest('/');

        $setup = new Setup('config', 'dependencies');

        request()->setMatchedRoute(null);

        $this->assertSame('Test', $setup->getModule());
        $this->assertSame('Meme', (new Setup('config', 'dependencies', true, 'Meme'))->getModule());
    }

    public function testDefaultExceptionMessageIsCapturedAtConstruction(): void
    {
        $setup = new Setup('config', 'app');

        $setup->setPathPrefix('other')->setFilename('changed');

        $this->assertSame('File `config' . DS . 'app` not found!', $setup->getExceptionMessage());
    }

    public function testSetGetPathPrefix(): void
    {
        $this->setup->setPathPrefix('config');

        $this->assertEquals('config', $this->setup->getPathPrefix());
    }

    public function testSetGetFilename(): void
    {
        $this->setup->setFilename('users');

        $this->assertEquals('users', $this->setup->getFilename());
    }

    public function testSetGetHierarchy(): void
    {
        $this->setup->setHierarchy(true);

        $this->assertTrue($this->setup->getHierarchy());
    }

    public function testSetGetModule(): void
    {
        $this->setup->setModule('admin');

        $this->assertEquals('admin', $this->setup->getModule());
    }

    public function testSetGetExceptionMessage(): void
    {
        $this->setup->setExceptionMessage('Action not allowed');

        $this->assertEquals('Action not allowed', $this->setup->getExceptionMessage());
    }

}
