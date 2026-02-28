<?php

namespace Tests\Unit\Services;

use App\Models\Appointment;
use App\Models\NotificationPreference;
use App\Models\User;
use App\Models\WaitlistEntry;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationServiceWaitlistTest extends TestCase
{
    use RefreshDatabase;

    protected $notificationService;
    protected $user;
    protected $preferences;

    protected function setUp(): void
    {
        parent::setUp();

        $this->notificationService = new NotificationService();
        $this->user = User::factory()->create();
        $this->preferences = NotificationPreference::factory()->create([
            'user_id' => $this->user->id,
            'waitlist_slot_available' => true,
            'waitlist_offer_expiring' => true,
            'waitlist_position_update' => true,
            'waitlist_auto_booked' => true,
            'waitlist_expired' => true,
            'respect_quiet_hours' => false, // Disable quiet hours for testing
        ]);
    }

    /** @test */
    public function it_can_send_waitlist_slot_available_notification()
    {
        $waitlistEntry = WaitlistEntry::factory()->create();

        $result = $this->notificationService->sendWaitlistSlotAvailableNotification($this->user, $waitlistEntry);

        $this->assertTrue($result);
    }

    /** @test */
    public function it_does_not_send_waitlist_slot_available_notification_when_disabled()
    {
        $this->preferences->update(['waitlist_slot_available' => false]);
        $waitlistEntry = WaitlistEntry::factory()->create();

        $result = $this->notificationService->sendWaitlistSlotAvailableNotification($this->user, $waitlistEntry);

        $this->assertFalse($result);
    }

    /** @test */
    public function it_can_send_waitlist_offer_expiring_notification()
    {
        $waitlistEntry = WaitlistEntry::factory()->create();

        $result = $this->notificationService->sendWaitlistOfferExpiringNotification($this->user, $waitlistEntry);

        $this->assertTrue($result);
    }

    /** @test */
    public function it_can_send_waitlist_position_update_notification()
    {
        $waitlistEntry = WaitlistEntry::factory()->create();

        $result = $this->notificationService->sendWaitlistPositionUpdateNotification($this->user, $waitlistEntry, 5, 3);

        $this->assertTrue($result);
    }

    /** @test */
    public function it_can_send_waitlist_auto_booked_notification()
    {
        $appointment = Appointment::factory()->create();

        $result = $this->notificationService->sendWaitlistAutoBookedNotification($this->user, $appointment);

        $this->assertTrue($result);
    }

    /** @test */
    public function it_can_send_waitlist_expired_notification()
    {
        $waitlistEntry = WaitlistEntry::factory()->create();

        $result = $this->notificationService->sendWaitlistExpiredNotification($this->user, $waitlistEntry);

        $this->assertTrue($result);
    }

    /** @test */
    public function it_respects_quiet_hours_for_waitlist_notifications()
    {
        $this->preferences->update([
            'respect_quiet_hours' => true,
            'quiet_hours_start' => '22:00',
            'quiet_hours_end' => '08:00',
        ]);

        // Mock current time to be within quiet hours
        $this->travelTo(now()->setTime(23, 0)); // 11 PM

        $waitlistEntry = WaitlistEntry::factory()->create();

        $result = $this->notificationService->sendWaitlistSlotAvailableNotification($this->user, $waitlistEntry);

        $this->assertFalse($result);
    }

    /** @test */
    public function it_sends_notifications_outside_quiet_hours()
    {
        $this->preferences->update([
            'respect_quiet_hours' => true,
            'quiet_hours_start' => '22:00',
            'quiet_hours_end' => '08:00',
        ]);

        // Mock current time to be outside quiet hours
        $this->travelTo(now()->setTime(14, 0)); // 2 PM

        $waitlistEntry = WaitlistEntry::factory()->create();

        $result = $this->notificationService->sendWaitlistSlotAvailableNotification($this->user, $waitlistEntry);

        $this->assertTrue($result);
    }

    /** @test */
    public function it_handles_notification_sending_errors_gracefully()
    {
        // Create a scenario that might cause an error (invalid user relationship)
        $waitlistEntry = WaitlistEntry::factory()->create();
        $userWithoutPreferences = User::factory()->create();

        // Remove notification preferences to simulate error
        $userWithoutPreferences->notificationPreferences()->delete();

        $result = $this->notificationService->sendWaitlistSlotAvailableNotification($userWithoutPreferences, $waitlistEntry);

        $this->assertFalse($result);
    }
}
