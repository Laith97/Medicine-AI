<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewNotification implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;
    protected $userId;

    /**
     * Create a new event instance.
     */
    public function __construct($message, ?int $userId = null)
    {
        $this->message = $message;
        $this->userId = $userId ?? ($message['user_id'] ?? null);
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @throws \RuntimeException if userId is not provided
     */
    public function broadcastOn(): array
    {
        if (!$this->userId) {
            throw new \RuntimeException('Cannot broadcast notification without userId');
        }

        return [
            new PrivateChannel('App.User.' . $this->userId),
        ];
    }
}