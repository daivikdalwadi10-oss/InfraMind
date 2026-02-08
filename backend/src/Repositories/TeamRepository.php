<?php

declare(strict_types=1);

namespace InfraMind\Repositories;

use InfraMind\Core\Database;
use InfraMind\Models\Team;
use InfraMind\Utils\Utils;

/**
 * Team repository for database operations.
 */
class TeamRepository
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function create(Team $team): Team
    {
        $sql = 'INSERT INTO teams (id, name, description, manager_id, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?)';

        $this->db->execute($sql, [
            $team->id,
            $team->name,
            $team->description,
            $team->managerId,
            $team->createdAt,
            $team->updatedAt,
        ]);

        return $team;
    }

    public function findById(string $id): ?Team
    {
        $row = $this->db->fetchOne('SELECT * FROM teams WHERE id = ?', [$id]);
        return $row ? $this->mapRowToTeam($row) : null;
    }

    public function listForManager(string $managerId, int $limit = 50, int $offset = 0): array
    {
        $rows = $this->db->fetchAll(
            'SELECT * FROM teams WHERE manager_id = ? ORDER BY created_at DESC LIMIT ? OFFSET ?',
            [$managerId, $limit, $offset]
        );

        return array_map([$this, 'mapRowToTeam'], $rows);
    }

    public function listAll(int $limit = 50, int $offset = 0): array
    {
        $rows = $this->db->fetchAll(
            'SELECT * FROM teams ORDER BY created_at DESC LIMIT ? OFFSET ?',
            [$limit, $offset]
        );

        return array_map([$this, 'mapRowToTeam'], $rows);
    }

    public function addMember(string $teamId, string $userId): void
    {
        $sql = 'INSERT INTO team_members (id, team_id, user_id, created_at)
                VALUES (?, ?, ?, ?)';

        $this->db->execute($sql, [
            Utils::generateUuid(),
            $teamId,
            $userId,
            Utils::now(),
        ]);
    }

    public function removeMember(string $teamId, string $userId): void
    {
        $this->db->execute('DELETE FROM team_members WHERE team_id = ? AND user_id = ?', [$teamId, $userId]);
    }

    public function listMembers(string $teamId): array
    {
        $sql = 'SELECT u.id, u.email, u.display_name, u.role, u.position
                FROM team_members tm
                JOIN users u ON u.id = tm.user_id
                WHERE tm.team_id = ? AND u.deleted_at IS NULL
                ORDER BY u.display_name ASC';

        return $this->db->fetchAll($sql, [$teamId]);
    }

    public function isMember(string $teamId, string $userId): bool
    {
        $row = $this->db->fetchOne(
            'SELECT 1 FROM team_members WHERE team_id = ? AND user_id = ? LIMIT 1',
            [$teamId, $userId]
        );

        return (bool) $row;
    }

    public function isManagerOfEmployee(string $managerId, string $employeeId): bool
    {
        $row = $this->db->fetchOne(
            'SELECT 1
             FROM team_members tm
             JOIN teams t ON t.id = tm.team_id
             WHERE t.manager_id = ? AND tm.user_id = ?
             LIMIT 1',
            [$managerId, $employeeId]
        );

        return (bool) $row;
    }

    private function mapRowToTeam(array $row): Team
    {
        return new Team(
            $row['id'],
            $row['name'],
            $row['description'] ?? null,
            $row['manager_id'],
            $row['created_at'],
            $row['updated_at'] ?? $row['created_at'],
        );
    }
}
