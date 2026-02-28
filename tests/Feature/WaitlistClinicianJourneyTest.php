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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;

class WaitlistClinicianJourneyTest extends TestCase
{
    use RefreshDatabase;

    protected WaitlistService $waitlistService;
    protected Doctor $doctor;
    protected User $patient1;
    protected User $patient2;
    protected User $patient3;

    protected function setUp(): void
    {
        parent::setUp();

        $this->waitlistService = app(WaitlistService::class);
        $this->doctor = Doctor::factory()->create();
        $this->patient1 = User::factory()->create();
        $this->patient2 = User::factory()->create();
        $this->patient3 = User::factory()->create();
    }

    /** @test */
    public function clinician_can_monitor_waitlist_statistics_and_manage_entries()
    {
        // Create multiple patients with different priorities
        $urgentWaitlist = $this->waitlistService->addToWaitlist($this->patient1->id, $this->doctor->id, [
            'priority_level' => 'urgent',
            'service_type' => 'emergency_consultation',
        ]);

        $highWaitlist = $this->waitlistService->addToWaitlist($this->patient2->id, $this->doctor->id, [
            'priority_level' => 'high',
            'service_type' => 'follow_up',
        ]);

        $mediumWaitlist = $this->waitlistService->addToWaitlist($this->patient3->id, $this->doctor->id, [
            'priority_level' => 'medium',
            'service_type' => 'consultation',
        ]);

        // Clinician checks waitlist statistics
        $stats = $this->waitlistService->getWaitlistStatistics($this->doctor->id);

        $this->assertEquals(3, $stats['total_active']);
        $this->assertEquals(1, $stats['by_priority']['urgent']);
        $this->assertEquals(1, $stats['by_priority']['high']);
        $this->assertEquals(1, $stats['by_priority']['medium']);

        // Clinician checks individual positions
        $position1 = $this->waitlistService->getWaitlistPosition($urgentWaitlist->id);
        $position2 = $this->waitlistService->getWaitlistPosition($highWaitlist->id);
        $position3 = $this->waitlistService->getWaitlistPosition($mediumWaitlist->id);

        // Urgent should be first (position 1)
        $this->assertEquals(1, $position1['position']);
        $this->assertEquals(3, $position1['total_waitlisted']);

        // High should be second (position 2)
        $this->assertEquals(2, $position2['position']);

        // Medium should be third (position 3)
        $this->assertEquals(3, $position3['position']);
    }

    /** @test */
    public function clinician_can_manage_slot_openings_and_offers()
    {
        // Create waitlist
        $waitlist = $this->waitlistService->addToWaitlist($this->patient1->id, $this->doctor->id, [
            'service_type' => 'consultation',
        ]);

        // Simulate appointment cancellation
        $cancelledAppointment = Appointment::create([
            'patient_id' => User::factory()->create()->id,
            'doctor_id' => $this->doctor->id,
            'appointment_date' => now()->addDays(1)->setTime(10, 0),
            'status' => 'cancelled',
            'appointment_type' => 'consultation',
            'duration' => 30,
        ]);

        // Create availability slot
        AvailabilitySlot::create([
            'doctor_id' => $this->doctor->id,
            'date' => $cancelledAppointment->appointment_date->toDateString(),
            'start_time' => $cancelledAppointment->appointment_date->format('H:i:s'),
            'duration' => 30,
            'is_available' => true,
        ]);

        // Clinician processes slot opening
        $this->waitlistService->processSlotOpening($cancelledAppointment);

        // Check that slot was offered to patient
        $this->assertDatabaseHas('waitlist_entries', [
            'waitlist_id' => $waitlist->id,
            'status' => 'offered',
            'slot_date' => $cancelledAppointment->appointment_date->toDateString(),
            'slot_time' => $cancelledAppointment->appointment_date->format('H:i:s'),
        ]);

        $entry = WaitlistEntry::where('waitlist_id', $waitlist->id)->first();

        // Clinician can check offer status
        $this->assertTrue($entry->isOffered());
        $this->assertFalse($entry->isResponseDeadlinePassed());
    }

    /** @test */
    public function clinician_can_handle_multiple_slot_openings_batch()
    {
        // Create multiple waitlists
        $patients = User::factory()->count(5)->create();
        foreach ($patients as $patient) {
            $this->waitlistService->addToWaitlist($patient->id, $this->doctor->id, [
                'service_type' => 'consultation',
            ]);
        }

        // Create multiple cancelled appointments
        $cancelledAppointments = [];
        for ($i = 0; $i < 3; $i++) {
            $appointment = Appointment::create([
                'patient_id' => User::factory()->create()->id,
                'doctor_id' => $this->doctor->id,
                'appointment_date' => now()->addDays($i + 1)->setTime(9, 0),
                'status' => 'cancelled',
                'appointment_type' => 'consultation',
                'duration' => 30,
            ]);

            // Create availability slot
            AvailabilitySlot::create([
                'doctor_id' => $this->doctor->id,
                'date' => $appointment->appointment_date->toDateString(),
                'start_time' => $appointment->appointment_date->format('H:i:s'),
                'duration' => 30,
                'is_available' => true,
            ]);

            $cancelledAppointments[] = $appointment;
        }

        // Clinician processes batch of slot openings
        $results = $this->waitlistService->processBatchSlotOpenings($cancelledAppointments);

        // Verify results
        $this->assertEquals(3, $results['processed']);
        $this->assertEquals(3, $results['slots_offered']);
        $this->assertEmpty($results['errors']);

        // Check that 3 entries were created (one for each slot)
        $this->assertDatabaseCount('waitlist_entries', 3);

        // All entries should be offered
        $entries = WaitlistEntry::all();
        foreach ($entries as $entry) {
            $this->assertEquals('offered', $entry->status);
        }
    }

    /** @test */
    public function clinician_can_monitor_patient_preferences_and_matching()
    {
        // Create patient with specific preferences
        $preferences = WaitlistPatientPreference::create([
            'patient_id' => $this->patient1->id,
            'doctor_id' => $this->doctor->id,
            'preferred_times' => ['morning'],
            'preferred_days' => ['monday', 'wednesday'],
            'service_priorities' => [
                'consultation' => 'high',
                'follow_up' => 'medium',
            ],
            'auto_accept_threshold' => 5,
            'notification_settings' => ['email' => true, 'sms' => true],
        ]);

        // Patient joins waitlist
        $waitlist = $this->waitlistService->addToWaitlist($this->patient1->id, $this->doctor->id, [
            'service_type' => 'consultation',
        ]);

        // Clinician can view patient preferences
        $this->assertEquals(['morning'], $preferences->preferred_times);
        $this->assertEquals(['monday', 'wednesday'], $preferences->preferred_days);
        $this->assertTrue($preferences->hasEmailNotifications());
        $this->assertTrue($preferences->hasSmsNotifications());
        $this->assertEquals(5, $preferences->auto_accept_threshold);

        // Create slots - one matching preferences, one not
        $matchingSlot = AvailabilitySlot::create([
            'doctor_id' => $this->doctor->id,
            'date' => now()->addDays(7)->next('monday')->toDateString(), // Monday
            'start_time' => '09:00:00', // Morning
            'duration' => 30,
            'is_available' => true,
        ]);

        $nonMatchingSlot = AvailabilitySlot::create([
            'doctor_id' => $this->doctor->id,
            'date' => now()->addDays(7)->next('friday')->toDateString(), // Friday
            'start_time' => '14:00:00', // Afternoon
            'duration' => 30,
            'is_available' => true,
        ]);

        // Clinician can check available slots
        $availableSlots = $this->waitlistService->findAvailableSlots($this->doctor->id, 14);
        $this->assertCount(2, $availableSlots);
    }

    /** @test */
    public function clinician_can_manage_waitlist_priorities_and_reorder()
    {
        // Create waitlists with different priorities
        $lowWaitlist = $this->waitlistService->addToWaitlist($this->patient1->id, $this->doctor->id, [
            'priority_level' => 'low',
        ]);

        $mediumWaitlist = $this->waitlistService->addToWaitlist($this->patient2->id, $this->doctor->id, [
            'priority_level' => 'medium',
        ]);

        $highWaitlist = $this->waitlistService->addToWaitlist($this->patient3->id, $this->doctor->id, [
            'priority_level' => 'high',
        ]);

        // Clinician can change priority levels
        $lowWaitlist->update(['priority_level' => 'urgent']);
        $lowWaitlist->refresh();

        $this->assertEquals('urgent', $lowWaitlist->priority_level);

        // Check updated statistics
        $stats = $this->waitlistService->getWaitlistStatistics($this->doctor->id);
        $this->assertEquals(1, $stats['by_priority']['urgent']);
        $this->assertEquals(1, $stats['by_priority']['high']);
        $this->assertEquals(1, $stats['by_priority']['medium']);
        $this->assertEquals(0, $stats['by_priority']['low']);
    }

    /** @test */
    public function clinician_can_handle_expired_offers_and_cleanup()
    {
        // Create waitlist
        $waitlist = $this->waitlistService->addToWaitlist($this->patient1->id, $this->doctor->id, []);

        // Create expired offer
        $expiredEntry = WaitlistEntry::create([
            'waitlist_id' => $waitlist->id,
            'slot_date' => now()->addDays(1)->toDateString(),
            'slot_time' => '09:00:00',
            'status' => 'offered',
            'response_deadline' => now()->subHours(2), // Expired 2 hours ago
        ]);

        // Clinician can find expired entries
        $expiredEntries = WaitlistEntry::expiredDeadline()->get();
        $this->assertCount(1, $expiredEntries);
        $this->assertEquals($expiredEntry->id, $expiredEntries->first()->id);

        // Clinician can expire the entry manually if needed
        $expiredEntry->expire();
        $this->assertEquals('expired', $expiredEntry->fresh()->status);
    }

    /** @test */
    public function clinician_can_remove_patients_from_waitlist()
    {
        // Create waitlist
        $waitlist = $this->waitlistService->addToWaitlist($this->patient1->id, $this->doctor->id, []);

        // Create some entries for the waitlist
        WaitlistEntry::factory()->count(2)->create(['waitlist_id' => $waitlist->id]);

        // Clinician removes patient from waitlist
        $result = $this->waitlistService->removeFromWaitlist($waitlist->id);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('waitlists', ['id' => $waitlist->id]);
        $this->assertDatabaseMissing('waitlist_entries', ['waitlist_id' => $waitlist->id]);
    }

    /** @test */
    public function clinician_can_monitor_waitlist_performance_metrics()
    {
        // Create waitlists with different creation times
        $oldWaitlist = $this->waitlistService->addToWaitlist($this->patient1->id, $this->doctor->id, []);
        $oldWaitlist->update(['created_at' => now()->subDays(10)]);
        $oldWaitlist->save();

        $newWaitlist = $this->waitlistService->addToWaitlist($this->patient2->id, $this->doctor->id, []);

        // Create some fulfilled waitlists for historical data
        $fulfilledWaitlist = $this->waitlistService->addToWaitlist($this->patient3->id, $this->doctor->id, []);
        $fulfilledWaitlist->update([
            'status' => 'fulfilled',
            'created_at' => now()->subDays(5)
        ]);
        $fulfilledWaitlist->save();

        // Clinician checks statistics
        $stats = $this->waitlistService->getWaitlistStatistics($this->doctor->id);

        $this->assertEquals(2, $stats['total_active']); // Only active ones
        $this->assertGreaterThan(0, $stats['average_wait_days']);

        // Average should be calculated from active waitlists
        $expectedAverage = (now()->diffInDays($oldWaitlist->created_at) + now()->diffInDays($newWaitlist->created_at)) / 2;
        $this->assertEquals(round($expectedAverage, 1), $stats['average_wait_days']);
    }

    /** @test */
    public function clinician_can_handle_emergency_slot_assignments()
    {
        // Create urgent waitlist
        $urgentWaitlist = $this->waitlistService->addToWaitlist($this->patient1->id, $this->doctor->id, [
            'priority_level' => 'urgent',
            'service_type' => 'emergency',
        ]);

        // Create regular waitlist
        $regularWaitlist = $this->waitlistService->addToWaitlist($this->patient2->id, $this->doctor->id, [
            'priority_level' => 'medium',
        ]);

        // Emergency slot opens
        $emergencyAppointment = Appointment::create([
            'patient_id' => User::factory()->create()->id,
            'doctor_id' => $this->doctor->id,
            'appointment_date' => now()->addHours(2), // Emergency - available in 2 hours
            'status' => 'cancelled',
            'appointment_type' => 'emergency',
            'duration' => 30,
        ]);

        AvailabilitySlot::create([
            'doctor_id' => $this->doctor->id,
            'date' => $emergencyAppointment->appointment_date->toDateString(),
            'start_time' => $emergencyAppointment->appointment_date->format('H:i:s'),
            'duration' => 30,
            'is_available' => true,
        ]);

        // Process emergency slot opening
        $this->waitlistService->processSlotOpening($emergencyAppointment);

        // Urgent patient should get the slot
        $this->assertDatabaseHas('waitlist_entries', [
            'waitlist_id' => $urgentWaitlist->id,
            'status' => 'offered',
        ]);

        // Regular patient should not
        $this->assertDatabaseMissing('waitlist_entries', [
            'waitlist_id' => $regularWaitlist->id,
        ]);
    }
}
