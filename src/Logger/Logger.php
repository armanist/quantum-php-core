<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Logger;

use Quantum\Logger\Contracts\ReportableInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Stringable;

/**
 * Class Logger
 * @package Quantum\Logger
 */
class Logger implements LoggerInterface
{
    private readonly ReportableInterface $adapter;

    public function __construct(ReportableInterface $adapter)
    {
        $this->adapter = $adapter;
    }

    public function getAdapter(): ReportableInterface
    {
        return $this->adapter;
    }

    /**
     * @inheritDoc
     * @param string|Stringable|array<string, mixed> $message
     */
    public function emergency(string|Stringable|array $message, array $context = []): void
    {
        $this->log(LogLevel::EMERGENCY, $message, $context);
    }

    /**
     * @inheritDoc
     * @param string|Stringable|array<string, mixed> $message
     */
    public function alert(string|Stringable|array $message, array $context = []): void
    {
        $this->log(LogLevel::ALERT, $message, $context);
    }

    /**
     * @inheritDoc
     * @param string|Stringable|array<string, mixed> $message
     */
    public function critical(string|Stringable|array $message, array $context = []): void
    {
        $this->log(LogLevel::CRITICAL, $message, $context);
    }

    /**
     * @inheritDoc
     * @param string|Stringable|array<string, mixed> $message
     */
    public function error(string|Stringable|array $message, array $context = []): void
    {
        $this->log(LogLevel::ERROR, $message, $context);
    }

    /**
     * @inheritDoc
     * @param string|Stringable|array<string, mixed> $message
     */
    public function warning(string|Stringable|array $message, array $context = []): void
    {
        $this->log(LogLevel::WARNING, $message, $context);
    }

    /**
     * @inheritDoc
     * @param string|Stringable|array<string, mixed> $message
     */
    public function notice(string|Stringable|array $message, array $context = []): void
    {
        $this->log(LogLevel::NOTICE, $message, $context);
    }

    /**
     * @inheritDoc
     * @param string|Stringable|array<string, mixed> $message
     */
    public function info(string|Stringable|array $message, array $context = []): void
    {
        $this->log(LogLevel::INFO, $message, $context);
    }

    /**
     * @inheritDoc
     * @param string|Stringable|array<string, mixed> $message
     */
    public function debug(string|Stringable|array $message, array $context = []): void
    {
        $this->log(LogLevel::DEBUG, $message, $context);
    }

    /**
     * Logs a message. An array message is reported as pretty-printed JSON, and the array itself
     * is passed to the adapter in the `payload` context key
     * @inheritDoc
     * @param string|Stringable|array<string, mixed> $message
     */
    public function log($level, string|Stringable|array $message, array $context = []): void
    {
        if (is_debug_mode() || LoggerConfig::getLogLevel($level) >= LoggerConfig::getAppLogLevel()) {
            if (is_array($message)) {
                $context['payload'] = $message;
                $message = json_encode($message, JSON_PRETTY_PRINT | JSON_PARTIAL_OUTPUT_ON_ERROR) ?: print_r($message, true);
            }

            $this->adapter->report($level, $message, $context);
        }
    }
}
