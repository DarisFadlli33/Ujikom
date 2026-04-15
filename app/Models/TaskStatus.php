<?php

namespace App\Models;

/**
 * TaskStatus Model
 */
class TaskStatus extends BaseModel
{
    protected $code;
    protected $label;
    protected $description;

    public function __construct(
        $code = null,
        $label = null,
        $description = null,
        $id = null
    ) {
        $this->code = $code;
        $this->label = $label;
        $this->description = $description;
        $this->id = $id;
    }

    // Getters
    public function getCode(): string
    {
        return $this->code;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    // Setters
    public function setCode(string $code): self
    {
        $this->code = $code;
        return $this;
    }

    public function setLabel(string $label): self
    {
        $this->label = $label;
        return $this;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    /**
     * Create from array
     */
    public static function fromArray(array $data): self
    {
        return new self(
            $data['code'] ?? null,
            $data['label'] ?? null,
            $data['description'] ?? null,
            $data['id'] ?? null
        );
    }
}
