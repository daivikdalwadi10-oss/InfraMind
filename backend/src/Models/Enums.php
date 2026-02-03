<?php

declare(strict_types=1);

namespace InfraMind\Models;

/**
 * User role enumeration.
 */
enum UserRole: string
{
    case EMPLOYEE = 'EMPLOYEE';
    case MANAGER = 'MANAGER';
    case OWNER = 'OWNER';

    /**
     * Check if role has at least the required role (hierarchy).
     */
    public function hasAtLeast(UserRole $required): bool
    {
        // OWNER > MANAGER > EMPLOYEE
        $hierarchy = [
            UserRole::EMPLOYEE->value => 1,
            UserRole::MANAGER->value => 2,
            UserRole::OWNER->value => 3,
        ];

        return ($hierarchy[$this->value] ?? 0) >= ($hierarchy[$required->value] ?? 0);
    }
}

/**
 * Analysis status enumeration with state transition rules.
 */
enum AnalysisStatus: string
{
    case DRAFT = 'DRAFT';
    case SUBMITTED = 'SUBMITTED';
    case NEEDS_CHANGES = 'NEEDS_CHANGES';
    case APPROVED = 'APPROVED';

    /**
     * Check if transition from one status to another is valid.
     */
    public static function isValidTransition(AnalysisStatus $from, AnalysisStatus $to): bool
    {
        $validTransitions = [
            AnalysisStatus::DRAFT->value => ['SUBMITTED', 'DRAFT'],
            AnalysisStatus::SUBMITTED->value => ['NEEDS_CHANGES', 'APPROVED'],
            AnalysisStatus::NEEDS_CHANGES->value => ['SUBMITTED', 'DRAFT'],
            AnalysisStatus::APPROVED->value => [], // Terminal state
        ];

        return in_array($to->value, $validTransitions[$from->value] ?? [], true);
    }
}

/**
 * Analysis type enumeration.
 */
enum AnalysisType: string
{
    case LATENCY = 'LATENCY';
    case SECURITY = 'SECURITY';
    case OUTAGE = 'OUTAGE';
    case CAPACITY = 'CAPACITY';
}

/**
 * Task status enumeration.
 */
enum TaskStatus: string
{
    case OPEN = 'OPEN';
    case IN_PROGRESS = 'IN_PROGRESS';
    case COMPLETED = 'COMPLETED';
}
