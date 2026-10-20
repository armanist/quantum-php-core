<?php

namespace Quantum\Tests\Unit\App\Stages\Web;

use Quantum\App\Stages\Web\ResolveRouteStage;
use Quantum\Tests\Unit\AppTestCase;
use Quantum\App\Adapters\Web\WebContext;
use Quantum\Router\RouteCollection;
use Quantum\Router\MatchedRoute;
use Quantum\Di\DiContainer;
use Quantum\App\AppContext;
use Quantum\Http\Request;
use Quantum\Router\Route;
use Quantum\App\App;

class ResolveRouteStageTest extends AppTestCase
{
    private WebContext $webContext;

    public function setUp(): void
    {
        parent::setUp();

        $this->webContext = new WebContext(App::getContext());
    }

    public function testResolveRouteStageStoresMatchedRoute(): void
    {
        request()->create('GET', '/test/am/tests');

        (new ResolveRouteStage())->process($this->webContext);

        $this->assertNotNull($this->webContext->getMatchedRoute());
        $this->assertSame('tests', $this->webContext->getMatchedRoute()->getRoute()->getAction());
        $this->assertSame($this->webContext->getMatchedRoute(), request()->getMatchedRoute());
        $this->assertFalse($this->webContext->isStopped());
    }

    public function testResolveRouteStageLeavesContextEmptyWhenNoRouteMatches(): void
    {
        request()->create('GET', '/non-existing-uri');

        (new ResolveRouteStage())->process($this->webContext);

        $this->assertNull($this->webContext->getMatchedRoute());
        $this->assertNull(request()->getMatchedRoute());
        $this->assertFalse($this->webContext->isStopped());
    }

    public function testResolveRouteStageClearsARouteSetBeforeItWhenNoRouteMatches(): void
    {
        request()->create('GET', '/non-existing-uri');
        request()->setMatchedRoute(new MatchedRoute(new Route(['GET'], '/test/am/tests', 'TestController', 'tests'), []));

        (new ResolveRouteStage())->process($this->webContext);

        $this->assertNull($this->webContext->getMatchedRoute());
        $this->assertNull(request()->getMatchedRoute());
    }

    public function testResolveRouteStageReadsTheRequestAndRoutesFromTheContext(): void
    {
        request()->create('GET', '/test/am/tests');

        $request = new Request();
        $request->create('GET', '/context/route');

        $routes = new RouteCollection();
        $routes->add(new Route(['GET'], '/context/route', 'TestController', 'contextAction'));

        $container = new DiContainer();
        $container->set(Request::class, $request);
        $container->set(RouteCollection::class, $routes);

        (new ResolveRouteStage())->process(new WebContext(new AppContext(PROJECT_ROOT, $container)));

        $this->assertSame('contextAction', $request->getMatchedRoute()->getRoute()->getAction());
        $this->assertNull(request()->getMatchedRoute());
    }
}
