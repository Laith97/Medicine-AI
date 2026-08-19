<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;

class NotificationDropdownFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $otherUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['role' => 'doctor']);
        $this->otherUser = User::factory()->create(['role' => 'patient']);
    }

    private function createNotification(User $user, array $overrides = []): DatabaseNotification
    {
        static $i = 0;
        $i++;

        $defaults = [
            'id' => sprintf(
                '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                mt_rand(0, 0xffff),
                mt_rand(0, 0x0fff) | 0x4000,
                mt_rand(0, 0x3fff) | 0x8000,
                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
            ),
            'notifiable_id' => $user->id,
            'notifiable_type' => User::class,
            'type' => 'appointment_booked',
            'data' => [
                'appointment_id' => $i,
                'title' => 'New Appointment Booked',
                'message' => 'Patient has booked a new appointment',
                'type' => 'appointment_booked',
            ],
            'read_at' => null,
        ];

        $attributes = array_merge($defaults, $overrides);

        return DatabaseNotification::query()->create($attributes);
    }

    // ─── API Notifications Index (used by Alpine's loadNotifications) ───

    /** @test */
    public function api_index_returns_notifications_for_authenticated_user()
    {
        $this->createNotification($this->user);
        $this->createNotification($this->user);

        $response = $this->actingAs($this->user)->getJson('/api/notifications');

        $response->assertOk();
        $response->assertJsonStructure([
            'notifications' => [
                '*' => ['id', 'type', 'title', 'message', 'data', 'read_at', 'created_at'],
            ],
            'unread_count',
        ]);
        $this->assertCount(2, $response->json('notifications'));
        $this->assertEquals(2, $response->json('unread_count'));
    }

    /** @test */
    public function api_index_returns_empty_array_when_no_notifications()
    {
        $response = $this->actingAs($this->user)->getJson('/api/notifications');

        $response->assertOk();
        $this->assertCount(0, $response->json('notifications'));
        $this->assertEquals(0, $response->json('unread_count'));
    }

    /** @test */
    public function api_index_only_returns_own_notifications()
    {
        $this->createNotification($this->user);
        $this->createNotification($this->otherUser);

        $response = $this->actingAs($this->user)->getJson('/api/notifications');

        $this->assertCount(1, $response->json('notifications'));
        $this->assertEquals(1, $response->json('unread_count'));
    }

    /** @test */
    public function api_index_orders_notifications_by_created_at_descending()
    {
        $old = $this->createNotification($this->user, ['created_at' => now()->subDays(3)]);
        $mid = $this->createNotification($this->user, ['created_at' => now()->subDay()]);
        $new = $this->createNotification($this->user, ['created_at' => now()]);

        $response = $this->actingAs($this->user)->getJson('/api/notifications');

        $ids = collect($response->json('notifications'))->pluck('id')->toArray();
        $this->assertEquals([$new->id, $mid->id, $old->id], $ids);
    }

    /** @test */
    public function api_index_limits_to_fifteen_notifications()
    {
        for ($i = 0; $i < 20; $i++) {
            $this->createNotification($this->user);
        }

        $response = $this->actingAs($this->user)->getJson('/api/notifications');

        $this->assertCount(15, $response->json('notifications'));
    }

    /** @test */
    public function api_index_requires_authentication()
    {
        $response = $this->getJson('/api/notifications');

        $response->assertUnauthorized();
    }

    /** @test */
    public function api_index_includes_both_unread_and_read_notifications()
    {
        $this->createNotification($this->user, ['read_at' => null]);
        $this->createNotification($this->user, ['read_at' => now()]);

        $response = $this->actingAs($this->user)->getJson('/api/notifications');

        $this->assertCount(2, $response->json('notifications'));
    }

    /** @test */
    public function api_index_unread_count_reflects_only_unread()
    {
        $this->createNotification($this->user, ['read_at' => null]);
        $this->createNotification($this->user, ['read_at' => null]);
        $this->createNotification($this->user, ['read_at' => now()]);

        $response = $this->actingAs($this->user)->getJson('/api/notifications');

        $this->assertEquals(2, $response->json('unread_count'));
    }

    /** @test */
    public function api_index_returns_data_structure_that_alpine_dropdown_expects()
    {
        $notification = $this->createNotification($this->user, [
            'type' => 'appointment_status_changed',
            'data' => [
                'appointment_id' => 42,
                'title' => 'Appointment Cancelled',
                'message' => 'Your appointment has been cancelled',
                'type' => 'appointment_status_changed',
                'body' => 'Appointment with Dr. Smith has been cancelled',
                'link' => '/appointments/42',
            ],
        ]);

        $response = $this->actingAs($this->user)->getJson('/api/notifications');
        $item = $response->json('notifications.0');

        $this->assertEquals($notification->id, $item['id']);
        $this->assertEquals('appointment_status_changed', $item['type']);
        $this->assertEquals('Appointment Cancelled', $item['title']);
        $this->assertStringContainsString('cancelled', $item['message']);
        $this->assertIsArray($item['data']);
        $this->assertEquals(42, $item['data']['appointment_id']);
        $this->assertNull($item['read_at']);
        $this->assertNotNull($item['created_at']);
    }

    /** @test */
    public function api_index_handles_fallback_when_data_title_missing()
    {
        $this->createNotification($this->user, [
            'data' => ['type' => 'appointment_booked'],
        ]);

        $response = $this->actingAs($this->user)->getJson('/api/notifications');

        $response->assertOk();
        $item = $response->json('notifications.0');
        $this->assertEquals('Notification', $item['title']);
        $this->assertEquals('You have a new notification', $item['message']);
    }

    // ─── Mark as read via web route (used by alpine's markAsRead) ───

    /** @test */
    public function mark_as_read_via_web_route_marks_notification()
    {
        $notification = $this->createNotification($this->user);

        $response = $this->actingAs($this->user)
            ->postJson(route('notifications.mark-read', ['id' => $notification->id]));

        $response->assertOk();
        $response->assertJson(['success' => true]);
        $this->assertNotNull($notification->fresh()->read_at);
    }

    /** @test */
    public function mark_as_read_via_web_route_decrements_unread_count()
    {
        $n1 = $this->createNotification($this->user);
        $this->createNotification($this->user);

        $this->actingAs($this->user)
            ->postJson(route('notifications.mark-read', ['id' => $n1->id]));

        $unread = DatabaseNotification::where('notifiable_id', $this->user->id)
            ->where('notifiable_type', User::class)
            ->whereNull('read_at')
            ->count();
        $this->assertEquals(1, $unread);
    }

    /** @test */
    public function mark_as_read_via_web_route_rejects_other_users_notifications()
    {
        $notification = $this->createNotification($this->otherUser);

        $response = $this->actingAs($this->user)
            ->postJson(route('notifications.mark-read', ['id' => $notification->id]));

        $response->assertNotFound();
    }

    /** @test */
    public function mark_as_read_via_web_route_handles_already_read()
    {
        $notification = $this->createNotification($this->user, ['read_at' => now()]);

        $response = $this->actingAs($this->user)
            ->postJson(route('notifications.mark-read', ['id' => $notification->id]));

        $response->assertOk();
        $response->assertJson(['success' => true]);
    }

    /** @test */
    public function mark_as_read_via_web_route_returns_404_for_nonexistent()
    {
        $response = $this->actingAs($this->user)
            ->postJson(route('notifications.mark-read', ['id' => '00000000-0000-0000-0000-000000000000']));

        $response->assertNotFound();
    }

    // ─── Mark all as read via API route (used by alpine's markAllAsRead) ───

    /** @test */
    public function mark_all_as_read_via_api_marks_all_unread()
    {
        $this->createNotification($this->user, ['read_at' => null]);
        $this->createNotification($this->user, ['read_at' => null]);

        $response = $this->actingAs($this->user)->postJson('/api/notifications/mark-all-read');

        $response->assertOk();
        $response->assertJson(['success' => true]);

        $unread = DatabaseNotification::where('notifiable_id', $this->user->id)
            ->where('notifiable_type', User::class)
            ->whereNull('read_at')
            ->count();
        $this->assertEquals(0, $unread);
    }

    /** @test */
    public function mark_all_as_read_via_api_only_affects_current_user()
    {
        $this->createNotification($this->user, ['read_at' => null]);
        $this->createNotification($this->otherUser, ['read_at' => null]);

        $this->actingAs($this->user)->postJson('/api/notifications/mark-all-read');

        $otherUnread = DatabaseNotification::where('notifiable_id', $this->otherUser->id)
            ->where('notifiable_type', User::class)
            ->whereNull('read_at')
            ->count();
        $this->assertEquals(1, $otherUnread);
    }

    /** @test */
    public function mark_all_as_read_via_api_handles_no_unread()
    {
        $this->createNotification($this->user, ['read_at' => now()]);

        $response = $this->actingAs($this->user)->postJson('/api/notifications/mark-all-read');

        $response->assertOk();
        $response->assertJson(['success' => true]);
    }

    /** @test */
    public function mark_all_as_read_via_api_requires_auth()
    {
        $response = $this->postJson('/api/notifications/mark-all-read');

        $response->assertUnauthorized();
    }

    // ─── Unread count endpoint ───

    /** @test */
    public function unread_count_endpoint_returns_correct_count()
    {
        $this->createNotification($this->user, ['read_at' => null]);
        $this->createNotification($this->user, ['read_at' => null]);
        $this->createNotification($this->user, ['read_at' => now()]);

        $response = $this->actingAs($this->user)->getJson('/api/notifications/unread-count');

        $response->assertOk();
        $response->assertJson(['count' => 2]);
    }

    /** @test */
    public function unread_count_endpoint_returns_zero_when_none_unread()
    {
        $response = $this->actingAs($this->user)->getJson('/api/notifications/unread-count');

        $response->assertOk();
        $response->assertJson(['count' => 0]);
    }

    // ─── Complex data payload ───

    /** @test */
    public function api_index_handles_notification_with_multiple_data_fields()
    {
        $this->createNotification($this->user, [
            'data' => [
                'appointment_id' => 99,
                'title' => 'Appointment Confirmed',
                'message' => 'Your appointment has been confirmed',
                'type' => 'appointment_status_changed',
                'link' => '/appointments/99',
                'link_text' => 'View',
                'old_status' => 'pending',
                'new_status' => 'confirmed',
                'doctor_name' => 'Dr. Laith',
                'patient_name' => 'John Doe',
            ],
        ]);

        $response = $this->actingAs($this->user)->getJson('/api/notifications');
        $item = $response->json('notifications.0');

        $this->assertEquals(99, $item['data']['appointment_id']);
        $this->assertEquals('confirmed', $item['data']['new_status']);
        $this->assertEquals('Dr. Laith', $item['data']['doctor_name']);
        $this->assertArrayHasKey('link_text', $item['data']);
    }

    /** @test */
    public function api_index_returns_different_notification_types()
    {
        $types = ['appointment_booked', 'appointment_cancelled', 'appointment_status_changed',
                   'diagnosis_submitted', 'review_submitted', 'system_alert'];

        foreach ($types as $type) {
            $this->createNotification($this->user, ['type' => $type]);
        }

        $response = $this->actingAs($this->user)->getJson('/api/notifications');

        $returnedTypes = collect($response->json('notifications'))->pluck('type')->toArray();
        foreach ($types as $type) {
            $this->assertContains($type, $returnedTypes);
        }
    }

    // ─── Increment/decrement verification flow ───

    /** @test */
    public function unread_count_reflects_real_time_create_and_mark_read_flow()
    {
        $this->assertEquals(0, $this->actingAs($this->user)->getJson('/api/notifications')->json('unread_count'));

        $n1 = $this->createNotification($this->user);
        $response = $this->actingAs($this->user)->getJson('/api/notifications');
        $this->assertEquals(1, $response->json('unread_count'), 'Count should be 1 after creating one unread');

        $n2 = $this->createNotification($this->user);
        $response = $this->actingAs($this->user)->getJson('/api/notifications');
        $this->assertEquals(2, $response->json('unread_count'), 'Count should be 2 after creating second unread');

        $this->actingAs($this->user)
            ->postJson(route('notifications.mark-read', ['id' => $n1->id]));
        $response = $this->actingAs($this->user)->getJson('/api/notifications');
        $this->assertEquals(1, $response->json('unread_count'), 'Count should be 1 after marking one read');

        $this->actingAs($this->user)->postJson('/api/notifications/mark-all-read');
        $response = $this->actingAs($this->user)->getJson('/api/notifications');
        $this->assertEquals(0, $response->json('unread_count'), 'Count should be 0 after marking all read');
    }

    /** @test */
    public function notification_has_title_and_message_at_top_level_for_alpine()
    {
        $this->createNotification($this->user, [
            'data' => [
                'appointment_id' => 5,
                'title' => 'Top Level Title',
                'message' => 'Top Level Message',
                'type' => 'appointment_booked',
            ],
        ]);

        $response = $this->actingAs($this->user)->getJson('/api/notifications');
        $item = $response->json('notifications.0');

        $this->assertEquals('Top Level Title', $item['title']);
        $this->assertEquals('Top Level Message', $item['message']);
    }

    /** @test */
    public function mark_as_read_via_web_route_returns_json_content_type()
    {
        $notification = $this->createNotification($this->user);

        $response = $this->actingAs($this->user)
            ->post(route('notifications.mark-read', ['id' => $notification->id]));

        $response->assertHeader('Content-Type', 'application/json');
    }

    /** @test */
    public function mark_all_as_read_preserves_read_timestamps()
    {
        $oldRead = $this->createNotification($this->user, ['read_at' => now()->subDay()]);
        $unread = $this->createNotification($this->user, ['read_at' => null]);

        $this->actingAs($this->user)->postJson('/api/notifications/mark-all-read');

        $this->assertNotNull($oldRead->fresh()->read_at);
        $this->assertNotNull($unread->fresh()->read_at);
    }
}
