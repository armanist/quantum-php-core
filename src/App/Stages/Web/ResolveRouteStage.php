<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\App\Stages\Web;

use Quantum\Router\Exceptions\RouteException;
use Quantum\App\Contracts\WebStageInterface;
use Quantum\App\Exceptions\BaseException;
use Quantum\App\Adapters\Web\WebContext;
use Quantum\Di\Exceptions\DiException;
use Quantum\Router\RouteFinder;

/**
 * Class ResolveRouteStage
 * @package Quantum\App
 */
class ResolveRouteStage implements WebStageInterface
{
    /**
     * Finds the route for the current request and stores it on the request, or null when nothing matches
     * @throws RouteException|BaseException|DiException
     */
    public function process(WebContext $context): void
    {
        if ($context->isStopped()) {
            return;
        }

        $request = $context->getRequest();

        $request->setMatchedRoute((new RouteFinder($context->getRoutes()))->find($request));
    }
}
