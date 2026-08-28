<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Broadcasting\PrivateChannel;
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
        $isDoctor = $notifiable->isDoctor();

        if ($isDoctor) {
            return [
                'type' => 'diagnosis_submitted',
                'title' => 'Diagnosis Submitted',
                'message' => "You submitted a diagnosis for {$this->diagnosis->patient_name} on {$this->diagnosis->created_at->format('M j, Y')}",
                'icon' => 'file-medical',
                'link' => route('diagnosis.show', $this->diagnosis->id),
                'link_text' => 'View Diagnosis',
                'related_type' => 'diagnosis',
                'related_id' => $this->diagnosis->id,
                'data' => [
                    'diagnosis_id' => $this->diagnosis->id,
                    'doctor_name' => $this->diagnosis->doctor->name,
                    'patient_name' => $this->diagnosis->patient_name,
                    'submitted_at' => $this->diagnosis->created_at->format('Y-m-d H:i:s'),
                    'has_ai_assistant' => $this->diagnosis->hasAiAssistantResults(),
                ]
            ];
        } else {
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
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $isDoctor = $notifiable->isDoctor();

        if ($isDoctor) {
            return (new MailMessage)
                ->subject('Diagnosis Submitted')
                ->greeting('Hello ' . $notifiable->name . ',')
                ->line("You have submitted a diagnosis for {$this->diagnosis->patient_name}")
                ->line('Diagnosis Date: ' . $this->diagnosis->created_at->format('M j, Y'))
                ->action('View Diagnosis', route('diagnosis.show', $this->diagnosis->id))
                ->line('Thank you for using our platform!');
        } else {
            return (new MailMessage)
                ->subject('New Diagnosis Submitted')
                ->greeting('Hello ' . $notifiable->name . ',')
                ->line("Dr. {$this->diagnosis->doctor->name} has submitted a new diagnosis for your case")
                ->line('Diagnosis Date: ' . $this->diagnosis->created_at->format('M j, Y'))
                ->action('View Diagnosis', route('diagnosis.patient.view', $this->diagnosis->id))
                ->line('Thank you for using our platform!');
        }
    }

    /**
     * Get the SMS representation of the notification.
     */
    public function toSms(object $notifiable): string
    {
        $isDoctor = $notifiable->isDoctor();

        if ($isDoctor) {
            return "You submitted a diagnosis for {$this->diagnosis->patient_name} on {$this->diagnosis->created_at->format('M j, Y')}. View: " . route('diagnosis.show', $this->diagnosis->id);
        } else {
            return "Dr. {$this->diagnosis->doctor->name} submitted a new diagnosis for your case on {$this->diagnosis->created_at->format('M j, Y')}. View: " . route('diagnosis.patient.view', $this->diagnosis->id);
        }
    }

    /**
     * Get the broadcastable representation of the notification.
     */
    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        $isDoctor = $notifiable->isDoctor();

        if ($isDoctor) {
            return new BroadcastMessage([
                'id' => $this->id,
                'type' => 'diagnosis_submitted',
                'title' => 'Diagnosis Submitted',
                'message' => "You submitted a diagnosis for {$this->diagnosis->patient_name}",
                'body' => "You submitted a diagnosis for {$this->diagnosis->patient_name}",
                'icon' => 'file-medical',
                'link' => route('diagnosis.show', $this->diagnosis->id),
                'link_text' => 'View Diagnosis',
                'data' => [
                    'diagnosis_id' => $this->diagnosis->id,
                    'doctor_name' => $this->diagnosis->doctor->name,
                    'patient_name' => $this->diagnosis->patient_name,
                    'submitted_at' => $this->diagnosis->created_at->format('Y-m-d H:i:s'),
                    'has_ai_assistant' => $this->diagnosis->hasAiAssistantResults(),
                ],
                'created_at' => now()->toISOString()
            ]);
        } else {
            return new BroadcastMessage([
                'id' => $this->id,
                'type' => 'diagnosis_submitted',
                'title' => 'New Diagnosis Submitted',
                'message' => "Dr. {$this->diagnosis->doctor->name} has submitted a new diagnosis for your case.",
                'body' => "Dr. {$this->diagnosis->doctor->name} has submitted a new diagnosis for your case.",
                'icon' => 'file-medical',
                'link' => route('diagnosis.patient.view', $this->diagnosis->id),
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
        return 'diagnosis-submitted';
    }
}
