<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\App\Adapters\Console;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Application;
use Quantum\App\Enums\ExitCode;
use Quantum\App\AppContext;
use Throwable;

/**
 * Class ConsoleContext
 *
 * Carries the state of a single console run through the console stages.
 * Unlike AppContext, which is immutable, the exit code is set by the stages.
 *
 * @package Quantum\App
 */
class ConsoleContext
{
    private AppContext $appContext;

    private Application $application;

    private InputInterface $input;

    private OutputInterface $output;

    private ?Throwable $bootFailure;

    private int $exitCode = ExitCode::SUCCESS;

    public function __construct(
        AppContext $appContext,
        Application $application,
        InputInterface $input,
        OutputInterface $output,
        ?Throwable $bootFailure = null
    ) {
        $this->appContext = $appContext;
        $this->application = $application;
        $this->input = $input;
        $this->output = $output;
        $this->bootFailure = $bootFailure;
    }

    public function getAppContext(): AppContext
    {
        return $this->appContext;
    }

    public function getApplication(): Application
    {
        return $this->application;
    }

    public function getInput(): InputInterface
    {
        return $this->input;
    }

    public function getOutput(): OutputInterface
    {
        return $this->output;
    }

    /**
     * The failure of the boot that the console run continued after, if there was one
     */
    public function getBootFailure(): ?Throwable
    {
        return $this->bootFailure;
    }

    public function getExitCode(): int
    {
        return $this->exitCode;
    }

    public function setExitCode(int $exitCode): void
    {
        $this->exitCode = $exitCode;
    }
}
