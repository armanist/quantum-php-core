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
 * Class OpenApiAccountController
 * @package Modules\Api
 */
abstract class OpenApiAccountController extends OpenApiController
{
    /**
     * Update user info action
     */
    #[OA\Put(
        path: '/api/account-settings/update',
        tags: ['Account'],
        summary: 'Update user info',
        operationId: 'updateAccount',
        security: [['bearer_token' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'application/json',
                schema: new OA\Schema(
                    required: ['firstname', 'lastname'],
                    properties: [
                        new OA\Property(property: 'firstname', type: 'string'),
                        new OA\Property(property: 'lastname', type: 'string'),
                    ],
                    example: ['firstname' => 'Jon', 'lastname' => 'Smit']
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(example: ['status' => 'success', 'message' => 'Updated successfully'])
            ),
            new OA\Response(response: 401, description: 'Unauthorized Request'),
            new OA\Response(response: 422, description: 'Unprocessable Entity'),
            new OA\Response(response: 429, description: 'Too Many Requests'),
            new OA\Response(response: 500, description: 'Internal Server Error'),
        ]
    )]
    abstract public function update(Request $request);

    /**
     * Update password action
     */
    #[OA\Put(
        path: '/api/account-settings/update-password',
        tags: ['Account'],
        summary: 'Update password',
        operationId: 'updatePassword',
        security: [['bearer_token' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'application/json',
                schema: new OA\Schema(
                    required: ['current_password', 'new_password', 'confirm_password'],
                    properties: [
                        new OA\Property(property: 'current_password', type: 'string'),
                        new OA\Property(property: 'new_password', type: 'string'),
                        new OA\Property(property: 'confirm_password', type: 'string'),
                    ],
                    example: [
                        'current_password' => 'oldPassword',
                        'new_password' => 'newPassword',
                        'confirm_password' => 'newPassword',
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(example: ['status' => 'success', 'message' => 'Updated successfully'])
            ),
            new OA\Response(response: 401, description: 'Unauthorized Request'),
            new OA\Response(response: 422, description: 'Unprocessable Entity'),
            new OA\Response(response: 429, description: 'Too Many Requests'),
            new OA\Response(response: 500, description: 'Internal Server Error'),
        ]
    )]
    abstract public function updatePassword(Request $request);
}
