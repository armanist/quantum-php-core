<?php

namespace Quantum\Tests\Unit\App\Adapters\Web\Stages\Runtime;

use Quantum\App\Adapters\Web\Stages\Runtime\HandleRouteNotFoundStage;
use Quantum\Tests\Unit\AppTestCase;
use Quantum\Http\Enums\ContentType;
use Quantum\Http\Enums\StatusCode;
use Quantum\App\Adapters\Web\WebContext;
use Quantum\Router\MatchedRoute;
use Quantum\Di\DiContainer;
use Quantum\App\AppContext;
use Quantum\Router\Route;
use Quantum\Http\Request;
use Quantum\App\App;

class HandleRouteNotFoundStageTest extends AppTestCase
{
    private WebContext $webContext;

    public function setUp(): void
    {
        parent::setUp();

        $this->webContext = new WebContext(App::getContext());
    }

    public function testHandleRouteNotFoundStageRespondsNotFoundWhenNoRouteMatched(): void
    {
        request()->create('GET', '/non-existing-uri');

        (new HandleRouteNotFoundStage())->process($this->webContext);

        $this->assertSame(response(), $this->webContext->getResponse());
        $this->assertSame(StatusCode::NOT_FOUND, $this->webContext->getResponse()->getStatusCode());
        $this->assertSame(ContentType::HTML, $this->webContext->getResponse()->getContentType());
        $this->assertTrue($this->webContext->isStopped());
    }

    public function testHandleRouteNotFoundStageRespondsWithJsonForJsonRequests(): void
    {
        request()->create('GET', '/non-existing-uri', [], ['Accept' => 'application/json']);

        (new HandleRouteNotFoundStage())->process($this->webContext);

        $this->assertSame(StatusCode::NOT_FOUND, $this->webContext->getResponse()->getStatusCode());
        $this->assertSame(ContentType::JSON, $this->webContext->getResponse()->getContentType());
        $this->assertStringContainsString('Page not found', $this->webContext->getResponse()->getContent());
        $this->assertTrue($this->webContext->isStopped());
    }

    public function testHandleRouteNotFoundStageRespondsWithJsonWhenAcceptListsJson(): void
    {
        request()->create('GET', '/non-existing-uri', [], ['Accept' => 'application/json, text/plain, */*']);

        (new HandleRouteNotFoundStage())->process($this->webContext);

        $this->assertSame(StatusCode::NOT_FOUND, $this->webContext->getResponse()->getStatusCode());
        $this->assertSame(ContentType::JSON, $this->webContext->getResponse()->getContentType());
        $this->assertTrue($this->webContext->isStopped());
    }

    public function testHandleRouteNotFoundStageDecidesByTheContextRequest(): void
    {
        request()->create('GET', '/non-existing-uri', [], ['Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8']);

        $request = new Request();
        $request->setHeader('Accept', 'application/json, text/plain, */*');

        $container = new DiContainer();
        $container->set(Request::class, $request);

        $webContext = new WebContext(new AppContext(PROJECT_ROOT, $container));

        (new HandleRouteNotFoundStage())->process($webContext);

        $this->assertSame(ContentType::JSON, $webContext->getResponse()->getContentType());
    }

    public function testHandleRouteNotFoundStageIgnoresMatchedRoute(): void
    {
        request()->setMatchedRoute(
            new MatchedRoute(new Route(['GET'], '/test/am/tests', 'TestController', 'tests'), [])
        );

        (new HandleRouteNotFoundStage())->process($this->webContext);

        $this->assertNull($this->webContext->getResponse());
        $this->assertFalse($this->webContext->isStopped());
    }
}
