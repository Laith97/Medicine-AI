<?php

namespace App\Notifications;

use App\Models\StripeInvoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\Twilio\TwilioChannel;
use NotificationChannels\Twilio\TwilioSmsMessage;

class InvoiceOverdue extends Notification
{
    use Queueable;

    public function __construct(
        public StripeInvoice $invoice
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = ['mail', 'database'];
        
        // Add SMS channel if phone number is available and Twilio is configured
        if ($notifiable->phone && config('services.twilio.sid')) {
            $channels[] = TwilioChannel::class;
        }
        
        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $daysOverdue = $this->invoice->due_date->diffInDays(now());
        $isRestricted = $notifiable->isRestricted();
        
        $message = (new MailMessage)
            ->subject('MedCura AI - Payment Required')
            ->from(config('mail.from.address'), 'MedCura AI')
            ->replyTo(config('mail.from.address'), 'MedCura AI Support')

            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('We wanted to let you know that your MedCura AI invoice payment is now overdue.')
            ->line('Invoice Amount: ' . $this->invoice->getFormattedAmountDue())
            ->line('Due Date: ' . $this->invoice->due_date->format('M d, Y') . ' (' . $daysOverdue . ' days ago)');
            
        if ($this->invoice->isMonthlyInvoice()) {
            $message->line('Billing Period: ' . $this->invoice->getFormattedPeriod());
            $message->line('Reminder #' . ($this->invoice->reminder_count + 1));
        }
        
        if ($isRestricted) {
            $message->line('Please note: Your account access has been temporarily limited due to this outstanding payment.');
        }
        
        $message->line('Description: ' . $this->invoice->description)
            ->action('View Invoice & Pay', route('invoices.show', $this->invoice))
            ->line('To restore full access to your MedCura AI account, please complete your payment as soon as possible.')
            ->line('If you have any questions or need assistance, our support team is here to help at ' . config('mail.from.address'))
            ->line('Thank you for your prompt attention to this matter.')
            ->salutation('Best regards,<br>The MedCura AI Team');
            
        return $message;
    }

    /**
     * Get the SMS representation of the notification.
     */
    public function toTwilio(object $notifiable): TwilioSmsMessage
    {
        $amount = $this->invoice->getFormattedAmountDue();
        $daysOverdue = $this->invoice->due_date->diffInDays(now());
        $isRestricted = $notifiable->isRestricted();
        
        $content = "URGENT: Invoice {$amount} is {$daysOverdue} days overdue.";
        if ($isRestricted) {
            $content .= " Account restricted.";
        }
        $content .= " Pay now: " . route('invoices.show', $this->invoice);
        
        return TwilioSmsMessage::create()->content($content);
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
            'stripe_invoice_id' => $this->invoice->stripe_invoice_id,
            'amount_due' => $this->invoice->amount_due,
            'due_date' => $this->invoice->due_date,
            'message' => 'OVERDUE: Invoice ' . $this->invoice->getFormattedAmountDue() . ' was due on ' . $this->invoice->due_date->format('M d, Y'),
        ];
    }
}
