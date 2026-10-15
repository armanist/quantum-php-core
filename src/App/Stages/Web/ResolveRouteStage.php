<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\App\Stages\Web;

use Quantum\App\Contracts\WebStageInterface;
use Quantum\Router\Exceptions\RouteException;
use Quantum\App\Exceptions\BaseException;
use Quantum\App\Adapters\Web\WebContext;
use Quantum\Router\RouteFinder;

/**
 * Class ResolveRouteStage
 * @package Quantum\App
 */
class ResolveRouteStage implements WebStageInterface
{
    /**
     * Finds the route for the current request and stores it on the request
     * @throws RouteException|BaseException
     */
    public function process(WebContext $context): void
    {
        if ($context->isStopped()) {
            return;
        }

        $request = $context->getRequest();

        $matchedRoute = (new RouteFinder($context->getRoutes()))->find($request);

        if ($matchedRoute === null) {
            return;
        }

        $request->setMatchedRoute($matchedRoute);
    }
}
