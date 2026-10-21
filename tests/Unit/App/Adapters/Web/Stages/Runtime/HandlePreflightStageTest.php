<?php

namespace Quantum\Tests\Unit\App\Adapters\Web\Stages\Runtime;

use Quantum\App\Adapters\Web\Stages\Runtime\HandlePreflightStage;
use Quantum\Tests\Unit\AppTestCase;
use Quantum\Http\Enums\StatusCode;
use Quantum\App\Adapters\Web\WebContext;
use Quantum\Di\DiContainer;
use Quantum\App\AppContext;
use Quantum\Http\Request;
use Quantum\App\App;

class HandlePreflightStageTest extends AppTestCase
{
    private WebContext $webContext;

    public function setUp(): void
    {
        parent::setUp();

        $this->webContext = new WebContext(App::getContext());
    }

    public function testHandlePreflightStageRespondsNoContentToOptionsRequest(): void
    {
        request()->create('OPTIONS', '/test/am/tests');

        (new HandlePreflightStage())->process($this->webContext);

        $this->assertSame(response(), $this->webContext->getResponse());
        $this->assertSame(StatusCode::NO_CONTENT, $this->webContext->getResponse()->getStatusCode());
        $this->assertTrue($this->webContext->isStopped());
    }

    public function testHandlePreflightStageIgnoresOtherMethods(): void
    {
        request()->create('GET', '/test/am/tests');

        (new HandlePreflightStage())->process($this->webContext);

        $this->assertNull($this->webContext->getResponse());
        $this->assertFalse($this->webContext->isStopped());
    }

    public function testHandlePreflightStageReadsTheRequestFromTheContext(): void
    {
        request()->create('GET', '/test/am/tests');

        $request = new Request();
        $request->create('OPTIONS', '/test/am/tests');

        $container = new DiContainer();
        $container->set(Request::class, $request);

        $webContext = new WebContext(new AppContext(PROJECT_ROOT, $container));

        (new HandlePreflightStage())->process($webContext);

        $this->assertTrue($webContext->isStopped());
    }
}
