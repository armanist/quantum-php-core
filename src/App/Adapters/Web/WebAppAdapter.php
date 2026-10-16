<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\App\Adapters\Web;

use Quantum\App\Stages\Web\HandleRouteNotFoundStage;
use Quantum\App\Stages\Boot\SetupErrorHandlerStage;
use Quantum\App\Stages\Boot\LoadEnvironmentStage;
use Quantum\App\Stages\Web\HandlePreflightStage;
use Quantum\App\Stages\Web\DispatchRequestStage;
use Quantum\App\Stages\Web\PrepareRequestStage;
use Quantum\App\Stages\Boot\LoadAppConfigStage;
use Quantum\App\Stages\Boot\InitDebuggerStage;
use Quantum\App\Stages\Web\SendResponseStage;
use Quantum\App\Stages\Web\ResolveRouteStage;
use Quantum\App\Stages\Boot\LoadModulesStage;
use Quantum\App\Stages\Boot\LoadHelpersStage;
use Quantum\App\Stages\Boot\InitHttpStage;
use Quantum\App\Contracts\AppInterface;
use Quantum\App\Enums\ExitCode;
use Quantum\App\AppContext;
use Quantum\App\Pipeline;

/**
 * Class WebAppAdapter
 * @package Quantum\App
 */
class WebAppAdapter implements AppInterface
{
    protected AppContext $context;

    public function __construct(AppContext $context)
    {
        $this->context = $context;

        $pipeline = new Pipeline([
            new LoadHelpersStage(),
            new LoadEnvironmentStage(),
            new LoadAppConfigStage(),
            new SetupErrorHandlerStage(),
            new InitHttpStage(),
            new InitDebuggerStage(),
            new LoadModulesStage(),
        ]);

        $pipeline->run($this->context);
    }

    public function start(): ?int
    {
        $pipeline = new Pipeline([
            new HandlePreflightStage(),
            new ResolveRouteStage(),
            new HandleRouteNotFoundStage(),
            new PrepareRequestStage(),
            new DispatchRequestStage(),
            new SendResponseStage(),
        ]);

        $pipeline->run(new WebContext($this->context));

        return ExitCode::SUCCESS;
    }
}
