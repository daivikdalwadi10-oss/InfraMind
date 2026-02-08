-- InfraMind MySQL Database Schema
-- MySQL 8.0+ compatible

-- Drop existing tables if they exist (for clean migration)
DROP TABLE IF EXISTS meetings;
DROP TABLE IF EXISTS architecture_risks;
DROP TABLE IF EXISTS infrastructure_states;
DROP TABLE IF EXISTS incidents;
DROP TABLE IF EXISTS analysis_revisions;
DROP TABLE IF EXISTS analysis_status_history;
DROP TABLE IF EXISTS analysis_hypotheses;
DROP TABLE IF EXISTS ai_outputs;
DROP TABLE IF EXISTS audit_logs;
DROP TABLE IF EXISTS reports;
DROP TABLE IF EXISTS analysis_inputs;
DROP TABLE IF EXISTS analyses;
DROP TABLE IF EXISTS team_members;
DROP TABLE IF EXISTS teams;
DROP TABLE IF EXISTS tasks;
DROP TABLE IF EXISTS users;

-- Users table
CREATE TABLE users (
    id VARCHAR(255) PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('EMPLOYEE', 'MANAGER', 'OWNER', 'DEVELOPER', 'SYSTEM_ADMIN') NOT NULL DEFAULT 'EMPLOYEE',
    display_name VARCHAR(255) NOT NULL,
    position VARCHAR(255),
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login_at TIMESTAMP NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    deleted_at TIMESTAMP NULL,
    INDEX idx_users_email (email),
    INDEX idx_users_role (role),
    INDEX idx_users_created_at (created_at),
    INDEX idx_users_deleted_at (deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tasks table
CREATE TABLE tasks (
    id VARCHAR(255) PRIMARY KEY,
    title VARCHAR(500) NOT NULL,
    description TEXT,
    assigned_to VARCHAR(255),
    created_by VARCHAR(255) NOT NULL,
    status ENUM('OPEN', 'IN_PROGRESS', 'COMPLETED') NOT NULL DEFAULT 'OPEN',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_tasks_created_by (created_by),
    INDEX idx_tasks_assigned_to (assigned_to),
    INDEX idx_tasks_status (status),
    INDEX idx_tasks_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Teams table
CREATE TABLE teams (
    id VARCHAR(255) PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    manager_id VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (manager_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY idx_teams_name (name),
    INDEX idx_teams_manager_id (manager_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Team members table
CREATE TABLE team_members (
    id VARCHAR(255) PRIMARY KEY,
    team_id VARCHAR(255) NOT NULL,
    user_id VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_team_member (team_id, user_id),
    INDEX idx_team_members_team_id (team_id),
    INDEX idx_team_members_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Analyses table
CREATE TABLE analyses (
    id VARCHAR(255) PRIMARY KEY,
    task_id VARCHAR(255) NOT NULL UNIQUE,
    title VARCHAR(500) NOT NULL,
    employee_id VARCHAR(255) NOT NULL,
    created_by VARCHAR(255) NOT NULL,
    team_id VARCHAR(255),
    status ENUM('DRAFT', 'SUBMITTED', 'NEEDS_CHANGES', 'APPROVED', 'REPORT_GENERATED') NOT NULL DEFAULT 'DRAFT',
    analysis_type ENUM('LATENCY', 'SECURITY', 'OUTAGE', 'CAPACITY') NOT NULL,
    symptoms JSON,
    signals JSON,
    hypotheses JSON,
    readiness_score INT NOT NULL DEFAULT 0,
    revision_count INT NOT NULL DEFAULT 0,
    manager_feedback TEXT,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
    FOREIGN KEY (employee_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE SET NULL,
    INDEX idx_analyses_employee_id (employee_id),
    INDEX idx_analyses_status (status),
    INDEX idx_analyses_task_id (task_id),
    INDEX idx_analyses_created_at (created_at),
    INDEX idx_analyses_created_by (created_by),
    INDEX idx_analyses_team_id (team_id),
    CHECK (readiness_score >= 0 AND readiness_score <= 100)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Analysis inputs (systems engineering context)
CREATE TABLE analysis_inputs (
    id VARCHAR(255) PRIMARY KEY,
    analysis_id VARCHAR(255) NOT NULL UNIQUE,
    environment_context JSON,
    timeline_events JSON,
    dependency_impact JSON,
    risk_classification JSON,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (analysis_id) REFERENCES analyses(id) ON DELETE CASCADE,
    INDEX idx_analysis_inputs_analysis_id (analysis_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Analysis hypotheses (normalized)
CREATE TABLE analysis_hypotheses (
    id VARCHAR(255) PRIMARY KEY,
    analysis_id VARCHAR(255) NOT NULL,
    text TEXT NOT NULL,
    confidence INT NOT NULL,
    evidence JSON,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (analysis_id) REFERENCES analyses(id) ON DELETE CASCADE,
    INDEX idx_hypotheses_analysis_id (analysis_id),
    INDEX idx_hypotheses_confidence (confidence),
    CHECK (confidence >= 0 AND confidence <= 100)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Reports table
CREATE TABLE reports (
    id VARCHAR(255) PRIMARY KEY,
    analysis_id VARCHAR(255) NOT NULL,
    summary TEXT NOT NULL,
    executive_summary TEXT,
    root_cause TEXT,
    impact TEXT,
    resolution TEXT,
    prevention_steps TEXT,
    ai_assisted BOOLEAN NOT NULL DEFAULT FALSE,
    status ENUM('DRAFT', 'FINALIZED') NOT NULL DEFAULT 'FINALIZED',
    created_by VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (analysis_id) REFERENCES analyses(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_reports_analysis_id (analysis_id),
    INDEX idx_reports_created_by (created_by),
    INDEX idx_reports_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Incidents table
CREATE TABLE incidents (
    id VARCHAR(255) PRIMARY KEY,
    title VARCHAR(500) NOT NULL,
    description TEXT,
    severity ENUM('LOW', 'MEDIUM', 'HIGH', 'CRITICAL') NOT NULL DEFAULT 'MEDIUM',
    status ENUM('OPEN', 'INVESTIGATING', 'RESOLVED') NOT NULL DEFAULT 'OPEN',
    reported_by VARCHAR(255) NOT NULL,
    assigned_to VARCHAR(255),
    occurred_at TIMESTAMP NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    resolved_at TIMESTAMP NULL,
    FOREIGN KEY (reported_by) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_incidents_reported_by (reported_by),
    INDEX idx_incidents_assigned_to (assigned_to),
    INDEX idx_incidents_status (status),
    INDEX idx_incidents_severity (severity)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Infrastructure states table
CREATE TABLE infrastructure_states (
    id VARCHAR(255) PRIMARY KEY,
    component VARCHAR(255) NOT NULL,
    status ENUM('HEALTHY', 'DEGRADED', 'OUTAGE', 'MAINTENANCE') NOT NULL DEFAULT 'HEALTHY',
    summary TEXT,
    observed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    reported_by VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (reported_by) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_infra_component (component),
    INDEX idx_infra_status (status),
    INDEX idx_infra_reported_by (reported_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Architecture risks table
CREATE TABLE architecture_risks (
    id VARCHAR(255) PRIMARY KEY,
    title VARCHAR(500) NOT NULL,
    description TEXT,
    severity ENUM('LOW', 'MEDIUM', 'HIGH', 'CRITICAL') NOT NULL DEFAULT 'MEDIUM',
    status ENUM('OPEN', 'MITIGATING', 'RESOLVED') NOT NULL DEFAULT 'OPEN',
    owner_id VARCHAR(255) NOT NULL,
    analysis_id VARCHAR(255),
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    resolved_at TIMESTAMP NULL,
    FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (analysis_id) REFERENCES analyses(id) ON DELETE SET NULL,
    INDEX idx_risks_owner (owner_id),
    INDEX idx_risks_analysis (analysis_id),
    INDEX idx_risks_status (status),
    INDEX idx_risks_severity (severity)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Meetings table
CREATE TABLE meetings (
    id VARCHAR(255) PRIMARY KEY,
    title VARCHAR(500) NOT NULL,
    agenda TEXT,
    status ENUM('SCHEDULED', 'COMPLETED', 'CANCELLED') NOT NULL DEFAULT 'SCHEDULED',
    scheduled_at TIMESTAMP NOT NULL,
    duration_minutes INT NOT NULL DEFAULT 30,
    organizer_id VARCHAR(255) NOT NULL,
    analysis_id VARCHAR(255),
    incident_id VARCHAR(255),
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (organizer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (analysis_id) REFERENCES analyses(id) ON DELETE SET NULL,
    FOREIGN KEY (incident_id) REFERENCES incidents(id) ON DELETE SET NULL,
    INDEX idx_meetings_organizer (organizer_id),
    INDEX idx_meetings_status (status),
    INDEX idx_meetings_scheduled_at (scheduled_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Audit logs
CREATE TABLE audit_logs (
    id VARCHAR(255) PRIMARY KEY,
    entity_type VARCHAR(100) NOT NULL,
    entity_id VARCHAR(255) NOT NULL,
    action VARCHAR(100) NOT NULL,
    user_id VARCHAR(255),
    changes JSON,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_audit_entity (entity_type, entity_id),
    INDEX idx_audit_user_id (user_id),
    INDEX idx_audit_action (action),
    INDEX idx_audit_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- AI outputs for hypotheses and reports
CREATE TABLE ai_outputs (
    id VARCHAR(255) PRIMARY KEY,
    analysis_id VARCHAR(255) NOT NULL,
    output_type ENUM('HYPOTHESES','REPORT_DRAFT') NOT NULL,
    generated_by VARCHAR(50) NOT NULL DEFAULT 'AI',
    status ENUM('GENERATED','ACCEPTED','REJECTED','EDITED') NOT NULL DEFAULT 'GENERATED',
    payload JSON NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (analysis_id) REFERENCES analyses(id) ON DELETE CASCADE,
    INDEX idx_ai_outputs_analysis_id (analysis_id),
    INDEX idx_ai_outputs_type (output_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Analysis status history
CREATE TABLE analysis_status_history (
    id VARCHAR(255) PRIMARY KEY,
    analysis_id VARCHAR(255) NOT NULL,
    status ENUM('DRAFT', 'SUBMITTED', 'NEEDS_CHANGES', 'APPROVED', 'REPORT_GENERATED') NOT NULL,
    changed_by VARCHAR(255),
    changed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    details TEXT,
    FOREIGN KEY (analysis_id) REFERENCES analyses(id) ON DELETE CASCADE,
    FOREIGN KEY (changed_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_status_history_analysis_id (analysis_id),
    INDEX idx_status_history_changed_at (changed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Analysis revisions
CREATE TABLE analysis_revisions (
    id VARCHAR(255) PRIMARY KEY,
    analysis_id VARCHAR(255) NOT NULL,
    revision_number INT NOT NULL,
    symptoms JSON,
    signals JSON,
    hypotheses JSON,
    readiness_score INT,
    created_by VARCHAR(255),
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    notes TEXT,
    FOREIGN KEY (analysis_id) REFERENCES analyses(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY unique_analysis_revision (analysis_id, revision_number),
    INDEX idx_revisions_analysis_id (analysis_id),
    INDEX idx_revisions_revision_number (revision_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Platform state (maintenance, soft shutdown, restart signals)
CREATE TABLE platform_state (
    id VARCHAR(255) PRIMARY KEY,
    maintenance_enabled BOOLEAN NOT NULL DEFAULT FALSE,
    maintenance_message TEXT,
    soft_shutdown_enabled BOOLEAN NOT NULL DEFAULT FALSE,
    last_restart_requested_at TIMESTAMP NULL,
    updated_by VARCHAR(255),
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- System announcements
CREATE TABLE announcements (
    id VARCHAR(255) PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    severity ENUM('INFO','WARNING','CRITICAL') NOT NULL DEFAULT 'INFO',
    target_roles VARCHAR(255) NOT NULL DEFAULT 'ALL',
    starts_at TIMESTAMP NULL,
    ends_at TIMESTAMP NULL,
    dismissible BOOLEAN NOT NULL DEFAULT TRUE,
    status ENUM('ACTIVE','ARCHIVED') NOT NULL DEFAULT 'ACTIVE',
    created_by VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_announcements_status (status),
    INDEX idx_announcements_severity (severity),
    INDEX idx_announcements_starts_at (starts_at),
    INDEX idx_announcements_ends_at (ends_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Service credentials (masked, hashed)
CREATE TABLE service_credentials (
    id VARCHAR(255) PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    status ENUM('ACTIVE','ROTATED','DISABLED') NOT NULL DEFAULT 'ACTIVE',
    secret_hash VARCHAR(255) NOT NULL,
    masked_value VARCHAR(255) NOT NULL,
    created_by VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_rotated_at TIMESTAMP NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY idx_credentials_name (name),
    INDEX idx_credentials_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Feature flags (optional)
CREATE TABLE feature_flags (
    id VARCHAR(255) PRIMARY KEY,
    flag_key VARCHAR(255) NOT NULL,
    description TEXT,
    enabled BOOLEAN NOT NULL DEFAULT FALSE,
    updated_by VARCHAR(255),
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY idx_feature_flags_key (flag_key),
    INDEX idx_feature_flags_enabled (enabled)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
