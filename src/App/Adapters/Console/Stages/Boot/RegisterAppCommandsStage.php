<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\App\Adapters\Console\Stages\Boot;

use Symfony\Component\Console\Application;
use Quantum\App\Contracts\StageInterface;
use Quantum\Di\Exceptions\DiException;
use Quantum\Console\CommandRegistrar;
use Quantum\App\AppContext;
use ReflectionException;
use Quantum\Di\Di;

/**
 * Class RegisterAppCommandsStage
 *
 * Runs last in the console boot, so the constructors of the app commands can use the environment and the config.
 *
 * @implements StageInterface<AppContext>
 * @package Quantum\App
 */
class RegisterAppCommandsStage implements StageInterface
{
    /**
     * Registers the commands of the application
     * @throws DiException|ReflectionException
     */
    public function process(AppContext $context): void
    {
        CommandRegistrar::register(
            Di::get(Application::class),
            $this->directory(),
            $this->namespace(),
        );
    }

    private function namespace(): string
    {
        return '\\Shared\\Commands\\';
    }

    private function directory(): string
    {
        return base_dir() . DS . 'shared' . DS . 'Commands';
    }
}
