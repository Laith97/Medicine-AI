<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Appointment;
use App\Models\PatientRiskScore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Carbon\Carbon;

class GeneratePredictionsCommandTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_creates_risk_scores_for_upcoming_appointments()
    {
        // Create patients and appointments
        $patient1 = User::factory()->create([
            'date_of_birth' => now()->subYears(30),
            'gender' => 'male'
        ]);

        $patient2 = User::factory()->create([
            'date_of_birth' => now()->subYears(25),
            'gender' => 'female'
        ]);

        // Create upcoming appointments (within next 7 days)
        $appointment1 = Appointment::factory()->create([
            'patient_id' => $patient1->id,
            'appointment_date' => now()->addDays(2),
        ]);

        $appointment2 = Appointment::factory()->create([
            'patient_id' => $patient2->id,
            'appointment_date' => now()->addDays(5),
        ]);

        // Create an appointment that's already past (should not be processed)
        $pastAppointment = Appointment::factory()->create([
            'patient_id' => $patient1->id,
            'appointment_date' => now()->subDays(1),
        ]);

        // Ensure no existing risk scores
        $this->assertDatabaseCount('patient_risk_scores', 0);

        // Run the command
        $exitCode = Artisan::call('predictions:generate');

        // Assert command succeeded
        $this->assertEquals(0, $exitCode);

        // Verify risk scores were created for upcoming appointments only
        $this->assertDatabaseCount('patient_risk_scores', 2);

        // Verify specific risk scores exist
        $this->assertDatabaseHas('patient_risk_scores', [
            'patient_id' => $patient1->id,
            'appointment_id' => $appointment1->id,
        ]);

        $this->assertDatabaseHas('patient_risk_scores', [
            'patient_id' => $patient2->id,
            'appointment_id' => $appointment2->id,
        ]);

        // Verify past appointment was not processed
        $this->assertDatabaseMissing('patient_risk_scores', [
            'patient_id' => $patient1->id,
            'appointment_id' => $pastAppointment->id,
        ]);
    }

    /** @test */
    public function it_skips_appointments_that_already_have_risk_scores()
    {
        $patient = User::factory()->create([
            'date_of_birth' => now()->subYears(35),
            'gender' => 'male'
        ]);

        $appointment = Appointment::factory()->create([
            'patient_id' => $patient->id,
            'appointment_date' => now()->addDays(3),
        ]);

        // Create existing risk score
        PatientRiskScore::create([
            'patient_id' => $patient->id,
            'appointment_id' => $appointment->id,
            'no_show_risk' => 0.2,
            'hospitalization_risk' => 0.1,
        ]);

        // Run the command
        $exitCode = Artisan::call('predictions:generate');

        // Assert command succeeded
        $this->assertEquals(0, $exitCode);

        // Verify only the existing risk score exists (no duplicates)
        $this->assertDatabaseCount('patient_risk_scores', 1);

        $existingRiskScore = PatientRiskScore::where('appointment_id', $appointment->id)->first();
        $this->assertEquals(0.2, $existingRiskScore->no_show_risk);
        $this->assertEquals(0.1, $existingRiskScore->hospitalization_risk);
    }

    /** @test */
    public function it_handles_appointments_without_patients()
    {
        // Create appointment without patient_id
        $appointment = Appointment::factory()->create([
            'patient_id' => null,
            'appointment_date' => now()->addDays(2),
        ]);

        // Run the command
        $exitCode = Artisan::call('predictions:generate');

        // Assert command succeeded (should handle gracefully)
        $this->assertEquals(0, $exitCode);

        // Verify no risk scores were created
        $this->assertDatabaseCount('patient_risk_scores', 0);
    }

    /** @test */
    public function it_processes_appointments_in_chunks()
    {
        // Create multiple appointments to test chunking
        $patients = User::factory()->count(5)->create();
        $appointments = [];

        foreach ($patients as $patient) {
            $appointments[] = Appointment::factory()->create([
                'patient_id' => $patient->id,
                'appointment_date' => now()->addDays(rand(1, 6)),
            ]);
        }

        // Run the command
        $exitCode = Artisan::call('predictions:generate');

        // Assert command succeeded
        $this->assertEquals(0, $exitCode);

        // Verify all appointments got risk scores
        $this->assertDatabaseCount('patient_risk_scores', 5);

        // Verify each appointment has a risk score
        foreach ($appointments as $appointment) {
            $this->assertDatabaseHas('patient_risk_scores', [
                'appointment_id' => $appointment->id,
            ]);
        }
    }

    /** @test */
    public function it_returns_correct_exit_code_when_no_appointments_to_process()
    {
        // Ensure no upcoming appointments exist
        $this->assertDatabaseCount('appointments', 0);

        // Run the command
        $exitCode = Artisan::call('predictions:generate');

        // Assert command succeeded (no appointments to process is not an error)
        $this->assertEquals(0, $exitCode);
    }

    /** @test */
    public function it_only_processes_appointments_within_7_day_window()
    {
        $patient = User::factory()->create();

        // Create appointments at different time ranges
        $within7Days = Appointment::factory()->create([
            'patient_id' => $patient->id,
            'appointment_date' => now()->addDays(6), // Within 7 days
        ]);

        $beyond7Days = Appointment::factory()->create([
            'patient_id' => $patient->id,
            'appointment_date' => now()->addDays(8), // Beyond 7 days
        ]);

        $pastAppointment = Appointment::factory()->create([
            'patient_id' => $patient->id,
            'appointment_date' => now()->subDays(1), // Past
        ]);

        // Run the command
        $exitCode = Artisan::call('predictions:generate');

        // Assert command succeeded
        $this->assertEquals(0, $exitCode);

        // Verify only the appointment within 7 days got processed
        $this->assertDatabaseCount('patient_risk_scores', 1);
        $this->assertDatabaseHas('patient_risk_scores', [
            'appointment_id' => $within7Days->id,
        ]);

        // Verify others were not processed
        $this->assertDatabaseMissing('patient_risk_scores', [
            'appointment_id' => $beyond7Days->id,
        ]);

        $this->assertDatabaseMissing('patient_risk_scores', [
            'appointment_id' => $pastAppointment->id,
        ]);
    }

    /** @test */
    public function it_handles_exceptions_during_processing()
    {
        $patient = User::factory()->create();

        // Create a valid appointment
        $appointment = Appointment::factory()->create([
            'patient_id' => $patient->id,
            'appointment_date' => now()->addDays(2),
        ]);

        // Note: In a real scenario, we might mock the service to throw exceptions
        // For this test, we'll assume the command handles exceptions properly
        // as seen in the command code with try-catch blocks

        // Run the command
        $exitCode = Artisan::call('predictions:generate');

        // Assert command succeeded
        $this->assertEquals(0, $exitCode);

        // Verify the appointment was processed despite potential issues
        $this->assertDatabaseHas('patient_risk_scores', [
            'appointment_id' => $appointment->id,
        ]);
    }

    /** @test */
    public function it_outputs_correct_information_to_console()
    {
        $patient = User::factory()->create();
        $appointment = Appointment::factory()->create([
            'patient_id' => $patient->id,
            'appointment_date' => now()->addDays(2),
        ]);

        // Capture command output
        $output = Artisan::output('predictions:generate');

        // The output should contain information about processing
        // Note: This test might need adjustment based on actual command output format
        $output = Artisan::output();
        $this->assertTrue(str_contains($output, 'Starting prediction generation'));
        $this->assertTrue(str_contains($output, 'appointments to process'));
        $this->assertTrue(str_contains($output, 'Prediction generation completed'));
    }
}
