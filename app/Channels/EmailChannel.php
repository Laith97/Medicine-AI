<?php

namespace App\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class EmailChannel
{
    /**
     * Send the given notification.
     */
    public function send(object $notifiable, Notification $notification): void
    {
        $message = $notification->toMail($notifiable);

        if (!$message instanceof MailMessage) {
            return;
        }

        // Send the email using Laravel's mail system
        \Mail::to($notifiable->email)->send($message);
    }
}
