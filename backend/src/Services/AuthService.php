<?php

declare(strict_types=1);

namespace InfraMind\Services;

use InfraMind\Core\PasswordManager;
use InfraMind\Core\TokenManager;
use InfraMind\Core\Logger;
use InfraMind\Repositories\UserRepository;
use InfraMind\Repositories\AuditLogRepository;
use InfraMind\Models\User;
use InfraMind\Models\UserRole;
use InfraMind\Exceptions\AuthenticationException;
use InfraMind\Exceptions\ConflictException;

/**
 * Authentication service for user signup, login, and token management.
 */
class AuthService
{
    private UserRepository $userRepository;
    private PasswordManager $passwordManager;
    private TokenManager $tokenManager;
    private Logger $logger;
    private AuditLogRepository $auditRepository;

    public function __construct()
    {
        $this->userRepository = new UserRepository();
        $this->passwordManager = new PasswordManager();
        $this->tokenManager = new TokenManager();
        $this->logger = Logger::getInstance();
        $this->auditRepository = new AuditLogRepository();
    }

    /**
     * Sign up new user with email, password, and role.
     *
     * @throws ConflictException if email already exists
     */
    public function signup(string $email, string $password, string $displayName, string $role): User
    {
        $email = strtolower(trim($email));
        $role = strtoupper(trim($role));

        if (in_array($role, ['DEVELOPER', 'SYSTEM_ADMIN'], true)) {
            throw new AuthenticationException('Role not permitted for signup');
        }

        // Check if user already exists
        $existing = $this->userRepository->findByEmail($email);
        if ($existing) {
            $this->logger->warning("Signup attempt with existing email: $email");
            throw new ConflictException('Email already registered');
        }

        // Hash password
        $passwordHash = $this->passwordManager->hash($password);

        // Create user
        $userId = $this->generateUuid();
        $now = date('Y-m-d H:i:s');

        $user = new User(
            $userId,
            $email,
            $passwordHash,
            UserRole::from($role),
            $displayName,
            $now,
            null,
        );

        $user = $this->userRepository->create($user);

        $this->logger->info("User signed up: $userId ($email) with role: $role");
        $this->auditRepository->log('AUTH', $userId, 'SIGNUP', $userId, ['email' => $email, 'role' => $role]);

        return $user;
    }

    /**
     * Login user and return access + refresh tokens.
     *
     * @throws AuthenticationException if credentials invalid
     */
    public function login(string $email, string $password): array
    {
        $email = strtolower(trim($email));

        // Find user
        $user = $this->userRepository->findByEmail($email);
        if (!$user) {
            $this->logger->warning("Login attempt with non-existent email: $email");
            $this->auditRepository->log('AUTH', $email, 'LOGIN_FAILED', 'SYSTEM', ['reason' => 'User not found']);
            throw new AuthenticationException('Invalid credentials');
        }

        // Verify password
        if (!$this->passwordManager->verify($password, $user->passwordHash)) {
            $this->logger->warning("Login attempt with wrong password for: $email");
            $this->auditRepository->log('AUTH', $user->id, 'LOGIN_FAILED', 'SYSTEM', ['reason' => 'Invalid password']);
            throw new AuthenticationException('Invalid credentials');
        }

        if (!$user->isActive) {
            $this->logger->warning("Login attempt with inactive user: $email");
            $this->auditRepository->log('AUTH', $user->id, 'LOGIN_FAILED', 'SYSTEM', ['reason' => 'Inactive account']);
            throw new AuthenticationException('Account is inactive');
        }

        // Record login
        $this->userRepository->recordLogin($user->id);

        // Generate tokens
        $accessToken = $this->tokenManager->generateAccessToken(
            $user->id,
            $user->email,
            $user->role->value,
        );

        $refreshToken = $this->tokenManager->generateRefreshToken($user->id);

        $this->logger->info("User logged in: {$user->id} ({$email})");
        $this->auditRepository->log('AUTH', $user->id, 'LOGIN_SUCCESS', $user->id, ['email' => $email]);

        return [
            'accessToken' => $accessToken,
            'refreshToken' => $refreshToken,
            'user' => $user->toArray(),
        ];
    }

    /**
     * Refresh access token using refresh token.
     *
     * @throws AuthenticationException if refresh token invalid
     */
    public function refreshToken(string $refreshToken): array
    {
        $decoded = $this->tokenManager->verifyRefreshToken($refreshToken);

        // Get fresh user data
        $user = $this->userRepository->findById($decoded->sub);
        if (!$user || !$user->isActive) {
            throw new AuthenticationException('User not found or inactive');
        }

        // Generate new access token
        $newAccessToken = $this->tokenManager->generateAccessToken(
            $user->id,
            $user->email,
            $user->role->value,
        );

        return [
            'accessToken' => $newAccessToken,
            'refreshToken' => $refreshToken, // Refresh token is still valid
        ];
    }

    /**
     * Verify access token is valid.
     */
    public function verifyToken(string $token): object
    {
        return $this->tokenManager->verifyAccessToken($token);
    }

    /**
     * Get user from token.
     */
    public function getUserFromToken(string $token): ?User
    {
        try {
            $decoded = $this->verifyToken($token);
            return $this->userRepository->findById($decoded->sub);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Validate user has required role.
     */
    public function requireRole(string $userId, string $role): User
    {
        $user = $this->userRepository->findById($userId);
        if (!$user) {
            throw new AuthenticationException('User not found');
        }

        if ($user->role->value !== $role) {
            $this->logger->warning("Unauthorized action attempt. User: $userId, Required role: $role");
            throw new AuthenticationException('Insufficient permissions');
        }

        return $user;
    }

    /**
     * Validate user has at least required role (supports hierarchy).
     */
    public function requireAtLeastRole(string $userId, array $roles): User
    {
        $user = $this->userRepository->findById($userId);
        if (!$user) {
            throw new AuthenticationException('User not found');
        }

        if (!in_array($user->role->value, $roles, true)) {
            $this->logger->warning("Unauthorized action attempt. User: $userId, Required roles: " . implode(',', $roles));
            throw new AuthenticationException('Insufficient permissions');
        }

        return $user;
    }

    /**
     * Generate UUID v4.
     */
    private function generateUuid(): string
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
}
