<?php

namespace App\Models;

use PDO;

/**
 * BaseModel - Base class untuk semua model
 * Menyediakan functionality dasar untuk semua model
 */
abstract class BaseModel
{
    protected static PDO $pdo;
    protected $id;
    protected $createdAt;
    protected $updatedAt;

    /**
     * Set PDO instance untuk semua model
     */
    public static function setPDO(PDO $pdo): void
    {
        static::$pdo = $pdo;
    }

    /**
     * Get PDO instance
     */
    protected static function getPDO(): PDO
    {
        if (!isset(static::$pdo)) {
            throw new \RuntimeException('PDO not initialized. Call BaseModel::setPDO() first.');
        }
        return static::$pdo;
    }

    // Getters
    public function getId()
    {
        return $this->id;
    }

    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    // Setters
    public function setId($id): self
    {
        $this->id = $id;
        return $this;
    }

    public function setCreatedAt($createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function setUpdatedAt($updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    /**
     * Convert model to array
     */
    public function toArray(): array
    {
        $reflection = new \ReflectionClass($this);
        $properties = $reflection->getProperties(\ReflectionProperty::IS_PROTECTED);
        
        $data = [];
        foreach ($properties as $property) {
            $data[$property->getName()] = $property->getValue($this);
        }
        return $data;
    }
}
