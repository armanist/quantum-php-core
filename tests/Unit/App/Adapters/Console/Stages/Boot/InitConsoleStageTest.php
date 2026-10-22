<?php

namespace Quantum\Tests\Unit\App\Adapters\Console\Stages\Boot;

use Quantum\App\Adapters\Console\Stages\Boot\InitConsoleStage;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Application;
use Quantum\Tests\Unit\AppTestCase;
use Quantum\App\Enums\AppType;
use Quantum\Di\Di;

class InitConsoleStageTest extends AppTestCase
{
    public function setUp(): void
    {
        $this->context = $this->createContext(AppType::CONSOLE);
    }

    public function tearDown(): void
    {
        $this->clearAppContext();
    }

    public function testInitConsoleStageRegistersTheApplicationNamedUnknown(): void
    {
        (new InitConsoleStage())->process($this->context);

        $application = Di::get(Application::class);

        $this->assertInstanceOf(Application::class, $application);
        $this->assertSame('UNKNOWN', $application->getName());
        $this->assertSame('UNKNOWN', $application->getVersion());
    }

    public function testInitConsoleStageRegistersTheApplicationThatTakesTheNameGivenOnTheFirstFetch(): void
    {
        (new InitConsoleStage())->process($this->context);

        $application = Di::get(Application::class, ['Qt Console', '1.2.3']);

        $this->assertSame('Qt Console', $application->getName());
        $this->assertSame('1.2.3', $application->getVersion());
        $this->assertSame($application, Di::get(Application::class));
    }

    public function testInitConsoleStageRegistersTheConsoleOutput(): void
    {
        (new InitConsoleStage())->process($this->context);

        $this->assertInstanceOf(ConsoleOutput::class, Di::get(OutputInterface::class));
    }

    public function testInitConsoleStageRegistersAnInputThatReadsTheCommandLine(): void
    {
        $argv = $_SERVER['argv'] ?? null;

        $_SERVER['argv'] = ['qt', 'core:env', '--yes'];

        try {
            (new InitConsoleStage())->process($this->context);

            $input = Di::get(InputInterface::class);

            $this->assertInstanceOf(ArgvInput::class, $input);
            $this->assertSame('core:env', $input->getFirstArgument());
            $this->assertTrue($input->hasParameterOption('--yes'));
        } finally {
            $_SERVER['argv'] = $argv;
        }
    }

    public function testInitConsoleStageKeepsTheObjectsThatAreAlreadyRegistered(): void
    {
        $application = new Application('Test', '0.1');
        $input = new ArrayInput([]);
        $output = new BufferedOutput();

        Di::set(Application::class, $application);
        Di::set(InputInterface::class, $input);
        Di::set(OutputInterface::class, $output);

        (new InitConsoleStage())->process($this->context);

        $this->assertSame($application, Di::get(Application::class));
        $this->assertSame($input, Di::get(InputInterface::class));
        $this->assertSame($output, Di::get(OutputInterface::class));
    }
}
