<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Support\Facades\Lang;

class ResetPasswordNotification extends Notification implements ShouldBroadcast
{
    use Queueable;

    /**
     * The password reset token.
     *
     * @var string
     */
    public $token;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($token)
    {
        $this->token = $token;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['database'];
    }

    /**
     * Get the broadcast representation of the notification.
     */
    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'id' => $this->id,
            'type' => 'password_reset',
            'title' => 'Password Reset',
            'message' => 'You have requested a password reset.',
            'link' => url(route('password.reset', ['token' => $this->token, 'email' => $notifiable->email], false)),
            'created_at' => now()->toISOString(),
        ]);
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $email = $notifiable->email;
        
        $url = url(route('password.reset', [
            'token' => $this->token,
            'email' => $email,
        ], false));

        // Use the custom email template
        return (new MailMessage)
            ->subject('Reset Your MedCura AI Password')
            ->view('emails.reset-password', ['url' => $url]);
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }

    /**
     * Get the channels the notification should broadcast on.
     *
     * @return array
     */
    public function broadcastOn(): array
    {
        return [new PrivateChannel('App.User.' . ($this->notifiable?->id ?? 'default'))];
    }

    /**
     * Get the broadcast event name.
     *
     * @return string
     */
    public function broadcastAs(): string
    {
        return 'password-reset';
    }
}