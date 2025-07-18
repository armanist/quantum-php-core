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
use Quantum\Http\Constants\StatusCode;
use Quantum\Libraries\Validation\Rule;
use Quantum\Middleware\QtMiddleware;
use Quantum\Http\Response;
use Quantum\Http\Request;
use Shared\Models\User;
use Closure;

/**
 * Class Forget
 * @package Modules\Api
 */
class Forget extends QtMiddleware
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

        $this->validator->addRule('email', [
            Rule::set('required'),
            Rule::set('email')
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
                $response->json([
                    'status' => 'error',
                    'message' => $this->validator->getErrors()
                ], StatusCode::UNPROCESSABLE_ENTITY);
                
                stop();
            }

            if (!$this->emailExists($request->get('email'))) {
                $response->json([
                    'status' => 'error',
                    'message' => [t('validation.nonExistingRecord', $request->get('email'))]
                ], StatusCode::UNPROCESSABLE_ENTITY);
                
                stop();
            }
        }

        return $next($request, $response);
    }

    /**
     * Check for email existence
     * @param string $email
     * @return bool
     */
    private function emailExists(string $email): bool
    {
        $userModel = ModelFactory::get(User::class);
        return !empty($userModel->findOneBy('email', $email)->asArray());
    }

}