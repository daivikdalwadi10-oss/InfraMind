<?php

declare(strict_types=1);

namespace InfraMind\Repositories;

use InfraMind\Core\Database;
use InfraMind\Models\Incident;
use InfraMind\Models\IncidentSeverity;
use InfraMind\Models\IncidentStatus;

/**
 * Incident repository for database operations.
 */
class IncidentRepository
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Create new incident.
     */
    public function create(Incident $incident): Incident
    {
        $sql = 'INSERT INTO incidents (
                    id, title, description, severity, status, reported_by, assigned_to,
                    occurred_at, created_at, updated_at, resolved_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';

        $this->db->execute($sql, [
            $incident->id,
            $incident->title,
            $incident->description,
            $incident->severity->value,
            $incident->status->value,
            $incident->reportedBy,
            $incident->assignedTo,
            $incident->occurredAt,
            $incident->createdAt,
            $incident->updatedAt,
            $incident->resolvedAt,
        ]);

        return $incident;
    }

    /**
     * Find incident by ID.
     */
    public function findById(string $id): ?Incident
    {
        $sql = 'SELECT * FROM incidents WHERE id = ?';
        $row = $this->db->fetchOne($sql, [$id]);

        return $row ? $this->mapRowToIncident($row) : null;
    }

    /**
     * List incidents for an employee (reported or assigned).
     */
    public function listForEmployee(string $userId, int $limit = 50, int $offset = 0): array
    {
        $sql = 'SELECT * FROM incidents
                WHERE reported_by = ? OR assigned_to = ?
                ORDER BY created_at DESC LIMIT ? OFFSET ?';

        $rows = $this->db->fetchAll($sql, [$userId, $userId, $limit, $offset]);
        return array_map([$this, 'mapRowToIncident'], $rows);
    }

    /**
     * List incidents for managers/owners.
     */
    public function listAll(int $limit = 50, int $offset = 0, ?string $status = null, ?string $severity = null): array
    {
        $sql = 'SELECT * FROM incidents WHERE 1=1';
        $params = [];

        if ($status) {
            $sql .= ' AND status = ?';
            $params[] = $status;
        }

        if ($severity) {
            $sql .= ' AND severity = ?';
            $params[] = $severity;
        }

        $sql .= ' ORDER BY created_at DESC LIMIT ? OFFSET ?';
        $params[] = $limit;
        $params[] = $offset;

        $rows = $this->db->fetchAll($sql, $params);
        return array_map([$this, 'mapRowToIncident'], $rows);
    }

    /**
     * Update incident.
     */
    public function update(Incident $incident): Incident
    {
        $sql = 'UPDATE incidents SET
                    title = ?, description = ?, severity = ?, status = ?,
                    reported_by = ?, assigned_to = ?, occurred_at = ?,
                    updated_at = ?, resolved_at = ?
                WHERE id = ?';

        $this->db->execute($sql, [
            $incident->title,
            $incident->description,
            $incident->severity->value,
            $incident->status->value,
            $incident->reportedBy,
            $incident->assignedTo,
            $incident->occurredAt,
            $incident->updatedAt,
            $incident->resolvedAt,
            $incident->id,
        ]);

        return $incident;
    }

    /**
     * Map database row to Incident model.
     */
    private function mapRowToIncident(array $row): Incident
    {
        return new Incident(
            $row['id'],
            $row['title'],
            $row['description'] ?? null,
            IncidentSeverity::from($row['severity']),
            IncidentStatus::from($row['status']),
            $row['reported_by'],
            $row['assigned_to'] ?? null,
            $row['occurred_at'] ?? null,
            $row['created_at'],
            $row['updated_at'],
            $row['resolved_at'] ?? null,
        );
    }
}
