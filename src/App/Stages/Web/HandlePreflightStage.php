<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\App\Stages\Web;

use Quantum\App\Contracts\WebStageInterface;
use Quantum\App\Adapters\Web\WebContext;
use Quantum\Di\Exceptions\DiException;
use Quantum\Http\Enums\StatusCode;

/**
 * Class HandlePreflightStage
 * @package Quantum\App
 */
class HandlePreflightStage implements WebStageInterface
{
    /**
     * Answers an OPTIONS request with an empty response and stops the remaining stages
     * @throws DiException
     */
    public function process(WebContext $context): void
    {
        if (!$context->getRequest()->isMethod('OPTIONS')) {
            return;
        }

        $context->setResponse(response()->setStatusCode(StatusCode::NO_CONTENT));
        $context->stop();
    }
}
