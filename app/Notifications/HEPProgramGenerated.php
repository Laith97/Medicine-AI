<?php

namespace App\Notifications;

use App\Models\HepProgram;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class HEPProgramGenerated extends Notification implements ShouldQueue
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
        return ['mail', 'database'];
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
        if ($this->status === 'completed') {
            return [
                'type' => 'hep_program_generated',
                'title' => 'HEP Program Generated',
                'message' => 'A Home Exercise Program has been generated for ' . $this->program->patient->name,
                'program_id' => $this->program->id,
                'program_title' => $this->program->title,
                'patient_name' => $this->program->patient->name,
                'action_url' => '/hep/programs/' . $this->program->id,
            ];
        } else {
            return [
                'type' => 'hep_program_failed',
                'title' => 'HEP Program Generation Failed',
                'message' => 'Failed to generate HEP program: ' . $this->errorMessage,
                'error_message' => $this->errorMessage,
                'action_url' => '/hep/generate',
            ];
        }
    }
}
