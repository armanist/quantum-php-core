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

namespace Quantum\Router\Exceptions;

use Quantum\App\Exceptions\BaseException;

/**
 * ControllerException class
 * @package Quantum\Router
 */
class RouteControllerException extends BaseException
{

    /**
     * @param string|null $name
     * @return RouteControllerException
     */
    public static function controllerNotDefined(?string $name): RouteControllerException
    {
        return new static("Controller class `$name` not found.", E_ERROR);
    }

    /**
     * @param string $name
     * @return RouteControllerException
     */
    public static function actionNotDefined(string $name): RouteControllerException
    {
        return new static("Action `$name` not defined", E_ERROR);
    }
}