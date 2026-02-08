<?php

declare(strict_types=1);

namespace InfraMind\Repositories;

use InfraMind\Core\Database;
use InfraMind\Utils\Utils;

class FeatureFlagRepository
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function listAll(): array
    {
        return $this->db->fetchAll('SELECT * FROM feature_flags ORDER BY flag_key ASC');
    }

    public function upsert(string $key, bool $enabled, ?string $description, ?string $userId): array
    {
        $existing = $this->db->fetchOne('SELECT id FROM feature_flags WHERE flag_key = ?', [$key]);
        $now = Utils::now();

        if ($existing) {
            $this->db->execute(
                'UPDATE feature_flags SET enabled = ?, description = ?, updated_by = ?, updated_at = ? WHERE flag_key = ?',
                [$enabled ? 1 : 0, $description, $userId, $now, $key]
            );
        } else {
            $this->db->execute(
                'INSERT INTO feature_flags (id, flag_key, description, enabled, updated_by, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?)',
                [Utils::generateUuid(), $key, $description, $enabled ? 1 : 0, $userId, $now, $now]
            );
        }

        return $this->db->fetchOne('SELECT * FROM feature_flags WHERE flag_key = ?', [$key]) ?? [];
    }
}
