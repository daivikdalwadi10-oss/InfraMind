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
    ) {
        $this->id = $id;
        $this->email = $email;
        $this->passwordHash = $passwordHash;
        $this->role = $role;
        $this->displayName = $displayName;
        $this->createdAt = $createdAt;
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
    public string $employeeId;
    public AnalysisStatus $status;
    public AnalysisType $analysisType;
    public array $symptoms = [];
    public array $signals = [];
    public array $hypotheses = [];
    public int $readinessScore = 0;
    public ?string $managerFeedback = null;
    public int $revisionCount = 0;
    public string $createdAt;
    public string $updatedAt;

    public function __construct(
        string $id,
        string $taskId,
        string $employeeId,
        AnalysisStatus $status,
        AnalysisType $analysisType,
        string $createdAt,
        string $updatedAt,
    ) {
        $this->id = $id;
        $this->taskId = $taskId;
        $this->employeeId = $employeeId;
        $this->status = $status;
        $this->analysisType = $analysisType;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'taskId' => $this->taskId,
            'employeeId' => $this->employeeId,
            'status' => $this->status->value,
            'analysisType' => $this->analysisType->value,
            'symptoms' => $this->symptoms,
            'signals' => $this->signals,
            'hypotheses' => $this->hypotheses,
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
    public string $createdBy;
    public string $createdAt;

    public function __construct(
        string $id,
        string $analysisId,
        string $summary,
        string $createdBy,
        string $createdAt,
    ) {
        $this->id = $id;
        $this->analysisId = $analysisId;
        $this->summary = $summary;
        $this->createdBy = $createdBy;
        $this->createdAt = $createdAt;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'analysisId' => $this->analysisId,
            'summary' => $this->summary,
            'createdBy' => $this->createdBy,
            'createdAt' => $this->createdAt,
        ];
    }
}
