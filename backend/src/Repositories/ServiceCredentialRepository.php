<?php

declare(strict_types=1);

namespace InfraMind\Repositories;

use InfraMind\Core\Database;
use InfraMind\Utils\Utils;

class ServiceCredentialRepository
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function listAll(int $limit = 100, int $offset = 0): array
    {
        $sql = 'SELECT id, name, description, status, masked_value, created_by, created_at, updated_at, last_rotated_at'
            . ' FROM service_credentials ORDER BY created_at DESC LIMIT ? OFFSET ?';
        return $this->db->fetchAll($sql, [$limit, $offset]);
    }

    public function create(array $data, string $userId): array
    {
        $id = Utils::generateUuid();
        $now = Utils::now();
        $secret = (string) ($data['secret'] ?? '');

        $this->db->execute(
            'INSERT INTO service_credentials (id, name, description, status, secret_hash, masked_value, created_by, created_at, updated_at, last_rotated_at)'
            . ' VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $id,
                $data['name'],
                $data['description'] ?? null,
                'ACTIVE',
                hash('sha256', $secret),
                $this->maskSecret($secret),
                $userId,
                $now,
                $now,
                $now,
            ]
        );

        return $this->db->fetchOne('SELECT id, name, description, status, masked_value, created_by, created_at, updated_at, last_rotated_at FROM service_credentials WHERE id = ?', [$id]) ?? [];
    }

    public function rotate(string $id, string $secret, string $userId): bool
    {
        $count = $this->db->executeAffecting(
            'UPDATE service_credentials SET status = ?, secret_hash = ?, masked_value = ?, last_rotated_at = ?, updated_at = ? WHERE id = ?',
            ['ROTATED', hash('sha256', $secret), $this->maskSecret($secret), Utils::now(), Utils::now(), $id]
        );

        return $count > 0;
    }

    public function disable(string $id, string $userId): bool
    {
        $count = $this->db->executeAffecting(
            'UPDATE service_credentials SET status = ?, updated_at = ? WHERE id = ?',
            ['DISABLED', Utils::now(), $id]
        );

        return $count > 0;
    }

    private function maskSecret(string $secret): string
    {
        $tail = strlen($secret) <= 4 ? $secret : substr($secret, -4);
        return '****' . $tail;
    }
}
