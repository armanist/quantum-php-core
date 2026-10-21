<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\App\Adapters\Console\Stages\Boot;

use Symfony\Component\Console\Input\InputInterface;
use Quantum\Environment\Exceptions\EnvException;
use Symfony\Component\Console\Application;
use Dotenv\Exception\InvalidFileException;
use Quantum\App\Contracts\StageInterface;
use Quantum\App\Exceptions\BaseException;
use Quantum\Di\Exceptions\DiException;
use Quantum\Environment\Environment;
use Quantum\Console\CliCommand;
use Quantum\App\AppContext;
use Quantum\Di\Di;

/**
 * Class ResolveEnvironmentStage
 *
 * Lets a command that does not need the environment run when the .env file is missing or malformed:
 * the console boot stops before the environment is loaded and before the app commands are registered.
 * Every other command fails with the exception of the environment file.
 * It runs after the core commands are registered, so they are the ones it can find.
 *
 * @implements StageInterface<AppContext>
 * @package Quantum\App
 */
class ResolveEnvironmentStage implements StageInterface
{
    /**
     * @throws EnvException|InvalidFileException|DiException|BaseException
     */
    public function process(AppContext $context): void
    {
        try {
            (new Environment())->load();
        } catch (EnvException|InvalidFileException $exception) {
            if ($this->requiresEnvironment()) {
                throw $exception;
            }

            $context->stop();
        }
    }

    /**
     * A CliCommand tells it itself, other commands (Symfony's list or help) do not need it,
     * and an unknown or missing command is treated as needing it
     * @throws DiException
     */
    private function requiresEnvironment(): bool
    {
        $application = Di::get(Application::class);
        $commandName = Di::get(InputInterface::class)->getFirstArgument();

        if ($commandName === null || !$application->has($commandName)) {
            return true;
        }

        $command = $application->get($commandName);

        return $command instanceof CliCommand && $command->requiresEnvironment();
    }
}
