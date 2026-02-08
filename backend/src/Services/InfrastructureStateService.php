<?php

declare(strict_types=1);

namespace InfraMind\Services;

use InfraMind\Repositories\InfrastructureStateRepository;
use InfraMind\Repositories\AuditLogRepository;
use InfraMind\Models\InfrastructureState;
use InfraMind\Models\InfrastructureStatus;
use InfraMind\Utils\Utils;
use InfraMind\Exceptions\NotFoundException;
use InfraMind\Core\Logger;

/**
 * Infrastructure state service.
 */
class InfrastructureStateService
{
    private InfrastructureStateRepository $stateRepository;
    private AuditLogRepository $auditRepository;
    private Logger $logger;

    public function __construct()
    {
        $this->stateRepository = new InfrastructureStateRepository();
        $this->auditRepository = new AuditLogRepository();
        $this->logger = Logger::getInstance();
    }

    /**
     * Create infrastructure state.
     */
    public function createState(
        string $reportedBy,
        string $component,
        string $status,
        ?string $summary,
        ?string $observedAt,
    ): InfrastructureState {
        $stateId = Utils::generateUuid();
        $now = Utils::now();

        $state = new InfrastructureState(
            $stateId,
            $component,
            InfrastructureStatus::from($status),
            $summary,
            $observedAt ?? $now,
            $reportedBy,
            $now,
            $now,
        );

        $state = $this->stateRepository->create($state);

        $this->auditRepository->log('InfrastructureState', $stateId, 'CREATED', $reportedBy, [
            'component' => $component,
            'status' => $status,
        ]);

        $this->logger->info("Infrastructure state created: $stateId by: $reportedBy");

        return $state;
    }

    /**
     * Get infrastructure state by ID.
     */
    public function getState(string $stateId): InfrastructureState
    {
        $state = $this->stateRepository->findById($stateId);
        if (!$state) {
            throw new NotFoundException('Infrastructure state not found');
        }
        return $state;
    }

    /**
     * List infrastructure states.
     */
    public function listAll(int $limit = 50, int $offset = 0, ?string $status = null): array
    {
        return $this->stateRepository->listAll($limit, $offset, $status);
    }

    /**
     * Update infrastructure state.
     */
    public function updateState(string $stateId, array $data, string $userId): InfrastructureState
    {
        $state = $this->getState($stateId);
        $before = $state->toArray();

        if (isset($data['component'])) {
            $state->component = $data['component'];
        }

        if (isset($data['status'])) {
            $state->status = InfrastructureStatus::from($data['status']);
        }

        if (array_key_exists('summary', $data)) {
            $state->summary = $data['summary'];
        }

        if (array_key_exists('observedAt', $data)) {
            $state->observedAt = $data['observedAt'] ?? $state->observedAt;
        }

        $state->updatedAt = Utils::now();

        $state = $this->stateRepository->update($state);

        $this->auditRepository->log('InfrastructureState', $stateId, 'UPDATED', $userId, [
            'before' => $before,
            'after' => $state->toArray(),
        ]);

        return $state;
    }
}
