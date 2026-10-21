<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\App\Adapters\Web\Stages\Runtime;

use Quantum\Config\Exceptions\ConfigException;
use Quantum\Lang\Exceptions\LangException;
use Quantum\App\Contracts\StageInterface;
use Quantum\App\Adapters\Web\WebContext;
use Quantum\Lang\Factories\LangFactory;
use Quantum\Di\Exceptions\DiException;
use Quantum\ResourceCache\ViewCache;
use Quantum\App\AppContext;
use ReflectionException;

/**
 * Class PrepareRequestStage
 *
 * Prepares a matched request: loads the language and sets up the view cache.
 *
 * @implements StageInterface<WebContext>
 * @package Quantum\App
 */
class PrepareRequestStage implements StageInterface
{
    public const BEFORE = 'web.prepare.before';

    public const AFTER = 'web.prepare.after';

    /**
     * @param WebContext $context
     * @throws LangException|ConfigException|DiException|ReflectionException
     */
    public function process(AppContext $context): void
    {
        LangFactory::get();

        $this->setupViewCache($context);
    }

    /**
     * @throws ConfigException|DiException|ReflectionException
     */
    private function setupViewCache(WebContext $context): void
    {
        $container = $context->getContainer();

        if (!$container->isRegistered(ViewCache::class)) {
            $container->register(ViewCache::class);
        }

        $viewCache = $context->getViewCache();

        if ($viewCache->isEnabled()) {
            $viewCache->setup();
        }
    }
}
