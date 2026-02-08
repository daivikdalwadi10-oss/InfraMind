<?php

declare(strict_types=1);

namespace InfraMind\Controllers;

use InfraMind\Core\Request;
use InfraMind\Core\Response;
use InfraMind\Repositories\AnnouncementRepository;
use InfraMind\Repositories\AuditLogRepository;
use InfraMind\Core\Logger;

class AnnouncementController
{
    private AnnouncementRepository $announcementRepository;
    private AuditLogRepository $auditRepository;
    private Logger $logger;
    private array $adminRoles = ['DEVELOPER', 'SYSTEM_ADMIN'];

    public function __construct()
    {
        $this->announcementRepository = new AnnouncementRepository();
        $this->auditRepository = new AuditLogRepository();
        $this->logger = Logger::getInstance();
    }

    /**
     * GET /announcements/active
     */
    public function listActive(Request $request): Response
    {
        $user = $request->getUser();
        $role = $user->role ?? null;
        $announcements = $this->announcementRepository->listActive($role, 20);

        return (new Response(200))->success($announcements);
    }

    /**
     * GET /admin/announcements
     */
    public function list(Request $request): Response
    {
        $auth = $this->requireAdmin($request);
        if ($auth) {
            return $auth;
        }

        $limit = min((int) $request->getQuery('limit', 100), 200);
        $offset = (int) $request->getQuery('offset', 0);
        $announcements = $this->announcementRepository->listAll($limit, $offset);

        return (new Response(200))->success($announcements);
    }

    /**
     * POST /admin/announcements
     */
    public function create(Request $request): Response
    {
        $auth = $this->requireAdmin($request);
        if ($auth) {
            return $auth;
        }

        $user = $request->getUser();
        $data = $request->getAll();

        if (empty($data['title']) || empty($data['message'])) {
            return (new Response(422))->error('title and message are required');
        }

        $announcement = $this->announcementRepository->create($data, $user->sub);
        $this->auditRepository->log('Announcement', $announcement['id'] ?? 'unknown', 'CREATED', $user->sub, [
            'title' => $announcement['title'] ?? null,
            'severity' => $announcement['severity'] ?? null,
        ]);

        $this->logger->info('Announcement created', ['id' => $announcement['id'] ?? null]);

        return (new Response(201))->success($announcement);
    }

    /**
     * POST /admin/announcements/{id}/archive
     */
    public function archive(Request $request, array $params): Response
    {
        $auth = $this->requireAdmin($request);
        if ($auth) {
            return $auth;
        }

        $user = $request->getUser();
        $id = $params['id'] ?? '';
        if ($id === '') {
            return (new Response(400))->error('Announcement id required');
        }

        $success = $this->announcementRepository->archive($id, $user->sub);
        if ($success) {
            $this->auditRepository->log('Announcement', $id, 'ARCHIVED', $user->sub);
        }

        return (new Response(200))->success(['archived' => $success]);
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
