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

use Quantum\Service\Factories\ServiceFactory;
use Quantum\Http\Constants\StatusCode;
use Quantum\Middleware\QtMiddleware;
use Shared\Services\PostService;
use Quantum\Http\Response;
use Quantum\Http\Request;
use Closure;

/**
 * Class Owner
 * @package Modules\Api
 */
class Owner extends QtMiddleware
{

    /**
     * @param Request $request
     * @param Response $response
     * @param Closure $next
     * @return mixed
     */
    public function apply(Request $request, Response $response, Closure $next)
    {
        $postId = (string)route_param('id');

        $post = ServiceFactory::get(PostService::class)->getPost($postId);

        if (!$post->asArray() || $post->user_id != auth()->user()->id) {
            $response->json([
                'status' => 'error',
                'message' => t('common.post_not_found')
            ], StatusCode::NOT_FOUND);

            stop();
        }

        return $next($request, $response);
    }

}