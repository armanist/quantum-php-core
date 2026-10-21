<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\App\Adapters\Web\Stages\Runtime;

use Quantum\Middleware\Exceptions\MiddlewareException;
use Quantum\App\Contracts\StageInterface;
use Quantum\App\Exceptions\BaseException;
use Quantum\Middleware\MiddlewareManager;
use Quantum\App\Adapters\Web\WebContext;
use Quantum\Di\Exceptions\DiException;
use Quantum\Router\RouteDispatcher;
use Quantum\App\AppContext;
use Quantum\Http\Response;
use Quantum\Http\Request;
use RuntimeException;

/**
 * Class DispatchRequestStage
 *
 * Produces the response of a matched request: the cached view if there is one, otherwise the
 * dispatched route, in both cases wrapped by the module and framework middlewares.
 *
 * @implements StageInterface<WebContext>
 * @package Quantum\App
 */
class DispatchRequestStage implements StageInterface
{
    /**
     * @param WebContext $context
     * @throws MiddlewareException|BaseException|DiException
     */
    public function process(AppContext $context): void
    {
        $matchedRoute = $context->getMatchedRoute();

        if ($matchedRoute === null) {
            throw new RuntimeException('Matched route is not set.');
        }

        $viewCache = $context->getViewCache();

        $terminal = fn (Request $request): Response => $viewCache->getCachedResponse($request->getUri() ?? '')
            ?? (new RouteDispatcher())->dispatch($matchedRoute, $request);

        $context->setResponse((new MiddlewareManager($matchedRoute))->applyMiddlewares($context->getRequest(), $terminal));
    }
}
