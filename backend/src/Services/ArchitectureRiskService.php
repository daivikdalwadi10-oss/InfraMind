<?php

declare(strict_types=1);

namespace InfraMind\Services;

use InfraMind\Repositories\ArchitectureRiskRepository;
use InfraMind\Repositories\AuditLogRepository;
use InfraMind\Models\ArchitectureRisk;
use InfraMind\Models\RiskSeverity;
use InfraMind\Models\RiskStatus;
use InfraMind\Utils\Utils;
use InfraMind\Exceptions\NotFoundException;
use InfraMind\Core\Logger;

/**
 * Architecture risk service.
 */
class ArchitectureRiskService
{
    private ArchitectureRiskRepository $riskRepository;
    private AuditLogRepository $auditRepository;
    private Logger $logger;

    public function __construct()
    {
        $this->riskRepository = new ArchitectureRiskRepository();
        $this->auditRepository = new AuditLogRepository();
        $this->logger = Logger::getInstance();
    }

    /**
     * Create architecture risk.
     */
    public function createRisk(
        string $ownerId,
        string $title,
        ?string $description,
        string $severity,
        string $status,
        ?string $analysisId,
    ): ArchitectureRisk {
        $riskId = Utils::generateUuid();
        $now = Utils::now();

        $risk = new ArchitectureRisk(
            $riskId,
            $title,
            $description,
            RiskSeverity::from($severity),
            RiskStatus::from($status),
            $ownerId,
            $analysisId,
            $now,
            $now,
            null,
        );

        $risk = $this->riskRepository->create($risk);

        $this->auditRepository->log('ArchitectureRisk', $riskId, 'CREATED', $ownerId, [
            'severity' => $severity,
            'status' => $status,
            'analysisId' => $analysisId,
        ]);

        $this->logger->info("Architecture risk created: $riskId by: $ownerId");

        return $risk;
    }

    /**
     * Get architecture risk by ID.
     */
    public function getRisk(string $riskId): ArchitectureRisk
    {
        $risk = $this->riskRepository->findById($riskId);
        if (!$risk) {
            throw new NotFoundException('Architecture risk not found');
        }
        return $risk;
    }

    /**
     * List architecture risks.
     */
    public function listAll(int $limit = 50, int $offset = 0, ?string $status = null, ?string $severity = null): array
    {
        return $this->riskRepository->listAll($limit, $offset, $status, $severity);
    }

    /**
     * Update architecture risk.
     */
    public function updateRisk(string $riskId, array $data, string $userId): ArchitectureRisk
    {
        $risk = $this->getRisk($riskId);
        $before = $risk->toArray();

        if (isset($data['title'])) {
            $risk->title = $data['title'];
        }

        if (array_key_exists('description', $data)) {
            $risk->description = $data['description'];
        }

        if (isset($data['severity'])) {
            $risk->severity = RiskSeverity::from($data['severity']);
        }

        if (isset($data['status'])) {
            $risk->status = RiskStatus::from($data['status']);
            if ($risk->status === RiskStatus::RESOLVED) {
                $risk->resolvedAt = Utils::now();
            }
            if ($risk->status !== RiskStatus::RESOLVED) {
                $risk->resolvedAt = null;
            }
        }

        if (array_key_exists('analysisId', $data)) {
            $risk->analysisId = $data['analysisId'];
        }

        $risk->updatedAt = Utils::now();

        $risk = $this->riskRepository->update($risk);

        $this->auditRepository->log('ArchitectureRisk', $riskId, 'UPDATED', $userId, [
            'before' => $before,
            'after' => $risk->toArray(),
        ]);

        return $risk;
    }
}
