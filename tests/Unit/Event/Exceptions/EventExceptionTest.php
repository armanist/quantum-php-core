<?php

namespace Quantum\Tests\Unit\Event\Exceptions;

use Quantum\Event\Exceptions\EventException;
use Quantum\Tests\Unit\AppTestCase;

class EventExceptionTest extends AppTestCase
{
    public function testEmptyEventName(): void
    {
        $exception = EventException::emptyEventName();

        $this->assertInstanceOf(EventException::class, $exception);
        $this->assertSame('The event name must not be empty.', $exception->getMessage());
        $this->assertSame(E_ERROR, $exception->getCode());
    }
}
