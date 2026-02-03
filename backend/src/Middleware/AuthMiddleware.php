<?php

declare(strict_types=1);

namespace InfraMind\Middleware;

use InfraMind\Core\Request;
use InfraMind\Core\Response;
use InfraMind\Exceptions\Exception as InfraMindException;

/**
 * Authentication middleware.
 */
class AuthMiddleware implements Middleware
{
    public function handle(Request $request, callable $next): Response
    {
        try {
            $token = \InfraMind\Core\TokenManager::extractFromHeader();
            if (!$token) {
                return (new Response(401))->error('Missing authentication token');
            }

            $tokenManager = new \InfraMind\Core\TokenManager();
            $decoded = $tokenManager->verifyAccessToken($token);

            // Set user on request
            $request->setUser($decoded);

            return $next($request);
        } catch (InfraMindException $e) {
            return (new Response($e->getCode() ?: 401))->error($e->getMessage());
        } catch (\Exception $e) {
            return (new Response(401))->error('Authentication failed');
        }
    }
}
