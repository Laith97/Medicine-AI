<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VoiceAssistantPerformanceAlert extends Notification implements ShouldQueue
{
    use Queueable;

    public array $alerts;
    public array $metrics;

    /**
     * Create a new notification instance.
     */
    public function __construct(array $alerts, array $metrics)
    {
        $this->alerts = $alerts;
        $this->metrics = $metrics;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
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
        $mail = (new MailMessage)
            ->subject('🚨 Voice Assistant Performance Alert - ' . config('app.name'))
            ->greeting('Voice Assistant Performance Alert')
            ->line('The automated monitoring system has detected performance issues with the Voice Assistant system.');

        foreach ($this->alerts as $alert) {
            $mail->line('⚠️ ' . $alert);
        }

        $mail->line('**System Metrics:**')
            ->line('• Total Sessions: ' . $this->metrics['total_sessions'])
            ->line('• Average Processing Time: ' . round($this->metrics['avg_processing_time'], 2) . 's')
            ->line('• Success Rate: ' . round($this->metrics['success_rate'], 1) . '%')
            ->line('• Error Rate: ' . round($this->metrics['error_rate'], 1) . '%')
            ->line('• Timestamp: ' . now()->format('Y-m-d H:i:s'));

        return $mail->action('View Performance Dashboard', route('voice-assistant.performance'))
            ->line('Please investigate and resolve the issues to ensure optimal Voice Assistant performance.')
            ->salutation('Regards, ' . config('app.name') . ' Monitoring System');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Voice Assistant Performance Alert',
            'message' => 'Performance issues detected with the Voice Assistant system.',
            'alerts' => $this->alerts,
            'metrics' => $this->metrics,
            'type' => 'performance_alert',
            'action_url' => route('voice-assistant.performance'),
            'action_text' => 'View Performance Dashboard',
            'severity' => 'warning',
        ];
    }
}