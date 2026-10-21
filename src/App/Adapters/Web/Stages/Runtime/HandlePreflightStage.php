<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\App\Adapters\Web\Stages\Runtime;

use Quantum\App\Contracts\StageInterface;
use Quantum\App\Adapters\Web\WebContext;
use Quantum\Di\Exceptions\DiException;
use Quantum\Http\Enums\StatusCode;
use Quantum\App\AppContext;

/**
 * Class HandlePreflightStage
 * @implements StageInterface<WebContext>
 * @package Quantum\App
 */
class HandlePreflightStage implements StageInterface
{
    /**
     * Answers an OPTIONS request with an empty response and stops the remaining stages
     * @param WebContext $context
     * @throws DiException
     */
    public function process(AppContext $context): void
    {
        if (!$context->getRequest()->isMethod('OPTIONS')) {
            return;
        }

        $context->setResponse(response()->setStatusCode(StatusCode::NO_CONTENT));
        $context->stop();
    }
}
