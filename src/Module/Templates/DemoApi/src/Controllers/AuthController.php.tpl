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
 * @since 2.9.9
 */

namespace {{MODULE_NAMESPACE}}\Controllers;

use Quantum\Auth\Exceptions\AuthException;
use Quantum\Http\Enums\StatusCode;
use {{MODULE_NAMESPACE}}\DTOs\UserDTO;
use {{MODULE_NAMESPACE}}\Enums\Role;
use Quantum\Http\Response;
use Quantum\Http\Request;

/**
 * Class AuthController
 * @package Modules\{{MODULE_NAME}}
 */
class AuthController extends BaseController
{

    /**
     * Action - sign in
     * @param Request $request
     * @return Response
     */
    public function signin(Request $request): Response
    {
        $response = response();
        try {
            $code = auth()->signin($request->get('email'), $request->get('password'));

            if (filter_var(config()->get('auth.two_fa'), FILTER_VALIDATE_BOOLEAN)) {
                $response->set('code', $code);
            }

            return $response->json([
                'status' => self::STATUS_SUCCESS
            ]);
        } catch (AuthException $e) {
            return $response->json([
                'status' => self::STATUS_ERROR,
                'message' => $e->getMessage()
            ], StatusCode::UNPROCESSABLE_ENTITY);
        }
    }

    /**
     * Action - me
     * @return Response
     */
    public function me(): Response
    {
        return response()->json([
            'status' => self::STATUS_SUCCESS,
            'data' => [
                'firstname' => auth()->user()->firstname,
                'lastname' => auth()->user()->lastname,
                'email' => auth()->user()->email
            ]
        ]);
    }

    /**
     * Action - sign out
     * @return Response
     */
    public function signout(): Response
    {
        if (auth()->signout()) {
            return response()->json([
                'status' => self::STATUS_SUCCESS
            ]);
        } else {
            return $response->json([
                'status' => self::STATUS_ERROR,
                'message' => t('validation.unauthorizedRequest')
            ], StatusCode::UNAUTHORIZED);
        }
    }

    /**
     *  Action - sign up
     * @param Request $request
     * @return Response
     */
    public function signup(Request $request): Response
    {
        $userDto = UserDTO::fromRequest($request, Role::EDITOR, uuid_ordered());

        auth()->signup($userDto->toArray());

        return response()->json([
            'status' => self::STATUS_SUCCESS,
            'message' => t('common.successfully_signed_up')
        ]);
    }

    /**
     * Action - activate
     * @param Request $request
     * @return Response
     */
    public function activate(Request $request): Response
    {
        auth()->activate($request->get('activation_token'));

        return response()->json([
            'status' => self::STATUS_SUCCESS,
            'message' => t('common.account_activated')
        ]);
    }

    /**
     * Action - forget
     * @param Request $request
     * @return Response
     */
    public function forget(Request $request): Response
    {
        auth()->forget($request->get('email'));

        return response()->json([
            'status' => self::STATUS_SUCCESS,
            'message' => t('common.check_email')
        ]);
    }

    /**
     * Action - reset
     * @param Request $request
     * @return Response
     */
    public function reset(Request $request): Response
    {
        auth()->reset($request->get('reset_token'), $request->get('password'));

        return response()->json([
            'status' => self::STATUS_SUCCESS
        ]);
    }

    /**
     * Action - Verify OTP
     * @param Request $request
     * @return Response
     */
    public function verify(Request $request): Response
    {
        try {
            auth()->verifyOtp((int)$request->get('otp'), $request->get('code'));

            return response()->json([
                'status' => self::STATUS_SUCCESS
            ]);
        } catch (AuthException $e) {
            return $response->json([
                'status' => self::STATUS_ERROR,
                'message' => $e->getMessage()
            ], StatusCode::UNAUTHORIZED);
        }
    }

    /**
     *  Action - Resend OTP
     * @return Response
     */
    public function resend(): Response
    {
        try {
            return response()->json([
                'status' => self::STATUS_SUCCESS,
                'code' => auth()->resendOtp(route_param('code'))
            ]);
        } catch (AuthException $e) {
            return $response->json([
                'status' => self::STATUS_ERROR,
                'message' => $e->getMessage()
            ], StatusCode::UNAUTHORIZED);
        }
    }
}
