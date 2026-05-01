<?php

namespace Quantum\Tests\Unit\Tracer;

use Quantum\Storage\Factories\FileSystemFactory;
use Quantum\Storage\Contracts\FilesystemAdapterInterface;
use Quantum\Storage\FileSystem;
use Quantum\Tests\Unit\AppTestCase;
use Quantum\Tracer\ErrorHandler;
use Quantum\Tracer\StackTraceFormatter;
use Quantum\Di\Di;
use Exception;

class StackTraceFormatterTest extends AppTestCase
{
    private StackTraceFormatter $formatter;

    public function setUp(): void
    {
        parent::setUp();

        $this->formatter = new StackTraceFormatter();
    }

    public function testComposeSkipsErrorHandlerClassEntriesAndBuildsCodeSnippets(): void
    {
        $sourceFile = PROJECT_ROOT . DS . 'shared' . DS . 'trace-source-formatter.php';
        $this->createFile($sourceFile, "<?php\nline1\nline2\nline3\nline4\nline5\n");

        try {
            $throwable = new Exception('trace');
            $throwableProperty = new \ReflectionProperty(Exception::class, 'file');
            $throwableProperty->setAccessible(true);
            $throwableProperty->setValue($throwable, $sourceFile);

            $lineProperty = new \ReflectionProperty(Exception::class, 'line');
            $lineProperty->setAccessible(true);
            $lineProperty->setValue($throwable, 3);

            $traceProperty = new \ReflectionProperty(Exception::class, 'trace');
            $traceProperty->setAccessible(true);
            $traceProperty->setValue($throwable, [
                ['class' => ErrorHandler::class, 'file' => $sourceFile, 'line' => 2],
                ['class' => 'ExternalClass', 'file' => $sourceFile, 'line' => 4],
                ['class' => 'NoFileClass'],
            ]);

            $trace = $this->formatter->compose($throwable);

            $this->assertCount(2, $trace);
            $this->assertSame($sourceFile, $trace[0]['file']);
            $this->assertStringContainsString('<ol start="1">', $trace[0]['code']);
            $this->assertStringContainsString('class="error-line"', $trace[0]['code']);
            $this->assertSame($sourceFile, $trace[1]['file']);
            $this->assertStringContainsString('class="switch-line"', $trace[1]['code']);
        } finally {
            $this->removeFile($sourceFile);
        }
    }

    public function testGetSourceCodeReturnsEmptyStringForNonLocalAdapter(): void
    {
        $adapter = new class () implements FilesystemAdapterInterface {
            public function makeDirectory(string $dirname, ?string $parentId = null): bool
            {
                return false;
            }
            public function removeDirectory(string $dirname): bool
            {
                return false;
            }
            public function get(string $filename)
            {
                return false;
            }
            public function put(string $filename, $content, ?string $parentId = null)
            {
                return false;
            }
            public function append(string $filename, $content)
            {
                return false;
            }
            public function rename(string $oldName, string $newName): bool
            {
                return false;
            }
            public function copy(string $source, string $dest): bool
            {
                return false;
            }
            public function exists(string $filename): bool
            {
                return false;
            }
            public function size(string $filename)
            {
                return false;
            }
            public function lastModified(string $filename)
            {
                return false;
            }
            public function remove(string $filename): bool
            {
                return false;
            }
            public function isFile(string $filename): bool
            {
                return false;
            }
            public function isDirectory(string $dirname): bool
            {
                return false;
            }
            public function listDirectory(string $dirname)
            {
                return false;
            }
        };

        $factory = Di::get(FileSystemFactory::class);
        $instancesProperty = new \ReflectionProperty(FileSystemFactory::class, 'instances');
        $instancesProperty->setAccessible(true);
        $originalInstances = $instancesProperty->getValue($factory);

        try {
            $instancesProperty->setValue($factory, ['local' => new FileSystem($adapter)]);

            $code = $this->formatter->getSourceCode(__FILE__, 10, 'error-line');

            $this->assertSame('', $code);
        } finally {
            $instancesProperty->setValue($factory, $originalInstances);
        }
    }
}
