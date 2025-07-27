<?php

namespace App\Notifications;

use App\Models\StripeInvoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\Twilio\TwilioChannel;
use NotificationChannels\Twilio\TwilioSmsMessage;

class InvoiceCreated extends Notification implements ShouldQueue
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
        $subject = $this->invoice->isMonthlyInvoice() 
            ? 'Monthly Invoice Created - MedCura AI'
            : 'New Invoice Created - MedCura AI';
            
        $message = (new MailMessage)
            ->subject($subject)
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('A new invoice has been created for your account.')
            ->line('Invoice Amount: ' . $this->invoice->getFormattedAmountDue())
            ->line('Due Date: ' . $this->invoice->due_date->format('M d, Y'));
            
        if ($this->invoice->isMonthlyInvoice()) {
            $message->line('Period: ' . $this->invoice->getFormattedPeriod());
            
            if ($this->invoice->grace_period_ends_at) {
                $message->line('Grace Period Ends: ' . $this->invoice->grace_period_ends_at->format('M d, Y'));
            }
        }
        
        $message->line('Description: ' . $this->invoice->description)
            ->action('View & Pay Invoice', route('invoices.show', $this->invoice))
            ->line('Please ensure payment is made by the due date to avoid any service interruptions.')
            ->line('Thank you for using MedCura AI!');
            
        return $message;
    }

    /**
     * Get the SMS representation of the notification.
     */
    public function toTwilio(object $notifiable): TwilioSmsMessage
    {
        $type = $this->invoice->isMonthlyInvoice() ? 'Monthly' : 'New';
        $amount = $this->invoice->getFormattedAmountDue();
        $dueDate = $this->invoice->due_date->format('M d');
        
        return TwilioSmsMessage::create()
            ->content("{$type} invoice created: {$amount} due {$dueDate}. View & pay: " . route('invoices.show', $this->invoice));
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
            'message' => 'New invoice created for ' . $this->invoice->getFormattedAmountDue(),
        ];
    }
}
