<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Event\Enums;

use Quantum\App\Enums\ExceptionMessages as BaseExceptionMessages;

/**
 * Class ExceptionMessages
 * @package Quantum\Event
 */
final class ExceptionMessages extends BaseExceptionMessages
{
    public const EMPTY_EVENT_NAME = 'The event name must not be empty.';
}
