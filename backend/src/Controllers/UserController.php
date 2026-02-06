<?php

declare(strict_types=1);

namespace InfraMind\Controllers;

use InfraMind\Core\Request;
use InfraMind\Core\Response;
use InfraMind\Repositories\UserRepository;
use InfraMind\Core\Logger;

class UserController
{
    private UserRepository $userRepository;
    private Logger $logger;

    public function __construct()
    {
        $this->userRepository = new UserRepository();
        $this->logger = Logger::getInstance();
    }

    /**
     * List all users (admin only)
     */
    public function list(Request $request): Response
    {
        try {
            $user = $request->getUser();
            
            if (!$user || !in_array($user['role'], ['OWNER', 'MANAGER'])) {
                return (new Response(403))->error('Insufficient permissions');
            }

            $users = $this->userRepository->findAll();
            
            // Remove sensitive data
            $users = array_map(function($user) {
                unset($user['password']);
                return $user;
            }, $users);

            return (new Response(200))->success($users);
        } catch (\Exception $e) {
            $this->logger->error('Failed to list users: ' . $e->getMessage());
            return (new Response(500))->error('Failed to list users');
        }
    }

    /**
     * Get user by ID
     */
    public function get(Request $request, array $params): Response
    {
        try {
            $currentUser = $request->getUser();
            $userId = $params['id'] ?? '';

            if (!$currentUser) {
                return (new Response(401))->error('Unauthorized');
            }

            // Users can only see their own data unless they're admin
            if ($currentUser['id'] !== $userId && 
                !in_array($currentUser['role'], ['OWNER', 'MANAGER'])) {
                return (new Response(403))->error('Insufficient permissions');
            }

            $user = $this->userRepository->findById($userId);
            
            if (!$user) {
                return (new Response(404))->error('User not found');
            }

            unset($user['password']);

            return (new Response(200))->success($user);
        } catch (\Exception $e) {
            $this->logger->error('Failed to get user: ' . $e->getMessage());
            return (new Response(500))->error('Failed to get user');
        }
    }
}
