<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\App\Contracts;

use Quantum\App\Adapters\Web\WebContext;

/**
 * Interface WebStageInterface
 * @package Quantum\App
 */
interface WebStageInterface
{
    /**
     * Processes a single web stage
     */
    public function process(WebContext $context): void;
}
