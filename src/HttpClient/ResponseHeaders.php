<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\HttpClient;

use ArrayAccess;
use Countable;
use Iterator;

/**
 * Class ResponseHeaders
 * @package Quantum\HttpClient
 * @implements ArrayAccess<string, mixed>
 * @implements Iterator<string, mixed>
 */
class ResponseHeaders implements ArrayAccess, Countable, Iterator
{
    /**
     * @var array<string, mixed>
     */
    private array $data = [];

    /**
     * @var array<string, string>
     */
    private array $keys = [];

    /**
     * @param array<string, mixed>|null $headers
     */
    public function __construct(?array $headers = null)
    {
        if ($headers !== null) {
            foreach ($headers as $key => $value) {
                $this->offsetSet($key, $value);
            }
        }
    }

    /**
     * @param string|null $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value): void
    {
        if ($offset === null) {
            return;
        }

        $normalizedOffset = strtolower($offset);
        $this->data[$normalizedOffset] = $value;
        $this->keys[$normalizedOffset] = $offset;
    }

    /**
     * @param string $offset
     */
    public function offsetExists($offset): bool
    {
        return array_key_exists(strtolower($offset), $this->data);
    }

    /**
     * @param string $offset
     */
    public function offsetUnset($offset): void
    {
        $normalizedOffset = strtolower($offset);

        unset($this->data[$normalizedOffset]);
        unset($this->keys[$normalizedOffset]);
    }

    /**
     * @param string $offset
     * @return mixed|null
     */
    public function offsetGet($offset)
    {
        return $this->data[strtolower($offset)] ?? null;
    }

    public function count(): int
    {
        return count($this->data);
    }

    /**
     * @return mixed
     */
    public function current()
    {
        return current($this->data);
    }

    public function next(): void
    {
        next($this->data);
    }

    /**
     * @return string|null
     */
    public function key()
    {
        $key = key($this->data);

        if ($key === null) {
            return null;
        }

        return $this->keys[$key] ?? $key;
    }

    public function valid(): bool
    {
        return key($this->data) !== null;
    }

    public function rewind(): void
    {
        reset($this->data);
    }
}
