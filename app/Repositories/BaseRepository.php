<?php

namespace App\Repositories;

use App\Models\BaseModel;
use PDO;

/**
 * BaseRepository - Base class untuk semua repository
 */
abstract class BaseRepository
{
    protected PDO $pdo;
    protected string $table;
    protected string $modelClass;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Find by ID
     */
    public function findById($id)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE id = ?");
        $stmt->execute([$id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $data ? $this->modelClass::fromArray($data) : null;
    }

    /**
     * Get all records
     */
    public function getAll(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM {$this->table}");
        $results = [];
        
        while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $results[] = $this->modelClass::fromArray($data);
        }
        
        return $results;
    }

    /**
     * Count all records
     */
    public function count(): int
    {
        return $this->pdo->query("SELECT COUNT(*) FROM {$this->table}")->fetchColumn();
    }

    /**
     * Delete by ID
     */
    public function delete($id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM {$this->table} WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
