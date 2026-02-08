<?php

declare(strict_types=1);

namespace InfraMind\Repositories;

use InfraMind\Core\Database;
use InfraMind\Models\User;
use InfraMind\Models\UserRole;
use InfraMind\Exceptions\NotFoundException;
use InfraMind\Utils\Utils;

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
        $sql = 'INSERT INTO users (id, email, password_hash, role, display_name, position, created_at, is_active)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)';

        $this->db->execute($sql, [
            $user->id,
            $user->email,
            $user->passwordHash,
            $user->role->value,
            $user->displayName,
            $user->position,
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
        $sql = 'UPDATE users SET display_name = ?, role = ?, position = ?, is_active = ?
                WHERE id = ? AND deleted_at IS NULL';

        $this->db->execute($sql, [
            $user->displayName,
            $user->role->value,
            $user->position,
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
        $sql = 'UPDATE users SET last_login_at = ? WHERE id = ?';
        $this->db->execute($sql, [Utils::now(), $userId]);
    }

    /**
     * Soft delete user.
     */
    public function delete(string $userId): void
    {
        $sql = 'UPDATE users SET deleted_at = ? WHERE id = ?';
        $this->db->execute($sql, [Utils::now(), $userId]);
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
     * List employees managed by a manager with workload context.
     */
    public function listManagedEmployeesWithWorkload(string $managerId, int $limit = 50, int $offset = 0): array
    {
        $sql = "SELECT
                    u.id,
                    u.email,
                    u.display_name,
                    u.role,
                    u.position,
                    GROUP_CONCAT(DISTINCT t.name) AS teams,
                    COUNT(DISTINCT a.id) AS active_analysis_count,
                    GROUP_CONCAT(DISTINCT a.id) AS active_analysis_ids
                FROM users u
                JOIN team_members tm ON tm.user_id = u.id
                JOIN teams t ON t.id = tm.team_id
                LEFT JOIN analyses a ON a.employee_id = u.id AND a.status IN ('DRAFT','NEEDS_CHANGES','SUBMITTED')
                WHERE t.manager_id = ? AND u.deleted_at IS NULL
                GROUP BY u.id
                ORDER BY u.created_at DESC
                LIMIT ? OFFSET ?";

        return $this->db->fetchAll($sql, [$managerId, $limit, $offset]);
    }

    /**
     * List all employees with workload context (owner).
     */
    public function listAllEmployeesWithWorkload(int $limit = 50, int $offset = 0): array
    {
        $sql = "SELECT
                    u.id,
                    u.email,
                    u.display_name,
                    u.role,
                    u.position,
                    GROUP_CONCAT(DISTINCT t.name) AS teams,
                    COUNT(DISTINCT a.id) AS active_analysis_count,
                    GROUP_CONCAT(DISTINCT a.id) AS active_analysis_ids
                FROM users u
                LEFT JOIN team_members tm ON tm.user_id = u.id
                LEFT JOIN teams t ON t.id = tm.team_id
                LEFT JOIN analyses a ON a.employee_id = u.id AND a.status IN ('DRAFT','NEEDS_CHANGES','SUBMITTED')
                WHERE u.deleted_at IS NULL
                GROUP BY u.id
                ORDER BY u.created_at DESC
                LIMIT ? OFFSET ?";

        return $this->db->fetchAll($sql, [$limit, $offset]);
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
            $row['position'] ?? null,
        );

        $user->lastLoginAt = $row['last_login_at'] ? (is_int($row['last_login_at']) ? date('Y-m-d H:i:s', $row['last_login_at']) : $row['last_login_at']) : null;
        $user->isActive = (bool) $row['is_active'];
        $user->deletedAt = $row['deleted_at'] ? (is_int($row['deleted_at']) ? date('Y-m-d H:i:s', $row['deleted_at']) : $row['deleted_at']) : null;

        return $user;
    }
}
