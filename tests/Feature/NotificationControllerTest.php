<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Notification;
use App\Models\NotificationPreference;
use Illuminate\Foundation\Testing\RefreshDatabase;

class NotificationControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['role' => 'patient']);
        $this->admin = User::factory()->create(['role' => 'admin']);

        // Create some test notifications
        Notification::factory()->count(5)->create([
            'notifiable_id' => $this->user->id,
            'notifiable_type' => 'users',
            'read_at' => null,
        ]);

        Notification::factory()->count(3)->create([
            'notifiable_id' => $this->user->id,
            'notifiable_type' => 'users',
            'read_at' => now(),
        ]);
    }

    /** @test */
    public function it_requires_authentication()
    {
        $response = $this->get(route('notifications.index'));
        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function it_shows_notifications_page()
    {
        $response = $this->actingAs($this->user)->get(route('notifications.index'));

        $response->assertStatus(200);
        $response->assertViewIs('notifications.index');
        $response->assertViewHas('notifications');
    }

    /** @test */
    public function it_shows_notifications_with_pagination()
    {
        // Create more notifications to test pagination
        Notification::factory()->count(15)->create([
            'notifiable_id' => $this->user->id,
            'notifiable_type' => 'users',
            'read_at' => null,
        ]);

        $response = $this->actingAs($this->user)->get(route('notifications.index'));

        $response->assertStatus(200);
        $response->assertViewHas('notifications', function ($notifications) {
            return $notifications->count() === 15; // Should show 15 notifications per page
        });
    }

    /** @test */
    public function it_shows_unread_notifications_count()
    {
        $response = $this->actingAs($this->user)->get(route('notifications.index'));

        $response->assertViewHas('unreadCount', 5);
    }

    /** @test */
    public function it_can_mark_notification_as_read()
    {
        $notification = Notification::where('notifiable_id', $this->user->id)
            ->whereNull('read_at')
            ->first();

        $response = $this->actingAs($this->user)->post(route('notifications.mark-read', $notification));

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Notification marked as read');

        $this->assertNotNull($notification->fresh()->read_at);
    }

    /** @test */
    public function it_can_mark_all_notifications_as_read()
    {
        $response = $this->actingAs($this->user)->post(route('notifications.mark-all-read'));

        $response->assertRedirect();
        $response->assertSessionHas('success', 'All notifications marked as read');

        $this->assertEquals(0, Notification::where('notifiable_id', $this->user->id)
            ->whereNull('read_at')
            ->count());
    }

    /** @test */
    public function it_can_delete_notification()
    {
        $notification = Notification::where('notifiable_id', $this->user->id)
            ->whereNull('read_at')
            ->first();

        $response = $this->actingAs($this->user)->delete(route('notifications.destroy', $notification));

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Notification deleted');

        $this->assertSoftDeleted($notification);
    }

    /** @test */
    public function it_can_bulk_delete_notifications()
    {
        $unreadNotifications = Notification::where('notifiable_id', $this->user->id)
            ->whereNull('read_at')
            ->pluck('id')
            ->toArray();

        $response = $this->actingAs($this->user)->post(route('notifications.bulk-delete'), [
            'notifications' => $unreadNotifications,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Selected notifications deleted');

        $this->assertEquals(0, Notification::where('notifiable_id', $this->user->id)
            ->whereNull('read_at')
            ->count());
    }

    /** @test */
    public function it_shows_notification_settings_page()
    {
        $response = $this->actingAs($this->user)->get(route('notifications.settings'));

        $response->assertStatus(200);
        $response->assertViewIs('notifications.settings');
        $response->assertViewHas('notificationTypes');
        $response->assertViewHas('preferences');
    }

    /** @test */
    public function it_can_update_notification_preferences()
    {
        $preferences = [
            'appointment_booked' => [
                'email' => true,
                'sms' => false,
                'in_app' => true,
            ],
            'diagnosis_submitted' => [
                'email' => true,
                'sms' => true,
                'in_app' => true,
            ],
        ];

        $response = $this->actingAs($this->user)->put(route('notifications.preferences.update'), [
            'preferences' => $preferences,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Notification preferences updated');

        // Verify preferences were saved
        foreach ($preferences as $type => $channels) {
            foreach ($channels as $channel => $enabled) {
                $preference = NotificationPreference::where('user_id', $this->user->id)
                    ->where('notification_type', $type)
                    ->where('channel', $channel)
                    ->first();

                $this->assertNotNull($preference);
                $this->assertEquals($enabled, $preference->enabled);
            }
        }
    }

    /** @test */
    public function it_can_get_unread_notifications_count()
    {
        $response = $this->actingAs($this->user)->get(route('notifications.unread-count'));

        $response->assertJson(['count' => 5]);
    }

    /** @test */
    public function it_can_get_notifications_dropdown()
    {
        $response = $this->actingAs($this->user)->get(route('notifications.dropdown'));

        $response->assertStatus(200);
        $response->assertViewIs('notifications.dropdown');
        $response->assertViewHas('notifications');
        $response->assertViewHas('unreadCount');
    }

    /** @test */
    public function it_shows_limited_notifications_in_dropdown()
    {
        // Create more notifications
        Notification::factory()->count(10)->create([
            'notifiable_id' => $this->user->id,
            'notifiable_type' => 'users',
            'read_at' => null,
        ]);

        $response = $this->actingAs($this->user)->get(route('notifications.dropdown'));

        $response->assertStatus(200);
        $response->assertViewHas('notifications', function ($notifications) {
            return $notifications->count() === 10; // Should show all unread notifications
        });
    }

    /** @test */
    public function it_can_filter_notifications_by_type()
    {
        // Create notifications of different types
        Notification::factory()->create([
            'notifiable_id' => $this->user->id,
            'notifiable_type' => 'users',
            'type' => 'appointment_booked',
            'read_at' => null,
        ]);

        Notification::factory()->create([
            'notifiable_id' => $this->user->id,
            'notifiable_type' => 'users',
            'type' => 'diagnosis_submitted',
            'read_at' => null,
        ]);

        $response = $this->actingAs($this->user)->get(route('notifications.index', ['type' => 'appointment_booked']));

        $response->assertStatus(200);
        $response->assertViewHas('notifications', function ($notifications) {
            return $notifications->count() === 1 && $notifications->first()->type === 'appointment_booked';
        });
    }

    /** @test */
    public function it_can_filter_notifications_by_read_status()
    {
        $response = $this->actingAs($this->user)->get(route('notifications.index', ['read' => 'unread']));

        $response->assertStatus(200);
        $response->assertViewHas('notifications', function ($notifications) {
            return $notifications->count() === 5; // Should show only unread notifications
        });
    }

    /** @test */
    public function it_can_search_notifications()
    {
        Notification::factory()->create([
            'notifiable_id' => $this->user->id,
            'notifiable_type' => 'users',
            'type' => 'appointment_booked',
            'data' => json_encode(['title' => 'Test Appointment Notification']),
            'read_at' => null,
        ]);

        $response = $this->actingAs($this->user)->get(route('notifications.index', ['search' => 'Test Appointment']));

        $response->assertStatus(200);
        $response->assertViewHas('notifications', function ($notifications) {
            return $notifications->count() === 1;
        });
    }

    /** @test */
    public function it_orders_notifications_by_created_at_descending()
    {
        $oldestNotification = Notification::factory()->create([
            'notifiable_id' => $this->user->id,
            'notifiable_type' => 'users',
            'created_at' => now()->subDays(5),
            'read_at' => null,
        ]);

        $newestNotification = Notification::factory()->create([
            'notifiable_id' => $this->user->id,
            'notifiable_type' => 'users',
            'created_at' => now(),
            'read_at' => null,
        ]);

        $response = $this->actingAs($this->user)->get(route('notifications.index'));

        $response->assertStatus(200);
        $notifications = $response->viewData('notifications');
        $this->assertEquals($newestNotification->id, $notifications->first()->id);
        $this->assertEquals($oldestNotification->id, $notifications->last()->id);
    }

    /** @test */
    public function it_can_access_notification_settings_for_different_user_roles()
    {
        // Test as patient
        $response = $this->actingAs($this->user)->get(route('notifications.settings'));
        $response->assertStatus(200);

        // Test as doctor
        $doctor = User::factory()->create(['role' => 'doctor']);
        $response = $this->actingAs($doctor)->get(route('notifications.settings'));
        $response->assertStatus(200);

        // Test as admin
        $response = $this->actingAs($this->admin)->get(route('notifications.settings'));
        $response->assertStatus(200);
    }

    /** @test */
    public function it_prevents_unauthorized_access_to_other_users_notifications()
    {
        $otherUser = User::factory()->create();
        $notification = Notification::factory()->create([
            'notifiable_id' => $otherUser->id,
            'notifiable_type' => 'users',
            'read_at' => null,
        ]);

        // Try to mark another user's notification as read
        $response = $this->actingAs($this->user)->post(route('notifications.mark-read', $notification));
        $response->assertForbidden();

        // Try to delete another user's notification
        $response = $this->actingAs($this->user)->delete(route('notifications.destroy', $notification));
        $response->assertForbidden();
    }

    /** @test */
    public function it_handles_empty_notifications_gracefully()
    {
        // Clear all notifications for the user
        Notification::where('notifiable_id', $this->user->id)->delete();

        $response = $this->actingAs($this->user)->get(route('notifications.index'));

        $response->assertStatus(200);
        $response->assertViewHas('notifications', function ($notifications) {
            return $notifications->count() === 0;
        });
    }
}
