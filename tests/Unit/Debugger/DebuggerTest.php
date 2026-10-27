<?php

namespace Quantum\Tests\Unit\Debugger;

use Quantum\Database\Adapters\Sleekdb\SleekDbal;
use Quantum\Database\Adapters\Idiorm\IdiormDbal;
use Quantum\Database\Exceptions\DatabaseException;
use DebugBar\DataCollector\MessagesCollector;
use Quantum\Mailer\Factories\MailerFactory;
use DebugBar\DataCollector\PhpInfoCollector;
use Quantum\View\Factories\ViewFactory;
use Quantum\Debugger\DebuggerStore;
use Quantum\Tests\Unit\AppTestCase;
use DebugBar\JavascriptRenderer;
use Quantum\Router\MatchedRoute;
use Quantum\Database\Database;
use Quantum\Debugger\Debugger;
use Quantum\Router\Route;
use Quantum\Config\Setup;
use DebugBar\DebugBar;
use Psr\Log\LogLevel;
use Quantum\Di\Di;
use Mockery;

class DebuggerTest extends AppTestCase
{
    private Debugger $debugger;

    private $debugBarMock;

    private DebuggerStore $debuggerStore;

    public function setUp(): void
    {

        parent::setUp();

        $this->debuggerStore = new DebuggerStore();
        $this->debuggerStore->flush();

        $this->debugBarMock = Mockery::mock(DebugBar::class);

        $collectors = [
            Mockery::mock(PhpInfoCollector::class),
            Mockery::mock(MessagesCollector::class),
        ];

        foreach ($collectors as $collector) {
            $this->debugBarMock->shouldReceive('addCollector')
                ->once()
                ->with($collector);
        }

        $this->debugger = new Debugger($this->debuggerStore, $this->debugBarMock, $collectors);
    }

    public function testDebuggerIsEnabled(): void
    {
        config()->set('app.debug', true);
        $this->assertTrue($this->debugger->isEnabled());

        config()->set('app.debug', false);
        $this->assertFalse($this->debugger->isEnabled());
    }

    public function testDebuggerInitStore(): void
    {
        $this->debugger->initStore();

        $this->assertTrue($this->debuggerStore->has(Debugger::MESSAGES));
        $this->assertTrue($this->debuggerStore->has(Debugger::QUERIES));
        $this->assertTrue($this->debuggerStore->has(Debugger::ROUTES));
        $this->assertTrue($this->debuggerStore->has(Debugger::EVENTS));
        $this->assertTrue($this->debuggerStore->has(Debugger::MAILS));
    }

    public function testDebuggerAddToStoreCell(): void
    {
        $this->debugger->addToStoreCell(Debugger::MESSAGES, LogLevel::INFO, 'Test message');

        $storedData = $this->debuggerStore->get(Debugger::MESSAGES);

        $this->assertEquals(['info' => 'Test message'], $storedData[0]);
    }

    public function testDebuggerGetStoreCell(): void
    {
        $this->debugger->addToStoreCell(Debugger::MESSAGES, LogLevel::INFO, 'Test message');

        $storedData = $this->debugger->getStoreCell(Debugger::MESSAGES);

        $this->assertEquals(['info' => 'Test message'], $storedData[0]);
    }

    public function testDebuggerClearStoreCell(): void
    {
        $this->debugger->addToStoreCell(Debugger::MESSAGES, LogLevel::INFO, 'Test message');
        $this->debugger->clearStoreCell(Debugger::MESSAGES);

        $storedData = $this->debuggerStore->get(Debugger::MESSAGES);

        $this->assertEmpty($storedData);
    }

    public function testDebuggerResetStore(): void
    {
        $this->debugger->initStore();

        $this->assertNotEmpty($this->debuggerStore->all());

        $this->debugger->resetStore();

        $this->assertEmpty($this->debuggerStore->all());
    }

    public function testDebugbarRender(): void
    {
        config()->set('app.debug', true);

        $this->debugger->initStore();

        $rendererMock = Mockery::mock(JavascriptRenderer::class);
        $rendererMock->shouldReceive('setBaseUrl')->andReturnSelf();
        $rendererMock->shouldReceive('addAssets')->andReturnSelf();
        $rendererMock->shouldReceive('renderHead')->andReturn('<head></head>');
        $rendererMock->shouldReceive('render')->andReturn('<div>Debug Bar</div>');

        $this->debugBarMock->shouldReceive('getJavascriptRenderer')->andReturn($rendererMock);

        $this->debugBarMock->shouldReceive('hasCollector')->with(Mockery::any())->andReturn(true);
        $this->debugBarMock->shouldReceive('getCollector')
            ->with(Mockery::any())
            ->andReturn(Mockery::mock(MessagesCollector::class));

        $output = $this->debugger->render();

        $this->assertStringContainsString('<head></head>', $output);
        $this->assertStringContainsString('<div>Debug Bar</div>', $output);
    }

    public function testDebugbarRenderReplaysArrayPayloadsThroughLog(): void
    {
        $this->debugger->initStore();
        $this->debugger->addToStoreCell(Debugger::ROUTES, LogLevel::INFO, ['Route' => '/welcome']);

        $collectorMock = Mockery::mock(MessagesCollector::class);
        $collectorMock->shouldReceive('log')
            ->once()
            ->with(LogLevel::INFO, ['Route' => '/welcome']);

        $this->debugBarMock->shouldReceive('hasCollector')
            ->with(Debugger::ROUTES)
            ->andReturn(true);
        $this->debugBarMock->shouldReceive('getCollector')
            ->with(Debugger::ROUTES)
            ->andReturn($collectorMock);

        $method = new \ReflectionMethod($this->debugger, 'createTab');
        $method->setAccessible(true);
        $method->invoke($this->debugger, Debugger::ROUTES, []);

        $this->assertEquals(
            ['info' => ['Route' => '/welcome']],
            $this->debuggerStore->get(Debugger::ROUTES)[0]
        );
    }

    public function testDebugbarRenderReturnsEmptyWhenDisabled(): void
    {
        config()->set('app.debug', false);

        $output = $this->debugger->render();

        $this->assertEmpty($output);
    }

    public function testDebugbarRenderPassesNothingToTheTabsWhenDisabled(): void
    {
        config()->set('app.debug', false);

        event()->listen('test.event', function (): void {
        });

        request()->setMatchedRoute(new MatchedRoute(new Route(['GET'], 'posts', 'PostController', 'posts'), []));

        info('Logged message');

        $this->connectSqlite();

        Database::query('SELECT 1');

        $this->assertSame([], $this->renderTabs());
    }

    public function testDebugbarRenderPassesTheStoredEntriesBeforeTheCollectedOnes(): void
    {
        config()->set('app.debug', true);

        $this->debugger->addToStoreCell(Debugger::MESSAGES, LogLevel::INFO, 'Written directly');

        info('Logged message');

        $tabs = $this->renderTabs();

        $this->assertSame([['info' => 'Written directly'], ['info' => 'Logged message']], $tabs[Debugger::MESSAGES]);
    }

    public function testDebugbarRenderLeavesTheStoreUnchanged(): void
    {
        config()->set('app.debug', true);

        $this->debugger->addToStoreCell(Debugger::MESSAGES, LogLevel::INFO, 'Written directly');

        info('Logged message');

        event()->listen('test.event', function (): void {
        });

        $this->renderTabs();

        $this->assertSame([Debugger::MESSAGES => [['info' => 'Written directly']]], $this->debuggerStore->all());
    }

    public function testDebugbarRenderListsTheRegisteredEventListenersInTheEventsTab(): void
    {
        config()->set('app.debug', true);

        $listener = function (): void {
        };

        event()->listen('test.event', $listener);

        $tabs = $this->renderTabs();

        $this->assertSame([['info' => ['test.event' => [$listener]]]], $tabs[Debugger::EVENTS]);
    }

    public function testDebugbarRenderPassesNothingToTheEventsTabWithoutEventListeners(): void
    {
        config()->set('app.debug', true);

        $this->assertSame([], event()->getRegistered());

        $this->assertArrayNotHasKey(Debugger::EVENTS, $this->renderTabs());
    }

    public function testDebugbarRenderListsTheMatchedRouteInTheRoutesTab(): void
    {
        config()->set('app.debug', true);

        $route = (new Route(['GET', 'POST'], 'posts/[id=:num]', 'PostController', 'show'))
            ->module('Web')
            ->name('post')
            ->group('blog')
            ->prefix('en')
            ->addMiddlewares(['Auth']);

        request()->setMatchedRoute(new MatchedRoute($route, ['id' => '5']));

        $tabs = $this->renderTabs();

        $this->assertSame([['info' => [
            'Methods' => 'GET, POST',
            'Pattern' => 'posts/[id=:num]',
            'Module' => 'Web',
            'Controller' => 'PostController',
            'Action' => 'show',
            'Name' => 'post',
            'Group' => 'blog',
            'Prefix' => 'en',
            'Middlewares' => ['Auth'],
            'Params' => ['id' => '5'],
        ]]], $tabs[Debugger::ROUTES]);
    }

    public function testDebugbarRenderListsAClosureRouteInTheRoutesTab(): void
    {
        config()->set('app.debug', true);

        $route = (new Route(['GET'], 'ping', null, null, function (): void {
        }))->module('Api');

        request()->setMatchedRoute(new MatchedRoute($route, []));

        $tabs = $this->renderTabs();

        $this->assertSame([['info' => [
            'Methods' => 'GET',
            'Pattern' => 'ping',
            'Module' => 'Api',
            'Action' => 'Closure',
        ]]], $tabs[Debugger::ROUTES]);
    }

    public function testDebugbarRenderAddsTheRenderedViewToTheRoutesTab(): void
    {
        config()->set('app.debug', true);

        request()->create('GET', '/test');
        request()->setMatchedRoute(new MatchedRoute((new Route(['GET'], 'test', 'SomeController', 'test'))->module('Test'), []));

        $view = ViewFactory::get();
        $view->setLayout('layout');
        $view->render('index');

        $routes = $this->renderTabs()[Debugger::ROUTES];

        $this->assertCount(1, $routes);
        $this->assertSame('Test/Views/index', $routes[0]['info']['View']);
        $this->assertSame('SomeController', $routes[0]['info']['Controller']);
    }

    public function testDebugbarRenderPassesNothingToTheRoutesTabWithoutARouteOrAView(): void
    {
        config()->set('app.debug', true);

        $this->assertNull(request()->getMatchedRoute());

        $this->assertArrayNotHasKey(Debugger::ROUTES, $this->renderTabs());
    }

    public function testDebugbarRenderListsTheQueriesOfTheRequestInTheQueriesTab(): void
    {
        config()->set('app.debug', true);

        $this->connectSqlite();

        Database::query('SELECT 1');

        $queries = $this->renderTabs()[Debugger::QUERIES];

        $this->assertCount(count(Database::queryLog()), $queries);
        $this->assertSame(['info' => 'SELECT 1'], end($queries));
    }

    public function testDebugbarRenderLeavesTheDatabaseUnusedWhenTheRequestDidNotUseIt(): void
    {
        config()->set('app.debug', true);

        $tabs = $this->renderTabs();

        $this->assertFalse(Di::isRegistered(Database::class));
        $this->assertArrayNotHasKey(Debugger::QUERIES, $tabs);
    }

    public function testDebugbarRenderPassesNothingToTheQueriesTabForAnAdapterWithoutAQueryLog(): void
    {
        config()->set('app.debug', true);

        if (!config()->has('database')) {
            config()->import(new Setup('config', 'database'));
        }

        config()->set('database.default', 'sleekdb');

        $this->assertSame(SleekDbal::class, db()->getOrmClass());

        $this->assertArrayNotHasKey(Debugger::QUERIES, $this->renderTabs());
    }

    public function testDebugbarRenderPassesNothingToTheQueriesTabAfterTheDatabaseDisconnected(): void
    {
        config()->set('app.debug', true);

        $this->connectSqlite();

        Database::query('SELECT 1');

        IdiormDbal::disconnect();

        $this->assertArrayNotHasKey(Debugger::QUERIES, $this->renderTabs());
    }

    public function testDebugbarRenderPassesNothingToTheQueriesTabWhenTheDatabaseFailedToStart(): void
    {
        config()->set('app.debug', true);

        if (!config()->has('database')) {
            config()->import(new Setup('config', 'database'));
        }

        config()->set('database.default', 'unknown');

        try {
            db();
            $this->fail('db() should throw for an unknown adapter');
        } catch (DatabaseException $e) {
        }

        $this->assertTrue(Di::isRegistered(Database::class));

        $this->assertArrayNotHasKey(Debugger::QUERIES, $this->renderTabs());
    }

    public function testDebugbarRenderListsTheLoggedMessagesInTheMessagesTab(): void
    {
        config()->set('app.debug', true);

        info('First message');
        warning('Second message');

        $tabs = $this->renderTabs();

        $this->assertSame([['info' => 'First message'], ['warning' => 'Second message']], $tabs[Debugger::MESSAGES]);
    }

    public function testDebugbarRenderListsAnArrayMessageAsAnArrayInTheMessagesTab(): void
    {
        config()->set('app.debug', true);

        $payload = ['order' => 1042, 'items' => ['a', 'b']];

        logger()->info($payload);
        logger()->warning('A text message');

        $tabs = $this->renderTabs();

        $this->assertSame([['info' => $payload], ['warning' => 'A text message']], $tabs[Debugger::MESSAGES]);
    }

    public function testDebugbarRenderListsTheTransportLogOfTheMailerInTheMailsTab(): void
    {
        config()->set('app.debug', true);

        $this->setPrivateProperty(mailer()->getAdapter(), 'transportLog', ['SERVER -> CLIENT: 220 ready', 'SERVER -> CLIENT: 535 Authentication failed']);

        $tabs = $this->renderTabs();

        $this->assertSame([['warning' => 'SERVER -> CLIENT: 220 ready'], ['warning' => 'SERVER -> CLIENT: 535 Authentication failed']], $tabs[Debugger::MAILS]);
    }

    public function testDebugbarRenderLeavesTheMailerUnbuiltWhenTheRequestDidNotUseIt(): void
    {
        config()->set('app.debug', true);

        $tabs = $this->renderTabs();

        $this->assertFalse(Di::isRegistered(MailerFactory::class));
        $this->assertArrayNotHasKey(Debugger::MAILS, $tabs);
    }

    /**
     * Connects the in-memory SQLite database again, so the connection logs queries with the current app.debug
     */
    private function connectSqlite(): void
    {
        if (!config()->has('database')) {
            config()->import(new Setup('config', 'database'));
        }

        config()->set('database.default', 'sqlite');

        IdiormDbal::disconnect();

        db();
    }

    /**
     * Renders against the mocked DebugBar and returns what each tab's collector received, as [tab => [[level => data], ...]]
     * @return array<string, array<int, array<string, mixed>>>
     */
    private function renderTabs(): array
    {
        $logged = [];

        $rendererMock = Mockery::mock(JavascriptRenderer::class);
        $rendererMock->shouldReceive('setBaseUrl')->andReturnSelf();
        $rendererMock->shouldReceive('addAssets')->andReturnSelf();
        $rendererMock->shouldReceive('renderHead')->andReturn('');
        $rendererMock->shouldReceive('render')->andReturn('');

        $this->debugBarMock->shouldReceive('getJavascriptRenderer')->andReturn($rendererMock);
        $this->debugBarMock->shouldReceive('hasCollector')->andReturn(true);
        $this->debugBarMock->shouldReceive('getCollector')->andReturnUsing(function (string $tab) use (&$logged) {
            $collector = Mockery::mock(MessagesCollector::class);
            $collector->shouldReceive('log')->andReturnUsing(function ($level, $data) use (&$logged, $tab): void {
                $logged[$tab][] = [$level => $data];
            });

            return $collector;
        });

        $this->debugger->render();

        return $logged;
    }
}
