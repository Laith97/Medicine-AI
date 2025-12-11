<?php

namespace App\Notifications;

use App\Models\PatientInsurance;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EligibilityExpiringNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $patientInsurance;
    protected $daysUntilExpiry;

    /**
     * Create a new notification instance.
     */
    public function __construct(PatientInsurance $patientInsurance, int $daysUntilExpiry)
    {
        $this->patientInsurance = $patientInsurance;
        $this->daysUntilExpiry = $daysUntilExpiry;
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
            ->subject("Insurance Eligibility Expiring Soon")
            ->greeting("Hello {$notifiable->name},")
            ->line("Your insurance eligibility for {$this->patientInsurance->insuranceProvider->name} is expiring in {$this->daysUntilExpiry} days.")
            ->line("Policy: {$this->patientInsurance->policy_number}")
            ->action('Update Insurance Information', url('/patient/insurance'))
            ->line('Please update your insurance information to avoid any service interruptions.')
            ->salutation('Best regards, Medicine AI Team');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Insurance Eligibility Expiring',
            'message' => "Your {$this->patientInsurance->insuranceProvider->name} eligibility expires in {$this->daysUntilExpiry} days.",
            'type' => 'warning',
            'action_url' => '/patient/insurance',
            'action_text' => 'Update Insurance',
            'related_type' => 'patient_insurance',
            'related_id' => $this->patientInsurance->id,
        ];
    }
}
