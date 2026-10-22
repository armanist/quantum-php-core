<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\App\Adapters\Web;

use Quantum\App\Adapters\Web\Stages\Runtime\HandleRouteNotFoundStage;
use Quantum\App\Adapters\Web\Stages\Runtime\HandlePreflightStage;
use Quantum\App\Adapters\Web\Stages\Runtime\DispatchRequestStage;
use Quantum\App\Adapters\Web\Stages\Runtime\PrepareRequestStage;
use Quantum\App\Adapters\Web\Stages\Runtime\SendResponseStage;
use Quantum\App\Adapters\Web\Stages\Runtime\ResolveRouteStage;
use Quantum\App\Adapters\Web\Stages\Boot\LoadModulesStage;
use Quantum\App\Adapters\Web\Stages\Boot\InitHttpStage;
use Quantum\App\Stages\Boot\SetupErrorHandlerStage;
use Quantum\App\Stages\Boot\LoadEnvironmentStage;
use Quantum\App\Stages\Boot\LoadAppConfigStage;
use Quantum\App\Stages\Boot\LoadHelpersStage;
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
    }

    public function boot(): void
    {
        $pipeline = new Pipeline([
            new LoadHelpersStage(),
            new LoadEnvironmentStage(),
            new LoadAppConfigStage(),
            new SetupErrorHandlerStage(),
            new InitHttpStage(),
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
        ], new SendResponseStage());

        $pipeline->run(new WebContext($this->context));

        return ExitCode::SUCCESS;
    }
}
