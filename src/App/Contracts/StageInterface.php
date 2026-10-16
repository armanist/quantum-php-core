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
 * Interface StageInterface
 *
 * A stage of a pipeline. A stage states the context it works with in its class docblock,
 * for example `@implements StageInterface<WebContext>`.
 *
 * @template TContext of AppContext
 * @package Quantum\App
 */
interface StageInterface
{
    /**
     * Processes a single stage
     * @param TContext $context
     */
    public function process(AppContext $context): void;
}
