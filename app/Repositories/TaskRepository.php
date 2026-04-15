<?php

namespace App\Repositories;

use App\Models\Task;
use PDO;

/**
 * TaskRepository - Database operations untuk Task
 */
class TaskRepository extends BaseRepository
{
    protected string $table = 'tasks';
    protected string $modelClass = Task::class;

    /**
     * Find tasks by user ID
     */
    public function findByUserId($userId, ?string $statusFilter = null, ?string $search = null): array
    {
        $where = ['t.user_id = ?'];
        $params = [$userId];

        if ($search) {
            $where[] = "(t.title LIKE ? OR t.description LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        if ($statusFilter) {
            $where[] = "s.code = ?";
            $params[] = $statusFilter;
        }

        $whereSQL = implode(' AND ', $where);

        $sql = "SELECT t.*, s.code AS status_code, s.label AS status_label,
                CASE WHEN t.created_by != t.user_id THEN 'admin' ELSE 'user' END AS task_source
                FROM {$this->table} t
                JOIN task_statuses s ON t.status_id = s.id
                WHERE $whereSQL
                ORDER BY t.deadline ASC, t.created_at DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $results = [];

        while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $results[] = Task::fromArray($data);
        }

        return $results;
    }

    /**
     * Get all tasks with per-status limit (for admin)
     */
    public function getAllWithPerStatusLimit(int $statusLimit = 3, ?string $search = null, ?string $statusFilter = null): array
    {
        $search_suffix = '';
        $params_list = [];

        if ($search) {
            $search_suffix = " AND (t.title LIKE ? OR t.description LIKE ? OR u.username LIKE ?)";
            $params_list = ["%$search%", "%$search%", "%$search%"];
        }

        if ($statusFilter) {
            $search_suffix .= " AND s.code = ?";
            array_push($params_list, $statusFilter);
        }

        $sql = "(SELECT t.*, u.username AS owner, s.code AS status_code, s.label AS status_label, t.completion_attachment 
                FROM tasks t 
                JOIN users u ON t.user_id=u.id 
                JOIN task_statuses s ON t.status_id=s.id 
                WHERE s.code='open' $search_suffix 
                ORDER BY t.created_at DESC 
                LIMIT $statusLimit)
        UNION ALL
        (SELECT t.*, u.username AS owner, s.code AS status_code, s.label AS status_label, t.completion_attachment 
                FROM tasks t 
                JOIN users u ON t.user_id=u.id 
                JOIN task_statuses s ON t.status_id=s.id 
                WHERE s.code='in_progress' $search_suffix 
                ORDER BY t.created_at DESC 
                LIMIT $statusLimit)
        UNION ALL
        (SELECT t.*, u.username AS owner, s.code AS status_code, s.label AS status_label, t.completion_attachment 
                FROM tasks t 
                JOIN users u ON t.user_id=u.id 
                JOIN task_statuses s ON t.status_id=s.id 
                WHERE s.code='done' $search_suffix 
                ORDER BY t.created_at DESC 
                LIMIT $statusLimit)
        ORDER BY created_at DESC";

        $params = array_merge($params_list, $params_list, $params_list);
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $results = [];

        while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $results[] = Task::fromArray($data);
        }

        return $results;
    }

    /**
     * Create new task
     */
    public function create(Task $task): int
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO {$this->table} (title, description, user_id, created_by, status_id, deadline, created_at) 
             VALUES (?, ?, ?, ?, ?, ?, NOW())"
        );

        $stmt->execute([
            $task->getTitle(),
            $task->getDescription(),
            $task->getUserId(),
            $task->getCreatedBy(),
            $task->getStatusId(),
            $task->getDeadline()
        ]);

        return $this->pdo->lastInsertId();
    }

    /**
     * Update task
     */
    public function update(Task $task): bool
    {
        $stmt = $this->pdo->prepare(
            "UPDATE {$this->table} 
             SET title = ?, description = ?, user_id = ?, status_id = ?, deadline = ?, updated_at = NOW()
             WHERE id = ?"
        );

        return $stmt->execute([
            $task->getTitle(),
            $task->getDescription(),
            $task->getUserId(),
            $task->getStatusId(),
            $task->getDeadline(),
            $task->getId()
        ]);
    }

    /**
     * Count tasks by status
     */
    public function countByStatus(string $statusCode): int
    {
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) FROM {$this->table} t
             JOIN task_statuses s ON t.status_id = s.id
             WHERE s.code = ?"
        );
        $stmt->execute([$statusCode]);
        return $stmt->fetchColumn();
    }

    /**
     * Delete task by ID
     */
    public function delete($id): bool
    {
        // Delete attachments first
        $stmt = $this->pdo->prepare("DELETE FROM attachments WHERE task_id = ?");
        $stmt->execute([$id]);

        // Then delete task
        $stmt = $this->pdo->prepare("DELETE FROM {$this->table} WHERE id = ?");
        return $stmt->execute([$id]);
    }

    /**
     * Bulk delete tasks
     */
    public function deleteMultiple(array $taskIds): bool
    {
        if (empty($taskIds)) {
            return false;
        }

        $placeholders = implode(',', array_fill(0, count($taskIds), '?'));

        // Delete attachments
        $stmt = $this->pdo->prepare("DELETE FROM attachments WHERE task_id IN ($placeholders)");
        $stmt->execute($taskIds);

        // Delete tasks
        $stmt = $this->pdo->prepare("DELETE FROM {$this->table} WHERE id IN ($placeholders)");
        return $stmt->execute($taskIds);
    }

    /**
     * Get total count for pagination
     */
    public function getTotal(): int
    {
        return $this->pdo->query("SELECT COUNT(*) FROM {$this->table}")->fetchColumn();
    }

    /**
     * Get count by user
     */
    public function countByUser($userId): int
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM {$this->table} WHERE user_id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetchColumn();
    }
}
