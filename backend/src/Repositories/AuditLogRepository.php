<?php

declare(strict_types=1);

namespace InfraMind\Repositories;

use InfraMind\Core\Database;
use InfraMind\Utils\Utils;

/**
 * Audit log repository for tracking all state changes.
 */
class AuditLogRepository
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Log state change.
     */
    public function log(
        string $entityType,
        string $entityId,
        string $action,
        string $userId,
        array $changes = [],
    ): void {
        $sql = 'INSERT INTO audit_logs (id, entity_type, entity_id, action, user_id, changes, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?)';

        $this->db->execute($sql, [
            Utils::generateUuid(),
            $entityType,
            $entityId,
            $action,
            $userId,
            json_encode($changes),
            Utils::now(),
        ]);
    }

    /**
     * Get audit history for entity.
     */
    public function getForEntity(string $entityType, string $entityId): array
    {
        $sql = 'SELECT * FROM audit_logs
                WHERE entity_type = ? AND entity_id = ?
                ORDER BY created_at DESC';

        return $this->db->fetchAll($sql, [$entityType, $entityId]);
    }

    /**
     * Get audit history for user.
     */
    public function getForUser(string $userId, int $limit = 100): array
    {
        $sql = 'SELECT * FROM audit_logs
                WHERE user_id = ?
                ORDER BY created_at DESC LIMIT ?';

        return $this->db->fetchAll($sql, [$userId, $limit]);
    }

    /**
     * List audit logs with optional filters.
     */
    public function listFiltered(array $filters = [], int $limit = 200): array
    {
        $sql = 'SELECT * FROM audit_logs WHERE 1=1';
        $params = [];

        if (!empty($filters['entity_type'])) {
            $sql .= ' AND entity_type = ?';
            $params[] = $filters['entity_type'];
        }

        if (!empty($filters['action'])) {
            $sql .= ' AND action = ?';
            $params[] = $filters['action'];
        }

        if (!empty($filters['user_id'])) {
            $sql .= ' AND user_id = ?';
            $params[] = $filters['user_id'];
        }

        if (!empty($filters['start'])) {
            $sql .= ' AND created_at >= ?';
            $params[] = $filters['start'];
        }

        if (!empty($filters['end'])) {
            $sql .= ' AND created_at <= ?';
            $params[] = $filters['end'];
        }

        $sql .= ' ORDER BY created_at DESC LIMIT ?';
        $params[] = $limit;

        return $this->db->fetchAll($sql, $params);
    }
}
