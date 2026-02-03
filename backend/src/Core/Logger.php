<?php

declare(strict_types=1);

namespace InfraMind\Core;

use Monolog\Logger as MonologLogger;
use Monolog\Handler\StreamHandler;
use Monolog\Formatter\LineFormatter;

/**
 * Centralized logging system using Monolog.
 */
class Logger
{
    private static ?self $instance = null;
    private MonologLogger $logger;

    private function __construct()
    {
        $this->initLogger();
    }

    /**
     * Get singleton logger instance.
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Initialize Monolog logger.
     */
    private function initLogger(): void
    {
        $logPath = $_ENV['LOG_PATH'] ?? './logs';
        $logLevel = strtoupper($_ENV['LOG_LEVEL'] ?? 'INFO');
        $appName = $_ENV['APP_NAME'] ?? 'InfraMind';

        if (!is_dir($logPath)) {
            @mkdir($logPath, 0755, true);
        }

        $this->logger = new MonologLogger($appName);

        // Map log level string to Monolog Level enum
        $levelMap = [
            'DEBUG' => \Monolog\Level::Debug,
            'INFO' => \Monolog\Level::Info,
            'NOTICE' => \Monolog\Level::Notice,
            'WARNING' => \Monolog\Level::Warning,
            'ERROR' => \Monolog\Level::Error,
            'CRITICAL' => \Monolog\Level::Critical,
            'ALERT' => \Monolog\Level::Alert,
            'EMERGENCY' => \Monolog\Level::Emergency,
        ];
        $level = $levelMap[$logLevel] ?? \Monolog\Level::Info;

        // Use simple stream handler for better compatibility
        $handler = new StreamHandler(
            $logPath . '/app.log',
            $level,
            true,
            0644
        );

        $formatter = new LineFormatter(
            "[%datetime%] %level_name%: %message% %context% %extra%\n"
        );
        $handler->setFormatter($formatter);
        $this->logger->pushHandler($handler);

        // Add console output in development
        if (($_ENV['APP_ENV'] ?? 'development') === 'development') {
            $consoleHandler = new StreamHandler('php://stdout', constant("Monolog\Logger::" . $logLevel));
            $consoleHandler->setFormatter($formatter);
            $this->logger->pushHandler($consoleHandler);
        }
    }

    /**
     * Log info message.
     */
    public function info(string $message, array $context = []): void
    {
        $this->logger->info($message, $context);
    }

    /**
     * Log warning message.
     */
    public function warning(string $message, array $context = []): void
    {
        $this->logger->warning($message, $context);
    }

    /**
     * Log error message.
     */
    public function error(string $message, array $context = []): void
    {
        $this->logger->error($message, $context);
    }

    /**
     * Log critical error.
     */
    public function critical(string $message, array $context = []): void
    {
        $this->logger->critical($message, $context);
    }

    /**
     * Log debug message.
     */
    public function debug(string $message, array $context = []): void
    {
        $this->logger->debug($message, $context);
    }
}
