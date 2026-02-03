<?php

declare(strict_types=1);

namespace InfraMind\Services;

use InfraMind\Repositories\AnalysisRepository;
use InfraMind\Repositories\AuditLogRepository;
use InfraMind\Repositories\TaskRepository;
use InfraMind\Models\Analysis;
use InfraMind\Models\AnalysisStatus;
use InfraMind\Models\AnalysisType;
use InfraMind\Exceptions\NotFoundException;
use InfraMind\Exceptions\AuthorizationException;
use InfraMind\Exceptions\InvalidStateException;
use InfraMind\Core\Logger;
use InfraMind\Core\Database;

/**
 * Analysis service with workflow state machine enforcement.
 */
class AnalysisService
{
    private AnalysisRepository $analysisRepository;
    private TaskRepository $taskRepository;
    private AuditLogRepository $auditRepository;
    private Logger $logger;
    private Database $db;

    // Minimum readiness score to allow submission
    private const MIN_READINESS_FOR_SUBMISSION = 75;

    public function __construct()
    {
        $this->analysisRepository = new AnalysisRepository();
        $this->taskRepository = new TaskRepository();
        $this->auditRepository = new AuditLogRepository();
        $this->logger = Logger::getInstance();
        $this->db = Database::getInstance();
    }

    /**
     * Create analysis (employee only).
     */
    public function createAnalysis(
        string $employeeId,
        string $taskId,
        string $analysisType,
    ): Analysis {
        // Verify task exists and is assigned to employee
        $task = $this->taskRepository->findById($taskId);
        if (!$task) {
            throw new NotFoundException('Task not found');
        }

        if ($task->assignedTo !== $employeeId) {
            throw new AuthorizationException('Task not assigned to you');
        }

        // Check if analysis already exists for this task
        $existing = $this->analysisRepository->findByTaskId($taskId);
        if ($existing) {
            throw new InvalidStateException('Analysis already exists for this task');
        }

        $analysisId = $this->generateUuid();
        $now = date('Y-m-d H:i:s');

        $analysis = new Analysis(
            $analysisId,
            $taskId,
            $employeeId,
            AnalysisStatus::DRAFT,
            AnalysisType::from($analysisType),
            $now,
            $now,
        );

        $analysis = $this->analysisRepository->create($analysis);

        $this->recordStatusChange(
            $analysisId,
            AnalysisStatus::DRAFT,
            $employeeId,
            'CREATED',
        );

        $this->auditRepository->log('Analysis', $analysisId, 'CREATED', $employeeId, [
            'taskId' => $taskId,
            'type' => $analysisType,
        ]);

        $this->logger->info("Analysis created: $analysisId for task: $taskId by: $employeeId");

        return $analysis;
    }

    /**
     * Get analysis by ID.
     */
    public function getAnalysis(string $analysisId): Analysis
    {
        $analysis = $this->analysisRepository->findById($analysisId);
        if (!$analysis) {
            throw new NotFoundException('Analysis not found');
        }
        return $analysis;
    }

    /**
     * Update analysis content (symptoms, signals, hypotheses).
     * Only allowed in DRAFT or NEEDS_CHANGES status.
     */
    public function updateAnalysisContent(
        string $analysisId,
        string $employeeId,
        array $symptoms,
        array $signals,
        array $hypotheses,
        int $readinessScore,
    ): Analysis {
        $analysis = $this->getAnalysis($analysisId);

        // Verify employee owns this analysis
        if ($analysis->employeeId !== $employeeId) {
            throw new AuthorizationException('You can only update your own analyses');
        }

        // Check status allows editing
        if (!in_array($analysis->status->value, ['DRAFT', 'NEEDS_CHANGES'], true)) {
            throw new InvalidStateException(
                'Can only edit analyses in DRAFT or NEEDS_CHANGES status. Current: ' . $analysis->status->value
            );
        }

        // Store old state for audit
        $oldState = [
            'symptoms' => $analysis->symptoms,
            'signals' => $analysis->signals,
            'hypotheses' => $analysis->hypotheses,
            'readinessScore' => $analysis->readinessScore,
        ];

        // Update content
        $analysis->symptoms = array_filter($symptoms);
        $analysis->signals = array_filter($signals);
        $analysis->hypotheses = $hypotheses; // Keep as-is for complex structures
        $analysis->readinessScore = $readinessScore;
        $analysis->revisionCount++;

        $this->analysisRepository->updateContent(
            $analysisId,
            $analysis->symptoms,
            $analysis->signals,
            $analysis->hypotheses,
        );

        // Update in main table
        $this->analysisRepository->update($analysis);

        // Record revision
        $this->recordRevision(
            $analysisId,
            $analysis->revisionCount,
            $analysis->symptoms,
            $analysis->signals,
            $analysis->hypotheses,
            $readinessScore,
            $employeeId,
        );

        $this->auditRepository->log('Analysis', $analysisId, 'CONTENT_UPDATED', $employeeId, [
            'oldState' => $oldState,
            'newState' => [
                'symptoms' => $analysis->symptoms,
                'signals' => $analysis->signals,
                'hypotheses' => count($analysis->hypotheses),
                'readinessScore' => $readinessScore,
            ],
        ]);

        return $analysis;
    }

    /**
     * Submit analysis (employee).
     * Only allowed in DRAFT or NEEDS_CHANGES status.
     * Requires readiness score >= 75.
     */
    public function submitAnalysis(string $analysisId, string $employeeId): Analysis
    {
        $analysis = $this->getAnalysis($analysisId);

        // Verify employee owns this analysis
        if ($analysis->employeeId !== $employeeId) {
            throw new AuthorizationException('You can only submit your own analyses');
        }

        // Check current status allows submission
        if (!in_array($analysis->status->value, ['DRAFT', 'NEEDS_CHANGES'], true)) {
            throw new InvalidStateException(
                'Analysis must be in DRAFT or NEEDS_CHANGES to submit. Current: ' . $analysis->status->value
            );
        }

        // Check readiness score
        if ($analysis->readinessScore < self::MIN_READINESS_FOR_SUBMISSION) {
            throw new InvalidStateException(
                'Readiness score must be at least ' . self::MIN_READINESS_FOR_SUBMISSION .
                '. Current: ' . $analysis->readinessScore
            );
        }

        // Transition state
        $analysis->status = AnalysisStatus::SUBMITTED;

        $this->analysisRepository->update($analysis);

        $this->recordStatusChange(
            $analysisId,
            AnalysisStatus::SUBMITTED,
            $employeeId,
            'SUBMITTED_BY_EMPLOYEE',
        );

        $this->auditRepository->log('Analysis', $analysisId, 'SUBMITTED', $employeeId, [
            'readinessScore' => $analysis->readinessScore,
        ]);

        $this->logger->info("Analysis submitted: $analysisId by: $employeeId");

        return $analysis;
    }

    /**
     * Manager reviews analysis.
     * Transitions to APPROVED or NEEDS_CHANGES.
     */
    public function reviewAnalysis(
        string $analysisId,
        string $managerId,
        string $decision, // 'APPROVE' or 'REJECT'
        ?string $feedback = null,
    ): Analysis {
        $analysis = $this->getAnalysis($analysisId);

        // Only manager can review submitted analyses
        if ($analysis->status->value !== AnalysisStatus::SUBMITTED->value) {
            throw new InvalidStateException('Can only review SUBMITTED analyses');
        }

        if (!in_array($decision, ['APPROVE', 'REJECT'], true)) {
            throw new \InvalidArgumentException('Decision must be APPROVE or REJECT');
        }

        // Transition
        if ($decision === 'APPROVE') {
            $analysis->status = AnalysisStatus::APPROVED;
        } else {
            $analysis->status = AnalysisStatus::NEEDS_CHANGES;
            $analysis->managerFeedback = $feedback;
        }

        $this->analysisRepository->update($analysis);

        $this->recordStatusChange(
            $analysisId,
            $analysis->status,
            $managerId,
            'REVIEWED_BY_MANAGER',
        );

        $this->auditRepository->log('Analysis', $analysisId, 'REVIEWED', $managerId, [
            'decision' => $decision,
            'feedback' => $feedback,
        ]);

        $this->logger->info("Analysis reviewed: $analysisId decision: $decision by: $managerId");

        return $analysis;
    }

    /**
     * Get analyses for employee to work on.
     */
    public function getAnalysesForEmployee(string $employeeId, int $limit = 50, int $offset = 0): array
    {
        return $this->analysisRepository->listForEmployee($employeeId, $limit, $offset);
    }

    /**
     * Get analyses for manager to review.
     */
    public function getAnalysesForReview(int $limit = 50, int $offset = 0): array
    {
        return $this->analysisRepository->listForReview($limit, $offset);
    }

    /**
     * Record status change in audit table.
     */
    private function recordStatusChange(
        string $analysisId,
        AnalysisStatus $status,
        string $userId,
        string $action,
    ): void {
        $stmt = $this->db->execute(
            'INSERT INTO analysis_status_history (analysis_id, status, changed_by, action, changed_at)
             VALUES (?, ?, ?, ?, datetime("now"))',
            [$analysisId, $status->value, $userId, $action]
        );
    }

    /**
     * Record analysis revision for version history.
     */
    private function recordRevision(
        string $analysisId,
        int $revisionNumber,
        array $symptoms,
        array $signals,
        array $hypotheses,
        int $readinessScore,
        string $createdBy,
    ): void {
        $revisionId = $this->generateUuid();

        $this->db->execute(
            'INSERT INTO analysis_revisions
             (id, analysis_id, revision_number, symptoms, signals, hypotheses, readiness_score, created_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $revisionId,
                $analysisId,
                $revisionNumber,
                json_encode($symptoms),
                json_encode($signals),
                json_encode($hypotheses),
                $readinessScore,
                $createdBy,
            ]
        );
    }

    /**
     * Generate UUID v4.
     */
    private function generateUuid(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }
}
