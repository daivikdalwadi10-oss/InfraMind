<?php

declare(strict_types=1);

namespace InfraMind\Repositories;

use InfraMind\Core\Database;
use InfraMind\Models\AiOutputStatus;
use InfraMind\Models\AiOutputType;
use InfraMind\Utils\Utils;

/**
 * AI outputs repository.
 */
class AiOutputRepository
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function create(string $analysisId, AiOutputType $type, array $payload, string $generatedBy = 'AI'): array
    {
        $id = Utils::generateUuid();
        $now = Utils::now();

        $this->db->execute(
            'INSERT INTO ai_outputs (id, analysis_id, output_type, generated_by, status, payload, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?)',
            [
                $id,
                $analysisId,
                $type->value,
                $generatedBy,
                AiOutputStatus::GENERATED->value,
                json_encode($payload),
                $now,
            ]
        );

        return [
            'id' => $id,
            'analysisId' => $analysisId,
            'outputType' => $type->value,
            'generatedBy' => $generatedBy,
            'status' => AiOutputStatus::GENERATED->value,
            'payload' => $payload,
            'createdAt' => $now,
        ];
    }

    public function listByAnalysis(string $analysisId): array
    {
        $rows = $this->db->fetchAll(
            'SELECT * FROM ai_outputs WHERE analysis_id = ? ORDER BY created_at DESC',
            [$analysisId]
        );

        return array_map([$this, 'mapRow'], $rows);
    }

    public function findById(string $id): ?array
    {
        $row = $this->db->fetchOne('SELECT * FROM ai_outputs WHERE id = ?', [$id]);
        return $row ? $this->mapRow($row) : null;
    }

    public function updateStatus(string $id, AiOutputStatus $status, ?array $payload = null): void
    {
        if ($payload !== null) {
            $this->db->execute(
                'UPDATE ai_outputs SET status = ?, payload = ? WHERE id = ?',
                [$status->value, json_encode($payload), $id]
            );
            return;
        }

        $this->db->execute(
            'UPDATE ai_outputs SET status = ? WHERE id = ?',
            [$status->value, $id]
        );
    }

    private function mapRow(array $row): array
    {
        return [
            'id' => $row['id'],
            'analysisId' => $row['analysis_id'],
            'outputType' => $row['output_type'],
            'generatedBy' => $row['generated_by'],
            'status' => $row['status'],
            'payload' => json_decode($row['payload'] ?? '{}', true) ?: [],
            'createdAt' => $row['created_at'],
        ];
    }
}
