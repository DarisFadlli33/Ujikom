<?php

namespace App\Repositories;

use App\Models\TaskStatus;
use PDO;

/**
 * TaskStatusRepository - Database operations untuk TaskStatus
 */
class TaskStatusRepository extends BaseRepository
{
    protected string $table = 'task_statuses';
    protected string $modelClass = TaskStatus::class;

    /**
     * Find by code
     */
    public function findByCode(string $code): ?TaskStatus
    {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE code = ?");
        $stmt->execute([$code]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        return $data ? TaskStatus::fromArray($data) : null;
    }

    /**
     * Get ID by code
     */
    public function getIdByCode(string $code): ?int
    {
        $stmt = $this->pdo->prepare("SELECT id FROM {$this->table} WHERE code = ?");
        $stmt->execute([$code]);
        $id = $stmt->fetchColumn();

        return $id ? intval($id) : null;
    }
}
