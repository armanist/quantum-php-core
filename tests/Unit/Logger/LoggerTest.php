<?php

namespace Quantum\Tests\Unit\Logger;

use Quantum\Logger\Contracts\ReportableInterface;
use Quantum\Logger\Adapters\MessageAdapter;
use Quantum\Tests\Unit\AppTestCase;
use Quantum\Logger\Logger;

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
}
