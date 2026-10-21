<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\App\Adapters\Web\Stages\Runtime;

use Quantum\Config\Exceptions\ConfigException;
use Quantum\App\Contracts\StageInterface;
use Quantum\App\Exceptions\BaseException;
use Quantum\App\Adapters\Web\WebContext;
use Quantum\Di\Exceptions\DiException;
use Quantum\App\AppContext;
use ReflectionException;

/**
 * Class HandleRouteNotFoundStage
 * @implements StageInterface<WebContext>
 * @package Quantum\App
 */
class HandleRouteNotFoundStage implements StageInterface
{
    public const BEFORE = 'web.not_found.before';

    public const AFTER = 'web.not_found.after';

    /**
     * Answers a request that matched no route with the page not found response and stops the remaining stages
     * @param WebContext $context
     * @throws ConfigException|BaseException|DiException|ReflectionException
     */
    public function process(AppContext $context): void
    {
        if ($context->getMatchedRoute() !== null) {
            return;
        }

        $context->setResponse(page_not_found_response($context->getRequest()));
        $context->stop();
    }
}
