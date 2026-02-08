<?php

declare(strict_types=1);

namespace InfraMind\Controllers;

use InfraMind\Core\Request;
use InfraMind\Core\Response;
use InfraMind\Repositories\UserRepository;
use InfraMind\Repositories\TeamRepository;
use InfraMind\Core\Logger;

class UserController
{
    private UserRepository $userRepository;
    private TeamRepository $teamRepository;
    private Logger $logger;

    public function __construct()
    {
        $this->userRepository = new UserRepository();
        $this->teamRepository = new TeamRepository();
        $this->logger = Logger::getInstance();
    }

    /**
     * List all users (admin only)
     */
    public function list(Request $request): Response
    {
        try {
            $user = $request->getUser();

            if (!$user || !in_array($user->role ?? null, ['OWNER', 'MANAGER'], true)) {
                return (new Response(403))->error('Insufficient permissions');
            }

            $limit = min((int) $request->getQuery('limit', 100), 200);
            $offset = (int) $request->getQuery('offset', 0);

            if ($user->role === 'OWNER') {
                $users = $this->userRepository->listAllEmployeesWithWorkload($limit, $offset);
            } else {
                $users = $this->userRepository->listManagedEmployeesWithWorkload($user->sub, $limit, $offset);
            }

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
            if ($currentUser->sub !== $userId &&
                !in_array($currentUser->role ?? null, ['OWNER', 'MANAGER'], true)) {
                return (new Response(403))->error('Insufficient permissions');
            }

            if ($currentUser->role === 'MANAGER' && $currentUser->sub !== $userId) {
                if (!$this->teamRepository->isManagerOfEmployee($currentUser->sub, $userId)) {
                    return (new Response(403))->error('Insufficient permissions');
                }
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
