<?php

namespace Quantum\Tests\Unit\Logger;

use Quantum\Logger\Contracts\ReportableInterface;
use Quantum\Logger\Adapters\MessageAdapter;
use Quantum\Tests\Unit\AppTestCase;
use Quantum\Logger\Logger;
use Psr\Log\LogLevel;
use Stringable;

class LoggerTest extends AppTestCase
{
    private Logger $logger;
    private MessageAdapter $adapter;

    public function setUp(): void
    {
        parent::setUp();

        $this->adapter = new MessageAdapter();

        $this->logger = new Logger($this->adapter);
    }

    public function testLoggerGetAdapter(): void
    {
        $this->assertEquals($this->adapter, $this->logger->getAdapter());

        $this->assertInstanceOf(MessageAdapter::class, $this->logger->getAdapter());

        $this->assertInstanceOf(ReportableInterface::class, $this->logger->getAdapter());
    }

    public function testLoggerLogReportsTheMessageToTheAdapter(): void
    {
        $this->logger->log('info', 'Test message', []);

        $this->assertSame([['info' => 'Test message']], $this->adapter->getMessages());

        $this->logger->log('error', 'Error message', []);

        $this->assertSame([['info' => 'Test message'], ['error' => 'Error message']], $this->adapter->getMessages());
    }

    /**
     * @dataProvider levelMethodProvider
     */
    public function testLoggerLevelMethodReportsTheMessageWithItsLevel(string $method, string $level): void
    {
        $this->logger->$method('Some message');

        $this->assertSame([[$level => 'Some message']], $this->adapter->getMessages());
    }

    public function testLoggerAcceptsAStringableMessage(): void
    {
        $message = new class () implements Stringable {
            public function __toString(): string
            {
                return 'From an object';
            }
        };

        $this->logger->critical($message);

        $this->assertSame([['critical' => $message]], $this->adapter->getMessages());
    }

    /**
     * @dataProvider levelMethodProvider
     */
    public function testLoggerReportsAnArrayMessageAsPrettyPrintedJson(string $method, string $level): void
    {
        $adapter = $this->recordingAdapter();

        $payload = ['order' => 1042, 'status' => 'paid'];

        (new Logger($adapter))->$method($payload);

        $this->assertCount(1, $adapter->reports);

        $this->assertSame($level, $adapter->reports[0]['level']);

        $this->assertSame(json_encode($payload, JSON_PRETTY_PRINT), $adapter->reports[0]['message']);
    }

    public function testLoggerLogReportsAnArrayMessage(): void
    {
        $adapter = $this->recordingAdapter();

        (new Logger($adapter))->log(LogLevel::NOTICE, ['key' => 'value']);

        $this->assertSame(json_encode(['key' => 'value'], JSON_PRETTY_PRINT), $adapter->reports[0]['message']);
    }

    public function testLoggerPassesTheOriginalArrayInTheContext(): void
    {
        $adapter = $this->recordingAdapter();

        $payload = ['order' => 1042, 'items' => ['a', 'b']];

        (new Logger($adapter))->info($payload, ['trace' => 'the trace']);

        $this->assertSame($payload, $adapter->reports[0]['context']['payload']);

        $this->assertSame('the trace', $adapter->reports[0]['context']['trace']);
    }

    public function testLoggerReportsAnArrayThatCannotBeEncodedAsAString(): void
    {
        $adapter = $this->recordingAdapter();

        (new Logger($adapter))->info(['name' => 'order', 'value' => NAN, 'text' => "\xB1\x31"]);

        $this->assertIsString($adapter->reports[0]['message']);

        $this->assertStringContainsString('order', $adapter->reports[0]['message']);
    }

    public function testLoggerLeavesTheContextOfAStringMessageAlone(): void
    {
        $adapter = $this->recordingAdapter();

        (new Logger($adapter))->info('Some message', ['trace' => 'the trace']);

        $this->assertSame('Some message', $adapter->reports[0]['message']);

        $this->assertSame(['trace' => 'the trace'], $adapter->reports[0]['context']);
    }

    /**
     * An adapter that records what it is asked to report
     */
    private function recordingAdapter(): ReportableInterface
    {
        return new class () implements ReportableInterface {
            /**
             * @var array<int, array<string, mixed>>
             */
            public array $reports = [];

            public function report(string $level, string|Stringable $message, ?array $context = []): void
            {
                $this->reports[] = ['level' => $level, 'message' => $message, 'context' => $context];
            }
        };
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public function levelMethodProvider(): array
    {
        return [
            'emergency' => ['emergency', LogLevel::EMERGENCY],
            'alert' => ['alert', LogLevel::ALERT],
            'critical' => ['critical', LogLevel::CRITICAL],
            'error' => ['error', LogLevel::ERROR],
            'warning' => ['warning', LogLevel::WARNING],
            'notice' => ['notice', LogLevel::NOTICE],
            'info' => ['info', LogLevel::INFO],
            'debug' => ['debug', LogLevel::DEBUG],
        ];
    }
}
