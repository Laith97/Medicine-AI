<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['role' => 'patient']);
    }

    /** @test */
    public function it_belongs_to_a_notifiable_user()
    {
        $notification = Notification::factory()->create([
            'notifiable_id' => $this->user->id,
            'notifiable_type' => 'users',
        ]);

        $this->assertEquals($this->user->id, $notification->notifiable->id);
        $this->assertEquals('users', $notification->notifiable_type);
    }

    /** @test */
    public function it_has_a_data_attribute()
    {
        $data = [
            'title' => 'Test Notification',
            'message' => 'This is a test notification',
            'type' => 'test',
            'icon' => 'info',
            'link' => '/test',
        ];

        $notification = Notification::factory()->create([
            'data' => $data,
        ]);

        $this->assertEquals($data, $notification->data);
    }

    /** @test */
    public function it_can_be_marked_as_read()
    {
        $notification = Notification::factory()->create([
            'notifiable_id' => $this->user->id,
            'notifiable_type' => 'users',
            'read_at' => null,
        ]);

        $notification->markAsRead();

        $this->assertNotNull($notification->fresh()->read_at);
    }

    /** @test */
    public function it_can_be_marked_as_unread()
    {
        $notification = Notification::factory()->create([
            'notifiable_id' => $this->user->id,
            'notifiable_type' => 'users',
            'read_at' => now(),
        ]);

        $notification->markAsUnread();

        $this->assertNull($notification->fresh()->read_at);
    }

    /** @test */
    public function it_can_check_if_it_is_read()
    {
        $unreadNotification = Notification::factory()->create([
            'notifiable_id' => $this->user->id,
            'notifiable_type' => 'users',
            'read_at' => null,
        ]);

        $readNotification = Notification::factory()->create([
            'notifiable_id' => $this->user->id,
            'notifiable_type' => 'users',
            'read_at' => now(),
        ]);

        $this->assertFalse($unreadNotification->isRead());
        $this->assertTrue($readNotification->isRead());
    }

    /** @test */
    public function it_can_check_if_it_is_unread()
    {
        $unreadNotification = Notification::factory()->create([
            'notifiable_id' => $this->user->id,
            'notifiable_type' => 'users',
            'read_at' => null,
        ]);

        $readNotification = Notification::factory()->create([
            'notifiable_id' => $this->user->id,
            'notifiable_type' => 'users',
            'read_at' => now(),
        ]);

        $this->assertTrue($unreadNotification->isUnread());
        $this->assertFalse($readNotification->isUnread());
    }

    /** @test */
    public function it_can_scope_unread_notifications()
    {
        Notification::factory()->create([
            'notifiable_id' => $this->user->id,
            'notifiable_type' => 'users',
            'read_at' => null,
        ]);

        Notification::factory()->create([
            'notifiable_id' => $this->user->id,
            'notifiable_type' => 'users',
            'read_at' => now(),
        ]);

        $unreadNotifications = Notification::unread()->get();

        $this->assertEquals(1, $unreadNotifications->count());
        $this->assertNull($unreadNotifications->first()->read_at);
    }

    /** @test */
    public function it_can_scope_read_notifications()
    {
        Notification::factory()->create([
            'notifiable_id' => $this->user->id,
            'notifiable_type' => 'users',
            'read_at' => null,
        ]);

        Notification::factory()->create([
            'notifiable_id' => $this->user->id,
            'notifiable_type' => 'users',
            'read_at' => now(),
        ]);

        $readNotifications = Notification::read()->get();

        $this->assertEquals(1, $readNotifications->count());
        $this->assertNotNull($readNotifications->first()->read_at);
    }

    /** @test */
    public function it_can_scope_notifications_by_type()
    {
        Notification::factory()->create([
            'notifiable_id' => $this->user->id,
            'notifiable_type' => 'users',
            'type' => 'appointment_booked',
        ]);

        Notification::factory()->create([
            'notifiable_id' => $this->user->id,
            'notifiable_type' => 'users',
            'type' => 'diagnosis_submitted',
        ]);

        $appointmentNotifications = Notification::type('appointment_booked')->get();

        $this->assertEquals(1, $appointmentNotifications->count());
        $this->assertEquals('appointment_booked', $appointmentNotifications->first()->type);
    }

    /** @test */
    public function it_can_scope_notifications_by_notifiable()
    {
        $otherUser = User::factory()->create();

        Notification::factory()->create([
            'notifiable_id' => $this->user->id,
            'notifiable_type' => 'users',
        ]);

        Notification::factory()->create([
            'notifiable_id' => $otherUser->id,
            'notifiable_type' => 'users',
        ]);

        $userNotifications = Notification::forNotifiable($this->user)->get();

        $this->assertEquals(1, $userNotifications->count());
        $this->assertEquals($this->user->id, $userNotifications->first()->notifiable_id);
    }

    /** @test */
    public function it_can_get_notification_title()
    {
        $notification = Notification::factory()->create([
            'notifiable_id' => $this->user->id,
            'notifiable_type' => 'users',
            'data' => ['title' => 'Test Title'],
        ]);

        $this->assertEquals('Test Title', $notification->getTitle());
    }

    /** @test */
    public function it_can_get_notification_message()
    {
        $notification = Notification::factory()->create([
            'notifiable_id' => $this->user->id,
            'notifiable_type' => 'users',
            'data' => ['message' => 'Test Message'],
        ]);

        $this->assertEquals('Test Message', $notification->getMessage());
    }

    /** @test */
    public function it_can_get_notification_icon()
    {
        $notification = Notification::factory()->create([
            'notifiable_id' => $this->user->id,
            'notifiable_type' => 'users',
            'data' => ['icon' => 'info'],
        ]);

        $this->assertEquals('info', $notification->getIcon());
    }

    /** @test */
    public function it_can_get_notification_link()
    {
        $notification = Notification::factory()->create([
            'notifiable_id' => $this->user->id,
            'notifiable_type' => 'users',
            'data' => ['link' => '/test-link'],
        ]);

        $this->assertEquals('/test-link', $notification->getLink());
    }

    /** @test */
    public function it_can_get_notification_type()
    {
        $notification = Notification::factory()->create([
            'notifiable_id' => $this->user->id,
            'notifiable_type' => 'users',
            'type' => 'appointment_booked',
        ]);

        $this->assertEquals('appointment_booked', $notification->getType());
    }

    /** @test */
    public function it_can_get_notification_related_type()
    {
        $notification = Notification::factory()->create([
            'notifiable_id' => $this->user->id,
            'notifiable_type' => 'users',
            'data' => ['related_type' => 'appointment'],
        ]);

        $this->assertEquals('appointment', $notification->getRelatedType());
    }

    /** @test */
    public function it_can_get_notification_related_id()
    {
        $notification = Notification::factory()->create([
            'notifiable_id' => $this->user->id,
            'notifiable_type' => 'users',
            'data' => ['related_id' => 123],
        ]);

        $this->assertEquals(123, $notification->getRelatedId());
    }

    /** @test */
    public function it_can_get_notification_data()
    {
        $data = ['title' => 'Test', 'message' => 'Test message'];
        $notification = Notification::factory()->create([
            'notifiable_id' => $this->user->id,
            'notifiable_type' => 'users',
            'data' => $data,
        ]);

        $this->assertEquals($data, $notification->getData());
    }

    /** @test */
    public function it_can_get_notification_data_by_key()
    {
        $notification = Notification::factory()->create([
            'notifiable_id' => $this->user->id,
            'notifiable_type' => 'users',
            'data' => ['title' => 'Test Title', 'message' => 'Test Message'],
        ]);

        $this->assertEquals('Test Title', $notification->getData('title'));
        $this->assertEquals('Test Message', $notification->getData('message'));
        $this->assertEquals('default', $notification->getData('nonexistent', 'default'));
    }

    /** @test */
    public function it_can_check_if_notification_has_data()
    {
        $notificationWithData = Notification::factory()->create([
            'notifiable_id' => $this->user->id,
            'notifiable_type' => 'users',
            'data' => ['title' => 'Test'],
        ]);

        $notificationWithoutData = Notification::factory()->create([
            'notifiable_id' => $this->user->id,
            'notifiable_type' => 'users',
            'data' => null,
        ]);

        $this->assertTrue($notificationWithData->hasData());
        $this->assertFalse($notificationWithoutData->hasData());
    }

    /** @test */
    public function it_can_check_if_notification_has_specific_data()
    {
        $notification = Notification::factory()->create([
            'notifiable_id' => $this->user->id,
            'notifiable_type' => 'users',
            'data' => ['title' => 'Test', 'message' => 'Test message'],
        ]);

        $this->assertTrue($notification->hasData('title'));
        $this->assertTrue($notification->hasData('message'));
        $this->assertFalse($notification->hasData('nonexistent'));
    }

    /** @test */
    public function it_can_get_notification_age()
    {
        $notification = Notification::factory()->create([
            'notifiable_id' => $this->user->id,
            'notifiable_type' => 'users',
            'created_at' => now()->subHours(2),
        ]);

        $this->assertEquals('2 hours ago', $notification->getAge());
    }

    /** @test */
    public function it_can_get_notification_age_in_minutes()
    {
        $notification = Notification::factory()->create([
            'notifiable_id' => $this->user->id,
            'notifiable_type' => 'users',
            'created_at' => now()->subMinutes(30),
        ]);

        $this->assertEquals('30 minutes ago', $notification->getAge());
    }

    /** @test */
    public function it_can_get_notification_age_in_days()
    {
        $notification = Notification::factory()->create([
            'notifiable_id' => $this->user->id,
            'notifiable_type' => 'users',
            'created_at' => now()->subDays(3),
        ]);

        $this->assertEquals('3 days ago', $notification->getAge());
    }

    /** @test */
    public function it_can_get_notification_age_in_weeks()
    {
        $notification = Notification::factory()->create([
            'notifiable_id' => $this->user->id,
            'notifiable_type' => 'users',
            'created_at' => now()->subWeeks(2),
        ]);

        $this->assertEquals('2 weeks ago', $notification->getAge());
    }

    /** @test */
    public function it_can_get_notification_age_in_months()
    {
        $notification = Notification::factory()->create([
            'notifiable_id' => $this->user->id,
            'notifiable_type' => 'users',
            'created_at' => now()->subMonths(2),
        ]);

        $this->assertEquals('2 months ago', $notification->getAge());
    }

    /** @test */
    public function it_can_get_notification_age_in_years()
    {
        $notification = Notification::factory()->create([
            'notifiable_id' => $this->user->id,
            'notifiable_type' => 'users',
            'created_at' => now()->subYears(1),
        ]);

        $this->assertEquals('1 year ago', $notification->getAge());
    }

    /** @test */
    public function it_can_get_notification_age_in_seconds()
    {
        $notification = Notification::factory()->create([
            'notifiable_id' => $this->user->id,
            'notifiable_type' => 'users',
            'created_at' => now()->subSeconds(30),
        ]);

        $this->assertEquals('30 seconds ago', $notification->getAge());
    }

    /** @test */
    public function it_can_get_notification_age_in_just_now()
    {
        $notification = Notification::factory()->create([
            'notifiable_id' => $this->user->id,
            'notifiable_type' => 'users',
            'created_at' => now(),
        ]);

        $this->assertEquals('just now', $notification->getAge());
    }

    /** @test */
    public function it_can_get_notification_age_in_future()
    {
        $notification = Notification::factory()->create([
            'notifiable_id' => $this->user->id,
            'notifiable_type' => 'users',
            'created_at' => now()->addHours(1),
        ]);

        $this->assertEquals('1 hour from now', $notification->getAge());
    }
}
