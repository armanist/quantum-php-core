<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\App\Stages\Boot;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\ArgvInput;
use Quantum\App\Contracts\BootStageInterface;
use Symfony\Component\Console\Application;
use Quantum\App\AppContext;
use Quantum\Di\Di;

/**
 * Class InitConsoleStage
 *
 * Runs before the helpers are loaded, so that the console application, input and output exist
 * even when a helper file fails; for the same reason it declares no boot events.
 *
 * @package Quantum\App
 */
class InitConsoleStage implements BootStageInterface
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
