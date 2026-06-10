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

class TaskOverdueNotification extends Notification implements ShouldQueue, ShouldBroadcast
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
            'type' => 'task_overdue',
            'task_id' => $this->task->id,
            'task_title' => $this->task->title,
            'task_type' => $this->task->task_type,
            'priority' => $this->task->priority,
            'due_date' => $this->task->due_date,
            'days_overdue' => abs($this->task->daysUntilDue()),
            'title' => 'Task Overdue',
            'message' => "OVERDUE: {$this->task->title} is past due",
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
            ->subject("OVERDUE TASK: {$this->task->title}")
            ->greeting("Hello {$notifiable->name},")
            ->line("⚠️ **IMPORTANT: You have an overdue task that requires immediate attention!**")
            ->line("**{$this->task->title}**")
            ->when($this->task->description, function ($mail) {
                return $mail->line($this->task->description);
            })
            ->line("**Original Due Date:** {$this->task->due_date->format('M d, Y g:i A')}")
            ->line("**Days Overdue:** {$daysOverdue} days")
            ->line("**Priority:** " . ucfirst($this->task->priority))
            ->action('View Task', url("/admin/tasks/{$this->task->id}"))
            ->line('This task is now overdue. Please complete it as soon as possible to avoid further delays and potential compliance issues.')
            ->when($this->task->priority === 'urgent', function ($mail) {
                return $mail->line('🚨 **URGENT:** This is a high-priority task that may impact patient care or compliance.');
            });
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'task_overdue',
            'task_id' => $this->task->id,
            'task_title' => $this->task->title,
            'task_type' => $this->task->task_type,
            'priority' => $this->task->priority,
            'due_date' => $this->task->due_date,
            'days_overdue' => abs($this->task->daysUntilDue()),
            'message' => "OVERDUE: {$this->task->title} is past due",
        ];
    }

    /**
     * Get the channels the notification should broadcast on.
     *
     * @return array
     */
    public function broadcastOn(): array
    {
        return [new PrivateChannel('App.User.' . ($this->notifiable?->id ?? 'default'))];
    }

    /**
     * Get the broadcast event name.
     *
     * @return string
     */
    public function broadcastAs(): string
    {
        return 'task-overdue';
    }
}
