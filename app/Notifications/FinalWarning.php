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

class FinalWarning extends Notification implements ShouldBroadcast
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
            'type' => 'final_warning',
            'days_remaining' => $daysRemaining,
            'title' => 'FINAL WARNING - Account Will Be Restricted',
            'message' => "Your account will be RESTRICTED in {$daysRemaining} days if you don't renew immediately.",
            'link' => route('subscription.manage'),
            'created_at' => now()->toISOString(),
        ]);
    }

    public function toMail($notifiable): MailMessage
    {
        $daysRemaining = $this->setting->getDaysRemainingInCurrentPeriod();
        $renewalUrl = route('subscription.manage');
        $gracePeriodEnd = $this->setting->getGracePeriodEndDate();

        return (new MailMessage)
            ->subject('🚨 FINAL WARNING - Account Will Be Restricted')
            ->greeting('URGENT: ' . $notifiable->name)
            ->line('**This is your final warning!**')
            ->line('Your grace period ended on ' . $gracePeriodEnd->format('M d, Y') . '.')
            ->line("Your account will be **RESTRICTED in {$daysRemaining} days** if you don't renew immediately.")
            ->line('Once restricted, you will lose access to:')
            ->line('• AI Medical Assistant')
            ->line('• Patient Case Management')
            ->line('• All Premium Features')
            ->line('**Your Plan:** ' . $this->setting->getAmountWithPeriod())
            ->action('🔥 RENEW NOW - AVOID RESTRICTION', $renewalUrl)
            ->line('**Don\'t wait!** Renew now to maintain uninterrupted access to all features.')
            ->line('Contact support immediately if you need assistance: ' . config('app.support_email', 'support@medcuraai.com'));
    }

    public function toSms($notifiable): array
    {
        $daysRemaining = $this->setting->getDaysRemainingInCurrentPeriod();
        $renewalUrl = route('subscription.manage');
        $message = "🚨 URGENT - MedCura AI: FINAL WARNING! Your account will be RESTRICTED in {$daysRemaining} days. Renew immediately: {$renewalUrl}";

        try {
            $smsService = new SmsService();
            $result = $smsService->send($notifiable->phone, $message);
            
            Log::info('Final warning SMS sent via notification', [
                'user_id' => $notifiable->id,
                'phone' => $notifiable->phone,
                'success' => $result['success'],
                'provider' => $result['data']['provider'] ?? 'unknown'
            ]);
            
            return $result;
        } catch (\Exception $e) {
            Log::error('Failed to send final warning SMS via notification', [
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
        return 'final-warning';
    }
}