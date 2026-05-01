<?php

namespace Quantum\Tests\Unit\Tracer;

use Quantum\Tests\Unit\AppTestCase;
use Quantum\Tracer\ErrorHandler;
use Quantum\Tracer\WebExceptionRenderer;
use Quantum\Logger\Logger;
use Symfony\Component\Console\Output\BufferedOutput;
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

    public function testHandleExceptionCliWritesExceptionMessage(): void
    {
        config()->set('app.debug', false);

        $output = new BufferedOutput();
        $this->errorHandler->setCliOutput($output);

        $this->errorHandler->handleException(new Exception('CLI failure'));

        $this->assertStringContainsString('CLI failure', $output->fetch());
    }

    public function testHandleExceptionCliInDebugModeWritesVerboseDiagnostics(): void
    {
        config()->set('app.debug', true);

        $output = new BufferedOutput();
        $this->errorHandler->setCliOutput($output);

        $this->errorHandler->handleException(new Exception('CLI debug failure'));

        $display = $output->fetch();
        $this->assertStringContainsString(Exception::class . ': CLI debug failure', $display);
        $this->assertStringContainsString('In ', $display);
        $this->assertStringContainsString(__FILE__, $display);
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

    public function testHandleWebExceptionFallsBackWhenRendererThrows(): void
    {
        config()->set('app.debug', true);

        $renderer = Mockery::mock(WebExceptionRenderer::class);
        $renderer->shouldReceive('render')
            ->once()
            ->andThrow(new Exception('render failed'));

        $handler = new ErrorHandler(null, null, $renderer);

        ob_start();
        $this->invokePrivateMethodOn($handler, 'handleWebException', [new Exception('boom')]);
        ob_get_clean();

        $this->assertSame('Internal Server Error', response()->getContent());
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

    /**
     * @param array<int, mixed> $args
     * @return mixed
     */
    private function invokePrivateMethodOn(object $object, string $method, array $args = [])
    {
        $reflectionMethod = new ReflectionMethod($object, $method);
        $reflectionMethod->setAccessible(true);

        return $reflectionMethod->invokeArgs($object, $args);
    }
}
