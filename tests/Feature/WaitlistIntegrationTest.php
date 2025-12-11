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

class WaitlistIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected WaitlistService $waitlistService;
    protected User $patient;
    protected Doctor $doctor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->waitlistService = app(WaitlistService::class);
        $this->patient = User::factory()->create();
        $this->doctor = Doctor::factory()->create();
    }

    /** @test */
    public function complete_waitlist_workflow_from_creation_to_booking()
    {
        // Step 1: Patient creates preferences
        $preferences = WaitlistPatientPreference::create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'preferred_times' => ['morning'],
            'preferred_days' => ['monday', 'wednesday'],
            'auto_accept_threshold' => 3,
            'notification_settings' => ['email' => true, 'sms' => true],
        ]);

        // Step 2: Patient joins waitlist
        $waitlist = $this->waitlistService->addToWaitlist($this->patient->id, $this->doctor->id, [
            'service_type' => 'consultation',
            'priority_level' => 'medium',
            'preferred_time_slots' => ['morning'],
            'preferred_days' => ['monday'],
            'max_wait_days' => 30,
            'notification_channels' => ['email'],
        ]);

        $this->assertEquals('active', $waitlist->status);
        $this->assertDatabaseHas('waitlists', [
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'status' => 'active',
        ]);

        // Step 3: Create available slots for the doctor
        $availableSlot = AvailabilitySlot::create([
            'doctor_id' => $this->doctor->id,
            'date' => now()->addDays(7)->next('monday')->toDateString(), // Next Monday
            'start_time' => '09:00:00',
            'duration' => 30,
            'is_available' => true,
        ]);

        // Step 4: Simulate appointment cancellation (slot opening)
        $cancelledAppointment = Appointment::create([
            'patient_id' => User::factory()->create()->id,
            'doctor_id' => $this->doctor->id,
            'appointment_date' => Carbon::parse($availableSlot->date . ' ' . $availableSlot->start_time),
            'status' => 'cancelled',
            'appointment_type' => 'consultation',
            'duration' => 30,
        ]);

        // Step 5: Process slot opening - should offer slot to waitlisted patient
        $this->waitlistService->processSlotOpening($cancelledAppointment);

        $this->assertDatabaseHas('waitlist_entries', [
            'waitlist_id' => $waitlist->id,
            'slot_date' => $availableSlot->date,
            'slot_time' => $availableSlot->start_time,
            'status' => 'offered',
        ]);

        $entry = WaitlistEntry::where('waitlist_id', $waitlist->id)->first();

        // Step 6: Patient accepts the slot offer
        $result = $this->waitlistService->acceptSlotOffer($entry->id);

        $this->assertTrue($result);

        // Verify appointment was created
        $this->assertDatabaseHas('appointments', [
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'status' => 'confirmed',
            'appointment_type' => 'consultation',
        ]);

        // Verify waitlist was fulfilled
        $waitlist->refresh();
        $this->assertEquals('fulfilled', $waitlist->status);

        // Verify entry was accepted
        $entry->refresh();
        $this->assertEquals('accepted', $entry->status);
    }

    /** @test */
    public function auto_accept_workflow_with_preferences()
    {
        // Create preferences with auto-accept threshold
        $preferences = WaitlistPatientPreference::create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'preferred_times' => ['morning'],
            'preferred_days' => ['monday'],
            'auto_accept_threshold' => 7, // Auto-accept within 7 days
            'notification_settings' => ['email' => true],
        ]);

        // Join waitlist
        $waitlist = $this->waitlistService->addToWaitlist($this->patient->id, $this->doctor->id, [
            'service_type' => 'consultation',
            'priority_level' => 'medium',
        ]);

        // Create slot within auto-accept threshold (3 days from now)
        $slotDate = now()->addDays(3)->next('monday');
        $cancelledAppointment = Appointment::create([
            'patient_id' => User::factory()->create()->id,
            'doctor_id' => $this->doctor->id,
            'appointment_date' => $slotDate->setTime(9, 0),
            'status' => 'cancelled',
            'appointment_type' => 'consultation',
            'duration' => 30,
        ]);

        // Create availability slot
        AvailabilitySlot::create([
            'doctor_id' => $this->doctor->id,
            'date' => $slotDate->toDateString(),
            'start_time' => '09:00:00',
            'duration' => 30,
            'is_available' => true,
        ]);

        // Process slot opening - should auto-accept
        $this->waitlistService->processSlotOpening($cancelledAppointment);

        // Check that entry was created and auto-accepted
        $entry = WaitlistEntry::where('waitlist_id', $waitlist->id)->first();
        $this->assertEquals('accepted', $entry->status);

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
    public function waitlist_position_tracking_and_statistics()
    {
        // Create multiple patients on waitlist
        $patients = User::factory()->count(5)->create();

        $waitlists = [];
        foreach ($patients as $index => $patient) {
            $waitlists[] = $this->waitlistService->addToWaitlist($patient->id, $this->doctor->id, [
                'service_type' => 'consultation',
                'priority_level' => 'medium',
            ]);
        }

        // Check positions
        foreach ($waitlists as $index => $waitlist) {
            $position = $this->waitlistService->getWaitlistPosition($waitlist->id);
            $this->assertEquals($index + 1, $position['position']);
            $this->assertEquals(5, $position['total_waitlisted']);
        }

        // Check statistics
        $stats = $this->waitlistService->getWaitlistStatistics($this->doctor->id);
        $this->assertEquals(5, $stats['total_active']);
        $this->assertEquals(5, $stats['by_priority']['medium']);
        $this->assertGreaterThan(0, $stats['average_wait_days']);
    }

    /** @test */
    public function batch_slot_processing_workflow()
    {
        // Create multiple waitlists
        $patients = User::factory()->count(3)->create();
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

        // Process batch
        $results = $this->waitlistService->processBatchSlotOpenings($cancelledAppointments);

        $this->assertEquals(3, $results['processed']);
        $this->assertEquals(3, $results['slots_offered']);
        $this->assertEmpty($results['errors']);

        // Check that entries were created
        $this->assertDatabaseCount('waitlist_entries', 3);
    }

    /** @test */
    public function waitlist_removal_and_cleanup_workflow()
    {
        // Create waitlist
        $waitlist = $this->waitlistService->addToWaitlist($this->patient->id, $this->doctor->id, [
            'service_type' => 'consultation',
        ]);

        // Create some entries
        WaitlistEntry::factory()->count(2)->create(['waitlist_id' => $waitlist->id]);

        // Remove from waitlist
        $result = $this->waitlistService->removeFromWaitlist($waitlist->id);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('waitlists', ['id' => $waitlist->id]);
        $this->assertDatabaseMissing('waitlist_entries', ['waitlist_id' => $waitlist->id]);
    }

    /** @test */
    public function slot_offer_expiration_workflow()
    {
        // Create waitlist
        $waitlist = $this->waitlistService->addToWaitlist($this->patient->id, $this->doctor->id, []);

        // Create expired entry
        $entry = WaitlistEntry::create([
            'waitlist_id' => $waitlist->id,
            'slot_date' => now()->addDays(1)->toDateString(),
            'slot_time' => '09:00:00',
            'status' => 'offered',
            'response_deadline' => now()->subHour(), // Already expired
        ]);

        // Try to accept expired offer
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Slot offer is no longer valid');

        $this->waitlistService->acceptSlotOffer($entry->id);
    }

    /** @test */
    public function preference_based_slot_matching_integration()
    {
        // Create preferences with specific requirements
        $preferences = WaitlistPatientPreference::create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'preferred_times' => ['morning'],
            'preferred_days' => ['monday'],
            'max_travel_distance' => 50,
        ]);

        // Join waitlist
        $waitlist = $this->waitlistService->addToWaitlist($this->patient->id, $this->doctor->id, []);

        // Create slots - one matching, one not matching
        $matchingAppointment = Appointment::create([
            'patient_id' => User::factory()->create()->id,
            'doctor_id' => $this->doctor->id,
            'appointment_date' => now()->addDays(7)->next('monday')->setTime(9, 0), // Monday morning
            'status' => 'cancelled',
            'appointment_type' => 'consultation',
            'duration' => 30,
        ]);

        $nonMatchingAppointment = Appointment::create([
            'patient_id' => User::factory()->create()->id,
            'doctor_id' => $this->doctor->id,
            'appointment_date' => now()->addDays(7)->next('friday')->setTime(14, 0), // Friday afternoon
            'status' => 'cancelled',
            'appointment_type' => 'consultation',
            'duration' => 30,
        ]);

        // Create availability slots
        foreach ([$matchingAppointment, $nonMatchingAppointment] as $appointment) {
            AvailabilitySlot::create([
                'doctor_id' => $this->doctor->id,
                'date' => $appointment->appointment_date->toDateString(),
                'start_time' => $appointment->appointment_date->format('H:i:s'),
                'duration' => 30,
                'is_available' => true,
            ]);
        }

        // Process matching slot - should create entry
        $this->waitlistService->processSlotOpening($matchingAppointment);
        $this->assertDatabaseHas('waitlist_entries', ['waitlist_id' => $waitlist->id]);

        // Process non-matching slot - should not create additional entry (only one patient gets offered)
        $this->waitlistService->processSlotOpening($nonMatchingAppointment);
        $this->assertDatabaseCount('waitlist_entries', 1); // Still only one entry
    }

    /** @test */
    public function concurrent_slot_offers_are_handled_properly()
    {
        // Create multiple waitlists with different priorities
        $urgentPatient = User::factory()->create();
        $highPatient = User::factory()->create();
        $mediumPatient = User::factory()->create();

        $urgentWaitlist = $this->waitlistService->addToWaitlist($urgentPatient->id, $this->doctor->id, [
            'priority_level' => 'urgent',
        ]);

        $highWaitlist = $this->waitlistService->addToWaitlist($highPatient->id, $this->doctor->id, [
            'priority_level' => 'high',
        ]);

        $mediumWaitlist = $this->waitlistService->addToWaitlist($mediumPatient->id, $this->doctor->id, [
            'priority_level' => 'medium',
        ]);

        // Create cancelled appointment
        $cancelledAppointment = Appointment::create([
            'patient_id' => User::factory()->create()->id,
            'doctor_id' => $this->doctor->id,
            'appointment_date' => now()->addDays(1)->setTime(9, 0),
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

        // Process slot opening - should offer to highest priority patient
        $this->waitlistService->processSlotOpening($cancelledAppointment);

        // Only urgent patient should get the offer
        $this->assertDatabaseHas('waitlist_entries', [
            'waitlist_id' => $urgentWaitlist->id,
            'status' => 'offered',
        ]);

        // Others should not have entries
        $this->assertDatabaseMissing('waitlist_entries', ['waitlist_id' => $highWaitlist->id]);
        $this->assertDatabaseMissing('waitlist_entries', ['waitlist_id' => $mediumWaitlist->id]);
    }
}
