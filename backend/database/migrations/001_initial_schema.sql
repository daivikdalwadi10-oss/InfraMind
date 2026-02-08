-- Initial schema migration for InfraMind
-- SQLite-compatible version (no ENUM, no ON UPDATE, no DEFAULT UUID())

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id TEXT PRIMARY KEY,
    email TEXT UNIQUE NOT NULL,
    password_hash TEXT NOT NULL,
    role TEXT NOT NULL DEFAULT 'EMPLOYEE' CHECK (role IN ('EMPLOYEE','MANAGER','OWNER','DEVELOPER','SYSTEM_ADMIN')),
    display_name TEXT NOT NULL,
    position TEXT,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_login_at TEXT,
    is_active INTEGER NOT NULL DEFAULT 1,
    deleted_at TEXT
);
CREATE INDEX IF NOT EXISTS idx_users_email ON users(email);
CREATE INDEX IF NOT EXISTS idx_users_role ON users(role);
CREATE INDEX IF NOT EXISTS idx_users_created_at ON users(created_at);
CREATE INDEX IF NOT EXISTS idx_users_deleted_at ON users(deleted_at);

-- Tasks table
CREATE TABLE IF NOT EXISTS tasks (
    id TEXT PRIMARY KEY,
    title TEXT NOT NULL,
    description TEXT,
    assigned_to TEXT,
    created_by TEXT NOT NULL,
    status TEXT NOT NULL DEFAULT 'OPEN' CHECK (status IN ('OPEN','IN_PROGRESS','COMPLETED')),
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id),
    FOREIGN KEY (assigned_to) REFERENCES users(id)
);
CREATE INDEX IF NOT EXISTS idx_tasks_created_by ON tasks(created_by);
CREATE INDEX IF NOT EXISTS idx_tasks_assigned_to ON tasks(assigned_to);
CREATE INDEX IF NOT EXISTS idx_tasks_status ON tasks(status);
CREATE INDEX IF NOT EXISTS idx_tasks_created_at ON tasks(created_at);

-- Teams table
CREATE TABLE IF NOT EXISTS teams (
    id TEXT PRIMARY KEY,
    name TEXT NOT NULL,
    description TEXT,
    manager_id TEXT NOT NULL,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (manager_id) REFERENCES users(id)
);
CREATE UNIQUE INDEX IF NOT EXISTS idx_teams_name ON teams(name);
CREATE INDEX IF NOT EXISTS idx_teams_manager_id ON teams(manager_id);

-- Team members table
CREATE TABLE IF NOT EXISTS team_members (
    id TEXT PRIMARY KEY,
    team_id TEXT NOT NULL,
    user_id TEXT NOT NULL,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE (team_id, user_id)
);
CREATE INDEX IF NOT EXISTS idx_team_members_team_id ON team_members(team_id);
CREATE INDEX IF NOT EXISTS idx_team_members_user_id ON team_members(user_id);

-- Analyses table
CREATE TABLE IF NOT EXISTS analyses (
    id TEXT PRIMARY KEY,
    task_id TEXT NOT NULL UNIQUE,
    title TEXT NOT NULL,
    employee_id TEXT NOT NULL,
    created_by TEXT NOT NULL,
    team_id TEXT,
    status TEXT NOT NULL DEFAULT 'DRAFT' CHECK (status IN ('DRAFT','SUBMITTED','NEEDS_CHANGES','APPROVED','REPORT_GENERATED')),
    analysis_type TEXT NOT NULL CHECK (analysis_type IN ('LATENCY','SECURITY','OUTAGE','CAPACITY')),
    symptoms TEXT,
    signals TEXT,
    hypotheses TEXT,
    readiness_score INTEGER NOT NULL DEFAULT 0,
    revision_count INTEGER NOT NULL DEFAULT 0,
    manager_feedback TEXT,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (task_id) REFERENCES tasks(id),
    FOREIGN KEY (employee_id) REFERENCES users(id),
    FOREIGN KEY (created_by) REFERENCES users(id),
    FOREIGN KEY (team_id) REFERENCES teams(id)
);
CREATE INDEX IF NOT EXISTS idx_analyses_employee_id ON analyses(employee_id);
CREATE INDEX IF NOT EXISTS idx_analyses_status ON analyses(status);
CREATE INDEX IF NOT EXISTS idx_analyses_task_id ON analyses(task_id);
CREATE INDEX IF NOT EXISTS idx_analyses_created_at ON analyses(created_at);
CREATE INDEX IF NOT EXISTS idx_analyses_created_by ON analyses(created_by);
CREATE INDEX IF NOT EXISTS idx_analyses_team_id ON analyses(team_id);

-- Analysis inputs (systems engineering context)
CREATE TABLE IF NOT EXISTS analysis_inputs (
    id TEXT PRIMARY KEY,
    analysis_id TEXT NOT NULL UNIQUE,
    environment_context TEXT,
    timeline_events TEXT,
    dependency_impact TEXT,
    risk_classification TEXT,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (analysis_id) REFERENCES analyses(id) ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS idx_analysis_inputs_analysis_id ON analysis_inputs(analysis_id);

-- Analysis hypotheses (normalized for better querying and revision tracking)
CREATE TABLE IF NOT EXISTS analysis_hypotheses (
    id TEXT PRIMARY KEY,
    analysis_id TEXT NOT NULL,
    text TEXT NOT NULL,
    confidence INTEGER NOT NULL,
    evidence TEXT,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (analysis_id) REFERENCES analyses(id) ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS idx_hypotheses_analysis_id ON analysis_hypotheses(analysis_id);
CREATE INDEX IF NOT EXISTS idx_hypotheses_confidence ON analysis_hypotheses(confidence);

-- Reports table
CREATE TABLE IF NOT EXISTS reports (
    id TEXT PRIMARY KEY,
    analysis_id TEXT NOT NULL,
    summary TEXT NOT NULL,
    executive_summary TEXT,
    root_cause TEXT,
    impact TEXT,
    resolution TEXT,
    prevention_steps TEXT,
    ai_assisted INTEGER NOT NULL DEFAULT 0,
    status TEXT NOT NULL DEFAULT 'FINALIZED' CHECK (status IN ('DRAFT','FINALIZED')),
    created_by TEXT NOT NULL,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (analysis_id) REFERENCES analyses(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
);
CREATE INDEX IF NOT EXISTS idx_reports_analysis_id ON reports(analysis_id);
CREATE INDEX IF NOT EXISTS idx_reports_created_by ON reports(created_by);
CREATE INDEX IF NOT EXISTS idx_reports_status ON reports(status);

-- Incidents table
CREATE TABLE IF NOT EXISTS incidents (
    id TEXT PRIMARY KEY,
    title TEXT NOT NULL,
    description TEXT,
    severity TEXT NOT NULL DEFAULT 'MEDIUM' CHECK (severity IN ('LOW','MEDIUM','HIGH','CRITICAL')),
    status TEXT NOT NULL DEFAULT 'OPEN' CHECK (status IN ('OPEN','INVESTIGATING','RESOLVED')),
    reported_by TEXT NOT NULL,
    assigned_to TEXT,
    occurred_at TEXT,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    resolved_at TEXT,
    FOREIGN KEY (reported_by) REFERENCES users(id),
    FOREIGN KEY (assigned_to) REFERENCES users(id)
);
CREATE INDEX IF NOT EXISTS idx_incidents_reported_by ON incidents(reported_by);
CREATE INDEX IF NOT EXISTS idx_incidents_assigned_to ON incidents(assigned_to);
CREATE INDEX IF NOT EXISTS idx_incidents_status ON incidents(status);
CREATE INDEX IF NOT EXISTS idx_incidents_severity ON incidents(severity);

-- Infrastructure states table
CREATE TABLE IF NOT EXISTS infrastructure_states (
    id TEXT PRIMARY KEY,
    component TEXT NOT NULL,
    status TEXT NOT NULL DEFAULT 'HEALTHY' CHECK (status IN ('HEALTHY','DEGRADED','OUTAGE','MAINTENANCE')),
    summary TEXT,
    observed_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    reported_by TEXT NOT NULL,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (reported_by) REFERENCES users(id)
);
CREATE INDEX IF NOT EXISTS idx_infra_component ON infrastructure_states(component);
CREATE INDEX IF NOT EXISTS idx_infra_status ON infrastructure_states(status);
CREATE INDEX IF NOT EXISTS idx_infra_reported_by ON infrastructure_states(reported_by);

-- Architecture risks table
CREATE TABLE IF NOT EXISTS architecture_risks (
    id TEXT PRIMARY KEY,
    title TEXT NOT NULL,
    description TEXT,
    severity TEXT NOT NULL DEFAULT 'MEDIUM' CHECK (severity IN ('LOW','MEDIUM','HIGH','CRITICAL')),
    status TEXT NOT NULL DEFAULT 'OPEN' CHECK (status IN ('OPEN','MITIGATING','RESOLVED')),
    owner_id TEXT NOT NULL,
    analysis_id TEXT,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    resolved_at TEXT,
    FOREIGN KEY (owner_id) REFERENCES users(id),
    FOREIGN KEY (analysis_id) REFERENCES analyses(id)
);
CREATE INDEX IF NOT EXISTS idx_risks_owner ON architecture_risks(owner_id);
CREATE INDEX IF NOT EXISTS idx_risks_analysis ON architecture_risks(analysis_id);
CREATE INDEX IF NOT EXISTS idx_risks_status ON architecture_risks(status);
CREATE INDEX IF NOT EXISTS idx_risks_severity ON architecture_risks(severity);

-- Meetings table
CREATE TABLE IF NOT EXISTS meetings (
    id TEXT PRIMARY KEY,
    title TEXT NOT NULL,
    agenda TEXT,
    status TEXT NOT NULL DEFAULT 'SCHEDULED' CHECK (status IN ('SCHEDULED','COMPLETED','CANCELLED')),
    scheduled_at TEXT NOT NULL,
    duration_minutes INTEGER NOT NULL DEFAULT 30,
    organizer_id TEXT NOT NULL,
    analysis_id TEXT,
    incident_id TEXT,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (organizer_id) REFERENCES users(id),
    FOREIGN KEY (analysis_id) REFERENCES analyses(id),
    FOREIGN KEY (incident_id) REFERENCES incidents(id)
);
CREATE INDEX IF NOT EXISTS idx_meetings_organizer ON meetings(organizer_id);
CREATE INDEX IF NOT EXISTS idx_meetings_status ON meetings(status);
CREATE INDEX IF NOT EXISTS idx_meetings_scheduled_at ON meetings(scheduled_at);


-- Audit logs for compliance and debugging
CREATE TABLE IF NOT EXISTS audit_logs (
    id TEXT PRIMARY KEY,
    entity_type TEXT NOT NULL,
    entity_id TEXT NOT NULL,
    action TEXT NOT NULL,
    user_id TEXT,
    changes TEXT,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS idx_audit_entity ON audit_logs(entity_type, entity_id);
CREATE INDEX IF NOT EXISTS idx_audit_user_id ON audit_logs(user_id);
CREATE INDEX IF NOT EXISTS idx_audit_action ON audit_logs(action);
CREATE INDEX IF NOT EXISTS idx_audit_created_at ON audit_logs(created_at);

-- AI outputs for hypotheses and reports
CREATE TABLE IF NOT EXISTS ai_outputs (
    id TEXT PRIMARY KEY,
    analysis_id TEXT NOT NULL,
    output_type TEXT NOT NULL CHECK (output_type IN ('HYPOTHESES','REPORT_DRAFT')),
    generated_by TEXT NOT NULL DEFAULT 'AI',
    status TEXT NOT NULL DEFAULT 'GENERATED' CHECK (status IN ('GENERATED','ACCEPTED','REJECTED','EDITED')),
    payload TEXT NOT NULL,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (analysis_id) REFERENCES analyses(id) ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS idx_ai_outputs_analysis_id ON ai_outputs(analysis_id);
CREATE INDEX IF NOT EXISTS idx_ai_outputs_type ON ai_outputs(output_type);

-- Status history tracking for analyses (denormalized for quick access)
CREATE TABLE IF NOT EXISTS analysis_status_history (
    id TEXT PRIMARY KEY,
    analysis_id TEXT NOT NULL,
    status TEXT NOT NULL,
    changed_by TEXT,
    changed_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    details TEXT,
    FOREIGN KEY (analysis_id) REFERENCES analyses(id) ON DELETE CASCADE,
    FOREIGN KEY (changed_by) REFERENCES users(id)
);
CREATE INDEX IF NOT EXISTS idx_status_history_analysis_id ON analysis_status_history(analysis_id);
CREATE INDEX IF NOT EXISTS idx_status_history_changed_at ON analysis_status_history(changed_at);

-- Analysis revisions for versioning and rollback capability
CREATE TABLE IF NOT EXISTS analysis_revisions (
    id TEXT PRIMARY KEY,
    analysis_id TEXT NOT NULL,
    revision_number INTEGER NOT NULL,
    symptoms TEXT,
    signals TEXT,
    hypotheses TEXT,
    readiness_score INTEGER,
    created_by TEXT,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    notes TEXT,
    FOREIGN KEY (analysis_id) REFERENCES analyses(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id),
    UNIQUE (analysis_id, revision_number)
);
CREATE INDEX IF NOT EXISTS idx_revisions_analysis_id ON analysis_revisions(analysis_id);
CREATE INDEX IF NOT EXISTS idx_revisions_revision_number ON analysis_revisions(revision_number);

-- Platform state (maintenance, soft shutdown, restart signals)
CREATE TABLE IF NOT EXISTS platform_state (
    id TEXT PRIMARY KEY,
    maintenance_enabled INTEGER NOT NULL DEFAULT 0,
    maintenance_message TEXT,
    soft_shutdown_enabled INTEGER NOT NULL DEFAULT 0,
    last_restart_requested_at TEXT,
    updated_by TEXT,
    updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (updated_by) REFERENCES users(id)
);

-- System announcements
CREATE TABLE IF NOT EXISTS announcements (
    id TEXT PRIMARY KEY,
    title TEXT NOT NULL,
    message TEXT NOT NULL,
    severity TEXT NOT NULL DEFAULT 'INFO' CHECK (severity IN ('INFO','WARNING','CRITICAL')),
    target_roles TEXT NOT NULL DEFAULT 'ALL',
    starts_at TEXT,
    ends_at TEXT,
    dismissible INTEGER NOT NULL DEFAULT 1,
    status TEXT NOT NULL DEFAULT 'ACTIVE' CHECK (status IN ('ACTIVE','ARCHIVED')),
    created_by TEXT NOT NULL,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id)
);
CREATE INDEX IF NOT EXISTS idx_announcements_status ON announcements(status);
CREATE INDEX IF NOT EXISTS idx_announcements_severity ON announcements(severity);
CREATE INDEX IF NOT EXISTS idx_announcements_starts_at ON announcements(starts_at);
CREATE INDEX IF NOT EXISTS idx_announcements_ends_at ON announcements(ends_at);

-- Service credentials (masked, hashed)
CREATE TABLE IF NOT EXISTS service_credentials (
    id TEXT PRIMARY KEY,
    name TEXT NOT NULL,
    description TEXT,
    status TEXT NOT NULL DEFAULT 'ACTIVE' CHECK (status IN ('ACTIVE','ROTATED','DISABLED')),
    secret_hash TEXT NOT NULL,
    masked_value TEXT NOT NULL,
    created_by TEXT NOT NULL,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_rotated_at TEXT,
    FOREIGN KEY (created_by) REFERENCES users(id)
);
CREATE INDEX IF NOT EXISTS idx_credentials_status ON service_credentials(status);
CREATE UNIQUE INDEX IF NOT EXISTS idx_credentials_name ON service_credentials(name);

-- Feature flags (optional)
CREATE TABLE IF NOT EXISTS feature_flags (
    id TEXT PRIMARY KEY,
    flag_key TEXT NOT NULL UNIQUE,
    description TEXT,
    enabled INTEGER NOT NULL DEFAULT 0,
    updated_by TEXT,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (updated_by) REFERENCES users(id)
);
CREATE INDEX IF NOT EXISTS idx_feature_flags_enabled ON feature_flags(enabled);
