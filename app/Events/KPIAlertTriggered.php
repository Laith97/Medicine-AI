<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class KPIAlertTriggered implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public array $alertData;
    public int $hospitalKey;
    public string $eventId;

    /**
     * Create a new event instance.
     */
    public function __construct(array $alertData, int $hospitalKey = 1)
    {
        $this->alertData = $alertData;
        $this->hospitalKey = $hospitalKey;
        $this->eventId = uniqid('alert_', true);
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        $channels = [
            new PrivateChannel("alerts.{$this->hospitalKey}"),
            new PrivateChannel("alerts.{$this->hospitalKey}.{$this->alertData['alert_level']}"),
        ];

        // Add user-specific channels for relevant users
        if (isset($this->alertData['recipients'])) {
            foreach ($this->alertData['recipients'] as $userId) {
                $channels[] = new PrivateChannel("alerts.user.{$userId}");
            }
        }

        return $channels;
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'alert.triggered';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'alert' => $this->alertData,
            'hospital_key' => $this->hospitalKey,
            'timestamp' => now()->toISOString(),
            'event_id' => $this->eventId,
            'severity' => $this->getSeverityLevel(),
        ];
    }

    /**
     * Get severity level for frontend handling
     */
    private function getSeverityLevel(): string
    {
        return match($this->alertData['alert_level']) {
            'critical' => 'high',
            'warning' => 'medium',
            'info' => 'low',
            default => 'medium'
        };
    }
}
