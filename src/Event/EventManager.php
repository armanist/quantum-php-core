<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Event;

use Quantum\Event\Exceptions\EventException;

/**
 * Class EventManager
 * @package Quantum\Event
 */
class EventManager
{
    /**
     * Registered listeners store
     * @var array<string, array<int, callable>>
     */
    private array $listeners = [];

    /**
     * Adds a listener for a given event
     * @throws EventException
     */
    public function listen(string $name, callable $listener): void
    {
        $this->assertName($name);

        $this->listeners[$name][] = $listener;
    }

    /**
     * Dispatches the event to its listeners in registration order
     * @param array<string, mixed> $payload
     * @throws EventException
     */
    public function dispatch(string $name, array $payload = []): void
    {
        $this->assertName($name);

        foreach ($this->listeners[$name] ?? [] as $listener) {
            $listener($payload);
        }
    }

    /**
     * Gets all registered listeners by event name
     * @return array<string, array<int, callable>>
     */
    public function getRegistered(): array
    {
        return $this->listeners;
    }

    /**
     * @throws EventException
     */
    private function assertName(string $name): void
    {
        if ($name === '') {
            throw EventException::emptyEventName();
        }
    }
}
