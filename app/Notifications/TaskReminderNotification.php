<?php

namespace App\Notifications;

use App\Models\WorkflowTask;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Notifications\Notification;

class TaskReminderNotification extends Notification implements ShouldQueue, ShouldBroadcast
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
            'type' => 'task_reminder',
            'task_id' => $this->task->id,
            'task_title' => $this->task->title,
            'task_type' => $this->task->task_type,
            'priority' => $this->task->priority,
            'due_date' => $this->task->due_date,
            'title' => 'Task Reminder',
            'message' => "Reminder: {$this->task->title} is due soon",
            'link' => "/admin/tasks/{$this->task->id}",
            'created_at' => now()->toISOString(),
        ]);
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $hoursUntilDue = $this->task->daysUntilDue() * 24; // Convert to hours

        return (new MailMessage)
            ->subject("Task Reminder: {$this->task->title}")
            ->greeting("Hello {$notifiable->name},")
            ->line("This is a reminder that you have a task due soon:")
            ->line("**{$this->task->title}**")
            ->when($this->task->description, function ($mail) {
                return $mail->line($this->task->description);
            })
            ->line("**Due Date:** {$this->task->due_date->format('M d, Y g:i A')}")
            ->when($hoursUntilDue > 0, function ($mail) use ($hoursUntilDue) {
                return $mail->line("**Time Remaining:** " . round($hoursUntilDue, 1) . " hours");
            })
            ->action('View Task', url("/admin/tasks/{$this->task->id}"))
            ->line('Please complete this task before the due date to avoid delays.');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'task_reminder',
            'task_id' => $this->task->id,
            'task_title' => $this->task->title,
            'task_type' => $this->task->task_type,
            'priority' => $this->task->priority,
            'due_date' => $this->task->due_date,
            'message' => "Reminder: {$this->task->title} is due soon",
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
        return 'task-reminder';
    }
}
