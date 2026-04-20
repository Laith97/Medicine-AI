<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Notification;
use App\Models\HepAssignment;
use App\Models\HepExercise;

class HEPExerciseReminder extends Notification implements ShouldQueue
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
        $program = $this->assignment->hepProgram;
        $doctor = $program->doctor;
        $doctorId = $doctor->id ?? 0;
        $hospitalId = $doctor->hospital_id ?? 0;

        return [
            'message' => $this->buildMessage() . ' View: ' . route('patient.hep.show', $this->assignment),
            'options' => [
                'doctor_id' => $doctorId,
                'hospital_id' => $hospitalId,
                'context' => 'hep_reminder',
                'context_id' => $this->assignment->id,
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
        $program = $this->assignment->hepProgram;
        $doctor = $program->doctor->user;

        $subject = $this->getSubject();
        $message = $this->buildMessage();

        return (new MailMessage)
            ->subject($subject)
            ->greeting("Hi {$notifiable->name},")
            ->line($message)
            ->action('View Your Exercises', route('patient.hep.show', $this->assignment))
            ->line("Your healthcare provider: Dr. {$doctor->name}")
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
        return [
            'type' => 'hep_reminder',
            'reminder_type' => $this->reminderType,
            'assignment_id' => $this->assignment->id,
            'program_title' => $this->assignment->hepProgram->title,
            'exercise_count' => count($this->exercises),
            'doctor_name' => $this->assignment->hepProgram->doctor->user->name,
            'message' => $this->buildMessage(),
            'action_url' => route('patient.hep.show', $this->assignment),
            'action_text' => 'View Exercises',
            'icon' => $this->getIcon(),
            'priority' => $this->getPriority(),
        ];
    }

    /**
     * Get the broadcast representation of the notification.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function toBroadcast($notifiable)
    {
        return [
            'id' => $this->id,
            'type' => 'hep_reminder',
            'reminder_type' => $this->reminderType,
            'assignment_id' => $this->assignment->id,
            'program_title' => $this->assignment->hepProgram->title,
            'exercise_count' => count($this->exercises),
            'doctor_name' => $this->assignment->hepProgram->doctor->user->name,
            'message' => $this->buildMessage(),
            'action_url' => route('patient.hep.show', $this->assignment),
            'action_text' => 'View Exercises',
            'icon' => $this->getIcon(),
            'priority' => $this->getPriority(),
            'created_at' => now()->toISOString(),
        ];
    }

    /**
     * Get the subject for the notification
     */
    protected function getSubject(): string
    {
        switch ($this->reminderType) {
            case 'missed':
                return "Don't forget your exercises - " . $this->assignment->hepProgram->title;
            case 'weekly':
                return "Weekly progress check - " . $this->assignment->hepProgram->title;
            case 'daily':
            default:
                return "Time for your exercises - " . $this->assignment->hepProgram->title;
        }
    }

    /**
     * Build the notification message
     */
    protected function buildMessage(): string
    {
        $program = $this->assignment->hepProgram;
        $exerciseCount = count($this->exercises);

        switch ($this->reminderType) {
            case 'missed':
                return "You have {$exerciseCount} exercise(s) waiting for you in your '{$program->title}' program. " .
                       "Completing your exercises regularly is important for your recovery progress.";

            case 'weekly':
                $currentWeek = min(
                    now()->diffInWeeks($this->assignment->assigned_at) + 1,
                    $program->duration_weeks
                );
                $completionRate = $this->assignment->getProgressPercentage();

                return "It's the end of week {$currentWeek} of your '{$program->title}' program. " .
                       "You've completed {$completionRate}% of your exercises so far. Keep up the great work!";

            case 'daily':
            default:
                if ($exerciseCount === 1) {
                    $exercise = $this->exercises[0];
                    return "It's time for your exercise: {$exercise->exercise->name} in your '{$program->title}' program.";
                } else {
                    return "You have {$exerciseCount} exercise(s) to complete today in your '{$program->title}' program.";
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
}
