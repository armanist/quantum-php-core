<?php

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace {{MODULE_NAMESPACE}}\Controllers\OpenApi;

use OpenApi\Attributes as OA;
use Quantum\Http\Request;

/**
 * Class OpenApiAuthController
 * @package Modules\Api
 */
abstract class OpenApiAuthController extends OpenApiController
{
    /**
     * Sign in action
     */
    #[OA\Post(
        path: '/api/signin',
        tags: ['Authentication'],
        summary: 'Sign in action',
        operationId: 'userSignIn',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'application/json',
                schema: new OA\Schema(
                    required: ['email', 'password'],
                    properties: [
                        new OA\Property(property: 'email', type: 'string'),
                        new OA\Property(property: 'password', type: 'string'),
                    ],
                    example: ['email' => 'mail@example.com', 'password' => 'password']
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(
                    oneOf: [
                        new OA\Schema(
                            example: [
                                'status' => 'success',
                                'tokens' => ['access_token' => 'base64-jwt-token', 'refresh_token' => 'refresh-token'],
                            ]
                        ),
                        new OA\Schema(example: ['status' => 'success', 'code' => 'otp_token']),
                    ]
                )
            ),
            new OA\Response(response: 422, description: 'Unprocessable Entity'),
            new OA\Response(response: 429, description: 'Too Many Requests'),
            new OA\Response(response: 500, description: 'Internal Server Error'),
        ]
    )]
    abstract public function signin(Request $request);

    /**
     * Gets the logged-in user data
     */
    #[OA\Get(
        path: '/api/me',
        tags: ['User'],
        summary: 'Gets the logged-in user data',
        operationId: 'me',
        security: [['bearer_token' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(
                    example: [
                        'status' => 'success',
                        'data' => ['firstname' => 'Jon', 'lastname' => 'Smit', 'email' => 'mail@example.com'],
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized Request'),
            new OA\Response(response: 429, description: 'Too Many Requests'),
            new OA\Response(response: 500, description: 'Internal Server Error'),
        ]
    )]
    abstract public function me();

    /**
     * Sign out action
     */
    #[OA\Get(
        path: '/api/signout',
        tags: ['Authentication'],
        summary: 'Sign out action',
        operationId: 'signout',
        parameters: [
            new OA\Parameter(
                name: 'refresh_token',
                description: 'Refresh token',
                required: true,
                in: 'header',
                schema: new OA\Schema(type: 'string')
            ),
        ],
        security: [['bearer_token' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(
                    example: [
                        'status' => 'success',
                        'tokens' => ['access_token' => 'base64-jwt-token', 'refresh_token' => 'refresh-token'],
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized Request'),
            new OA\Response(response: 422, description: 'Unprocessable Entity'),
            new OA\Response(response: 429, description: 'Too Many Requests'),
            new OA\Response(response: 500, description: 'Internal Server Error'),
        ]
    )]
    abstract public function signout();

    /**
     * Sign up action
     */
    #[OA\Post(
        path: '/api/signup',
        tags: ['Authentication'],
        summary: 'Sign up action',
        operationId: 'signUpApi',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'application/json',
                schema: new OA\Schema(
                    required: ['email', 'password', 'firstname', 'lastname'],
                    properties: [
                        new OA\Property(property: 'email', type: 'string'),
                        new OA\Property(property: 'password', type: 'string'),
                        new OA\Property(property: 'firstname', type: 'string'),
                        new OA\Property(property: 'lastname', type: 'string'),
                    ],
                    example: [
                        'email' => 'mail@example.com',
                        'password' => 'password',
                        'firstname' => 'Jon',
                        'lastname' => 'Smit',
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(example: ['status' => 'success', 'message' => 'Successfully signed up'])
            ),
            new OA\Response(response: 422, description: 'Unprocessable Entity'),
            new OA\Response(response: 429, description: 'Too Many Requests'),
            new OA\Response(response: 500, description: 'Internal Server Error'),
        ]
    )]
    abstract public function signup(Request $request);

    /**
     * Activate action
     */
    #[OA\Get(
        path: '/api/activate/{token}',
        tags: ['Authentication'],
        summary: 'Activate action',
        operationId: 'activateProfile',
        parameters: [
            new OA\Parameter(
                name: 'token',
                description: 'Activate token',
                required: true,
                in: 'path',
                schema: new OA\Schema(type: 'string')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(example: ['status' => 'success', 'message' => 'Account activated'])
            ),
            new OA\Response(response: 422, description: 'Unprocessable Entity'),
            new OA\Response(response: 429, description: 'Too Many Requests'),
            new OA\Response(response: 500, description: 'Internal Server Error'),
        ]
    )]
    abstract public function activate(Request $request);

    /**
     * Forget action
     */
    #[OA\Post(
        path: '/api/forget',
        tags: ['Authentication'],
        summary: 'Forget action',
        operationId: 'forgetPassword',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'application/json',
                schema: new OA\Schema(
                    required: ['email'],
                    properties: [new OA\Property(property: 'email', type: 'string')],
                    example: ['email' => 'mail@example.com']
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(example: ['status' => 'success', 'message' => 'Check your email'])
            ),
            new OA\Response(response: 422, description: 'Unprocessable Entity'),
            new OA\Response(response: 429, description: 'Too Many Requests'),
            new OA\Response(response: 500, description: 'Internal Server Error'),
        ]
    )]
    abstract public function forget(Request $request);

    /**
     * Reset action
     */
    #[OA\Post(
        path: '/api/reset/{token}',
        tags: ['Authentication'],
        summary: 'Reset action',
        operationId: 'resetPassword',
        parameters: [
            new OA\Parameter(
                name: 'token',
                description: 'Reset token',
                required: true,
                in: 'path',
                schema: new OA\Schema(type: 'string')
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'application/json',
                schema: new OA\Schema(
                    required: ['password', 'repeat_password'],
                    properties: [
                        new OA\Property(property: 'password', type: 'string'),
                        new OA\Property(property: 'repeat_password', type: 'string'),
                    ],
                    example: ['password' => 'password', 'repeat_password' => 'password']
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(
                    example: [
                        'status' => 'success',
                        'tokens' => ['access_token' => 'base64-jwt-token', 'refresh_token' => 'refresh-token'],
                    ]
                )
            ),
            new OA\Response(response: 422, description: 'Unprocessable Entity'),
            new OA\Response(response: 429, description: 'Too Many Requests'),
            new OA\Response(response: 500, description: 'Internal Server Error'),
        ]
    )]
    abstract public function reset(Request $request);

    /**
     * Verify action
     */
    #[OA\Post(
        path: '/api/verify',
        tags: ['Authentication'],
        summary: 'Verify action',
        operationId: 'accountVerify',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'application/json',
                schema: new OA\Schema(
                    required: ['otp', 'code'],
                    properties: [
                        new OA\Property(property: 'otp', type: 'integer'),
                        new OA\Property(property: 'code', type: 'string'),
                    ],
                    example: ['otp' => 123456, 'code' => 'otp_token']
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(example: ['status' => 'success'])
            ),
            new OA\Response(response: 401, description: 'Unauthorized Request'),
            new OA\Response(response: 422, description: 'Unprocessable Entity'),
            new OA\Response(response: 429, description: 'Too Many Requests'),
            new OA\Response(response: 500, description: 'Internal Server Error'),
        ]
    )]
    abstract public function verify(Request $request);

    /**
     * Resend action
     */
    #[OA\Get(
        path: '/api/resend/{code}',
        tags: ['Authentication'],
        summary: 'Resend action',
        operationId: 'resendOTP',
        parameters: [
            new OA\Parameter(
                name: 'code',
                description: 'OTP code',
                required: true,
                in: 'path',
                schema: new OA\Schema(type: 'string')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(example: ['status' => 'success', 'code' => 'otp_token'])
            ),
            new OA\Response(response: 401, description: 'Unauthorized Request'),
            new OA\Response(response: 422, description: 'Unprocessable Entity'),
            new OA\Response(response: 429, description: 'Too Many Requests'),
            new OA\Response(response: 500, description: 'Internal Server Error'),
        ]
    )]
    abstract public function resend();
}
