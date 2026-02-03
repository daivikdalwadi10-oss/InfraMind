<?php

declare(strict_types=1);

namespace InfraMind\Core;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use InfraMind\Exceptions\AuthenticationException;

/**
 * JWT token handling and validation.
 */
class TokenManager
{
    private string $secret;
    private string $algorithm = 'HS256';
    private int $expirationSeconds;
    private int $refreshExpirationSeconds;

    public function __construct()
    {
        $this->secret = Config::get('JWT_SECRET');
        if (empty($this->secret)) {
            throw new \RuntimeException('JWT_SECRET not configured');
        }

        $this->algorithm = Config::get('JWT_ALGORITHM', 'HS256');
        $this->expirationSeconds = Config::getInt('JWT_EXPIRATION', 86400);
        $this->refreshExpirationSeconds = Config::getInt('JWT_REFRESH_EXPIRATION', 604800);
    }

    /**
     * Generate access token.
     */
    public function generateAccessToken(string $userId, string $email, string $role): string
    {
        $now = time();
        $payload = [
            'iat' => $now,
            'exp' => $now + $this->expirationSeconds,
            'sub' => $userId,
            'email' => $email,
            'role' => $role,
            'type' => 'access',
        ];

        return JWT::encode($payload, $this->secret, $this->algorithm);
    }

    /**
     * Generate refresh token.
     */
    public function generateRefreshToken(string $userId): string
    {
        $now = time();
        $payload = [
            'iat' => $now,
            'exp' => $now + $this->refreshExpirationSeconds,
            'sub' => $userId,
            'type' => 'refresh',
        ];

        return JWT::encode($payload, $this->secret, $this->algorithm);
    }

    /**
     * Verify and decode access token.
     */
    public function verifyAccessToken(string $token): object
    {
        try {
            return JWT::decode($token, new Key($this->secret, $this->algorithm));
        } catch (\Exception $e) {
            Logger::getInstance()->warning('Invalid token: ' . $e->getMessage());
            throw new AuthenticationException('Invalid or expired token');
        }
    }

    /**
     * Verify and decode refresh token.
     */
    public function verifyRefreshToken(string $token): object
    {
        try {
            $decoded = JWT::decode($token, new Key($this->secret, $this->algorithm));
            if ($decoded->type !== 'refresh') {
                throw new AuthenticationException('Invalid token type');
            }
            return $decoded;
        } catch (\Exception $e) {
            Logger::getInstance()->warning('Invalid refresh token: ' . $e->getMessage());
            throw new AuthenticationException('Invalid or expired refresh token');
        }
    }

    /**
     * Extract token from Authorization header.
     */
    public static function extractFromHeader(): ?string
    {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (preg_match('/^Bearer\s+(.+)$/', $authHeader, $matches)) {
            return $matches[1];
        }
        return null;
    }
}
