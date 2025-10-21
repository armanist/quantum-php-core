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
 * @since 2.9.9
 */

namespace Quantum\Middleware\Exceptions;

use Quantum\App\Exceptions\BaseException;

/**
 * Class MiddlewareException
 * @package Quantum\Exceptions
 */
class MiddlewareException extends BaseException
{

    /**
     * @param string $name
     * @return MiddlewareException
     */
    public static function middlewareNotFound(string $name): MiddlewareException
    {
        return new static("Middleware class `$name` not found.", E_ERROR);
    }
}