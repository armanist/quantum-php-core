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
use Quantum\App\AppContext;
use Exception;

/**
 * Class RunCommandStage
 *
 * Runs the requested command; a missing or unknown command is rejected before anything runs.
 *
 * @implements StageInterface<ConsoleContext>
 * @package Quantum\App
 */
class RunCommandStage implements StageInterface
{
    /**
     * @param ConsoleContext $context
     * @throws Exception
     */
    public function process(AppContext $context): void
    {
        $commandName = $context->getInput()->getFirstArgument();

        if ($commandName === null || !$context->getApplication()->has($commandName)) {
            throw new Exception("Command `$commandName` is not defined");
        }

        $context->setExitCode(
            $context->getApplication()->run($context->getInput(), $context->getOutput())
        );
    }
}
