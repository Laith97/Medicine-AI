<?php

namespace App\Notifications;

use App\Models\StripeInvoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\Twilio\TwilioChannel;
use NotificationChannels\Twilio\TwilioSmsMessage;

class InvoiceReminder extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public StripeInvoice $invoice,
        public int $reminderNumber
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = ['mail', 'sms'];
        
        return $channels;
    }

    /**
     * Get the SMS representation of the notification.
     */
    public function toSms(object $notifiable): array
    {
        $daysOverdue = $this->invoice->due_date->diffInDays(now());
        $urgencyLevel = $this->getUrgencyLevel();
        $doctorId = $this->invoice->user_id ?? 0;
        $hospitalId = 0;

        $message = "{$urgencyLevel['sms_prefix']} Reminder #{$this->reminderNumber}: ";
        $message .= "Invoice {$this->invoice->getFormattedPeriod()} ({$this->invoice->getFormattedAmountDue()}) ";
        $message .= "is {$daysOverdue} days overdue. Pay now: " . route('invoices.show', $this->invoice);

        return [
            'message' => $message,
            'options' => [
                'doctor_id' => $doctorId,
                'hospital_id' => $hospitalId,
                'context' => 'invoice_notification',
                'context_id' => $this->invoice->id,
            ]
        ];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $daysOverdue = $this->invoice->due_date->diffInDays(now());
        $urgencyLevel = $this->getUrgencyLevel();
        
        return (new MailMessage)
            ->subject($urgencyLevel['subject'] . ' - Invoice Reminder #' . $this->reminderNumber)
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line($urgencyLevel['opening'])
            ->line('**Invoice Details:**')
            ->line('Invoice: ' . $this->invoice->getFormattedPeriod())
            ->line('Amount Due: ' . $this->invoice->getFormattedAmountDue())
            ->line('Original Due Date: ' . $this->invoice->due_date->format('F j, Y'))
            ->line('Days Overdue: ' . $daysOverdue)
            ->action('Pay Now', route('invoices.show', $this->invoice))
            ->line($urgencyLevel['warning'])
            ->line('If you have any questions or need assistance, please contact our support team immediately.')
            ->salutation('Urgent regards, ' . config('app.name') . ' Team');
    }

    /**
     * Get the SMS representation of the notification.
     */
    public function toTwilio(object $notifiable): TwilioSmsMessage
    {
        $daysOverdue = $this->invoice->due_date->diffInDays(now());
        $urgencyLevel = $this->getUrgencyLevel();
        
        $message = "{$urgencyLevel['sms_prefix']} Reminder #{$this->reminderNumber}: ";
        $message .= "Invoice {$this->invoice->getFormattedPeriod()} ({$this->invoice->getFormattedAmountDue()}) ";
        $message .= "is {$daysOverdue} days overdue. Pay now: " . route('invoices.show', $this->invoice);
        
        return TwilioSmsMessage::create()
            ->content($message);
    }

    /**
     * Get urgency level based on reminder number and days overdue.
     */
    private function getUrgencyLevel(): array
    {
        $daysOverdue = $this->invoice->due_date->diffInDays(now());
        
        if ($this->reminderNumber >= 3 || $daysOverdue >= 14) {
            return [
                'subject' => 'URGENT: Final Notice',
                'sms_prefix' => 'URGENT',
                'opening' => 'This is a FINAL NOTICE regarding your overdue invoice. Immediate action is required to avoid service restrictions.',
                'warning' => '⚠️ WARNING: Your account access may be restricted if payment is not received within 24 hours.'
            ];
        } elseif ($this->reminderNumber >= 2 || $daysOverdue >= 7) {
            return [
                'subject' => 'Important: Payment Overdue',
                'sms_prefix' => 'IMPORTANT',
                'opening' => 'Your invoice payment is now overdue. Please make payment as soon as possible to avoid any service interruptions.',
                'warning' => 'Continued non-payment may result in temporary restrictions to your account access.'
            ];
        } else {
            return [
                'subject' => 'Friendly Reminder: Payment Due',
                'sms_prefix' => 'REMINDER',
                'opening' => 'This is a friendly reminder that your invoice payment is past due.',
                'warning' => 'Please make payment at your earliest convenience to keep your account in good standing.'
            ];
        }
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'invoice_id' => $this->invoice->id,
            'reminder_number' => $this->reminderNumber,
            'amount_due' => $this->invoice->amount_due,
            'days_overdue' => $this->invoice->due_date->diffInDays(now()),
        ];
    }
}
