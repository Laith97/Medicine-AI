<?php

namespace App\Notifications;

use App\Models\HepProgram;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Broadcasting\PrivateChannel;

class HEPProgramGenerated extends Notification implements ShouldQueue, ShouldBroadcast
{
    use Queueable;

    protected $program;
    protected $status;
    protected $errorMessage;

    /**
     * Create a new notification instance.
     */
    public function __construct(?HepProgram $program, string $status, string $errorMessage = null)
    {
        $this->program = $program;
        $this->status = $status;
        $this->errorMessage = $errorMessage;
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
        $patientName = $this->program?->patient?->name ?? 'Patient';
        $programTitle = $this->program?->title ?? 'Program';

        if ($this->status === 'completed') {
            return new BroadcastMessage([
                'id' => $this->id,
                'type' => 'hep_program_generated',
                'program_id' => $this->program?->id ?? 0,
                'program_title' => $programTitle,
                'patient_name' => $patientName,
                'title' => 'HEP Program Generated',
                'message' => 'A Home Exercise Program has been generated for ' . $patientName,
                'link' => '/hep/programs/' . ($this->program?->id ?? 0),
                'created_at' => now()->toISOString(),
            ]);
        } else {
            return new BroadcastMessage([
                'id' => $this->id,
                'type' => 'hep_program_failed',
                'error_message' => $this->errorMessage,
                'title' => 'HEP Program Generation Failed',
                'message' => 'Failed to generate HEP program: ' . ($this->errorMessage ?? 'Unknown error'),
                'link' => '/hep/generate',
                'created_at' => now()->toISOString(),
            ]);
        }
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        if ($this->status === 'completed') {
            return (new MailMessage)
                ->subject('HEP Program Generated Successfully')
                ->greeting('Hello ' . $notifiable->name . '!')
                ->line('A Home Exercise Program has been successfully generated for your patient.')
                ->line('**Program Details:**')
                ->line('Title: ' . $this->program->title)
                ->line('Duration: ' . $this->program->duration_weeks . ' weeks')
                ->line('Frequency: ' . $this->program->frequency_per_week . ' times per week')
                ->line('Patient: ' . $this->program->patient->name)
                ->action('View Program', url('/hep/programs/' . $this->program->id))
                ->line('Please review the program and assign it to the patient when ready.');
        } else {
            return (new MailMessage)
                ->subject('HEP Program Generation Failed')
                ->greeting('Hello ' . $notifiable->name . '!')
                ->line('There was an error generating the Home Exercise Program.')
                ->line('**Error Details:**')
                ->line($this->errorMessage)
                ->line('Please try again or contact technical support if the issue persists.')
                ->action('Try Again', url('/hep/generate'));
        }
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        $patientName = $this->program?->patient?->name ?? 'Patient';
        $programTitle = $this->program?->title ?? 'Program';

        if ($this->status === 'completed') {
            return [
                'type' => 'hep_program_generated',
                'title' => 'HEP Program Generated',
                'message' => 'A Home Exercise Program has been generated for ' . $patientName,
                'program_id' => $this->program?->id ?? 0,
                'program_title' => $programTitle,
                'patient_name' => $patientName,
                'action_url' => '/hep/programs/' . ($this->program?->id ?? 0),
            ];
        } else {
            return [
                'type' => 'hep_program_failed',
                'title' => 'HEP Program Generation Failed',
                'message' => 'Failed to generate HEP program: ' . ($this->errorMessage ?? 'Unknown error'),
                'error_message' => $this->errorMessage,
                'action_url' => '/hep/generate',
            ];
        }
    }

    /**
     * Get the channels the notification should broadcast on.
     *
     * @return array
     */
    public function broadcastOn(): array
    {
        // Use program's patient_id directly since notifiable may be null during queue processing
        $userId = $this->program->patient_id ?? $this->notifiable?->id ?? 'default';
        return [new PrivateChannel('App.User.' . $userId)];
    }

    /**
     * Get the broadcast event name.
     *
     * @return string
     */
    public function broadcastAs(): string
    {
        return 'hep-program-generated';
    }
}
