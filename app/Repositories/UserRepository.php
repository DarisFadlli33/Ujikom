<?php

namespace App\Repositories;

use App\Models\User;
use PDO;

/**
 * UserRepository - Database operations untuk User
 */
class UserRepository extends BaseRepository
{
    protected string $table = 'users';
    protected string $modelClass = User::class;

    /**
     * Find user by username
     */
    public function findByUsername(string $username): ?User
    {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE username = ?");
        $stmt->execute([$username]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $data ? User::fromArray($data) : null;
    }

    /**
     * Find by role
     */
    public function findByRole($roleId): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE role_id = ?");
        $stmt->execute([$roleId]);
        $results = [];
        
        while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $results[] = User::fromArray($data);
        }
        
        return $results;
    }

    /**
     * Check if username exists
     */
    public function usernameExists(string $username): bool
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM {$this->table} WHERE username = ?");
        $stmt->execute([$username]);
        return $stmt->fetchColumn() > 0;
    }

    /**
     * Create new user
     */
    public function create(User $user): int
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO {$this->table} (username, password, role_id, created_at) 
             VALUES (?, ?, ?, NOW())"
        );
        
        $stmt->execute([
            $user->getUsername(),
            $user->getPassword(),
            $user->getRoleId()
        ]);
        
        return $this->pdo->lastInsertId();
    }

    /**
     * Update user
     */
    public function update(User $user): bool
    {
        $stmt = $this->pdo->prepare(
            "UPDATE {$this->table} 
             SET username = ?, password = ?, role_id = ?, updated_at = NOW()
             WHERE id = ?"
        );
        
        return $stmt->execute([
            $user->getUsername(),
            $user->getPassword(),
            $user->getRoleId(),
            $user->getId()
        ]);
    }

    /**
     * Get user with role info
     */
    public function findByIdWithRole($id): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT u.*, r.name AS role_name, r.id AS role_id
             FROM {$this->table} u
             LEFT JOIN roles r ON u.role_id = r.id
             WHERE u.id = ?"
        );
        $stmt->execute([$id]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Count by role
     */
    public function countByRole($roleId): int
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM {$this->table} WHERE role_id = ?");
        $stmt->execute([$roleId]);
        return $stmt->fetchColumn();
    }
}
