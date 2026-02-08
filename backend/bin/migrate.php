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
    $logger = Logger::getInstance();
    $db = Database::getInstance();

    $logger->info('Starting database migration...');

    // Get migration files based on driver
    $migrationDir = __DIR__ . '/../database/migrations';
    $driver = $_ENV['DB_DRIVER'] ?? 'mysql';

    $pattern = match ($driver) {
        'sqlite' => '*_initial_schema.sql',
        'mysql' => '*_mysql_schema.mysql.sql',
        'sqlsrv' => '*_sqlserver_schema.sql',
        default => '*.sql',
    };

    $migrations = glob($migrationDir . '/' . $pattern);
    sort($migrations);

    if (empty($migrations)) {
        $logger->warning('No migration files found');
        exit(0);
    }

    // For SQLite, disable foreign key constraints during migration
    $driver = $_ENV['DB_DRIVER'] ?? 'mysql';
    if ($driver === 'sqlite') {
        $db->getConnection()->exec('PRAGMA foreign_keys = OFF;');
        $logger->info('SQLite: Disabled foreign keys for migration');
    }

    foreach ($migrations as $migrationFile) {
        $migrationName = basename($migrationFile);
        $logger->info("Running migration: $migrationName");

        $sql = file_get_contents($migrationFile);
        if ($sql === false) {
            throw new RuntimeException("Failed to read migration file: $migrationFile");
        }

        // Remove comments and split by statement separators
        $sql = preg_replace('/--.*$/m', '', $sql);
        if ($driver === 'sqlsrv') {
            $statements = preg_split('/^\s*GO\s*$/mi', $sql) ?: [];
        } else {
            $statements = explode(';', $sql);
        }
        $statements = array_filter(array_map('trim', $statements));

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

    // Re-enable foreign keys for SQLite
    if ($driver === 'sqlite') {
        $db->getConnection()->exec('PRAGMA foreign_keys = ON;');
        $logger->info('SQLite: Re-enabled foreign keys after migration');
    }

    $logger->info('Database migration completed successfully');
} catch (Exception $e) {
    $logger->critical('Database migration failed: ' . $e->getMessage());
    fwrite(STDERR, "Error: " . $e->getMessage() . "\n");
    exit(1);
}
