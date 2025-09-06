<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RealTimeInsightCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $sessionUuid;
    public int $doctorId;
    public array $insight;

    public function __construct(string $sessionUuid, int $doctorId, array $insight)
    {
        $this->sessionUuid = $sessionUuid;
        $this->doctorId = $doctorId;
        $this->insight = $insight;
    }

    public function broadcastOn(): Channel
    {
        return new PrivateChannel('doctor.' . $this->doctorId);
    }

    public function broadcastAs(): string
    {
        return 'ambient.insight.created';
    }
}
