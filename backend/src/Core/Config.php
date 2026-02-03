<?php

declare(strict_types=1);

namespace InfraMind\Core;

use InfraMind\Exceptions\ValidationException;
use InfraMind\Exceptions\Exception as InfraMindException;

/**
 * Environment and application configuration loader.
 */
class Config
{
    private static array $values = [];

    /**
     * Load configuration from .env file.
     */
    public static function load(string $envPath = null): void
    {
        if ($envPath === null) {
            $envPath = dirname(__DIR__, 2) . '/.env';
        }

        if (file_exists($envPath)) {
            $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                // Skip comments
                if (strpos(trim($line), '#') === 0) {
                    continue;
                }

                // Parse key=value
                if (strpos($line, '=') !== false) {
                    [$key, $value] = explode('=', $line, 2);
                    $key = trim($key);
                    $value = trim($value);

                    // Remove quotes
                    if ((str_starts_with($value, '"') && str_ends_with($value, '"')) ||
                        (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
                        $value = substr($value, 1, -1);
                    }

                    $_ENV[$key] = $value;
                    self::$values[$key] = $value;
                }
            }
        }

        // Set defaults if not present
        self::setDefaults();
    }

    /**
     * Set default configuration values.
     */
    private static function setDefaults(): void
    {
        $defaults = [
            'APP_ENV' => 'development',
            'APP_DEBUG' => 'true',
            'APP_NAME' => 'InfraMind',
            'APP_URL' => 'http://localhost:8000',
            'SERVER_PORT' => '8000',
            'DB_DRIVER' => 'mysql',
            'DB_HOST' => 'localhost',
            'DB_PORT' => '3306',
            'DB_CHARSET' => 'utf8mb4',
            'DB_COLLATION' => 'utf8mb4_unicode_ci',
            'JWT_ALGORITHM' => 'HS256',
            'JWT_EXPIRATION' => '86400',
            'JWT_REFRESH_EXPIRATION' => '604800',
            'RATE_LIMIT_ENABLED' => 'true',
            'RATE_LIMIT_REQUESTS' => '100',
            'RATE_LIMIT_WINDOW' => '60',
            'LOG_LEVEL' => 'info',
            'CORS_ENABLED' => 'true',
        ];

        foreach ($defaults as $key => $value) {
            if (!isset($_ENV[$key])) {
                $_ENV[$key] = $value;
                self::$values[$key] = $value;
            }
        }
    }

    /**
     * Get configuration value.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        return $_ENV[$key] ?? self::$values[$key] ?? $default;
    }

    /**
     * Check if configuration key exists.
     */
    public static function has(string $key): bool
    {
        return isset($_ENV[$key]) || isset(self::$values[$key]);
    }

    /**
     * Get as boolean.
     */
    public static function getBool(string $key, bool $default = false): bool
    {
        $value = self::get($key);
        if ($value === null) {
            return $default;
        }
        return in_array(strtolower($value), ['true', '1', 'yes', 'on'], true);
    }

    /**
     * Get as integer.
     */
    public static function getInt(string $key, int $default = 0): int
    {
        $value = self::get($key);
        return $value !== null ? (int) $value : $default;
    }

    /**
     * Validate required configuration keys exist.
     */
    public static function require(array $keys): void
    {
        $missing = [];
        foreach ($keys as $key) {
            if (!self::has($key)) {
                $missing[] = $key;
            }
        }

        if (!empty($missing)) {
            throw new InfraMindException('Missing required configuration: ' . implode(', ', $missing));
        }
    }
}
