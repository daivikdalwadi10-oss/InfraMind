<?php
// Standalone SQLite-based backend for development
// This allows the backend to run without MySQL installation

require_once __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

// Create SQLite database file
$dbPath = __DIR__ . '/database.sqlite';

echo "Creating SQLite database at: $dbPath\n";

try {
    $pdo = new PDO("sqlite:$dbPath");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Read and execute schema (converted for SQLite)
    $schema = <<<SQL
-- Users table
CREATE TABLE IF NOT EXISTS users (
    id TEXT PRIMARY KEY,
    email TEXT UNIQUE NOT NULL,
    password_hash TEXT NOT NULL,
    role TEXT NOT NULL CHECK(role IN ('EMPLOYEE', 'MANAGER', 'OWNER')),
    display_name TEXT NOT NULL,
    created_at INTEGER NOT NULL,
    last_login_at INTEGER,
    is_active INTEGER DEFAULT 1,
    deleted_at INTEGER
);

-- Tasks table
CREATE TABLE IF NOT EXISTS tasks (
    id TEXT PRIMARY KEY,
    title TEXT NOT NULL,
    description TEXT,
    assigned_to TEXT,
    created_by TEXT NOT NULL,
    status TEXT NOT NULL CHECK(status IN ('OPEN', 'IN_PROGRESS', 'COMPLETED')),
    created_at INTEGER NOT NULL,
    updated_at INTEGER NOT NULL,
    FOREIGN KEY (assigned_to) REFERENCES users(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Analyses table
CREATE TABLE IF NOT EXISTS analyses (
    id TEXT PRIMARY KEY,
    task_id TEXT UNIQUE NOT NULL,
    employee_id TEXT NOT NULL,
    status TEXT NOT NULL CHECK(status IN ('DRAFT', 'SUBMITTED', 'NEEDS_CHANGES', 'APPROVED')),
    analysis_type TEXT NOT NULL CHECK(analysis_type IN ('LATENCY', 'SECURITY', 'OUTAGE', 'CAPACITY')),
    symptoms TEXT,
    signals TEXT,
    hypotheses TEXT,
    readiness_score INTEGER DEFAULT 0,
    manager_feedback TEXT,
    created_at INTEGER NOT NULL,
    updated_at INTEGER NOT NULL,
    FOREIGN KEY (task_id) REFERENCES tasks(id),
    FOREIGN KEY (employee_id) REFERENCES users(id)
);

-- Reports table
CREATE TABLE IF NOT EXISTS reports (
    id TEXT PRIMARY KEY,
    analysis_id TEXT NOT NULL,
    summary TEXT NOT NULL,
    created_by TEXT NOT NULL,
    created_at INTEGER NOT NULL,
    FOREIGN KEY (analysis_id) REFERENCES analyses(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Audit logs table
CREATE TABLE IF NOT EXISTS audit_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    entity_type TEXT NOT NULL,
    entity_id TEXT NOT NULL,
    action TEXT NOT NULL,
    user_id TEXT NOT NULL,
    changes TEXT,
    created_at INTEGER NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Analysis status history table
CREATE TABLE IF NOT EXISTS analysis_status_history (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    analysis_id TEXT NOT NULL,
    status TEXT NOT NULL,
    changed_by TEXT NOT NULL,
    changed_at INTEGER NOT NULL,
    notes TEXT,
    FOREIGN KEY (analysis_id) REFERENCES analyses(id),
    FOREIGN KEY (changed_by) REFERENCES users(id)
);

-- Analysis revisions table
CREATE TABLE IF NOT EXISTS analysis_revisions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    analysis_id TEXT NOT NULL,
    revision_number INTEGER NOT NULL,
    symptoms TEXT,
    signals TEXT,
    hypotheses TEXT,
    created_at INTEGER NOT NULL,
    created_by TEXT NOT NULL,
    FOREIGN KEY (analysis_id) REFERENCES analyses(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Create indices
CREATE INDEX IF NOT EXISTS idx_users_email ON users(email);
CREATE INDEX IF NOT EXISTS idx_users_role ON users(role);
CREATE INDEX IF NOT EXISTS idx_tasks_assigned_to ON tasks(assigned_to);
CREATE INDEX IF NOT EXISTS idx_tasks_created_by ON tasks(created_by);
CREATE INDEX IF NOT EXISTS idx_tasks_status ON tasks(status);
CREATE INDEX IF NOT EXISTS idx_analyses_task_id ON analyses(task_id);
CREATE INDEX IF NOT EXISTS idx_analyses_employee_id ON analyses(employee_id);
CREATE INDEX IF NOT EXISTS idx_analyses_status ON analyses(status);
CREATE INDEX IF NOT EXISTS idx_reports_analysis_id ON reports(analysis_id);
CREATE INDEX IF NOT EXISTS idx_audit_logs_entity ON audit_logs(entity_type, entity_id);
SQL;

    $pdo->exec($schema);
    echo "✅ Database schema created successfully!\n";
    
    // Seed test data
    echo "\nSeeding test data...\n";
    
    $now = time();
    
    // Create test users
    $users = [
        ['owner@example.com', 'Owner Account', 'OWNER'],
        ['manager@example.com', 'Manager Account', 'MANAGER'],
        ['employee1@example.com', 'Employee One', 'EMPLOYEE'],
        ['employee2@example.com', 'Employee Two', 'EMPLOYEE'],
    ];
    
    foreach ($users as [$email, $name, $role]) {
        $id = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
        
        $passwordHash = password_hash('password123ABC!', PASSWORD_BCRYPT, ['cost' => 12]);
        
        $pdo->prepare("INSERT INTO users (id, email, password_hash, role, display_name, created_at, is_active) VALUES (?, ?, ?, ?, ?, ?, 1)")
            ->execute([$id, $email, $passwordHash, $role, $name, $now]);
        
        echo "✅ Created user: $email (password: password123ABC!)\n";
    }
    
    echo "\n✅ Setup complete!\n";
    echo "\nDatabase: $dbPath\n";
    echo "Test accounts:\n";
    echo "  - owner@example.com / password123ABC!\n";
    echo "  - manager@example.com / password123ABC!\n";
    echo "  - employee1@example.com / password123ABC!\n";
    echo "  - employee2@example.com / password123ABC!\n";
    echo "\nStart the server with: php -S localhost:8000 -t public\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
SQL;
