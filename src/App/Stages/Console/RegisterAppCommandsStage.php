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
 * Class RegisterAppCommandsStage
 * @package Quantum\App
 */
class RegisterAppCommandsStage implements ConsoleStageInterface
{
    /**
     * Registers the commands of the application
     * @throws ReflectionException
     */
    public function process(ConsoleContext $context): void
    {
        CommandRegistrar::register(
            $context->getApplication(),
            base_dir() . DS . 'shared' . DS . 'Commands',
            '\\Shared\\Commands\\'
        );
    }
}
