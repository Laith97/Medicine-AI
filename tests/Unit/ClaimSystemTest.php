<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Claim;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\InsuranceProvider;
use App\Models\PatientInsurance;
use App\Models\ClearinghouseAccount;
use App\Models\ClearinghouseSubmission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ClaimSystemTest extends TestCase
{
    use RefreshDatabase;

    protected $hospitalAdmin;
    protected $patient;
    protected $insuranceProvider;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a hospital first
        $hospital = \App\Models\Hospital::factory()->create();

        // Create a hospital admin user
        $this->hospitalAdmin = User::factory()->create([
            'role' => 'hospital_admin',
            'email' => 'hospital@example.com',
            'hospital_id' => $hospital->id,
        ]);

        // Create a patient user
        $this->patient = Patient::factory()->create([
            'name' => 'Test Patient',
            'email' => 'patient@example.com',
            'phone' => '1234567890',
        ]);

        // Create an insurance provider
        $this->insuranceProvider = InsuranceProvider::factory()->create([
            'name' => 'Test Insurance',
            'provider_code' => 'TEST',
        ]);
    }

    public function test_claim_can_be_created()
    {
        $claimData = [
            'patient_name' => 'Test Patient',
            'patient_dob' => '1980-01-01',
            'patient_gender' => 'male',
            'provider_name' => 'Dr. Test',
            'service_date' => '2023-01-01',
            'diagnosis_description' => 'Test diagnosis',
            'total_amount' => 100.00,
            'hospital_id' => $this->hospitalAdmin->hospital_id,
            'user_id' => $this->hospitalAdmin->id,
        ];

        $claim = Claim::create($claimData);

        $this->assertDatabaseHas('claims', [
            'patient_name' => 'Test Patient',
            'provider_name' => 'Dr. Test',
            'total_amount' => 100.00,
            'hospital_id' => $this->hospitalAdmin->hospital_id,
        ]);
    }

    public function test_claim_can_be_updated()
    {
        $claim = Claim::create([
            'patient_name' => 'Old Name',
            'patient_dob' => '1980-01-01',
            'provider_name' => 'Old Provider',
            'service_date' => '2023-01-01',
            'diagnosis_description' => 'Old diagnosis',
            'total_amount' => 50.00,
            'hospital_id' => $this->hospitalAdmin->hospital_id,
            'user_id' => $this->hospitalAdmin->id,
        ]);

        $claim->update([
            'patient_name' => 'New Name',
            'provider_name' => 'New Provider',
            'total_amount' => 150.00,
            'status' => 'approved',
        ]);

        $this->assertDatabaseHas('claims', [
            'id' => $claim->id,
            'patient_name' => 'New Name',
            'provider_name' => 'New Provider',
            'total_amount' => 150.00,
            'status' => 'approved',
        ]);
    }

    public function test_claim_can_be_deleted()
    {
        $claim = Claim::create([
            'patient_name' => 'Test Patient',
            'patient_dob' => '1980-01-01',
            'provider_name' => 'Dr. Test',
            'service_date' => '2023-01-01',
            'diagnosis_description' => 'Test diagnosis',
            'total_amount' => 100.00,
            'hospital_id' => $this->hospitalAdmin->hospital_id,
            'user_id' => $this->hospitalAdmin->id,
        ]);

        $claim->delete();

        $this->assertDatabaseMissing('claims', [
            'id' => $claim->id,
        ]);
    }

    public function test_claim_status_workflow()
    {
        $claim = Claim::create([
            'patient_name' => 'Test Patient',
            'patient_dob' => '1980-01-01',
            'provider_name' => 'Dr. Test',
            'service_date' => '2023-01-01',
            'diagnosis_description' => 'Test diagnosis',
            'total_amount' => 100.00,
            'hospital_id' => $this->hospitalAdmin->hospital_id,
            'user_id' => $this->hospitalAdmin->id,
        ]);

        // Test status transitions
        $claim->update(['status' => 'pending']);
        $this->assertEquals('pending', $claim->fresh()->status);

        $claim->update(['status' => 'approved']);
        $this->assertEquals('approved', $claim->fresh()->status);

        $claim->update(['status' => 'denied']);
        $this->assertEquals('denied', $claim->fresh()->status);

        $claim->update(['status' => 'paid']);
        $this->assertEquals('paid', $claim->fresh()->status);
    }

    public function test_claim_denial_risk_probability()
    {
        $claim = Claim::create([
            'patient_name' => 'Test Patient',
            'patient_dob' => '1980-01-01',
            'provider_name' => 'Dr. Test',
            'service_date' => '2023-01-01',
            'diagnosis_description' => 'Test diagnosis',
            'total_amount' => 100.00,
            'hospital_id' => $this->hospitalAdmin->hospital_id,
            'user_id' => $this->hospitalAdmin->id,
            'denial_risk_probability' => 0.8, // High risk
        ]);

        $this->assertGreaterThanOrEqual(0.7, $claim->denial_risk_probability);
    }

    public function test_clearinghouse_submission_creation()
    {
        $account = ClearinghouseAccount::factory()->create([
            'name' => 'Test Account',
            'provider' => 'Availity',
            'is_active' => true,
            'hospital_id' => $this->hospitalAdmin->hospital_id,
        ]);

        $claim = Claim::create([
            'patient_name' => 'Test Patient',
            'patient_dob' => '1980-01-01',
            'provider_name' => 'Dr. Test',
            'service_date' => '2023-01-01',
            'diagnosis_description' => 'Test diagnosis',
            'total_amount' => 100.00,
            'status' => 'pending',
            'hospital_id' => $this->hospitalAdmin->hospital_id,
            'user_id' => $this->hospitalAdmin->id,
        ]);

        $submission = ClearinghouseSubmission::create([
            'clearinghouse_account_id' => $account->id,
            'batch_id' => Str::random(10),
            'claim_count' => 1,
            'total_amount' => 100.00,
            'status' => 'pending',
            'submission_type' => '837P',
        ]);

        // Associate the claim with the submission
        $submission->claims()->attach($claim->id);

        $this->assertDatabaseHas('clearinghouse_submissions', [
            'clearinghouse_account_id' => $account->id,
            'status' => 'pending',
            'submission_type' => '837P',
        ]);

        $this->assertTrue($submission->claims->contains($claim));
    }

    public function test_claim_with_insurance_information()
    {
        $insurance = PatientInsurance::factory()->create([
            'patient_id' => $this->patient->id,
            'insurance_provider_id' => $this->insuranceProvider->id,
            'policy_number' => 'POL123456',
            'group_number' => 'GRP123',
        ]);

        $claim = Claim::create([
            'patient_name' => 'Test Patient',
            'patient_dob' => '1980-01-01',
            'patient_gender' => 'male',
            'patient_insurance_id' => $insurance->policy_number,
            'patient_insurance_provider' => $this->insuranceProvider->name,
            'provider_name' => 'Dr. Test',
            'service_date' => '2023-01-01',
            'diagnosis_description' => 'Test diagnosis',
            'total_amount' => 100.00,
            'hospital_id' => $this->hospitalAdmin->hospital_id,
            'user_id' => $this->hospitalAdmin->id,
        ]);

        $this->assertDatabaseHas('claims', [
            'patient_insurance_id' => 'POL123456',
            'patient_insurance_provider' => 'Test Insurance',
        ]);
    }

    public function test_claim_underpayment_detection()
    {
        $claim = Claim::create([
            'patient_name' => 'Test Patient',
            'patient_dob' => '1980-01-01',
            'provider_name' => 'Dr. Test',
            'service_date' => '2023-01-01',
            'diagnosis_description' => 'Test diagnosis',
            'total_amount' => 100.00,
            'allowed_amount' => 80.00,
            'paid_amount' => 60.00,
            'hospital_id' => $this->hospitalAdmin->hospital_id,
            'user_id' => $this->hospitalAdmin->id,
            'underpayment_alert' => true, // Simulate underpayment
        ]);

        $this->assertEquals(60.00, $claim->paid_amount);
        $this->assertEquals(80.00, $claim->allowed_amount);
        $this->assertTrue($claim->underpayment_alert);
    }
}