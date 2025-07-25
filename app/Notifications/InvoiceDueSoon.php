<?php

namespace App\Notifications;

use App\Models\StripeInvoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InvoiceDueSoon extends Notification implements ShouldQueue
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
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $daysUntilDue = now()->diffInDays($this->invoice->due_date, false);
        
        return (new MailMessage)
            ->subject('Invoice Due Soon - MedCura AI')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('This is a reminder that you have an invoice due soon.')
            ->line('Invoice Amount: ' . $this->invoice->getFormattedAmountDue())
            ->line('Due Date: ' . $this->invoice->due_date->format('M d, Y') . ' (' . abs($daysUntilDue) . ' days)')
            ->line('Description: ' . $this->invoice->description)
            ->action('Pay Now', route('invoices.show', $this->invoice))
            ->line('Please make your payment before the due date to avoid any service interruptions.')
            ->line('Thank you for using MedCura AI!');
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
            'message' => 'Invoice due soon: ' . $this->invoice->getFormattedAmountDue() . ' due on ' . $this->invoice->due_date->format('M d, Y'),
        ];
    }
}
