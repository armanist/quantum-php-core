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
 * @since 2.9.6
 */

namespace Quantum\Libraries\Cache\Adapters;

use Quantum\Libraries\Database\Contracts\DbalInterface;
use Quantum\Model\Factories\ModelFactory;
use Psr\SimpleCache\CacheInterface;
use InvalidArgumentException;
use Exception;

/**
 * Class DatabaseAdapter
 * @package Quantum\Libraries\Cache
 */
class DatabaseAdapter implements CacheInterface
{

    /**
     * @var int
     */
    private $ttl;

    /**
     * @var string
     */
    private $prefix;

    /**
     * @var DbalInterface
     */
    private $cacheModel;

    /**
     * @param array $params
     */
    public function __construct(array $params)
    {
        $this->ttl = $params['ttl'];
        $this->prefix = $params['prefix'];
        $this->cacheModel = ModelFactory::createDynamicModel($params['table']);
    }

    /**
     * @inheritDoc
     */
    public function get($key, $default = null)
    {
        if ($this->has($key)) {
            $cacheItem = $this->cacheModel->findOneBy('key', $this->keyHash($key));

            try {
                return unserialize($cacheItem->prop('value'));
            } catch (Exception $e) {
                $this->delete($key);
                return $default;
            }
        }

        return $default;
    }

    /**
     * @inheritDoc
     */
    public function getMultiple($keys, $default = null)
    {
        if (!is_array($keys)) {
            throw new InvalidArgumentException(t(_message('exception.non_iterable_value', '$keys')), E_WARNING);
        }

        $result = [];

        foreach ($keys as $key) {
            $result[$key] = $this->get($key, $default);
        }

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function has($key): bool
    {
        $cacheItem = $this->cacheModel->findOneBy('key', $this->keyHash($key));

        if (empty($cacheItem->asArray())) {
            return false;
        }

        if (time() - $cacheItem->prop('ttl') > $this->ttl) {
            $this->delete($key);
            return false;
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function set($key, $value, $ttl = null): bool
    {
        $cacheItem = $this->cacheModel->findOneBy('key', $this->keyHash($key));

        if (empty($cacheItem->asArray())) {
            $cacheItem = $this->cacheModel->create();
        }

        $cacheItem->prop('key', $this->keyHash($key));
        $cacheItem->prop('value', serialize($value));
        $cacheItem->prop('ttl', time());

        return $cacheItem->save();
    }

    /**
     * @inheritDoc
     * @throws InvalidArgumentException
     */
    public function setMultiple($values, $ttl = null): bool
    {
        if (!is_array($values)) {
            throw new InvalidArgumentException(t(_message('exception.non_iterable_value', '$values')), E_WARNING);
        }

        $results = [];

        foreach ($values as $key => $value) {
            $results[] = $this->set($key, $value, $ttl);
        }

        return !in_array(false, $results, true);
    }

    /**
     * @inheritDoc
     */
    public function delete($key): bool
    {
        $cacheItem = $this->cacheModel->findOneBy('key', $this->keyHash($key));

        if (!empty($cacheItem->asArray())) {
            return $this->cacheModel->delete();
        }

        return false;
    }

    /**
     * @inheritDoc
     * @throws InvalidArgumentException
     */
    public function deleteMultiple($keys): bool
    {
        if (!is_array($keys)) {
            throw new InvalidArgumentException(t(_message('exception.non_iterable_value', '$keys')), E_WARNING);
        }

        $results = [];

        foreach ($keys as $key) {
            $results[] = $this->delete($key);
        }

        return !in_array(false, $results, true);
    }

    /**
     * @inheritDoc
     */
    public function clear(): bool
    {
        return $this->cacheModel->deleteMany();
    }

    /**
     * Gets the hashed key
     * @param string $key
     * @return string
     */
    private function keyHash(string $key): string
    {
        return sha1($this->prefix . $key);
    }
}
