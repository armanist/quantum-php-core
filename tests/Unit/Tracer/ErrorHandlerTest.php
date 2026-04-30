<?php

namespace Quantum\Tests\Unit\Tracer;

use Quantum\Storage\Factories\FileSystemFactory;
use Quantum\Storage\Contracts\FilesystemAdapterInterface;
use Quantum\Storage\FileSystem;
use Quantum\Tests\Unit\AppTestCase;
use Quantum\Tracer\ErrorHandler;
use Quantum\Di\Di;
use Quantum\Logger\Logger;
use ReflectionException;
use ReflectionMethod;
use ErrorException;
use ParseError;
use Exception;
use Mockery;

class ErrorHandlerTest extends AppTestCase
{
    private ErrorHandler $errorHandler;

    public function setUp(): void
    {
        parent::setUp();

        $this->errorHandler = new ErrorHandler();
    }

    public function tearDown(): void
    {
        restore_error_handler();
        restore_exception_handler();
        parent::tearDown();
    }

    public function testSetupRegistersErrorHandler(): void
    {
        $logger = Mockery::mock(Logger::class);

        $this->errorHandler->setup($logger);

        $currentHandler = set_error_handler(function () {
        });
        restore_error_handler();

        $this->assertNotNull($currentHandler);
        $this->assertIsArray($currentHandler);
        $this->assertInstanceOf(ErrorHandler::class, $currentHandler[0]);
        $this->assertEquals('handleError', $currentHandler[1]);
    }

    public function testSetupRegistersExceptionHandler(): void
    {
        $logger = Mockery::mock(Logger::class);

        $this->errorHandler->setup($logger);

        $currentHandler = set_exception_handler(function () {
        });
        restore_exception_handler();

        $this->assertNotNull($currentHandler);
        $this->assertIsArray($currentHandler);
        $this->assertInstanceOf(ErrorHandler::class, $currentHandler[0]);
        $this->assertEquals('handleException', $currentHandler[1]);
    }

    public function testHandleErrorThrowsErrorException(): void
    {
        $oldLevel = error_reporting(E_ALL);

        try {
            $this->errorHandler->handleError(E_WARNING, 'Test error', __FILE__, __LINE__);
            $this->fail('Expected ErrorException was not thrown');
        } catch (ErrorException $e) {
            $this->assertEquals('Test error', $e->getMessage());
            $this->assertEquals(E_WARNING, $e->getSeverity());
        } finally {
            error_reporting($oldLevel);
        }
    }

    public function testHandleErrorReturnsFalseForSuppressedErrors(): void
    {
        $oldLevel = error_reporting(0);

        try {
            $result = $this->errorHandler->handleError(E_NOTICE, 'Suppressed', __FILE__, __LINE__);
            $this->assertFalse($result);
        } finally {
            error_reporting($oldLevel);
        }
    }

    public function testErrorTypesConstant(): void
    {
        $this->assertEquals('error', ErrorHandler::ERROR_TYPES[E_ERROR]);
        $this->assertEquals('warning', ErrorHandler::ERROR_TYPES[E_WARNING]);
        $this->assertEquals('notice', ErrorHandler::ERROR_TYPES[E_NOTICE]);
        $this->assertEquals('error', ErrorHandler::ERROR_TYPES[E_PARSE]);
    }

    public function testHandleExceptionCliWritesExceptionMessage(): void
    {
        $this->expectNotToPerformAssertions();
        $this->errorHandler->handleException(new Exception('CLI failure'));
    }

    public function testHandleWebExceptionInDebugModeRendersTraceView(): void
    {
        config()->set('app.debug', true);

        $exception = new Exception('Web debug failure');

        ob_start();
        $this->invokePrivateMethod('handleWebException', [$exception]);
        ob_get_clean();

        $content = response()->getContent();
        $this->assertStringContainsString('TRACE VIEW', $content);
        $this->assertStringContainsString('Error :: Web debug failure', $content);
    }

    public function testHandleWebExceptionInProductionModeLogsAndRenders500(): void
    {
        config()->set('app.debug', false);

        $logger = Mockery::mock(Logger::class);
        $logger->shouldReceive('warning')
            ->once()
            ->with('Web production warning', Mockery::on(function (array $context): bool {
                return array_key_exists('trace', $context) && is_string($context['trace']);
            }));

        $this->setPrivateProperty($this->errorHandler, 'logger', $logger);

        $exception = new ErrorException('Web production warning', 0, E_WARNING, __FILE__, __LINE__);

        ob_start();
        $this->invokePrivateMethod('handleWebException', [$exception]);
        ob_get_clean();

        $content = response()->getContent();
        $this->assertStringContainsString('ERROR 500 VIEW', $content);
    }

    public function testLogErrorReturnsEarlyWhenLoggerIsNull(): void
    {
        $this->setPrivateProperty($this->errorHandler, 'logger', null);

        $this->invokePrivateMethod('logError', [new Exception('No logger'), 'error']);

        $this->assertTrue(true);
    }

    public function testLogErrorFallsBackToErrorMethodForUnknownLevel(): void
    {
        $logger = Mockery::mock(Logger::class);
        $logger->shouldReceive('error')
            ->once()
            ->with('Unknown level message', Mockery::type('array'));

        $this->setPrivateProperty($this->errorHandler, 'logger', $logger);

        $this->invokePrivateMethod('logError', [new Exception('Unknown level message'), 'not_existing_level']);

        $this->assertTrue(true);
    }

    public function testComposeStackTraceSkipsHandlerClassEntriesAndBuildsCodeSnippets(): void
    {
        $sourceFile = PROJECT_ROOT . DS . 'shared' . DS . 'trace-source.php';
        $this->createFile($sourceFile, "<?php\nline1\nline2\nline3\nline4\nline5\n");

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

        $trace = $this->invokePrivateMethod('composeStackTrace', [$throwable]);

        $this->removeFile($sourceFile);

        $this->assertCount(2, $trace);
        $this->assertSame($sourceFile, $trace[0]['file']);
        $this->assertStringContainsString('<ol start="1">', $trace[0]['code']);
        $this->assertStringContainsString('class="error-line"', $trace[0]['code']);
        $this->assertSame($sourceFile, $trace[1]['file']);
        $this->assertStringContainsString('class="switch-line"', $trace[1]['code']);
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
        $instancesProperty->setValue($factory, ['local' => new FileSystem($adapter)]);

        $code = $this->invokePrivateMethod('getSourceCode', [__FILE__, 10, 'error-line']);

        $this->assertSame('', $code);
    }

    public function testGetSourceCodeBuildsOrderedListForLocalAdapter(): void
    {
        $file = PROJECT_ROOT . DS . 'shared' . DS . 'source-code.php';
        $this->createFile($file, "<?php\nA\nB\nC\nD\nE\nF\nG\n");

        $code = $this->invokePrivateMethod('getSourceCode', [$file, 5, 'error-line']);

        $this->removeFile($file);

        $this->assertStringContainsString('<ol start="1">', $code);
        $this->assertStringContainsString('class="error-line"', $code);
        $this->assertStringContainsString('<pre>C</pre>', $code);
    }

    public function testFormatLineItemEscapesHtmlAndAppliesHighlightClass(): void
    {
        $html = $this->invokePrivateMethod('formatLineItem', [5, '<div class="x">A&B</div>', 5, 'switch-line']);
        $plain = $this->invokePrivateMethod('formatLineItem', [4, 'plain', 5, 'switch-line']);

        $this->assertStringContainsString('class="switch-line"', $html);
        $this->assertStringContainsString('&lt;div class=&quot;x&quot;&gt;A&amp;B&lt;/div&gt;', $html);
        $this->assertStringNotContainsString('class="switch-line"', $plain);
    }

    /**
     * @dataProvider errorTypeProvider
     */
    public function testGetErrorTypeMappings(\Throwable $throwable, string $expected): void
    {
        $type = $this->invokePrivateMethod('getErrorType', [$throwable]);

        $this->assertSame($expected, $type);
    }

    /**
     * @return array<string, array{0: \Throwable, 1: string}>
     */
    public function errorTypeProvider(): array
    {
        return [
            'mapped-error-exception' => [new ErrorException('x', 0, E_WARNING), 'warning'],
            'unmapped-error-exception' => [new ErrorException('x', 0, E_USER_DEPRECATED), 'error'],
            'parse-error' => [new ParseError('bad syntax'), 'critical'],
            'reflection-exception' => [new ReflectionException('reflection failed'), 'warning'],
            'generic-exception' => [new Exception('unknown'), 'error'],
        ];
    }

    /**
     * @param array<int, mixed> $args
     * @return mixed
     */
    private function invokePrivateMethod(string $method, array $args = [])
    {
        $reflectionMethod = new ReflectionMethod($this->errorHandler, $method);
        $reflectionMethod->setAccessible(true);

        return $reflectionMethod->invokeArgs($this->errorHandler, $args);
    }
}
