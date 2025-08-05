<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\NotificationPreference;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    protected $doctor;
    protected $patient;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test users
        $this->doctor = User::factory()->create([
            'role' => 'doctor',
            'name' => 'Dr. John Smith',
            'email' => 'doctor@example.com',
        ]);

        $this->patient = User::factory()->create([
            'role' => 'patient',
            'name' => 'Jane Doe',
            'email' => 'patient@example.com',
        ]);

        // Don't create notification preferences here - let getOrCreateNotificationPreferences() create them with defaults
    }

    /** @test */
    public function it_creates_database_notification()
    {
        // Create test notification data
        $notificationData = [
            'title' => 'Test Notification',
            'message' => 'This is a test notification',
            'icon' => 'info',
            'link' => '/test-link',
        ];

        // Send notification to doctor
        $this->doctor->notify(new \App\Notifications\TestNotification($notificationData));

        // Assert notification was created
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $this->doctor->id,
            'notifiable_type' => 'App\\Models\\User',
            'type' => 'App\\Notifications\\TestNotification',
        ]);
    }

    /** @test */
    public function it_marks_notification_as_read()
    {
        // Create and send notification
        $notificationData = [
            'title' => 'Test Notification',
            'message' => 'This is a test notification',
            'icon' => 'info',
            'link' => '/test-link',
        ];

        $notification = new \App\Notifications\TestNotification($notificationData);
        $this->doctor->notify($notification);

        // Get the notification from database
        $dbNotification = \Illuminate\Notifications\DatabaseNotification::where('notifiable_id', $this->doctor->id)
            ->where('type', 'App\\Notifications\\TestNotification')
            ->first();

        // Mark as read
        $dbNotification->markAsRead();

        // Assert notification is marked as read
        $this->assertNotNull($dbNotification->fresh()->read_at);
    }

    /** @test */
    public function it_marks_all_notifications_as_read()
    {
        // Create and send multiple notifications
        $notificationData1 = [
            'title' => 'Test Notification 1',
            'message' => 'This is a test notification',
            'icon' => 'info',
            'link' => '/test-link',
        ];

        $notificationData2 = [
            'title' => 'Test Notification 2',
            'message' => 'This is another test notification',
            'icon' => 'info',
            'link' => '/test-link',
        ];

        $this->doctor->notify(new \App\Notifications\TestNotification($notificationData1));
        $this->doctor->notify(new \App\Notifications\TestNotification($notificationData2));

        // Assert we have 2 unread notifications
        $this->assertEquals(2, $this->doctor->unreadNotifications()->count());

        // Mark all as read
        $this->doctor->markAllNotificationsAsRead();

        // Assert all notifications are marked as read
        $this->assertEquals(0, $this->doctor->unreadNotifications()->count());
    }

    /** @test */
    public function it_deletes_notification()
    {
        // Create and send notification
        $notificationData = [
            'title' => 'Test Notification',
            'message' => 'This is a test notification',
            'icon' => 'info',
            'link' => '/test-link',
        ];

        $notification = new \App\Notifications\TestNotification($notificationData);
        $this->doctor->notify($notification);

        // Assert notification exists
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $this->doctor->id,
            'type' => 'App\\Notifications\\TestNotification',
        ]);

        // Delete notification
        $dbNotification = \Illuminate\Notifications\DatabaseNotification::where('notifiable_id', $this->doctor->id)
            ->where('type', 'App\\Notifications\\TestNotification')
            ->first();

        $dbNotification->delete();

        // Assert notification is deleted
        $this->assertDatabaseMissing('notifications', [
            'notifiable_id' => $this->doctor->id,
            'type' => 'App\\Notifications\\TestNotification',
        ]);
    }

    /** @test */
    public function it_returns_unread_count()
    {
        // Create and send notification
        $notificationData = [
            'title' => 'Test Notification',
            'message' => 'This is a test notification',
            'icon' => 'info',
            'link' => '/test-link',
        ];

        $this->doctor->notify(new \App\Notifications\TestNotification($notificationData));

        // Assert correct unread count
        $this->assertEquals(1, $this->doctor->unreadNotificationsCount());
    }

    /** @test */
    public function user_has_notification_relationships()
    {
        // Create a notification first
        $notificationData = [
            'title' => 'Test Notification',
            'message' => 'This is a test notification',
            'icon' => 'info',
            'link' => '/test-link',
        ];

        $this->doctor->notify(new \App\Notifications\TestNotification($notificationData));

        // Test notification relationships
        $this->assertInstanceOf(\Illuminate\Notifications\DatabaseNotification::class, $this->doctor->notifications()->first());
        $this->assertInstanceOf(\Illuminate\Notifications\DatabaseNotification::class, $this->doctor->unreadNotifications()->first());
        $this->assertNull($this->doctor->readNotifications()->first()); // Should be null since we haven't marked any as read
    }

    /** @test */
    public function user_can_mark_all_notifications_as_read()
    {
        // Create and send multiple notifications
        $notificationData1 = [
            'title' => 'Test Notification 1',
            'message' => 'This is a test notification',
            'icon' => 'info',
            'link' => '/test-link',
        ];

        $notificationData2 = [
            'title' => 'Test Notification 2',
            'message' => 'This is another test notification',
            'icon' => 'info',
            'link' => '/test-link',
        ];

        $this->doctor->notify(new \App\Notifications\TestNotification($notificationData1));
        $this->doctor->notify(new \App\Notifications\TestNotification($notificationData2));

        // Assert we have 2 unread notifications
        $this->assertEquals(2, $this->doctor->unreadNotifications()->count());

        // Mark all as read using model method
        $this->doctor->markAllNotificationsAsRead();

        // Assert all notifications are marked as read
        $this->assertEquals(0, $this->doctor->unreadNotifications()->count());
    }

    /** @test */
    public function user_has_notification_preferences()
    {
        // Test getOrCreateNotificationPreferences method
        $retrievedPreferences = $this->doctor->fresh()->getOrCreateNotificationPreferences();
        $this->assertInstanceOf(NotificationPreference::class, $retrievedPreferences);

        // Test wantsNotification method
        $this->assertTrue($this->doctor->fresh()->wantsNotification('appointment_booked'));
        $this->assertTrue($this->doctor->fresh()->wantsNotification('diagnosis_submitted'));
        $this->assertTrue($this->doctor->fresh()->wantsNotification('review_submitted'));

        // Test wantsNotificationChannel method
        $this->assertTrue($this->doctor->fresh()->wantsNotificationChannel('email'));
        $this->assertTrue($this->doctor->fresh()->wantsNotificationChannel('in_app'));
        $this->assertFalse($this->doctor->fresh()->wantsNotificationChannel('sms'));
    }

    /** @test */
    public function it_handles_notification_preferences_correctly()
    {
        // Create specific preferences for testing
        $preferences = $this->doctor->getOrCreateNotificationPreferences();

        // The getOrCreateNotificationPreferences method creates specific defaults
        $this->assertTrue($preferences->email_enabled);
        $this->assertTrue($preferences->in_app_enabled);
        $this->assertFalse($preferences->sms_enabled);
        $this->assertTrue($preferences->appointment_booked);
        $this->assertTrue($preferences->diagnosis_submitted);
        $this->assertTrue($preferences->review_submitted);
    }
}
