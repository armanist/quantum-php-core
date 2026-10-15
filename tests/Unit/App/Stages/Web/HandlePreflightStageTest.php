<?php

namespace Quantum\Tests\Unit\App\Stages\Web;

use Quantum\App\Stages\Web\HandlePreflightStage;
use Quantum\Tests\Unit\AppTestCase;
use Quantum\Http\Enums\StatusCode;
use Quantum\App\Adapters\Web\WebContext;
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
}
