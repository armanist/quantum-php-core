<?php

namespace Quantum\Tests\Unit\App\Adapters\Web\Stages\Runtime;

use Quantum\App\Adapters\Web\Stages\Runtime\PrepareRequestStage;
use Quantum\Tests\Unit\AppTestCase;
use Quantum\ResourceCache\ViewCache;
use Quantum\App\Adapters\Web\WebContext;
use Quantum\Router\MatchedRoute;
use Quantum\Di\DiContainer;
use Quantum\App\AppContext;
use Quantum\Router\Route;
use Quantum\App\App;
use Quantum\Di\Di;

class PrepareRequestStageTest extends AppTestCase
{
    private WebContext $webContext;

    public function setUp(): void
    {
        parent::setUp();

        $this->webContext = new WebContext(App::getContext());

        request()->create('GET', '/test/am/tests');

        request()->setMatchedRoute(new MatchedRoute(
            (new Route(['GET'], '/test/am/tests', 'TestController', 'tests'))->module('Test'),
            []
        ));
    }

    public function testPrepareRequestStageLoadsLanguage(): void
    {
        $this->assertFalse(config()->has('lang'));

        (new PrepareRequestStage())->process($this->webContext);

        $this->assertTrue(config()->has('lang'));
    }

    public function testPrepareRequestStageRegistersViewCache(): void
    {
        $this->assertFalse(Di::isRegistered(ViewCache::class));

        (new PrepareRequestStage())->process($this->webContext);

        $this->assertInstanceOf(ViewCache::class, $this->webContext->getViewCache());
        $this->assertSame(Di::get(ViewCache::class), $this->webContext->getViewCache());
    }

    public function testPrepareRequestStageSetsUpViewCacheWhenEnabled(): void
    {
        $viewsDir = base_dir() . DS . 'cache' . DS . 'views';
        $moduleDir = $viewsDir . DS . 'test';
        $viewsDirExisted = is_dir($viewsDir);

        $this->assertFalse(config()->has('view_cache'));

        Di::register(ViewCache::class);
        Di::get(ViewCache::class)->enableCaching(true);

        try {
            (new PrepareRequestStage())->process($this->webContext);

            $this->assertTrue(config()->has('view_cache'));
            $this->assertDirectoryExists($moduleDir);
        } finally {
            if (is_dir($moduleDir)) {
                rmdir($moduleDir);
            }

            if (!$viewsDirExisted && is_dir($viewsDir)) {
                rmdir($viewsDir);
            }
        }
    }

    public function testPrepareRequestStageDoesNotSetUpViewCacheWhenDisabled(): void
    {
        (new PrepareRequestStage())->process($this->webContext);

        $this->assertFalse(config()->has('view_cache'));
    }

    public function testPrepareRequestStageRegistersViewCacheInTheContextContainer(): void
    {
        $container = new DiContainer();

        (new PrepareRequestStage())->process(new WebContext(new AppContext(PROJECT_ROOT, $container)));

        $this->assertTrue($container->isRegistered(ViewCache::class));
        $this->assertFalse(Di::isRegistered(ViewCache::class));
    }

    public function testPrepareRequestStageSetsUpTheViewCacheOfTheContextContainer(): void
    {
        $viewsDir = base_dir() . DS . 'cache' . DS . 'views';
        $moduleDir = $viewsDir . DS . 'test';
        $viewsDirExisted = is_dir($viewsDir);

        $container = new DiContainer();
        $container->register(ViewCache::class);
        $container->get(ViewCache::class)->enableCaching(true);

        try {
            (new PrepareRequestStage())->process(new WebContext(new AppContext(PROJECT_ROOT, $container)));

            $this->assertDirectoryExists($moduleDir);
        } finally {
            if (is_dir($moduleDir)) {
                rmdir($moduleDir);
            }

            if (!$viewsDirExisted && is_dir($viewsDir)) {
                rmdir($viewsDir);
            }
        }
    }
}
