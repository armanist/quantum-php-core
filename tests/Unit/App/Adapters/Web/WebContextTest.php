<?php

namespace Quantum\Tests\Unit\App\Adapters\Web;

use Quantum\ResourceCache\ViewCache;
use Quantum\Tests\Unit\AppTestCase;
use Quantum\Router\MatchedRoute;
use Quantum\App\Adapters\Web\WebContext;
use Quantum\App\AppContext;
use Quantum\Router\Route;
use Quantum\App\App;
use Quantum\Di\Di;

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

    public function testWebContextExposesAppContext(): void
    {
        $this->assertSame($this->appContext, $this->webContext->getAppContext());
    }

    public function testWebContextStoresMatchedRoute(): void
    {
        $this->assertNull($this->webContext->getMatchedRoute());

        $matchedRoute = new MatchedRoute(new Route(['GET'], '/test/am/tests', 'TestController', 'tests'), []);

        $this->webContext->setMatchedRoute($matchedRoute);

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
