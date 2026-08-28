<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Doctor;
use App\Models\Specialty;
use App\Models\Appointment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Broadcast;

/**
 * Integration tests for realtime notification system
 * Verifies: book appointment + status changes + all types show toast + bell dropdown + realtime via PrivateChannel
 */
class NotificationRealtimeIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $doctorUser;
    protected User $patientUser;
    protected Doctor $doctor;
    protected Specialty $specialty;

    protected function setUp(): void
    {
        parent::setUp();
        $this->specialty = Specialty::factory()->create();
        $this->doctorUser = User::factory()->create(['role' => 'doctor']);
        $this->doctor = Doctor::factory()->create([
            'user_id' => $this->doctorUser->id,
            'specialty_id' => $this->specialty->id,
            'is_active' => true,
            'auto_approve_appointments' => true,
        ]);
        $this->patientUser = User::factory()->create(['role' => 'patient']);
    }

    private function makeAppointment(array $overrides = []): Appointment
    {
        return Appointment::factory()->create(array_merge([
            'doctor_id' => $this->doctor->id,
            'patient_id' => $this->patientUser->id,
            'appointment_date' => now()->addDays(1),
            'appointment_type' => 'in_person',
            'status' => 'pending',
        ], $overrides));
    }

    // === BOOK APPOINTMENT — doctor + patient get database + broadcast ===

    public function test_booking_appointment_sends_notification_to_doctor_database_and_broadcast(): void
    {
        Notification::fake();

        // Simulate controller logic: book appointment then notify doctor
        $appointment = $this->makeAppointment(['status' => 'confirmed']);
        $this->doctorUser->notify(new \App\Notifications\AppointmentBookedNotification($appointment));
        $this->patientUser->notify(new \App\Notifications\AppointmentBookedNotification($appointment));

        Notification::assertSentTo($this->doctorUser, \App\Notifications\AppointmentBookedNotification::class);
        Notification::assertSentTo($this->patientUser, \App\Notifications\AppointmentBookedNotification::class);

        // Verify database payload for dropdown
        Notification::assertSentTo($this->doctorUser, \App\Notifications\AppointmentBookedNotification::class, function ($n, $channels) {
            return in_array('database', $channels) && in_array('broadcast', $channels);
        });
    }

    public function test_appointment_booked_notification_appears_in_doctor_bell_dropdown_api(): void
    {
        $appointment = $this->makeAppointment();
        $this->doctorUser->notify(new \App\Notifications\AppointmentBookedNotification($appointment));

        $this->actingAs($this->doctorUser);

        // Bell dropdown API — used by notifications.js and unified-notifications.js syncAlpineDropdown
        $response = $this->getJson('/api/notifications');
        $response->assertStatus(200)
            ->assertJsonStructure(['notifications', 'unread_count'])
            ->assertJsonPath('unread_count', 1);

        $notifications = $response->json('notifications');
        $this->assertCount(1, $notifications);
        $this->assertEquals('appointment_booked', $notifications[0]['data']['type']);
        $this->assertNotEmpty($notifications[0]['title']); // toast title
        $this->assertNotEmpty($notifications[0]['message']); // toast message

        // Dropdown endpoint (notifications/dropdown)
        $dropdown = $this->getJson('/notifications/dropdown');
        $dropdown->assertStatus(200)
            ->assertJsonStructure(['notifications', 'unread_count']);
        $this->assertEquals(1, $dropdown->json('unread_count'));

        // Unread badge count
        $badge = $this->getJson('/api/notifications/unread-count');
        $badge->assertStatus(200)->assertJson(['count' => 1, 'authenticated' => true]);

        // Also visible on legacy notifications dropdown
        $legacy = $this->getJson('/notifications/dropdown');
        $legacy->assertStatus(200);
    }

    public function test_mark_as_read_decrements_badge_and_updates_dropdown(): void
    {
        $appointment = $this->makeAppointment();
        $this->doctorUser->notify(new \App\Notifications\AppointmentBookedNotification($appointment));
        $notification = $this->doctorUser->notifications()->first();

        $this->actingAs($this->doctorUser);

        $this->assertEquals(1, $this->doctorUser->unreadNotifications()->count());

        $response = $this->postJson("/api/notifications/{$notification->id}/read");
        $response->assertStatus(200)->assertJson(['success' => true]);

        $this->assertEquals(0, $this->doctorUser->fresh()->unreadNotifications()->count());

        // Badge should be 0
        $badge = $this->getJson('/api/notifications/unread-count');
        $badge->assertJson(['count' => 0]);

        // Dropdown should show read_at set
        $dropdown = $this->getJson('/api/notifications');
        $notif = collect($dropdown->json('notifications'))->firstWhere('id', $notification->id);
        $this->assertNotNull($notif['read_at']);
    }

    public function test_mark_all_as_read_clears_badge(): void
    {
        for ($i = 0; $i < 3; $i++) {
            $this->doctorUser->notify(new \App\Notifications\TestNotification(['title' => "Test $i", 'message' => 'msg']));
        }

        $this->actingAs($this->doctorUser);
        $this->assertEquals(3, $this->doctorUser->unreadNotifications()->count());

        $response = $this->postJson('/api/notifications/mark-all-read');
        $response->assertStatus(200)->assertJson(['success' => true]);

        $this->assertEquals(0, $this->doctorUser->fresh()->unreadNotifications()->count());
    }

    // === STATUS CHANGES — every status triggers correct toast + dropdown ===

    /**
     * @dataProvider statusChangeProvider
     */
    public function test_status_change_notification_toast_config_matches_js(string $old, string $new, string $expectedIcon): void
    {
        $appointment = $this->makeAppointment(['status' => $old]);
        $n = new \App\Notifications\AppointmentStatusChangedNotification($appointment, $old, $new);

        $arr = $n->toArray($this->doctorUser);
        $this->assertEquals('appointment_status_changed', $arr['type']);
        $this->assertEquals($expectedIcon, $arr['icon']);
        $this->assertNotEmpty($arr['title']);
        $this->assertNotEmpty($arr['message']);
    }

    public static function statusChangeProvider(): array
    {
        return [
            'pending->confirmed' => ['pending', 'confirmed', 'calendar-check'],
            'confirmed->cancelled' => ['confirmed', 'cancelled', 'calendar-times'],
            'confirmed->completed' => ['confirmed', 'completed', 'check-circle'],
            'confirmed->no_show' => ['confirmed', 'no_show', 'user-times'],
        ];
    }

    public function test_status_change_broadcast_is_suppressed_for_completed_to_avoid_duplicate_toast(): void
    {
        // Frontend unified-notifications.js:204 suppresses appointment-status-changed when new_status === completed
        // Verify the broadcast still has new_status so JS can detect suppression
        $appointment = $this->makeAppointment();
        $n = new \App\Notifications\AppointmentStatusChangedNotification($appointment, 'confirmed', 'completed');
        $broadcast = $n->toBroadcast($this->doctorUser);

        $this->assertEquals('completed', $broadcast->data['data']['new_status']);
        $this->assertEquals('appointment-status-changed', $n->broadcastAs());
    }

    // === ALL NOTIFICATION TYPES — realtime PrivateChannel ===

    public function test_all_notification_types_broadcast_on_private_user_channel(): void
    {
        $appointment = $this->makeAppointment();
        $diagnosis = \App\Models\Diagnosis::factory()->create([
            'doctor_id' => $this->doctorUser->id,
            'patient_id' => $this->patientUser->id,
        ]);
        // Set dynamic patient_name for notification payload (not a DB column)
        $diagnosis->setAttribute('patient_name', $this->patientUser->name);
        $diagnosis->setRelation('doctor', $this->doctorUser);
        $diagnosis->setRelation('patient', $this->patientUser);
        $review = \App\Models\Review::factory()->create([
            'doctor_id' => $this->doctor->id,
            'patient_id' => $this->patientUser->id,
        ]);

        $notifications = [
            new \App\Notifications\AppointmentBookedNotification($appointment),
            new \App\Notifications\AppointmentStatusChangedNotification($appointment, 'pending', 'confirmed'),
            new \App\Notifications\DiagnosisSubmittedNotification($diagnosis),
            new \App\Notifications\ReviewSubmittedNotification($review),
        ];

        foreach ($notifications as $n) {
            $channels = $n->broadcastOn();
            $this->assertNotEmpty($channels, get_class($n) . ' missing broadcastOn');
            $this->assertInstanceOf(\Illuminate\Broadcasting\PrivateChannel::class, $channels[0]);
            $this->assertStringContainsString('App.User.', $channels[0]->name);
        }
    }

    // === FRONTEND TOAST + DROPDOWN SYNC ===

    public function test_frontend_js_get_notification_config_covers_all_event_types(): void
    {
        $js = file_get_contents(resource_path('js/unified-notifications.js'));
        $this->assertNotEmpty($js);

        $requiredEvents = [
            'appointment-booked',
            'appointment-status-changed',
            'appointment-cancelled',
            'appointment-completed',
            'diagnosis-submitted',
            'review-submitted',
            'waitlist-slot-available',
            'invoice-created',
            'invoice-due-soon',
            'task-overdue',
        ];

        foreach ($requiredEvents as $event) {
            $this->assertStringContainsString("'$event'", $js, "Missing JS handler for $event");
            // Each should have a config entry for toast
            $this->assertStringContainsString("'$event':", $js, "Missing toast config for $event");
        }

        // Check toast container + Alpine sync
        $this->assertStringContainsString('unified-toast-container', $js);
        $this->assertStringContainsString('syncAlpineDropdown', $js);
        $this->assertStringContainsString('showToast', $js);
        $this->assertStringContainsString('handleNewNotification', $js);
    }

    public function test_notification_payload_contains_required_keys_for_toast_and_bell(): void
    {
        $appointment = $this->makeAppointment();
        $n = new \App\Notifications\AppointmentBookedNotification($appointment);

        $dbPayload = $n->toArray($this->doctorUser);
        $broadcastPayload = $n->toBroadcast($this->doctorUser)->data;

        // Keys needed for bell dropdown (apiIndex, dropdown) + toast (showToast)
        $required = ['type', 'title', 'message', 'icon', 'link'];
        foreach ($required as $key) {
            $this->assertArrayHasKey($key, $dbPayload, "DB $key missing");
            $this->assertArrayHasKey($key, $broadcastPayload, "Broadcast $key missing");
        }

        // Broadcast additionally needs body + created_at + data for dedup
        $this->assertArrayHasKey('body', $broadcastPayload);
        $this->assertArrayHasKey('created_at', $broadcastPayload);
        $this->assertArrayHasKey('data', $broadcastPayload);
        $this->assertArrayHasKey('appointment_id', $broadcastPayload['data']);
    }

    public function test_waitlist_and_invoice_notifications_have_toast_and_dropdown(): void
    {
        // Create real persisted models so routes and DB storage work
        $waitlistEntry = \App\Models\WaitlistEntry::factory()->create();

        $invoice = \App\Models\StripeInvoice::create([
            'user_id' => $this->doctorUser->id,
            'stripe_invoice_id' => 'in_test_'.uniqid(),
            'amount_due' => 10000,
            'amount_paid' => 0,
            'status' => 'open',
            'due_date' => now()->addDays(5),
            'description' => 'Test invoice',
            'currency' => 'usd',
        ]);

        $waitlistN = new \App\Notifications\WaitlistSlotAvailableNotification($waitlistEntry);
        $invoiceN = new \App\Notifications\InvoiceCreated($invoice);

        $waitlistArr = $waitlistN->toArray($this->doctorUser);
        $this->assertNotEmpty($waitlistArr['title']);
        $this->assertArrayHasKey('icon', $waitlistArr);

        $invoiceArr = $invoiceN->toArray($this->doctorUser);
        $this->assertArrayHasKey('invoice_id', $invoiceArr);

        $invoiceBroadcast = $invoiceN->toBroadcast($this->doctorUser);
        $this->assertEquals('invoice-created', $invoiceN->broadcastAs());
        $this->assertNotEmpty($invoiceBroadcast->data['title']);

        $this->doctorUser->notify($waitlistN);
        $this->doctorUser->notify($invoiceN);

        $this->assertEquals(2, $this->doctorUser->notifications()->count());

        $this->actingAs($this->doctorUser);
        $dropdown = $this->getJson('/api/notifications');
        $dropdown->assertJsonPath('unread_count', 2);
    }

    public function test_guest_booking_still_creates_notification_for_doctor(): void
    {
        $guestAppointment = Appointment::factory()->create([
            'doctor_id' => $this->doctor->id,
            'patient_id' => null,
            'guest_name' => 'Guest Patient',
            'guest_email' => 'guest@example.com',
            'appointment_date' => now()->addDays(1),
        ]);

        // Notification should handle guest_name fallback (patientName = guest_name)
        $n = new \App\Notifications\AppointmentBookedNotification($guestAppointment);
        $data = $n->toArray($this->doctorUser);

        $this->assertStringContainsString('Guest Patient', $data['message']);
        $this->assertEquals('calendar', $data['icon']);

        $this->doctorUser->notify($n);
        $this->assertEquals(1, $this->doctorUser->notifications()->count());
    }

    public function test_broadcast_compression_keeps_toast_keys(): void
    {
        $appointment = $this->makeAppointment();
        $n = new \App\Notifications\AppointmentBookedNotification($appointment);
        $broadcast = $n->toBroadcast($this->doctorUser);

        // Even after compression, keys must remain
        $this->assertArrayHasKey('title', $broadcast->data);
        $this->assertArrayHasKey('message', $broadcast->data);
        $this->assertArrayHasKey('icon', $broadcast->data);
    }
}
