<?php

declare(strict_types=1);

namespace InfraMind\Repositories;

use InfraMind\Core\Database;
use InfraMind\Models\ArchitectureRisk;
use InfraMind\Models\RiskSeverity;
use InfraMind\Models\RiskStatus;

/**
 * Architecture risk repository for database operations.
 */
class ArchitectureRiskRepository
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Create new architecture risk.
     */
    public function create(ArchitectureRisk $risk): ArchitectureRisk
    {
        $sql = 'INSERT INTO architecture_risks (
                    id, title, description, severity, status, owner_id,
                    analysis_id, created_at, updated_at, resolved_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';

        $this->db->execute($sql, [
            $risk->id,
            $risk->title,
            $risk->description,
            $risk->severity->value,
            $risk->status->value,
            $risk->ownerId,
            $risk->analysisId,
            $risk->createdAt,
            $risk->updatedAt,
            $risk->resolvedAt,
        ]);

        return $risk;
    }

    /**
     * Find architecture risk by ID.
     */
    public function findById(string $id): ?ArchitectureRisk
    {
        $sql = 'SELECT * FROM architecture_risks WHERE id = ?';
        $row = $this->db->fetchOne($sql, [$id]);

        return $row ? $this->mapRowToRisk($row) : null;
    }

    /**
     * List architecture risks.
     */
    public function listAll(int $limit = 50, int $offset = 0, ?string $status = null, ?string $severity = null): array
    {
        $sql = 'SELECT * FROM architecture_risks WHERE 1=1';
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
        return array_map([$this, 'mapRowToRisk'], $rows);
    }

    /**
     * Update architecture risk.
     */
    public function update(ArchitectureRisk $risk): ArchitectureRisk
    {
        $sql = 'UPDATE architecture_risks SET
                    title = ?, description = ?, severity = ?, status = ?,
                    owner_id = ?, analysis_id = ?, updated_at = ?, resolved_at = ?
                WHERE id = ?';

        $this->db->execute($sql, [
            $risk->title,
            $risk->description,
            $risk->severity->value,
            $risk->status->value,
            $risk->ownerId,
            $risk->analysisId,
            $risk->updatedAt,
            $risk->resolvedAt,
            $risk->id,
        ]);

        return $risk;
    }

    /**
     * Map database row to ArchitectureRisk model.
     */
    private function mapRowToRisk(array $row): ArchitectureRisk
    {
        return new ArchitectureRisk(
            $row['id'],
            $row['title'],
            $row['description'] ?? null,
            RiskSeverity::from($row['severity']),
            RiskStatus::from($row['status']),
            $row['owner_id'],
            $row['analysis_id'] ?? null,
            $row['created_at'],
            $row['updated_at'],
            $row['resolved_at'] ?? null,
        );
    }
}
