<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\App\Stages\Web;

use Quantum\Config\Exceptions\ConfigException;
use Quantum\App\Contracts\WebStageInterface;
use Quantum\Lang\Exceptions\LangException;
use Quantum\App\Adapters\Web\WebContext;
use Quantum\Lang\Factories\LangFactory;
use Quantum\Di\Exceptions\DiException;
use Quantum\ResourceCache\ViewCache;
use Quantum\Debugger\Debugger;
use ReflectionException;
use Quantum\Di\Di;

/**
 * Class PrepareRequestStage
 *
 * Prepares a matched request: loads the language, logs debug info and sets up the view cache.
 * The order of these steps is kept from the former WebAppAdapter flow.
 *
 * @package Quantum\App
 */
class PrepareRequestStage implements WebStageInterface
{
    /**
     * @throws LangException|ConfigException|DiException|ReflectionException
     */
    public function process(WebContext $context): void
    {
        if ($context->isStopped()) {
            return;
        }

        LangFactory::get();

        $this->logDebugInfo();

        $this->setupViewCache();
    }

    /**
     * @throws DiException|ReflectionException
     */
    private function logDebugInfo(): void
    {
        $debugBar = debugbar();

        if ($debugBar->isEnabled()) {
            $debugBar->addToStoreCell(Debugger::EVENTS, 'info', event()->getRegistered());
        }
    }

    /**
     * @throws ConfigException|DiException|ReflectionException
     */
    private function setupViewCache(): void
    {
        if (!Di::isRegistered(ViewCache::class)) {
            Di::register(ViewCache::class);
        }

        $viewCache = Di::get(ViewCache::class);

        if ($viewCache->isEnabled()) {
            $viewCache->setup();
        }
    }
}
