<?php

declare(strict_types=1);

/**
 * Database seeder for test and demo data.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use InfraMind\Core\Config;
use InfraMind\Core\Database;
use InfraMind\Core\Logger;
use InfraMind\Services\AuthService;
use InfraMind\Services\TaskService;
use InfraMind\Services\AnalysisService;

// Load environment
Config::load(__DIR__ . '/../.env');

$logger = Logger::getInstance();

try {
    $logger->info('Starting database seeding...');

    $db = Database::getInstance();
    $authService = new AuthService();

    // Clear existing data (for testing - commented out for safety)
    // $db->execute('DELETE FROM analysis_revisions');
    // $db->execute('DELETE FROM analysis_status_history');
    // $db->execute('DELETE FROM analysis_hypotheses');
    // $db->execute('DELETE FROM reports');
    // $db->execute('DELETE FROM analyses');
    // $db->execute('DELETE FROM tasks');
    // $db->execute('DELETE FROM users');

    // Create test users
    echo "Creating test users...\n";

    $owner = $authService->signup('owner@example.com', 'Owner123!@#', 'Alice Owner', 'OWNER');
    echo "✓ Owner: " . $owner->email . "\n";

    $manager = $authService->signup('manager@example.com', 'Manager123!@#', 'Bob Manager', 'MANAGER');
    echo "✓ Manager: " . $manager->email . "\n";

    $employee1 = $authService->signup('employee1@example.com', 'Employee123!@#', 'Charlie Employee', 'EMPLOYEE');
    echo "✓ Employee 1: " . $employee1->email . "\n";

    $employee2 = $authService->signup('employee2@example.com', 'Employee123!@#', 'Diana Employee', 'EMPLOYEE');
    echo "✓ Employee 2: " . $employee2->email . "\n";

    // Create tasks
    echo "\nCreating test tasks...\n";

    $taskService = new TaskService();

    $task1 = $taskService->createTask(
        $manager->id,
        'Investigate API Latency',
        'The user-facing API has been experiencing increased latency. Investigate root cause and recommend solutions.',
        $employee1->id,
    );
    echo "✓ Task 1: " . $task1->title . " (assigned to " . $employee1->displayName . ")\n";

    $task2 = $taskService->createTask(
        $manager->id,
        'Review Database Security',
        'Conduct comprehensive security review of database infrastructure and access controls.',
        $employee2->id,
    );
    echo "✓ Task 2: " . $task2->title . " (assigned to " . $employee2->displayName . ")\n";

    // Create analyses
    echo "\nCreating test analyses...\n";

    $analysisService = new AnalysisService();

    $analysis1 = $analysisService->createAnalysis(
        $employee1->id,
        $task1->id,
        'LATENCY',
    );
    echo "✓ Analysis 1 created for task: " . $task1->title . "\n";

    // Update analysis with content
    $analysis1 = $analysisService->updateAnalysisContent(
        $analysis1->id,
        $employee1->id,
        ['High response times on user endpoint', 'Database connections timing out', 'Memory pressure on API servers'],
        ['CPU utilization >90%', 'Database query time 5s+', 'Cache hit rate <20%'],
        [
            ['text' => 'Database queries unoptimized', 'confidence' => 85, 'evidence' => ['slow queries in logs']],
            ['text' => 'Connection pool exhausted', 'confidence' => 70, 'evidence' => ['connection timeout errors']],
            ['text' => 'Cache invalidation issue', 'confidence' => 45, 'evidence' => ['low hit rates']],
        ],
        82,
    );
    echo "  - Analysis 1 updated with content (readiness: 82%)\n";

    // Submit analysis
    $analysis1 = $analysisService->submitAnalysis($analysis1->id, $employee1->id);
    echo "  - Analysis 1 submitted by employee\n";

    // Manager reviews
    $analysis1 = $analysisService->reviewAnalysis(
        $analysis1->id,
        $manager->id,
        'APPROVE',
    );
    echo "  - Analysis 1 approved by manager\n";

    // Create second analysis in draft state
    $analysis2 = $analysisService->createAnalysis(
        $employee2->id,
        $task2->id,
        'SECURITY',
    );
    echo "✓ Analysis 2 created for task: " . $task2->title . "\n";

    $analysis2 = $analysisService->updateAnalysisContent(
        $analysis2->id,
        $employee2->id,
        ['Weak password policy', 'No multi-factor authentication', 'Database exposed to all internal networks'],
        ['Plaintext passwords in logs', 'Default credentials still active', 'No audit logging'],
        [
            ['text' => 'Weak password requirements', 'confidence' => 90, 'evidence' => ['policy review']],
            ['text' => 'No 2FA enforcement', 'confidence' => 95, 'evidence' => ['system inspection']],
        ],
        65,
    );
    echo "  - Analysis 2 in progress (readiness: 65%)\n";

    $logger->info('Database seeding completed successfully');
    echo "\n✅ Seeding complete!\n";
    echo "Test credentials:\n";
    echo "  Owner: owner@example.com / Owner123!@#\n";
    echo "  Manager: manager@example.com / Manager123!@#\n";
    echo "  Employee 1: employee1@example.com / Employee123!@#\n";
    echo "  Employee 2: employee2@example.com / Employee123!@#\n";
} catch (\Exception $e) {
    $logger->critical('Seeding failed: ' . $e->getMessage());
    fwrite(STDERR, "Error: " . $e->getMessage() . "\n");
    exit(1);
}
