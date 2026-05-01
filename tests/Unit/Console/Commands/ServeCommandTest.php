<?php

namespace Quantum\Tests\Unit\Console\Commands;

use Symfony\Component\Console\Tester\CommandTester;
use Quantum\Console\Commands\ServeCommand;
use Quantum\Tests\Unit\AppTestCase;
use RuntimeException;

class ServeCommandTest extends AppTestCase
{
    private ServeCommand $command;

    public function setUp(): void
    {
        parent::setUp();

        $this->command = new ServeCommand();
    }

    public function testCommandMetadata(): void
    {
        $this->assertSame('serve', $this->command->getName());
        $this->assertSame('Serves the application on the PHP development server', $this->command->getDescription());
    }

    public function testCommandOptionsAreRegistered(): void
    {
        $definition = $this->command->getDefinition();

        $this->assertTrue($definition->hasOption('host'));
        $this->assertTrue($definition->hasOption('port'));
        $this->assertTrue($definition->hasOption('open'));
    }

    public function testBrowserCommandReturnsArrayForKnownPlatform(): void
    {
        $method = new \ReflectionMethod($this->command, 'browserCommand');
        $method->setAccessible(true);

        $result = $method->invoke($this->command, 'http://localhost:8000');

        if (in_array(PHP_OS_FAMILY, ['Windows', 'Linux', 'Darwin'], true)) {
            $this->assertIsArray($result);
            $this->assertCount(2, $result);
            $this->assertSame('http://localhost:8000', $result[1]);
        } else {
            $this->assertNull($result);
        }
    }

    public function testExecUsesResolvedHostAndPortFlow(): void
    {
        $command = new class () extends ServeCommand {
            public string $receivedHost = '';
            public int $receivedPort = 0;

            /** @var array<string, mixed> */
            public array $receivedServerData = [];

            protected function startServerOnAvailablePort(string $host, int $startPort): array
            {
                $this->receivedHost = $host;
                $this->receivedPort = $startPort;

                return [
                    'process' => fopen('php://memory', 'r'),
                    'port' => $startPort,
                    'url' => "http://{$host}:{$startPort}",
                ];
            }

            protected function handleServerExecution(array $serverData): void
            {
                $this->receivedServerData = $serverData;
            }
        };

        $tester = new CommandTester($command);
        $tester->execute([
            '--host' => '127.0.0.1',
            '--port' => '8011',
        ]);

        $this->assertSame('127.0.0.1', $command->receivedHost);
        $this->assertSame(8011, $command->receivedPort);
        $this->assertSame('http://127.0.0.1:8011', $command->receivedServerData['url']);
    }

    public function testPortThrowsForInvalidRange(): void
    {
        $command = new ServeCommand();

        $tester = new CommandTester($command);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Port must be between 1 and 65535');
        $tester->execute(['--port' => '70000']);
    }

    public function testStartServerOnAvailablePortSkipsBusyPort(): void
    {
        $command = new class () extends ServeCommand {
            public int $cleanupCalls = 0;
            protected int $maxPortScan = 3;
            /** @var array<int> */
            public array $checkedPorts = [];

            public function info(string $message): void
            {
            }

            protected function isPortInUse(string $host, int $port): bool
            {
                $this->checkedPorts[] = $port;
                return $port === 8000;
            }

            protected function startPhpServer(string $host, int $port)
            {
                return fopen('php://memory', 'r');
            }

            protected function waitUntilServerIsReady(string $host, int $port, $process): void
            {
            }

            protected function cleanupProcess($process): void
            {
                $this->cleanupCalls++;
            }

            public function exposeStartServerOnAvailablePort(string $host, int $port): array
            {
                return $this->startServerOnAvailablePort($host, $port);
            }
        };

        $serverData = $command->exposeStartServerOnAvailablePort('127.0.0.1', 8000);

        $this->assertSame([8000, 8001], $command->checkedPorts);
        $this->assertSame(8001, $serverData['port']);
        $this->assertSame('http://127.0.0.1:8001', $serverData['url']);
        $this->assertSame(0, $command->cleanupCalls);
    }

    public function testStartServerOnAvailablePortCleansUpWhenServerReadinessFails(): void
    {
        $command = new class () extends ServeCommand {
            public int $cleanupCalls = 0;
            protected int $maxPortScan = 2;
            /** @var array<int> */
            private array $attemptedPorts = [];

            public function info(string $message): void
            {
            }

            protected function isPortInUse(string $host, int $port): bool
            {
                return false;
            }

            protected function startPhpServer(string $host, int $port)
            {
                $this->attemptedPorts[] = $port;
                return fopen('php://memory', 'r');
            }

            protected function waitUntilServerIsReady(string $host, int $port, $process): void
            {
                throw new RuntimeException('not ready');
            }

            protected function cleanupProcess($process): void
            {
                $this->cleanupCalls++;
            }

            public function exposeStartServerOnAvailablePort(string $host, int $port): array
            {
                return $this->startServerOnAvailablePort($host, $port);
            }
        };

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unable to start PHP server on any available port.');

        try {
            $command->exposeStartServerOnAvailablePort('127.0.0.1', 8000);
        } finally {
            $this->assertSame(2, $command->cleanupCalls);
        }
    }

    public function testHandleServerExecutionOpensBrowserWhenOptionEnabled(): void
    {
        $command = new class () extends ServeCommand {
            public bool $openCalled = false;
            public bool $waitCalled = false;

            protected function startServerOnAvailablePort(string $host, int $startPort): array
            {
                return [
                    'process' => fopen('php://memory', 'r'),
                    'port' => $startPort,
                    'url' => "http://{$host}:{$startPort}",
                ];
            }

            protected function openBrowser(string $url): void
            {
                $this->openCalled = true;
            }

            protected function waitForProcess($process): void
            {
                $this->waitCalled = true;
            }
        };

        $tester = new CommandTester($command);
        $tester->execute(['--open' => true]);

        $this->assertTrue($command->openCalled);
        $this->assertTrue($command->waitCalled);
    }

    public function testIsPortInUseDetectsListeningSocket(): void
    {
        $command = new class () extends ServeCommand {
            public function exposeIsPortInUse(string $host, int $port): bool
            {
                return $this->isPortInUse($host, $port);
            }
        };

        $server = stream_socket_server('tcp://127.0.0.1:0', $errno, $errstr);
        $this->assertIsResource($server);
        $name = stream_socket_get_name($server, false);
        $port = (int) substr((string) $name, strrpos((string) $name, ':') + 1);

        try {
            $this->assertTrue($command->exposeIsPortInUse('127.0.0.1', $port));
        } finally {
            fclose($server);
        }

        $this->assertFalse($command->exposeIsPortInUse('127.0.0.1', $port));
    }

    public function testWaitForProcessClosesCompletedProcess(): void
    {
        $command = new class () extends ServeCommand {
            public function exposeWaitForProcess($process): void
            {
                $this->waitForProcess($process);
            }
        };

        $process = proc_open([PHP_BINARY, '-r', 'usleep(100000);'], [0 => STDIN, 1 => STDOUT, 2 => STDERR], $pipes);
        $this->assertIsResource($process);

        $command->exposeWaitForProcess($process);
        $this->assertFalse(is_resource($process));
    }

    public function testStartPhpServerAndWaitUntilReady(): void
    {
        $command = new class () extends ServeCommand {
            public function exposeStartPhpServer(string $host, int $port)
            {
                return $this->startPhpServer($host, $port);
            }

            public function exposeWaitUntilServerIsReady(string $host, int $port, $process): void
            {
                $this->waitUntilServerIsReady($host, $port, $process);
            }

            public function exposeCleanupProcess($process): void
            {
                $this->cleanupProcess($process);
            }

            protected function serverProcessDescriptors(): array
            {
                return [
                    0 => ['pipe', 'r'],
                    1 => ['pipe', 'w'],
                    2 => ['pipe', 'w'],
                ];
            }
        };

        $tempRoot = base_dir() . DS . 'var' . DS . 'tmp_serve_' . uniqid('', true);
        $publicDir = $tempRoot . DS . 'public';
        $indexFile = $publicDir . DS . 'index.php';
        $originalCwd = getcwd();

        mkdir($publicDir, 0777, true);
        file_put_contents($indexFile, "<?php echo 'ok';");

        $socket = stream_socket_server('tcp://127.0.0.1:0', $errno, $errstr);
        $name = stream_socket_get_name($socket, false);
        $port = (int) substr((string) $name, strrpos((string) $name, ':') + 1);
        fclose($socket);

        try {
            $this->assertTrue(chdir($tempRoot));
            $process = $command->exposeStartPhpServer('127.0.0.1', $port);
            $this->assertIsResource($process);
            $command->exposeWaitUntilServerIsReady('127.0.0.1', $port, $process);
            $this->assertTrue(true);
        } finally {
            if (isset($process) && is_resource($process)) {
                proc_terminate($process);
            }
            if (isset($process)) {
                $command->exposeCleanupProcess($process);
            }
            chdir($originalCwd);
            @unlink($indexFile);
            @rmdir($publicDir);
            @rmdir($tempRoot);
        }
    }

    public function testOpenBrowserReturnsEarlyWhenCommandIsNotSupported(): void
    {
        $command = new class () extends ServeCommand {
            protected function browserCommand(string $url): ?array
            {
                return null;
            }

            public function exposeOpenBrowser(string $url): void
            {
                $this->openBrowser($url);
            }
        };

        $command->exposeOpenBrowser('http://127.0.0.1:8000');
        $this->assertTrue(true);
    }
}
