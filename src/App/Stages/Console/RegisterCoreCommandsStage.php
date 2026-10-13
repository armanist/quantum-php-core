<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\App\Stages\Console;

use Quantum\App\Contracts\ConsoleStageInterface;
use Quantum\App\Adapters\Console\ConsoleContext;
use Quantum\Console\CommandRegistrar;
use ReflectionException;

/**
 * Class RegisterCoreCommandsStage
 * @package Quantum\App
 */
class RegisterCoreCommandsStage implements ConsoleStageInterface
{
    /**
     * Registers the commands that ship with the framework
     * @throws ReflectionException
     */
    public function process(ConsoleContext $context): void
    {
        CommandRegistrar::register(
            $context->getApplication(),
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
