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
 * @since 2.9.8
 */

namespace {{MODULE_NAMESPACE}}\Middlewares;

use Quantum\Libraries\Validation\Validator;
use Quantum\Model\Factories\ModelFactory;
use Quantum\Libraries\Validation\Rule;
use Quantum\Middleware\QtMiddleware;
use Quantum\Http\Response;
use Quantum\Http\Request;
use Shared\Models\User;
use Closure;

/**
 * Class Verify
 * @package Modules\Web
 */
class Verify extends QtMiddleware
{
    /**
     * @var Validator
     */
    private $validator;

    /**
     * Class constructor
     */
    public function __construct()
    {
        $this->validator = new Validator();

        $this->validator->addRules([
            'otp' => [
                Rule::set('required')
            ],
            'code' => [
                Rule::set('required')
            ]
        ]);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param Closure $next
     * @return mixed
     */
    public function apply(Request $request, Response $response, Closure $next)
    {
        if ($request->isMethod('post')) {
            if (!$this->validator->isValid($request->all())) {
                session()->setFlash('error', $this->validator->getErrors());
                redirectWith(base_url(true) . '/' . current_lang() . '/verify', $request->all());
            }
        } else {
            $token = (string)route_param('code');

            if (!$this->checkToken($token)) {
                stop(function () use ($response) {
                    $response->html(partial('errors/404'), 404);
                });
            }

        }

        return $next($request, $response);
    }

    private function checkToken(string $token): bool
    {
        $userModel = ModelFactory::get(User::class);
        return !empty($userModel->findOneBy('otp_token', $token)->asArray());
    }
}