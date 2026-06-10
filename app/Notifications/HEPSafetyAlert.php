<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Broadcasting\PrivateChannel;
use App\Models\User;

class HEPSafetyAlert extends Notification implements ShouldQueue, ShouldBroadcast
{
    use Queueable;

    protected $patient;
    protected $alerts;
    protected $alertType;

    /**
     * Create a new notification instance.
     */
    public function __construct(User $patient, array $alerts, string $alertType = 'general')
    {
        $this->patient = $patient;
        $this->alerts = $alerts;
        $this->alertType = $alertType;
        $this->onQueue('realtime');
        $this->delay(0);
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
            'type' => 'hep_safety_alert',
            'alert_type' => $this->alertType,
            'patient_id' => $this->patient->id,
            'patient_name' => $this->patient->name,
            'alerts' => $this->alerts,
            'title' => 'HEP Safety Alert',
            'message' => $this->buildMessage(),
            'severity' => $this->getHighestSeverity(),
            'link' => "/admin/patients/{$this->patient->id}/hep",
            'created_at' => now()->toISOString(),
        ]);
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $subject = $this->getSubject();
        $message = $this->buildMessage();

        return (new MailMessage)
            ->subject($subject)
            ->greeting('Urgent: HEP Safety Alert')
            ->line($message)
            ->line('Patient: ' . $this->patient->name)
            ->line('Patient ID: ' . $this->patient->id)
            ->when($this->alertType === 'emergency', function ($mail) {
                return $mail->action('Contact Emergency Services', 'tel:911')
                           ->line('This is a critical safety alert requiring immediate attention.');
            })
            ->line('Please review the patient\'s condition and HEP program immediately.')
            ->salutation('Medical Alert System');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'hep_safety_alert',
            'alert_type' => $this->alertType,
            'patient_id' => $this->patient->id,
            'patient_name' => $this->patient->name,
            'alerts' => $this->alerts,
            'message' => $this->buildMessage(),
            'severity' => $this->getHighestSeverity(),
            'timestamp' => now(),
        ];
    }

    /**
     * Get the subject for the email
     */
    private function getSubject(): string
    {
        $severity = $this->getHighestSeverity();

        switch ($severity) {
            case 'critical':
                return '🚨 CRITICAL: HEP Safety Alert - Immediate Action Required';
            case 'high':
                return '⚠️ URGENT: HEP Safety Alert - Review Required';
            case 'medium':
                return '⚡ HEP Safety Alert - Monitor Patient';
            default:
                return 'HEP Safety Alert';
        }
    }

    /**
     * Build the message from alerts
     */
    private function buildMessage(): string
    {
        $messages = array_column($this->alerts, 'message');
        return implode('. ', $messages);
    }

    /**
     * Get the highest severity from alerts
     */
    private function getHighestSeverity(): string
    {
        $severities = ['low', 'medium', 'high', 'critical'];
        $maxSeverity = 'low';

        foreach ($this->alerts as $alert) {
            $severity = $alert['severity'] ?? 'low';
            $currentIndex = array_search($severity, $severities);
            $maxIndex = array_search($maxSeverity, $severities);

            if ($currentIndex > $maxIndex) {
                $maxSeverity = $severity;
            }
        }

        return $maxSeverity;
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
        return 'hep-safety-alert';
    }
}
