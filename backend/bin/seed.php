<?php

declare(strict_types=1);

/**
 * Database seeder for test and demo data.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use InfraMind\Core\Config;
use InfraMind\Core\Database;
use InfraMind\Core\Logger;
use InfraMind\Core\PasswordManager;

// Load environment
Config::load(__DIR__ . '/../.env');

$logger = Logger::getInstance();

function generateId(): string {
    return bin2hex(random_bytes(18));
}

try {
    $logger->info('Starting database seeding...');

    $db = Database::getInstance();
    $now = date('Y-m-d H:i:s');

    // For SQLite, disable foreign keys during seeding
    $driver = $_ENV['DB_DRIVER'] ?? 'mysql';
    if ($driver === 'sqlite') {
        $db->getConnection()->exec('PRAGMA foreign_keys = OFF;');
        $logger->info('SQLite: Disabled foreign keys for seeding');
    }

    $existingUsers = $db->fetchOne('SELECT COUNT(*) AS count FROM users');
    if ($existingUsers && (int) ($existingUsers['count'] ?? 0) > 0) {
        echo "Users already exist. Skipping seed data.\n";
        exit(0);
    }

    // Create test users using direct INSERT to avoid service layer issues
    echo "Creating test users...\n";

    $ownerId = generateId();
    $managerId = generateId();
    $employee1Id = generateId();
    $employee2Id = generateId();

    $passwordManager = new PasswordManager();
    
    $ownerHash = $passwordManager->hash('Owner123!@#');
    $managerHash = $passwordManager->hash('Manager123!@#');
    $employee1Hash = $passwordManager->hash('Employee123!@#');
    $employee2Hash = $passwordManager->hash('Employee123!@#');

    // Insert users
    $db->execute(
        'INSERT INTO users (id, email, password_hash, role, display_name, created_at, updated_at, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
        [$ownerId, 'owner@example.com', $ownerHash, 'OWNER', 'Alice Owner', $now, $now, 1]
    );
    echo "✓ Owner: owner@example.com\n";

    $db->execute(
        'INSERT INTO users (id, email, password_hash, role, display_name, created_at, updated_at, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
        [$managerId, 'manager@example.com', $managerHash, 'MANAGER', 'Bob Manager', $now, $now, 1]
    );
    echo "✓ Manager: manager@example.com\n";

    $db->execute(
        'INSERT INTO users (id, email, password_hash, role, display_name, created_at, updated_at, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
        [$employee1Id, 'employee1@example.com', $employee1Hash, 'EMPLOYEE', 'Charlie Employee', $now, $now, 1]
    );
    echo "✓ Employee 1: employee1@example.com\n";

    $db->execute(
        'INSERT INTO users (id, email, password_hash, role, display_name, created_at, updated_at, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
        [$employee2Id, 'employee2@example.com', $employee2Hash, 'EMPLOYEE', 'Diana Employee', $now, $now, 1]
    );
    echo "✓ Employee 2: employee2@example.com\n";

    // Create tasks
    echo "\nCreating test tasks...\n";

    $task1Id = generateId();
    $task2Id = generateId();

    $db->execute(
        'INSERT INTO tasks (id, title, description, created_by, assigned_to, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
        [
            $task1Id,
            'Investigate API Latency',
            'The user-facing API has been experiencing increased latency. Investigate root cause and recommend solutions.',
            $managerId,
            $employee1Id,
            'IN_PROGRESS',
            $now,
            $now
        ]
    );
    echo "✓ Task 1: Investigate API Latency\n";

    $db->execute(
        'INSERT INTO tasks (id, title, description, created_by, assigned_to, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
        [
            $task2Id,
            'Review Database Security',
            'Conduct comprehensive security review of database infrastructure and access controls.',
            $managerId,
            $employee2Id,
            'OPEN',
            $now,
            $now
        ]
    );
    echo "✓ Task 2: Review Database Security\n";

    // Create analyses
    echo "\nCreating test analyses...\n";

    $analysis1Id = generateId();
    $analysis2Id = generateId();

    $symptoms = json_encode([
        'High response times on user endpoint',
        'Database connections timing out',
        'Memory pressure on API servers'
    ]);

    $signals = json_encode([
        'CPU utilization >90%',
        'Database query time 5s+',
        'Cache hit rate <20%'
    ]);

    $hypotheses = json_encode([
        ['text' => 'Database queries unoptimized', 'confidence' => 85, 'evidence' => ['slow queries in logs']],
        ['text' => 'Connection pool exhausted', 'confidence' => 70, 'evidence' => ['connection timeout errors']],
        ['text' => 'Cache invalidation issue', 'confidence' => 45, 'evidence' => ['low hit rates']]
    ]);

    $db->execute(
        'INSERT INTO analyses (id, task_id, employee_id, status, analysis_type, symptoms, signals, hypotheses, readiness_score, revision_count, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
        [
            $analysis1Id,
            $task1Id,
            $employee1Id,
            'SUBMITTED',
            'LATENCY',
            $symptoms,
            $signals,
            $hypotheses,
            82,
            1,
            $now,
            $now
        ]
    );
    echo "✓ Analysis 1 created (LATENCY, readiness: 82%)\n";

    // Add status history for analysis 1
    $statusHistoryId = generateId();
    $db->execute(
        'INSERT INTO analysis_status_history (id, analysis_id, status, changed_by, changed_at) VALUES (?, ?, ?, ?, ?)',
        [$statusHistoryId, $analysis1Id, 'SUBMITTED', $employee1Id, $now]
    );

    $symptoms2 = json_encode([
        'Unauthorized access attempts detected',
        'Missing encryption on data at rest',
        'Weak password policies'
    ]);

    $signals2 = json_encode([
        'Failed login attempts spike',
        'Plaintext passwords in logs',
        'No 2FA requirement'
    ]);

    $hypotheses2 = json_encode([
        ['text' => 'Brute force attack vector', 'confidence' => 80, 'evidence' => ['IP block lists', 'login audit trail']],
        ['text' => 'Missing TLS enforcement', 'confidence' => 75, 'evidence' => ['protocol analysis']],
        ['text' => 'Insufficient access controls', 'confidence' => 90, 'evidence' => ['role-based access review']]
    ]);

    $db->execute(
        'INSERT INTO analyses (id, task_id, employee_id, status, analysis_type, symptoms, signals, hypotheses, readiness_score, revision_count, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
        [
            $analysis2Id,
            $task2Id,
            $employee2Id,
            'DRAFT',
            'SECURITY',
            $symptoms2,
            $signals2,
            $hypotheses2,
            65,
            0,
            $now,
            $now
        ]
    );
    echo "✓ Analysis 2 created (SECURITY, readiness: 65%)\n";

    // Re-enable foreign keys for SQLite
    if ($driver === 'sqlite') {
        $db->getConnection()->exec('PRAGMA foreign_keys = ON;');
        $logger->info('SQLite: Re-enabled foreign keys after seeding');
    }

    echo "\n✅ Database seeding completed successfully!\n";
    echo "\nTest Credentials:\n";
    echo "  Owner:     owner@example.com / Owner123!@#\n";
    echo "  Manager:   manager@example.com / Manager123!@#\n";
    echo "  Employee1: employee1@example.com / Employee123!@#\n";
    echo "  Employee2: employee2@example.com / Employee123!@#\n";

} catch (Exception $e) {
    $logger->critical('Database seeding failed: ' . $e->getMessage());
    fwrite(STDERR, "Error: " . $e->getMessage() . "\n");
    fwrite(STDERR, $e->getTraceAsString() . "\n");
    exit(1);
}
