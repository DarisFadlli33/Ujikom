<?php

namespace App\Repositories;

use App\Models\Role;
use PDO;

/**
 * RoleRepository - Database operations untuk Role
 */
class RoleRepository extends BaseRepository
{
    protected string $table = 'roles';
    protected string $modelClass = Role::class;

    /**
     * Find role by name
     */
    public function findByName(string $name): ?Role
    {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE name = ?");
        $stmt->execute([$name]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        return $data ? Role::fromArray($data) : null;
    }

    /**
     * Get role ID by name
     */
    public function getIdByName(string $name): ?int
    {
        $stmt = $this->pdo->prepare("SELECT id FROM {$this->table} WHERE name = ?");
        $stmt->execute([$name]);
        $id = $stmt->fetchColumn();

        return $id ? intval($id) : null;
    }
}
