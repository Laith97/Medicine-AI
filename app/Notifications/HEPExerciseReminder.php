<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Broadcasting\PrivateChannel;
use App\Models\HepAssignment;
use App\Models\HepExercise;

class HEPExerciseReminder extends Notification implements ShouldQueue, ShouldBroadcast
{
    use Queueable;

    protected $assignment;
    protected $exercises;
    protected $reminderType;

    /**
     * Create a new notification instance.
     *
     * @param HepAssignment $assignment
     * @param array $exercises
     * @param string $reminderType ('daily', 'missed', 'weekly')
     */
    public function __construct(HepAssignment $assignment, array $exercises = [], string $reminderType = 'daily')
    {
        $this->assignment = $assignment;
        $this->exercises = $exercises;
        $this->reminderType = $reminderType;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail', 'database', 'broadcast', 'sms'];
    }

    /**
     * Get the SMS representation of the notification.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function toSms($notifiable): array
    {
        $program = $this->assignment?->hepProgram;
        $doctor = $program?->doctor;
        $doctorId = $doctor?->id ?? 0;
        $hospitalId = $doctor?->hospital_id ?? 0;
        $assignmentId = $this->assignment?->id ?? 0;

        return [
            'message' => $this->buildMessage() . ' View: ' . ($assignmentId > 0 ? route('patient.hep.show', $this->assignment) : '#'),
            'options' => [
                'doctor_id' => $doctorId,
                'hospital_id' => $hospitalId,
                'context' => 'hep_reminder',
                'context_id' => $assignmentId,
            ]
        ];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param mixed $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $program = $this->assignment?->hepProgram;
        $doctor = $program?->doctor?->user;
        $assignmentId = $this->assignment?->id ?? 0;

        $subject = $this->getSubject();
        $message = $this->buildMessage();

        return (new MailMessage)
            ->subject($subject)
            ->greeting("Hi {$notifiable->name},")
            ->line($message)
            ->action('View Your Exercises', $assignmentId > 0 ? route('patient.hep.show', $this->assignment) : '#')
            ->line("Your healthcare provider: Dr. " . ($doctor?->name ?? 'Unknown'))
            ->line('Consistent exercise is key to your recovery. Stay committed!')
            ->salutation('Best regards, Your Healthcare Team');
    }

    /**
     * Get the database representation of the notification.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function toDatabase($notifiable)
    {
        $program = $this->assignment?->hepProgram;
        $doctorUser = $program?->doctor?->user;
        $assignmentId = $this->assignment?->id ?? 0;

        return [
            'type' => 'hep_reminder',
            'reminder_type' => $this->reminderType,
            'assignment_id' => $assignmentId,
            'program_title' => $program?->title ?? 'HEP Program',
            'exercise_count' => count($this->exercises),
            'doctor_name' => $doctorUser?->name ?? 'Unknown Doctor',
            'message' => $this->buildMessage(),
            'action_url' => $assignmentId > 0 ? route('patient.hep.show', $this->assignment) : '#',
            'action_text' => 'View Exercises',
            'icon' => $this->getIcon(),
            'priority' => $this->getPriority(),
        ];
    }

    /**
     * Get the broadcast representation of the notification.
     *
     * @param mixed $notifiable
     * @return BroadcastMessage
     */
    public function toBroadcast($notifiable): BroadcastMessage
    {
        $program = $this->assignment?->hepProgram;
        $doctorUser = $program?->doctor?->user;
        $assignmentId = $this->assignment?->id ?? 0;

        $payload = [
            'id' => $this->id,
            'type' => 'hep_exercise_reminder',
            'reminder_type' => $this->reminderType,
            'assignment_id' => $assignmentId,
            'program_title' => $program?->title ?? 'HEP Program',
            'exercise_count' => count($this->exercises),
            'doctor_name' => $doctorUser?->name ?? 'Unknown Doctor',
            'message' => $this->buildMessage(),
            'action_url' => $assignmentId > 0 ? route('patient.hep.show', $this->assignment) : '#',
            'action_text' => 'View Exercises',
            'icon' => $this->getIcon(),
            'priority' => $this->getPriority(),
            'created_at' => now()->toISOString(),
        ];

        if (class_exists('App\Services\NotificationCompressionService')) {
            $compressionService = app(\App\Services\NotificationCompressionService::class);
            $compressedPayload = $compressionService->compressPayload($payload);
            return new BroadcastMessage($compressedPayload);
        }

        return new BroadcastMessage($payload);
    }

    /**
     * Get the subject for the notification
     */
    protected function getSubject(): string
    {
        $programTitle = $this->assignment?->hepProgram?->title ?? 'Program';
        switch ($this->reminderType) {
            case 'missed':
                return "Don't forget your exercises - " . $programTitle;
            case 'weekly':
                return "Weekly progress check - " . $programTitle;
            case 'daily':
            default:
                return "Time for your exercises - " . $programTitle;
        }
    }

    /**
     * Build the notification message
     */
    protected function buildMessage(): string
    {
        $program = $this->assignment?->hepProgram;
        $programTitle = $program?->title ?? 'Program';
        $exerciseCount = count($this->exercises);

        switch ($this->reminderType) {
            case 'missed':
                return "You have {$exerciseCount} exercise(s) waiting for you in your '{$programTitle}' program. " .
                       "Completing your exercises regularly is important for your recovery progress.";

            case 'weekly':
                $currentWeek = 1;
                if ($program && $this->assignment?->assigned_at) {
                    $currentWeek = min(
                        now()->diffInWeeks($this->assignment->assigned_at) + 1,
                        $program->duration_weeks ?? 1
                    );
                }
                $completionRate = $this->assignment?->getProgressPercentage() ?? 0;

                return "It's the end of week {$currentWeek} of your '{$programTitle}' program. " .
                       "You've completed {$completionRate}% of your exercises so far. Keep up the great work!";

            case 'daily':
            default:
                if ($exerciseCount === 1 && isset($this->exercises[0])) {
                    $exercise = $this->exercises[0];
                    return "It's time for your exercise: {$exercise->exercise->name} in your '{$programTitle}' program.";
                } else {
                    return "You have {$exerciseCount} exercise(s) to complete today in your '{$programTitle}' program.";
                }
        }
    }

    /**
     * Get the icon for the notification
     */
    protected function getIcon(): string
    {
        switch ($this->reminderType) {
            case 'missed':
                return 'exclamation-triangle';
            case 'weekly':
                return 'chart-line';
            case 'daily':
            default:
                return 'dumbbell';
        }
    }

    /**
     * Get the priority for the notification
     */
    protected function getPriority(): string
    {
        switch ($this->reminderType) {
            case 'missed':
                return 'high';
            case 'weekly':
                return 'medium';
            case 'daily':
            default:
                return 'normal';
        }
    }

    /**
     * Get the channels the notification should broadcast on.
     *
     * @return array
     */
    public function broadcastOn(): array
    {
        // Use assignment's patient_id directly since notifiable may be null during queue processing
        $userId = $this->assignment->patient_id ?? $this->notifiable?->id ?? 'default';
        return [new PrivateChannel('App.User.' . $userId)];
    }

    /**
     * Get the broadcast event name.
     *
     * @return string
     */
    public function broadcastAs(): string
    {
        return 'hep-exercise-reminder';
    }
}
