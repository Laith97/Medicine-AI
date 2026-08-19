<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Claim;
use App\Models\Doctor;
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
        $this->patient = User::factory()->create([
            'role' => 'patient',
            'name' => 'Test Patient',
            'email' => 'patient@example.com',
            'phone' => '1234567890',
        ]);

        // Create an insurance provider
        $this->insuranceProvider = InsuranceProvider::factory()->create([
            'name' => 'Test Insurance',
        ]);
    }

    public function test_claim_can_be_created()
    {
        $claim = Claim::create([
            'patient_id' => $this->patient->id,
            'diagnosis_text' => 'Test diagnosis',
            'procedure_text' => 'Test procedure',
            'icd10_codes' => ['A00.0'],
            'cpt_codes' => ['99213'],
            'payer' => $this->insuranceProvider->name,
            'claim_status' => 'submitted',
            'expected_amount' => 100.00,
            'service_date' => '2023-01-01',
        ]);

        $this->assertDatabaseHas('claims', [
            'id' => $claim->id,
            'patient_id' => $this->patient->id,
            'claim_status' => 'submitted',
        ]);
        $this->assertEquals('Test diagnosis', $claim->diagnosis_text);
        $this->assertEquals(['A00.0'], $claim->icd10_codes);
        $this->assertNotNull($claim->claim_id);
    }

    public function test_claim_can_be_updated()
    {
        $claim = Claim::create([
            'patient_id' => $this->patient->id,
            'diagnosis_text' => 'Old diagnosis',
            'claim_status' => 'submitted',
            'expected_amount' => 50.00,
            'service_date' => '2023-01-01',
        ]);

        $claim->update([
            'diagnosis_text' => 'New diagnosis',
            'expected_amount' => 150.00,
            'claim_status' => 'approved',
        ]);

        $this->assertDatabaseHas('claims', [
            'id' => $claim->id,
            'diagnosis_text' => 'New diagnosis',
            'expected_amount' => 150.00,
            'claim_status' => 'approved',
        ]);
    }

    public function test_claim_can_be_deleted()
    {
        $claim = Claim::create([
            'patient_id' => $this->patient->id,
            'diagnosis_text' => 'Test diagnosis',
            'claim_status' => 'submitted',
            'expected_amount' => 100.00,
            'service_date' => '2023-01-01',
        ]);

        $claim->delete();

        $this->assertDatabaseMissing('claims', [
            'id' => $claim->id,
        ]);
    }

    public function test_claim_status_workflow()
    {
        $claim = Claim::create([
            'patient_id' => $this->patient->id,
            'diagnosis_text' => 'Test diagnosis',
            'claim_status' => 'submitted',
            'expected_amount' => 100.00,
            'service_date' => '2023-01-01',
        ]);

        // Test status transitions
        $claim->update(['claim_status' => 'pending']);
        $this->assertEquals('pending', $claim->fresh()->claim_status);

        $claim->update(['claim_status' => 'approved']);
        $this->assertEquals('approved', $claim->fresh()->claim_status);

        $claim->update(['claim_status' => 'denied']);
        $this->assertEquals('denied', $claim->fresh()->claim_status);

        $claim->update(['claim_status' => 'paid']);
        $this->assertEquals('paid', $claim->fresh()->claim_status);

        // Scopes
        $deniedClaim = Claim::create([
            'patient_id' => $this->patient->id,
            'diagnosis_text' => 'Denied diagnosis',
            'claim_status' => 'denied',
            'expected_amount' => 50.00,
            'service_date' => '2023-01-01',
        ]);
        $this->assertTrue(Claim::denied()->where('id', $deniedClaim->id)->exists());
        $this->assertTrue(Claim::paid()->where('id', $claim->id)->exists());
    }

    public function test_claim_denial_code_normalization()
    {
        $claim = Claim::create([
            'patient_id' => $this->patient->id,
            'diagnosis_text' => 'Test diagnosis',
            'claim_status' => 'denied',
            'raw_denial_code' => '16',
            'expected_amount' => 100.00,
            'service_date' => '2023-01-01',
        ]);

        $this->assertEquals('documentation_missing', Claim::normalizeDenialCode('16'));
        $this->assertEquals('coding_error', Claim::normalizeDenialCode('4'));
        $this->assertEquals('coverage_issue', Claim::normalizeDenialCode('1'));
        $this->assertEquals('medical_necessity', Claim::normalizeDenialCode('50'));
        $this->assertEquals('timely_filing', Claim::normalizeDenialCode('54'));
        $this->assertEquals('other', Claim::normalizeDenialCode('999'));
    }

    public function test_clearinghouse_submission_creation()
    {
        $account = ClearinghouseAccount::factory()->create([
            'name' => 'Test Account',
            'provider' => 'Availity',
            'is_active' => true,
        ]);

        $submission = ClearinghouseSubmission::create([
            'clearinghouse_account_id' => $account->id,
            'batch_id' => Str::random(10),
            'claim_count' => 1,
            'total_amount' => 100.00,
            'status' => 'pending',
            'submission_type' => '837P',
            'edi_content' => 'ISA*00*...',
        ]);

        $claim = Claim::create([
            'patient_id' => $this->patient->id,
            'diagnosis_text' => 'Test diagnosis',
            'claim_status' => 'pending',
            'expected_amount' => 100.00,
            'service_date' => '2023-01-01',
            'clearinghouse_submission_id' => $submission->id,
        ]);

        $this->assertDatabaseHas('clearinghouse_submissions', [
            'clearinghouse_account_id' => $account->id,
            'status' => 'pending',
            'submission_type' => '837P',
        ]);

        $this->assertTrue($submission->claims->contains($claim));
    }

    public function test_claim_with_insurance_information()
    {
        $patientData = \App\Models\PatientData::factory()->create([
            'user_id' => $this->patient->id,
        ]);

        $insurance = PatientInsurance::create([
            'patient_id' => $patientData->id,
            'insurance_provider_id' => $this->insuranceProvider->id,
            'policy_number' => 'POL123456',
            'group_number' => 'GRP123',
            'subscriber_id' => 'SUB123456',
            'relationship_to_subscriber' => 'self',
            'effective_date' => '2023-01-01',
        ]);

        $claim = Claim::create([
            'patient_id' => $this->patient->id,
            'diagnosis_text' => 'Test diagnosis',
            'payer' => $this->insuranceProvider->name,
            'claim_status' => 'submitted',
            'expected_amount' => 100.00,
            'service_date' => '2023-01-01',
        ]);

        $this->assertDatabaseHas('claims', [
            'id' => $claim->id,
            'payer' => 'Test Insurance',
        ]);
        $this->assertEquals($insurance->policy_number, 'POL123456');
    }

    public function test_claim_payment_difference_detection()
    {
        $claim = Claim::create([
            'patient_id' => $this->patient->id,
            'diagnosis_text' => 'Test diagnosis',
            'claim_status' => 'partially_paid',
            'expected_amount' => 100.00,
            'paid_amount' => 60.00,
            'service_date' => '2023-01-01',
        ]);

        $this->assertEquals(60.00, $claim->paid_amount);
        $this->assertEquals(100.00, $claim->expected_amount);
        $this->assertEquals(40.00, $claim->calculatePaymentDifference());
    }
}
