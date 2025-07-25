<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class UsageWarning extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $user;
    public $usagePercentage;
    public $currentUsage;
    public $tokenLimit;

    /**
     * Create a new message instance.
     */
    public function __construct(User $user, int $usagePercentage, int $currentUsage, int $tokenLimit)
    {
        $this->user = $user;
        $this->usagePercentage = $usagePercentage;
        $this->currentUsage = $currentUsage;
        $this->tokenLimit = $tokenLimit;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subject = $this->usagePercentage >= 90 
            ? 'Action Required: Token Limit Almost Reached'
            : 'Usage Alert: Approaching Token Limit';

        return new Envelope(
            subject: $subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.usage-warning',
            with: [
                'user' => $this->user,
                'usagePercentage' => $this->usagePercentage,
                'currentUsage' => $this->currentUsage,
                'tokenLimit' => $this->tokenLimit,
                'planConfig' => $this->user->getPlanConfig(),
            ]
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
