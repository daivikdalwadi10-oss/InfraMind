<?php

declare(strict_types=1);

namespace InfraMind\Controllers;

use InfraMind\Core\Request;
use InfraMind\Core\Response;
use InfraMind\Repositories\ServiceCredentialRepository;
use InfraMind\Repositories\AuditLogRepository;
use InfraMind\Core\Logger;

class CredentialController
{
    private ServiceCredentialRepository $credentialRepository;
    private AuditLogRepository $auditRepository;
    private Logger $logger;
    private array $adminRoles = ['DEVELOPER', 'SYSTEM_ADMIN'];

    public function __construct()
    {
        $this->credentialRepository = new ServiceCredentialRepository();
        $this->auditRepository = new AuditLogRepository();
        $this->logger = Logger::getInstance();
    }

    /**
     * GET /admin/credentials
     */
    public function list(Request $request): Response
    {
        $auth = $this->requireAdmin($request);
        if ($auth) {
            return $auth;
        }

        $limit = min((int) $request->getQuery('limit', 100), 200);
        $offset = (int) $request->getQuery('offset', 0);

        $credentials = $this->credentialRepository->listAll($limit, $offset);
        return (new Response(200))->success($credentials);
    }

    /**
     * POST /admin/credentials
     */
    public function create(Request $request): Response
    {
        $auth = $this->requireAdmin($request);
        if ($auth) {
            return $auth;
        }

        $user = $request->getUser();
        $data = $request->getAll();

        if (empty($data['name']) || empty($data['secret'])) {
            return (new Response(422))->error('name and secret are required');
        }

        $credential = $this->credentialRepository->create($data, $user->sub);
        $this->auditRepository->log('Credential', $credential['id'] ?? 'unknown', 'CREATED', $user->sub, [
            'name' => $credential['name'] ?? null,
        ]);

        $this->logger->info('Credential created', ['id' => $credential['id'] ?? null]);

        return (new Response(201))->success($credential);
    }

    /**
     * POST /admin/credentials/{id}/rotate
     */
    public function rotate(Request $request, array $params): Response
    {
        $auth = $this->requireAdmin($request);
        if ($auth) {
            return $auth;
        }

        $user = $request->getUser();
        $id = $params['id'] ?? '';
        $secret = (string) $request->get('secret', '');
        if ($id === '' || $secret === '') {
            return (new Response(422))->error('credential id and secret are required');
        }

        $success = $this->credentialRepository->rotate($id, $secret, $user->sub);
        if ($success) {
            $this->auditRepository->log('Credential', $id, 'ROTATED', $user->sub);
        }

        return (new Response(200))->success(['rotated' => $success]);
    }

    /**
     * POST /admin/credentials/{id}/disable
     */
    public function disable(Request $request, array $params): Response
    {
        $auth = $this->requireAdmin($request);
        if ($auth) {
            return $auth;
        }

        $user = $request->getUser();
        $id = $params['id'] ?? '';
        if ($id === '') {
            return (new Response(422))->error('credential id is required');
        }

        $success = $this->credentialRepository->disable($id, $user->sub);
        if ($success) {
            $this->auditRepository->log('Credential', $id, 'DISABLED', $user->sub);
        }

        return (new Response(200))->success(['disabled' => $success]);
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
