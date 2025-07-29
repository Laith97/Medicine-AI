<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
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
            'grace_period' => 'Payment Grace Period - Action Required',
            'warning_period' => 'Final Warning - Payment Due Soon',
            'overdue' => 'Invoice Overdue - Immediate Action Required',
        ];

        $subject = $subjects[$this->reminderType] ?? 'Payment Reminder';

        return new Envelope(
            subject: $subject,
            from: new Address(env('MAIL_FROM_ADDRESS', 'info@medcuraai.com'), 'MedCura AI Billing'),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $templates = [
            'grace_period' => 'emails.reminders.grace-period',
            'warning_period' => 'emails.reminders.warning-period', 
            'overdue' => 'emails.reminders.overdue',
        ];

        $template = $templates[$this->reminderType] ?? 'emails.reminders.grace-period';

        return new Content(
            view: $template,
            with: [
                'user' => $this->user,
                'setting' => $this->setting,
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