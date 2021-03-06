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
 * @since 2.0.0
 */

namespace Quantum\Mvc;

use Quantum\Exceptions\ControllerException;
use Quantum\Routes\RouteController;
use BadMethodCallException;


/**
 * Class QtController
 * @package Quantum\Mvc
 */
class QtController extends RouteController
{

    /**
     * Instance of QtController
     * @var QtController
     */
    private static $instance;

    /**
     * Gets the QtController singleton instance
     * @return QtController
     */
    public static function getInstance(): QtController
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Handles the missing methods of the controller
     * @param string $method
     * @param array $arguments
     */
    public function __call(string $method, array $arguments)
    {
        throw new BadMethodCallException(_message(ControllerException::UNDEFINED_METHOD, $method));
    }

}
