<?php

namespace Quantum\Tests\Unit\Tracer;

use Quantum\Storage\Contracts\FilesystemAdapterInterface;
use Quantum\Storage\Factories\FileSystemFactory;
use Quantum\Tracer\StackTraceFormatter;
use Quantum\Tests\Unit\AppTestCase;
use Quantum\Tracer\ErrorHandler;
use Quantum\Storage\FileSystem;
use ReflectionProperty;
use Quantum\Di\Di;
use Exception;
use Mockery;

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
            $this->setPrivateProperty($throwable, 'file', $sourceFile);
            $this->setPrivateProperty($throwable, 'line', 3);
            $this->setPrivateProperty($throwable, 'trace', [
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
        $adapter = Mockery::mock(FilesystemAdapterInterface::class);

        $factory = Di::get(FileSystemFactory::class);
        $instancesProperty = new ReflectionProperty(FileSystemFactory::class, 'instances');
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
