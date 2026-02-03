<?php

declare(strict_types=1);

/**
 * Database setup and migration script.
 * Reads all migration files and applies them to the database.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use InfraMind\Core\Config;
use InfraMind\Core\Database;
use InfraMind\Core\Logger;

// Load environment
Config::load(__DIR__ . '/../.env');

try {
    $db = Database::getInstance();
    $logger = Logger::getInstance();

    $logger->info('Starting database migration...');

    // Get migration files
    $migrationDir = __DIR__ . '/../database/migrations';
    $migrations = glob($migrationDir . '/*.sql');
    sort($migrations);

    if (empty($migrations)) {
        $logger->warning('No migration files found');
        exit(0);
    }

    foreach ($migrations as $migrationFile) {
        $migrationName = basename($migrationFile);
        $logger->info("Running migration: $migrationName");

        $sql = file_get_contents($migrationFile);
        if ($sql === false) {
            throw new RuntimeException("Failed to read migration file: $migrationFile");
        }

        // Split by semicolon to handle multiple statements
        $statements = array_filter(array_map('trim', explode(';', $sql)));

        foreach ($statements as $statement) {
            if (!empty($statement)) {
                try {
                    $db->getConnection()->exec($statement);
                } catch (Exception $e) {
                    // Log warning but continue if statement already executed
                    $logger->warning("Migration statement warning in $migrationName: " . $e->getMessage());
                }
            }
        }

        $logger->info("Completed migration: $migrationName");
    }

    $logger->info('Database migration completed successfully');
} catch (Exception $e) {
    $logger->critical('Database migration failed: ' . $e->getMessage());
    fwrite(STDERR, "Error: " . $e->getMessage() . "\n");
    exit(1);
}
