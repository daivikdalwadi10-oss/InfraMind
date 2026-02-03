<?php

declare(strict_types=1);

namespace InfraMind\Core;

/**
 * Password hashing and verification using PHP's password_hash functions.
 */
class PasswordManager
{
    private string|int $hashAlgo = PASSWORD_BCRYPT;
    private array $hashOptions = ['cost' => 12];

    /**
     * Hash a password securely.
     */
    public function hash(string $password): string
    {
        $hash = password_hash($password, $this->hashAlgo, $this->hashOptions);
        if ($hash === false) {
            throw new \RuntimeException('Failed to hash password');
        }
        return $hash;
    }

    /**
     * Verify password against hash.
     */
    public function verify(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    /**
     * Check if password needs rehashing (for security updates).
     */
    public function needsRehash(string $hash): bool
    {
        return password_needs_rehash($hash, $this->hashAlgo, $this->hashOptions);
    }
}
