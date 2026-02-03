<?php

declare(strict_types=1);

namespace InfraMind\Repositories;

use InfraMind\Core\Database;
use InfraMind\Models\Task;
use InfraMind\Models\TaskStatus;

/**
 * Task repository for database operations.
 */
class TaskRepository
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Create new task.
     */
    public function create(Task $task): Task
    {
        $sql = 'INSERT INTO tasks (id, title, description, assigned_to, created_by, status, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)';

        $this->db->execute($sql, [
            $task->id,
            $task->title,
            $task->description,
            $task->assignedTo,
            $task->createdBy,
            $task->status->value,
            $task->createdAt,
            $task->updatedAt,
        ]);

        return $task;
    }

    /**
     * Find task by ID.
     */
    public function findById(string $id): ?Task
    {
        $sql = 'SELECT * FROM tasks WHERE id = ?';
        $row = $this->db->fetchOne($sql, [$id]);

        return $row ? $this->mapRowToTask($row) : null;
    }

    /**
     * Update task.
     */
    public function update(Task $task): Task
    {
        $sql = "UPDATE tasks SET title = ?, description = ?, assigned_to = ?, status = ?, updated_at = datetime(\"now\")
                WHERE id = ?";

        $this->db->execute($sql, [
            $task->title,
            $task->description,
            $task->assignedTo,
            $task->status->value,
            $task->id,
        ]);

        return $task;
    }

    /**
     * List tasks for manager.
     */
    public function listForManager(string $managerId, ?TaskStatus $status = null, int $limit = 50, int $offset = 0): array
    {
        $sql = 'SELECT * FROM tasks WHERE created_by = ?';
        $params = [$managerId];

        if ($status) {
            $sql .= ' AND status = ?';
            $params[] = $status->value;
        }

        $sql .= ' ORDER BY created_at DESC LIMIT ? OFFSET ?';
        $params[] = $limit;
        $params[] = $offset;

        $rows = $this->db->fetchAll($sql, $params);
        return array_map([$this, 'mapRowToTask'], $rows);
    }

    /**
     * List tasks assigned to employee.
     */
    public function listAssignedToEmployee(string $employeeId, int $limit = 50, int $offset = 0): array
    {
        $sql = 'SELECT * FROM tasks WHERE assigned_to = ?
                ORDER BY created_at DESC LIMIT ? OFFSET ?';

        $rows = $this->db->fetchAll($sql, [$employeeId, $limit, $offset]);
        return array_map([$this, 'mapRowToTask'], $rows);
    }

    /**
     * Map database row to Task model.
     */
    private function mapRowToTask(array $row): Task
    {
        return new Task(
            $row['id'],
            $row['title'],
            $row['description'],
            $row['created_by'],
            TaskStatus::from($row['status']),
            $row['created_at'],
            $row['updated_at'],
            $row['assigned_to'],
        );
    }
}
