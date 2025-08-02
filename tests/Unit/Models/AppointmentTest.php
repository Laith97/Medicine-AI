<?php

namespace Tests\Unit\Models;

use App\Models\Appointment;
use App\Models\User;
use App\Models\Doctor;
use App\Models\Specialty;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppointmentTest extends TestCase
{
    use RefreshDatabase;

    protected $appointment;
    protected $patient;
    protected $doctor;
    protected $doctorUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->patient = User::factory()->create(['role' => 'patient']);
        $this->doctorUser = User::factory()->create(['role' => 'doctor']);

        $specialty = Specialty::factory()->create();
        $this->doctor = Doctor::factory()->create([
            'user_id' => $this->doctorUser->id,
            'specialty_id' => $specialty->id
        ]);

        $this->appointment = Appointment::factory()->create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'appointment_date' => now()->addDay(),
            'status' => 'pending',
            'appointment_type' => 'consultation',
            'duration' => 30,
            'fee' => 10000, // $100.00
            'notes' => 'Initial consultation',
            'follow_up_required' => false,
            'prescription_given' => false
        ]);
    }

    public function test_appointment_can_be_created()
    {
        $this->assertInstanceOf(Appointment::class, $this->appointment);
        $this->assertEquals($this->patient->id, $this->appointment->patient_id);
        $this->assertEquals($this->doctor->id, $this->appointment->doctor_id);
    }

    public function test_appointment_has_fillable_attributes()
    {
        $expectedFillable = [
            'patient_id', 'doctor_id', 'appointment_date', 'status',
            'appointment_type', 'duration', 'fee', 'notes', 'cancellation_reason',
            'cancelled_by', 'cancelled_at', 'confirmed_at', 'completed_at',
            'payment_status', 'payment_intent_id', 'meeting_link', 'meeting_id',
            'reminder_sent_at', 'follow_up_required', 'follow_up_date',
            'prescription_given', 'visit_number'
        ];

        $actualFillable = $this->appointment->getFillable();

        // Check that all expected fields are present
        foreach ($expectedFillable as $field) {
            $this->assertContains($field, $actualFillable, "Field '$field' should be fillable");
        }
    }

    public function test_appointment_casts_attributes_correctly()
    {
        $this->assertInstanceOf(Carbon::class, $this->appointment->appointment_date);
        $this->assertIsInt($this->appointment->duration);
        $this->assertIsInt($this->appointment->fee);
        $this->assertIsBool($this->appointment->follow_up_required);
        $this->assertIsBool($this->appointment->prescription_given);
    }

    public function test_appointment_patient_relationship()
    {
        $this->assertInstanceOf(User::class, $this->appointment->patient);
        $this->assertEquals($this->patient->id, $this->appointment->patient->id);
    }

    public function test_appointment_doctor_relationship()
    {
        $this->assertInstanceOf(Doctor::class, $this->appointment->doctor);
        $this->assertEquals($this->doctor->id, $this->appointment->doctor->id);
    }

    public function test_appointment_pending_scope()
    {
        $pendingAppointment = Appointment::factory()->create(['status' => 'pending']);
        $confirmedAppointment = Appointment::factory()->create(['status' => 'confirmed']);

        $pendingAppointments = Appointment::pending()->get();

        $this->assertTrue($pendingAppointments->contains($pendingAppointment));
        $this->assertFalse($pendingAppointments->contains($confirmedAppointment));
    }

    public function test_appointment_confirmed_scope()
    {
        $confirmedAppointment = Appointment::factory()->create(['status' => 'confirmed']);
        $pendingAppointment = Appointment::factory()->create(['status' => 'pending']);

        $confirmedAppointments = Appointment::confirmed()->get();

        $this->assertTrue($confirmedAppointments->contains($confirmedAppointment));
        $this->assertFalse($confirmedAppointments->contains($pendingAppointment));
    }

    public function test_appointment_completed_scope()
    {
        $completedAppointment = Appointment::factory()->create(['status' => 'completed']);
        $pendingAppointment = Appointment::factory()->create(['status' => 'pending']);

        $completedAppointments = Appointment::completed()->get();

        $this->assertTrue($completedAppointments->contains($completedAppointment));
        $this->assertFalse($completedAppointments->contains($pendingAppointment));
    }

    public function test_appointment_cancelled_scope()
    {
        $cancelledAppointment = Appointment::factory()->create(['status' => 'cancelled']);
        $pendingAppointment = Appointment::factory()->create(['status' => 'pending']);

        $cancelledAppointments = Appointment::cancelled()->get();

        $this->assertTrue($cancelledAppointments->contains($cancelledAppointment));
        $this->assertFalse($cancelledAppointments->contains($pendingAppointment));
    }

    public function test_appointment_today_scope()
    {
        $todayAppointment = Appointment::factory()->create([
            'appointment_date' => now()->startOfDay()->addHours(10)
        ]);
        $tomorrowAppointment = Appointment::factory()->create([
            'appointment_date' => now()->addDay()
        ]);

        $todayAppointments = Appointment::today()->get();

        $this->assertTrue($todayAppointments->contains($todayAppointment));
        $this->assertFalse($todayAppointments->contains($tomorrowAppointment));
    }

    public function test_appointment_upcoming_scope()
    {
        $upcomingAppointment = Appointment::factory()->create([
            'appointment_date' => now()->addHour()
        ]);
        $pastAppointment = Appointment::factory()->create([
            'appointment_date' => now()->subHour()
        ]);

        $upcomingAppointments = Appointment::upcoming()->get();

        $this->assertTrue($upcomingAppointments->contains($upcomingAppointment));
        $this->assertFalse($upcomingAppointments->contains($pastAppointment));
    }

    public function test_appointment_past_scope()
    {
        $pastAppointment = Appointment::factory()->create([
            'appointment_date' => now()->subHour()
        ]);
        $upcomingAppointment = Appointment::factory()->create([
            'appointment_date' => now()->addHour()
        ]);

        $pastAppointments = Appointment::past()->get();

        $this->assertTrue($pastAppointments->contains($pastAppointment));
        $this->assertFalse($pastAppointments->contains($upcomingAppointment));
    }

    public function test_appointment_get_fee_dollars_attribute()
    {
        $this->assertEquals(100.0, $this->appointment->fee_dollars);
    }

    public function test_appointment_get_status_color_attribute()
    {
        $this->appointment->status = 'pending';
        $this->assertEquals('warning', $this->appointment->status_color);

        $this->appointment->status = 'confirmed';
        $this->assertEquals('primary', $this->appointment->status_color);

        $this->appointment->status = 'completed';
        $this->assertEquals('success', $this->appointment->status_color);

        $this->appointment->status = 'cancelled';
        $this->assertEquals('danger', $this->appointment->status_color);
    }

    public function test_appointment_get_formatted_date_attribute()
    {
        $date = Carbon::parse('2024-01-15 14:30:00');
        $this->appointment->appointment_date = $date;

        $this->assertEquals('Jan 15, 2024 2:30 PM', $this->appointment->formatted_date);
    }

    public function test_appointment_get_formatted_time_attribute()
    {
        $date = Carbon::parse('2024-01-15 14:30:00');
        $this->appointment->appointment_date = $date;

        $this->assertEquals('2:30 PM', $this->appointment->formatted_time);
    }

    public function test_appointment_is_pending_method()
    {
        $this->assertTrue($this->appointment->isPending());

        $this->appointment->status = 'confirmed';
        $this->assertFalse($this->appointment->isPending());
    }

    public function test_appointment_is_confirmed_method()
    {
        $this->assertFalse($this->appointment->isConfirmed());

        $this->appointment->status = 'confirmed';
        $this->assertTrue($this->appointment->isConfirmed());
    }

    public function test_appointment_is_completed_method()
    {
        $this->assertFalse($this->appointment->isCompleted());

        $this->appointment->status = 'completed';
        $this->assertTrue($this->appointment->isCompleted());
    }

    public function test_appointment_is_cancelled_method()
    {
        $this->assertFalse($this->appointment->isCancelled());

        $this->appointment->status = 'cancelled';
        $this->assertTrue($this->appointment->isCancelled());
    }

    public function test_appointment_can_be_cancelled_method()
    {
        // Future appointment can be cancelled
        $this->appointment->appointment_date = now()->addDay();
        $this->appointment->status = 'pending';
        $this->assertTrue($this->appointment->canBeCancelled());

        // Past appointment cannot be cancelled
        $this->appointment->appointment_date = now()->subHour();
        $this->assertFalse($this->appointment->canBeCancelled());

        // Completed appointment cannot be cancelled
        $this->appointment->appointment_date = now()->addDay();
        $this->appointment->status = 'completed';
        $this->assertFalse($this->appointment->canBeCancelled());

        // Already cancelled appointment cannot be cancelled again
        $this->appointment->status = 'cancelled';
        $this->assertFalse($this->appointment->canBeCancelled());
    }

    public function test_appointment_can_be_rescheduled_method()
    {
        // Pending appointment can be rescheduled
        $this->appointment->status = 'pending';
        $this->assertTrue($this->appointment->canBeRescheduled());

        // Confirmed appointment can be rescheduled
        $this->appointment->status = 'confirmed';
        $this->assertTrue($this->appointment->canBeRescheduled());

        // Completed appointment cannot be rescheduled
        $this->appointment->status = 'completed';
        $this->assertFalse($this->appointment->canBeRescheduled());

        // Cancelled appointment cannot be rescheduled
        $this->appointment->status = 'cancelled';
        $this->assertFalse($this->appointment->canBeRescheduled());
    }

    public function test_appointment_is_today_method()
    {
        $this->appointment->appointment_date = now()->startOfDay()->addHours(10);
        $this->assertTrue($this->appointment->isToday());

        $this->appointment->appointment_date = now()->addDay();
        $this->assertFalse($this->appointment->isToday());
    }

    public function test_appointment_is_upcoming_method()
    {
        $this->appointment->appointment_date = now()->addHour();
        $this->assertTrue($this->appointment->isUpcoming());

        $this->appointment->appointment_date = now()->subHour();
        $this->assertFalse($this->appointment->isUpcoming());
    }

    public function test_appointment_is_past_method()
    {
        $this->appointment->appointment_date = now()->subHour();
        $this->assertTrue($this->appointment->isPast());

        $this->appointment->appointment_date = now()->addHour();
        $this->assertFalse($this->appointment->isPast());
    }

    public function test_appointment_confirm_method()
    {
        $this->appointment->confirm();

        $this->assertEquals('confirmed', $this->appointment->status);
        $this->assertNotNull($this->appointment->confirmed_at);
    }

    public function test_appointment_complete_method()
    {
        $this->appointment->complete();

        $this->assertEquals('completed', $this->appointment->status);
        $this->assertNotNull($this->appointment->completed_at);
    }

    public function test_appointment_cancel_method()
    {
        $reason = 'Patient requested cancellation';
        $cancelledBy = 'patient';

        $this->appointment->cancel($reason, $cancelledBy);

        $this->assertEquals('cancelled', $this->appointment->status);
        $this->assertEquals($reason, $this->appointment->cancellation_reason);
        $this->assertEquals($cancelledBy, $this->appointment->cancelled_by);
        $this->assertNotNull($this->appointment->cancelled_at);
    }

    public function test_appointment_reschedule_method()
    {
        $newDate = now()->addDays(2);
        $this->appointment->reschedule($newDate);

        $this->assertEquals($newDate->format('Y-m-d H:i:s'), $this->appointment->appointment_date->format('Y-m-d H:i:s'));
        $this->assertEquals('pending', $this->appointment->status);
        $this->assertNull($this->appointment->confirmed_at);
    }

    public function test_appointment_get_duration_in_hours_method()
    {
        $this->appointment->duration = 90; // 90 minutes
        $this->assertEquals(1.5, $this->appointment->getDurationInHours());

        $this->appointment->duration = 30; // 30 minutes
        $this->assertEquals(0.5, $this->appointment->getDurationInHours());
    }

    public function test_appointment_get_end_time_method()
    {
        $startTime = Carbon::parse('2024-01-15 14:30:00');
        $this->appointment->appointment_date = $startTime;
        $this->appointment->duration = 30;

        $expectedEndTime = $startTime->copy()->addMinutes(30);
        $this->assertEquals($expectedEndTime, $this->appointment->getEndTime());
    }

    public function test_appointment_needs_reminder_method()
    {
        // Appointment in 2 hours should need reminder
        $this->appointment->appointment_date = now()->addHours(2);
        $this->appointment->reminder_sent_at = null;
        $this->assertTrue($this->appointment->needsReminder());

        // Appointment with reminder already sent should not need reminder
        $this->appointment->reminder_sent_at = now()->subHour();
        $this->assertFalse($this->appointment->needsReminder());

        // Past appointment should not need reminder
        $this->appointment->appointment_date = now()->subHour();
        $this->appointment->reminder_sent_at = null;
        $this->assertFalse($this->appointment->needsReminder());
    }

    public function test_appointment_mark_reminder_sent_method()
    {
        $this->appointment->markReminderSent();

        $this->assertNotNull($this->appointment->reminder_sent_at);
        $this->assertTrue($this->appointment->reminder_sent_at->isToday());
    }
}
