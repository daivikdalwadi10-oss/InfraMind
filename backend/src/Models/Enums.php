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
    case DEVELOPER = 'DEVELOPER';
    case SYSTEM_ADMIN = 'SYSTEM_ADMIN';

    /**
     * Check if role has at least the required role (hierarchy).
     */
    public function hasAtLeast(UserRole $required): bool
    {
        // SYSTEM_ADMIN > DEVELOPER > OWNER > MANAGER > EMPLOYEE
        $hierarchy = [
            UserRole::EMPLOYEE->value => 1,
            UserRole::MANAGER->value => 2,
            UserRole::OWNER->value => 3,
            UserRole::DEVELOPER->value => 4,
            UserRole::SYSTEM_ADMIN->value => 5,
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
    case REPORT_GENERATED = 'REPORT_GENERATED';

    /**
     * Check if transition from one status to another is valid.
     */
    public static function isValidTransition(AnalysisStatus $from, AnalysisStatus $to): bool
    {
        $validTransitions = [
            AnalysisStatus::DRAFT->value => ['SUBMITTED', 'DRAFT'],
            AnalysisStatus::SUBMITTED->value => ['NEEDS_CHANGES', 'APPROVED'],
            AnalysisStatus::NEEDS_CHANGES->value => ['SUBMITTED', 'DRAFT'],
            AnalysisStatus::APPROVED->value => ['REPORT_GENERATED'],
            AnalysisStatus::REPORT_GENERATED->value => [],
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

/**
 * Report status enumeration.
 */
enum ReportStatus: string
{
    case DRAFT = 'DRAFT';
    case FINALIZED = 'FINALIZED';
}

/**
 * Incident severity enumeration.
 */
enum IncidentSeverity: string
{
    case LOW = 'LOW';
    case MEDIUM = 'MEDIUM';
    case HIGH = 'HIGH';
    case CRITICAL = 'CRITICAL';
}

/**
 * Incident status enumeration.
 */
enum IncidentStatus: string
{
    case OPEN = 'OPEN';
    case INVESTIGATING = 'INVESTIGATING';
    case RESOLVED = 'RESOLVED';
}

/**
 * Infrastructure state enumeration.
 */
enum InfrastructureStatus: string
{
    case HEALTHY = 'HEALTHY';
    case DEGRADED = 'DEGRADED';
    case OUTAGE = 'OUTAGE';
    case MAINTENANCE = 'MAINTENANCE';
}

/**
 * Architecture risk severity enumeration.
 */
enum RiskSeverity: string
{
    case LOW = 'LOW';
    case MEDIUM = 'MEDIUM';
    case HIGH = 'HIGH';
    case CRITICAL = 'CRITICAL';
}

/**
 * Architecture risk status enumeration.
 */
enum RiskStatus: string
{
    case OPEN = 'OPEN';
    case MITIGATING = 'MITIGATING';
    case RESOLVED = 'RESOLVED';
}

/**
 * Meeting status enumeration.
 */
enum MeetingStatus: string
{
    case SCHEDULED = 'SCHEDULED';
    case COMPLETED = 'COMPLETED';
    case CANCELLED = 'CANCELLED';
}

/**
 * AI output types.
 */
enum AiOutputType: string
{
    case HYPOTHESES = 'HYPOTHESES';
    case REPORT_DRAFT = 'REPORT_DRAFT';
}

/**
 * AI output status.
 */
enum AiOutputStatus: string
{
    case GENERATED = 'GENERATED';
    case ACCEPTED = 'ACCEPTED';
    case REJECTED = 'REJECTED';
    case EDITED = 'EDITED';
}
