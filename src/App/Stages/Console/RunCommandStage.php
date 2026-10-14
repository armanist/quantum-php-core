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
use Quantum\Environment\Environment;
use Quantum\Console\CliCommand;
use Exception;

/**
 * Class RunCommandStage
 *
 * Runs the requested command; a missing or unknown command is rejected before anything runs,
 * and so is a command that needs the environment file when there is none.
 *
 * @package Quantum\App
 */
class RunCommandStage implements ConsoleStageInterface
{
    /**
     * @throws Exception
     */
    public function process(ConsoleContext $context): void
    {
        $commandName = $context->getInput()->getFirstArgument();

        if ($commandName === null || !$context->getApplication()->has($commandName)) {
            throw new Exception("Command `$commandName` is not defined");
        }

        $command = $context->getApplication()->get($commandName);
        $environment = new Environment();

        if ($command instanceof CliCommand && $command->requiresEnvironment() && !$environment->fileExists()) {
            $environment->load();
        }

        $context->setExitCode(
            $context->getApplication()->run($context->getInput(), $context->getOutput())
        );
    }
}
