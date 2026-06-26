<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\App\Contracts;

use Quantum\App\AppContext;

/**
 * Interface BootStageInterface
 */
interface BootStageInterface
{
    /**
     * Processes a single boot stage
     */
    public function process(AppContext $context): void;
}
