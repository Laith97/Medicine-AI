<?php

namespace App\Notifications;

use App\Models\PatientInsurance;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EligibilityCheckFailedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $patientInsurance;
    protected $serviceType;
    protected $errorMessage;

    /**
     * Create a new notification instance.
     */
    public function __construct(PatientInsurance $patientInsurance, string $serviceType, string $errorMessage)
    {
        $this->patientInsurance = $patientInsurance;
        $this->serviceType = $serviceType;
        $this->errorMessage = $errorMessage;
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
            ->subject("Insurance Eligibility Check Failed")
            ->greeting("Hello {$notifiable->name},")
            ->line("We were unable to verify eligibility for your {$this->patientInsurance->insuranceProvider->name} insurance.")
            ->line("Policy: {$this->patientInsurance->policy_number}")
            ->line("Service Type: " . ucfirst(str_replace('_', ' ', $this->serviceType)))
            ->line("Error: {$this->errorMessage}")
            ->action('Check Eligibility Status', url('/patient/eligibility'))
            ->line('Please verify your insurance information or contact your insurance provider.')
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
            'title' => 'Eligibility Check Failed',
            'message' => "Unable to verify eligibility for {$this->patientInsurance->insuranceProvider->name} - {$this->serviceType}.",
            'type' => 'error',
            'action_url' => '/patient/eligibility',
            'action_text' => 'Check Status',
            'related_type' => 'patient_insurance',
            'related_id' => $this->patientInsurance->id,
        ];
    }
}
