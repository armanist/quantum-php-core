<?php

namespace Quantum\Tests\Unit\App\Adapters\Web\Stages\Runtime;

use Quantum\App\Adapters\Web\Stages\Runtime\SendResponseStage;
use Quantum\Http\Exceptions\HttpException;
use Quantum\Tests\Unit\AppTestCase;
use Quantum\App\Adapters\Web\WebContext;
use Quantum\Router\MatchedRoute;
use Quantum\Di\DiContainer;
use Quantum\App\AppContext;
use Quantum\Config\Config;
use Quantum\Router\Route;
use Quantum\Http\Response;
use Quantum\Http\Request;
use Quantum\App\App;
use RuntimeException;

class SendResponseStageTest extends AppTestCase
{
    private WebContext $webContext;

    public function setUp(): void
    {
        parent::setUp();

        $this->webContext = new WebContext(App::getContext());
    }

    public function testSendResponseStageSendsTheResponse(): void
    {
        $this->webContext->setResponse(response()->html('<p>hello</p>'));

        ob_start();
        (new SendResponseStage())->process($this->webContext);
        $output = ob_get_clean();

        $this->assertSame('<p>hello</p>', $output);
    }

    public function testSendResponseStageAppliesCorsHeaders(): void
    {
        $this->assertFalse(config()->has('cors'));

        $this->webContext->setResponse(response()->html(''));

        ob_start();
        (new SendResponseStage())->process($this->webContext);
        ob_end_clean();

        $this->assertTrue(config()->has('cors'));
        $this->assertSame('*', response()->getHeader('Access-Control-Allow-Origin'));
        $this->assertSame('*', response()->getHeader('Access-Control-Allow-Methods'));
    }

    public function testSendResponseStageCleansUpRequestAfterSending(): void
    {
        $this->prepareMatchedRequest();

        $this->webContext->setResponse(response()->html(''));

        ob_start();
        (new SendResponseStage())->process($this->webContext);
        ob_end_clean();

        $this->assertNull(request()->getMatchedRoute());
        $this->assertNull(request()->getUri());
    }

    public function testSendResponseStageCleansUpRequestWhenSendFails(): void
    {
        $this->prepareMatchedRequest();

        response()->setHeader('X-Test', '1');
        response()->json(['foo' => 'bar']);

        $this->webContext->setResponse(new class () extends Response {
            public function send(): void
            {
                throw new HttpException('boom');
            }
        });

        try {
            (new SendResponseStage())->process($this->webContext);
            $this->fail('Expected response sending to fail.');
        } catch (HttpException $exception) {
            $this->assertSame('boom', $exception->getMessage());
        }

        $this->assertNull(request()->getMatchedRoute());
        $this->assertNull(request()->getUri());
        $this->assertSame(['foo' => 'bar'], response()->all());
        $this->assertSame('1', response()->getHeader('X-Test'));
        $this->assertSame(200, response()->getStatusCode());
    }

    public function testSendResponseStageFailsAndCleansUpWithoutResponse(): void
    {
        $this->prepareMatchedRequest();

        try {
            (new SendResponseStage())->process($this->webContext);
            $this->fail('Expected the stage to fail without a response.');
        } catch (RuntimeException $exception) {
            $this->assertSame('The request has no response to send.', $exception->getMessage());
        }

        $this->assertNull(request()->getMatchedRoute());
        $this->assertNull(request()->getUri());
    }

    public function testSendResponseStageReadsTheConfigAndRequestFromTheContext(): void
    {
        $this->prepareMatchedRequest();

        $request = new Request();
        $request->create('GET', '/context/route');
        $request->setMatchedRoute(new MatchedRoute(new Route(['GET'], '/context/route', 'TestController', 'tests'), []));

        $config = new Config();
        $config->set('cors', ['X-Context-Cors' => 'yes']);

        $container = new DiContainer();
        $container->set(Request::class, $request);
        $container->set(Config::class, $config);

        $webContext = new WebContext(new AppContext(PROJECT_ROOT, $container));
        $webContext->setResponse(response()->html(''));

        ob_start();
        (new SendResponseStage())->process($webContext);
        ob_end_clean();

        $this->assertSame('yes', response()->getHeader('X-Context-Cors'));
        $this->assertFalse(config()->has('cors'));
        $this->assertNull($request->getMatchedRoute());
        $this->assertNull($request->getUri());
        $this->assertNotNull(request()->getMatchedRoute());
        $this->assertNotNull(request()->getUri());
    }

    private function prepareMatchedRequest(): void
    {
        request()->create('GET', '/test/am/tests');

        request()->setMatchedRoute(new MatchedRoute(
            new Route(['GET'], '/test/am/tests', 'TestController', 'tests'),
            []
        ));
    }
}
