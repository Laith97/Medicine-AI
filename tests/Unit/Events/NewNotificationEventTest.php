<?php

namespace Tests\Unit\Events;

use Tests\TestCase;
use App\Events\NewNotification;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Testing\RefreshDatabase;

class NewNotificationEventTest extends TestCase
{
    /** @test */
    public function it_can_be_created()
    {
        $message = ['title' => 'Test', 'body' => 'Hello'];
        $event = new NewNotification($message, 42);

        $this->assertInstanceOf(NewNotification::class, $event);
        $this->assertEquals($message, $event->message);
    }

    /** @test */
    public function it_broadcasts_on_correct_channel()
    {
        $event = new NewNotification(['title' => 'Test'], 42);

        $channels = $event->broadcastOn();

        $this->assertCount(1, $channels);
        $this->assertInstanceOf(PrivateChannel::class, $channels[0]);
        $this->assertEquals('private-App.User.42', $channels[0]->name);
    }

    /** @test */
    public function it_broadcasts_with_correct_event_name()
    {
        $event = new NewNotification(['title' => 'Test'], 42);

        $this->assertEquals('NewNotification', $event->broadcastAs());
    }

    /** @test */
    public function it_returns_message_as_broadcast_data()
    {
        $message = ['title' => 'Test', 'body' => 'Hello', 'type' => 'info'];
        $event = new NewNotification($message, 42);

        $this->assertEquals($message, $event->broadcastWith());
    }

    /** @test */
    public function it_uses_user_id_from_message_if_not_provided()
    {
        $message = ['title' => 'Test', 'user_id' => 99];

        $event = new NewNotification($message);

        $channels = $event->broadcastOn();
        $this->assertEquals('private-App.User.99', $channels[0]->name);
    }

    /** @test */
    public function it_throws_exception_without_user_id()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot broadcast notification without userId');

        $event = new NewNotification(['title' => 'Test']);
        $event->broadcastOn();
    }
}
