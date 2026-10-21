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
use Quantum\App\AppContext;

/**
 * Class ConsoleContext
 *
 * Carries the state of a single console run through the console stages.
 * It is an AppContext (same base directory and container) whose exit code is set by the stages.
 * The Symfony application, input and output are read from the container.
 *
 * @package Quantum\App
 */
class ConsoleContext extends AppContext
{
    public function __construct(AppContext $appContext)
    {
        parent::__construct($appContext->getBaseDir(), $appContext->getContainer());
    }

    public function getApplication(): Application
    {
        return $this->resolveFromContainer(Application::class);
    }

    public function getInput(): InputInterface
    {
        return $this->resolveFromContainer(InputInterface::class);
    }

    public function getOutput(): OutputInterface
    {
        return $this->resolveFromContainer(OutputInterface::class);
    }
}
