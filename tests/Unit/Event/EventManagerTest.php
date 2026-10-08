<?php

namespace Quantum\Tests\Unit\Event;

use Quantum\Event\Exceptions\EventException;
use Quantum\Tests\Unit\AppTestCase;
use Quantum\Event\EventManager;
use RuntimeException;

class EventManagerTest extends AppTestCase
{
    private EventManager $events;

    public function setUp(): void
    {
        parent::setUp();

        $this->events = new EventManager();
    }

    public function testListenAndDispatch(): void
    {
        $output = '';

        $this->events->listen('SAVE', function () use (&$output): void {
            $output .= 'Saved!';
        });

        $this->events->dispatch('SAVE');

        $this->assertSame('Saved!', $output);
    }

    public function testDispatchPassesPayloadToListener(): void
    {
        $received = null;

        $this->events->listen('NOTIFY', function (array $payload) use (&$received): void {
            $received = $payload;
        });

        $this->events->dispatch('NOTIFY', ['user' => 'John']);

        $this->assertSame(['user' => 'John'], $received);
    }

    public function testDispatchWithoutPayloadPassesEmptyArray(): void
    {
        $received = null;

        $this->events->listen('NOTIFY', function (array $payload) use (&$received): void {
            $received = $payload;
        });

        $this->events->dispatch('NOTIFY');

        $this->assertSame([], $received);
    }

    public function testListenersRunInRegistrationOrder(): void
    {
        $output = '';

        $this->events->listen('SAVE', function () use (&$output): void {
            $output .= 'A';
        });

        $this->events->listen('SAVE', function () use (&$output): void {
            $output .= 'B';
        });

        $this->events->dispatch('SAVE');

        $this->assertSame('AB', $output);
    }

    public function testListenersPersistAfterDispatch(): void
    {
        $count = 0;

        $this->events->listen('SAVE', function () use (&$count): void {
            $count++;
        });

        $this->events->dispatch('SAVE');
        $this->events->dispatch('SAVE');

        $this->assertSame(2, $count);
    }

    public function testListenersAreScopedByEventName(): void
    {
        $output = '';

        $this->events->listen('SAVE', function () use (&$output): void {
            $output .= 'save';
        });

        $this->events->listen('DELETE', function () use (&$output): void {
            $output .= 'delete';
        });

        $this->events->dispatch('DELETE');

        $this->assertSame('delete', $output);
    }

    public function testDispatchWithoutListenersIsNoOp(): void
    {
        $this->events->dispatch('NEVER_LISTENED');

        $this->assertSame([], $this->events->getRegistered());
    }

    public function testListenerExceptionPropagatesAndStopsLaterListeners(): void
    {
        $called = false;

        $this->events->listen('SAVE', function (): void {
            throw new RuntimeException('Listener failed');
        });

        $this->events->listen('SAVE', function () use (&$called): void {
            $called = true;
        });

        try {
            $this->events->dispatch('SAVE');
            $this->fail('The listener exception was not propagated.');
        } catch (RuntimeException $e) {
            $this->assertSame('Listener failed', $e->getMessage());
        }

        $this->assertFalse($called);
    }

    public function testListenWithEmptyNameThrows(): void
    {
        $this->expectException(EventException::class);
        $this->expectExceptionMessage('The event name must not be empty.');

        $this->events->listen('', function (): void {
        });
    }

    public function testDispatchWithEmptyNameThrows(): void
    {
        $this->expectException(EventException::class);
        $this->expectExceptionMessage('The event name must not be empty.');

        $this->events->dispatch('');
    }

    public function testGetRegisteredReturnsListenersByName(): void
    {
        $this->events->listen('SAVE', function (): void {
        });

        $this->events->listen('SAVE', function (): void {
        });

        $registered = $this->events->getRegistered();

        $this->assertArrayHasKey('SAVE', $registered);
        $this->assertCount(2, $registered['SAVE']);
    }

    public function testWorksWithoutLoadedConfig(): void
    {
        config()->flush();

        $events = new EventManager();
        $output = '';

        $events->listen('BOOT', function () use (&$output): void {
            $output .= 'ok';
        });

        $events->dispatch('BOOT');

        $this->assertSame('ok', $output);
        $this->assertFalse(config()->has('hooks'));
        $this->assertFalse(config()->has('events'));
    }
}
