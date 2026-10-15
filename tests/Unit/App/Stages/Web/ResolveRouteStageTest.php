<?php

namespace Quantum\Tests\Unit\App\Stages\Web;

use Quantum\App\Stages\Web\ResolveRouteStage;
use Quantum\Tests\Unit\AppTestCase;
use Quantum\App\Adapters\Web\WebContext;
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

    public function testResolveRouteStageSkipsWhenContextIsStopped(): void
    {
        request()->create('GET', '/test/am/tests');

        $this->webContext->stop();

        (new ResolveRouteStage())->process($this->webContext);

        $this->assertNull($this->webContext->getMatchedRoute());
        $this->assertNull(request()->getMatchedRoute());
    }
}
