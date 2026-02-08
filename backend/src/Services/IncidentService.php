<?php

declare(strict_types=1);

namespace InfraMind\Services;

use InfraMind\Repositories\IncidentRepository;
use InfraMind\Repositories\AuditLogRepository;
use InfraMind\Models\Incident;
use InfraMind\Models\IncidentSeverity;
use InfraMind\Models\IncidentStatus;
use InfraMind\Utils\Utils;
use InfraMind\Exceptions\NotFoundException;
use InfraMind\Core\Logger;

/**
 * Incident service for workflow and audit logging.
 */
class IncidentService
{
    private IncidentRepository $incidentRepository;
    private AuditLogRepository $auditRepository;
    private Logger $logger;

    public function __construct()
    {
        $this->incidentRepository = new IncidentRepository();
        $this->auditRepository = new AuditLogRepository();
        $this->logger = Logger::getInstance();
    }

    /**
     * Create incident.
     */
    public function createIncident(
        string $reportedBy,
        string $title,
        ?string $description,
        string $severity,
        ?string $assignedTo,
        ?string $occurredAt,
    ): Incident {
        $incidentId = Utils::generateUuid();
        $now = Utils::now();

        $incident = new Incident(
            $incidentId,
            $title,
            $description,
            IncidentSeverity::from($severity),
            IncidentStatus::OPEN,
            $reportedBy,
            $assignedTo,
            $occurredAt,
            $now,
            $now,
            null,
        );

        $incident = $this->incidentRepository->create($incident);

        $this->auditRepository->log('Incident', $incidentId, 'CREATED', $reportedBy, [
            'severity' => $severity,
            'assignedTo' => $assignedTo,
        ]);

        $this->logger->info("Incident created: $incidentId by: $reportedBy");

        return $incident;
    }

    /**
     * Get incident by ID.
     */
    public function getIncident(string $incidentId): Incident
    {
        $incident = $this->incidentRepository->findById($incidentId);
        if (!$incident) {
            throw new NotFoundException('Incident not found');
        }
        return $incident;
    }

    /**
     * List incidents for employee.
     */
    public function listForEmployee(string $userId, int $limit = 50, int $offset = 0): array
    {
        return $this->incidentRepository->listForEmployee($userId, $limit, $offset);
    }

    /**
     * List incidents for manager/owner.
     */
    public function listAll(int $limit = 50, int $offset = 0, ?string $status = null, ?string $severity = null): array
    {
        return $this->incidentRepository->listAll($limit, $offset, $status, $severity);
    }

    /**
     * Update incident.
     */
    public function updateIncident(string $incidentId, array $data, string $userId): Incident
    {
        $incident = $this->getIncident($incidentId);
        $before = $incident->toArray();

        if (isset($data['title'])) {
            $incident->title = $data['title'];
        }

        if (array_key_exists('description', $data)) {
            $incident->description = $data['description'];
        }

        if (isset($data['severity'])) {
            $incident->severity = IncidentSeverity::from($data['severity']);
        }

        if (isset($data['status'])) {
            $incident->status = IncidentStatus::from($data['status']);
            if ($incident->status === IncidentStatus::RESOLVED && !$incident->resolvedAt) {
                $incident->resolvedAt = Utils::now();
            }
            if ($incident->status !== IncidentStatus::RESOLVED) {
                $incident->resolvedAt = null;
            }
        }

        if (array_key_exists('assignedTo', $data)) {
            $incident->assignedTo = $data['assignedTo'];
        }

        if (array_key_exists('occurredAt', $data)) {
            $incident->occurredAt = $data['occurredAt'];
        }

        if (array_key_exists('resolvedAt', $data)) {
            $incident->resolvedAt = $data['resolvedAt'];
        }

        $incident->updatedAt = Utils::now();

        $incident = $this->incidentRepository->update($incident);

        $this->auditRepository->log('Incident', $incidentId, 'UPDATED', $userId, [
            'before' => $before,
            'after' => $incident->toArray(),
        ]);

        return $incident;
    }
}
