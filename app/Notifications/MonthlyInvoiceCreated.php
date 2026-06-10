<?php

namespace App\Notifications;

use App\Models\StripeInvoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Broadcasting\PrivateChannel;
use NotificationChannels\Twilio\TwilioChannel;
use NotificationChannels\Twilio\TwilioSmsMessage;

class MonthlyInvoiceCreated extends Notification implements ShouldQueue, ShouldBroadcast
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
            'type' => 'monthly_invoice_created',
            'invoice_id' => $this->invoice->id,
            'amount_due' => $this->invoice->amount_due,
            'due_date' => $this->invoice->due_date,
            'period' => $this->invoice->getFormattedPeriod(),
            'title' => 'Monthly Invoice Created',
            'message' => 'New monthly invoice for ' . $this->invoice->getFormattedPeriod() . ': ' . $this->invoice->getFormattedAmountDue(),
            'link' => route('invoices.show', $this->invoice),
            'created_at' => now()->toISOString(),
        ]);
    }

    /**
     * Get the SMS representation of the notification.
     */
    public function toSms(object $notifiable): array
    {
        $doctorId = $this->invoice->user_id ?? 0;
        $hospitalId = 0;

        $message = "New monthly invoice for {$this->invoice->getFormattedPeriod()}: {$this->invoice->getFormattedAmountDue()} due {$this->invoice->due_date->format('M j')}. ";
        $message .= "Pay online: " . route('invoices.show', $this->invoice);

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
        return (new MailMessage)
            ->subject('New Monthly Invoice - ' . $this->invoice->getFormattedPeriod())
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('Your monthly invoice for ' . $this->invoice->getFormattedPeriod() . ' has been generated.')
            ->line('**Invoice Details:**')
            ->line('Amount Due: ' . $this->invoice->getFormattedAmountDue())
            ->line('Due Date: ' . $this->invoice->due_date->format('F j, Y'))
            ->line('Grace Period Ends: ' . $this->invoice->grace_period_ends_at->format('F j, Y'))
            ->action('View & Pay Invoice', route('invoices.show', $this->invoice))
            ->line('Please pay your invoice by the due date to avoid any service interruptions.')
            ->line('If you have any questions, please contact our support team.')
            ->salutation('Best regards, ' . config('app.name') . ' Team');
    }

    /**
     * Get the SMS representation of the notification.
     */
    public function toTwilio(object $notifiable): TwilioSmsMessage
    {
        $message = "New monthly invoice for {$this->invoice->getFormattedPeriod()}: {$this->invoice->getFormattedAmountDue()} due {$this->invoice->due_date->format('M j')}. ";
        $message .= "Pay online: " . route('invoices.show', $this->invoice);
        
        return TwilioSmsMessage::create()
            ->content($message);
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
            'amount_due' => $this->invoice->amount_due,
            'due_date' => $this->invoice->due_date,
            'period' => $this->invoice->getFormattedPeriod(),
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
        return 'monthly-invoice-created';
    }
}
