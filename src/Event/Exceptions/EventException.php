<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Event\Exceptions;

use Quantum\Event\Enums\ExceptionMessages;
use Quantum\App\Exceptions\BaseException;

/**
 * Class EventException
 * @package Quantum\Event
 */
class EventException extends BaseException
{
    public static function emptyEventName(): self
    {
        return new self(
            ExceptionMessages::EMPTY_EVENT_NAME,
            E_ERROR
        );
    }
}
