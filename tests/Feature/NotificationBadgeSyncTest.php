<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;

class NotificationBadgeSyncTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['role' => 'doctor']);
    }

    private function createNotification(User $user, array $data = [], $readAt = null): DatabaseNotification
    {
        return DatabaseNotification::create([
            'id' => (string) Str::uuid(),
            'type' => 'App\\Notifications\\TestNotification',
            'notifiable_type' => User::class,
            'notifiable_id' => $user->id,
            'data' => array_merge(['title' => 'T', 'message' => 'M', 'type' => 'appointment_booked'], $data),
            'read_at' => $readAt,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /** @test */
    public function badge_count_increments_immediately_when_notification_reached()
    {
        // Initial badge 0
        $this->assertEquals(0, $this->actingAs($this->user)->getJson('/api/notifications/unread-count')->json('count'));

        // Simulate "reached" = DB insert via notification creation (what Pusher would persist)
        $this->createNotification($this->user, ['title' => 'Reached 1', 'message' => 'msg1', 'type' => 'appointment_booked']);

        // Badge should be 1 immediately - this is what Alpine's unreadCount does after handleNewNotification
        $response = $this->actingAs($this->user)->getJson('/api/notifications/unread-count');
        $response->assertOk();
        $this->assertEquals(1, $response->json('count'));

        // Also dropdown unread_count should match
        $dropdown = $this->actingAs($this->user)->getJson('/api/notifications');
        $this->assertEquals(1, $dropdown->json('unread_count'));
        $this->assertCount(1, $dropdown->json('notifications'));
    }

    /** @test */
    public function badge_syncs_with_dropdown_after_multiple_reached_notifications()
    {
        $this->createNotification($this->user, ['title' => 'N1', 'type' => 'appointment_booked']);
        $this->assertEquals(1, $this->actingAs($this->user)->getJson('/api/notifications/unread-count')->json('count'));

        $this->createNotification($this->user, ['title' => 'N2', 'type' => 'usage_limit_reached', 'message' => 'You have reached your monthly usage limit.']);
        $this->assertEquals(2, $this->actingAs($this->user)->getJson('/api/notifications/unread-count')->json('count'));

        $this->createNotification($this->user, ['title' => 'N3', 'type' => 'system_alert']);
        $badge = $this->actingAs($this->user)->getJson('/api/notifications/unread-count')->json('count');
        $dropdown = $this->actingAs($this->user)->getJson('/api/notifications')->json('unread_count');
        $this->assertEquals(3, $badge);
        $this->assertEquals($badge, $dropdown, 'badge and dropdown unread_count should be identical');
    }

    /** @test */
    public function badge_decrements_when_notification_marked_read_from_dropdown()
    {
        $n1 = $this->createNotification($this->user, ['title' => 'N1']);
        $n2 = $this->createNotification($this->user, ['title' => 'N2']);
        $this->assertEquals(2, $this->actingAs($this->user)->getJson('/api/notifications/unread-count')->json('count'));

        // Simulate clicking notification in dropdown -> markAsRead
        $this->actingAs($this->user)->postJson(route('notifications.mark-read', ['id' => $n1->id]))
            ->assertOk()->assertJson(['success' => true]);

        $this->assertEquals(1, $this->actingAs($this->user)->getJson('/api/notifications/unread-count')->json('count'));
        $this->assertEquals(1, $this->actingAs($this->user)->getJson('/api/notifications')->json('unread_count'));
    }

    /** @test */
    public function badge_resets_to_zero_after_mark_all_read()
    {
        $this->createNotification($this->user, ['title' => 'N1']);
        $this->createNotification($this->user, ['title' => 'N2']);
        $this->createNotification($this->user, ['title' => 'N3']);

        $this->actingAs($this->user)->postJson('/api/notifications/mark-all-read')
            ->assertOk()->assertJson(['success' => true]);

        $this->assertEquals(0, $this->actingAs($this->user)->getJson('/api/notifications/unread-count')->json('count'));
        $this->assertEquals(0, $this->actingAs($this->user)->getJson('/api/notifications')->json('unread_count'));
    }

    /** @test */
    public function badge_handles_mixed_read_unread_correctly()
    {
        $this->createNotification($this->user, ['title' => 'Unread 1'], null);
        $this->createNotification($this->user, ['title' => 'Unread 2'], null);
        $this->createNotification($this->user, ['title' => 'Read 1'], now());

        $this->assertEquals(2, $this->actingAs($this->user)->getJson('/api/notifications/unread-count')->json('count'));
        $dropdown = $this->actingAs($this->user)->getJson('/api/notifications');
        $this->assertEquals(2, $dropdown->json('unread_count'));
        $this->assertCount(3, $dropdown->json('notifications'));
    }

    /** @test */
    public function badge_for_usage_limit_reached_is_counted_and_synced()
    {
        // usage_limit_reached is the specific "reached" type user asked about
        $this->createNotification($this->user, [
            'title' => 'Usage Limit Reached',
            'message' => 'You have reached your monthly usage limit.',
            'type' => 'usage_limit_reached'
        ]);

        $this->assertEquals(1, $this->actingAs($this->user)->getJson('/api/notifications/unread-count')->json('count'));
        $this->assertEquals(1, $this->actingAs($this->user)->getJson('/api/notifications')->json('unread_count'));

        // Second usage notification (different type) should increment to 2, not dedup incorrectly
        $this->createNotification($this->user, [
            'title' => 'Usage Warning',
            'message' => 'You are approaching limit',
            'type' => 'usage_warning'
        ]);
        $this->assertEquals(2, $this->actingAs($this->user)->getJson('/api/notifications/unread-count')->json('count'));
    }

    /** @test */
    public function badge_polling_endpoint_returns_json_structure_expected_by_master_blade()
    {
        $this->createNotification($this->user, ['title' => 'T']);
        $response = $this->actingAs($this->user)->getJson('/api/notifications/unread-count');
        $response->assertOk();
        $response->assertJsonStructure(['count', 'authenticated']);
        $this->assertTrue($response->json('authenticated'));
    }

    /** @test */
    public function rapid_multiple_reached_notifications_all_counted_in_badge()
    {
        for ($i = 0; $i < 5; $i++) {
            $this->createNotification($this->user, ['title' => "Rapid $i", 'message' => "msg $i", 'type' => 'appointment_booked', 'appointment_id' => $i]);
        }
        $this->assertEquals(5, $this->actingAs($this->user)->getJson('/api/notifications/unread-count')->json('count'));
        $this->assertEquals(5, $this->actingAs($this->user)->getJson('/api/notifications')->json('unread_count'));
        // Ensure 99+ logic would cap at UI level - backend still returns true count
        $this->assertEquals(5, $this->actingAs($this->user)->getJson('/api/notifications')->json('unread_count'));
    }
}
