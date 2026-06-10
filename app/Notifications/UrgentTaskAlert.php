<?php

namespace App\Notifications;

use App\Models\WorkflowTask;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Broadcasting\PrivateChannel;

class UrgentTaskAlert extends Notification implements ShouldQueue, ShouldBroadcast
{
    use Queueable;

    protected WorkflowTask $task;

    /**
     * Create a new notification instance.
     */
    public function __construct(WorkflowTask $task)
    {
        $this->task = $task;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database', 'broadcast'];
    }

    /**
     * Get the broadcast representation of the notification.
     */
    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'id' => $this->id,
            'type' => 'urgent_task_escalation',
            'task_id' => $this->task->id,
            'task_title' => $this->task->title,
            'task_type' => $this->task->task_type,
            'assigned_to' => $this->task->assigned_to,
            'due_date' => $this->task->due_date,
            'days_overdue' => abs($this->task->daysUntilDue()),
            'title' => 'URGENT: Task Escalated',
            'message' => "URGENT ESCALATION: {$this->task->title} is critically overdue",
            'link' => "/admin/tasks/{$this->task->id}",
            'created_at' => now()->toISOString(),
        ]);
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $daysOverdue = abs($this->task->daysUntilDue());

        return (new MailMessage)
            ->subject("🚨 URGENT TASK ESCALATION: {$this->task->title}")
            ->greeting("Hello {$notifiable->name},")
            ->line("🚨 **CRITICAL ESCALATION: An urgent task is severely overdue and requires immediate supervisory attention!**")
            ->line("**Task:** {$this->task->title}")
            ->when($this->task->description, function ($mail) {
                return $mail->line("**Description:** {$this->task->description}");
            })
            ->line("**Assigned To:** " . ($this->task->assignedUser ? $this->task->assignedUser->name : 'Unassigned'))
            ->line("**Original Due Date:** {$this->task->due_date->format('M d, Y g:i A')}")
            ->line("**Days Overdue:** {$daysOverdue} days")
            ->line("**Task Type:** " . ucfirst(str_replace('_', ' ', $this->task->task_type)))
            ->action('View Task Details', url("/admin/tasks/{$this->task->id}"))
            ->line('This urgent task has been escalated to you for immediate intervention. Please assess the situation and take appropriate action to resolve this critical issue.')
            ->when($this->task->taskable, function ($mail) {
                $relatedInfo = $this->getRelatedEntityInfo();
                if ($relatedInfo) {
                    return $mail->line("**Related:** {$relatedInfo}");
                }
            })
            ->line('Failure to address urgent tasks can result in compliance violations, delayed patient care, or financial penalties.');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'urgent_task_escalation',
            'task_id' => $this->task->id,
            'task_title' => $this->task->title,
            'task_type' => $this->task->task_type,
            'assigned_to' => $this->task->assigned_to,
            'due_date' => $this->task->due_date,
            'days_overdue' => abs($this->task->daysUntilDue()),
            'related_entity' => $this->getRelatedEntityInfo(),
            'message' => "URGENT ESCALATION: {$this->task->title} is critically overdue",
        ];
    }

    /**
     * Get information about the related entity
     */
    protected function getRelatedEntityInfo(): ?string
    {
        if (!$this->task->taskable) {
            return null;
        }

        return match ($this->task->taskable_type) {
            'App\Models\Claim' => "Claim #{$this->task->taskable->claim_id}",
            'App\Models\AppealWorkflow' => "Appeal for Claim #{$this->task->taskable->claim->claim_id}",
            default => null,
        };
    }

    /**
     * Get the channels the notification should broadcast on.
     *
     * @return array
     */
    public function broadcastOn(): array
    {
        // Use the task's assigned_to directly since notifiable may be null during queue processing
        $userId = $this->task->assigned_to ?? $this->notifiable?->id ?? 'default';
        return [new PrivateChannel('App.User.' . $userId)];
    }

    /**
     * Get the broadcast event name.
     *
     * @return string
     */
    public function broadcastAs(): string
    {
        return 'urgent-task-escalation';
    }
}
