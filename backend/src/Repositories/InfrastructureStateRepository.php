<?php

declare(strict_types=1);

namespace InfraMind\Repositories;

use InfraMind\Core\Database;
use InfraMind\Models\InfrastructureState;
use InfraMind\Models\InfrastructureStatus;

/**
 * Infrastructure state repository for database operations.
 */
class InfrastructureStateRepository
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Create new infrastructure state.
     */
    public function create(InfrastructureState $state): InfrastructureState
    {
        $sql = 'INSERT INTO infrastructure_states (
                    id, component, status, summary, observed_at,
                    reported_by, created_at, updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)';

        $this->db->execute($sql, [
            $state->id,
            $state->component,
            $state->status->value,
            $state->summary,
            $state->observedAt,
            $state->reportedBy,
            $state->createdAt,
            $state->updatedAt,
        ]);

        return $state;
    }

    /**
     * Find infrastructure state by ID.
     */
    public function findById(string $id): ?InfrastructureState
    {
        $sql = 'SELECT * FROM infrastructure_states WHERE id = ?';
        $row = $this->db->fetchOne($sql, [$id]);

        return $row ? $this->mapRowToState($row) : null;
    }

    /**
     * List infrastructure states.
     */
    public function listAll(int $limit = 50, int $offset = 0, ?string $status = null): array
    {
        $sql = 'SELECT * FROM infrastructure_states WHERE 1=1';
        $params = [];

        if ($status) {
            $sql .= ' AND status = ?';
            $params[] = $status;
        }

        $sql .= ' ORDER BY observed_at DESC LIMIT ? OFFSET ?';
        $params[] = $limit;
        $params[] = $offset;

        $rows = $this->db->fetchAll($sql, $params);
        return array_map([$this, 'mapRowToState'], $rows);
    }

    /**
     * Update infrastructure state.
     */
    public function update(InfrastructureState $state): InfrastructureState
    {
        $sql = 'UPDATE infrastructure_states SET
                    component = ?, status = ?, summary = ?, observed_at = ?,
                    reported_by = ?, updated_at = ?
                WHERE id = ?';

        $this->db->execute($sql, [
            $state->component,
            $state->status->value,
            $state->summary,
            $state->observedAt,
            $state->reportedBy,
            $state->updatedAt,
            $state->id,
        ]);

        return $state;
    }

    /**
     * Map database row to InfrastructureState model.
     */
    private function mapRowToState(array $row): InfrastructureState
    {
        return new InfrastructureState(
            $row['id'],
            $row['component'],
            InfrastructureStatus::from($row['status']),
            $row['summary'] ?? null,
            $row['observed_at'],
            $row['reported_by'],
            $row['created_at'],
            $row['updated_at'],
        );
    }
}
