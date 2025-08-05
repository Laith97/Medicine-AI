<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\VoiceTranscription;

class VoiceTranscriptionCompletedNotification extends Notification
{
    use Queueable;

    protected $transcription;

    /**
     * Create a new notification instance.
     */
    public function __construct(VoiceTranscription $transcription)
    {
        $this->transcription = $transcription;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'voice_transcription_completed',
            'title' => 'Voice Transcription Completed',
            'message' => "Your voice transcription session has been completed and is ready for review.",
            'icon' => 'microphone',
            'link' => route('voice-assistant.show', $this->transcription->id),
            'link_text' => 'View Transcription',
            'related_type' => 'voice_transcription',
            'related_id' => $this->transcription->id,
            'data' => [
                'transcription_id' => $this->transcription->id,
                'session_id' => $this->transcription->session_id,
                'duration' => $this->transcription->session_ended_at ? $this->transcription->session_ended_at->diffInMinutes($this->transcription->session_started_at) : 0,
                'has_ai_analysis' => !empty($this->transcription->ai_analysis),
                'completed_at' => $this->transcription->session_ended_at ? $this->transcription->session_ended_at->format('Y-m-d H:i:s') : null,
            ]
        ];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Voice Transcription Completed')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line("Your voice transcription session has been completed and is ready for review.")
            ->line('Session ID: ' . $this->transcription->session_id)
            ->line('Duration: ' . $this->transcription->session_ended_at ? $this->transcription->session_ended_at->diffInMinutes($this->transcription->session_started_at) . ' minutes' : 'Unknown')
            ->line('AI Analysis: ' . ($this->transcription->ai_analysis ? 'Available' : 'Not available'))
            ->action('View Transcription', route('voice-assistant.show', $this->transcription->id))
            ->line('Thank you for using our platform!');
    }

    /**
     * Get the SMS representation of the notification.
     */
    public function toSms(object $notifiable): string
    {
        return "Voice transcription session completed. Session ID: {$this->transcription->session_id}. View details: " . route('voice-assistant.show', $this->transcription->id);
    }
}
