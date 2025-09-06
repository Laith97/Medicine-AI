<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AmbientSessionUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $sessionUuid;
    public int $doctorId;
    public array $payload;

    public function __construct(string $sessionUuid, int $doctorId, array $payload)
    {
        $this->sessionUuid = $sessionUuid;
        $this->doctorId = $doctorId;
        $this->payload = $payload;
    }

    public function broadcastOn(): Channel
    {
        return new PrivateChannel('doctor.' . $this->doctorId);
    }

    public function broadcastAs(): string
    {
        return 'ambient.session.updated';
    }
}
