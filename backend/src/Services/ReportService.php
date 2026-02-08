<?php

declare(strict_types=1);

namespace InfraMind\Services;

use InfraMind\Repositories\AnalysisRepository;
use InfraMind\Repositories\AuditLogRepository;
use InfraMind\Repositories\TeamRepository;
use InfraMind\Core\Database;
use InfraMind\Core\Logger;
use InfraMind\Exceptions\NotFoundException;
use InfraMind\Exceptions\AuthorizationException;
use InfraMind\Models\AnalysisStatus;
use InfraMind\Models\ReportStatus;
use InfraMind\Utils\Utils;

/**
 * Report service for generating and managing reports from approved analyses.
 * Owners can only read finalized reports.
 */
class ReportService
{
    private AnalysisRepository $analysisRepository;
    private AuditLogRepository $auditRepository;
    private TeamRepository $teamRepository;
    private Logger $logger;
    private Database $db;

    public function __construct()
    {
        $this->analysisRepository = new AnalysisRepository();
        $this->auditRepository = new AuditLogRepository();
        $this->teamRepository = new TeamRepository();
        $this->logger = Logger::getInstance();
        $this->db = Database::getInstance();
    }

    /**
     * Create report from approved analysis (manager only).
     * Report can only be created from APPROVED analyses.
     */
    public function createReport(
        string $analysisId,
        string $executiveSummary,
        string $rootCause,
        string $impact,
        string $resolution,
        string $preventionSteps,
        bool $aiAssisted,
        string $managerId,
    ): array
    {
        $analysis = $this->analysisRepository->findById($analysisId);
        if (!$analysis) {
            throw new NotFoundException('Analysis not found');
        }

        if (!$this->teamRepository->isManagerOfEmployee($managerId, $analysis->employeeId)) {
            throw new AuthorizationException('You can only create reports for your teams');
        }

        // Only approved analyses can have reports
        if ($analysis->status->value !== AnalysisStatus::APPROVED->value) {
            throw new \InvalidArgumentException('Can only create reports from APPROVED analyses');
        }

        $reportId = $this->generateUuid();
        $now = Utils::now();
        $summary = $executiveSummary;

        // Create report
        $this->db->execute(
            'INSERT INTO reports (
                id, analysis_id, summary, executive_summary, root_cause, impact, resolution, prevention_steps,
                ai_assisted, status, created_by, created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $reportId,
                $analysisId,
                $summary,
                $executiveSummary,
                $rootCause,
                $impact,
                $resolution,
                $preventionSteps,
                $aiAssisted ? 1 : 0,
                ReportStatus::FINALIZED->value,
                $managerId,
                $now,
                $now,
            ]
        );

        $analysis->status = AnalysisStatus::REPORT_GENERATED;
        $this->analysisRepository->update($analysis);

        $this->recordStatusChange(
            $analysisId,
            AnalysisStatus::REPORT_GENERATED,
            $managerId,
            'REPORT_GENERATED',
        );

        $this->auditRepository->log('Report', $reportId, 'CREATED', $managerId, [
            'analysisId' => $analysisId,
            'status' => ReportStatus::FINALIZED->value,
        ]);

        $this->logger->info("Report created: $reportId for analysis: $analysisId by: $managerId");

        return [
            'id' => $reportId,
            'analysisId' => $analysisId,
            'summary' => $summary,
            'executiveSummary' => $executiveSummary,
            'rootCause' => $rootCause,
            'impact' => $impact,
            'resolution' => $resolution,
            'preventionSteps' => $preventionSteps,
            'aiAssisted' => $aiAssisted,
            'createdBy' => $managerId,
            'createdAt' => $now,
            'updatedAt' => $now,
            'status' => ReportStatus::FINALIZED->value,
        ];
    }

    /**
     * Get report by ID.
     * Owners can only read reports. Managers can read their own.
     */
    public function getReport(string $reportId, string $userId, string $userRole): array
    {
        $stmt = $this->db->execute(
            'SELECT * FROM reports WHERE id = ?',
            [$reportId]
        );

        $report = $stmt->fetch();
        if (!$report) {
            throw new NotFoundException('Report not found');
        }

        // Owners can only read finalized reports
        if ($userRole === 'OWNER' && $report['status'] !== ReportStatus::FINALIZED->value) {
            throw new AuthorizationException('Owners can only access finalized reports');
        }

        // Managers can only read their own
        if ($userRole === 'MANAGER' && $report['created_by'] !== $userId) {
            throw new AuthorizationException('You can only access your own reports');
        }

        return $report;
    }

    /**
     * List reports for manager (their own reports).
     */
    public function listReportsForManager(string $managerId, int $limit = 50, int $offset = 0): array
    {
        $stmt = $this->db->execute(
            'SELECT * FROM reports WHERE created_by = ?
             ORDER BY created_at DESC LIMIT ? OFFSET ?',
            [$managerId, $limit, $offset]
        );

        return $stmt->fetchAll();
    }

    /**
     * List all reports (for owners).
     */
    public function listAllReports(int $limit = 50, int $offset = 0): array
    {
        $stmt = $this->db->execute(
            'SELECT r.*, a.analysis_type, a.employee_id
             FROM reports r
             JOIN analyses a ON r.analysis_id = a.id
             WHERE r.status = ?
             ORDER BY r.created_at DESC LIMIT ? OFFSET ?',
            [ReportStatus::FINALIZED->value, $limit, $offset]
        );

        return $stmt->fetchAll();
    }

    /**
     * Get report with full analysis details.
     */
    public function getReportWithAnalysis(string $reportId, string $userId, string $userRole): array
    {
        if ($userRole === 'OWNER') {
            throw new AuthorizationException('Owners cannot access raw analysis data');
        }

        $report = $this->getReport($reportId, $userId, $userRole);

        $analysis = $this->analysisRepository->findById($report['analysis_id']);
        if (!$analysis) {
            throw new NotFoundException('Associated analysis not found');
        }

        return [
            'report' => $report,
            'analysis' => $analysis->toArray(),
        ];
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

    /**
     * Record analysis status change from report creation.
     */
    private function recordStatusChange(
        string $analysisId,
        AnalysisStatus $status,
        string $userId,
        string $details,
    ): void {
        $this->db->execute(
            'INSERT INTO analysis_status_history (id, analysis_id, status, changed_by, details, changed_at)
             VALUES (?, ?, ?, ?, ?, ?)',
            [Utils::generateUuid(), $analysisId, $status->value, $userId, $details, Utils::now()]
        );
    }
}
