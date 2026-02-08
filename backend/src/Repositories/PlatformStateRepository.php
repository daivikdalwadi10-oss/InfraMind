<?php

declare(strict_types=1);

namespace InfraMind\Repositories;

use InfraMind\Core\Database;
use InfraMind\Utils\Utils;

class PlatformStateRepository
{
    private const STATE_ID = 'platform_state';

    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function getState(): array
    {
        $state = $this->db->fetchOne('SELECT * FROM platform_state WHERE id = ?', [self::STATE_ID]);
        if ($state) {
            return $state;
        }

        $now = Utils::now();
        $this->db->execute(
            'INSERT INTO platform_state (id, maintenance_enabled, maintenance_message, soft_shutdown_enabled, updated_at) VALUES (?, ?, ?, ?, ?)',
            [self::STATE_ID, 0, null, 0, $now]
        );

        return $this->db->fetchOne('SELECT * FROM platform_state WHERE id = ?', [self::STATE_ID]) ?? [
            'id' => self::STATE_ID,
            'maintenance_enabled' => 0,
            'maintenance_message' => null,
            'soft_shutdown_enabled' => 0,
            'last_restart_requested_at' => null,
            'updated_by' => null,
            'updated_at' => $now,
        ];
    }

    public function updateMaintenance(bool $enabled, ?string $message, string $userId): array
    {
        $this->ensureState();
        $this->db->execute(
            'UPDATE platform_state SET maintenance_enabled = ?, maintenance_message = ?, updated_by = ?, updated_at = ? WHERE id = ?',
            [$enabled ? 1 : 0, $message, $userId, Utils::now(), self::STATE_ID]
        );

        return $this->getState();
    }

    public function updateSoftShutdown(bool $enabled, string $userId): array
    {
        $this->ensureState();
        $this->db->execute(
            'UPDATE platform_state SET soft_shutdown_enabled = ?, updated_by = ?, updated_at = ? WHERE id = ?',
            [$enabled ? 1 : 0, $userId, Utils::now(), self::STATE_ID]
        );

        return $this->getState();
    }

    public function recordRestartRequest(string $userId): array
    {
        $this->ensureState();
        $this->db->execute(
            'UPDATE platform_state SET last_restart_requested_at = ?, updated_by = ?, updated_at = ? WHERE id = ?',
            [Utils::now(), $userId, Utils::now(), self::STATE_ID]
        );

        return $this->getState();
    }

    private function ensureState(): void
    {
        $existing = $this->db->fetchOne('SELECT id FROM platform_state WHERE id = ?', [self::STATE_ID]);
        if ($existing) {
            return;
        }

        $this->db->execute(
            'INSERT INTO platform_state (id, maintenance_enabled, maintenance_message, soft_shutdown_enabled, updated_at) VALUES (?, ?, ?, ?, ?)',
            [self::STATE_ID, 0, null, 0, Utils::now()]
        );
    }
}
