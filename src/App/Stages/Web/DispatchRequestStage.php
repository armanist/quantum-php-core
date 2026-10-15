<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\App\Stages\Web;

use Quantum\Middleware\Exceptions\MiddlewareException;
use Quantum\App\Contracts\WebStageInterface;
use Quantum\App\Exceptions\BaseException;
use Quantum\Middleware\MiddlewareManager;
use Quantum\Router\RouteDispatcher;
use Quantum\App\Adapters\Web\WebContext;
use Quantum\Http\Response;
use Quantum\Http\Request;
use RuntimeException;

/**
 * Class DispatchRequestStage
 *
 * Produces the response of a matched request: the cached view if there is one, otherwise the
 * dispatched route, in both cases wrapped by the module and framework middlewares.
 *
 * @package Quantum\App
 */
class DispatchRequestStage implements WebStageInterface
{
    /**
     * @throws MiddlewareException|BaseException
     */
    public function process(WebContext $context): void
    {
        if ($context->isStopped()) {
            return;
        }

        $matchedRoute = $context->getMatchedRoute();
        $viewCache = $context->getViewCache();

        if ($matchedRoute === null) {
            throw new RuntimeException('Matched route is not set.');
        }

        if ($viewCache === null) {
            throw new RuntimeException('View cache is not set.');
        }

        $terminal = fn (Request $request): Response => $viewCache->getCachedResponse($request->getUri() ?? '')
            ?? (new RouteDispatcher())->dispatch($matchedRoute, $request);

        $context->setResponse((new MiddlewareManager($matchedRoute))->applyMiddlewares(request(), $terminal));
    }
}
