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

class EligibilityExpiringNotification extends Notification implements ShouldQueue, ShouldBroadcast
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
            'type' => 'eligibility_expiring',
            'patient_insurance_id' => $this->patientInsurance->id,
            'insurance_provider' => $this->patientInsurance->insuranceProvider->name,
            'policy_number' => $this->patientInsurance->policy_number,
            'days_until_expiry' => $this->daysUntilExpiry,
            'title' => 'Insurance Eligibility Expiring',
            'message' => "Your {$this->patientInsurance->insuranceProvider->name} eligibility expires in {$this->daysUntilExpiry} days",
            'link' => '/patient/insurance',
            'created_at' => now()->toISOString(),
        ]);
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
        return 'eligibility-expiring';
    }
}
