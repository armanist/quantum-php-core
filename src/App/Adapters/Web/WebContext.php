<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\App\Adapters\Web;

use Quantum\ResourceCache\ViewCache;
use Quantum\Router\RouteCollection;
use Quantum\Router\MatchedRoute;
use Quantum\App\AppContext;
use Quantum\Http\Response;
use Quantum\Http\Request;

/**
 * Class WebContext
 *
 * Carries the state of a single web request through the web stages.
 * It is an AppContext (same base directory and container) that the stages mutate, and it holds
 * the response of the request: the one the stages set, which is what is sent.
 * The request and the routes are read from the container, and the matched route from the request.
 *
 * @package Quantum\App
 */
class WebContext extends AppContext
{
    private ?Response $response = null;

    private ?ViewCache $viewCache = null;

    private bool $stopped = false;

    public function __construct(AppContext $appContext)
    {
        parent::__construct($appContext->getBaseDir(), $appContext->getContainer());
    }

    public function getRequest(): Request
    {
        return $this->resolveFromContainer(Request::class);
    }

    public function getRoutes(): RouteCollection
    {
        return $this->resolveFromContainer(RouteCollection::class);
    }

    public function getMatchedRoute(): ?MatchedRoute
    {
        return $this->getRequest()->getMatchedRoute();
    }

    public function getResponse(): ?Response
    {
        return $this->response;
    }

    public function setResponse(Response $response): void
    {
        $this->response = $response;
    }

    public function getViewCache(): ?ViewCache
    {
        return $this->viewCache;
    }

    public function setViewCache(ViewCache $viewCache): void
    {
        $this->viewCache = $viewCache;
    }

    /**
     * Marks the request as finished early, so stages that compute a response skip their work.
     * The stage that sends the response still runs.
     */
    public function stop(): void
    {
        $this->stopped = true;
    }

    public function isStopped(): bool
    {
        return $this->stopped;
    }
}
