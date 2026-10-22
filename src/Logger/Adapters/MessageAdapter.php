<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Logger\Adapters;

use Quantum\Logger\Contracts\ReportableInterface;
use Stringable;

/**
 * Class MessageAdapter
 * @package Quantum\Logger
 */
class MessageAdapter implements ReportableInterface
{
    /**
     * Reported messages, as [level => message]
     * @var array<int, array<string, string|Stringable>>
     */
    private array $messages = [];

    public function report(string $level, string|Stringable $message, ?array $context = []): void
    {
        $this->messages[] = [$level => $message];
    }

    /**
     * Gets the reported messages in the order they were reported
     * @return array<int, array<string, string|Stringable>>
     */
    public function getMessages(): array
    {
        return $this->messages;
    }
}
