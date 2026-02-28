<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Doctor;
use App\Models\Waitlist;
use App\Models\WaitlistEntry;
use App\Models\WaitlistPatientPreference;
use App\Models\Appointment;
use App\Models\AvailabilitySlot;
use App\Services\WaitlistService;
use App\Services\WaitlistPreferenceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;

class WaitlistPatientJourneyTest extends TestCase
{
    use RefreshDatabase;

    protected WaitlistService $waitlistService;
    protected WaitlistPreferenceService $preferenceService;
    protected Doctor $doctor;
    protected User $patient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->waitlistService = app(WaitlistService::class);
        $this->preferenceService = app(WaitlistPreferenceService::class);
        $this->doctor = Doctor::factory()->create();
        $this->patient = User::factory()->create();
    }

    /** @test */
    public function patient_can_set_preferences_and_join_waitlist()
    {
        // Patient sets preferences
        $preferences = WaitlistPatientPreference::create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'preferred_times' => ['morning', 'afternoon'],
            'preferred_days' => ['monday', 'wednesday', 'friday'],
            'service_priorities' => [
                'consultation' => 'high',
                'follow_up' => 'medium',
            ],
            'auto_accept_threshold' => 7,
            'notification_settings' => ['email' => true, 'sms' => true],
            'max_travel_distance' => 25.5,
        ]);

        // Patient joins waitlist
        $waitlist = $this->waitlistService->addToWaitlist($this->patient->id, $this->doctor->id, [
            'service_type' => 'consultation',
            'priority_level' => 'medium',
            'preferred_time_slots' => ['morning'],
            'preferred_days' => ['monday'],
        ]);

        $this->assertEquals('active', $waitlist->status);
        $this->assertEquals('consultation', $waitlist->service_type);
        $this->assertEquals($this->patient->id, $waitlist->patient_id);
        $this->assertEquals($this->doctor->id, $waitlist->doctor_id);
    }

    /** @test */
    public function patient_can_check_waitlist_position_and_estimated_wait_time()
    {
        // Create multiple patients on waitlist
        $patients = User::factory()->count(4)->create();

        $waitlists = [];
        foreach ($patients as $patient) {
            $waitlists[] = $this->waitlistService->addToWaitlist($patient->id, $this->doctor->id, []);
        }

        // Patient checks their position
        $position = $this->waitlistService->getWaitlistPosition($waitlists[2]->id);

        $this->assertEquals(3, $position['position']);
        $this->assertEquals(4, $position['total_waitlisted']);
        $this->assertGreaterThan(0, $position['estimated_wait_days']);
    }

    /** @test */
    public function patient_receives_slot_offers_and_can_respond()
    {
        // Patient joins waitlist
        $waitlist = $this->waitlistService->addToWaitlist($this->patient->id, $this->doctor->id, []);

        // Simulate slot becoming available
        $cancelledAppointment = Appointment::create([
            'patient_id' => User::factory()->create()->id,
            'doctor_id' => $this->doctor->id,
            'appointment_date' => now()->addDays(3)->setTime(10, 0),
            'status' => 'cancelled',
            'appointment_type' => 'consultation',
            'duration' => 30,
        ]);

        AvailabilitySlot::create([
            'doctor_id' => $this->doctor->id,
            'date' => $cancelledAppointment->appointment_date->toDateString(),
            'start_time' => $cancelledAppointment->appointment_date->format('H:i:s'),
            'duration' => 30,
            'is_available' => true,
        ]);

        // Process slot opening - patient receives offer
        $this->waitlistService->processSlotOpening($cancelledAppointment);

        $entry = WaitlistEntry::where('waitlist_id', $waitlist->id)->first();
        $this->assertEquals('offered', $entry->status);
        $this->assertNotNull($entry->response_deadline);

        // Patient accepts the offer
        $result = $this->waitlistService->acceptSlotOffer($entry->id);

        $this->assertTrue($result);

        // Check appointment was created
        $this->assertDatabaseHas('appointments', [
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'status' => 'confirmed',
        ]);

        // Check waitlist was fulfilled
        $waitlist->refresh();
        $this->assertEquals('fulfilled', $waitlist->status);
    }

    /** @test */
    public function patient_can_decline_slot_offers()
    {
        // Patient joins waitlist
        $waitlist = $this->waitlistService->addToWaitlist($this->patient->id, $this->doctor->id, []);

        // Create offered entry
        $entry = WaitlistEntry::create([
            'waitlist_id' => $waitlist->id,
            'slot_date' => now()->addDays(2)->toDateString(),
            'slot_time' => '14:00:00',
            'status' => 'offered',
            'response_deadline' => now()->addHours(24),
        ]);

        // Patient declines the offer
        $result = $this->waitlistService->declineSlotOffer($entry->id);

        $this->assertTrue($result);
        $this->assertEquals('declined', $entry->fresh()->status);

        // Waitlist should still be active
        $waitlist->refresh();
        $this->assertEquals('active', $waitlist->status);
    }

    /** @test */
    public function patient_can_update_preferences()
    {
        // Create initial preferences
        $preferences = WaitlistPatientPreference::create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'preferred_times' => ['morning'],
            'preferred_days' => ['monday'],
            'auto_accept_threshold' => 7,
        ]);

        // Patient updates preferences
        $preferences->update([
            'preferred_times' => ['afternoon', 'evening'],
            'preferred_days' => ['tuesday', 'thursday'],
            'auto_accept_threshold' => 14,
        ]);

        $preferences->refresh();

        $this->assertEquals(['afternoon', 'evening'], $preferences->preferred_times);
        $this->assertEquals(['tuesday', 'thursday'], $preferences->preferred_days);
        $this->assertEquals(14, $preferences->auto_accept_threshold);
    }

    /** @test */
    public function patient_can_get_smart_matching_recommendations()
    {
        // Create preferences
        $preferences = WaitlistPatientPreference::create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'preferred_times' => ['morning'],
            'preferred_days' => ['monday'],
        ]);

        // Create available slots
        AvailabilitySlot::create([
            'doctor_id' => $this->doctor->id,
            'date' => now()->addDays(7)->next('monday')->toDateString(), // Monday
            'start_time' => '09:00:00', // Morning - perfect match
            'duration' => 30,
            'is_available' => true,
        ]);

        AvailabilitySlot::create([
            'doctor_id' => $this->doctor->id,
            'date' => now()->addDays(7)->next('friday')->toDateString(), // Friday
            'start_time' => '14:00:00', // Afternoon - poor match
            'duration' => 30,
            'is_available' => true,
        ]);

        // Get recommendations
        $recommendations = $this->preferenceService->getMatchingRecommendations($this->patient->id, $this->doctor->id);

        $this->assertNotEmpty($recommendations);
        $this->assertGreaterThan(60, $recommendations[0]['matching_score']);
        $this->assertArrayHasKey('slot', $recommendations[0]);
        $this->assertArrayHasKey('match_reasons', $recommendations[0]);
    }

    /** @test */
    public function patient_can_get_suggested_preferences_from_history()
    {
        // Create appointment history
        Appointment::create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'appointment_date' => Carbon::parse('2025-01-01 09:00:00'), // Monday morning
            'status' => 'completed',
            'appointment_type' => 'consultation',
            'duration' => 30,
        ]);

        Appointment::create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'appointment_date' => Carbon::parse('2025-01-03 14:00:00'), // Wednesday afternoon
            'status' => 'completed',
            'appointment_type' => 'consultation',
            'duration' => 30,
        ]);

        // Get suggested preferences
        $suggestions = $this->preferenceService->getSuggestedPreferences($this->patient->id);

        $this->assertArrayHasKey('preferred_times', $suggestions);
        $this->assertArrayHasKey('preferred_days', $suggestions);
        $this->assertContains('morning', $suggestions['preferred_times']);
        $this->assertContains('monday', $suggestions['preferred_days']);
    }

    /** @test */
    public function patient_can_view_preference_analytics()
    {
        // Create multiple preferences for different doctors
        $doctor2 = Doctor::factory()->create();

        WaitlistPatientPreference::factory()->count(3)->create([
            'patient_id' => $this->patient->id,
        ]);

        // Get analytics
        $analytics = $this->preferenceService->getPreferenceAnalytics($this->patient->id);

        $this->assertEquals(3, $analytics['total_preferences']);
        $this->assertGreaterThan(0, $analytics['doctors_with_preferences']);
        $this->assertArrayHasKey('most_common_time_preferences', $analytics);
        $this->assertArrayHasKey('most_common_day_preferences', $analytics);
        $this->assertArrayHasKey('average_auto_accept_threshold', $analytics);
    }

    /** @test */
    public function patient_experiences_auto_accept_when_slot_matches_preferences()
    {
        // Create preferences with auto-accept
        $preferences = WaitlistPatientPreference::create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'preferred_times' => ['morning'],
            'preferred_days' => ['monday'],
            'auto_accept_threshold' => 7, // Auto-accept within 7 days
        ]);

        // Join waitlist
        $waitlist = $this->waitlistService->addToWaitlist($this->patient->id, $this->doctor->id, []);

        // Create slot within auto-accept threshold
        $slotDate = now()->addDays(3)->next('monday'); // 3 days from now, Monday
        $cancelledAppointment = Appointment::create([
            'patient_id' => User::factory()->create()->id,
            'doctor_id' => $this->doctor->id,
            'appointment_date' => $slotDate->setTime(9, 0), // Morning
            'status' => 'cancelled',
            'appointment_type' => 'consultation',
            'duration' => 30,
        ]);

        AvailabilitySlot::create([
            'doctor_id' => $this->doctor->id,
            'date' => $cancelledAppointment->appointment_date->toDateString(),
            'start_time' => $cancelledAppointment->appointment_date->format('H:i:s'),
            'duration' => 30,
            'is_available' => true,
        ]);

        // Process slot opening - should auto-accept
        $this->waitlistService->processSlotOpening($cancelledAppointment);

        // Check that entry was auto-accepted
        $entry = WaitlistEntry::where('waitlist_id', $waitlist->id)->first();
        $this->assertEquals('accepted', $entry->status);

        // Check appointment was created
        $this->assertDatabaseHas('appointments', [
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'status' => 'confirmed',
        ]);
    }

    /** @test */
    public function patient_can_remove_themselves_from_waitlist()
    {
        // Join waitlist
        $waitlist = $this->waitlistService->addToWaitlist($this->patient->id, $this->doctor->id, []);

        // Create some entries
        WaitlistEntry::factory()->count(2)->create(['waitlist_id' => $waitlist->id]);

        // Patient removes themselves
        $result = $this->waitlistService->removeFromWaitlist($waitlist->id);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('waitlists', ['id' => $waitlist->id]);
        $this->assertDatabaseMissing('waitlist_entries', ['waitlist_id' => $waitlist->id]);
    }

    /** @test */
    public function patient_cannot_accept_expired_slot_offers()
    {
        // Create waitlist
        $waitlist = $this->waitlistService->addToWaitlist($this->patient->id, $this->doctor->id, []);

        // Create expired entry
        $entry = WaitlistEntry::create([
            'waitlist_id' => $waitlist->id,
            'slot_date' => now()->addDays(1)->toDateString(),
            'slot_time' => '10:00:00',
            'status' => 'offered',
            'response_deadline' => now()->subHour(), // Already expired
        ]);

        // Try to accept expired offer
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Slot offer is no longer valid');

        $this->waitlistService->acceptSlotOffer($entry->id);
    }

    /** @test */
    public function patient_receives_multiple_offers_and_can_choose()
    {
        // Join waitlist
        $waitlist = $this->waitlistService->addToWaitlist($this->patient->id, $this->doctor->id, []);

        // Create two different slot offers
        $entry1 = WaitlistEntry::create([
            'waitlist_id' => $waitlist->id,
            'slot_date' => now()->addDays(2)->toDateString(),
            'slot_time' => '09:00:00',
            'status' => 'offered',
            'response_deadline' => now()->addHours(24),
        ]);

        $entry2 = WaitlistEntry::create([
            'waitlist_id' => $waitlist->id,
            'slot_date' => now()->addDays(5)->toDateString(),
            'slot_time' => '14:00:00',
            'status' => 'offered',
            'response_deadline' => now()->addHours(24),
        ]);

        // Patient accepts the first offer
        $result = $this->waitlistService->acceptSlotOffer($entry1->id);

        $this->assertTrue($result);

        // Check first entry is accepted
        $entry1->refresh();
        $this->assertEquals('accepted', $entry1->status);

        // Check second entry is still offered (could be declined or expired)
        $entry2->refresh();
        $this->assertEquals('offered', $entry2->status);

        // Check waitlist is fulfilled
        $waitlist->refresh();
        $this->assertEquals('fulfilled', $waitlist->status);
    }
}
