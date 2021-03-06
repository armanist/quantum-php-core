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
 * @since 1.9.5
 */

namespace Quantum\Exceptions;

/**
 * ServiceException class
 * 
 * @package Quantum
 * @category Exceptions
 */
class ServiceException extends \Exception
{
    /**
     * Service not found message
     */
    const SERVICE_NOT_FOUND = 'Service `{%1}` not found';

    /**
     * Model not instance of QtModel
     */
    const NOT_INSTANCE_OF_SERVICE = 'Service `{%1}` is not instance of `{%2}`';

    /**
     * Undefined method
     */
    const UNDEFINED_METHOD = 'The method `{%1}` is not defined';
}
