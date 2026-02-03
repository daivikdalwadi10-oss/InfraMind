<?php

declare(strict_types=1);

namespace InfraMind\Repositories;

use InfraMind\Core\Database;
use InfraMind\Models\User;
use InfraMind\Models\UserRole;
use InfraMind\Exceptions\NotFoundException;

/**
 * User repository for database operations.
 */
class UserRepository
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Create new user.
     */
    public function create(User $user): User
    {
        $sql = 'INSERT INTO users (id, email, password_hash, role, display_name, created_at, is_active)
                VALUES (?, ?, ?, ?, ?, ?, ?)';

        $this->db->execute($sql, [
            $user->id,
            $user->email,
            $user->passwordHash,
            $user->role->value,
            $user->displayName,
            $user->createdAt,
            $user->isActive ? 1 : 0,
        ]);

        return $user;
    }

    /**
     * Find user by ID.
     */
    public function findById(string $id): ?User
    {
        $sql = 'SELECT * FROM users WHERE id = ? AND deleted_at IS NULL';
        $row = $this->db->fetchOne($sql, [$id]);

        return $row ? $this->mapRowToUser($row) : null;
    }

    /**
     * Find user by email.
     */
    public function findByEmail(string $email): ?User
    {
        $sql = 'SELECT * FROM users WHERE email = ? AND deleted_at IS NULL';
        $row = $this->db->fetchOne($sql, [strtolower($email)]);

        return $row ? $this->mapRowToUser($row) : null;
    }

    /**
     * Update user.
     */
    public function update(User $user): User
    {
        $sql = 'UPDATE users SET display_name = ?, role = ?, is_active = ?
                WHERE id = ? AND deleted_at IS NULL';

        $this->db->execute($sql, [
            $user->displayName,
            $user->role->value,
            $user->isActive ? 1 : 0,
            $user->id,
        ]);

        return $user;
    }

    /**
     * Record last login.
     */
    public function recordLogin(string $userId): void
    {
        $sql = "UPDATE users SET last_login_at = datetime(\"now\") WHERE id = ?";
        $this->db->execute($sql, [$userId]);
    }

    /**
     * Soft delete user.
     */
    public function delete(string $userId): void
    {
        $sql = "UPDATE users SET deleted_at = datetime(\"now\") WHERE id = ?";
        $this->db->execute($sql, [$userId]);
    }

    /**
     * List all active users.
     */
    public function listActive(int $limit = 50, int $offset = 0): array
    {
        $sql = 'SELECT * FROM users WHERE deleted_at IS NULL
                ORDER BY created_at DESC LIMIT ? OFFSET ?';

        $rows = $this->db->fetchAll($sql, [$limit, $offset]);
        return array_map([$this, 'mapRowToUser'], $rows);
    }

    /**
     * Count active users.
     */
    public function countActive(): int
    {
        $sql = 'SELECT COUNT(*) as count FROM users WHERE deleted_at IS NULL';
        $row = $this->db->fetchOne($sql);
        return $row['count'] ?? 0;
    }

    /**
     * Map database row to User model.
     */
    private function mapRowToUser(array $row): User
    {
        $user = new User(
            $row['id'],
            $row['email'],
            $row['password_hash'],
            UserRole::from($row['role']),
            $row['display_name'],
            is_int($row['created_at']) ? date('Y-m-d H:i:s', $row['created_at']) : $row['created_at'],
        );

        $user->lastLoginAt = $row['last_login_at'] ? (is_int($row['last_login_at']) ? date('Y-m-d H:i:s', $row['last_login_at']) : $row['last_login_at']) : null;
        $user->isActive = (bool) $row['is_active'];
        $user->deletedAt = $row['deleted_at'] ? (is_int($row['deleted_at']) ? date('Y-m-d H:i:s', $row['deleted_at']) : $row['deleted_at']) : null;

        return $user;
    }
}
