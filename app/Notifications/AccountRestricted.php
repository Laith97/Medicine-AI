<?php

namespace App\Notifications;

use App\Models\MonthlyInvoiceSetting;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\Twilio\TwilioChannel;
use NotificationChannels\Twilio\TwilioSmsMessage;

class AccountRestricted extends Notification implements ShouldQueue
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
        $renewalUrl = route('subscription.manage');
        $invoicesUrl = route('invoices.index');
        $warningPeriodEnd = $this->setting->getWarningPeriodEndDate();

        return (new MailMessage)
            ->subject('❌ Account Restricted - Immediate Action Required')
            ->greeting('Account Restricted: ' . $notifiable->name)
            ->line('**Your account has been restricted due to an expired subscription.**')
            ->line('Warning period ended on: ' . $warningPeriodEnd->format('M d, Y'))
            ->line('**Restricted Access:**')
            ->line('• ❌ AI Medical Assistant - Disabled')
            ->line('• ❌ Patient Case Management - Disabled')
            ->line('• ❌ All Premium Features - Disabled')
            ->line('• ✅ Invoices & Payment - Available')
            ->line('**To restore full access:**')
            ->line('1. Pay any outstanding invoices')
            ->line('2. Renew your subscription')
            ->line('**Your Plan:** ' . $this->setting->getAmountWithPeriod())
            ->action('💳 Pay & Restore Access', $renewalUrl)
            ->action('📄 View Outstanding Invoices', $invoicesUrl)
            ->line('**Need help?** Contact our support team immediately:')
            ->line('📧 Email: ' . config('app.support_email', 'support@medcuraai.com'))
            ->line('📞 Phone: ' . config('app.support_phone', '+1-800-MEDCURA'))
            ->line('We\'re here to help you restore your access quickly!');
    }

    public function toTwilio($notifiable): TwilioSmsMessage
    {
        $renewalUrl = route('subscription.manage');

        return (new TwilioSmsMessage())
            ->content("❌ MedCura AI: Your account has been RESTRICTED due to expired subscription. Pay now to restore access: {$renewalUrl}");
    }
}