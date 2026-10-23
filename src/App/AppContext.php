<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\App;

use Quantum\Environment\Environment;
use Quantum\App\Enums\ExitCode;
use Quantum\Di\DiContainer;
use Quantum\Config\Config;

/**
 * Class AppContext
 *
 * Carries the base directory, the container and the type (web or console) of an application, and the state of the run it is passed through:
 * the stopped flag, which ends the pipeline run early, and the exit code.
 *
 * @package Quantum\App
 */
class AppContext
{
    private string $baseDir;

    private DiContainer $container;

    private string $type;

    private bool $stopped = false;

    private int $exitCode = ExitCode::SUCCESS;

    /**
     * @param string $type AppType::WEB or AppType::CONSOLE
     */
    public function __construct(string $baseDir, DiContainer $container, string $type)
    {
        $this->baseDir = $baseDir;
        $this->container = $container;
        $this->type = $type;
    }

    public function getBaseDir(): string
    {
        return $this->baseDir;
    }

    public function getContainer(): DiContainer
    {
        return $this->container;
    }

    /**
     * Gets the type of the application: AppType::WEB or AppType::CONSOLE
     */
    public function getType(): string
    {
        return $this->type;
    }

    public function getEnvironment(): Environment
    {
        return $this->resolveFromContainer(Environment::class);
    }

    public function getConfig(): Config
    {
        return $this->resolveFromContainer(Config::class);
    }

    /**
     * Ends the pipeline run after the current stage; only the closing stage still runs
     */
    public function stop(): void
    {
        $this->stopped = true;
    }

    public function isStopped(): bool
    {
        return $this->stopped;
    }

    public function getExitCode(): int
    {
        return $this->exitCode;
    }

    public function setExitCode(int $exitCode): void
    {
        $this->exitCode = $exitCode;
    }

    /**
     * @template T of object
     * @param class-string<T> $class
     * @return T
     */
    protected function resolveFromContainer(string $class)
    {
        return $this->container->get($class);
    }
}
