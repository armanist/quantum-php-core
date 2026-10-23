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

    /**
     * The number of last messages kept; 0 keeps every message
     */
    private readonly int $limit;

    public function __construct(int $limit = 1000)
    {
        $this->limit = $limit;
    }

    public function report(string $level, string|Stringable $message, ?array $context = []): void
    {
        $this->messages[] = [$level => $message];

        if ($this->limit > 0 && count($this->messages) > $this->limit) {
            $this->messages = array_slice($this->messages, -$this->limit);
        }
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
