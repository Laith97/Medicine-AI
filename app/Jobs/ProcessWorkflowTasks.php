<?php

namespace App\Jobs;

use App\Models\WorkflowTask;
use App\Notifications\TaskReminderNotification;
use App\Notifications\TaskOverdueNotification;
use App\Notifications\UrgentTaskAlert;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class ProcessWorkflowTasks implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Starting workflow tasks processing');

        try {
            // Process overdue tasks
            $this->processOverdueTasks();

            // Send reminders for upcoming due tasks
            $this->sendTaskReminders();

            // Escalate urgent overdue tasks
            $this->escalateUrgentTasks();

            Log::info('Workflow tasks processing completed');

        } catch (\Exception $e) {
            Log::error('Workflow tasks processing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    /**
     * Process overdue tasks
     */
    protected function processOverdueTasks(): void
    {
        $overdueTasks = WorkflowTask::overdue()
            ->where('status', '!=', 'completed')
            ->get();

        foreach ($overdueTasks as $task) {
            try {
                // Mark as overdue in task data
                $taskData = $task->task_data ?? [];
                $taskData['overdue_since'] = now();
                $task->task_data = $taskData;
                $task->save();

                // Send overdue notification
                if ($task->assignedUser) {
                    $task->assignedUser->notify(new TaskOverdueNotification($task));
                }

                Log::warning('Task marked as overdue', [
                    'task_id' => $task->id,
                    'assigned_to' => $task->assigned_to,
                    'due_date' => $task->due_date
                ]);

            } catch (\Exception $e) {
                Log::error('Failed to process overdue task', [
                    'task_id' => $task->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        Log::info('Processed overdue tasks', ['count' => $overdueTasks->count()]);
    }

    /**
     * Send reminders for upcoming due tasks
     */
    protected function sendTaskReminders(): void
    {
        // Tasks due in 24 hours
        $tasksDueSoon = WorkflowTask::where('status', '!=', 'completed')
            ->where('due_date', '>', now())
            ->where('due_date', '<=', now()->addHours(24))
            ->where(function ($query) {
                $query->whereNull('reminders_sent')
                      ->orWhereRaw("JSON_LENGTH(reminders_sent) < 2"); // Max 2 reminders
            })
            ->get();

        foreach ($tasksDueSoon as $task) {
            try {
                if ($this->shouldSendReminder($task)) {
                    if ($task->assignedUser) {
                        $task->assignedUser->notify(new TaskReminderNotification($task));
                        $task->addReminderSent();
                    }
                }
            } catch (\Exception $e) {
                Log::error('Failed to send task reminder', [
                    'task_id' => $task->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        Log::info('Sent task reminders', ['count' => $tasksDueSoon->count()]);
    }

    /**
     * Escalate urgent overdue tasks
     */
    protected function escalateUrgentTasks(): void
    {
        $urgentOverdueTasks = WorkflowTask::where('priority', 'urgent')
            ->overdue()
            ->where('status', '!=', 'completed')
            ->where(function ($query) {
                $query->whereNull('reminders_sent')
                      ->orWhereRaw("JSON_LENGTH(reminders_sent) < 1"); // Ensure at least one escalation
            })
            ->get();

        foreach ($urgentOverdueTasks as $task) {
            try {
                // Find supervisors or managers to notify
                $supervisors = $this->getTaskSupervisors($task);

                if (!empty($supervisors)) {
                    Notification::send($supervisors, new UrgentTaskAlert($task));
                    $task->addReminderSent();
                }

                Log::warning('Escalated urgent overdue task', [
                    'task_id' => $task->id,
                    'supervisors_notified' => count($supervisors)
                ]);

            } catch (\Exception $e) {
                Log::error('Failed to escalate urgent task', [
                    'task_id' => $task->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        Log::info('Escalated urgent tasks', ['count' => $urgentOverdueTasks->count()]);
    }

    /**
     * Check if reminder should be sent for task
     */
    protected function shouldSendReminder(WorkflowTask $task): bool
    {
        $remindersSent = $task->reminders_sent ?? [];
        $hoursUntilDue = now()->diffInHours($task->due_date, false);

        // Don't send reminders for tasks due in more than 24 hours
        if ($hoursUntilDue > 24) {
            return false;
        }

        // Don't send more than 2 reminders per task
        if (count($remindersSent) >= 2) {
            return false;
        }

        // Send first reminder when 24 hours remain
        if (count($remindersSent) === 0 && $hoursUntilDue <= 24) {
            return true;
        }

        // Send second reminder when 4 hours remain
        if (count($remindersSent) === 1 && $hoursUntilDue <= 4) {
            return true;
        }

        return false;
    }

    /**
     * Get supervisors for task escalation
     */
    protected function getTaskSupervisors(WorkflowTask $task): array
    {
        // This would integrate with user management system
        // For now, return empty array - would need to implement based on hospital hierarchy
        return [];

        // Example implementation:
        /*
        $assignedUser = $task->assignedUser;
        if (!$assignedUser) {
            return [];
        }

        // Get user's supervisor, department head, etc.
        return User::where('hospital_id', $assignedUser->hospital_id)
            ->whereIn('role', ['supervisor', 'manager', 'admin'])
            ->get()
            ->toArray();
        */
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Workflow tasks processing job failed', [
            'error' => $exception->getMessage()
        ]);
    }
}
