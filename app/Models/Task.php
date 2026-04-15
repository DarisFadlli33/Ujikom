<?php

namespace App\Models;

/**
 * Task Model
 */
class Task extends BaseModel
{
    protected $title;
    protected $description;
    protected $userId;
    protected $createdBy;
    protected $statusId;
    protected $deadline;
    protected $completionAttachment;

    // Related data (optional)
    protected $owner = null;
    protected $statusCode = null;
    protected $statusLabel = null;
    protected $taskSource = null;

    public function __construct(
        $title = null,
        $description = null,
        $userId = null,
        $createdBy = null,
        $statusId = null,
        $deadline = null,
        $id = null
    ) {
        $this->title = $title;
        $this->description = $description;
        $this->userId = $userId;
        $this->createdBy = $createdBy;
        $this->statusId = $statusId;
        $this->deadline = $deadline;
        $this->id = $id;
    }

    // Getters
    public function getTitle(): string
    {
        return $this->title;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getUserId()
    {
        return $this->userId;
    }

    public function getCreatedBy()
    {
        return $this->createdBy;
    }

    public function getStatusId()
    {
        return $this->statusId;
    }

    public function getDeadline(): ?string
    {
        return $this->deadline;
    }

    public function getCompletionAttachment(): ?string
    {
        return $this->completionAttachment;
    }

    public function getOwner(): ?string
    {
        return $this->owner;
    }

    public function getStatusCode(): ?string
    {
        return $this->statusCode;
    }

    public function getStatusLabel(): ?string
    {
        return $this->statusLabel;
    }

    public function getTaskSource(): ?string
    {
        return $this->taskSource;
    }

    // Setters
    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function setUserId($userId): self
    {
        $this->userId = $userId;
        return $this;
    }

    public function setCreatedBy($createdBy): self
    {
        $this->createdBy = $createdBy;
        return $this;
    }

    public function setStatusId($statusId): self
    {
        $this->statusId = $statusId;
        return $this;
    }

    public function setDeadline(?string $deadline): self
    {
        $this->deadline = $deadline;
        return $this;
    }

    public function setCompletionAttachment(?string $attachment): self
    {
        $this->completionAttachment = $attachment;
        return $this;
    }

    public function setOwner(?string $owner): self
    {
        $this->owner = $owner;
        return $this;
    }

    public function setStatusCode(?string $code): self
    {
        $this->statusCode = $code;
        return $this;
    }

    public function setStatusLabel(?string $label): self
    {
        $this->statusLabel = $label;
        return $this;
    }

    public function setTaskSource(?string $source): self
    {
        $this->taskSource = $source;
        return $this;
    }

    /**
     * Check if task is overdue
     */
    public function isOverdue(): bool
    {
        if (!$this->deadline || $this->statusCode === 'done') {
            return false;
        }

        $now = new \DateTime();
        $deadline = new \DateTime($this->deadline);
        return $deadline < $now;
    }

    /**
     * Check if task is created by admin for this user
     */
    public function isFromAdmin(): bool
    {
        return $this->taskSource === 'admin';
    }

    /**
     * Create from array
     */
    public static function fromArray(array $data): self
    {
        $task = new self(
            $data['title'] ?? null,
            $data['description'] ?? null,
            $data['user_id'] ?? null,
            $data['created_by'] ?? null,
            $data['status_id'] ?? null,
            $data['deadline'] ?? null,
            $data['id'] ?? null
        );

        if (isset($data['completion_attachment'])) {
            $task->setCompletionAttachment($data['completion_attachment']);
        }
        if (isset($data['owner'])) {
            $task->setOwner($data['owner']);
        }
        if (isset($data['status_code'])) {
            $task->setStatusCode($data['status_code']);
        }
        if (isset($data['status_label'])) {
            $task->setStatusLabel($data['status_label']);
        }
        if (isset($data['task_source'])) {
            $task->setTaskSource($data['task_source']);
        }
        if (isset($data['created_at'])) {
            $task->setCreatedAt($data['created_at']);
        }
        if (isset($data['updated_at'])) {
            $task->setUpdatedAt($data['updated_at']);
        }

        return $task;
    }
}
