<?php

declare(strict_types=1);

namespace InfraMind\Middleware;

use InfraMind\Core\Request;
use InfraMind\Core\Response;

/**
 * CORS middleware.
 */
class CorsMiddleware implements Middleware
{
    public function handle(Request $request, callable $next): Response
    {
        if (!\InfraMind\Core\Config::getBool('CORS_ENABLED')) {
            return $next($request);
        }

        $origins = explode(',', \InfraMind\Core\Config::get('CORS_ORIGINS', 'http://localhost:3000'));
        $requestOrigin = $_SERVER['HTTP_ORIGIN'] ?? '';
        $isDev = \InfraMind\Core\Config::get('APP_ENV', 'development') === 'development';

        $allowedOrigin = in_array($requestOrigin, array_map('trim', $origins), true) ? $requestOrigin : '';
        if ($isDev && $requestOrigin) {
            $allowedOrigin = $requestOrigin;
        }

        $response = $next($request);

        if ($allowedOrigin) {
            $response->setHeader('Access-Control-Allow-Origin', $allowedOrigin);
            $response->setHeader('Access-Control-Allow-Credentials', 'true');
            $response->setHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
            $response->setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization');
        }

        // Handle preflight
        if ($request->getMethod() === 'OPTIONS') {
            return (new Response(200))->success();
        }

        return $response;
    }
}
