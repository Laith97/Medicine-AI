<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\AvailabilityService;
use App\Models\Doctor;
use App\Models\AvailabilitySlot;
use App\Models\Appointment;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AvailabilityServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $availabilityService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->availabilityService = new AvailabilityService();
    }

    /** @test */
    public function it_can_check_slot_availability()
    {
        $doctor = Doctor::factory()->create();

        // Create an availability slot
        $slot = AvailabilitySlot::create([
            'doctor_id' => $doctor->id,
            'day_of_week' => strtolower(now()->format('l')),
            'start_time' => '09:00:00',
            'end_time' => '10:00:00',
            'slot_duration' => 30,
            'max_bookings_per_slot' => 1,
            'is_active' => true,
            'effective_from' => now()->toDateString(),
        ]);

        $isAvailable = $this->availabilityService->checkSlotAvailability(
            $doctor->id,
            now()->toDateString(),
            '09:00:00'
        );

        $this->assertTrue($isAvailable);
    }

    /** @test */
    public function it_can_get_available_slots()
    {
        $doctor = Doctor::factory()->create();

        // Create availability slots
        AvailabilitySlot::create([
            'doctor_id' => $doctor->id,
            'day_of_week' => strtolower(now()->format('l')),
            'start_time' => '09:00:00',
            'end_time' => '10:00:00',
            'slot_duration' => 30,
            'max_bookings_per_slot' => 1,
            'is_active' => true,
            'effective_from' => now()->toDateString(),
        ]);

        $slots = $this->availabilityService->getAvailableSlots(
            $doctor->id,
            now(),
            now()->addDays(7)
        );

        $this->assertIsArray($slots);
        $this->assertGreaterThan(0, count($slots));
    }

    /** @test */
    public function it_can_calculate_slot_utilization()
    {
        $doctor = Doctor::factory()->create();

        // Create availability slots
        AvailabilitySlot::create([
            'doctor_id' => $doctor->id,
            'day_of_week' => strtolower(now()->format('l')),
            'start_time' => '09:00:00',
            'end_time' => '10:00:00',
            'slot_duration' => 30,
            'max_bookings_per_slot' => 1,
            'is_active' => true,
            'effective_from' => now()->toDateString(),
        ]);

        $utilization = $this->availabilityService->calculateSlotUtilization(
            $doctor->id,
            now(),
            now()->addDays(7)
        );

        $this->assertIsArray($utilization);
        $this->assertArrayHasKey('total_slots', $utilization);
        $this->assertArrayHasKey('booked_slots', $utilization);
        $this->assertArrayHasKey('utilization_rate', $utilization);
    }

    /** @test */
    public function it_can_get_next_available_slots()
    {
        $doctor = Doctor::factory()->create();

        // Create availability slots
        AvailabilitySlot::create([
            'doctor_id' => $doctor->id,
            'day_of_week' => strtolower(now()->format('l')),
            'start_time' => '09:00:00',
            'end_time' => '10:00:00',
            'slot_duration' => 30,
            'max_bookings_per_slot' => 1,
            'is_active' => true,
            'effective_from' => now()->toDateString(),
        ]);

        $nextSlots = $this->availabilityService->getNextAvailableSlots($doctor->id, 3);

        $this->assertIsArray($nextSlots);
        $this->assertLessThanOrEqual(3, count($nextSlots));
    }
}
