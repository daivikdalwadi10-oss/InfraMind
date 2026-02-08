<?php

declare(strict_types=1);

namespace InfraMind\Repositories;

use InfraMind\Core\Database;
use InfraMind\Utils\Utils;

class AnnouncementRepository
{
    private Database $db;
    private string $driver;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->driver = $_ENV['DB_DRIVER'] ?? 'mysql';
    }

    public function listAll(int $limit = 100, int $offset = 0): array
    {
        $sql = 'SELECT * FROM announcements ORDER BY created_at DESC LIMIT ? OFFSET ?';
        return $this->db->fetchAll($sql, [$limit, $offset]);
    }

    public function listActive(?string $role, int $limit = 50): array
    {
        $now = Utils::now();
        $sql = 'SELECT * FROM announcements WHERE status = ?'
            . ' AND (starts_at IS NULL OR starts_at <= ?)'
            . ' AND (ends_at IS NULL OR ends_at >= ?)';

        $params = ['ACTIVE', $now, $now];

        if ($role) {
            $sql .= ' AND (' . $this->roleMatchExpression() . ' OR target_roles = ?)';
            $params[] = "%,$role,%";
            $params[] = 'ALL';
        } else {
            $sql .= ' AND target_roles = ?';
            $params[] = 'ALL';
        }

        $sql .= ' ORDER BY created_at DESC LIMIT ?';
        $params[] = $limit;

        return $this->db->fetchAll($sql, $params);
    }

    public function create(array $data, string $userId): array
    {
        $id = Utils::generateUuid();
        $now = Utils::now();
        $targets = $this->normalizeTargets($data['targetRoles'] ?? 'ALL');

        $this->db->execute(
            'INSERT INTO announcements (id, title, message, severity, target_roles, starts_at, ends_at, dismissible, status, created_by, created_at, updated_at)'
            . ' VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $id,
                $data['title'],
                $data['message'],
                $data['severity'] ?? 'INFO',
                $targets,
                $data['startsAt'] ?? null,
                $data['endsAt'] ?? null,
                !empty($data['dismissible']) ? 1 : 0,
                'ACTIVE',
                $userId,
                $now,
                $now,
            ]
        );

        return $this->db->fetchOne('SELECT * FROM announcements WHERE id = ?', [$id]) ?? [];
    }

    public function archive(string $id, string $userId): bool
    {
        $count = $this->db->executeAffecting(
            'UPDATE announcements SET status = ?, updated_at = ? WHERE id = ?',
            ['ARCHIVED', Utils::now(), $id]
        );

        return $count > 0;
    }

    private function normalizeTargets(string|array $targets): string
    {
        $list = is_array($targets) ? $targets : explode(',', $targets);
        $normalized = array_values(array_filter(array_map(fn ($value) => strtoupper(trim((string) $value)), $list)));

        if (empty($normalized)) {
            return 'ALL';
        }

        return implode(',', array_unique($normalized));
    }

    private function roleMatchExpression(): string
    {
        if ($this->driver === 'sqlite') {
            return "(',' || target_roles || ',') LIKE ?";
        }

        return "CONCAT(',', target_roles, ',') LIKE ?";
    }
}
