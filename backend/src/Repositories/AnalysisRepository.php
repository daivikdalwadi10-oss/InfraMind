<?php

declare(strict_types=1);

namespace InfraMind\Repositories;

use InfraMind\Core\Database;
use InfraMind\Models\Analysis;
use InfraMind\Models\AnalysisStatus;
use InfraMind\Models\AnalysisType;
use InfraMind\Utils\Utils;

/**
 * Analysis repository for database operations.
 */
class AnalysisRepository
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Create new analysis.
     */
    public function create(Analysis $analysis): Analysis
    {
        $sql = 'INSERT INTO analyses (
                    id, task_id, title, employee_id, created_by, team_id, status, analysis_type,
                    readiness_score, revision_count, created_at, updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';

        $this->db->execute($sql, [
            $analysis->id,
            $analysis->taskId,
            $analysis->title,
            $analysis->employeeId,
            $analysis->createdBy,
            $analysis->teamId,
            $analysis->status->value,
            $analysis->analysisType->value,
            $analysis->readinessScore,
            $analysis->revisionCount,
            $analysis->createdAt,
            $analysis->updatedAt,
        ]);

        return $analysis;
    }

    /**
     * Find analysis by ID.
     */
    public function findById(string $id): ?Analysis
    {
        $sql = 'SELECT a.*, i.environment_context, i.timeline_events, i.dependency_impact, i.risk_classification
                FROM analyses a
                LEFT JOIN analysis_inputs i ON a.id = i.analysis_id
                WHERE a.id = ?';
        $row = $this->db->fetchOne($sql, [$id]);
        if (!$row) {
            return null;
        }

        return $this->mapRowToAnalysis($row);
    }

    /**
     * Find analysis by task ID.
     */
    public function findByTaskId(string $taskId): ?Analysis
    {
        $sql = 'SELECT * FROM analyses WHERE task_id = ? LIMIT 1';
        $row = $this->db->fetchOne($sql, [$taskId]);
        if (!$row) {
            return null;
        }

        return $this->mapRowToAnalysis($row);
    }

    /**
     * Update analysis.
     */
    public function update(Analysis $analysis): Analysis
    {
        $sql = 'UPDATE analyses SET
                    title = ?, status = ?, readiness_score = ?, manager_feedback = ?,
                    revision_count = ?, team_id = ?, updated_at = ?
                WHERE id = ?';

        $this->db->execute($sql, [
            $analysis->title,
            $analysis->status->value,
            $analysis->readinessScore,
            $analysis->managerFeedback,
            $analysis->revisionCount,
            $analysis->teamId,
            Utils::now(),
            $analysis->id,
        ]);

        return $analysis;
    }

    /**
     * Update analysis content (symptoms, signals, hypotheses).
     */
    public function updateContent(string $id, array $symptoms, array $signals, array $hypotheses): void
    {
        $sql = 'UPDATE analyses SET symptoms = ?, signals = ?, hypotheses = ?, updated_at = ? WHERE id = ?';

        $this->db->execute($sql, [
            json_encode($symptoms),
            json_encode($signals),
            json_encode($hypotheses),
            Utils::now(),
            $id,
        ]);
    }

    /**
     * Upsert analysis inputs (environment, timeline, dependencies, risks).
     */
    public function upsertInputs(
        string $analysisId,
        array $environmentContext,
        array $timelineEvents,
        array $dependencyImpact,
        array $riskClassification,
    ): void {
        $existing = $this->db->fetchOne('SELECT id FROM analysis_inputs WHERE analysis_id = ?', [$analysisId]);
        if ($existing) {
            $sql = 'UPDATE analysis_inputs
                    SET environment_context = ?, timeline_events = ?, dependency_impact = ?, risk_classification = ?, updated_at = ?
                    WHERE analysis_id = ?';
            $this->db->execute($sql, [
                json_encode($environmentContext),
                json_encode($timelineEvents),
                json_encode($dependencyImpact),
                json_encode($riskClassification),
                Utils::now(),
                $analysisId,
            ]);
            return;
        }

        $sql = 'INSERT INTO analysis_inputs
                (id, analysis_id, environment_context, timeline_events, dependency_impact, risk_classification, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)';

        $this->db->execute($sql, [
            Utils::generateUuid(),
            $analysisId,
            json_encode($environmentContext),
            json_encode($timelineEvents),
            json_encode($dependencyImpact),
            json_encode($riskClassification),
            Utils::now(),
            Utils::now(),
        ]);
    }

    /**
     * List analyses for employee.
     */
    public function listForEmployee(string $employeeId, int $limit = 50, int $offset = 0): array
    {
        $sql = 'SELECT * FROM analyses WHERE employee_id = ?
                ORDER BY created_at DESC LIMIT ? OFFSET ?';

        $rows = $this->db->fetchAll($sql, [$employeeId, $limit, $offset]);
        return array_map([$this, 'mapRowToAnalysis'], $rows);
    }

    /**
     * List analyses for manager review (submitted).
     */
    public function listForReview(string $managerId, int $limit = 50, int $offset = 0): array
    {
        $sql = 'SELECT a.*
                FROM analyses a
                JOIN team_members tm ON tm.user_id = a.employee_id
                JOIN teams t ON t.id = tm.team_id
                WHERE a.status = ? AND t.manager_id = ?
                ORDER BY a.created_at DESC LIMIT ? OFFSET ?';

        $rows = $this->db->fetchAll($sql, [
            AnalysisStatus::SUBMITTED->value,
            $managerId,
            $limit,
            $offset,
        ]);

        return array_map([$this, 'mapRowToAnalysis'], $rows);
    }

    /**
     * Get analysis with full details (including hypotheses).
     */
    public function getWithHypotheses(string $id): ?array
    {
        $sql = 'SELECT a.*, h.id as hypothesis_id, h.text, h.confidence, h.evidence
                FROM analyses a
                LEFT JOIN analysis_hypotheses h ON a.id = h.analysis_id
                WHERE a.id = ?
                ORDER BY h.confidence DESC';

        $rows = $this->db->fetchAll($sql, [$id]);
        if (empty($rows)) {
            return null;
        }

        $analysis = $this->mapRowToAnalysis($rows[0]);
        $hypotheses = [];
        foreach ($rows as $row) {
            if ($row['hypothesis_id']) {
                $hypotheses[] = [
                    'id' => $row['hypothesis_id'],
                    'text' => $row['text'],
                    'confidence' => $row['confidence'],
                    'evidence' => json_decode($row['evidence'], true) ?: [],
                ];
            }
        }

        return [
            'analysis' => $analysis,
            'hypotheses' => $hypotheses,
        ];
    }

    /**
     * Count analyses in draft state by employee.
     */
    public function countDraftsByEmployee(string $employeeId): int
    {
        $sql = 'SELECT COUNT(*) as count FROM analyses
                WHERE employee_id = ? AND status IN (?, ?)';

        $row = $this->db->fetchOne($sql, [
            $employeeId,
            AnalysisStatus::DRAFT->value,
            AnalysisStatus::NEEDS_CHANGES->value,
        ]);

        return $row['count'] ?? 0;
    }

    /**
     * Map database row to Analysis model.
     */
    private function mapRowToAnalysis(array $row): Analysis
    {
        $analysis = new Analysis(
            $row['id'],
            $row['task_id'],
            $row['title'] ?? '',
            $row['employee_id'],
            $row['created_by'] ?? $row['employee_id'],
            AnalysisStatus::from($row['status']),
            AnalysisType::from($row['analysis_type']),
            $row['created_at'],
            $row['updated_at'],
            $row['team_id'] ?? null,
        );

        $analysis->symptoms = json_decode($row['symptoms'] ?? '[]', true) ?: [];
        $analysis->signals = json_decode($row['signals'] ?? '[]', true) ?: [];
        $analysis->hypotheses = json_decode($row['hypotheses'] ?? '[]', true) ?: [];
        $analysis->environmentContext = json_decode($row['environment_context'] ?? '{}', true) ?: [];
        $analysis->timelineEvents = json_decode($row['timeline_events'] ?? '{}', true) ?: [];
        $analysis->dependencyImpact = json_decode($row['dependency_impact'] ?? '{}', true) ?: [];
        $analysis->riskClassification = json_decode($row['risk_classification'] ?? '{}', true) ?: [];
        $analysis->readinessScore = (int) $row['readiness_score'];
        $analysis->revisionCount = (int) $row['revision_count'];
        $analysis->managerFeedback = $row['manager_feedback'];

        return $analysis;
    }
}
