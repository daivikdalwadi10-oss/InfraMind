<?php

declare(strict_types=1);

namespace InfraMind\Core;

use PDO;
use PDOException;

/**
 * Database connection and management.
 * Implements singleton pattern with connection pooling support.
 */
class Database
{
    private static ?self $instance = null;
    private PDO $pdo;
    private string $driver;
    private int $connectTimeout = 5;

    private function __construct()
    {
        $this->driver = $_ENV['DB_DRIVER'] ?? 'mysql';
        $this->connect();
    }

    /**
     * Get singleton database instance.
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Establish database connection with error handling.
     */
    private function connect(): void
    {
        try {
            $dsn = $this->buildDsn();
            
            // SQLite doesn't need user/password
            if ($this->driver === 'sqlite') {
                $this->pdo = new PDO(
                    $dsn,
                    null,
                    null,
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false,
                    ]
                );
            } else {
                $this->pdo = new PDO(
                    $dsn,
                    $_ENV['DB_USER'] ?? 'root',
                    $_ENV['DB_PASSWORD'] ?? '',
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false,
                        PDO::ATTR_PERSISTENT => false,
                        PDO::ATTR_TIMEOUT => $this->connectTimeout,
                    ]
                );
            }

            // Set charset for MySQL
            if ($this->driver === 'mysql') {
                $charset = $_ENV['DB_CHARSET'] ?? 'utf8mb4';
                $this->pdo->exec("SET NAMES $charset");
            }

            Logger::getInstance()->info('Database connected successfully');
        } catch (PDOException $e) {
            Logger::getInstance()->error('Database connection failed: ' . $e->getMessage());
            throw new \RuntimeException('Failed to connect to database: ' . $e->getMessage());
        }
    }

    /**
     * Build DSN string based on driver.
     */
    private function buildDsn(): string
    {
        if ($this->driver === 'sqlite') {
            $dbPath = $_ENV['DB_PATH'] ?? __DIR__ . '/../../database.sqlite';
            return "sqlite:$dbPath";
        }
        
        $host = $_ENV['DB_HOST'] ?? 'localhost';
        $port = $_ENV['DB_PORT'] ?? 3306;
        $dbName = $_ENV['DB_NAME'] ?? 'inframind';

        return match ($this->driver) {
            'mysql' => "mysql:host=$host;port=$port;dbname=$dbName",
            'pgsql' => "pgsql:host=$host;port=$port;dbname=$dbName",
            default => throw new \RuntimeException("Unsupported database driver: {$this->driver}"),
        };
    }

    /**
     * Get PDO connection.
     */
    public function getConnection(): PDO
    {
        return $this->pdo;
    }

    /**
     * Execute prepared statement with parameters.
     */
    public function execute(string $sql, array $params = []): \PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);
        if (!$stmt->execute($params)) {
            throw new \RuntimeException('Failed to execute statement');
        }
        return $stmt;
    }

    /**
     * Fetch single row.
     */
    public function fetchOne(string $sql, array $params = []): ?array
    {
        $stmt = $this->execute($sql, $params);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Fetch all rows.
     */
    public function fetchAll(string $sql, array $params = []): array
    {
        $stmt = $this->execute($sql, $params);
        return $stmt->fetchAll() ?: [];
    }

    /**
     * Execute query and return affected row count.
     */
    public function executeAffecting(string $sql, array $params = []): int
    {
        $stmt = $this->execute($sql, $params);
        return $stmt->rowCount();
    }

    /**
     * Begin transaction.
     */
    public function beginTransaction(): void
    {
        if (!$this->pdo->inTransaction()) {
            $this->pdo->beginTransaction();
        }
    }

    /**
     * Commit transaction.
     */
    public function commit(): void
    {
        if ($this->pdo->inTransaction()) {
            $this->pdo->commit();
        }
    }

    /**
     * Rollback transaction.
     */
    public function rollback(): void
    {
        if ($this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }
    }

    /**
     * Get last inserted ID.
     */
    public function lastInsertId(): string
    {
        return $this->pdo->lastInsertId();
    }

    /**
     * Close connection.
     */
    public function close(): void
    {
        $this->pdo = null;
    }
}
