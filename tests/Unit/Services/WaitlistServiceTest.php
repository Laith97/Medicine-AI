<?php

namespace Tests\Unit\Services;

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

class WaitlistServiceTest extends TestCase
{
    use RefreshDatabase;

    protected WaitlistService $waitlistService;
    protected User $user;
    protected Doctor $doctor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->waitlistService = app(WaitlistService::class);
        $this->user = User::factory()->create();
        $this->doctor = Doctor::factory()->create();
    }

    /** @test */
    public function it_can_add_patient_to_waitlist()
    {
        $data = [
            'service_type' => 'consultation',
            'priority_level' => 'high',
            'preferred_time_slots' => ['morning', 'afternoon'],
            'preferred_days' => ['monday', 'wednesday'],
            'max_wait_days' => 14,
            'notification_channels' => ['email', 'sms'],
        ];

        $waitlist = $this->waitlistService->addToWaitlist($this->user->id, $this->doctor->id, $data);

        $this->assertInstanceOf(Waitlist::class, $waitlist);
        $this->assertEquals($this->user->id, $waitlist->patient_id);
        $this->assertEquals($this->doctor->id, $waitlist->doctor_id);
        $this->assertEquals('consultation', $waitlist->service_type);
        $this->assertEquals('high', $waitlist->priority_level);
        $this->assertEquals('active', $waitlist->status);
    }

    /** @test */
    public function it_prevents_duplicate_active_waitlist_for_same_doctor()
    {
        // Create existing waitlist
        Waitlist::create([
            'patient_id' => $this->user->id,
            'doctor_id' => $this->doctor->id,
            'service_type' => 'consultation',
            'priority_level' => 'medium',
            'status' => 'active',
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Patient is already on the waitlist for this doctor');

        $this->waitlistService->addToWaitlist($this->user->id, $this->doctor->id, []);
    }

    /** @test */
    public function it_can_remove_patient_from_waitlist()
    {
        $waitlist = Waitlist::factory()->create([
            'patient_id' => $this->user->id,
            'doctor_id' => $this->doctor->id,
        ]);

        // Create some entries
        WaitlistEntry::factory()->count(2)->create(['waitlist_id' => $waitlist->id]);

        $result = $this->waitlistService->removeFromWaitlist($waitlist->id);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('waitlist_entries', ['waitlist_id' => $waitlist->id]);
        $this->assertDatabaseHas('waitlists', [
            'id' => $waitlist->id,
            'status' => 'cancelled',
        ]);
    }

    /** @test */
    public function it_can_find_available_slots_for_doctor()
    {
        // Create availability slot templates
        AvailabilitySlot::factory()->create([
            'doctor_id' => $this->doctor->id,
            'day_of_week' => strtolower(now()->addDays(1)->format('l')),
            'start_time' => '09:00:00',
            'end_time' => '10:00:00',
            'slot_duration' => 30,
            'max_bookings_per_slot' => 1,
            'is_active' => true,
            'effective_from' => now()->toDateString(),
            'effective_until' => null,
        ]);

        AvailabilitySlot::factory()->create([
            'doctor_id' => $this->doctor->id,
            'day_of_week' => strtolower(now()->addDays(2)->format('l')),
            'start_time' => '14:00:00',
            'end_time' => '15:00:00',
            'slot_duration' => 30,
            'max_bookings_per_slot' => 1,
            'is_active' => true,
            'effective_from' => now()->toDateString(),
            'effective_until' => null,
        ]);

        // Create slot template with appointment (should not be returned)
        $slotWithAppointment = AvailabilitySlot::factory()->create([
            'doctor_id' => $this->doctor->id,
            'day_of_week' => strtolower(now()->addDays(3)->format('l')),
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'slot_duration' => 30,
            'max_bookings_per_slot' => 1,
            'is_active' => true,
            'effective_from' => now()->toDateString(),
            'effective_until' => null,
        ]);

        Appointment::factory()->create([
            'doctor_id' => $this->doctor->id,
            'appointment_date' => now()->addDays(3)->setTime(10, 0),
            'status' => 'confirmed',
        ]);

        $availableSlots = $this->waitlistService->findAvailableSlots($this->doctor->id, 7);

        $this->assertCount(2, $availableSlots);
        $this->assertEquals('09:00:00', $availableSlots[0]['time']);
        $this->assertEquals('14:00:00', $availableSlots[1]['time']);
    }

    /** @test */
    public function it_can_process_slot_opening_and_offer_to_waitlist_patients()
    {
        // Create waitlist
        $waitlist = Waitlist::factory()->create([
            'patient_id' => $this->user->id,
            'doctor_id' => $this->doctor->id,
            'status' => 'active',
        ]);

        // Create cancelled appointment
        $cancelledAppointment = Appointment::factory()->create([
            'doctor_id' => $this->doctor->id,
            'appointment_date' => now()->addDays(1)->setTime(10, 0),
            'status' => 'cancelled',
        ]);

        $this->waitlistService->processSlotOpening($cancelledAppointment);

        $this->assertDatabaseHas('waitlist_entries', [
            'waitlist_id' => $waitlist->id,
            'slot_date' => $cancelledAppointment->appointment_date->toDateString(),
            'slot_time' => $cancelledAppointment->appointment_date->format('H:i:s'),
            'status' => 'offered',
        ]);
    }

    /** @test */
    public function it_can_accept_slot_offer_and_create_appointment()
    {
        $waitlist = Waitlist::factory()->create([
            'patient_id' => $this->user->id,
            'doctor_id' => $this->doctor->id,
        ]);

        $entry = WaitlistEntry::factory()->create([
            'waitlist_id' => $waitlist->id,
            'slot_date' => now()->addDays(1)->toDateString(),
            'slot_time' => '10:00:00',
            'status' => 'offered',
            'response_deadline' => now()->addHours(24),
        ]);

        $result = $this->waitlistService->acceptSlotOffer($entry->id);

        $this->assertTrue($result);

        // Check appointment was created
        $this->assertDatabaseHas('appointments', [
            'patient_id' => $this->user->id,
            'doctor_id' => $this->doctor->id,
            'status' => 'confirmed',
        ]);

        // Check appointment_end was set (NOT NULL column, was a production bug)
        $appointment = \App\Models\Appointment::where('patient_id', $this->user->id)
            ->where('doctor_id', $this->doctor->id)
            ->first();
        $this->assertNotNull($appointment->appointment_end);
        $this->assertEquals(
            \Carbon\Carbon::parse($entry->formatted_slot)->addMinutes(30)->format('Y-m-d H:i:s'),
            $appointment->appointment_end->format('Y-m-d H:i:s')
        );
        $this->assertEquals('in_person', $appointment->appointment_type);

        // Check waitlist was fulfilled
        $waitlist->refresh();
        $this->assertEquals('fulfilled', $waitlist->status);

        // Check entry was accepted
        $entry->refresh();
        $this->assertEquals('accepted', $entry->status);
    }

    /** @test */
    public function it_can_decline_slot_offer()
    {
        $entry = WaitlistEntry::factory()->create([
            'status' => 'offered',
        ]);

        $result = $this->waitlistService->declineSlotOffer($entry->id);

        $this->assertTrue($result);
        $this->assertEquals('declined', $entry->fresh()->status);
    }

    /** @test */
    public function it_can_get_waitlist_position()
    {
        // Create multiple waitlists for the doctor
        $waitlist1 = Waitlist::factory()->create([
            'doctor_id' => $this->doctor->id,
            'status' => 'active',
            'created_at' => now()->subDays(2),
        ]);

        $waitlist2 = Waitlist::factory()->create([
            'doctor_id' => $this->doctor->id,
            'status' => 'active',
            'created_at' => now()->subDay(),
        ]);

        $waitlist3 = Waitlist::factory()->create([
            'patient_id' => $this->user->id,
            'doctor_id' => $this->doctor->id,
            'status' => 'active',
            'created_at' => now(),
        ]);

        $position = $this->waitlistService->getWaitlistPosition($waitlist3->id);

        $this->assertEquals(3, $position['position']);
        $this->assertEquals(3, $position['total_waitlisted']);
        $this->assertGreaterThan(0, $position['estimated_wait_days']);
    }

    /** @test */
    public function it_can_get_waitlist_statistics()
    {
        // Create waitlists with different priorities
        Waitlist::factory()->count(2)->create([
            'doctor_id' => $this->doctor->id,
            'priority_level' => 'urgent',
            'status' => 'active',
            'created_at' => now()->subDays(3),
        ]);

        Waitlist::factory()->count(3)->create([
            'doctor_id' => $this->doctor->id,
            'priority_level' => 'high',
            'status' => 'active',
            'created_at' => now()->subDays(2),
        ]);

        Waitlist::factory()->count(5)->create([
            'doctor_id' => $this->doctor->id,
            'priority_level' => 'medium',
            'status' => 'active',
            'created_at' => now()->subDay(),
        ]);

        $stats = $this->waitlistService->getWaitlistStatistics($this->doctor->id);

        $this->assertEquals(10, $stats['total_active']);
        $this->assertEquals(2, $stats['by_priority']['urgent']);
        $this->assertEquals(3, $stats['by_priority']['high']);
        $this->assertEquals(5, $stats['by_priority']['medium']);
        $this->assertGreaterThan(0, $stats['average_wait_days']);
    }

    /** @test */
    public function it_handles_edge_cases_in_slot_matching()
    {
        $waitlist = Waitlist::factory()->create([
            'patient_id' => $this->user->id,
            'doctor_id' => $this->doctor->id,
        ]);

        // Test with no preferences (should match any slot)
        $matches = $this->invokePrivateMethod($this->waitlistService, 'slotMatchesPreferences', [
            $waitlist,
            now()->addDays(1)->toDateString(),
            '10:00:00'
        ]);

        $this->assertTrue($matches);
    }

    /** @test */
    public function it_handles_expired_slot_offers()
    {
        $entry = WaitlistEntry::factory()->create([
            'status' => 'offered',
            'response_deadline' => now()->subHour(),
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Slot offer is no longer valid');

        $this->waitlistService->acceptSlotOffer($entry->id);
    }

    /** @test */
    public function it_processes_batch_slot_openings()
    {
        // Create an active waitlist for the doctor so slots can be offered
        Waitlist::factory()->create([
            'patient_id' => $this->user->id,
            'doctor_id' => $this->doctor->id,
            'status' => 'active',
        ]);

        $appointments = Appointment::factory()->count(3)->create([
            'doctor_id' => $this->doctor->id,
            'status' => 'cancelled',
            'appointment_date' => now()->addDays(1)->setTime(10, 0),
        ]);

        $results = $this->waitlistService->processBatchSlotOpenings($appointments->all());

        $this->assertEquals(3, $results['processed']);
        $this->assertEquals(3, $results['slots_offered']);
        $this->assertEmpty($results['errors']);
    }

    /**
     * Helper method to invoke private methods for testing
     */
    private function invokePrivateMethod($object, $method, $parameters = [])
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($method);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }
}
