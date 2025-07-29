<?php

namespace Tests\Unit\Services;

use App\Services\NotificationService;
use App\Models\User;
use App\Models\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class NotificationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $notificationService;
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->notificationService = new NotificationService();
        $this->user = User::factory()->create([
            'email' => 'test@example.com',
            'name' => 'Test User'
        ]);
    }

    public function test_notification_service_can_be_instantiated()
    {
        $this->assertInstanceOf(NotificationService::class, $this->notificationService);
    }

    public function test_create_notification()
    {
        $notificationData = [
            'title' => 'Test Notification',
            'message' => 'This is a test notification',
            'type' => 'info',
            'action_url' => '/dashboard'
        ];

        $notification = $this->notificationService->createNotification($this->user, $notificationData);

        $this->assertInstanceOf(Notification::class, $notification);
        $this->assertEquals($this->user->id, $notification->user_id);
        $this->assertEquals('Test Notification', $notification->title);
        $this->assertEquals('This is a test notification', $notification->message);
        $this->assertEquals('info', $notification->type);
        $this->assertFalse($notification->is_read);
    }

    public function test_send_email_notification()
    {
        Mail::fake();

        $notificationData = [
            'title' => 'Email Notification',
            'message' => 'This notification will be sent via email',
            'type' => 'warning',
            'send_email' => true
        ];

        $result = $this->notificationService->sendNotification($this->user, $notificationData);

        $this->assertTrue($result);

        // Verify email was sent
        Mail::assertSent(function ($mail) {
            return $mail->hasTo($this->user->email) &&
                   str_contains($mail->build()->subject, 'Email Notification');
        });

        // Verify notification was created in database
        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->user->id,
            'title' => 'Email Notification',
            'type' => 'warning'
        ]);
    }

    public function test_send_push_notification()
    {
        $notificationData = [
            'title' => 'Push Notification',
            'message' => 'This is a push notification',
            'type' => 'success',
            'send_push' => true
        ];

        $result = $this->notificationService->sendNotification($this->user, $notificationData);

        $this->assertTrue($result);

        // Verify notification was created
        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->user->id,
            'title' => 'Push Notification',
            'type' => 'success'
        ]);
    }

    public function test_send_sms_notification()
    {
        $this->user->phone = '+1234567890';
        $this->user->save();

        $notificationData = [
            'title' => 'SMS Notification',
            'message' => 'This is an SMS notification',
            'type' => 'urgent',
            'send_sms' => true
        ];

        $result = $this->notificationService->sendNotification($this->user, $notificationData);

        $this->assertTrue($result);

        // Verify notification was created
        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->user->id,
            'title' => 'SMS Notification',
            'type' => 'urgent'
        ]);
    }

    public function test_get_user_notifications()
    {
        // Create test notifications
        Notification::factory()->count(5)->create([
            'user_id' => $this->user->id,
            'is_read' => false
        ]);

        Notification::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'is_read' => true
        ]);

        $notifications = $this->notificationService->getUserNotifications($this->user);

        $this->assertCount(8, $notifications);
    }

    public function test_get_unread_notifications()
    {
        // Create test notifications
        Notification::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'is_read' => false
        ]);

        Notification::factory()->count(2)->create([
            'user_id' => $this->user->id,
            'is_read' => true
        ]);

        $unreadNotifications = $this->notificationService->getUnreadNotifications($this->user);

        $this->assertCount(3, $unreadNotifications);
        foreach ($unreadNotifications as $notification) {
            $this->assertFalse($notification->is_read);
        }
    }

    public function test_mark_notification_as_read()
    {
        $notification = Notification::factory()->create([
            'user_id' => $this->user->id,
            'is_read' => false
        ]);

        $result = $this->notificationService->markAsRead($notification);

        $this->assertTrue($result);
        $notification->refresh();
        $this->assertTrue($notification->is_read);
        $this->assertNotNull($notification->read_at);
    }

    public function test_mark_all_notifications_as_read()
    {
        // Create unread notifications
        Notification::factory()->count(5)->create([
            'user_id' => $this->user->id,
            'is_read' => false
        ]);

        $result = $this->notificationService->markAllAsRead($this->user);

        $this->assertTrue($result);

        // Verify all notifications are marked as read
        $unreadCount = Notification::where('user_id', $this->user->id)
                                 ->where('is_read', false)
                                 ->count();
        $this->assertEquals(0, $unreadCount);
    }

    public function test_delete_notification()
    {
        $notification = Notification::factory()->create([
            'user_id' => $this->user->id
        ]);

        $result = $this->notificationService->deleteNotification($notification);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('notifications', ['id' => $notification->id]);
    }

    public function test_delete_old_notifications()
    {
        // Create old notifications (older than 30 days)
        Notification::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'created_at' => now()->subDays(35)
        ]);

        // Create recent notifications
        Notification::factory()->count(2)->create([
            'user_id' => $this->user->id,
            'created_at' => now()->subDays(10)
        ]);

        $deletedCount = $this->notificationService->deleteOldNotifications(30);

        $this->assertEquals(3, $deletedCount);

        // Verify recent notifications still exist
        $remainingCount = Notification::where('user_id', $this->user->id)->count();
        $this->assertEquals(2, $remainingCount);
    }

    public function test_send_bulk_notification()
    {
        $users = User::factory()->count(5)->create();

        $notificationData = [
            'title' => 'Bulk Notification',
            'message' => 'This is sent to multiple users',
            'type' => 'announcement'
        ];

        $result = $this->notificationService->sendBulkNotification($users, $notificationData);

        $this->assertTrue($result);

        // Verify notifications were created for all users
        foreach ($users as $user) {
            $this->assertDatabaseHas('notifications', [
                'user_id' => $user->id,
                'title' => 'Bulk Notification',
                'type' => 'announcement'
            ]);
        }
    }

    public function test_send_scheduled_notification()
    {
        Queue::fake();

        $notificationData = [
            'title' => 'Scheduled Notification',
            'message' => 'This notification is scheduled',
            'type' => 'reminder',
            'scheduled_at' => now()->addHours(2)
        ];

        $result = $this->notificationService->scheduleNotification($this->user, $notificationData);

        $this->assertTrue($result);

        // Verify job was queued
        Queue::assertPushed(\App\Jobs\SendScheduledNotification::class);
    }

    public function test_get_notification_preferences()
    {
        $preferences = $this->notificationService->getNotificationPreferences($this->user);

        $this->assertIsArray($preferences);
        $this->assertArrayHasKey('email_notifications', $preferences);
        $this->assertArrayHasKey('push_notifications', $preferences);
        $this->assertArrayHasKey('sms_notifications', $preferences);
    }

    public function test_update_notification_preferences()
    {
        $newPreferences = [
            'email_notifications' => false,
            'push_notifications' => true,
            'sms_notifications' => false,
            'notification_frequency' => 'daily'
        ];

        $result = $this->notificationService->updateNotificationPreferences($this->user, $newPreferences);

        $this->assertTrue($result);

        // Verify preferences were updated
        $updatedPreferences = $this->notificationService->getNotificationPreferences($this->user);
        $this->assertFalse($updatedPreferences['email_notifications']);
        $this->assertTrue($updatedPreferences['push_notifications']);
        $this->assertEquals('daily', $updatedPreferences['notification_frequency']);
    }

    public function test_send_appointment_reminder()
    {
        $appointmentData = [
            'appointment_date' => now()->addDay(),
            'doctor_name' => 'Dr. Smith',
            'patient_name' => 'John Doe'
        ];

        $result = $this->notificationService->sendAppointmentReminder($this->user, $appointmentData);

        $this->assertTrue($result);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->user->id,
            'type' => 'appointment_reminder'
        ]);
    }

    public function test_send_subscription_expiry_warning()
    {
        $expiryData = [
            'expires_at' => now()->addDays(3),
            'plan_name' => 'Premium Plan'
        ];

        $result = $this->notificationService->sendSubscriptionExpiryWarning($this->user, $expiryData);

        $this->assertTrue($result);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->user->id,
            'type' => 'subscription_expiry'
        ]);
    }

    public function test_send_usage_limit_warning()
    {
        $usageData = [
            'current_usage' => 850,
            'limit' => 1000,
            'usage_type' => 'tokens'
        ];

        $result = $this->notificationService->sendUsageLimitWarning($this->user, $usageData);

        $this->assertTrue($result);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->user->id,
            'type' => 'usage_warning'
        ]);
    }

    public function test_send_payment_failed_notification()
    {
        $paymentData = [
            'amount' => 25.00,
            'payment_method' => 'card ending in 4242',
            'retry_date' => now()->addDays(3)
        ];

        $result = $this->notificationService->sendPaymentFailedNotification($this->user, $paymentData);

        $this->assertTrue($result);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->user->id,
            'type' => 'payment_failed'
        ]);
    }

    public function test_get_notification_statistics()
    {
        // Create various types of notifications
        Notification::factory()->count(10)->create([
            'user_id' => $this->user->id,
            'type' => 'info',
            'is_read' => false
        ]);

        Notification::factory()->count(5)->create([
            'user_id' => $this->user->id,
            'type' => 'warning',
            'is_read' => true
        ]);

        $stats = $this->notificationService->getNotificationStatistics($this->user);

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total_notifications', $stats);
        $this->assertArrayHasKey('unread_notifications', $stats);
        $this->assertArrayHasKey('notifications_by_type', $stats);
        $this->assertEquals(15, $stats['total_notifications']);
        $this->assertEquals(10, $stats['unread_notifications']);
    }

    public function test_notification_respects_user_preferences()
    {
        // Set user preferences to disable email notifications
        $this->notificationService->updateNotificationPreferences($this->user, [
            'email_notifications' => false,
            'push_notifications' => true
        ]);

        Mail::fake();

        $notificationData = [
            'title' => 'Test Notification',
            'message' => 'This should not send email',
            'type' => 'info',
            'send_email' => true
        ];

        $this->notificationService->sendNotification($this->user, $notificationData);

        // Email should not be sent due to user preferences
        Mail::assertNotSent(\App\Mail\NotificationMail::class);

        // But notification should still be created in database
        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->user->id,
            'title' => 'Test Notification'
        ]);
    }

    public function test_notification_templates()
    {
        $templateData = [
            'user_name' => $this->user->name,
            'appointment_date' => now()->addDay()->format('M j, Y g:i A')
        ];

        $message = $this->notificationService->renderTemplate('appointment_reminder', $templateData);

        $this->assertStringContains($this->user->name, $message);
        $this->assertStringContains('appointment', strtolower($message));
    }

    public function test_notification_rate_limiting()
    {
        // Send multiple notifications quickly
        for ($i = 0; $i < 10; $i++) {
            $this->notificationService->sendNotification($this->user, [
                'title' => "Notification {$i}",
                'message' => 'Rate limit test',
                'type' => 'info'
            ]);
        }

        // Should respect rate limiting (e.g., max 5 notifications per minute)
        $recentNotifications = Notification::where('user_id', $this->user->id)
                                         ->where('created_at', '>=', now()->subMinute())
                                         ->count();

        $this->assertLessThanOrEqual(5, $recentNotifications);
    }
}
