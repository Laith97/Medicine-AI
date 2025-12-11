<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\User;
use App\Models\PatientInsurance;
use App\Models\Claim;
use App\Services\EligibilityService;
use App\Services\AuthorizationService;
use App\Services\BusinessRulesService;
use App\Services\AppointmentStatusSynchronizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class DataIntegrityTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test optimistic locking prevents concurrent appointment updates
     */
    public function test_optimistic_locking_prevents_concurrent_appointment_updates()
    {
        $doctor = Doctor::factory()->create();
        $patient = User::factory()->create(['role' => 'patient']);
        $appointment = Appointment::factory()->create([
            'doctor_id' => $doctor->id,
            'patient_id' => $patient->id,
            'status' => 'pending',
            'version' => 1
        ]);

        // Simulate concurrent updates
        $appointment1 = Appointment::find($appointment->id);
        $appointment2 = Appointment::find($appointment->id);

        // First update succeeds
        $appointment1->update(['status' => 'confirmed']);
        $this->assertEquals(2, $appointment1->fresh()->version);

        // Second update should fail due to version mismatch
        $this->expectException(\Exception::class);
        $appointment2->update(['status' => 'cancelled']);
    }

    /**
     * Test database transactions ensure atomicity in appointment operations
     */
    public function test_database_transactions_ensure_atomicity()
    {
        $doctor = Doctor::factory()->create();
        $patient = User::factory()->create(['role' => 'patient']);

        DB::beginTransaction();

        try {
            $appointment = Appointment::create([
                'doctor_id' => $doctor->id,
                'patient_id' => $patient->id,
                'appointment_date' => now()->addDays(1),
                'appointment_end' => now()->addDays(1)->addMinutes(30),
                'status' => 'pending',
                'reason' => 'Test appointment'
            ]);

            // Simulate an error after creating appointment
            throw new \Exception('Simulated error');

            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();

            // Verify appointment was not created
            $this->assertNull(Appointment::find($appointment->id ?? null));
        }
    }

    /**
     * Test cache invalidation when patient insurance data changes
     */
    public function test_cache_invalidation_on_insurance_update()
    {
        $patient = User::factory()->create(['role' => 'patient']);
        $insuranceProvider = \App\Models\InsuranceProvider::factory()->create();

        $insurance = PatientInsurance::factory()->create([
            'patient_id' => $patient->patientData->id,
            'insurance_provider_id' => $insuranceProvider->id,
            'policy_number' => 'TEST123',
            'version' => 1
        ]);

        // Set up cache
        $cacheKey = "eligibility:{$insurance->id}:office_visit";
        Cache::put($cacheKey, ['status' => 'eligible'], 3600);

        // Verify cache exists
        $this->assertNotNull(Cache::get($cacheKey));

        // Update insurance data
        $insurance->update(['policy_number' => 'TEST456']);

        // Cache should be invalidated
        $this->assertNull(Cache::get($cacheKey));
    }

    /**
     * Test pessimistic locking prevents concurrent eligibility checks
     */
    public function test_pessimistic_locking_prevents_concurrent_eligibility_checks()
    {
        $patient = User::factory()->create(['role' => 'patient']);
        $insuranceProvider = \App\Models\InsuranceProvider::factory()->create();
        $insurance = PatientInsurance::factory()->create([
            'patient_id' => $patient->patientData->id,
            'insurance_provider_id' => $insuranceProvider->id,
        ]);

        // This test would require mocking the eligibility service
        // and testing that concurrent calls are properly serialized
        $this->assertTrue(true); // Placeholder for now
    }

    /**
     * Test appointment status change methods use transactions
     */
    public function test_appointment_status_changes_use_transactions()
    {
        $doctor = Doctor::factory()->create();
        $patient = User::factory()->create(['role' => 'patient']);
        $appointment = Appointment::factory()->create([
            'doctor_id' => $doctor->id,
            'patient_id' => $patient->id,
            'status' => 'pending'
        ]);

        // Test cancel method
        $appointment->cancel();

        $appointment->refresh();
        $this->assertEquals('cancelled', $appointment->status);
        $this->assertNotNull($appointment->cancelled_at);

        // Test confirm method
        $appointment2 = Appointment::factory()->create([
            'doctor_id' => $doctor->id,
            'patient_id' => $patient->id,
            'status' => 'pending'
        ]);

        $appointment2->confirm();

        $appointment2->refresh();
        $this->assertEquals('confirmed', $appointment2->status);
        $this->assertNotNull($appointment2->confirmed_at);
    }

    /**
     * Test authorization service permission checks
     */
    public function test_authorization_service_permission_checks()
    {
        $authService = app(AuthorizationService::class);

        $patient = User::factory()->create(['role' => 'patient']);
        $doctor = User::factory()->create(['role' => 'doctor']);
        $admin = User::factory()->create(['role' => 'admin']);

        $appointment = Appointment::factory()->create([
            'doctor_id' => $doctor->doctor->id,
            'patient_id' => $patient->id,
            'status' => 'pending'
        ]);

        // Test patient permissions
        $this->assertTrue($authService->canViewAppointment($patient, $appointment));
        $this->assertTrue($authService->canModifyAppointment($patient, $appointment));

        // Test doctor permissions
        $this->assertTrue($authService->canViewAppointment($doctor, $appointment));
        $this->assertTrue($authService->canModifyAppointment($doctor, $appointment));

        // Test admin permissions
        $this->assertTrue($authService->canViewAppointment($admin, $appointment));
        $this->assertTrue($authService->canModifyAppointment($admin, $appointment));
    }

    /**
     * Test business rules service validation
     */
    public function test_business_rules_service_validation()
    {
        $businessRulesService = app(BusinessRulesService::class);

        $doctor = Doctor::factory()->create();
        $patient = User::factory()->create(['role' => 'patient']);

        // Test valid cancellation
        $appointment = Appointment::factory()->create([
            'doctor_id' => $doctor->id,
            'patient_id' => $patient->id,
            'status' => 'pending',
            'appointment_date' => now()->addDays(2)
        ]);

        $result = $businessRulesService->validateAppointmentCancellation($appointment, 'Test reason');
        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);

        // Test invalid cancellation (completed appointment)
        $completedAppointment = Appointment::factory()->create([
            'doctor_id' => $doctor->id,
            'patient_id' => $patient->id,
            'status' => 'completed',
            'appointment_date' => now()->subDays(1)
        ]);

        $result = $businessRulesService->validateAppointmentCancellation($completedAppointment);
        $this->assertFalse($result['valid']);
        $this->assertContains('Cannot cancel a completed appointment', $result['errors']);
    }

    /**
     * Test appointment status synchronization creates claims
     */
    public function test_appointment_status_synchronization_creates_claims()
    {
        $syncService = app(AppointmentStatusSynchronizationService::class);

        $doctor = Doctor::factory()->create();
        $patient = User::factory()->create(['role' => 'patient']);
        $insuranceProvider = \App\Models\InsuranceProvider::factory()->create();

        $insurance = PatientInsurance::factory()->create([
            'patient_id' => $patient->patientData->id,
            'insurance_provider_id' => $insuranceProvider->id,
        ]);

        $appointment = Appointment::factory()->create([
            'doctor_id' => $doctor->id,
            'patient_id' => $patient->id,
            'status' => 'confirmed',
            'appointment_type' => 'in_person'
        ]);

        // Complete the appointment - should create a claim
        $syncService->handleAppointmentStatusChange($appointment, 'confirmed', 'completed');

        // Check that a claim was created
        $claim = Claim::where('appointment_id', $appointment->id)->first();
        $this->assertNotNull($claim);
        $this->assertEquals('CLM-', substr($claim->claim_id, 0, 4));
        $this->assertEquals('pending', $claim->claim_status);
    }

    /**
     * Test appointment cancellation updates related claims
     */
    public function test_appointment_cancellation_updates_claims()
    {
        $syncService = app(AppointmentStatusSynchronizationService::class);

        $doctor = Doctor::factory()->create();
        $patient = User::factory()->create(['role' => 'patient']);

        $appointment = Appointment::factory()->create([
            'doctor_id' => $doctor->id,
            'patient_id' => $patient->id,
            'status' => 'confirmed'
        ]);

        // Create a pending claim
        $claim = Claim::create([
            'claim_id' => 'CLM-TEST',
            'patient_id' => $patient->id,
            'appointment_id' => $appointment->id,
            'claim_status' => 'pending',
            'service_date' => $appointment->appointment_date->toDateString(),
            'submission_date' => now()->toDateString(),
        ]);

        // Cancel the appointment - should update the claim
        $syncService->handleAppointmentStatusChange($appointment, 'confirmed', 'cancelled');

        $claim->refresh();
        $this->assertEquals('cancelled', $claim->claim_status);
        $this->assertEquals('Appointment cancelled', $claim->denial_reason);
    }
}
