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
        $this->assertDatabaseMissing('waitlists', ['id' => $waitlist->id]);
        $this->assertDatabaseMissing('waitlist_entries', ['waitlist_id' => $waitlist->id]);
    }

    /** @test */
    public function it_can_find_available_slots_for_doctor()
    {
        // Create availability slots
        AvailabilitySlot::factory()->create([
            'doctor_id' => $this->doctor->id,
            'date' => now()->addDays(1)->toDateString(),
            'start_time' => '09:00:00',
            'duration' => 30,
            'is_available' => true,
        ]);

        AvailabilitySlot::factory()->create([
            'doctor_id' => $this->doctor->id,
            'date' => now()->addDays(2)->toDateString(),
            'start_time' => '14:00:00',
            'duration' => 30,
            'is_available' => true,
        ]);

        // Create slot with appointment (should not be returned)
        $slotWithAppointment = AvailabilitySlot::factory()->create([
            'doctor_id' => $this->doctor->id,
            'date' => now()->addDays(3)->toDateString(),
            'start_time' => '10:00:00',
            'duration' => 30,
            'is_available' => true,
        ]);

        Appointment::factory()->create([
            'doctor_id' => $this->doctor->id,
            'appointment_date' => Carbon::parse($slotWithAppointment->date . ' ' . $slotWithAppointment->start_time),
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
        ]);

        // Create cancelled appointment
        $cancelledAppointment = Appointment::factory()->create([
            'doctor_id' => $this->doctor->id,
            'appointment_date' => now()->addDays(1)->setTime(10, 0),
            'status' => 'cancelled',
        ]);

        // Create availability slot for the cancelled appointment
        AvailabilitySlot::factory()->create([
            'doctor_id' => $this->doctor->id,
            'date' => $cancelledAppointment->appointment_date->toDateString(),
            'start_time' => $cancelledAppointment->appointment_date->format('H:i:s'),
            'is_available' => true,
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
            'created_at' => now()->subDays(2),
        ]);

        $waitlist2 = Waitlist::factory()->create([
            'doctor_id' => $this->doctor->id,
            'created_at' => now()->subDay(),
        ]);

        $waitlist3 = Waitlist::factory()->create([
            'patient_id' => $this->user->id,
            'doctor_id' => $this->doctor->id,
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
        ]);

        Waitlist::factory()->count(3)->create([
            'doctor_id' => $this->doctor->id,
            'priority_level' => 'high',
        ]);

        Waitlist::factory()->count(5)->create([
            'doctor_id' => $this->doctor->id,
            'priority_level' => 'medium',
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
        $appointments = Appointment::factory()->count(3)->create([
            'doctor_id' => $this->doctor->id,
            'status' => 'cancelled',
        ]);

        // Create corresponding availability slots
        foreach ($appointments as $appointment) {
            AvailabilitySlot::factory()->create([
                'doctor_id' => $this->doctor->id,
                'date' => $appointment->appointment_date->toDateString(),
                'start_time' => $appointment->appointment_date->format('H:i:s'),
                'is_available' => true,
            ]);
        }

        $results = $this->waitlistService->processBatchSlotOpenings($appointments);

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
