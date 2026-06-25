<?php

namespace Tests\Unit\Notifications;

use Tests\TestCase;
use App\Models\User;
use App\Notifications\SystemAlertNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

class SystemAlertNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['role' => 'admin']);
    }

    /** @test */
    public function it_can_be_created_with_required_params()
    {
        $notification = new SystemAlertNotification('Alert', 'Something happened');

        $this->assertInstanceOf(SystemAlertNotification::class, $notification);
    }

    /** @test */
    public function it_has_correct_channels()
    {
        $notification = new SystemAlertNotification('Alert', 'Something happened');

        $channels = $notification->via($this->user);

        $this->assertEquals(['database', 'broadcast'], $channels);
    }

    /** @test */
    public function it_has_correct_array_content()
    {
        $notification = new SystemAlertNotification(
            'Server Warning',
            'CPU usage is high',
            'warning',
            ['link' => '/admin/alerts', 'related_type' => 'server', 'related_id' => 1]
        );

        $content = $notification->toArray($this->user);

        $this->assertEquals('system_alert', $content['type']);
        $this->assertEquals('Server Warning', $content['title']);
        $this->assertEquals('CPU usage is high', $content['message']);
        $this->assertEquals('exclamation-triangle', $content['icon']);
        $this->assertEquals('/admin/alerts', $content['link']);
        $this->assertEquals('View Details', $content['link_text']);
        $this->assertEquals('server', $content['related_type']);
        $this->assertEquals(1, $content['related_id']);
        $this->assertEquals('warning', $content['data']['alert_type']);
        $this->assertArrayHasKey('created_at', $content['data']);
    }

    /** @test */
    public function it_uses_correct_icon_per_type()
    {
        $cases = [
            'error' => 'exclamation-circle',
            'warning' => 'exclamation-triangle',
            'success' => 'check-circle',
            'info' => 'info-circle',
            'unknown' => 'bell',
        ];

        foreach ($cases as $type => $expectedIcon) {
            $notification = new SystemAlertNotification('Test', 'Message', $type);
            $array = $notification->toArray($this->user);
            $this->assertEquals($expectedIcon, $array['icon'], "Icon mismatch for type: {$type}");
        }
    }

    /** @test */
    public function it_has_correct_broadcast_content()
    {
        $notification = new SystemAlertNotification(
            'Server Warning',
            'CPU usage is high',
            'warning',
            ['link' => '/admin/alerts', 'user_id' => $this->user->id]
        );

        $broadcast = $notification->toBroadcast($this->user);

        $data = $broadcast->data;
        $this->assertEquals('system_alert', $data['type']);
        $this->assertEquals('Server Warning', $data['title']);
        $this->assertEquals('CPU usage is high', $data['message']);
        $this->assertEquals('exclamation-triangle', $data['icon']);
        $this->assertEquals('/admin/alerts', $data['link']);
    }

    /** @test */
    public function it_broadcasts_on_correct_channel()
    {
        $notification = new SystemAlertNotification(
            'Test',
            'Message',
            'info',
            ['user_id' => 42]
        );

        $channels = $notification->broadcastOn();

        $this->assertCount(1, $channels);
        $this->assertEquals('private-App.User.42', $channels[0]->name);
    }

    /** @test */
    public function it_broadcasts_with_correct_event_name()
    {
        $notification = new SystemAlertNotification('Test', 'Message');

        $this->assertEquals('system-alert', $notification->broadcastAs());
    }

    /** @test */
    public function it_can_be_stored_in_database()
    {
        $notification = new SystemAlertNotification(
            'DB Test',
            'Testing database storage',
            'info',
            ['link' => '/dashboard']
        );

        $this->user->notify($notification);

        $dbNotification = $this->user->notifications()->first();

        $this->assertNotNull($dbNotification);
        $this->assertEquals(SystemAlertNotification::class, $dbNotification->type);

        $data = $dbNotification->data;
        $this->assertEquals('system_alert', $data['type']);
        $this->assertEquals('DB Test', $data['title']);
        $this->assertEquals('Testing database storage', $data['message']);
        $this->assertEquals('/dashboard', $data['link']);
    }

    /** @test */
    public function it_can_send_to_multiple_users()
    {
        $users = User::factory()->count(3)->create();

        $notification = new SystemAlertNotification('Broadcast', 'Message to all');

        foreach ($users as $user) {
            $user->notify($notification);
        }

        foreach ($users as $user) {
            $this->assertEquals(1, $user->notifications()->count());
        }
    }

    /** @test */
    public function it_can_be_created_with_minimal_params()
    {
        $notification = new SystemAlertNotification('Minimal', 'Just a message');

        $array = $notification->toArray($this->user);

        $this->assertEquals('Minimal', $array['title']);
        $this->assertEquals('Just a message', $array['message']);
        $this->assertEquals('warning', $array['data']['alert_type']); // default type
        $this->assertNull($array['link']);
        $this->assertNull($array['related_type']);
    }

    /** @test */
    public function it_handles_null_data_gracefully()
    {
        $notification = new SystemAlertNotification('Test', 'Message', 'info', []);

        $broadcast = $notification->toBroadcast($this->user);

        $this->assertNull($broadcast->data['link']);
        $this->assertEquals('View Details', $broadcast->data['link_text']);
    }
}
