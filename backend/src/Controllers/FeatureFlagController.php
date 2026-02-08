<?php

declare(strict_types=1);

namespace InfraMind\Controllers;

use InfraMind\Core\Request;
use InfraMind\Core\Response;
use InfraMind\Repositories\FeatureFlagRepository;
use InfraMind\Repositories\AuditLogRepository;

class FeatureFlagController
{
    private FeatureFlagRepository $featureRepository;
    private AuditLogRepository $auditRepository;
    private array $adminRoles = ['DEVELOPER', 'SYSTEM_ADMIN'];

    public function __construct()
    {
        $this->featureRepository = new FeatureFlagRepository();
        $this->auditRepository = new AuditLogRepository();
    }

    /**
     * GET /admin/feature-flags
     */
    public function list(Request $request): Response
    {
        $auth = $this->requireAdmin($request);
        if ($auth) {
            return $auth;
        }

        return (new Response(200))->success($this->featureRepository->listAll());
    }

    /**
     * PUT /admin/feature-flags
     */
    public function upsert(Request $request): Response
    {
        $auth = $this->requireAdmin($request);
        if ($auth) {
            return $auth;
        }

        $user = $request->getUser();
        $key = (string) $request->get('key', '');
        $enabled = (bool) $request->get('enabled', false);
        $description = $request->get('description');

        if ($key === '') {
            return (new Response(422))->error('key is required');
        }

        $flag = $this->featureRepository->upsert($key, $enabled, $description, $user->sub);
        $this->auditRepository->log('FeatureFlag', $flag['id'] ?? $key, $enabled ? 'ENABLED' : 'DISABLED', $user->sub, [
            'key' => $key,
        ]);

        return (new Response(200))->success($flag);
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
