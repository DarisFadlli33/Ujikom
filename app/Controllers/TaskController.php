<?php

namespace App\Controllers;

use App\Services\AuthService;
use App\Services\TaskService;

/**
 * TaskController - Handle task operations (CRUD)
 */
class TaskController extends BaseController
{
    private TaskService $taskService;

    public function __construct(TaskService $taskService)
    {
        $this->taskService = $taskService;
    }

    /**
     * Create task (admin only)
     */
    public function add(): void
    {
        AuthService::requireAdmin();

        if (!$this->isPost()) {
            return;
        }

        $title = trim($this->post('title', ''));
        $description = trim($this->post('description', ''));
        $userId = intval($this->post('user_id', 0));
        $deadline = $this->post('deadline', null);

        try {
            $this->taskService->createTask(
                $title,
                $description,
                $userId,
                $_SESSION['user_id'],
                $deadline
            );

            $this->setFlashMessage('Task ditambahkan berhasil', 'success');
            $this->redirect('admin/dashboard.php');
        } catch (\InvalidArgumentException $e) {
            $this->redirect('admin/dashboard.php', [
                'message' => $e->getMessage(),
                'type' => 'error'
            ]);
        }
    }

    /**
     * Edit task (admin only)
     */
    public function editAdmin(): void
    {
        AuthService::requireAdmin();

        if (!$this->isPost()) {
            return;
        }

        $taskId = intval($this->post('task_id', 0));
        $title = trim($this->post('title', ''));
        $description = trim($this->post('description', ''));
        $userId = intval($this->post('user_id', 0));
        $status = trim($this->post('status', 'open'));
        $deadline = $this->post('deadline', null);

        try {
            $this->taskService->updateTask(
                $taskId,
                $title,
                $description,
                $userId,
                $status,
                $deadline
            );

            $this->setFlashMessage('Task diperbarui berhasil', 'success');
            $this->redirect('admin/dashboard.php');
        } catch (\Exception $e) {
            $this->redirect('admin/dashboard.php', [
                'message' => $e->getMessage(),
                'type' => 'error'
            ]);
        }
    }

    /**
     * Delete task by ID (admin only)
     */
    public function deleteAdmin(): void
    {
        AuthService::requireAdmin();

        if (!$this->isPost()) {
            return;
        }

        $taskId = intval($this->post('task_id', 0));

        if (empty($taskId)) {
            $this->redirect('admin/dashboard.php', [
                'message' => 'ID task tidak valid',
                'type' => 'error'
            ]);
            return;
        }

        try {
            $this->taskService->deleteTask($taskId);

            $this->setFlashMessage('Task dihapus berhasil', 'success');
            $this->redirect('admin/dashboard.php');
        } catch (\Exception $e) {
            $this->redirect('admin/dashboard.php', [
                'message' => $e->getMessage(),
                'type' => 'error'
            ]);
        }
    }

    /**
     * Bulk delete tasks (admin only)
     */
    public function bulkDeleteTasks(): void
    {
        AuthService::requireAdmin();

        if (!$this->isPost()) {
            return;
        }

        $taskIds = $this->post('task_ids', []);
        
        if (!is_array($taskIds)) {
            $taskIds = [$taskIds];
        }

        $taskIds = array_map('intval', $taskIds);
        $taskIds = array_filter($taskIds); // Remove zeros

        try {
            if (empty($taskIds)) {
                throw new \InvalidArgumentException('Pilih minimal 1 task');
            }

            $this->taskService->deleteMultipleTasks($taskIds);

            $this->setFlashMessage('Task dihapus berhasil', 'success');
            $this->redirect('admin/dashboard.php');
        } catch (\Exception $e) {
            $this->redirect('admin/dashboard.php', [
                'message' => $e->getMessage(),
                'type' => 'error'
            ]);
        }
    }
}
