<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class KPIUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $kpiName;
    public array $data;
    public int $hospitalKey;
    public string $eventId;

    /**
     * Create a new event instance.
     */
    public function __construct(string $kpiName, array $data, int $hospitalKey = 1)
    {
        $this->kpiName = $kpiName;
        $this->data = $data;
        $this->hospitalKey = $hospitalKey;
        $this->eventId = uniqid('kpi_', true);
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("kpi.{$this->hospitalKey}"),
            new PrivateChannel("kpi.{$this->hospitalKey}.{$this->kpiName}"),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'kpi.updated';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'kpi_name' => $this->kpiName,
            'data' => $this->data,
            'hospital_key' => $this->hospitalKey,
            'timestamp' => now()->toISOString(),
            'event_id' => $this->eventId,
        ];
    }
}
