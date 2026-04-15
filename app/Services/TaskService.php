<?php

namespace App\Services;

use App\Models\Task;
use App\Repositories\TaskRepository;
use App\Repositories\TaskStatusRepository;

/**
 * TaskService - Business logic untuk Task management
 */
class TaskService
{
    private TaskRepository $taskRepository;
    private TaskStatusRepository $statusRepository;

    public function __construct(TaskRepository $taskRepository, TaskStatusRepository $statusRepository)
    {
        $this->taskRepository = $taskRepository;
        $this->statusRepository = $statusRepository;
    }

    /**
     * Create task baru (admin only)
     */
    public function createTask(
        string $title,
        ?string $description,
        int $userId,
        int $createdBy,
        ?string $deadline = null
    ): int {
        if (empty($title)) {
            throw new \InvalidArgumentException('Title tidak boleh kosong');
        }

        if (empty($userId)) {
            throw new \InvalidArgumentException('User harus dipilih');
        }

        $task = new Task($title, $description, $userId, $createdBy);
        
        // Set status ke 'open' (id = 1)
        $statusId = $this->statusRepository->getIdByCode('open');
        $task->setStatusId($statusId);
        $task->setDeadline($deadline);

        return $this->taskRepository->create($task);
    }

    /**
     * Update task (admin only)
     */
    public function updateTask(
        int $taskId,
        string $title,
        ?string $description,
        int $userId,
        string $statusCode,
        ?string $deadline = null
    ): bool {
        if (empty($title)) {
            throw new \InvalidArgumentException('Title tidak boleh kosong');
        }

        $task = $this->taskRepository->findById($taskId);
        if (!$task) {
            throw new \RuntimeException('Task tidak ditemukan');
        }

        // Konversi status code ke ID
        $statusId = $this->statusRepository->getIdByCode($statusCode);
        if (!$statusId) {
            throw new \InvalidArgumentException('Status tidak valid');
        }

        $task->setTitle($title)
            ->setDescription($description)
            ->setUserId($userId)
            ->setStatusId($statusId)
            ->setDeadline($deadline);

        return $this->taskRepository->update($task);
    }

    /**
     * Delete task
     */
    public function deleteTask(int $taskId): bool
    {
        return $this->taskRepository->delete($taskId);
    }

    /**
     * Delete multiple tasks
     */
    public function deleteMultipleTasks(array $taskIds): bool
    {
        if (empty($taskIds)) {
            throw new \InvalidArgumentException('Pilih minimal 1 task');
        }

        return $this->taskRepository->deleteMultiple($taskIds);
    }

    /**
     * Get tasks untuk admin (dengan per-status limit)
     */
    public function getAdminTasks(?string $search = null, ?string $statusFilter = null): array
    {
        return $this->taskRepository->getAllWithPerStatusLimit(3, $search, $statusFilter);
    }

    /**
     * Get tasks untuk user (hanya task miliknya)
     */
    public function getUserTasks(int $userId, ?string $search = null, ?string $statusFilter = null): array
    {
        $tasks = $this->taskRepository->findByUserId($userId, $statusFilter, $search);

        // Filter overdue tasks (hide dari user)
        return array_filter($tasks, function (Task $task) {
            return !$task->isOverdue();
        });
    }

    /**
     * Get task statistics
     */
    public function getStatistics(): array
    {
        return [
            'total_open' => $this->taskRepository->countByStatus('open'),
            'total_progress' => $this->taskRepository->countByStatus('in_progress'),
            'total_completed' => $this->taskRepository->countByStatus('done'),
            'total_tasks' => $this->taskRepository->getTotal()
        ];
    }

    /**
     * Get tasks for pagination
     */
    public function getTasksForPage(int $page, int $perPage, ?string $search = null, ?string $statusFilter = null): array
    {
        $tasks = $this->taskRepository->getAllWithPerStatusLimit(3, $search, $statusFilter);
        $offset = ($page - 1) * $perPage;

        return [
            'tasks' => array_slice($tasks, $offset, $perPage),
            'total' => count($tasks),
            'pages' => ceil(count($tasks) / $perPage),
            'current_page' => $page
        ];
    }
}
