<?php

declare(strict_types=1);

namespace InfraMind\Controllers;

use InfraMind\Core\Request;
use InfraMind\Core\Response;
use InfraMind\Repositories\PlatformStateRepository;
use InfraMind\Repositories\AuditLogRepository;
use InfraMind\Core\Logger;

class MaintenanceController
{
    private PlatformStateRepository $platformRepository;
    private AuditLogRepository $auditRepository;
    private Logger $logger;
    private array $adminRoles = ['DEVELOPER', 'SYSTEM_ADMIN'];

    public function __construct()
    {
        $this->platformRepository = new PlatformStateRepository();
        $this->auditRepository = new AuditLogRepository();
        $this->logger = Logger::getInstance();
    }

    /**
     * GET /maintenance/status (public)
     */
    public function status(Request $request): Response
    {
        $state = $this->platformRepository->getState();

        return (new Response(200))->success([
            'maintenanceEnabled' => (bool) ($state['maintenance_enabled'] ?? false),
            'maintenanceMessage' => $state['maintenance_message'] ?? null,
            'softShutdownEnabled' => (bool) ($state['soft_shutdown_enabled'] ?? false),
            'updatedAt' => $state['updated_at'] ?? null,
        ]);
    }

    /**
     * GET /admin/maintenance
     */
    public function get(Request $request): Response
    {
        $auth = $this->requireAdmin($request);
        if ($auth) {
            return $auth;
        }

        $state = $this->platformRepository->getState();

        return (new Response(200))->success([
            'maintenanceEnabled' => (bool) ($state['maintenance_enabled'] ?? false),
            'maintenanceMessage' => $state['maintenance_message'] ?? null,
            'softShutdownEnabled' => (bool) ($state['soft_shutdown_enabled'] ?? false),
            'lastRestartRequestedAt' => $state['last_restart_requested_at'] ?? null,
            'updatedAt' => $state['updated_at'] ?? null,
        ]);
    }

    /**
     * PUT /admin/maintenance
     */
    public function update(Request $request): Response
    {
        $auth = $this->requireAdmin($request);
        if ($auth) {
            return $auth;
        }

        $user = $request->getUser();
        $enabled = (bool) $request->get('maintenanceEnabled', false);
        $message = $request->get('maintenanceMessage');

        $state = $this->platformRepository->updateMaintenance($enabled, $message, $user->sub);
        $this->auditRepository->log('Platform', 'maintenance', $enabled ? 'MAINTENANCE_ENABLED' : 'MAINTENANCE_DISABLED', $user->sub, [
            'message' => $message,
        ]);

        $this->logger->info('Maintenance mode updated', ['enabled' => $enabled]);

        return (new Response(200))->success([
            'maintenanceEnabled' => (bool) ($state['maintenance_enabled'] ?? false),
            'maintenanceMessage' => $state['maintenance_message'] ?? null,
            'softShutdownEnabled' => (bool) ($state['soft_shutdown_enabled'] ?? false),
            'updatedAt' => $state['updated_at'] ?? null,
        ]);
    }

    private function requireAdmin(Request $request): ?Response
    {
        $user = $request->getUser();
        if (!$user || !in_array($user->role ?? null, $this->adminRoles, true)) {
            return (new Response(403))->error('Insufficient permissions');
        }

        return null;
    }
}
