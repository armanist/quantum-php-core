<?php

/**
 * Quantum PHP Framework
 *
 * An open source software development framework for PHP
 *
 * @package Quantum
 * @author Arman Ag. <arman.ag@softberg.org>
 * @copyright Copyright (c) 2018 Softberg LLC (https://softberg.org)
 * @link http://quantum.softberg.org/
 * @since 2.9.7
 */

namespace Quantum\App\Adapters;

use Quantum\Libraries\Encryption\Exceptions\CryptorException;
use Quantum\Libraries\Database\Exceptions\DatabaseException;
use Quantum\Libraries\Session\Exceptions\SessionException;
use Quantum\Middleware\Exceptions\MiddlewareException;
use Quantum\Router\Exceptions\RouteControllerException;
use Quantum\Libraries\Csrf\Exceptions\CsrfException;
use Quantum\Libraries\Lang\Exceptions\LangException;
use Quantum\Renderer\Exceptions\RendererException;
use Quantum\App\Exceptions\StopExecutionException;
use Quantum\Environment\Exceptions\EnvException;
use Quantum\Module\Exceptions\ModuleException;
use Quantum\Config\Exceptions\ConfigException;
use Quantum\Router\Exceptions\RouteException;
use Quantum\Middleware\MiddlewareManager;
use Quantum\App\Exceptions\BaseException;
use Quantum\Di\Exceptions\DiException;
use Quantum\App\Traits\WebAppTrait;
use Quantum\Router\RouteDispatcher;
use DebugBar\DebugBarException;
use Quantum\Debugger\Debugger;
use Quantum\Hook\HookManager;
use Quantum\Http\Response;
use Quantum\Http\Request;
use ReflectionException;
use Quantum\Di\Di;

/**
 * Class WebAppAdapter
 * @package Quantum\App
 */
class WebAppAdapter extends AppAdapter
{

    use WebAppTrait;

    /**
     * @var Request
     */
    private $request;

    /**
     * @var Response
     */
    private $response;

    /**
     * @throws BaseException
     * @throws ConfigException
     * @throws DiException
     * @throws EnvException
     * @throws ReflectionException
     */
    public function __construct()
    {
        parent::__construct();

        $this->loadEnvironment();
        $this->loadConfig();

        $this->request = Di::get(Request::class);
        $this->response = Di::get(Response::class);
    }

    /**
     * @return int|null
     * @throws BaseException
     * @throws ConfigException
     * @throws RouteControllerException
     * @throws CryptorException
     * @throws CsrfException
     * @throws DatabaseException
     * @throws DebugBarException
     * @throws DiException
     * @throws LangException
     * @throws MiddlewareException
     * @throws ModuleException
     * @throws ReflectionException
     * @throws RouteException
     * @throws SessionException
     * @throws RendererException
     */
    public function start(): ?int
    {
        try {
            $this->initializeRequestResponse($this->request, $this->response);

            $this->loadLanguage();

            if ($this->request->isMethod('OPTIONS')) {
                stop();
            }

            $this->setupErrorHandler();
            $this->initializeDebugger();

            $this->loadModulesRoutes();
            $this->initializeRouter($this->request);

            info(HookManager::getInstance()->getRegistered(), ['tab' => Debugger::HOOKS]);

            if (current_middlewares()) {
                list($this->request, $this->response) = (new MiddlewareManager())->applyMiddlewares($this->request, $this->response);
            }

            $viewCache = $this->setupViewCache();

            if ($viewCache->serveCachedView(route_uri(), $this->response)) {
                stop();
            }

            RouteDispatcher::handle($this->request);

            stop();
        } catch (StopExecutionException $exception) {
            $this->handleCors($this->response);
            $this->response->send();

            return $exception->getCode();
        }
    }
}