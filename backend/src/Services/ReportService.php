<?php

declare(strict_types=1);

namespace InfraMind\Services;

use InfraMind\Repositories\AnalysisRepository;
use InfraMind\Repositories\AuditLogRepository;
use InfraMind\Core\Database;
use InfraMind\Core\Logger;
use InfraMind\Exceptions\NotFoundException;
use InfraMind\Exceptions\AuthorizationException;
use InfraMind\Models\AnalysisStatus;

/**
 * Report service for generating and managing reports from approved analyses.
 * Owners can only read finalized reports.
 */
class ReportService
{
    private AnalysisRepository $analysisRepository;
    private AuditLogRepository $auditRepository;
    private Logger $logger;
    private Database $db;

    public function __construct()
    {
        $this->analysisRepository = new AnalysisRepository();
        $this->auditRepository = new AuditLogRepository();
        $this->logger = Logger::getInstance();
        $this->db = Database::getInstance();
    }

    /**
     * Create report from approved analysis (manager only).
     * Report can only be created from APPROVED analyses.
     */
    public function createReport(string $analysisId, string $summary, string $managerId): array
    {
        $analysis = $this->analysisRepository->findById($analysisId);
        if (!$analysis) {
            throw new NotFoundException('Analysis not found');
        }

        // Only approved analyses can have reports
        if ($analysis->status->value !== AnalysisStatus::APPROVED->value) {
            throw new \InvalidArgumentException('Can only create reports from APPROVED analyses');
        }

        $reportId = $this->generateUuid();
        $now = date('Y-m-d H:i:s');

        // Create report
        $stmt = $this->db->execute(
            'INSERT INTO reports (id, analysis_id, summary, created_by, created_at)
             VALUES (?, ?, ?, ?, ?)',
            [$reportId, $analysisId, $summary, $managerId, $now]
        );

        $this->auditRepository->log('Report', $reportId, 'CREATED', $managerId, [
            'analysisId' => $analysisId,
        ]);

        $this->logger->info("Report created: $reportId for analysis: $analysisId by: $managerId");

        return [
            'id' => $reportId,
            'analysisId' => $analysisId,
            'summary' => $summary,
            'createdBy' => $managerId,
            'createdAt' => $now,
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

        // Owners can read any report
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
             ORDER BY r.created_at DESC LIMIT ? OFFSET ?',
            [$limit, $offset]
        );

        return $stmt->fetchAll();
    }

    /**
     * Get report with full analysis details.
     */
    public function getReportWithAnalysis(string $reportId, string $userId, string $userRole): array
    {
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
}
