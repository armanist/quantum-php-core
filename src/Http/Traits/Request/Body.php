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
 * @since 2.9.8
 */

namespace Quantum\Http\Traits\Request;

use Quantum\App\Constants\ReservedKeys;
use InvalidArgumentException;

/**
 * Trait Body
 * @package Quantum\Http\Request
 */
trait Body
{

    /**
     * Request body
     * @var array
     */
    private static $__request = [];

    /**
     * Checks if request contains a data by given key
     * @param string $key
     * @return bool
     */
    public static function has(string $key): bool
    {
        return isset(self::$__request[$key]);
    }

    /**
     * Retrieves data from request by given key
     * @param string $key
     * @param string|null $default
     * @param bool $raw
     * @return mixed
     */
    public static function get(string $key, string $default = null, bool $raw = false)
    {
        if(!self::has($key)) {
            return $default;
        }

        $value = self::$__request[$key];

        if ($raw) {
            return $value;
        }

        return is_array($value)
            ? array_map('strip_tags', $value)
            : strip_tags($value);
    }

    /**
     * Sets new key/value pair into request
     * @param string $key
     * @param mixed $value
     */
    public static function set(string $key, $value)
    {
        if ($key === ReservedKeys::RENDERED_VIEW) {
            throw new InvalidArgumentException("Cannot set reserved key: `$key`");
        }

        self::$__request[$key] = $value;
    }

    /**
     * Gets all request parameters
     * @return array
     */
    public static function all(): array
    {
        return array_merge(self::$__request, self::$__files);
    }

    /**
     * Deletes the element from request by given key
     * @param string $key
     */
    public static function delete(string $key)
    {
        if (self::has($key)) {
            unset(self::$__request[$key]);
        }
    }
}