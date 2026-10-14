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
use Quantum\Console\CliCommand;
use Exception;
use Throwable;

/**
 * Class RunCommandStage
 *
 * Runs the requested command; a missing or unknown command is rejected before anything runs,
 * and so is a command that needs the environment after the boot failed.
 *
 * @package Quantum\App
 */
class RunCommandStage implements ConsoleStageInterface
{
    /**
     * @throws Exception|Throwable
     */
    public function process(ConsoleContext $context): void
    {
        $commandName = $context->getInput()->getFirstArgument();

        if ($commandName === null || !$context->getApplication()->has($commandName)) {
            throw new Exception("Command `$commandName` is not defined");
        }

        $command = $context->getApplication()->get($commandName);

        if ($context->getBootFailure() !== null && $command instanceof CliCommand && $command->requiresEnvironment()) {
            throw $context->getBootFailure();
        }

        $context->setExitCode(
            $context->getApplication()->run($context->getInput(), $context->getOutput())
        );
    }
}
