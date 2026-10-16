<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\App\Adapters\Console;

use Quantum\App\Stages\Boot\SetupConsoleApplicationStage;
use Quantum\App\Stages\Console\RegisterCoreCommandsStage;
use Quantum\App\Stages\Console\RegisterAppCommandsStage;
use Quantum\App\Stages\Boot\SetupErrorHandlerStage;
use Quantum\App\Stages\Boot\LoadEnvironmentStage;
use Quantum\App\Stages\Console\RunCommandStage;
use Quantum\App\Stages\Boot\LoadAppConfigStage;
use Quantum\App\Stages\Boot\InitConsoleStage;
use Quantum\App\Stages\Boot\LoadHelpersStage;
use Quantum\App\Contracts\AppInterface;
use Quantum\App\AppContext;
use Quantum\App\Pipeline;
use Throwable;
use Exception;

if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}

/**
 * Class ConsoleAppAdapter
 * @package Quantum\App
 */
class ConsoleAppAdapter implements AppInterface
{
    protected AppContext $context;

    protected ?Throwable $bootFailure = null;

    public function __construct(AppContext $context)
    {
        $this->context = $context;

        try {
            $pipeline = new Pipeline([
                new InitConsoleStage(),
                new LoadHelpersStage(),
                new LoadEnvironmentStage(),
                new LoadAppConfigStage(),
                new SetupErrorHandlerStage(),
                new SetupConsoleApplicationStage(),
            ]);

            $pipeline->run($this->context);
        } catch (Throwable $exception) {
            $this->bootFailure = $exception;
        }
    }

    /**
    * @throws Exception|Throwable
    */
    public function start(): ?int
    {
        $pipeline = new Pipeline([
            new RegisterCoreCommandsStage(),
            new RegisterAppCommandsStage(),
            new RunCommandStage(),
        ]);

        $context = new ConsoleContext($this->context, $this->bootFailure);

        try {
            $pipeline->run($context);
        } catch (Throwable $exception) {
            throw $this->bootFailure ?? $exception;
        }

        return $context->getExitCode();
    }
}
