<?php

namespace Quantum\Tests\Unit\Logger\Adapters;

use Quantum\Logger\Adapters\MessageAdapter;
use Quantum\Tests\Unit\AppTestCase;

class MessageAdapterTest extends AppTestCase
{
    private MessageAdapter $adapter;

    public function setUp(): void
    {
        parent::setUp();

        $this->adapter = new MessageAdapter();
    }

    public function testGetMessagesIsEmptyBeforeAnyReport(): void
    {
        $this->assertSame([], $this->adapter->getMessages());
    }

    public function testReportKeepsTheMessagesInOrder(): void
    {
        $this->adapter->report('info', 'Test message');
        $this->adapter->report('error', 'Error message');

        $this->assertSame([['info' => 'Test message'], ['error' => 'Error message']], $this->adapter->getMessages());
    }
}
