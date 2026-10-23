<?php

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace {{MODULE_NAMESPACE}}\Controllers\OpenApi;

use OpenApi\Attributes as OA;
use Quantum\Http\Response;

/**
 * Class OpenApiPostController
 * @package Modules\Api
 */
abstract class OpenApiMainController extends OpenApiController
{

    #[OA\Get(
        path: '/{{MODULE_NAME}}',
        tags: ['{{MODULE_NAME}}'],
        summary: 'Get status of {{MODULE_NAME}}',
        description: 'Returns status of {{MODULE_NAME}} module.',
        responses: [
            new OA\Response(
                response: 200,
                description: 'Successful response',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: '{{MODULE_NAME}} module.'),
                    ]
                )
            ),
        ]
    )]
    abstract public function index();

}

