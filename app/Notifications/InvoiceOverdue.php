<?php

namespace App\Notifications;

use App\Models\StripeInvoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\Twilio\TwilioChannel;
use NotificationChannels\Twilio\TwilioSmsMessage;

class InvoiceOverdue extends Notification implements ShouldQueue
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
            ->subject('URGENT: Invoice Overdue - MedCura AI')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('Your invoice is now overdue and requires immediate attention.')
            ->line('Invoice Amount: ' . $this->invoice->getFormattedAmountDue())
            ->line('Due Date: ' . $this->invoice->due_date->format('M d, Y') . ' (' . $daysOverdue . ' days overdue)');
            
        if ($this->invoice->isMonthlyInvoice()) {
            $message->line('Period: ' . $this->invoice->getFormattedPeriod());
            $message->line('Reminder #' . ($this->invoice->reminder_count + 1));
        }
        
        if ($isRestricted) {
            $message->line('⚠️ Your account access has been restricted due to this overdue payment.');
        }
        
        $message->line('Description: ' . $this->invoice->description)
            ->action('Pay Now', route('invoices.show', $this->invoice))
            ->line('Please make your payment immediately to restore full access to your account.')
            ->line('If you have any questions, please contact our support team.')
            ->line('Thank you for using MedCura AI!');
            
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
