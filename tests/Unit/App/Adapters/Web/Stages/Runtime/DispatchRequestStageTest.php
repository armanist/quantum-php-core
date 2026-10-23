<?php

namespace Quantum\Tests\_root\modules\Test\Middlewares {
    use Quantum\Middleware\Middleware;
    use Quantum\Http\Request;
    use Quantum\Http\Response;
    use Closure;

    class DispatchStageMwPassThrough extends Middleware
    {
        public static array $calls = [];

        public function apply(Request $request, Closure $next): Response
        {
            self::$calls[] = 'DispatchStageMwPassThrough';

            return $next($request);
        }
    }

    class DispatchStageMwRecorder extends Middleware
    {
        public static ?Request $request = null;

        public function apply(Request $request, Closure $next): Response
        {
            self::$request = $request;

            return response()->json(['status' => 'recorded']);
        }
    }

    class DispatchStageMwBlocker extends Middleware
    {
        public function apply(Request $request, Closure $next): Response
        {
            return response()->json(['status' => 'blocked']);
        }
    }
}

namespace Quantum\Tests\Unit\App\Adapters\Web\Stages\Runtime {
    use Quantum\Tests\_root\modules\Test\Middlewares\DispatchStageMwPassThrough;
    use Quantum\Tests\_root\modules\Test\Middlewares\DispatchStageMwRecorder;
    use Quantum\App\Adapters\Web\Stages\Runtime\DispatchRequestStage;
    use Quantum\App\Adapters\Web\Stages\Runtime\ResolveRouteStage;
    use Quantum\Router\Exceptions\RouteException;
    use Quantum\Di\Exceptions\DiException;
    use Quantum\Tests\Unit\AppTestCase;
    use Quantum\ResourceCache\ViewCache;
    use Quantum\App\Adapters\Web\WebContext;
    use Quantum\Router\MatchedRoute;
    use Quantum\App\Enums\AppType;
    use Quantum\Di\DiContainer;
    use Quantum\App\AppContext;
    use Quantum\Http\Request;
    use Quantum\Http\Enums\StatusCode;
    use Quantum\Router\Route;
    use Quantum\App\App;
    use RuntimeException;
    use Quantum\Di\Di;

    class DispatchRequestStageTest extends AppTestCase
    {
        private WebContext $webContext;

        public function setUp(): void
        {
            parent::setUp();

            $this->webContext = new WebContext(App::getContext());

            DispatchStageMwPassThrough::$calls = [];
        }

        public function testDispatchRequestStageDispatchesMatchedRoute(): void
        {
            $this->prepareRequest('/test/am/tests');

            (new DispatchRequestStage())->process($this->webContext);

            $this->assertSame(response(), $this->webContext->getResponse());
            $this->assertSame(StatusCode::OK, $this->webContext->getResponse()->getStatusCode());
            $this->assertSame('', $this->webContext->getResponse()->getContent());
        }

        public function testDispatchRequestStageServesViewCacheHit(): void
        {
            $this->prepareRequest('/test/am/tests');

            $uri = request()->getUri();
            $viewsDir = base_dir() . DS . 'cache' . DS . 'views';
            $moduleDir = $viewsDir . DS . 'test';
            $viewsDirExisted = is_dir($viewsDir);
            $moduleDirExisted = is_dir($moduleDir);

            $viewCache = $this->webContext->getViewCache();
            $viewCache->setup();
            $viewCache->enableCaching(true);
            $viewCache->set($uri, '<p>cached page</p>');

            try {
                (new DispatchRequestStage())->process($this->webContext);

                $this->assertSame('<p>cached page</p>', $this->webContext->getResponse()->getContent());
            } finally {
                $viewCache->delete($uri);
                $viewCache->enableCaching(false);

                if (!$moduleDirExisted && is_dir($moduleDir)) {
                    rmdir($moduleDir);
                }

                if (!$viewsDirExisted && is_dir($viewsDir)) {
                    rmdir($viewsDir);
                }
            }
        }

        public function testDispatchRequestStageRunsModuleMiddlewaresBeforeController(): void
        {
            $this->prepareRequest('/test/am/tests');

            $this->webContext->getMatchedRoute()->getRoute()->addMiddlewares(['DispatchStageMwPassThrough']);

            (new DispatchRequestStage())->process($this->webContext);

            $this->assertSame(['DispatchStageMwPassThrough'], DispatchStageMwPassThrough::$calls);
            $this->assertSame(StatusCode::OK, $this->webContext->getResponse()->getStatusCode());
        }

        public function testDispatchRequestStageLetsMiddlewareShortCircuit(): void
        {
            $this->prepareRequest('/test/am/tests');

            // The controller does not exist, so reaching the dispatcher would throw.
            $matchedRoute = new MatchedRoute($this->createRoute('tests', ['DispatchStageMwBlocker'], 'MissingController'), []);

            request()->setMatchedRoute($matchedRoute);

            (new DispatchRequestStage())->process($this->webContext);

            $this->assertStringContainsString('blocked', $this->webContext->getResponse()->getContent());
        }

        public function testDispatchRequestStagePropagatesDispatchException(): void
        {
            $this->prepareRequest('/test/Test/5');

            try {
                (new DispatchRequestStage())->process($this->webContext);
                $this->fail('Expected the dispatcher to fail for a missing controller action.');
            } catch (RouteException $exception) {
                $this->assertInstanceOf(RouteException::class, $exception);
            }

            $this->assertNull($this->webContext->getResponse());
        }

        public function testDispatchRequestStageFailsWithoutMatchedRoute(): void
        {
            $this->registerViewCache();

            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage('Matched route is not set.');

            (new DispatchRequestStage())->process($this->webContext);
        }

        public function testDispatchRequestStageFailsWithoutViewCache(): void
        {
            request()->setMatchedRoute(new MatchedRoute($this->createRoute('tests'), []));

            $this->expectException(DiException::class);
            $this->expectExceptionMessage('The dependency `' . ViewCache::class . '` is not registered.');

            (new DispatchRequestStage())->process($this->webContext);
        }

        public function testDispatchRequestStagePassesTheContextRequestToTheMiddlewares(): void
        {
            $this->prepareRequest('/test/am/tests');

            $request = new Request();
            $request->create('GET', '/test/am/tests');
            $request->setMatchedRoute(new MatchedRoute($this->createRoute('tests', ['DispatchStageMwRecorder']), []));

            $container = new DiContainer();
            $container->set(Request::class, $request);
            $container->register(ViewCache::class);

            DispatchStageMwRecorder::$request = null;

            (new DispatchRequestStage())->process(new WebContext(new AppContext(PROJECT_ROOT, $container, AppType::WEB)));

            $this->assertSame($request, DispatchStageMwRecorder::$request);
        }

        private function prepareRequest(string $uri): void
        {
            request()->create('GET', $uri);

            (new ResolveRouteStage())->process($this->webContext);

            $this->registerViewCache();
        }

        private function registerViewCache(): void
        {
            if (!Di::isRegistered(ViewCache::class)) {
                Di::register(ViewCache::class);
            }
        }

        /**
         * @param string[] $middlewares
         */
        private function createRoute(string $action, array $middlewares = [], string $controller = 'TestController'): Route
        {
            $route = (new Route(['GET'], '/test/am/tests', $controller, $action))->module('Test');

            if ($middlewares !== []) {
                $route->addMiddlewares($middlewares);
            }

            return $route;
        }
    }
}
