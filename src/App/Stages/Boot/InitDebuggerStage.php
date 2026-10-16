<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\App\Stages\Boot;

use Quantum\App\Contracts\StageInterface;
use Quantum\App\AppContext;

/**
 * Class InitDebuggerStage
 * @implements StageInterface<AppContext>
 * @package Quantum\App
 */
class InitDebuggerStage implements StageInterface
{
    public function process(AppContext $context): void
    {
        debugbar()->initStore();
    }
}
