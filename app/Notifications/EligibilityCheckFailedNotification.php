<?php

namespace App\Notifications;

use App\Models\PatientInsurance;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Broadcasting\PrivateChannel;

class EligibilityCheckFailedNotification extends Notification implements ShouldQueue, ShouldBroadcast
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
        $this->onQueue('realtime');
        $this->delay(0);
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
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
            'type' => 'eligibility_check_failed',
            'patient_insurance_id' => $this->patientInsurance->id,
            'insurance_provider' => $this->patientInsurance->insuranceProvider->name,
            'policy_number' => $this->patientInsurance->policy_number,
            'service_type' => $this->serviceType,
            'error_message' => $this->errorMessage,
            'title' => 'Eligibility Check Failed',
            'message' => "Unable to verify eligibility for {$this->patientInsurance->insuranceProvider->name} - {$this->serviceType}",
            'link' => '/patient/eligibility',
            'created_at' => now()->toISOString(),
        ]);
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
        return 'eligibility-check-failed';
    }
}
