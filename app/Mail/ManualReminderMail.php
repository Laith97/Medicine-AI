<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Queue\SerializesModels;
use App\Models\User;
use App\Models\MonthlyInvoiceSetting;

class ManualReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $setting;
    public $reminderType;

    /**
     * Create a new message instance.
     */
    public function __construct(User $user, MonthlyInvoiceSetting $setting, string $reminderType)
    {
        $this->user = $user;
        $this->setting = $setting;
        $this->reminderType = $reminderType;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subjects = [
            'grace_period' => 'MedCura AI - Payment Reminder',
            'warning_period' => 'MedCura AI - Payment Due',
            'overdue' => 'MedCura AI - Account Update Needed',
        ];

        $subject = $subjects[$this->reminderType] ?? 'Payment Reminder';

        return new Envelope(
            subject: $subject,
            from: new Address(env('MAIL_FROM_ADDRESS', 'info@medcuraai.com'), 'MedCura AI'),
            replyTo: [new Address(env('MAIL_FROM_ADDRESS', 'info@medcuraai.com'), 'MedCura AI Support')],
            using: [
                function ($message) {
                    $message->getHeaders()
                        ->addTextHeader('X-Mailer', 'MedCura AI System')
                        ->addTextHeader('X-Priority', '3')
                        ->addTextHeader('List-Unsubscribe', '<mailto:unsubscribe@medcuraai.com>')
                        ->addTextHeader('X-Auto-Response-Suppress', 'OOF, DR, RN, NRN')
                        ->addTextHeader('X-Entity-ID', 'MedCura-AI-' . uniqid());
                    
                    // Set return path properly
                    $message->returnPath(env('MAIL_FROM_ADDRESS', 'info@medcuraai.com'));
                },
            ],
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $templates = [
            'grace_period' => 'emails.reminders.grace-period-simple',
            'warning_period' => 'emails.reminders.warning-period', 
            'overdue' => 'emails.reminders.overdue',
        ];

        $template = $templates[$this->reminderType] ?? 'emails.reminders.grace-period-simple';

        return new Content(
            view: $template,
            with: [
                'userName' => $this->user->name,
                'userEmail' => $this->user->email,
                'billingAmount' => $this->setting->billing_amount ?? 0,
                'gracePeriodDays' => $this->setting->grace_period_days ?? 7,
                'subscriptionEndsAt' => $this->setting->subscription_ends_at,
                'reminderType' => $this->reminderType,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}