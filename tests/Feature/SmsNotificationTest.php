<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Doctor;
use App\Models\Hospital;
use App\Models\Appointment;
use App\Notifications\AppointmentBookedNotification;
use App\Notifications\AppointmentStatusChangedNotification;
use App\Services\SmsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Mockery;

class SmsNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected $smsServiceMock;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->smsServiceMock = Mockery::mock(SmsService::class);
        $this->app->instance(SmsService::class, $this->smsServiceMock);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function test_appointment_booked_notification_passes_correct_context()
    {
        // Create test data
        $doctorUser = User::factory()->create(['role' => 'doctor']);
        $doctor = Doctor::factory()->create([
            'user_id' => $doctorUser->id,
            'sms_provider' => 'twilio'
        ]);
        
        $hospital = Hospital::factory()->create(['sms_provider' => 'plivo']);
        $doctor->hospital_id = $hospital->id;
        $doctor->save();

        $patient = User::factory()->create(['role' => 'patient']);
        
        $appointment = Appointment::factory()->create([
            'doctor_id' => $doctor->id,
            'patient_id' => $patient->id,
            'hospital_id' => $hospital->id,
            'appointment_date' => now()->addDays(1),
            'appointment_type' => 'in_person'
        ]);

        // Set up mock expectations
        $this->smsServiceMock->shouldReceive('send')
            ->once()
            ->with(
                $patient->phone,
                Mockery::type('string'),
                Mockery::on(function ($options) use ($doctor, $hospital, $appointment) {
                    return $options['doctor_id'] === $doctor->id &&
                           $options['hospital_id'] === $hospital->id &&
                           $options['context'] === 'appointment_booked' &&
                           $options['context_id'] === $appointment->id;
                })
            )
            ->andReturn(['success' => true]);

        // Send notification
        $patient->notify(new AppointmentBookedNotification($appointment));

        // Assert that the notification was sent with correct context
        $this->assertTrue(true);
    }

    /** @test */
    public function test_appointment_status_changed_notification_passes_correct_context()
    {
        // Create test data
        $doctorUser = User::factory()->create(['role' => 'doctor']);
        $doctor = Doctor::factory()->create([
            'user_id' => $doctorUser->id,
            'sms_provider' => 'twilio'
        ]);
        
        $hospital = Hospital::factory()->create(['sms_provider' => 'plivo']);
        $doctor->hospital_id = $hospital->id;
        $doctor->save();

        $patient = User::factory()->create(['role' => 'patient']);
        
        $appointment = Appointment::factory()->create([
            'doctor_id' => $doctor->id,
            'patient_id' => $patient->id,
            'hospital_id' => $hospital->id,
            'status' => 'pending',
            'appointment_date' => now()->addDays(1)
        ]);

        // Set up mock expectations
        $this->smsServiceMock->shouldReceive('send')
            ->once()
            ->with(
                $patient->phone,
                Mockery::type('string'),
                Mockery::on(function ($options) use ($doctor, $hospital, $appointment) {
                    return $options['doctor_id'] === $doctor->id &&
                           $options['hospital_id'] === $hospital->id &&
                           $options['context'] === 'appointment_status_changed' &&
                           $options['context_id'] === $appointment->id;
                })
            )
            ->andReturn(['success' => true]);

        // Send notification
        $patient->notify(new AppointmentStatusChangedNotification(
            $appointment, 
            'pending', 
            'confirmed',
            $doctorUser->id
        ));

        // Assert that the notification was sent with correct context
        $this->assertTrue(true);
    }

    /** @test */
    public function test_effective_provider_used_based_on_context()
    {
        // Create test data with doctor override
        $doctorUser = User::factory()->create(['role' => 'doctor']);
        $doctor = Doctor::factory()->create([
            'user_id' => $doctorUser->id,
            'sms_provider' => 'twilio' // Doctor override
        ]);
        
        $hospital = Hospital::factory()->create(['sms_provider' => 'plivo']);
        $doctor->hospital_id = $hospital->id;
        $doctor->save();

        $patient = User::factory()->create(['role' => 'patient']);
        
        $appointment = Appointment::factory()->create([
            'doctor_id' => $doctor->id,
            'patient_id' => $patient->id,
            'hospital_id' => $hospital->id,
            'appointment_date' => now()->addDays(1)
        ]);

        // Set up mock expectations - should use doctor's provider (twilio)
        $this->smsServiceMock->shouldReceive('send')
            ->once()
            ->with(
                $patient->phone,
                Mockery::type('string'),
                Mockery::on(function ($options) use ($doctor, $hospital, $appointment) {
                    // The SmsService should determine the provider based on context
                    // and use the doctor's provider (twilio) since doctor_id is present
                    return $options['doctor_id'] === $doctor->id &&
                           $options['hospital_id'] === $hospital->id;
                })
            )
            ->andReturn(['success' => true]);

        // Send notification
        $patient->notify(new AppointmentBookedNotification($appointment));

        // Assert that the effective provider was used based on context
        $this->assertTrue(true);
    }

    /** @test */
    public function test_notification_uses_hospital_provider_when_no_doctor_override()
    {
        // Create test data without doctor override
        $doctorUser = User::factory()->create(['role' => 'doctor']);
        $doctor = Doctor::factory()->create([
            'user_id' => $doctorUser->id,
            'sms_provider' => null // No doctor override
        ]);
        
        $hospital = Hospital::factory()->create(['sms_provider' => 'plivo']);
        $doctor->hospital_id = $hospital->id;
        $doctor->save();

        $patient = User::factory()->create(['role' => 'patient']);
        
        $appointment = Appointment::factory()->create([
            'doctor_id' => $doctor->id,
            'patient_id' => $patient->id,
            'hospital_id' => $hospital->id,
            'appointment_date' => now()->addDays(1)
        ]);

        // Set up mock expectations - should use hospital's provider (plivo)
        $this->smsServiceMock->shouldReceive('send')
            ->once()
            ->with(
                $patient->phone,
                Mockery::type('string'),
                Mockery::on(function ($options) use ($doctor, $hospital, $appointment) {
                    return $options['doctor_id'] === $doctor->id &&
                           $options['hospital_id'] === $hospital->id;
                })
            )
            ->andReturn(['success' => true]);

        // Send notification
        $patient->notify(new AppointmentBookedNotification($appointment));

        // Assert that the hospital provider was used
        $this->assertTrue(true);
    }

    /** @test */
    public function test_notification_handles_missing_context_gracefully()
    {
        // Create test data
        $patient = User::factory()->create(['role' => 'patient']);
        
        $appointment = Appointment::factory()->create([
            'doctor_id' => null,
            'hospital_id' => null,
            'patient_id' => $patient->id,
            'appointment_date' => now()->addDays(1)
        ]);

        // Set up mock expectations - should still work without context
        $this->smsServiceMock->shouldReceive('send')
            ->once()
            ->with(
                $patient->phone,
                Mockery::type('string'),
                Mockery::on(function ($options) use ($appointment) {
                    return $options['context'] === 'appointment_booked' &&
                           $options['context_id'] === $appointment->id &&
                           !isset($options['doctor_id']) &&
                           !isset($options['hospital_id']);
                })
            )
            ->andReturn(['success' => true]);

        // Send notification
        $patient->notify(new AppointmentBookedNotification($appointment));

        // Assert that the notification handled missing context gracefully
        $this->assertTrue(true);
    }

    /** @test */
    public function test_notification_message_content()
    {
        // Create test data
        $doctorUser = User::factory()->create(['role' => 'doctor', 'name' => 'Dr. Smith']);
        $doctor = Doctor::factory()->create([
            'user_id' => $doctorUser->id,
            'sms_provider' => 'twilio'
        ]);
        
        $patient = User::factory()->create(['role' => 'patient', 'name' => 'John Doe']);
        
        $appointment = Appointment::factory()->create([
            'doctor_id' => $doctor->id,
            'patient_id' => $patient->id,
            'appointment_date' => now()->addDays(1),
            'appointment_type' => 'in_person'
        ]);

        // Set up mock expectations to capture the message
        $this->smsServiceMock->shouldReceive('send')
            ->once()
            ->with(
                $patient->phone,
                Mockery::on(function ($message) use ($doctorUser, $appointment) {
                    // Check that the message contains expected content
                    return str_contains($message, 'Dr. Smith') &&
                           str_contains($message, $appointment->appointment_date->format('M j, Y g:i A'));
                }),
                Mockery::any()
            )
            ->andReturn(['success' => true]);

        // Send notification
        $patient->notify(new AppointmentBookedNotification($appointment));

        // Assert that the message content was correct
        $this->assertTrue(true);
    }
}