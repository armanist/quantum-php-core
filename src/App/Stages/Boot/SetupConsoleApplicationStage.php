<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\App\Stages\Boot;

use Quantum\App\Contracts\BootStageInterface;
use Symfony\Component\Console\Application;
use Quantum\App\AppContext;
use Quantum\Di\Di;

/**
 * Class SetupConsoleApplicationStage
 * @package Quantum\App
 */
class SetupConsoleApplicationStage implements BootStageInterface
{
    public function process(AppContext $context): void
    {
        $application = Di::get(Application::class);

        $application->setName(config()->get('app.name') ?? 'UNKNOWN');
        $application->setVersion(config()->get('app.version') ?? 'UNKNOWN');
    }
}
