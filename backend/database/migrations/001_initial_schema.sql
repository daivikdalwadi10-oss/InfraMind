-- Initial schema migration for InfraMind
-- SQLite-compatible version (no ENUM, no ON UPDATE, no DEFAULT UUID())

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id TEXT PRIMARY KEY,
    email TEXT UNIQUE NOT NULL,
    password_hash TEXT NOT NULL,
    role TEXT NOT NULL DEFAULT 'EMPLOYEE' CHECK (role IN ('EMPLOYEE','MANAGER','OWNER')),
    display_name TEXT NOT NULL,
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

-- Analyses table
CREATE TABLE IF NOT EXISTS analyses (
    id TEXT PRIMARY KEY,
    task_id TEXT NOT NULL UNIQUE,
    employee_id TEXT NOT NULL,
    status TEXT NOT NULL DEFAULT 'DRAFT' CHECK (status IN ('DRAFT','SUBMITTED','NEEDS_CHANGES','APPROVED')),
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
    FOREIGN KEY (employee_id) REFERENCES users(id)
);
CREATE INDEX IF NOT EXISTS idx_analyses_employee_id ON analyses(employee_id);
CREATE INDEX IF NOT EXISTS idx_analyses_status ON analyses(status);
CREATE INDEX IF NOT EXISTS idx_analyses_task_id ON analyses(task_id);
CREATE INDEX IF NOT EXISTS idx_analyses_created_at ON analyses(created_at);

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
    created_by TEXT NOT NULL,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (analysis_id) REFERENCES analyses(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
);
CREATE INDEX IF NOT EXISTS idx_reports_analysis_id ON reports(analysis_id);
CREATE INDEX IF NOT EXISTS idx_reports_created_by ON reports(created_by);

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
