<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\App\Adapters\Web\Stages\Boot;

use Quantum\App\Contracts\StageInterface;
use Quantum\App\AppContext;
use Quantum\Http\Response;
use Quantum\Http\Request;
use Quantum\Di\Di;

/**
 * Class InitHttpStage
 * @implements StageInterface<AppContext>
 * @package Quantum\App
 */
class InitHttpStage implements StageInterface
{
    public const BEFORE = 'boot.http.before';

    public const AFTER = 'boot.http.after';

    public function process(AppContext $context): void
    {
        if (!Di::isRegistered(Request::class)) {
            Di::register(Request::class);
        }

        if (!Di::isRegistered(Response::class)) {
            Di::register(Response::class);
        }
    }
}
