<?php

declare(strict_types=1);

namespace InfraMind\Controllers;

use InfraMind\Core\Request;
use InfraMind\Core\Response;
use InfraMind\Services\TaskService;
use InfraMind\Validators\TaskValidator;
use InfraMind\Exceptions\ValidationException;
use InfraMind\Core\Logger;

/**
 * Task controller.
 */
class TaskController
{
    private TaskService $taskService;
    private Logger $logger;

    public function __construct()
    {
        $this->taskService = new TaskService();
        $this->logger = Logger::getInstance();
    }

    /**
     * POST /tasks
     */
    public function create(Request $request): Response
    {
        try {
            $user = $request->getUser();
            if ($user->role !== 'MANAGER') {
                return (new Response(403))->error('Only managers can create tasks');
            }

            $data = $request->getAll();
            TaskValidator::validateCreate($data);

            $task = $this->taskService->createTask(
                $user->sub,
                $data['title'],
                $data['description'],
                $data['assignedTo'] ?? null,
            );

            return (new Response(201))->success($task->toArray(), 'Task created');
        } catch (ValidationException $e) {
            return (new Response(422))->error('Validation failed', 422, $e->getErrors());
        } catch (\Exception $e) {
            $this->logger->error('Task creation error: ' . $e->getMessage());
            return (new Response(400))->error($e->getMessage());
        }
    }

    /**
     * GET /tasks/:id
     */
    public function get(Request $request, string $id): Response
    {
        try {
            $user = $request->getUser();
            if (!$user) {
                return (new Response(401))->error('Not authenticated');
            }

            if ($user->role === 'OWNER') {
                return (new Response(403))->error('Owners cannot view tasks');
            }

            $task = $this->taskService->getTask($id);
            if ($user->role === 'EMPLOYEE' && $task->assignedTo !== $user->sub) {
                return (new Response(403))->error('You can only view assigned tasks');
            }

            if ($user->role === 'MANAGER' && $task->createdBy !== $user->sub) {
                return (new Response(403))->error('You can only view tasks you created');
            }
            return (new Response(200))->success($task->toArray());
        } catch (\Exception $e) {
            return (new Response(404))->error($e->getMessage());
        }
    }

    /**
     * GET /tasks
     */
    public function list(Request $request): Response
    {
        try {
            $user = $request->getUser();

            $limit = min((int) $request->getQuery('limit', 50), 100);
            $offset = (int) $request->getQuery('offset', 0);
            $status = $request->getQuery('status');

            if ($user->role === 'MANAGER') {
                $tasks = $this->taskService->getTasksForManager(
                    $user->sub,
                    $status,
                    $limit,
                    $offset,
                );
            } elseif ($user->role === 'EMPLOYEE') {
                $tasks = $this->taskService->getTasksForEmployee($user->sub, $limit, $offset);
            } else {
                return (new Response(403))->error('Insufficient permissions');
            }

            return (new Response(200))->success(array_map(fn($t) => $t->toArray(), $tasks));
        } catch (\Exception $e) {
            $this->logger->error('Task list error: ' . $e->getMessage());
            return (new Response(400))->error($e->getMessage());
        }
    }

    /**
     * PUT /tasks/:id/status
     */
    public function updateStatus(Request $request, string $id): Response
    {
        try {
            $user = $request->getUser();
            if ($user->role !== 'MANAGER') {
                return (new Response(403))->error('Only managers can update task status');
            }

            $data = $request->getAll();
            if (!isset($data['status'])) {
                return (new Response(400))->error('status required');
            }

            $task = $this->taskService->updateTaskStatus($id, $user->sub, $data['status']);
            return (new Response(200))->success($task->toArray());
        } catch (\Exception $e) {
            $this->logger->error('Task status update error: ' . $e->getMessage());
            return (new Response(400))->error($e->getMessage());
        }
    }
}
