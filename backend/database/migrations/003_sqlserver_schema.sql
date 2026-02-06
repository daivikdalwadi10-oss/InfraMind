-- InfraMind SQL Server 2025 schema
-- Intended for SQL Server only (not SQLite/MySQL)

IF OBJECT_ID('dbo.users', 'U') IS NULL
BEGIN
    CREATE TABLE dbo.users (
        id NVARCHAR(255) NOT NULL,
        email NVARCHAR(255) NOT NULL,
        password_hash NVARCHAR(255) NOT NULL,
        role NVARCHAR(20) NOT NULL CONSTRAINT DF_users_role DEFAULT ('EMPLOYEE'),
        display_name NVARCHAR(255) NOT NULL,
        created_at DATETIME2(3) NOT NULL CONSTRAINT DF_users_created_at DEFAULT (SYSUTCDATETIME()),
        updated_at DATETIME2(3) NOT NULL CONSTRAINT DF_users_updated_at DEFAULT (SYSUTCDATETIME()),
        last_login_at DATETIME2(3) NULL,
        is_active BIT NOT NULL CONSTRAINT DF_users_is_active DEFAULT (1),
        deleted_at DATETIME2(3) NULL,
        CONSTRAINT PK_users PRIMARY KEY (id),
        CONSTRAINT UQ_users_email UNIQUE (email),
        CONSTRAINT CK_users_role CHECK (role IN ('EMPLOYEE', 'MANAGER', 'OWNER'))
    );
END;
GO

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'idx_users_email' AND object_id = OBJECT_ID('dbo.users'))
    CREATE INDEX idx_users_email ON dbo.users(email);
GO
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'idx_users_role' AND object_id = OBJECT_ID('dbo.users'))
    CREATE INDEX idx_users_role ON dbo.users(role);
GO
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'idx_users_created_at' AND object_id = OBJECT_ID('dbo.users'))
    CREATE INDEX idx_users_created_at ON dbo.users(created_at);
GO
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'idx_users_deleted_at' AND object_id = OBJECT_ID('dbo.users'))
    CREATE INDEX idx_users_deleted_at ON dbo.users(deleted_at);
GO

IF OBJECT_ID('dbo.tasks', 'U') IS NULL
BEGIN
    CREATE TABLE dbo.tasks (
        id NVARCHAR(255) NOT NULL,
        title NVARCHAR(500) NOT NULL,
        description NVARCHAR(MAX) NULL,
        assigned_to NVARCHAR(255) NULL,
        created_by NVARCHAR(255) NOT NULL,
        status NVARCHAR(20) NOT NULL CONSTRAINT DF_tasks_status DEFAULT ('OPEN'),
        created_at DATETIME2(3) NOT NULL CONSTRAINT DF_tasks_created_at DEFAULT (SYSUTCDATETIME()),
        updated_at DATETIME2(3) NOT NULL CONSTRAINT DF_tasks_updated_at DEFAULT (SYSUTCDATETIME()),
        CONSTRAINT PK_tasks PRIMARY KEY (id),
        CONSTRAINT CK_tasks_status CHECK (status IN ('OPEN', 'IN_PROGRESS', 'COMPLETED')),
        CONSTRAINT FK_tasks_created_by FOREIGN KEY (created_by) REFERENCES dbo.users(id) ON DELETE NO ACTION,
        CONSTRAINT FK_tasks_assigned_to FOREIGN KEY (assigned_to) REFERENCES dbo.users(id) ON DELETE NO ACTION
    );
END;
GO

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'idx_tasks_created_by' AND object_id = OBJECT_ID('dbo.tasks'))
    CREATE INDEX idx_tasks_created_by ON dbo.tasks(created_by);
GO
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'idx_tasks_assigned_to' AND object_id = OBJECT_ID('dbo.tasks'))
    CREATE INDEX idx_tasks_assigned_to ON dbo.tasks(assigned_to);
GO
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'idx_tasks_status' AND object_id = OBJECT_ID('dbo.tasks'))
    CREATE INDEX idx_tasks_status ON dbo.tasks(status);
GO
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'idx_tasks_created_at' AND object_id = OBJECT_ID('dbo.tasks'))
    CREATE INDEX idx_tasks_created_at ON dbo.tasks(created_at);
GO

IF OBJECT_ID('dbo.analyses', 'U') IS NULL
BEGIN
    CREATE TABLE dbo.analyses (
        id NVARCHAR(255) NOT NULL,
        task_id NVARCHAR(255) NOT NULL,
        employee_id NVARCHAR(255) NOT NULL,
        status NVARCHAR(20) NOT NULL CONSTRAINT DF_analyses_status DEFAULT ('DRAFT'),
        analysis_type NVARCHAR(20) NOT NULL,
        symptoms NVARCHAR(MAX) NULL,
        signals NVARCHAR(MAX) NULL,
        hypotheses NVARCHAR(MAX) NULL,
        readiness_score INT NOT NULL CONSTRAINT DF_analyses_readiness DEFAULT (0),
        revision_count INT NOT NULL CONSTRAINT DF_analyses_revision_count DEFAULT (0),
        manager_feedback NVARCHAR(MAX) NULL,
        created_at DATETIME2(3) NOT NULL CONSTRAINT DF_analyses_created_at DEFAULT (SYSUTCDATETIME()),
        updated_at DATETIME2(3) NOT NULL CONSTRAINT DF_analyses_updated_at DEFAULT (SYSUTCDATETIME()),
        CONSTRAINT PK_analyses PRIMARY KEY (id),
        CONSTRAINT UQ_analyses_task_id UNIQUE (task_id),
        CONSTRAINT FK_analyses_task_id FOREIGN KEY (task_id) REFERENCES dbo.tasks(id) ON DELETE CASCADE,
        CONSTRAINT FK_analyses_employee_id FOREIGN KEY (employee_id) REFERENCES dbo.users(id) ON DELETE NO ACTION,
        CONSTRAINT CK_analyses_status CHECK (status IN ('DRAFT', 'SUBMITTED', 'NEEDS_CHANGES', 'APPROVED')),
        CONSTRAINT CK_analyses_type CHECK (analysis_type IN ('LATENCY', 'SECURITY', 'OUTAGE', 'CAPACITY')),
        CONSTRAINT CK_analyses_readiness CHECK (readiness_score >= 0 AND readiness_score <= 100),
        CONSTRAINT CK_analyses_symptoms_json CHECK (symptoms IS NULL OR ISJSON(symptoms) = 1),
        CONSTRAINT CK_analyses_signals_json CHECK (signals IS NULL OR ISJSON(signals) = 1),
        CONSTRAINT CK_analyses_hypotheses_json CHECK (hypotheses IS NULL OR ISJSON(hypotheses) = 1)
    );
END;
GO

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'idx_analyses_employee_id' AND object_id = OBJECT_ID('dbo.analyses'))
    CREATE INDEX idx_analyses_employee_id ON dbo.analyses(employee_id);
GO
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'idx_analyses_status' AND object_id = OBJECT_ID('dbo.analyses'))
    CREATE INDEX idx_analyses_status ON dbo.analyses(status);
GO
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'idx_analyses_task_id' AND object_id = OBJECT_ID('dbo.analyses'))
    CREATE INDEX idx_analyses_task_id ON dbo.analyses(task_id);
GO
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'idx_analyses_created_at' AND object_id = OBJECT_ID('dbo.analyses'))
    CREATE INDEX idx_analyses_created_at ON dbo.analyses(created_at);
GO

IF OBJECT_ID('dbo.analysis_hypotheses', 'U') IS NULL
BEGIN
    CREATE TABLE dbo.analysis_hypotheses (
        id NVARCHAR(255) NOT NULL,
        analysis_id NVARCHAR(255) NOT NULL,
        text NVARCHAR(MAX) NOT NULL,
        confidence INT NOT NULL,
        evidence NVARCHAR(MAX) NULL,
        created_at DATETIME2(3) NOT NULL CONSTRAINT DF_hypotheses_created_at DEFAULT (SYSUTCDATETIME()),
        CONSTRAINT PK_analysis_hypotheses PRIMARY KEY (id),
        CONSTRAINT FK_hypotheses_analysis_id FOREIGN KEY (analysis_id) REFERENCES dbo.analyses(id) ON DELETE CASCADE,
        CONSTRAINT CK_hypotheses_confidence CHECK (confidence >= 0 AND confidence <= 100),
        CONSTRAINT CK_hypotheses_evidence_json CHECK (evidence IS NULL OR ISJSON(evidence) = 1)
    );
END;
GO

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'idx_hypotheses_analysis_id' AND object_id = OBJECT_ID('dbo.analysis_hypotheses'))
    CREATE INDEX idx_hypotheses_analysis_id ON dbo.analysis_hypotheses(analysis_id);
GO
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'idx_hypotheses_confidence' AND object_id = OBJECT_ID('dbo.analysis_hypotheses'))
    CREATE INDEX idx_hypotheses_confidence ON dbo.analysis_hypotheses(confidence);
GO

IF OBJECT_ID('dbo.reports', 'U') IS NULL
BEGIN
    CREATE TABLE dbo.reports (
        id NVARCHAR(255) NOT NULL,
        analysis_id NVARCHAR(255) NOT NULL,
        summary NVARCHAR(MAX) NOT NULL,
        created_by NVARCHAR(255) NOT NULL,
        created_at DATETIME2(3) NOT NULL CONSTRAINT DF_reports_created_at DEFAULT (SYSUTCDATETIME()),
        CONSTRAINT PK_reports PRIMARY KEY (id),
        CONSTRAINT FK_reports_analysis_id FOREIGN KEY (analysis_id) REFERENCES dbo.analyses(id) ON DELETE CASCADE,
        CONSTRAINT FK_reports_created_by FOREIGN KEY (created_by) REFERENCES dbo.users(id) ON DELETE NO ACTION
    );
END;
GO

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'idx_reports_analysis_id' AND object_id = OBJECT_ID('dbo.reports'))
    CREATE INDEX idx_reports_analysis_id ON dbo.reports(analysis_id);
GO
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'idx_reports_created_by' AND object_id = OBJECT_ID('dbo.reports'))
    CREATE INDEX idx_reports_created_by ON dbo.reports(created_by);
GO

IF OBJECT_ID('dbo.audit_logs', 'U') IS NULL
BEGIN
    CREATE TABLE dbo.audit_logs (
        id NVARCHAR(255) NOT NULL,
        entity_type NVARCHAR(100) NOT NULL,
        entity_id NVARCHAR(255) NOT NULL,
        action NVARCHAR(100) NOT NULL,
        user_id NVARCHAR(255) NULL,
        changes NVARCHAR(MAX) NULL,
        created_at DATETIME2(3) NOT NULL CONSTRAINT DF_audit_logs_created_at DEFAULT (SYSUTCDATETIME()),
        CONSTRAINT PK_audit_logs PRIMARY KEY (id),
        CONSTRAINT CK_audit_logs_changes_json CHECK (changes IS NULL OR ISJSON(changes) = 1)
    );
END;
GO

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'idx_audit_entity' AND object_id = OBJECT_ID('dbo.audit_logs'))
    CREATE INDEX idx_audit_entity ON dbo.audit_logs(entity_type, entity_id);
GO
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'idx_audit_user_id' AND object_id = OBJECT_ID('dbo.audit_logs'))
    CREATE INDEX idx_audit_user_id ON dbo.audit_logs(user_id);
GO
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'idx_audit_action' AND object_id = OBJECT_ID('dbo.audit_logs'))
    CREATE INDEX idx_audit_action ON dbo.audit_logs(action);
GO
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'idx_audit_created_at' AND object_id = OBJECT_ID('dbo.audit_logs'))
    CREATE INDEX idx_audit_created_at ON dbo.audit_logs(created_at);
GO

IF OBJECT_ID('dbo.analysis_status_history', 'U') IS NULL
BEGIN
    CREATE TABLE dbo.analysis_status_history (
        id NVARCHAR(255) NOT NULL,
        analysis_id NVARCHAR(255) NOT NULL,
        status NVARCHAR(20) NOT NULL,
        changed_by NVARCHAR(255) NULL,
        changed_at DATETIME2(3) NOT NULL CONSTRAINT DF_status_history_changed_at DEFAULT (SYSUTCDATETIME()),
        details NVARCHAR(MAX) NULL,
        CONSTRAINT PK_analysis_status_history PRIMARY KEY (id),
        CONSTRAINT FK_status_history_analysis_id FOREIGN KEY (analysis_id) REFERENCES dbo.analyses(id) ON DELETE CASCADE,
        CONSTRAINT FK_status_history_changed_by FOREIGN KEY (changed_by) REFERENCES dbo.users(id) ON DELETE NO ACTION,
        CONSTRAINT CK_status_history_status CHECK (status IN ('DRAFT', 'SUBMITTED', 'NEEDS_CHANGES', 'APPROVED'))
    );
END;
GO

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'idx_status_history_analysis_id' AND object_id = OBJECT_ID('dbo.analysis_status_history'))
    CREATE INDEX idx_status_history_analysis_id ON dbo.analysis_status_history(analysis_id);
GO
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'idx_status_history_changed_at' AND object_id = OBJECT_ID('dbo.analysis_status_history'))
    CREATE INDEX idx_status_history_changed_at ON dbo.analysis_status_history(changed_at);
GO

IF OBJECT_ID('dbo.analysis_revisions', 'U') IS NULL
BEGIN
    CREATE TABLE dbo.analysis_revisions (
        id NVARCHAR(255) NOT NULL,
        analysis_id NVARCHAR(255) NOT NULL,
        revision_number INT NOT NULL,
        symptoms NVARCHAR(MAX) NULL,
        signals NVARCHAR(MAX) NULL,
        hypotheses NVARCHAR(MAX) NULL,
        readiness_score INT NULL,
        created_by NVARCHAR(255) NULL,
        created_at DATETIME2(3) NOT NULL CONSTRAINT DF_revisions_created_at DEFAULT (SYSUTCDATETIME()),
        notes NVARCHAR(MAX) NULL,
        CONSTRAINT PK_analysis_revisions PRIMARY KEY (id),
        CONSTRAINT FK_revisions_analysis_id FOREIGN KEY (analysis_id) REFERENCES dbo.analyses(id) ON DELETE CASCADE,
        CONSTRAINT FK_revisions_created_by FOREIGN KEY (created_by) REFERENCES dbo.users(id) ON DELETE NO ACTION,
        CONSTRAINT UQ_revisions_analysis_revision UNIQUE (analysis_id, revision_number),
        CONSTRAINT CK_revisions_readiness CHECK (readiness_score IS NULL OR (readiness_score >= 0 AND readiness_score <= 100)),
        CONSTRAINT CK_revisions_symptoms_json CHECK (symptoms IS NULL OR ISJSON(symptoms) = 1),
        CONSTRAINT CK_revisions_signals_json CHECK (signals IS NULL OR ISJSON(signals) = 1),
        CONSTRAINT CK_revisions_hypotheses_json CHECK (hypotheses IS NULL OR ISJSON(hypotheses) = 1)
    );
END;
GO

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'idx_revisions_analysis_id' AND object_id = OBJECT_ID('dbo.analysis_revisions'))
    CREATE INDEX idx_revisions_analysis_id ON dbo.analysis_revisions(analysis_id);
GO
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'idx_revisions_revision_number' AND object_id = OBJECT_ID('dbo.analysis_revisions'))
    CREATE INDEX idx_revisions_revision_number ON dbo.analysis_revisions(revision_number);
GO
