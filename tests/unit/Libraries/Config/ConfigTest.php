<?php

namespace Quantum\Tests\Libraries\Config;

use Quantum\Exceptions\ConfigException;
use Quantum\Exceptions\LoaderException;
use Quantum\Libraries\Config\Config;
use Dflydev\DotAccessData\Data;
use Quantum\Tests\AppTestCase;
use Quantum\Loader\Setup;

class ConfigTest extends AppTestCase
{

    public function setUp(): void
    {
        parent::setUp();
        
        config()->flush();
    }

    public function testConfigLoad()
    {
        $config = Config::getInstance();

        $this->assertEmpty($config->all());

        $config->load(new Setup('config', 'config'));

        $this->assertNotEmpty($config->all());

        $this->assertInstanceOf(Data::class, $config->all());

        $this->expectException(ConfigException::class);

        $this->expectExceptionMessage('config_already_loaded');

        $config->load(new Setup('config', 'config'));
    }

    public function testLoadingNonExistingConfigFile()
    {
        $this->expectException(LoaderException::class);

        $this->expectExceptionMessage('File `config\somefile` not found!');

        Config::getInstance()->load(new Setup('config', 'somefile'));
    }

    public function testConfigImport()
    {
        $config = Config::getInstance();

        $config->load(new Setup('config', 'config'));

        $this->assertNull($config->get('database.current'));

        $config->import(new Setup('config', 'database'));

        $this->assertNotNull($config->get('database.current'));

        $this->assertEquals('mysql', $config->get('database.current'));
    }

    public function testImportingNonExistingConfigFile()
    {
        $this->expectException(LoaderException::class);

        $this->expectExceptionMessage('File `config\somefile` not found!');

        Config::getInstance()->import(new Setup('config', 'somefile'));
    }

    public function testCollisionAtImporting()
    {
        $config = Config::getInstance();

        $config->import(new Setup('config', 'config'));

        $this->expectException(ConfigException::class);

        $this->expectExceptionMessage('config_collision');

        $config->import(new Setup('config', 'config'));
    }

    public function testConfigHas()
    {
        $config = Config::getInstance();

        $config->load(new Setup('config', 'config'));

        $this->assertTrue($config->has('debug'));

        $this->assertTrue($config->has('test'));

        $this->assertFalse($config->has('none'));
    }

    public function testConfigGet()
    {
        $config = Config::getInstance();

        $config->load(new Setup('config', 'config'));

        $this->assertIsArray($config->get('langs'));

        $this->assertEquals('Testing', $config->get('test'));

        $this->assertEquals('Default Value', $config->get('not-exists', 'Default Value'));

        $this->assertNull($config->get('not-exists'));
    }

    public function testConfigSet()
    {
        $config = Config::getInstance();

        $this->assertFalse($config->has('new-value'));

        $config->set('new-value', 'New Value');

        $this->assertTrue($config->has('new-value'));

        $this->assertEquals('New Value', $config->get('new-value'));

        $config->set('other.nested', 'Nested Value');

        $this->assertTrue($config->has('other.nested'));

        $this->assertEquals('Nested Value', $config->get('other.nested'));
    }

    public function testConfigDelete()
    {
        $config = Config::getInstance();

        $config->load(new Setup('config', 'config'));

        $this->assertNotNull($config->get('test'));

        $config->delete('test');

        $this->assertFalse($config->has('test'));

        $this->assertNull($config->get('test'));
    }

    public function testConfigFlush()
    {
        $config = Config::getInstance();

        $config->load(new Setup('config', 'config'));

        $this->assertNotEmpty($config->all());

        $config->flush();

        $this->assertEmpty($config->all());
    }

}
