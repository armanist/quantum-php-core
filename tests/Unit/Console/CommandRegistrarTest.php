<?php

namespace Quantum\Tests\Unit\Console;

use Quantum\Tests\_root\shared\Commands\TestCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Application;
use Quantum\Console\CommandRegistrar;
use Quantum\Tests\Unit\AppTestCase;

class CommandRegistrarTest extends AppTestCase
{
    private Application $application;

    private string $directory;

    private string $namespace = '\\Quantum\\Tests\\_root\\shared\\Commands\\';

    public function setUp(): void
    {
        parent::setUp();

        $this->application = new Application('Qt Console Application', 'test');

        $this->directory = base_dir() . DS . 'shared' . DS . 'Commands';
    }

    public function testCommandRegistrarAddsDiscoveredCommands(): void
    {
        $this->assertFalse($this->application->has('test:dummy'));

        CommandRegistrar::register($this->application, $this->directory, $this->namespace);

        $this->assertTrue($this->application->has('test:dummy'));
        $this->assertInstanceOf(TestCommand::class, $this->application->get('test:dummy'));
    }

    public function testCommandRegistrarIgnoresMissingDirectory(): void
    {
        $before = array_keys($this->application->all());

        CommandRegistrar::register($this->application, $this->directory . DS . 'missing', $this->namespace);

        $this->assertSame($before, array_keys($this->application->all()));
    }

    public function testCommandRegistrarIgnoresClassesOutsideTheNamespace(): void
    {
        $before = array_keys($this->application->all());

        CommandRegistrar::register($this->application, $this->directory, '\\Some\\Other\\Namespace\\');

        $this->assertSame($before, array_keys($this->application->all()));
    }

    public function testCommandRegistrarReplacesAnExistingCommandWithTheSameName(): void
    {
        $this->application->add(new Command('test:dummy'));

        $this->assertNotInstanceOf(TestCommand::class, $this->application->get('test:dummy'));

        CommandRegistrar::register($this->application, $this->directory, $this->namespace);

        $this->assertInstanceOf(TestCommand::class, $this->application->get('test:dummy'));
    }
}
