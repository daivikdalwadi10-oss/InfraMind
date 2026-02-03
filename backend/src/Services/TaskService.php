<?php

declare(strict_types=1);

namespace InfraMind\Services;

use InfraMind\Repositories\TaskRepository;
use InfraMind\Repositories\AnalysisRepository;
use InfraMind\Repositories\AuditLogRepository;
use InfraMind\Models\Task;
use InfraMind\Models\TaskStatus;
use InfraMind\Exceptions\NotFoundException;
use InfraMind\Exceptions\AuthorizationException;
use InfraMind\Core\Logger;

/**
 * Task service for task management.
 */
class TaskService
{
    private TaskRepository $taskRepository;
    private AuditLogRepository $auditRepository;
    private Logger $logger;

    public function __construct()
    {
        $this->taskRepository = new TaskRepository();
        $this->auditRepository = new AuditLogRepository();
        $this->logger = Logger::getInstance();
    }

    /**
     * Create task (manager only).
     */
    public function createTask(
        string $managerId,
        string $title,
        string $description,
        ?string $assignedTo = null,
    ): Task {
        $taskId = $this->generateUuid();
        $now = date('Y-m-d H:i:s');

        $status = $assignedTo ? TaskStatus::IN_PROGRESS : TaskStatus::OPEN;

        $task = new Task(
            $taskId,
            $title,
            $description,
            $managerId,
            $status,
            $now,
            $now,
            $assignedTo,
        );

        $task = $this->taskRepository->create($task);

        $this->auditRepository->log('Task', $taskId, 'CREATED', $managerId, [
            'title' => $title,
            'assignedTo' => $assignedTo,
        ]);

        $this->logger->info("Task created: $taskId by manager: $managerId");

        return $task;
    }

    /**
     * Get task by ID.
     */
    public function getTask(string $taskId): Task
    {
        $task = $this->taskRepository->findById($taskId);
        if (!$task) {
            throw new NotFoundException('Task not found');
        }
        return $task;
    }

    /**
     * Get tasks for manager.
     */
    public function getTasksForManager(string $managerId, ?string $status = null, int $limit = 50, int $offset = 0): array
    {
        $taskStatus = null;
        if ($status) {
            try {
                $taskStatus = TaskStatus::from($status);
            } catch (\ValueError $e) {
                throw new \InvalidArgumentException('Invalid task status');
            }
        }

        return $this->taskRepository->listForManager($managerId, $taskStatus, $limit, $offset);
    }

    /**
     * Get tasks assigned to employee.
     */
    public function getTasksForEmployee(string $employeeId, int $limit = 50, int $offset = 0): array
    {
        return $this->taskRepository->listAssignedToEmployee($employeeId, $limit, $offset);
    }

    /**
     * Update task status.
     */
    public function updateTaskStatus(string $taskId, string $managerId, string $newStatus): Task
    {
        $task = $this->getTask($taskId);

        // Verify manager owns this task
        if ($task->createdBy !== $managerId) {
            throw new AuthorizationException('You can only update your own tasks');
        }

        try {
            $task->status = TaskStatus::from($newStatus);
        } catch (\ValueError $e) {
            throw new \InvalidArgumentException('Invalid task status');
        }

        $task = $this->taskRepository->update($task);

        $this->auditRepository->log('Task', $taskId, 'STATUS_CHANGED', $managerId, [
            'newStatus' => $newStatus,
        ]);

        return $task;
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
