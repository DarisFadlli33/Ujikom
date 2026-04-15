<?php

namespace App\Models;

/**
 * User Model
 */
class User extends BaseModel
{
    protected $username;
    protected $password;
    protected $roleId;

    public function __construct(
        $username = null,
        $password = null,
        $roleId = null,
        $id = null
    ) {
        $this->username = $username;
        $this->password = $password;
        $this->roleId = $roleId;
        $this->id = $id;
    }

    // Getters
    public function getUsername(): string
    {
        return $this->username;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function getRoleId()
    {
        return $this->roleId;
    }

    // Setters
    public function setUsername(string $username): self
    {
        $this->username = $username;
        return $this;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;
        return $this;
    }

    public function setRoleId($roleId): self
    {
        $this->roleId = $roleId;
        return $this;
    }

    /**
     * Hash password menggunakan bcrypt
     */
    public function hashPassword(): self
    {
        if ($this->password) {
            $this->password = password_hash($this->password, PASSWORD_DEFAULT);
        }
        return $this;
    }

    /**
     * Verify password
     */
    public function verifyPassword(string $plainPassword): bool
    {
        return password_verify($plainPassword, $this->password);
    }

    /**
     * Create user from array
     */
    public static function fromArray(array $data): self
    {
        $user = new self(
            $data['username'] ?? null,
            $data['password'] ?? null,
            $data['role_id'] ?? null,
            $data['id'] ?? null
        );
        
        if (isset($data['created_at'])) {
            $user->setCreatedAt($data['created_at']);
        }
        if (isset($data['updated_at'])) {
            $user->setUpdatedAt($data['updated_at']);
        }
        
        return $user;
    }
}
