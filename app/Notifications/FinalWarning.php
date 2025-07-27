<?php

namespace App\Notifications;

use App\Models\MonthlyInvoiceSetting;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\Twilio\TwilioChannel;
use NotificationChannels\Twilio\TwilioSmsMessage;

class FinalWarning extends Notification implements ShouldQueue
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
            $channels[] = TwilioChannel::class;
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

    public function toTwilio($notifiable): TwilioSmsMessage
    {
        $daysRemaining = $this->setting->getDaysRemainingInCurrentPeriod();
        $renewalUrl = route('subscription.manage');

        return (new TwilioSmsMessage())
            ->content("🚨 URGENT - MedCura AI: FINAL WARNING! Your account will be RESTRICTED in {$daysRemaining} days. Renew immediately: {$renewalUrl}");
    }
}