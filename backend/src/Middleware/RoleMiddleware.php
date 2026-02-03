<?php

declare(strict_types=1);

namespace InfraMind\Middleware;

use InfraMind\Core\Request;
use InfraMind\Core\Response;

/**
 * Role-based authorization middleware.
 */
class RoleMiddleware implements Middleware
{
    private array $allowedRoles;

    public function __construct(array $allowedRoles)
    {
        $this->allowedRoles = $allowedRoles;
    }

    public function handle(Request $request, callable $next): Response
    {
        $user = $request->getUser();
        if (!$user || !isset($user->role)) {
            return (new Response(403))->error('Forbidden');
        }

        if (!in_array($user->role, $this->allowedRoles, true)) {
            return (new Response(403))->error('Insufficient permissions');
        }

        return $next($request);
    }
}
