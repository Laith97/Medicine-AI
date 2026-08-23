<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;

class NotificationReachedDropdownTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected NotificationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['role' => 'doctor']);
        $this->service = new NotificationService();
    }

    private function createDbNotification(User $user, array $dataOverrides = [], string $type = 'App\\Notifications\\TestNotification'): DatabaseNotification
    {
        return DatabaseNotification::create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'type' => $type,
            'notifiable_type' => User::class,
            'notifiable_id' => $user->id,
            'data' => array_merge([
                'title' => 'Test Title',
                'message' => 'Test message body',
                'type' => 'appointment_booked',
                'icon' => 'bell',
                'link' => '/appointments/1',
            ], $dataOverrides),
            'read_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /** @test */
    public function reached_notification_appears_in_dropdown_after_bell_click_simulation()
    {
        // Simulate "reached" = notification created via service (like Pusher broadcast persisted)
        $notification = $this->service->createNotification($this->user, [
            'title' => 'New Appointment Booked',
            'message' => 'Patient has booked a new appointment',
            'type' => 'appointment_booked',
            'action_url' => '/appointments/42',
        ]);

        // Simulate bell click => GET /api/notifications (what Alpine's loadNotifications does)
        $response = $this->actingAs($this->user)->getJson('/api/notifications');

        $response->assertOk();
        $this->assertCount(1, $response->json('notifications'));
        $item = $response->json('notifications.0');
        $this->assertEquals('New Appointment Booked', $item['title']);
        $this->assertEquals('Patient has booked a new appointment', $item['message']);
        $this->assertEquals('appointment_booked', $item['data']['type']);
        $this->assertEquals($notification->id, $item['id']);
        $this->assertEquals(1, $response->json('unread_count'));
    }

    /** @test */
    public function usage_limit_reached_notification_shows_correctly_in_dropdown()
    {
        // Directly test the "reached" type the user asked about
        $notification = $this->service->sendUsageNotification($this->user, 'usage_limit_reached');

        $this->assertEquals('Usage Limit Reached', $notification->title);
        $this->assertEquals('You have reached your monthly usage limit.', $notification->message);

        $response = $this->actingAs($this->user)->getJson('/api/notifications');
        $response->assertOk();
        $item = $response->json('notifications.0');
        $this->assertEquals('Usage Limit Reached', $item['title']);
        $this->assertEquals('You have reached your monthly usage limit.', $item['message']);
        // type stored as warning for usage_* (see NotificationService.php:117)
        $this->assertEquals('warning', $item['data']['type']);
    }

    /** @test */
    public function usage_warning_notification_shows_correctly_in_dropdown()
    {
        $notification = $this->service->sendUsageNotification($this->user, 'usage_warning');
        $this->assertEquals('Usage Warning', $notification->title);

        $response = $this->actingAs($this->user)->getJson('/api/notifications');
        $item = $response->json('notifications.0');
        $this->assertEquals('Usage Warning', $item['title']);
    }

    /** @test */
    public function multiple_different_types_all_appear_in_dropdown_ordered_desc()
    {
        // Create 3 distinct reached notifications
        $older = DatabaseNotification::create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'type' => 'App\\Notifications\\TestNotification',
            'notifiable_type' => User::class,
            'notifiable_id' => $this->user->id,
            'data' => ['title' => 'Oldest', 'message' => 'old', 'type' => 'appointment_booked'],
            'read_at' => null,
            'created_at' => now()->subHours(3),
            'updated_at' => now()->subHours(3),
        ]);
        $middle = DatabaseNotification::create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'type' => 'App\\Notifications\\TestNotification',
            'notifiable_type' => User::class,
            'notifiable_id' => $this->user->id,
            'data' => ['title' => 'Middle', 'message' => 'middle', 'type' => 'usage_limit_reached'],
            'read_at' => null,
            'created_at' => now()->subHours(2),
            'updated_at' => now()->subHours(2),
        ]);
        $newest = DatabaseNotification::create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'type' => 'App\\Notifications\\TestNotification',
            'notifiable_type' => User::class,
            'notifiable_id' => $this->user->id,
            'data' => ['title' => 'Newest System Alert', 'message' => 'system msg', 'type' => 'system_alert'],
            'read_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($this->user)->getJson('/api/notifications');
        $ids = collect($response->json('notifications'))->pluck('id')->toArray();
        // Most recent first
        $this->assertEquals([$newest->id, $middle->id, $older->id], $ids);
        $this->assertEquals(3, $response->json('unread_count'));
    }

    /** @test */
    public function dropdown_shows_no_duplicate_after_bell_click_when_same_notification_re_fetched()
    {
        $n = $this->createDbNotification($this->user, ['title' => 'Once Only', 'message' => 'msg once', 'type' => 'appointment_booked']);

        // First bell click
        $first = $this->actingAs($this->user)->getJson('/api/notifications');
        $this->assertCount(1, $first->json('notifications'));

        // Second bell click (Alpine's toggleDropdown calls loadNotifications again)
        $second = $this->actingAs($this->user)->getJson('/api/notifications');
        $this->assertCount(1, $second->json('notifications'));
        $this->assertEquals($n->id, $second->json('notifications.0.id'));
    }

    /** @test */
    public function bell_dropdown_correctly_updates_after_mark_as_read_and_subsequent_bell_click()
    {
        $n1 = $this->createDbNotification($this->user, ['title' => 'N1', 'message' => 'm1']);
        $n2 = $this->createDbNotification($this->user, ['title' => 'N2', 'message' => 'm2']);

        // Initially 2 unread
        $this->assertEquals(2, $this->actingAs($this->user)->getJson('/api/notifications/unread-count')->json('count'));

        // User clicks bell, then clicks a notification to mark read (Alpine's markAsRead hits POST /notifications/{id}/mark-read)
        $this->actingAs($this->user)->postJson(route('notifications.mark-read', ['id' => $n1->id]))
            ->assertOk()->assertJson(['success' => true]);

        // Bell click again should show 1 unread, 1 read
        $after = $this->actingAs($this->user)->getJson('/api/notifications');
        $this->assertEquals(1, $after->json('unread_count'));
        $items = collect($after->json('notifications'));
        $this->assertNotNull($items->firstWhere('id', $n1->id)['read_at']);
        $this->assertNull($items->firstWhere('id', $n2->id)['read_at']);
    }

    /** @test */
    public function bell_dropdown_shows_fallback_title_message_when_data_missing()
    {
        $this->createDbNotification($this->user, ['type' => 'appointment_booked', 'title' => null, 'message' => null]);
        // Override with minimal data to test fallback logic in NotificationController::apiIndex
        \Illuminate\Support\Facades\DB::table('notifications')
            ->where('notifiable_id', $this->user->id)
            ->update(['data' => json_encode(['type' => 'appointment_booked'])]);

        $response = $this->actingAs($this->user)->getJson('/api/notifications');
        $item = $response->json('notifications.0');
        $this->assertEquals('Notification', $item['title']);
        $this->assertEquals('You have a new notification', $item['message']);
    }

    /** @test */
    public function non_appointment_type_without_appointment_id_does_not_duplicate_on_re_fetch()
    {
        // This replicates the scenario for usage_limit_reached which has no appointment_id
        // Previously loadNotifications merge would fail dedup because key required appointment_id
        $this->createDbNotification($this->user, [
            'title' => 'Usage Limit Reached',
            'message' => 'You have reached your monthly usage limit.',
            'type' => 'usage_limit_reached',
        ]);

        $first = $this->actingAs($this->user)->getJson('/api/notifications');
        $second = $this->actingAs($this->user)->getJson('/api/notifications');

        // Should still be 1, not duplicated
        $this->assertCount(1, $first->json('notifications'));
        $this->assertCount(1, $second->json('notifications'));
        $this->assertEquals($first->json('notifications.0.id'), $second->json('notifications.0.id'));
    }

    /** @test */
    public function notification_badge_count_matches_dropdown_unread_after_multiple_scenarios()
    {
        $this->createDbNotification($this->user, ['title' => 'A']);
        $this->createDbNotification($this->user, ['title' => 'B']);
        $this->createDbNotification($this->user, ['title' => 'C', 'type' => 'system_alert']);

        $dropdown = $this->actingAs($this->user)->getJson('/api/notifications');
        $badge = $this->actingAs($this->user)->getJson('/api/notifications/unread-count');

        $this->assertEquals($dropdown->json('unread_count'), $badge->json('count'));
        $this->assertEquals(3, $badge->json('count'));
    }
}
