<?php

declare(strict_types=1);

namespace InfraMind\Middleware;

use InfraMind\Core\Request;
use InfraMind\Core\Response;
use InfraMind\Core\TokenManager;
use InfraMind\Repositories\PlatformStateRepository;

class MaintenanceMiddleware implements Middleware
{
    private PlatformStateRepository $platformRepository;
    private array $adminRoles = ['DEVELOPER', 'SYSTEM_ADMIN'];

    public function __construct()
    {
        $this->platformRepository = new PlatformStateRepository();
    }

    public function handle(Request $request, callable $next): Response
    {
        if ($this->isExemptPath($request->getPath())) {
            return $next($request);
        }

        $state = $this->platformRepository->getState();
        $maintenanceEnabled = (bool) ($state['maintenance_enabled'] ?? false);
        $softShutdownEnabled = (bool) ($state['soft_shutdown_enabled'] ?? false);

        if (!$maintenanceEnabled && !$softShutdownEnabled) {
            return $next($request);
        }

        $role = $this->getRoleFromToken();
        if ($role && in_array($role, $this->adminRoles, true)) {
            return $next($request);
        }

        $message = $state['maintenance_message'] ?? 'The platform is currently under maintenance.';
        $status = $maintenanceEnabled ? 'MAINTENANCE' : 'SOFT_SHUTDOWN';

        return (new Response(503))->error($message, 503, ['status' => $status]);
    }

    private function getRoleFromToken(): ?string
    {
        $token = TokenManager::extractFromHeader();
        if (!$token) {
            return null;
        }

        try {
            $decoded = (new TokenManager())->verifyAccessToken($token);
            return $decoded->role ?? null;
        } catch (\Exception $e) {
            return null;
        }
    }

    private function isExemptPath(string $path): bool
    {
        $exempt = [
            '/api/health',
            '/api/maintenance/status',
            '/api/auth/login',
            '/api/auth/refresh',
            '/api/auth/signup',
            '/api/auth/me',
        ];

        if (in_array($path, $exempt, true)) {
            return true;
        }

        return str_starts_with($path, '/api/admin');
    }
}
