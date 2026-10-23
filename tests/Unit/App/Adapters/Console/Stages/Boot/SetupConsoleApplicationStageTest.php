<?php

namespace Quantum\Tests\Unit\App\Adapters\Console\Stages\Boot;

use Quantum\App\Adapters\Console\Stages\Boot\SetupConsoleApplicationStage;
use Quantum\App\Stages\Boot\LoadHelpersStage;
use Symfony\Component\Console\Application;
use Quantum\Tests\Unit\AppTestCase;
use Quantum\App\Enums\AppType;
use Quantum\Di\Di;

class SetupConsoleApplicationStageTest extends AppTestCase
{
    public function setUp(): void
    {
        $this->context = $this->createContext(AppType::CONSOLE);

        (new LoadHelpersStage())->process($this->context);

        Di::register(Application::class);
    }

    public function tearDown(): void
    {
        config()->flush();
        $this->clearAppContext();
    }

    public function testSetupConsoleApplicationStageNamesTheApplicationFromTheConfig(): void
    {
        config()->set('app', ['name' => 'Qt Console', 'version' => '1.2.3']);

        (new SetupConsoleApplicationStage())->process($this->context);

        $this->assertSame('Qt Console', Di::get(Application::class)->getName());
        $this->assertSame('1.2.3', Di::get(Application::class)->getVersion());
    }

    public function testSetupConsoleApplicationStageNamesTheApplicationUnknownWhenTheConfigHasNoNameOrVersion(): void
    {
        config()->set('app', ['name' => null, 'version' => null]);

        (new SetupConsoleApplicationStage())->process($this->context);

        $this->assertSame('UNKNOWN', Di::get(Application::class)->getName());
        $this->assertSame('UNKNOWN', Di::get(Application::class)->getVersion());
    }

    public function testSetupConsoleApplicationStageKeepsAnEmptyNameAndVersion(): void
    {
        config()->set('app', ['name' => '', 'version' => '0']);

        (new SetupConsoleApplicationStage())->process($this->context);

        $this->assertSame('', Di::get(Application::class)->getName());
        $this->assertSame('0', Di::get(Application::class)->getVersion());
    }

    public function testSetupConsoleApplicationStageNamesAnApplicationThatIsAlreadyRegistered(): void
    {
        $application = new Application('Registered', '0.1');

        $this->context = $this->createContext(AppType::CONSOLE);
        (new LoadHelpersStage())->process($this->context);
        Di::set(Application::class, $application);

        config()->set('app', ['name' => 'Qt Console', 'version' => '1.2.3']);

        (new SetupConsoleApplicationStage())->process($this->context);

        $this->assertSame($application, Di::get(Application::class));
        $this->assertSame('Qt Console', $application->getName());
        $this->assertSame('1.2.3', $application->getVersion());
    }
}
