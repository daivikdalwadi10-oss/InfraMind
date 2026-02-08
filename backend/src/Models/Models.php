<?php

declare(strict_types=1);

namespace InfraMind\Models;

/**
 * User model.
 */
class User
{
    public string $id;
    public string $email;
    public string $passwordHash;
    public UserRole $role;
    public string $displayName;
    public ?string $position = null;
    public string $createdAt;
    public ?string $lastLoginAt = null;
    public bool $isActive = true;
    public ?string $deletedAt = null;

    public function __construct(
        string $id,
        string $email,
        string $passwordHash,
        UserRole $role,
        string $displayName,
        string $createdAt,
        ?string $position = null,
    ) {
        $this->id = $id;
        $this->email = $email;
        $this->passwordHash = $passwordHash;
        $this->role = $role;
        $this->displayName = $displayName;
        $this->createdAt = $createdAt;
        $this->position = $position;
    }

    /**
     * Convert to array for JSON serialization.
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'role' => $this->role->value,
            'displayName' => $this->displayName,
            'position' => $this->position,
            'createdAt' => $this->createdAt,
            'lastLoginAt' => $this->lastLoginAt,
            'isActive' => $this->isActive,
        ];
    }
}

/**
 * Task model.
 */
class Task
{
    public string $id;
    public string $title;
    public string $description;
    public ?string $assignedTo;
    public string $createdBy;
    public TaskStatus $status;
    public string $createdAt;
    public string $updatedAt;

    public function __construct(
        string $id,
        string $title,
        string $description,
        string $createdBy,
        TaskStatus $status,
        string $createdAt,
        string $updatedAt,
        ?string $assignedTo = null,
    ) {
        $this->id = $id;
        $this->title = $title;
        $this->description = $description;
        $this->createdBy = $createdBy;
        $this->status = $status;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
        $this->assignedTo = $assignedTo;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'assignedTo' => $this->assignedTo,
            'createdBy' => $this->createdBy,
            'status' => $this->status->value,
            'createdAt' => $this->createdAt,
            'updatedAt' => $this->updatedAt,
        ];
    }
}

/**
 * Analysis model.
 */
class Analysis
{
    public string $id;
    public string $taskId;
    public string $title;
    public string $employeeId;
    public string $createdBy;
    public ?string $teamId = null;
    public AnalysisStatus $status;
    public AnalysisType $analysisType;
    public array $symptoms = [];
    public array $signals = [];
    public array $hypotheses = [];
    public array $environmentContext = [];
    public array $timelineEvents = [];
    public array $dependencyImpact = [];
    public array $riskClassification = [];
    public int $readinessScore = 0;
    public ?string $managerFeedback = null;
    public int $revisionCount = 0;
    public string $createdAt;
    public string $updatedAt;

    public function __construct(
        string $id,
        string $taskId,
        string $title,
        string $employeeId,
        string $createdBy,
        AnalysisStatus $status,
        AnalysisType $analysisType,
        string $createdAt,
        string $updatedAt,
        ?string $teamId = null,
    ) {
        $this->id = $id;
        $this->taskId = $taskId;
        $this->title = $title;
        $this->employeeId = $employeeId;
        $this->createdBy = $createdBy;
        $this->status = $status;
        $this->analysisType = $analysisType;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
        $this->teamId = $teamId;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'taskId' => $this->taskId,
            'title' => $this->title,
            'employeeId' => $this->employeeId,
            'createdBy' => $this->createdBy,
            'teamId' => $this->teamId,
            'status' => $this->status->value,
            'analysisType' => $this->analysisType->value,
            'symptoms' => $this->symptoms,
            'signals' => $this->signals,
            'hypotheses' => $this->hypotheses,
            'environmentContext' => $this->environmentContext,
            'timelineEvents' => $this->timelineEvents,
            'dependencyImpact' => $this->dependencyImpact,
            'riskClassification' => $this->riskClassification,
            'readinessScore' => $this->readinessScore,
            'managerFeedback' => $this->managerFeedback,
            'revisionCount' => $this->revisionCount,
            'createdAt' => $this->createdAt,
            'updatedAt' => $this->updatedAt,
        ];
    }
}

/**
 * Report model.
 */
class Report
{
    public string $id;
    public string $analysisId;
    public string $summary;
    public ?string $executiveSummary = null;
    public ?string $rootCause = null;
    public ?string $impact = null;
    public ?string $resolution = null;
    public ?string $preventionSteps = null;
    public bool $aiAssisted = false;
    public ReportStatus $status;
    public string $createdBy;
    public string $createdAt;
    public string $updatedAt;

    public function __construct(
        string $id,
        string $analysisId,
        string $summary,
        ReportStatus $status,
        string $createdBy,
        string $createdAt,
        string $updatedAt,
        ?string $executiveSummary = null,
        ?string $rootCause = null,
        ?string $impact = null,
        ?string $resolution = null,
        ?string $preventionSteps = null,
        bool $aiAssisted = false,
    ) {
        $this->id = $id;
        $this->analysisId = $analysisId;
        $this->summary = $summary;
        $this->status = $status;
        $this->createdBy = $createdBy;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
        $this->executiveSummary = $executiveSummary;
        $this->rootCause = $rootCause;
        $this->impact = $impact;
        $this->resolution = $resolution;
        $this->preventionSteps = $preventionSteps;
        $this->aiAssisted = $aiAssisted;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'analysisId' => $this->analysisId,
            'summary' => $this->summary,
            'executiveSummary' => $this->executiveSummary,
            'rootCause' => $this->rootCause,
            'impact' => $this->impact,
            'resolution' => $this->resolution,
            'preventionSteps' => $this->preventionSteps,
            'aiAssisted' => $this->aiAssisted,
            'status' => $this->status->value,
            'createdBy' => $this->createdBy,
            'createdAt' => $this->createdAt,
            'updatedAt' => $this->updatedAt,
        ];
    }
}

/**
 * Team model.
 */
class Team
{
    public string $id;
    public string $name;
    public ?string $description;
    public string $managerId;
    public string $createdAt;
    public string $updatedAt;

    public function __construct(
        string $id,
        string $name,
        ?string $description,
        string $managerId,
        string $createdAt,
        string $updatedAt,
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->description = $description;
        $this->managerId = $managerId;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'managerId' => $this->managerId,
            'createdAt' => $this->createdAt,
            'updatedAt' => $this->updatedAt,
        ];
    }
}

/**
 * Incident model.
 */
class Incident
{
    public string $id;
    public string $title;
    public ?string $description;
    public IncidentSeverity $severity;
    public IncidentStatus $status;
    public string $reportedBy;
    public ?string $assignedTo;
    public ?string $occurredAt;
    public string $createdAt;
    public string $updatedAt;
    public ?string $resolvedAt;

    public function __construct(
        string $id,
        string $title,
        ?string $description,
        IncidentSeverity $severity,
        IncidentStatus $status,
        string $reportedBy,
        ?string $assignedTo,
        ?string $occurredAt,
        string $createdAt,
        string $updatedAt,
        ?string $resolvedAt,
    ) {
        $this->id = $id;
        $this->title = $title;
        $this->description = $description;
        $this->severity = $severity;
        $this->status = $status;
        $this->reportedBy = $reportedBy;
        $this->assignedTo = $assignedTo;
        $this->occurredAt = $occurredAt;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
        $this->resolvedAt = $resolvedAt;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'severity' => $this->severity->value,
            'status' => $this->status->value,
            'reportedBy' => $this->reportedBy,
            'assignedTo' => $this->assignedTo,
            'occurredAt' => $this->occurredAt,
            'createdAt' => $this->createdAt,
            'updatedAt' => $this->updatedAt,
            'resolvedAt' => $this->resolvedAt,
        ];
    }
}

/**
 * Infrastructure state model.
 */
class InfrastructureState
{
    public string $id;
    public string $component;
    public InfrastructureStatus $status;
    public ?string $summary;
    public string $observedAt;
    public string $reportedBy;
    public string $createdAt;
    public string $updatedAt;

    public function __construct(
        string $id,
        string $component,
        InfrastructureStatus $status,
        ?string $summary,
        string $observedAt,
        string $reportedBy,
        string $createdAt,
        string $updatedAt,
    ) {
        $this->id = $id;
        $this->component = $component;
        $this->status = $status;
        $this->summary = $summary;
        $this->observedAt = $observedAt;
        $this->reportedBy = $reportedBy;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'component' => $this->component,
            'status' => $this->status->value,
            'summary' => $this->summary,
            'observedAt' => $this->observedAt,
            'reportedBy' => $this->reportedBy,
            'createdAt' => $this->createdAt,
            'updatedAt' => $this->updatedAt,
        ];
    }
}

/**
 * Architecture risk model.
 */
class ArchitectureRisk
{
    public string $id;
    public string $title;
    public ?string $description;
    public RiskSeverity $severity;
    public RiskStatus $status;
    public string $ownerId;
    public ?string $analysisId;
    public string $createdAt;
    public string $updatedAt;
    public ?string $resolvedAt;

    public function __construct(
        string $id,
        string $title,
        ?string $description,
        RiskSeverity $severity,
        RiskStatus $status,
        string $ownerId,
        ?string $analysisId,
        string $createdAt,
        string $updatedAt,
        ?string $resolvedAt,
    ) {
        $this->id = $id;
        $this->title = $title;
        $this->description = $description;
        $this->severity = $severity;
        $this->status = $status;
        $this->ownerId = $ownerId;
        $this->analysisId = $analysisId;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
        $this->resolvedAt = $resolvedAt;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'severity' => $this->severity->value,
            'status' => $this->status->value,
            'ownerId' => $this->ownerId,
            'analysisId' => $this->analysisId,
            'createdAt' => $this->createdAt,
            'updatedAt' => $this->updatedAt,
            'resolvedAt' => $this->resolvedAt,
        ];
    }
}

/**
 * Meeting model.
 */
class Meeting
{
    public string $id;
    public string $title;
    public ?string $agenda;
    public MeetingStatus $status;
    public string $scheduledAt;
    public int $durationMinutes;
    public string $organizerId;
    public ?string $analysisId;
    public ?string $incidentId;
    public string $createdAt;
    public string $updatedAt;

    public function __construct(
        string $id,
        string $title,
        ?string $agenda,
        MeetingStatus $status,
        string $scheduledAt,
        int $durationMinutes,
        string $organizerId,
        ?string $analysisId,
        ?string $incidentId,
        string $createdAt,
        string $updatedAt,
    ) {
        $this->id = $id;
        $this->title = $title;
        $this->agenda = $agenda;
        $this->status = $status;
        $this->scheduledAt = $scheduledAt;
        $this->durationMinutes = $durationMinutes;
        $this->organizerId = $organizerId;
        $this->analysisId = $analysisId;
        $this->incidentId = $incidentId;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'agenda' => $this->agenda,
            'status' => $this->status->value,
            'scheduledAt' => $this->scheduledAt,
            'durationMinutes' => $this->durationMinutes,
            'organizerId' => $this->organizerId,
            'analysisId' => $this->analysisId,
            'incidentId' => $this->incidentId,
            'createdAt' => $this->createdAt,
            'updatedAt' => $this->updatedAt,
        ];
    }
}
