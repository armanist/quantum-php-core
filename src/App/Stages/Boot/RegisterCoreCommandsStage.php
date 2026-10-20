<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\App\Stages\Boot;

use Symfony\Component\Console\Application;
use Quantum\App\Contracts\StageInterface;
use Quantum\Di\Exceptions\DiException;
use Quantum\Console\CommandRegistrar;
use Quantum\App\AppContext;
use ReflectionException;
use Quantum\Di\Di;

/**
 * Class RegisterCoreCommandsStage
 *
 * Runs right after the helpers, before the environment, so that ResolveEnvironmentStage can find
 * core:env when the .env file cannot be loaded. A core command constructor therefore uses
 * no environment and no config.
 *
 * @implements StageInterface<AppContext>
 * @package Quantum\App
 */
class RegisterCoreCommandsStage implements StageInterface
{
    /**
     * Registers the commands that ship with the framework
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
        return '\\Quantum\\Console\\Commands\\';
    }

    private function directory(): string
    {
        return framework_dir() . DS . 'Console' . DS . 'Commands';
    }
}
