<?php

declare(strict_types=1);

namespace InfraMind\Repositories;

use InfraMind\Core\Database;

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
        $sql = 'INSERT INTO audit_logs (entity_type, entity_id, action, user_id, changes, created_at)
                VALUES (?, ?, ?, ?, ?, datetime("now"))';

        $this->db->execute($sql, [
            $entityType,
            $entityId,
            $action,
            $userId,
            json_encode($changes),
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
}
