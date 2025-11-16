<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class WorkflowTask extends Model
{
    use HasFactory;

    protected $fillable = [
        'task_type',
        'taskable_type',
        'taskable_id',
        'title',
        'description',
        'task_data',
        'priority',
        'status',
        'assigned_to',
        'due_date',
        'completed_at',
        'reminders_sent',
    ];

    protected $casts = [
        'task_data' => 'array',
        'due_date' => 'datetime',
        'completed_at' => 'datetime',
        'reminders_sent' => 'array',
    ];

    /**
     * Get the parent taskable model (claim, appeal_workflow, etc.).
     */
    public function taskable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the user assigned to this task.
     */
    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Scope for pending tasks
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for in progress tasks
     */
    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    /**
     * Scope for overdue tasks
     */
    public function scopeOverdue($query)
    {
        return $query->where('due_date', '<', now())
                    ->whereIn('status', ['pending', 'in_progress']);
    }

    /**
     * Scope for tasks by type
     */
    public function scopeByType($query, $type)
    {
        return $query->where('task_type', $type);
    }

    /**
     * Scope for high priority tasks
     */
    public function scopeHighPriority($query)
    {
        return $query->whereIn('priority', ['high', 'urgent']);
    }

    /**
     * Scope for tasks assigned to user
     */
    public function scopeAssignedTo($query, $userId)
    {
        return $query->where('assigned_to', $userId);
    }

    /**
     * Check if task is overdue
     */
    public function isOverdue(): bool
    {
        return $this->due_date && $this->due_date->isPast() &&
               in_array($this->status, ['pending', 'in_progress']);
    }

    /**
     * Mark task as completed
     */
    public function markCompleted(): void
    {
        $this->status = 'completed';
        $this->completed_at = now();
        $this->save();
    }

    /**
     * Mark task as in progress
     */
    public function markInProgress(): void
    {
        $this->status = 'in_progress';
        $this->save();
    }

    /**
     * Add reminder sent timestamp
     */
    public function addReminderSent(): void
    {
        $reminders = $this->reminders_sent ?? [];
        $reminders[] = now();
        $this->reminders_sent = $reminders;
        $this->save();
    }

    /**
     * Get days until due
     */
    public function daysUntilDue(): ?int
    {
        return $this->due_date ? now()->diffInDays($this->due_date, false) : null;
    }
}
