<?php

namespace App\Events;

use Illuminate\Support\Facades\Auth;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NotificationRead implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $userId;
    public $notificationId;

    public function __construct($userId, $notificationId)
    {
        $this->userId = $userId;
        $this->notificationId = $notificationId;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('App.User.' . $this->userId);
    }

    public function broadcastAs()
    {
        return 'NotificationRead';
    }
}
