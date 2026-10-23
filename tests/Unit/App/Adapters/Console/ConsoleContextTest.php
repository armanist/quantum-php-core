<?php

namespace Quantum\Tests\Unit\App\Adapters\Console;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Application;
use Quantum\Tests\Unit\AppTestCase;
use Quantum\App\Adapters\Console\ConsoleContext;
use Quantum\App\Enums\ExitCode;
use Quantum\App\Enums\AppType;
use Quantum\App\AppContext;
use Quantum\Di\DiContainer;
use Quantum\App\App;
use Quantum\Di\Di;

class ConsoleContextTest extends AppTestCase
{
    private AppContext $appContext;

    private Application $application;

    private ArrayInput $input;

    private BufferedOutput $output;

    private ConsoleContext $consoleContext;

    public function setUp(): void
    {
        parent::setUp();

        $this->appContext = App::getContext();
        $this->application = new Application();
        $this->input = new ArrayInput([]);
        $this->output = new BufferedOutput();

        Di::set(Application::class, $this->application);
        Di::set(InputInterface::class, $this->input);
        Di::set(OutputInterface::class, $this->output);

        $this->consoleContext = new ConsoleContext($this->appContext);
    }

    public function testConsoleContextIsAnAppContextBuiltFromTheOneItIsGiven(): void
    {
        $container = new DiContainer();

        $consoleContext = new ConsoleContext(new AppContext('/base/dir', $container, AppType::CONSOLE));

        $this->assertInstanceOf(AppContext::class, $consoleContext);
        $this->assertSame('/base/dir', $consoleContext->getBaseDir());
        $this->assertSame($container, $consoleContext->getContainer());
        $this->assertSame(AppType::CONSOLE, $consoleContext->getType());
    }

    public function testConsoleContextExposesApplicationInputAndOutputFromTheContainer(): void
    {
        $this->assertSame($this->application, $this->consoleContext->getApplication());
        $this->assertSame($this->input, $this->consoleContext->getInput());
        $this->assertSame($this->output, $this->consoleContext->getOutput());
    }

    public function testConsoleContextReadsTheConsoleObjectsFromTheContainerWhenAsked(): void
    {
        $context = new AppContext(PROJECT_ROOT, new \Quantum\Di\DiContainer(), AppType::CONSOLE);
        $consoleContext = new ConsoleContext($context);

        $application = new Application('Late', '1.0');
        $context->getContainer()->set(Application::class, $application);

        $this->assertSame($application, $consoleContext->getApplication());
    }

    public function testConsoleContextDefaultsExitCodeToSuccess(): void
    {
        $this->assertSame(ExitCode::SUCCESS, $this->consoleContext->getExitCode());
    }

    public function testConsoleContextStoresExitCode(): void
    {
        $this->consoleContext->setExitCode(ExitCode::INVALID);

        $this->assertSame(ExitCode::INVALID, $this->consoleContext->getExitCode());
    }
}
