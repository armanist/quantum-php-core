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
use Quantum\App\Adapters\Web\WebContext;
use Quantum\Di\Exceptions\DiException;
use Quantum\App\AppContext;
use Quantum\Config\Config;
use Quantum\Http\Response;
use Quantum\Http\Request;
use Quantum\Config\Setup;
use ReflectionException;
use RuntimeException;
use Exception;

/**
 * Class SendResponseStage
 *
 * Sends the response of the request and always clears the request state afterwards,
 * even when sending fails. It is the closing stage of the web runtime, so it also runs after a stop.
 *
 * @implements StageInterface<WebContext>
 * @package Quantum\App
 */
class SendResponseStage implements StageInterface
{
    /**
     * @param WebContext $context
     * @throws ConfigException|DiException|ReflectionException|Exception
     */
    public function process(AppContext $context): void
    {
        try {
            $response = $context->getResponse();

            if ($response === null) {
                throw new RuntimeException('The request has no response to send.');
            }

            $this->handleCors($response, $context->getConfig());
            $response->send();
        } finally {
            $this->cleanupRequestState($context->getRequest());
        }
    }

    /**
     * @throws ConfigException|DiException|ReflectionException
     */
    private function handleCors(Response $response, Config $config): void
    {
        if (!$config->has('cors')) {
            $config->import(new Setup('config', 'cors'));
        }

        foreach ($config->get('cors') as $key => $value) {
            $response->setHeader($key, (string) $value);
        }
    }

    /**
     * Clears request-scoped state after the response has been sent.
     */
    private function cleanupRequestState(Request $request): void
    {
        $request->setMatchedRoute(null);
        $request->flush();
    }
}
