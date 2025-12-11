<?php

namespace Tests\Unit\Models;

use App\Models\NotificationPreference;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationPreferenceWaitlistTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $preferences;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->preferences = NotificationPreference::factory()->create([
            'user_id' => $this->user->id,
            'waitlist_slot_available' => true,
            'waitlist_offer_expiring' => true,
            'waitlist_position_update' => false,
            'waitlist_auto_booked' => true,
            'waitlist_expired' => false,
            'waitlist_channels' => ['database', 'mail'],
            'waitlist_frequency' => 'immediate',
        ]);
    }

    /** @test */
    public function it_has_waitlist_notification_fields()
    {
        $this->assertTrue($this->preferences->waitlist_slot_available);
        $this->assertTrue($this->preferences->waitlist_offer_expiring);
        $this->assertFalse($this->preferences->waitlist_position_update);
        $this->assertTrue($this->preferences->waitlist_auto_booked);
        $this->assertFalse($this->preferences->waitlist_expired);
        $this->assertEquals(['database', 'mail'], $this->preferences->waitlist_channels);
        $this->assertEquals('immediate', $this->preferences->waitlist_frequency);
    }

    /** @test */
    public function it_can_get_active_waitlist_notification_types()
    {
        $activeTypes = $this->preferences->getActiveWaitlistNotificationTypes();

        $this->assertContains('waitlist_slot_available', $activeTypes);
        $this->assertContains('waitlist_offer_expiring', $activeTypes);
        $this->assertContains('waitlist_auto_booked', $activeTypes);
        $this->assertNotContains('waitlist_position_update', $activeTypes);
        $this->assertNotContains('waitlist_expired', $activeTypes);
    }

    /** @test */
    public function it_can_get_waitlist_channels()
    {
        $channels = $this->preferences->getWaitlistChannels();

        $this->assertEquals(['database', 'mail'], $channels);
    }

    /** @test */
    public function it_can_get_waitlist_frequency_label()
    {
        $this->assertEquals('Immediate', $this->preferences->getWaitlistFrequencyLabel());

        $this->preferences->update(['waitlist_frequency' => 'daily']);
        $this->assertEquals('Daily Digest', $this->preferences->getWaitlistFrequencyLabel());
    }

    /** @test */
    public function it_can_check_if_all_waitlist_notifications_are_enabled()
    {
        $this->assertFalse($this->preferences->allWaitlistNotificationsEnabled());

        $this->preferences->update([
            'waitlist_slot_available' => true,
            'waitlist_offer_expiring' => true,
            'waitlist_position_update' => true,
            'waitlist_auto_booked' => true,
            'waitlist_expired' => true,
        ]);

        $this->assertTrue($this->preferences->allWaitlistNotificationsEnabled());
    }

    /** @test */
    public function it_can_check_if_all_waitlist_notifications_are_disabled()
    {
        $this->preferences->update([
            'waitlist_slot_available' => false,
            'waitlist_offer_expiring' => false,
            'waitlist_position_update' => false,
            'waitlist_auto_booked' => false,
            'waitlist_expired' => false,
        ]);

        $this->assertTrue($this->preferences->allWaitlistNotificationsDisabled());
    }

    /** @test */
    public function it_can_enable_all_waitlist_notifications()
    {
        $this->preferences->update([
            'waitlist_slot_available' => false,
            'waitlist_offer_expiring' => false,
            'waitlist_position_update' => false,
            'waitlist_auto_booked' => false,
            'waitlist_expired' => false,
        ]);

        $this->preferences->enableAllWaitlistNotifications();

        $this->assertTrue($this->preferences->fresh()->waitlist_slot_available);
        $this->assertTrue($this->preferences->fresh()->waitlist_offer_expiring);
        $this->assertTrue($this->preferences->fresh()->waitlist_position_update);
        $this->assertTrue($this->preferences->fresh()->waitlist_auto_booked);
        $this->assertTrue($this->preferences->fresh()->waitlist_expired);
    }

    /** @test */
    public function it_can_disable_all_waitlist_notifications()
    {
        $this->preferences->disableAllWaitlistNotifications();

        $this->assertFalse($this->preferences->fresh()->waitlist_slot_available);
        $this->assertFalse($this->preferences->fresh()->waitlist_offer_expiring);
        $this->assertFalse($this->preferences->fresh()->waitlist_position_update);
        $this->assertFalse($this->preferences->fresh()->waitlist_auto_booked);
        $this->assertFalse($this->preferences->fresh()->waitlist_expired);
    }

    /** @test */
    public function it_includes_waitlist_notifications_in_all_notifications_check()
    {
        $this->preferences->update([
            'appointment_booked' => true,
            'appointment_reminder' => true,
            'diagnosis_submitted' => true,
            'review_submitted' => true,
            'voice_transcription_completed' => true,
            'system_alert' => true,
            'waitlist_slot_available' => false,
        ]);

        $this->assertFalse($this->preferences->allNotificationsEnabled());

        $this->preferences->update(['waitlist_slot_available' => true]);
        $this->assertTrue($this->preferences->allNotificationsEnabled());
    }

    /** @test */
    public function it_includes_waitlist_notifications_in_get_active_notification_types()
    {
        $this->preferences->update([
            'appointment_booked' => false,
            'waitlist_slot_available' => true,
        ]);

        $activeTypes = $this->preferences->getActiveNotificationTypes();

        $this->assertContains('waitlist_slot_available', $activeTypes);
        $this->assertNotContains('appointment_booked', $activeTypes);
    }
}
