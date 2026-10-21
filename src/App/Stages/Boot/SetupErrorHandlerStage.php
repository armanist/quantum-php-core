<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\App\Stages\Boot;

use Quantum\Config\Exceptions\ConfigException;
use Quantum\Logger\Factories\LoggerFactory;
use Quantum\App\Contracts\StageInterface;
use Quantum\App\Exceptions\BaseException;
use Quantum\Di\Exceptions\DiException;
use Quantum\Tracer\ErrorHandler;
use Quantum\App\AppContext;
use ReflectionException;
use Quantum\Di\Di;

/**
 * Class SetupErrorHandlerStage
 * @implements StageInterface<AppContext>
 * @package Quantum\App
 */
class SetupErrorHandlerStage implements StageInterface
{
    /**
     * @throws ConfigException|DiException|BaseException|ReflectionException
     */
    public function process(AppContext $context): void
    {
        if (!Di::isRegistered(ErrorHandler::class)) {
            Di::register(ErrorHandler::class);
        }

        Di::get(ErrorHandler::class)->setup(LoggerFactory::get());
    }
}
