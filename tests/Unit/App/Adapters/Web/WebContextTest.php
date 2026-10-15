<?php

namespace Quantum\Tests\Unit\App\Adapters\Web;

use Quantum\App\Adapters\Web\WebContext;
use Quantum\ResourceCache\ViewCache;
use Quantum\Router\RouteCollection;
use Quantum\Tests\Unit\AppTestCase;
use Quantum\Router\MatchedRoute;
use Quantum\Di\DiContainer;
use Quantum\App\AppContext;
use Quantum\Http\Request;
use Quantum\Router\Route;
use Quantum\App\App;
use Quantum\Di\Di;
use Mockery;

class WebContextTest extends AppTestCase
{
    private AppContext $appContext;

    private WebContext $webContext;

    public function setUp(): void
    {
        parent::setUp();

        $this->appContext = App::getContext();
        $this->webContext = new WebContext($this->appContext);
    }

    public function testWebContextIsAnAppContextBuiltFromTheOneItIsGiven(): void
    {
        $container = new DiContainer();

        $webContext = new WebContext(new AppContext('/base/dir', $container));

        $this->assertInstanceOf(AppContext::class, $webContext);
        $this->assertSame('/base/dir', $webContext->getBaseDir());
        $this->assertSame($container, $webContext->getContainer());
    }

    public function testWebContextExposesRequestAndRoutesFromTheContainer(): void
    {
        $container = new DiContainer();
        $request = Mockery::mock(Request::class);
        $routes = new RouteCollection();

        $container->set(Request::class, $request);
        $container->set(RouteCollection::class, $routes);

        $webContext = new WebContext(new AppContext('/base/dir', $container));

        $this->assertSame($request, $webContext->getRequest());
        $this->assertSame($routes, $webContext->getRoutes());
    }

    public function testWebContextReadsMatchedRouteFromTheRequest(): void
    {
        $this->assertNull($this->webContext->getMatchedRoute());

        $matchedRoute = new MatchedRoute(new Route(['GET'], '/test/am/tests', 'TestController', 'tests'), []);

        request()->setMatchedRoute($matchedRoute);

        $this->assertSame($matchedRoute, $this->webContext->getMatchedRoute());
    }

    public function testWebContextStoresResponse(): void
    {
        $this->assertNull($this->webContext->getResponse());

        $this->webContext->setResponse(response());

        $this->assertSame(response(), $this->webContext->getResponse());
    }

    public function testWebContextStoresViewCache(): void
    {
        $this->assertNull($this->webContext->getViewCache());

        if (!Di::isRegistered(ViewCache::class)) {
            Di::register(ViewCache::class);
        }

        $viewCache = Di::get(ViewCache::class);

        $this->webContext->setViewCache($viewCache);

        $this->assertSame($viewCache, $this->webContext->getViewCache());
    }

    public function testWebContextIsNotStoppedByDefault(): void
    {
        $this->assertFalse($this->webContext->isStopped());
    }

    public function testWebContextStopMarksContextStopped(): void
    {
        $this->webContext->stop();

        $this->assertTrue($this->webContext->isStopped());
    }
}
