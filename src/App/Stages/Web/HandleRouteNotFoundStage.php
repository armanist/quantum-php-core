<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\App\Stages\Web;

use Quantum\Config\Exceptions\ConfigException;
use Quantum\App\Contracts\WebStageInterface;
use Quantum\App\Exceptions\BaseException;
use Quantum\App\Adapters\Web\WebContext;
use Quantum\Di\Exceptions\DiException;
use ReflectionException;

/**
 * Class HandleRouteNotFoundStage
 * @package Quantum\App
 */
class HandleRouteNotFoundStage implements WebStageInterface
{
    /**
     * Answers a request that matched no route with the page not found response and stops the remaining stages
     * @throws ConfigException|BaseException|DiException|ReflectionException
     */
    public function process(WebContext $context): void
    {
        if ($context->isStopped() || $context->getMatchedRoute() !== null) {
            return;
        }

        $context->setResponse(page_not_found_response($context->getRequest()));
        $context->stop();
    }
}
