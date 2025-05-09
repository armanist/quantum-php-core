<?php

namespace Quantum\Tests\Unit\Libraries\Logger\Adapters;

use Quantum\Libraries\Logger\Adapters\MessageAdapter;
use Quantum\Debugger\DebuggerStore;
use Quantum\Debugger\Debugger;
use Quantum\Tests\Unit\AppTestCase;

class MessageAdapterTest extends AppTestCase
{
    private $adapter;
    private $debugger;

    public function setUp(): void
    {
        parent::setUp();

        $store = new DebuggerStore();

        $this->debugger = Debugger::getInstance($store);

        $this->debugger->initStore();

        $this->adapter = new MessageAdapter();
    }

    public function testReportAddsMessageToDebuggerStore()
    {
        $level = 'info';
        $message = 'Test message';

        $this->adapter->report($level, $message);

        $storedMessages = $this->debugger->getStoreCell(Debugger::MESSAGES);

        $this->assertArrayHasKey($level, $storedMessages[0]);

        $this->assertEquals($message, $storedMessages[0][$level]);
    }
}