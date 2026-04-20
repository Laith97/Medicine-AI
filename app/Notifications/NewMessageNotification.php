<?php

namespace App\Notifications;

use App\Models\MessageThread;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class NewMessageNotification extends Notification implements ShouldBroadcast
{
    use Queueable;

    public function __construct(
        private MessageThread $thread,
        private User $sender
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'new_message',
            'thread_id' => $this->thread->id,
            'sender_name' => $this->sender->name,
            'subject' => $this->thread->subject,
            'message_preview' => $this->thread->messages()->latest()->first()?->body,
            'thread_type' => $this->thread->type,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = $notifiable->role === 'doctor'
            ? url("/doctor/messages/{$this->thread->id}")
            : url("/patient/messages/{$this->thread->id}");

        return (new MailMessage)
            ->subject("New message from {$this->sender->name}")
            ->line("Subject: {$this->thread->subject}")
            ->action('View Message', url($url))
            ->line('This is an automated notification.');
    }

    /**
     * Get the broadcastable representation of the notification.
     */
    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'id' => $this->id,
            'type' => 'new_message',
            'title' => 'New Message',
            'message' => "You have a new message from {$this->sender->name}: {$this->thread->subject}",
            'body' => "You have a new message from {$this->sender->name}: {$this->thread->subject}",
            'icon' => 'envelope',
            'link' => $notifiable->role === 'doctor'
                ? url("/doctor/messages/{$this->thread->id}")
                : url("/patient/messages/{$this->thread->id}"),
            'link_text' => 'View Message',
            'data' => [
                'thread_id' => $this->thread->id,
                'sender_name' => $this->sender->name,
                'subject' => $this->thread->subject,
                'message_preview' => $this->thread->messages()->latest()->first()?->body,
                'thread_type' => $this->thread->type,
            ],
            'created_at' => now()->toISOString()
        ]);
    }

    /**
     * Get the channels the notification should broadcast on.
     *
     * @return array
     */
    public function broadcastOn()
    {
        return [
            new \Illuminate\Broadcasting\PrivateChannel('user.' . $this->notifiable->id),
        ];
    }

    /**
     * Get the broadcast event name.
     *
     * @return string
     */
    public function broadcastAs()
    {
        return 'new-message';
    }
}
