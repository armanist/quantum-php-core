<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\App\Stages\Console;

use Quantum\App\Adapters\Console\ConsoleContext;
use Quantum\App\Contracts\StageInterface;
use Quantum\Console\CommandRegistrar;
use Quantum\App\AppContext;
use ReflectionException;

/**
 * Class RegisterAppCommandsStage
 * @implements StageInterface<ConsoleContext>
 * @package Quantum\App
 */
class RegisterAppCommandsStage implements StageInterface
{
    /**
     * Registers the commands of the application
     * @param ConsoleContext $context
     * @throws ReflectionException
     */
    public function process(AppContext $context): void
    {
        CommandRegistrar::register(
            $context->getApplication(),
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
