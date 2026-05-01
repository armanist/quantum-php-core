<?php

namespace Quantum\Tests\Unit\Console\Commands;

use Quantum\Console\Commands\ResourceCacheClearCommand;
use Symfony\Component\Console\Tester\CommandTester;
use Quantum\Tests\Unit\AppTestCase;
use Quantum\Storage\FileSystem;
use ReflectionMethod;

class ResourceCacheClearCommandTest extends AppTestCase
{
    private ResourceCacheClearCommand $command;

    private CommandTester $tester;

    public function setUp(): void
    {
        parent::setUp();

        $this->command = new ResourceCacheClearCommand();
        $this->tester = new CommandTester($this->command);
    }

    public function testCommandMetadata(): void
    {
        $this->assertSame('cache:clear', $this->command->getName());
        $this->assertSame('Clears resource cache', $this->command->getDescription());
        $this->assertSame('The command will clear the resource cache', $this->command->getHelp());
    }

    public function testCommandOptionsAreRegistered(): void
    {
        $definition = $this->command->getDefinition();

        $this->assertTrue($definition->hasOption('all'));
        $this->assertTrue($definition->hasOption('type'));
        $this->assertTrue($definition->hasOption('module'));
    }

    public function testConstructorInitializesFileSystem(): void
    {
        $fs = $this->getPrivateProperty($this->command, 'fs');
        $this->assertInstanceOf(FileSystem::class, $fs);
    }

    public function testExecShowsErrorWhenNoOptionsProvided(): void
    {
        config()->set('view_cache', ['cache_dir' => 'cache']);

        $cacheDir = base_dir() . DS . 'cache';
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0777, true);
        }

        $this->tester->execute([]);

        $output = $this->tester->getDisplay();
        $this->assertStringContainsString('Please specify at least one of the following options', $output);

        @rmdir($cacheDir);
    }

    public function testInitTypeAcceptsKnownType(): void
    {
        $method = new ReflectionMethod($this->command, 'initType');
        $method->setAccessible(true);
        $method->invoke($this->command, 'views');

        $this->assertSame('views', $this->getPrivateProperty($this->command, 'type'));
    }

    public function testInitTypeThrowsForInvalidType(): void
    {
        $method = new ReflectionMethod($this->command, 'initType');
        $method->setAccessible(true);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Cache type {invalid} is invalid.');
        $method->invoke($this->command, 'invalid');
    }

    public function testInitModuleAcceptsKnownModule(): void
    {
        $this->setPrivateProperty($this->command, 'modules', ['blog', 'shop']);

        $method = new ReflectionMethod($this->command, 'initModule');
        $method->setAccessible(true);
        $method->invoke($this->command, 'BLOG');

        $this->assertSame('blog', $this->getPrivateProperty($this->command, 'module'));
    }

    public function testClearResourceModuleAndTypeRemovesMatchedFiles(): void
    {
        config()->set('view_cache', ['cache_dir' => 'cache']);

        $cacheDir = base_dir() . DS . 'var' . DS . 'tmp_cache_' . uniqid();
        $moduleDir = $cacheDir . DS . 'views' . DS . 'blog';
        mkdir($moduleDir, 0777, true);
        file_put_contents($moduleDir . DS . 'view.php', 'cached');

        $this->setPrivateProperty($this->command, 'cacheDir', $cacheDir);

        $method = new ReflectionMethod($this->command, 'clearResourceModuleAndType');
        $method->setAccessible(true);
        $method->invoke($this->command, 'blog', 'views');

        $this->assertFileDoesNotExist($moduleDir . DS . 'view.php');

        @rmdir($moduleDir);
        @rmdir($cacheDir . DS . 'views');
        @rmdir($cacheDir);
    }

    public function testExecClearsAllCacheWhenAllOptionProvided(): void
    {
        config()->set('view_cache', ['cache_dir' => 'tmp_cache_' . uniqid()]);

        $cacheDir = base_dir() . DS . config()->get('view_cache.cache_dir');
        mkdir($cacheDir, 0777, true);
        file_put_contents($cacheDir . DS . 'a.cache', 'value');

        $this->tester->execute(['--all' => true]);

        $this->assertFileDoesNotExist($cacheDir . DS . 'a.cache');
        $this->assertStringContainsString('Resource cache cleared successfully.', $this->tester->getDisplay());

        @rmdir($cacheDir);
    }
}
