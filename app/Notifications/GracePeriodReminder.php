<?php

namespace App\Notifications;

use App\Models\MonthlyInvoiceSetting;
use App\Services\SmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Support\Facades\Log;

class GracePeriodReminder extends Notification implements ShouldBroadcast
{
    use Queueable;

    public function __construct(
        private MonthlyInvoiceSetting $setting
    ) {}

    public function via($notifiable): array
    {
        $channels = ['mail', 'database', 'broadcast'];

        // Add SMS if user has phone number
        if ($notifiable->phone) {
            $channels[] = 'sms';
        }

        return $channels;
    }

    /**
     * Get the broadcast representation of the notification.
     */
    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        $daysRemaining = $this->setting->getDaysRemainingInCurrentPeriod();
        return new BroadcastMessage([
            'id' => $this->id,
            'type' => 'grace_period_reminder',
            'days_remaining' => $daysRemaining,
            'title' => 'Subscription Expired - Grace Period Active',
            'message' => "You have {$daysRemaining} days remaining in your grace period. Renew now!",
            'link' => route('subscription.manage'),
            'created_at' => now()->toISOString(),
        ]);
    }

    public function toMail($notifiable): MailMessage
    {
        $daysRemaining = $this->setting->getDaysRemainingInCurrentPeriod();
        $renewalUrl = route('subscription.manage');

        return (new MailMessage)
            ->subject('Subscription Expired - Grace Period Active')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('Your subscription expired on ' . $this->setting->subscription_ends_at->format('M d, Y') . ', but you still have access during your grace period.')
            ->line("You have **{$daysRemaining} days remaining** in your grace period.")
            ->line('Renew your subscription now to continue enjoying unlimited access to our AI medical assistant.')
            ->line('**Your Plan:** ' . $this->setting->getAmountWithPeriod())
            ->action('Renew Subscription', $renewalUrl)
            ->line('If you don\'t renew before the grace period ends, your account will enter a warning period with limited access.')
            ->line('Thank you for using our service!');
    }

    public function toSms($notifiable): array
    {
        $daysRemaining = $this->setting->getDaysRemainingInCurrentPeriod();
        $renewalUrl = route('subscription.manage');
        $message = "🔔 MedCura AI: Your subscription expired but you're in grace period. {$daysRemaining} days remaining. Renew now: {$renewalUrl}";

        try {
            $smsService = new SmsService();
            $result = $smsService->send($notifiable->phone, $message);
            
            Log::info('Grace period reminder SMS sent via notification', [
                'user_id' => $notifiable->id,
                'phone' => $notifiable->phone,
                'success' => $result['success'],
                'provider' => $result['data']['provider'] ?? 'unknown'
            ]);
            
            return $result;
        } catch (\Exception $e) {
            Log::error('Failed to send grace period reminder SMS via notification', [
                'user_id' => $notifiable->id,
                'phone' => $notifiable->phone,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'data' => []
            ];
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
        return 'grace-period-reminder';
    }
}