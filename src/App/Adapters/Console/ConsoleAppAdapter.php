<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\App\Adapters\Console;

use Quantum\App\Adapters\Console\Stages\Boot\SetupConsoleApplicationStage;
use Quantum\App\Adapters\Console\Stages\Boot\RegisterCoreCommandsStage;
use Quantum\App\Adapters\Console\Stages\Boot\RegisterAppCommandsStage;
use Quantum\App\Adapters\Console\Stages\Boot\ResolveEnvironmentStage;
use Quantum\App\Adapters\Console\Stages\Runtime\RunCommandStage;
use Quantum\App\Adapters\Console\Stages\Boot\InitConsoleStage;
use Quantum\App\Stages\Boot\SetupErrorHandlerStage;
use Quantum\App\Stages\Boot\LoadEnvironmentStage;
use Quantum\App\Stages\Boot\LoadAppConfigStage;
use Quantum\App\Stages\Boot\LoadHelpersStage;
use Quantum\App\Contracts\AppInterface;
use Quantum\App\AppContext;
use Quantum\App\Pipeline;
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

    public function __construct(AppContext $context)
    {
        $this->context = $context;
    }

    public function boot(): void
    {
        $pipeline = new Pipeline([
            new InitConsoleStage(),
            new LoadHelpersStage(),
            new RegisterCoreCommandsStage(),
            new ResolveEnvironmentStage(),
            new LoadEnvironmentStage(),
            new LoadAppConfigStage(),
            new SetupErrorHandlerStage(),
            new SetupConsoleApplicationStage(),
            new RegisterAppCommandsStage(),
        ]);

        $pipeline->run($this->context);
    }

    /**
     * @throws Exception
     */
    public function start(): ?int
    {
        $pipeline = new Pipeline([
            new RunCommandStage(),
        ]);

        $context = new ConsoleContext($this->context);

        $pipeline->run($context);

        return $context->getExitCode();
    }
}
