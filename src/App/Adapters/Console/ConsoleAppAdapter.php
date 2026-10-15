<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\App\Adapters\Console;

use Quantum\App\Stages\Console\RegisterCoreCommandsStage;
use Quantum\App\Stages\Console\RegisterAppCommandsStage;
use Symfony\Component\Console\Output\OutputInterface;
use Quantum\App\Stages\Boot\SetupErrorHandlerStage;
use Symfony\Component\Console\Input\InputInterface;
use Quantum\App\Stages\Boot\LoadEnvironmentStage;
use Quantum\App\Stages\Console\RunCommandStage;
use Quantum\App\Stages\Boot\LoadAppConfigStage;
use Quantum\App\Stages\Boot\InitConsoleStage;
use Quantum\App\Stages\Boot\LoadHelpersStage;
use Symfony\Component\Console\Application;
use Quantum\App\Contracts\AppInterface;
use Quantum\App\Enums\ExitCode;
use Quantum\App\BootPipeline;
use Quantum\App\AppContext;
use Quantum\Di\Di;
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
    private const UNKNOWN = 'UNKNOWN';

    protected AppContext $context;

    protected ?Throwable $bootFailure = null;

    public function __construct(AppContext $context)
    {
        $this->context = $context;

        try {
            $pipeline = new BootPipeline([
                new LoadHelpersStage(),
                new InitConsoleStage(),
                new LoadEnvironmentStage(),
                new LoadAppConfigStage(),
                new SetupErrorHandlerStage(),
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
        $pipeline = new ConsolePipeline([
            new RegisterCoreCommandsStage(),
            new RegisterAppCommandsStage(),
            new RunCommandStage(),
        ]);

        try {
            $context = new ConsoleContext(
                $this->context,
                Di::get(Application::class, [
                    config()->get('app.name') ?? self::UNKNOWN,
                    config()->get('app.version') ?? self::UNKNOWN,
                ]),
                Di::get(InputInterface::class),
                Di::get(OutputInterface::class),
                $this->bootFailure
            );

            $pipeline->run($context);
        } catch (Throwable $exception) {
            throw $this->bootFailure ?? $exception;
        }

        return $context->getExitCode() ?: ExitCode::SUCCESS;
    }
}
