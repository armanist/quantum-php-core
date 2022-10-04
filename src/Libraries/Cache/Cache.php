<?php

/**
 * Quantum PHP Framework
 *
 * An open source software development framework for PHP
 *
 * @package Quantum
 * @author Arman Ag. <arman.ag@softberg.org>
 * @copyright Copyright (c) 2018 Softberg LLC (https://softberg.org)
 * @link http://quantum.softberg.org/
 * @since 2.8.0
 */

namespace Quantum\Libraries\Cache;

use Quantum\Exceptions\AppException;
use Psr\SimpleCache\CacheInterface;

/**
 *
 */
class Cache
{

    /**
     * @var Psr\SimpleCache\CacheInterface
     */
    private $adapter;

    /**
     * Cache constructor
     * @param Psr\SimpleCache\CacheInterface $cacheAdapter
     */
    public function __construct(CacheInterface $cacheAdapter)
    {
        $this->adapter = $cacheAdapter;
    }

    /**
     * Gets the current adapter
     * @return Psr\SimpleCache\CacheInterface
     */
    public function getAdapter(): CacheInterface
    {
        return $this->adapter;
    }

    /**
     * @param string $method
     * @param array|null $arguments
     * @return mixed
     * @throws \Quantum\Exceptions\AppException
     */
    public function __call(string $method, ?array $arguments)
    {
        if (!method_exists($this->adapter, $method)) {
            throw AppException::methodNotSupported($method, get_class($this->adapter));
        }

        return $this->adapter->$method(...$arguments);
    }

}
