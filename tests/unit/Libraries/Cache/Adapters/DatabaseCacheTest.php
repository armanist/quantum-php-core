<?php

namespace Libraries\Cache\Adapters;

use Quantum\Libraries\Cache\Adapters\DatabaseCache;
use Quantum\Libraries\Database\Sleekdb\SleekDbal;
use PHPUnit\Framework\TestCase;
use Quantum\Loader\Setup;
use Quantum\Di\Di;
use Quantum\App;

/**
 *  @runTestsInSeparateProcesses
 */
class DatabaseCacheTest extends TestCase
{

    private $databaseCache;

    public function setUp(): void
    {
        App::loadCoreFunctions(dirname(__DIR__, 5) . DS . 'src' . DS . 'Helpers');

        App::setBaseDir(dirname(__DIR__, 3) . DS . '_root');

        Di::loadDefinitions();

        config()->flush();

        config()->import(new Setup('config', 'database'));

        config()->set('database.current', 'sleekdb');

        SleekDbal::connect(config()->get('database.sleekdb'));
        
        $params = [
            'table' => 'cache',
            'ttl' => 60
        ];

        $this->databaseCache = new DatabaseCache($params);
    }

    public function tearDown(): void
    {
        config()->flush();

        $this->databaseCache->clear();

        SleekDbal::disconnect();
    }

    public function testDatabaseCacheSetGetDelete()
    {
        
        $this->assertNull($this->databaseCache->get('test'));

        $this->assertNotNull($this->databaseCache->get('test', 'Some default value'));

        $this->assertEquals('Some default value', $this->databaseCache->get('test', 'Some default value'));

        $this->databaseCache->set('test', 'Test value');

        $this->assertNotNull($this->databaseCache->get('test'));

        $this->assertEquals('Test value', $this->databaseCache->get('test'));

        $this->databaseCache->delete('test');

        $this->assertNull($this->databaseCache->get('test'));
    }

    public function testFileCacheHas()
    {
        $this->assertFalse($this->databaseCache->has('test'));

        $this->databaseCache->set('test', 'Some value');

        $this->assertTrue($this->databaseCache->has('test'));
    }

    public function testFileCacheGetMultiple()
    {
        $cacheItems = $this->databaseCache->getMultiple(['test1', 'test2']);

        $this->assertIsArray($cacheItems);

        $this->assertArrayHasKey('test1', $cacheItems);

        $this->assertNull($cacheItems['test1']);

        $cacheItems = $this->databaseCache->getMultiple(['test1', 'test2'], 'Default value for all');

        $this->assertIsArray($cacheItems);

        $this->assertArrayHasKey('test1', $cacheItems);

        $this->assertNotNull($cacheItems['test1']);

        $this->assertEquals('Default value for all', $cacheItems['test1']);
    }

    public function testDatabaseCacheSetMultiple()
    {
        $this->assertFalse($this->databaseCache->has('test1'));

        $this->assertFalse($this->databaseCache->has('test2'));

        $this->databaseCache->setMultiple(['test1' => 'Test value one', 'test2' => 'Test value two']);

        $this->assertTrue($this->databaseCache->has('test1'));

        $this->assertEquals('Test value one', $this->databaseCache->get('test1'));

        $this->assertTrue($this->databaseCache->has('test2'));

        $this->assertEquals('Test value two', $this->databaseCache->get('test2'));
    }

    public function testDatabaseCacheDeleteMultiple()
    {
        $this->databaseCache->setMultiple(['test1' => 'Test value one', 'test2' => 'Test value two']);

        $this->assertTrue($this->databaseCache->has('test1'));

        $this->assertTrue($this->databaseCache->has('test2'));

        $this->databaseCache->deleteMultiple(['test1', 'test2']);

        $this->assertFalse($this->databaseCache->has('test1'));

        $this->assertFalse($this->databaseCache->has('test2'));
    }

    public function testDatabaseCacheClear()
    {
        $this->databaseCache->setMultiple(['test1' => 'Test value one', 'test2' => 'Test value two']);

        $this->assertTrue($this->databaseCache->has('test1'));

        $this->assertTrue($this->databaseCache->has('test2'));

        $this->databaseCache->clear();

        $this->assertFalse($this->databaseCache->has('test1'));

        $this->assertFalse($this->databaseCache->has('test2'));
    }

    public function testDatabaseCacheExpired()
    {
        $params = [
            'table' => 'cache',
            'ttl' => -1
        ];

        $databaseCache = new DatabaseCache($params);

        $databaseCache->set('test', 'Test value');

        $this->assertNull($databaseCache->get('test'));
    }

}
