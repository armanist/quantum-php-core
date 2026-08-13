<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\HttpClient\Traits;

/**
 * Trait AdapterTrait
 * @package Quantum\HttpClient
 */
trait AdapterTrait
{
    public function supportsMethod(string $method): bool
    {
        return in_array($method, self::SUPPORTED_METHODS, true);
    }

    /**
     * @param array<mixed> $arguments
     * @return mixed
     */
    public function callMethod(string $method, array $arguments)
    {
        if ($this->supportsMethod($method)) {
            return $this->$method(...$arguments);
        }

        return null;
    }
}
