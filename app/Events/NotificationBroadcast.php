<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NotificationBroadcast implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $userId;
    public $notification;
    public $type;

    public function __construct($userId, array $notification, string $type = 'general')
    {
        $this->userId = $userId;
        $this->notification = $notification;
        $this->type = $type;
    }

    public function broadcastOn()
    {
        return new Channel('App.User.' . $this->userId);
    }

    public function broadcastAs()
    {
        return 'notification.received';
    }

    public function broadcastWith()
    {
        return $this->notification;
    }
}
