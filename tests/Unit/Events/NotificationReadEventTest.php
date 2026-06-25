<?php

namespace Tests\Unit\Events;

use Tests\TestCase;
use App\Events\NotificationRead;
use Illuminate\Broadcasting\PrivateChannel;

class NotificationReadEventTest extends TestCase
{
    /** @test */
    public function it_can_be_created()
    {
        $event = new NotificationRead(42, 'uuid-123');

        $this->assertInstanceOf(NotificationRead::class, $event);
        $this->assertEquals(42, $event->userId);
        $this->assertEquals('uuid-123', $event->notificationId);
    }

    /** @test */
    public function it_broadcasts_on_correct_channel()
    {
        $event = new NotificationRead(42, 'uuid-123');

        $channels = $event->broadcastOn();

        $this->assertInstanceOf(PrivateChannel::class, $channels);
        $this->assertEquals('private-App.User.42', $channels->name);
    }

    /** @test */
    public function it_broadcasts_with_correct_event_name()
    {
        $event = new NotificationRead(42, 'uuid-123');

        $this->assertEquals('NotificationRead', $event->broadcastAs());
    }
}
