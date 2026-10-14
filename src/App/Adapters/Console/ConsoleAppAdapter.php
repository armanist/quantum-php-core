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
use Symfony\Component\Console\Output\ConsoleOutput;
use Quantum\App\Stages\Boot\SetupErrorHandlerStage;
use Quantum\App\Stages\Boot\LoadEnvironmentStage;
use Quantum\App\Stages\Console\RunCommandStage;
use Symfony\Component\Console\Input\ArgvInput;
use Quantum\App\Stages\Boot\LoadAppConfigStage;
use Quantum\App\Stages\Boot\LoadHelpersStage;
use Symfony\Component\Console\Application;
use Quantum\App\Contracts\AppInterface;
use Quantum\App\Enums\ExitCode;
use Quantum\App\BootPipeline;
use Quantum\App\AppContext;
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

    protected ArgvInput $input;

    protected ConsoleOutput $output;

    protected Application $application;

    protected ?Throwable $bootFailure = null;

    public function __construct(AppContext $context)
    {
        $this->context = $context;

        $this->input = new ArgvInput();
        $this->output = new ConsoleOutput();

        $this->application = $this->createApplication(self::UNKNOWN, self::UNKNOWN);

        try {
            $pipeline = new BootPipeline([
                new LoadHelpersStage(),
                new LoadEnvironmentStage(),
                new LoadAppConfigStage(),
                new SetupErrorHandlerStage(),
            ]);

            $pipeline->run($this->context);

            environment()->setMutable(true);

            $this->application->setName(config()->get('app.name') ?? self::UNKNOWN);
            $this->application->setVersion(config()->get('app.version') ?? self::UNKNOWN);
        } catch (Throwable $exception) {
            $this->bootFailure = $exception;
        }
    }

    public function createApplication(string $name, string $version): Application
    {
        return new Application($name, $version);
    }

    /**
    * @throws Exception|Throwable
    */
    public function start(): ?int
    {
        $context = new ConsoleContext($this->context, $this->application, $this->input, $this->output, $this->bootFailure);

        $pipeline = new ConsolePipeline([
            new RegisterCoreCommandsStage(),
            new RegisterAppCommandsStage(),
            new RunCommandStage(),
        ]);

        try {
            $pipeline->run($context);
        } catch (Throwable $exception) {
            throw $this->bootFailure ?? $exception;
        }

        return $context->getExitCode() ?: ExitCode::SUCCESS;
    }
}
