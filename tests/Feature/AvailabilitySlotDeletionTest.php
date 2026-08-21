<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\AvailabilitySlot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AvailabilitySlotDeletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_doctor_can_delete_slot_without_bookings_even_with_other_future_appointments(): void
    {
        extract($this->createDoctor());

        $slot = AvailabilitySlot::create([
            'doctor_id' => $doctor->id,
            'day_of_week' => 'saturday',
            'start_time' => '09:00:00',
            'end_time' => '15:00:00',
        ]);

        Appointment::factory()->create([
            'doctor_id' => $doctor->id,
            'appointment_date' => now()->next('monday')->setTime(10, 0),
            'status' => 'confirmed',
        ]);

        $this->actingAs($user)
            ->delete(route('doctor.availability.destroy', $slot))
            ->assertRedirect(route('doctor.availability.index'));

        $this->assertDatabaseMissing('availability_slots', ['id' => $slot->id]);
    }

    public function test_slot_with_future_appointment_inside_window_cannot_be_deleted(): void
    {
        extract($this->createDoctor());

        $slot = AvailabilitySlot::create([
            'doctor_id' => $doctor->id,
            'day_of_week' => 'saturday',
            'start_time' => '09:00:00',
            'end_time' => '15:00:00',
        ]);

        Appointment::factory()->create([
            'doctor_id' => $doctor->id,
            'appointment_date' => now()->next('saturday')->setTime(10, 0),
            'status' => 'confirmed',
        ]);

        $this->actingAs($user)
            ->delete(route('doctor.availability.destroy', $slot))
            ->assertSessionHasErrors('error');

        $this->assertDatabaseHas('availability_slots', ['id' => $slot->id]);
    }
}
