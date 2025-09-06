<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;
use App\Models\Diagnosis;

class DiagnosisSubmittedNotification extends Notification implements ShouldBroadcast
{
    use Queueable;

    protected $diagnosis;

    /**
     * Create a new notification instance.
     */
    public function __construct(Diagnosis $diagnosis)
    {
        $this->diagnosis = $diagnosis;

        // Use realtime queue for instant processing
        $this->onQueue('realtime');
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'broadcast', 'mail'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'diagnosis_submitted',
            'title' => 'New Diagnosis Submitted',
            'message' => "Dr. {$this->diagnosis->doctor->name} has submitted a new diagnosis for your case.",
            'icon' => 'file-medical',
            'link' => route('diagnosis.patient.view', $this->diagnosis->id),
            'link_text' => 'View Diagnosis',
            'related_type' => 'diagnosis',
            'related_id' => $this->diagnosis->id,
            'data' => [
                'diagnosis_id' => $this->diagnosis->id,
                'doctor_name' => $this->diagnosis->doctor->name,
                'submitted_at' => $this->diagnosis->created_at->format('Y-m-d H:i:s'),
                'has_ai_assistant' => $this->diagnosis->hasAiAssistantResults(),
            ]
        ];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New Diagnosis Submitted')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line("A new diagnosis has been submitted by {$this->diagnosis->doctor->name}")
            ->line('Diagnosis Date: ' . $this->diagnosis->created_at->format('M j, Y'))
            ->action('View Diagnosis', route('diagnosis.show', $this->diagnosis))
            ->line('Thank you for using our platform!');
    }

    /**
     * Get the SMS representation of the notification.
     */
    public function toSms(object $notifiable): string
    {
        return "New diagnosis submitted by {$this->diagnosis->doctor->name} on {$this->diagnosis->created_at->format('M j, Y')}. View details: " . route('diagnosis.show', $this->diagnosis);
    }

    /**
     * Get the broadcastable representation of the notification.
     */
    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'id' => $this->id,
            'type' => 'diagnosis_submitted',
            'title' => 'New Diagnosis Submitted',
            'message' => "Dr. {$this->diagnosis->doctor->name} has submitted a new diagnosis for your case.",
            'body' => "Dr. {$this->diagnosis->doctor->name} has submitted a new diagnosis for your case.",
            'icon' => 'file-medical',
            'link' => route('diagnosis.patient-view', $this->diagnosis->id),
            'link_text' => 'View Diagnosis',
            'data' => [
                'diagnosis_id' => $this->diagnosis->id,
                'doctor_name' => $this->diagnosis->doctor->name,
                'submitted_at' => $this->diagnosis->created_at->format('Y-m-d H:i:s'),
                'has_ai_assistant' => $this->diagnosis->hasAiAssistantResults(),
            ],
            'created_at' => now()->toISOString()
        ]);
    }
}
