<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Debugger;

use DebugBar\DataCollector\RequestDataCollector;
use DebugBar\DataCollector\TimeDataCollector;
use DebugBar\DataCollector\MessagesCollector;
use DebugBar\DataCollector\PhpInfoCollector;
use DebugBar\DataCollector\MemoryCollector;
use Quantum\Config\Exceptions\ConfigException;
use Quantum\App\Exceptions\BaseException;
use Quantum\View\Factories\ViewFactory;
use Quantum\Di\Exceptions\DiException;
use DebugBar\JavascriptRenderer;
use DebugBar\DebugBarException;
use ReflectionException;
use DebugBar\DebugBar;
use Quantum\Di\Di;

/**
 * Class Debugger
 * @package Quantum\Debugger
 * @uses DebugBar
 */
class Debugger
{
    /**
     * Messages tab
     */
    public const MESSAGES = 'messages';

    /**
     * Queries tab
     */
    public const QUERIES = 'queries';

    /**
     * Routes tab
     */
    public const ROUTES = 'routes';

    /**
     * Events tab
     */
    public const EVENTS = 'events';

    /**
     * Mails tab
     */
    public const MAILS = 'mails';

    /**
     * Store
     */
    private DebuggerStore $store;

    /**
     * DebugBar instance
     */
    private DebugBar $debugBar;

    /**
     * Assets url
     */
    private string $assetsUrl = '/assets/DebugBar/Resources';

    /**
     * Custom CSS
     */
    private string $customCss = 'custom_debugbar.css';

    /**
     * @param array<mixed> $collectors
     * @throws DebugBarException
     */
    public function __construct(?DebuggerStore $store = null, ?DebugBar $debugBar = null, array $collectors = [])
    {
        $this->store = $store ?? new DebuggerStore();
        $this->debugBar = $debugBar ?? new DebugBar();

        $collectors = $collectors ?: self::getDefaultCollectors();

        foreach ($collectors as $collector) {
            $this->debugBar->addCollector($collector);
        }
    }

    /**
     * Checks if debug bar enabled
     * @throws DiException|ReflectionException
     */
    public function isEnabled(): bool
    {
        return filter_var(config()->get('app.debug'), FILTER_VALIDATE_BOOLEAN);
    }

    public function initStore(): void
    {
        $this->store->init([
            Debugger::MESSAGES,
            Debugger::QUERIES,
            Debugger::ROUTES,
            Debugger::EVENTS,
            Debugger::MAILS,
        ]);
    }

    /**
     * Adds data to the store cell
     * @param mixed $data
     */
    public function addToStoreCell(string $cell, string $level, $data): void
    {
        if (!empty($data)) {
            $this->store->set($cell, [$level => $data]);
        }
    }

    /**
     * @return array<mixed>
     */
    public function getStoreCell(string $cell): array
    {
        return $this->store->get($cell);
    }

    /**
     * Clears the store cell
     */
    public function clearStoreCell(string $cell): void
    {
        $this->store->delete($cell);
    }

    public function resetStore(): void
    {
        $this->store->flush();
    }

    /**
     * Renders the debug bar. The events and routes tabs are filled here, with the event listeners,
     * the matched route and the rendered view as they are at this point.
     * @throws DebugBarException|DiException|ReflectionException|ConfigException|BaseException
     */
    public function render(): string
    {
        if (!$this->isEnabled()) {
            return '';
        }

        $this->clearStoreCell(self::EVENTS);
        $this->addToStoreCell(self::EVENTS, 'info', event()->getRegistered());

        $this->clearStoreCell(self::ROUTES);
        $this->addToStoreCell(self::ROUTES, 'info', $this->routeInfo());

        foreach ([self::MESSAGES, self::QUERIES, self::ROUTES, self::EVENTS, self::MAILS] as $tab) {
            $this->createTab($tab);
        }

        $renderer = $this->getRenderer();

        return $renderer->renderHead() . $renderer->render();
    }

    /**
     * The matched route and the rendered view, without the values that are not set
     * @return array<string, mixed>
     * @throws DiException|ReflectionException|ConfigException|BaseException
     */
    protected function routeInfo(): array
    {
        $info = [];

        $matchedRoute = request()->getMatchedRoute();

        if ($matchedRoute !== null) {
            $route = $matchedRoute->getRoute();

            $info = [
                'Methods' => implode(', ', $route->getMethods()),
                'Pattern' => $route->getPattern(),
                'Module' => $route->getModule(),
                'Controller' => $route->getController(),
                'Action' => $route->getClosure() !== null ? 'Closure' : $route->getAction(),
                'Name' => $route->getName(),
                'Group' => $route->getGroup(),
                'Prefix' => $route->getPrefix(),
                'Middlewares' => $route->getMiddlewares(),
                'Params' => $matchedRoute->getParams(),
            ];
        }

        if (Di::isRegistered(ViewFactory::class) && ViewFactory::get()->getViewFile() !== null) {
            $info['View'] = request()->getCurrentModule() . '/Views/' . ViewFactory::get()->getViewFile();
        }

        return array_filter($info, fn ($value): bool => $value !== null && $value !== []);
    }

    /**
     * Creates a tab
     * @throws DebugBarException
     */
    protected function createTab(string $type): void
    {
        $collector = $this->resolveMessagesCollector($type);

        $messages = $this->store->get($type);

        foreach ($messages as $message) {
            $fn = key($message);
            $collector->log($fn, $message[$fn]);
        }
    }

    /**
     * Ensures the requested debug tab collector can accept mixed message payloads.
     * @throws DebugBarException
     */
    protected function resolveMessagesCollector(string $type): MessagesCollector
    {
        if (!$this->debugBar->hasCollector($type)) {
            $this->debugBar->addCollector(new MessagesCollector($type));
        }

        $collector = $this->debugBar->getCollector($type);

        if (!$collector instanceof MessagesCollector) {
            throw new DebugBarException("Collector '{$type}' must be a MessagesCollector instance");
        }

        return $collector;
    }

    /**
     * Gets the renderer
     */
    protected function getRenderer(): JavascriptRenderer
    {
        return $this->debugBar
            ->getJavascriptRenderer()
            ->setBaseUrl(base_url() . $this->assetsUrl)
            ->addAssets([$this->customCss], []);
    }

    /**
     * @return array<mixed>
     */
    protected static function getDefaultCollectors(): array
    {
        return [
            new PhpInfoCollector(),
            new MessagesCollector(),
            new RequestDataCollector(),
            new TimeDataCollector(),
            new MemoryCollector(),
        ];
    }

}
