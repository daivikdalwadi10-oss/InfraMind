<?php
// MySQL database setup for InfraMind backend

require_once __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

// MySQL connection (without database initially)
echo "Connecting to MySQL Server...\n";

try {
    // Connect to MySQL root
    $pdo = new PDO('mysql:host=localhost:3306', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Drop existing database if it exists
    $pdo->exec('DROP DATABASE IF EXISTS inframind');
    
    // Create database
    $pdo->exec('CREATE DATABASE inframind CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    
    echo "✓ Database 'inframind' created\n";
    
    // Connect to the new database
    $pdo = new PDO('mysql:host=localhost:3306;dbname=inframind', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create tables
    $schema = <<<SQL
-- Users table
CREATE TABLE users (
    id VARCHAR(36) PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('EMPLOYEE', 'MANAGER', 'OWNER') NOT NULL,
    display_name VARCHAR(255) NOT NULL,
    created_at DATETIME NOT NULL,
    last_login_at DATETIME NULL,
    is_active BOOLEAN DEFAULT TRUE,
    deleted_at DATETIME NULL,
    INDEX idx_email (email),
    INDEX idx_role (role)
);

-- Tasks table
CREATE TABLE tasks (
    id VARCHAR(36) PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    assigned_to VARCHAR(36),
    created_by VARCHAR(36) NOT NULL,
    status ENUM('OPEN', 'IN_PROGRESS', 'COMPLETED') NOT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX idx_assigned_to (assigned_to),
    INDEX idx_created_by (created_by),
    INDEX idx_status (status)
);

-- Analyses table
CREATE TABLE analyses (
    id VARCHAR(36) PRIMARY KEY,
    task_id VARCHAR(36) NOT NULL,
    employee_id VARCHAR(36) NOT NULL,
    status ENUM('DRAFT', 'SUBMITTED', 'APPROVED', 'REJECTED') NOT NULL,
    analysis_type VARCHAR(100),
    symptoms LONGTEXT,
    signals LONGTEXT,
    hypotheses LONGTEXT,
    readiness_score INT DEFAULT 0,
    manager_feedback TEXT,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    FOREIGN KEY (task_id) REFERENCES tasks(id),
    FOREIGN KEY (employee_id) REFERENCES users(id),
    INDEX idx_task_id (task_id),
    INDEX idx_employee_id (employee_id),
    INDEX idx_status (status)
);

-- Analysis Hypotheses table
CREATE TABLE analysis_hypotheses (
    id VARCHAR(36) PRIMARY KEY,
    analysis_id VARCHAR(36) NOT NULL,
    text TEXT NOT NULL,
    confidence INT DEFAULT 0,
    evidence LONGTEXT,
    created_at DATETIME NOT NULL,
    FOREIGN KEY (analysis_id) REFERENCES analyses(id) ON DELETE CASCADE,
    INDEX idx_analysis_id (analysis_id)
);

-- Reports table
CREATE TABLE reports (
    id VARCHAR(36) PRIMARY KEY,
    analysis_id VARCHAR(36) NOT NULL,
    executive_summary_draft LONGTEXT,
    executive_summary_final LONGTEXT,
    status ENUM('DRAFT', 'FINALIZED') NOT NULL,
    created_by VARCHAR(36) NOT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    FOREIGN KEY (analysis_id) REFERENCES analyses(id),
    FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX idx_analysis_id (analysis_id),
    INDEX idx_status (status)
);

-- Audit Logs table
CREATE TABLE audit_logs (
    id VARCHAR(36) PRIMARY KEY,
    user_id VARCHAR(36),
    action VARCHAR(100),
    entity_type VARCHAR(100),
    entity_id VARCHAR(36),
    changes LONGTEXT,
    created_at DATETIME NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_entity_type (entity_type),
    INDEX idx_created_at (created_at)
);

-- Analysis Status History table
CREATE TABLE analysis_status_history (
    id VARCHAR(36) PRIMARY KEY,
    analysis_id VARCHAR(36) NOT NULL,
    status VARCHAR(50) NOT NULL,
    changed_by VARCHAR(36) NOT NULL,
    changed_at DATETIME NOT NULL,
    reason TEXT,
    FOREIGN KEY (analysis_id) REFERENCES analyses(id) ON DELETE CASCADE,
    FOREIGN KEY (changed_by) REFERENCES users(id),
    INDEX idx_analysis_id (analysis_id),
    INDEX idx_changed_at (changed_at)
);

-- Analysis Revisions table
CREATE TABLE analysis_revisions (
    id VARCHAR(36) PRIMARY KEY,
    analysis_id VARCHAR(36) NOT NULL,
    revision_number INT NOT NULL,
    symptoms LONGTEXT,
    signals LONGTEXT,
    hypotheses LONGTEXT,
    created_by VARCHAR(36) NOT NULL,
    created_at DATETIME NOT NULL,
    FOREIGN KEY (analysis_id) REFERENCES analyses(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX idx_analysis_id (analysis_id),
    INDEX idx_revision_number (revision_number)
);
SQL;

    // Split schema into individual statements and execute
    $statements = array_filter(array_map('trim', explode(';', $schema)));
    
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            $pdo->exec($statement);
        }
    }
    
    echo "✓ All tables created successfully\n";
    
    // Seed test data
    echo "\nSeeding test data...\n";
    
    $users = [
        [
            'id' => '11ee2e7c-7251-46f1-a38b-5a6c9180d900',
            'email' => 'owner@example.com',
            'password' => password_hash('password123ABC!', PASSWORD_BCRYPT),
            'role' => 'OWNER',
            'name' => 'Owner User'
        ],
        [
            'id' => '11ee2e7c-7251-46f1-a38b-5a6c9180d901',
            'email' => 'manager@example.com',
            'password' => password_hash('password123ABC!', PASSWORD_BCRYPT),
            'role' => 'MANAGER',
            'name' => 'Manager User'
        ],
        [
            'id' => '11ee2e7c-7251-46f1-a38b-5a6c9180d902',
            'email' => 'employee1@example.com',
            'password' => password_hash('password123ABC!', PASSWORD_BCRYPT),
            'role' => 'EMPLOYEE',
            'name' => 'Employee One'
        ],
        [
            'id' => '11ee2e7c-7251-46f1-a38b-5a6c9180d903',
            'email' => 'employee2@example.com',
            'password' => password_hash('password123ABC!', PASSWORD_BCRYPT),
            'role' => 'EMPLOYEE',
            'name' => 'Employee Two'
        ]
    ];
    
    $stmt = $pdo->prepare('
        INSERT INTO users (id, email, password_hash, role, display_name, created_at, is_active)
        VALUES (?, ?, ?, ?, ?, NOW(), 1)
    ');
    
    foreach ($users as $user) {
        $stmt->execute([
            $user['id'],
            $user['email'],
            $user['password'],
            $user['role'],
            $user['name']
        ]);
    }
    
    echo "✓ Seeded 4 test users\n";
    
    // Create test task
    $taskId = '11ee2e7c-7251-46f1-a38b-5a6c9180d950';
    $pdo->prepare('
        INSERT INTO tasks (id, title, description, assigned_to, created_by, status, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())
    ')->execute([
        $taskId,
        'Test Task',
        'This is a test task for analysis',
        '11ee2e7c-7251-46f1-a38b-5a6c9180d902',
        '11ee2e7c-7251-46f1-a38b-5a6c9180d901',
        'OPEN'
    ]);
    
    echo "✓ Created test task\n";
    
    echo "\n✅ MySQL Database setup completed successfully!\n";
    echo "\nTest Credentials:\n";
    echo "  Owner:     owner@example.com / password123ABC!\n";
    echo "  Manager:   manager@example.com / password123ABC!\n";
    echo "  Employee:  employee1@example.com / password123ABC!\n";
    echo "  Employee:  employee2@example.com / password123ABC!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
    exit(1);
}
