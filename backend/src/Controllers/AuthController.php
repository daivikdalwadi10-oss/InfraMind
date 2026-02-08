<?php

declare(strict_types=1);

namespace InfraMind\Controllers;

use InfraMind\Core\Request;
use InfraMind\Core\Response;
use InfraMind\Services\AuthService;
use InfraMind\Repositories\UserRepository;
use InfraMind\Validators\SignupValidator;
use InfraMind\Validators\LoginValidator;
use InfraMind\Exceptions\ValidationException;
use InfraMind\Core\Logger;

/**
 * Authentication controller.
 */
class AuthController
{
    private AuthService $authService;
    private Logger $logger;

    public function __construct()
    {
        $this->authService = new AuthService();
        $this->logger = Logger::getInstance();
    }

    /**
     * POST /auth/signup
     */
    public function signup(Request $request): Response
    {
        try {
            $data = $request->getAll();

            // Validate input
            SignupValidator::validate($data);

            // Create user
            $user = $this->authService->signup(
                $data['email'],
                $data['password'],
                $data['displayName'],
                $data['role'],
            );

            return (new Response(201))->success($user->toArray(), 'User created successfully');
        } catch (ValidationException $e) {
            return (new Response(422))->error('Validation failed', 422, $e->getErrors());
        } catch (\Exception $e) {
            $this->logger->error('Signup error: ' . $e->getMessage());
            return (new Response(400))->error($e->getMessage());
        }
    }

    /**
     * POST /auth/login
     */
    public function login(Request $request): Response
    {
        try {
            $data = $request->getAll();

            // Validate input
            LoginValidator::validate($data);

            // Login user
            $result = $this->authService->login($data['email'], $data['password']);

            return (new Response(200))->success($result, 'Login successful');
        } catch (ValidationException $e) {
            return (new Response(422))->error('Validation failed', 422, $e->getErrors());
        } catch (\Exception $e) {
            $this->logger->error('Login error: ' . $e->getMessage());
            return (new Response(401))->error($e->getMessage());
        }
    }

    /**
     * POST /auth/refresh
     */
    public function refreshToken(Request $request): Response
    {
        try {
            $data = $request->getAll();

            if (!isset($data['refreshToken'])) {
                return (new Response(400))->error('refreshToken required');
            }

            $result = $this->authService->refreshToken($data['refreshToken']);
            return (new Response(200))->success($result, 'Token refreshed');
        } catch (\Exception $e) {
            $this->logger->error('Token refresh error: ' . $e->getMessage());
            return (new Response(401))->error($e->getMessage());
        }
    }

    /**
     * GET /auth/me
     */
    public function getCurrentUser(Request $request): Response
    {
        try {
            $tokenUser = $request->getUser();
            if (!$tokenUser) {
                return (new Response(401))->error('Not authenticated');
            }

            $userRepository = new UserRepository();
            $user = $userRepository->findById($tokenUser->sub);
            if (!$user) {
                return (new Response(401))->error('User not found');
            }

            return (new Response(200))->success([
                'user' => $user->toArray(),
            ]);
        } catch (\Exception $e) {
            return (new Response(401))->error('Authentication failed');
        }
    }
}
