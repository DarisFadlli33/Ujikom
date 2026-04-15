<?php

namespace App\Models;

/**
 * Role Model
 */
class Role extends BaseModel
{
    protected $name;
    protected $description;

    public function __construct(
        $name = null,
        $description = null,
        $id = null
    ) {
        $this->name = $name;
        $this->description = $description;
        $this->id = $id;
    }

    // Getters
    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    // Setters
    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    /**
     * Check if role is admin
     */
    public function isAdmin(): bool
    {
        return strtolower($this->name) === 'admin';
    }

    /**
     * Create role from array
     */
    public static function fromArray(array $data): self
    {
        return new self(
            $data['name'] ?? null,
            $data['description'] ?? null,
            $data['id'] ?? null
        );
    }
}
