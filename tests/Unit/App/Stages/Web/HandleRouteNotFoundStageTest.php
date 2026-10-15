<?php

namespace Quantum\Tests\Unit\App\Stages\Web;

use Quantum\App\Stages\Web\HandleRouteNotFoundStage;
use Quantum\Tests\Unit\AppTestCase;
use Quantum\Http\Enums\ContentType;
use Quantum\Http\Enums\StatusCode;
use Quantum\App\Adapters\Web\WebContext;
use Quantum\Router\MatchedRoute;
use Quantum\Router\Route;
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

    public function testHandleRouteNotFoundStageIgnoresMatchedRoute(): void
    {
        $this->webContext->setMatchedRoute(
            new MatchedRoute(new Route(['GET'], '/test/am/tests', 'TestController', 'tests'), [])
        );

        (new HandleRouteNotFoundStage())->process($this->webContext);

        $this->assertNull($this->webContext->getResponse());
        $this->assertFalse($this->webContext->isStopped());
    }

    public function testHandleRouteNotFoundStageSkipsWhenContextIsStopped(): void
    {
        request()->create('GET', '/non-existing-uri');

        $this->webContext->stop();

        (new HandleRouteNotFoundStage())->process($this->webContext);

        $this->assertNull($this->webContext->getResponse());
    }
}
