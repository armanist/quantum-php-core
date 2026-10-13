<?php

namespace Quantum\Tests\Unit\App\Adapters\Console;

use Quantum\App\Adapters\Console\ConsoleAppAdapter;
use Symfony\Component\Console\Application;
use Quantum\Environment\Environment;
use Quantum\Tests\Unit\AppTestCase;
use Quantum\Tracer\ErrorHandler;
use Quantum\Di\Di;
use Exception;
use Mockery;

class ConsoleAppAdapterTest extends AppTestCase
{
    private $consoleAppAdapter;

    public function setUp(): void
    {
        $applicationMock = Mockery::mock(Application::class)->makePartial();
        $applicationMock->shouldReceive('getName')->andReturn('Qt Console Application');
        $applicationMock->shouldReceive('run')->andReturn(0);

        $this->consoleAppAdapter = Mockery::mock(ConsoleAppAdapter::class)
            ->shouldAllowMockingProtectedMethods()
            ->makePartial();

        $this->consoleAppAdapter
            ->shouldReceive('createApplication')
            ->andReturn($applicationMock);
    }

    public function tearDown(): void
    {
        config()->flush();
        $this->clearAppContext();
    }

    public function testConsoleAppAdapterStartSuccessfully(): void
    {
        $_SERVER['argv'] = ['qt', 'list', '--quiet'];

        $this->consoleAppAdapter->__construct($this->createContext());

        $result = $this->consoleAppAdapter->start();

        $this->assertEquals(0, $result);
    }

    public function testConsoleAppAdapterReturnsNonZeroExitCode(): void
    {
        $_SERVER['argv'] = ['qt', 'list', '--quiet'];

        $applicationMock = Mockery::mock(Application::class)->makePartial();
        $applicationMock->shouldReceive('getName')->andReturn('Qt Console Application');
        $applicationMock->shouldReceive('run')->andReturn(2);

        $consoleAppAdapter = Mockery::mock(ConsoleAppAdapter::class)
            ->shouldAllowMockingProtectedMethods()
            ->makePartial();

        $consoleAppAdapter
            ->shouldReceive('createApplication')
            ->andReturn($applicationMock);

        $consoleAppAdapter->__construct($this->createContext());

        $result = $consoleAppAdapter->start();

        $this->assertEquals(2, $result);
    }

    public function testConsoleAppAdapterStartFails(): void
    {
        $_SERVER['argv'] = ['qt', 'unknown', '--quiet'];

        $this->consoleAppAdapter->__construct($this->createContext());

        $this->expectException(Exception::class);

        $this->consoleAppAdapter->start();
    }

    public function testConsoleAppAdapterStartFailsWithoutCommandName(): void
    {
        $_SERVER['argv'] = ['qt'];

        $this->consoleAppAdapter->__construct($this->createContext());

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Command `` is not defined');

        $this->consoleAppAdapter->start();
    }

    public function testConsoleAppAdapterStartRegistersCoreCommands(): void
    {
        $directory = PROJECT_ROOT . DS . 'vendor' . DS . 'quantum' . DS . 'framework' . DS . 'src' . DS . 'Console' . DS . 'Commands';

        $this->withTemporaryCommand($directory, 'Quantum\Console\Commands', function (string $commandName): void {
            $this->assertSame(0, $this->startWithRealApplication($commandName));
            $this->assertSame($commandName, $GLOBALS['tmpCommandRan'] ?? null);
        });
    }

    public function testConsoleAppAdapterStartRegistersAppCommands(): void
    {
        $directory = PROJECT_ROOT . DS . 'shared' . DS . 'Commands';

        $this->withTemporaryCommand($directory, 'Shared\Commands', function (string $commandName): void {
            $this->assertSame(0, $this->startWithRealApplication($commandName));
            $this->assertSame($commandName, $GLOBALS['tmpCommandRan'] ?? null);
        });
    }

    public function testConsoleAppAdapterStartRegistersCoreCommandsBeforeAppCommands(): void
    {
        $coreDirectory = PROJECT_ROOT . DS . 'vendor' . DS . 'quantum' . DS . 'framework' . DS . 'src' . DS . 'Console' . DS . 'Commands';
        $appDirectory = PROJECT_ROOT . DS . 'shared' . DS . 'Commands';

        $this->withTemporaryCommand($coreDirectory, 'Quantum\Console\Commands', function (string $coreCommand) use ($appDirectory): void {
            $this->withTemporaryCommand($appDirectory, 'Shared\Commands', function (string $appCommand) use ($coreCommand): void {
                $application = new Application('Qt Console Application', 'test');
                $application->setAutoExit(false);
                $application->setCatchExceptions(false);

                $adapter = Mockery::mock(ConsoleAppAdapter::class)
                    ->shouldAllowMockingProtectedMethods()
                    ->makePartial();

                $adapter->shouldReceive('createApplication')->andReturn($application);

                $_SERVER['argv'] = ['qt', 'list', '--quiet'];

                $adapter->__construct($this->createContext());
                $adapter->start();

                $names = array_keys($application->all());

                $this->assertContains($coreCommand, $names);
                $this->assertContains($appCommand, $names);
                $this->assertLessThan(array_search($appCommand, $names, true), array_search($coreCommand, $names, true));
            });
        });
    }

    public function testConsoleAppAdapterStartLetsAppCommandOverrideCoreCommandWithSameName(): void
    {
        $coreDirectory = PROJECT_ROOT . DS . 'vendor' . DS . 'quantum' . DS . 'framework' . DS . 'src' . DS . 'Console' . DS . 'Commands';
        $appDirectory = PROJECT_ROOT . DS . 'shared' . DS . 'Commands';

        $this->withTemporaryCommand($coreDirectory, 'Quantum\Console\Commands', function (string $sharedName) use ($appDirectory): void {
            $this->withTemporaryCommand($appDirectory, 'Shared\Commands', function (string $commandName): void {
                $this->assertSame(0, $this->startWithRealApplication($commandName));
                $this->assertSame('Shared\Commands', $GLOBALS['tmpCommandSource'] ?? null);
            }, $sharedName);
        });
    }

    public function testConsoleAppAdapterStartWorksWithoutAppCommandsDirectory(): void
    {
        $directory = PROJECT_ROOT . DS . 'shared' . DS . 'Commands';
        $hidden = PROJECT_ROOT . DS . 'shared' . DS . 'Commands_hidden';

        $this->assertTrue(rename($directory, $hidden));

        try {
            $this->assertSame(0, $this->startWithRealApplication('list'));
        } finally {
            rename($hidden, $directory);
        }
    }

    public function testConsoleAppAdapterStartRegistersOnlyInstantiableCommands(): void
    {
        $directory = PROJECT_ROOT . DS . 'shared' . DS . 'Commands';

        $before = $this->registeredCommandNames();

        $plain = 'TmpPlain' . substr(md5(uniqid('', true)), 0, 8);
        $abstract = 'TmpAbstract' . substr(md5(uniqid('', true)), 0, 8);

        $plainCode = "<?php\n\nnamespace Shared\\Commands;\n\nclass {$plain}\n{\n}\n";
        $abstractCode = "<?php\n\nnamespace Shared\\Commands;\n\nabstract class {$abstract} extends \\Quantum\\Console\\CliCommand\n{\n}\n";

        $this->withTemporaryFile($directory, $plain, $plainCode, function () use ($directory, $abstract, $abstractCode, $before): void {
            $this->withTemporaryFile($directory, $abstract, $abstractCode, function () use ($before): void {
                $this->assertSame($before, $this->registeredCommandNames());
            });
        });
    }

    public function testConsoleAppAdapterBootsAllStagesForRegularCommands(): void
    {
        $_SERVER['argv'] = ['qt', 'list', '--quiet'];

        $this->consoleAppAdapter->__construct($this->createContext());

        $this->assertTrue(Di::has(Environment::class));
        $this->assertTrue(Di::isRegistered(ErrorHandler::class));
        $this->assertTrue(config()->has('app'));
    }

    public function testConsoleAppAdapterBootsReducedStagesForCoreEnvCommand(): void
    {
        $_SERVER['argv'] = ['qt', 'core:env'];

        $this->consoleAppAdapter->__construct($this->createContext());

        $this->assertFalse(Di::has(Environment::class));
        $this->assertFalse(Di::isRegistered(ErrorHandler::class));
        $this->assertFalse(config()->has('app'));
    }

    /**
     * Starts the adapter with a real Symfony Application (no auto exit, no exception catching),
     * so the registered command is really executed and its exit code comes from Symfony.
     */
    private function startWithRealApplication(string $commandName): ?int
    {
        $application = new Application('Qt Console Application', 'test');
        $application->setAutoExit(false);
        $application->setCatchExceptions(false);

        $adapter = Mockery::mock(ConsoleAppAdapter::class)
            ->shouldAllowMockingProtectedMethods()
            ->makePartial();

        $adapter->shouldReceive('createApplication')->andReturn($application);

        $_SERVER['argv'] = ['qt', $commandName, '--quiet'];

        $adapter->__construct($this->createContext());

        return $adapter->start();
    }

    /**
     * Returns the names of the commands that start() leaves registered on a real Symfony Application.
     * @return array<int, string>
     */
    private function registeredCommandNames(): array
    {
        $application = new Application('Qt Console Application', 'test');
        $application->setAutoExit(false);
        $application->setCatchExceptions(false);

        $adapter = Mockery::mock(ConsoleAppAdapter::class)
            ->shouldAllowMockingProtectedMethods()
            ->makePartial();

        $adapter->shouldReceive('createApplication')->andReturn($application);

        $_SERVER['argv'] = ['qt', 'list', '--quiet'];

        $adapter->__construct($this->createContext());
        $adapter->start();

        return array_keys($application->all());
    }

    /**
     * Creates a temporary command class in the given directory, loads it, runs the test
     * with the command name, and removes the file and any directories it had to create.
     * The command records that it ran, and from which namespace, in $GLOBALS['tmpCommandRan'] and
     * $GLOBALS['tmpCommandSource']. A fixed command name creates two commands with the same name.
     */
    private function withTemporaryCommand(string $directory, string $namespace, callable $test, ?string $name = null): void
    {
        $suffix = substr(md5(uniqid('', true)), 0, 8);
        $className = 'TmpCommand' . $suffix;
        $commandName = $name ?? 'tmp:command' . $suffix;

        $command = <<<PHP
<?php

namespace {$namespace};

use Quantum\Console\CliCommand;

class {$className} extends CliCommand
{
    protected ?string \$name = '{$commandName}';
    protected ?string \$description = 'Temporary command';
    protected ?string \$help = 'Created by a test';

    public function exec(): void
    {
        \$GLOBALS['tmpCommandRan'] = '{$commandName}';
        \$GLOBALS['tmpCommandSource'] = '{$namespace}';
    }
}
PHP;

        $this->withTemporaryFile($directory, $className, $command, function () use ($test, $commandName): void {
            $test($commandName);
        });
    }

    /**
     * Writes a temporary PHP file named after the class into the directory, loads it, runs the test,
     * and removes the file and any directories it had to create.
     */
    private function withTemporaryFile(string $directory, string $className, string $code, callable $test): void
    {
        $file = $directory . DS . $className . '.php';

        $missing = [];

        for ($dir = $directory; !is_dir($dir); $dir = dirname($dir)) {
            array_unshift($missing, $dir);
        }

        try {
            if ($missing !== []) {
                $this->assertTrue(mkdir($directory, 0777, true));
            }

            $this->assertNotFalse(file_put_contents($file, $code));

            require_once $file;

            $test();
        } finally {
            if (is_file($file)) {
                unlink($file);
            }

            foreach (array_reverse($missing) as $dir) {
                if (is_dir($dir)) {
                    rmdir($dir);
                }
            }

            unset($GLOBALS['tmpCommandRan'], $GLOBALS['tmpCommandSource']);
        }
    }
}
