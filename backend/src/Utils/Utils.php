<?php

declare(strict_types=1);

namespace InfraMind\Utils;

/**
 * Utility functions for the application.
 */
class Utils
{
    /**
     * Generate UUID v4 (random).
     */
    public static function generateUuid(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }

    /**
     * Validate UUID format.
     */
    public static function isValidUuid(string $uuid): bool
    {
        $uuidPattern = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i';
        return preg_match($uuidPattern, $uuid) === 1;
    }

    /**
     * Sanitize string input (basic).
     */
    public static function sanitize(string $input): string
    {
        return trim(htmlspecialchars($input, ENT_QUOTES, 'UTF-8'));
    }

    /**
     * Check if string is empty or only whitespace.
     */
    public static function isEmpty(?string $input): bool
    {
        return empty(trim($input ?? ''));
    }

    /**
     * Get current ISO 8601 timestamp.
     */
    public static function now(): string
    {
        return date('Y-m-d H:i:s');
    }

    /**
     * Get current ISO 8601 timestamp with timezone.
     */
    public static function nowIso(): string
    {
        return date('c');
    }

    /**
     * Array key exists and not null.
     */
    public static function hasValue(array $array, string $key): bool
    {
        return isset($array[$key]) && $array[$key] !== null;
    }

    /**
     * Get nested array value with default.
     */
    public static function get(array $array, string $path, mixed $default = null): mixed
    {
        $keys = explode('.', $path);
        $current = $array;

        foreach ($keys as $key) {
            if (!is_array($current) || !isset($current[$key])) {
                return $default;
            }
            $current = $current[$key];
        }

        return $current;
    }

    /**
     * Filter and flatten errors array.
     */
    public static function flattenErrors(array $errors): array
    {
        $flattened = [];
        foreach ($errors as $field => $messages) {
            if (is_array($messages)) {
                $flattened[$field] = implode(', ', $messages);
            } else {
                $flattened[$field] = (string) $messages;
            }
        }
        return $flattened;
    }
}
