<?php

declare(strict_types=1);

namespace InfraMind\Controllers;

use InfraMind\Core\Config;
use InfraMind\Core\Database;
use InfraMind\Core\Request;
use InfraMind\Core\Response;
use InfraMind\Core\Logger;
use InfraMind\Repositories\PlatformStateRepository;
use InfraMind\Repositories\UserRepository;
use InfraMind\Repositories\TeamRepository;
use InfraMind\Repositories\AuditLogRepository;
use InfraMind\Services\AdminLogService;
use InfraMind\Utils\Utils;

class AdminController
{
    private Database $db;
    private PlatformStateRepository $platformRepository;
    private UserRepository $userRepository;
    private TeamRepository $teamRepository;
    private AuditLogRepository $auditRepository;
    private AdminLogService $logService;
    private Logger $logger;
    private array $adminRoles = ['DEVELOPER', 'SYSTEM_ADMIN'];

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->platformRepository = new PlatformStateRepository();
        $this->userRepository = new UserRepository();
        $this->teamRepository = new TeamRepository();
        $this->auditRepository = new AuditLogRepository();
        $this->logService = new AdminLogService();
        $this->logger = Logger::getInstance();
    }

    /**
     * GET /admin/health
     */
    public function health(Request $request): Response
    {
        $auth = $this->requireAdmin($request);
        if ($auth) {
            return $auth;
        }

        $state = $this->platformRepository->getState();
        $dbOk = false;
        try {
            $row = $this->db->fetchOne('SELECT 1 AS status');
            $dbOk = (bool) $row;
        } catch (\Exception $e) {
            $dbOk = false;
        }

        $maintenanceEnabled = (bool) ($state['maintenance_enabled'] ?? false);
        $softShutdownEnabled = (bool) ($state['soft_shutdown_enabled'] ?? false);
        $status = $dbOk ? 'UP' : 'DOWN';
        if (($maintenanceEnabled || $softShutdownEnabled) && $status === 'UP') {
            $status = 'DEGRADED';
        }

        $deployedAt = Config::get('DEPLOYED_AT');
        if (!$deployedAt) {
            $file = dirname(__DIR__, 2) . '/public/index.php';
            $deployedAt = is_file($file) ? date('Y-m-d H:i:s', filemtime($file)) : Utils::now();
        }

        return (new Response(200))->success([
            'status' => $status,
            'api' => 'OK',
            'database' => $dbOk ? 'CONNECTED' : 'DISCONNECTED',
            'environment' => Config::get('APP_ENV', 'development'),
            'lastDeployment' => $deployedAt,
            'maintenanceEnabled' => $maintenanceEnabled,
            'softShutdownEnabled' => $softShutdownEnabled,
        ]);
    }

    /**
     * GET /admin/insights
     */
    public function insights(Request $request): Response
    {
        $auth = $this->requireAdmin($request);
        if ($auth) {
            return $auth;
        }

        $activeUsers = $this->db->fetchOne('SELECT COUNT(*) as count FROM users WHERE deleted_at IS NULL AND is_active = 1');
        $activeAnalyses = $this->db->fetchOne("SELECT COUNT(*) as count FROM analyses WHERE status IN ('DRAFT','NEEDS_CHANGES','SUBMITTED')");
        $pendingApprovals = $this->db->fetchOne("SELECT COUNT(*) as count FROM analyses WHERE status = 'SUBMITTED'");
        $aiUsage = $this->db->fetchOne('SELECT COUNT(*) as count FROM ai_outputs WHERE created_at >= ?', [date('Y-m-d H:i:s', strtotime('-24 hours'))]);

        $errorTrend = $this->db->fetchAll(
            "SELECT DATE(created_at) as day, COUNT(*) as count FROM audit_logs WHERE action IN ('ERROR','AUTH_FAILURE','AI_FAILURE') AND created_at >= ? GROUP BY DATE(created_at) ORDER BY day DESC LIMIT 7",
            [date('Y-m-d H:i:s', strtotime('-7 days'))]
        );

        return (new Response(200))->success([
            'activeUsers' => (int) ($activeUsers['count'] ?? 0),
            'activeAnalyses' => (int) ($activeAnalyses['count'] ?? 0),
            'pendingApprovals' => (int) ($pendingApprovals['count'] ?? 0),
            'aiUsageLast24h' => (int) ($aiUsage['count'] ?? 0),
            'errorTrend' => $errorTrend,
        ]);
    }

    /**
     * GET /admin/logs
     */
    public function logs(Request $request): Response
    {
        $auth = $this->requireAdmin($request);
        if ($auth) {
            return $auth;
        }

        $type = (string) $request->getQuery('type', 'audit');
        $filters = [
            'user_id' => $request->getQuery('user'),
            'role' => $request->getQuery('role'),
            'start' => $request->getQuery('start'),
            'end' => $request->getQuery('end'),
        ];
        $limit = min((int) $request->getQuery('limit', 200), 500);

        $logs = $this->logService->listLogs($type, $filters, $limit);
        return (new Response(200))->success($logs);
    }

    /**
     * POST /admin/server-actions
     */
    public function serverAction(Request $request): Response
    {
        $auth = $this->requireAdmin($request);
        if ($auth) {
            return $auth;
        }

        $user = $request->getUser();
        $action = strtoupper((string) $request->get('action', ''));
        $reason = (string) $request->get('reason', '');

        if (!in_array($action, ['SOFT_SHUTDOWN', 'RESUME', 'RESTART'], true)) {
            return (new Response(422))->error('Invalid action');
        }

        if ($action === 'SOFT_SHUTDOWN') {
            $state = $this->platformRepository->updateSoftShutdown(true, $user->sub);
        } elseif ($action === 'RESUME') {
            $state = $this->platformRepository->updateSoftShutdown(false, $user->sub);
        } else {
            $state = $this->platformRepository->recordRestartRequest($user->sub);
        }

        $this->auditRepository->log('Platform', 'server', $action, $user->sub, ['reason' => $reason]);
        $this->logger->info('Server action requested', ['action' => $action, 'reason' => $reason]);

        return (new Response(200))->success([
            'action' => $action,
            'state' => $state,
        ]);
    }

    /**
     * GET /admin/users
     */
    public function users(Request $request): Response
    {
        $auth = $this->requireAdmin($request);
        if ($auth) {
            return $auth;
        }

        $limit = min((int) $request->getQuery('limit', 100), 200);
        $offset = (int) $request->getQuery('offset', 0);
        $users = $this->userRepository->listActive($limit, $offset);

        $payload = array_map(fn ($user) => $user->toArray(), $users);
        return (new Response(200))->success($payload);
    }

    /**
     * GET /admin/teams
     */
    public function teams(Request $request): Response
    {
        $auth = $this->requireAdmin($request);
        if ($auth) {
            return $auth;
        }

        $limit = min((int) $request->getQuery('limit', 100), 200);
        $offset = (int) $request->getQuery('offset', 0);
        $teams = $this->teamRepository->listAll($limit, $offset);
        $payload = array_map(fn ($team) => $team->toArray(), $teams);

        return (new Response(200))->success($payload);
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
