<?php

namespace Quantum\Tests\Environment;

use Quantum\Libraries\Storage\FileSystem;
use Quantum\Environment\Environment;
use Quantum\Tests\AppTestCase;
use Quantum\Loader\Setup;

class EnvironmentTest extends AppTestCase
{

    private $env;

    public function setUp(): void
    {
        parent::setUp();

        $fs = new FileSystem();

        $fs->put(base_dir() . DS . '.env.staging', "DEBUG=TRUE\nAPP_KEY=AB1234567890\n");

        $this->env = Environment::getInstance()->load(new Setup('config', 'env'));
    }

    public function testEnvLoadAndGetValue()
    {
        $this->assertNull($this->env->getValue('NON_EXISTING_KEY'));

        $this->assertNotNull($this->env->getValue('APP_KEY'));

        $this->assertEquals('AB1234567890', $this->env->getValue('APP_KEY'));

        $this->assertEquals('TRUE', $this->env->getValue('DEBUG'));
    }

    public function testEnvUpdateRow()
    {
        $this->assertEquals('AB1234567890', $this->env->getValue('APP_KEY'));

        $this->env->updateRow('APP_KEY', 'ZX1234567890');

        $this->assertEquals('ZX1234567890', $this->env->getValue('APP_KEY'));

        $this->assertNull($this->env->getValue('NON_YET_EXISTING_KEY'));

        $this->env->updateRow('NON_YET_EXISTING_KEY', 'Something');

        $this->assertEquals('Something', $this->env->getValue('NON_YET_EXISTING_KEY'));
    }

}
