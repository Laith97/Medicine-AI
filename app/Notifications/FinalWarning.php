<?php

namespace App\Notifications;

use App\Models\MonthlyInvoiceSetting;
use App\Services\SmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class FinalWarning extends Notification
{
    use Queueable;

    public function __construct(
        private MonthlyInvoiceSetting $setting
    ) {}

    public function via($notifiable): array
    {
        $channels = ['mail'];
        
        // Add SMS if user has phone number
        if ($notifiable->phone) {
            $channels[] = 'sms';
        }
        
        return $channels;
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
}