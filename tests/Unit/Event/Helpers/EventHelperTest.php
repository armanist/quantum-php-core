<?php

namespace Quantum\Tests\Unit\Event\Helpers;

use Quantum\Tests\Unit\AppTestCase;
use Quantum\Event\EventManager;
use Quantum\Di\Di;

class EventHelperTest extends AppTestCase
{
    public function testEventHelperReturnsInstance(): void
    {
        $this->assertInstanceOf(EventManager::class, event());
    }

    public function testEventHelperRegistersManagerInContainer(): void
    {
        event();

        $this->assertTrue(Di::isRegistered(EventManager::class));
    }

    public function testEventHelperReturnsSameInstance(): void
    {
        $this->assertSame(event(), event());
    }

    public function testEventHelperListenAndDispatch(): void
    {
        $output = '';

        event()->listen('SAVE', function (array $payload) use (&$output): void {
            $output .= 'The file ' . $payload['filename'] . ' was saved';
        });

        event()->dispatch('SAVE', ['filename' => 'doc.pdf']);

        $this->assertSame('The file doc.pdf was saved', $output);
    }
}
