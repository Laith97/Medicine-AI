<?php

namespace App\Notifications;

use App\Models\PatientVital;
use App\Models\ClinicalAlertRule;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ClinicalAlertNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public PatientVital $vital, public ClinicalAlertRule $rule)
    {
        //
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
        return (new MailMessage)
                    ->line('A clinical alert has been triggered for one of your patients.')
                    ->line('Patient: ' . $this->vital->patient->name)
                    ->line('Vital Type: ' . $this->vital->vital_type)
                    ->line('Value: ' . $this->vital->value)
                    ->line('Condition: ' . $this->rule->condition . ' ' . $this->rule->threshold)
                    ->action('View Dashboard', url('/doctor/monitoring/dashboard'))
                    ->line('Thank you for using our application!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'patient_id' => $this->vital->patient_id,
            'patient_name' => $this->vital->patient->name,
            'vital_type' => $this->vital->vital_type,
            'value' => $this->vital->value,
            'condition' => $this->rule->condition,
            'threshold' => $this->rule->threshold,
            'timestamp' => $this->vital->timestamp,
        ];
    }
}
