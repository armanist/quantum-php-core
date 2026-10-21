<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\App\Adapters\Console\Stages\Boot;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Application;
use Quantum\App\Contracts\StageInterface;
use Quantum\App\AppContext;
use Quantum\Di\Di;

/**
 * Class InitConsoleStage
 *
 * Runs first in the console boot, before the helpers are loaded, so it declares no boot events.
 * The core command registration and ResolveEnvironmentStage read the application and the input it registers.
 *
 * @implements StageInterface<AppContext>
 * @package Quantum\App
 */
class InitConsoleStage implements StageInterface
{
    public function process(AppContext $context): void
    {
        if (!Di::isRegistered(Application::class)) {
            Di::register(Application::class);
        }

        if (!Di::isRegistered(OutputInterface::class)) {
            Di::register(ConsoleOutput::class, OutputInterface::class);
        }

        // An instance, not a registration: the container would autowire the ?array $argv parameter as an empty array
        if (!Di::isRegistered(InputInterface::class)) {
            Di::set(InputInterface::class, new ArgvInput());
        }
    }
}
