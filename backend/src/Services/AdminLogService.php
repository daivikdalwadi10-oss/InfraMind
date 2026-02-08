<?php

declare(strict_types=1);

namespace InfraMind\Services;

use InfraMind\Core\Database;
use InfraMind\Repositories\AuditLogRepository;

class AdminLogService
{
    private Database $db;
    private AuditLogRepository $auditRepository;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->auditRepository = new AuditLogRepository();
    }

    public function listLogs(string $type, array $filters, int $limit = 200): array
    {
        return match ($type) {
            'app' => $this->readAppLogs(null, $limit),
            'error' => $this->readAppLogs(['ERROR', 'CRITICAL', 'ALERT', 'EMERGENCY'], $limit),
            'auth' => $this->listAuditLogs('AUTH', $filters, $limit),
            'workflow' => $this->listWorkflowLogs($filters, $limit),
            'ai' => $this->listAuditLogs('AI_OUTPUT', $filters, $limit),
            default => $this->listAuditLogs(null, $filters, $limit),
        };
    }

    private function listAuditLogs(?string $entityType, array $filters, int $limit): array
    {
        $sql = 'SELECT a.*, u.role AS user_role, u.email AS user_email
                FROM audit_logs a
                LEFT JOIN users u ON u.id = a.user_id
                WHERE 1=1';
        $params = [];

        if ($entityType) {
            $sql .= ' AND a.entity_type = ?';
            $params[] = $entityType;
        }

        if (!empty($filters['user_id'])) {
            $sql .= ' AND a.user_id = ?';
            $params[] = $filters['user_id'];
        }

        if (!empty($filters['role'])) {
            $sql .= ' AND u.role = ?';
            $params[] = $filters['role'];
        }

        if (!empty($filters['start'])) {
            $sql .= ' AND a.created_at >= ?';
            $params[] = $filters['start'];
        }

        if (!empty($filters['end'])) {
            $sql .= ' AND a.created_at <= ?';
            $params[] = $filters['end'];
        }

        $sql .= ' ORDER BY a.created_at DESC LIMIT ?';
        $params[] = $limit;

        return $this->db->fetchAll($sql, $params);
    }

    private function listWorkflowLogs(array $filters, int $limit): array
    {
        $sql = 'SELECT h.id, h.analysis_id, h.status, h.details, h.changed_at, h.changed_by, u.role AS user_role, u.email AS user_email
                FROM analysis_status_history h
                LEFT JOIN users u ON u.id = h.changed_by
                WHERE 1=1';
        $params = [];

        if (!empty($filters['user_id'])) {
            $sql .= ' AND h.changed_by = ?';
            $params[] = $filters['user_id'];
        }

        if (!empty($filters['role'])) {
            $sql .= ' AND u.role = ?';
            $params[] = $filters['role'];
        }

        if (!empty($filters['start'])) {
            $sql .= ' AND h.changed_at >= ?';
            $params[] = $filters['start'];
        }

        if (!empty($filters['end'])) {
            $sql .= ' AND h.changed_at <= ?';
            $params[] = $filters['end'];
        }

        $sql .= ' ORDER BY h.changed_at DESC LIMIT ?';
        $params[] = $limit;

        return $this->db->fetchAll($sql, $params);
    }

    private function readAppLogs(?array $levels, int $limit): array
    {
        $path = rtrim($_ENV['LOG_PATH'] ?? './logs', '/\\') . '/app.log';
        if (!is_file($path)) {
            return [];
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (!$lines) {
            return [];
        }

        $lines = array_slice($lines, max(0, count($lines) - $limit));
        $entries = [];

        foreach (array_reverse($lines) as $line) {
            $parsed = $this->parseLogLine($line);
            if (!$parsed) {
                continue;
            }
            if ($levels && !in_array($parsed['level'], $levels, true)) {
                continue;
            }
            $entries[] = $parsed;
        }

        return $entries;
    }

    private function parseLogLine(string $line): ?array
    {
        if (!preg_match('/^\[(.*?)\]\s+([A-Z]+):\s+(.*?)(?:\s+(\{.*\}|\[.*\]))?(?:\s+(\{.*\}|\[.*\]))?$/', $line, $matches)) {
            return null;
        }

        return [
            'timestamp' => $matches[1],
            'level' => $matches[2],
            'message' => trim($matches[3]),
            'context' => $matches[4] ?? null,
            'extra' => $matches[5] ?? null,
            'source' => 'app.log',
        ];
    }
}
