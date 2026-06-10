<?php

namespace App\Notifications;

use App\Models\StripeInvoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Notifications\Notification;

class InvoiceDueSoon extends Notification implements ShouldQueue, ShouldBroadcast
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
        $channels = ['mail', 'database', 'broadcast', 'sms'];
        return $channels;
    }

    /**
     * Get the broadcast representation of the notification.
     */
    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'id' => $this->id,
            'type' => 'invoice_due_soon',
            'invoice_id' => $this->invoice->id,
            'stripe_invoice_id' => $this->invoice->stripe_invoice_id,
            'amount_due' => $this->invoice->amount_due,
            'due_date' => $this->invoice->due_date,
            'title' => 'Invoice Due Soon',
            'message' => 'Invoice due soon: ' . $this->invoice->getFormattedAmountDue() . ' due on ' . $this->invoice->due_date->format('M d, Y'),
            'link' => route('invoices.show', $this->invoice),
            'created_at' => now()->toISOString(),
        ]);
    }

    /**
     * Get the SMS representation of the notification.
     */
    public function toSms(object $notifiable): array
    {
        $daysUntilDue = now()->diffInDays($this->invoice->due_date, false);
        $doctorId = $this->invoice->user_id ?? 0;
        $hospitalId = 0;

        return [
            'message' => "Reminder: Your MedCura AI invoice of {$this->invoice->getFormattedAmountDue()} is due in {$daysUntilDue} days. Due: {$this->invoice->due_date->format('M d, Y')}. Pay: " . route('invoices.show', $this->invoice),
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
        $daysUntilDue = now()->diffInDays($this->invoice->due_date, false);
        
        return (new MailMessage)
            ->subject('MedCura AI - Payment Due Soon')
            ->from(config('mail.from.address'), 'MedCura AI')
            ->replyTo(config('mail.from.address'), 'MedCura AI Support')

            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('We wanted to remind you that your MedCura AI invoice will be due soon.')
            ->line('Invoice Amount: ' . $this->invoice->getFormattedAmountDue())
            ->line('Due Date: ' . $this->invoice->due_date->format('M d, Y') . ' (' . abs($daysUntilDue) . ' days)')
            ->line('Description: ' . $this->invoice->description)
            ->action('View Invoice & Pay', route('invoices.show', $this->invoice))
            ->line('To continue enjoying uninterrupted access to MedCura AI, please complete your payment before the due date.')
            ->line('If you have any questions, our support team is here to help at ' . config('mail.from.address'))
            ->line('Thank you for choosing MedCura AI!')
            ->salutation('Best regards,<br>The MedCura AI Team');
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

    /**
     * Get the channels the notification should broadcast on.
     *
     * @return array
     */
    public function broadcastOn(): array
    {
        // Use invoice's user_id directly since notifiable may be null during queue processing
        $userId = $this->invoice->user_id ?? $this->notifiable?->id ?? 'default';
        return [new PrivateChannel('App.User.' . $userId)];
    }

    /**
     * Get the broadcast event name.
     *
     * @return string
     */
    public function broadcastAs(): string
    {
        return 'invoice-due-soon';
    }
}
