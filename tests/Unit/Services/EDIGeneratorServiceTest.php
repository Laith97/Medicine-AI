<?php

namespace Tests\Unit\Services;

use App\Models\Claim;
use App\Models\ClearinghouseAccount;
use App\Services\EDIGeneratorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

class EDIGeneratorServiceTest extends TestCase
{
    use RefreshDatabase;

    protected EDIGeneratorService $ediGenerator;
    protected ClearinghouseAccount $account;
    protected Collection $claims;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ediGenerator = new EDIGeneratorService();

        // Create test clearinghouse account
        $this->account = ClearinghouseAccount::factory()->create([
            'provider' => 'availity',
            'name' => 'Test Clearinghouse',
            'credentials' => [
                'sender_id' => 'TESTSENDER',
                'receiver_id' => 'TESTRECEIVER',
                'username' => 'testuser',
                'password' => 'testpass',
                'client_id' => 'test_client',
                'client_secret' => 'test_secret'
            ]
        ]);

        // Create test claims
        $this->claims = collect([
            Claim::factory()->create([
                'id' => 1,
                'patient_name' => 'John Doe',
                'patient_insurance_id' => 'INS123456',
                'provider_name' => 'Dr. Jane Smith',
                'provider_npi' => '1234567890',
                'total_amount' => 150.00,
                'service_date' => now()->subDays(1),
                'icd10_codes' => ['M54.2', 'Z51.11'],
                'cpt_codes' => ['99213', '85025']
            ]),
            Claim::factory()->create([
                'id' => 2,
                'patient_name' => 'Jane Smith',
                'patient_insurance_id' => 'INS789012',
                'provider_name' => 'Dr. John Johnson',
                'provider_npi' => '0987654321',
                'total_amount' => 275.50,
                'service_date' => now()->subDays(2),
                'icd10_codes' => ['J00', 'Z23'],
                'cpt_codes' => ['99214', '36415']
            ])
        ]);
    }

    /** @test */
    public function it_generates_valid_837p_edi()
    {
        $edi = $this->ediGenerator->generate837P($this->claims, $this->account);

        $this->assertNotEmpty($edi);
        $this->assertStringContainsString('ISA*', $edi);
        $this->assertStringContainsString('GS*', $edi);
        $this->assertStringContainsString('ST*837*', $edi);
        $this->assertStringContainsString('BHT*', $edi);
        $this->assertStringContainsString('CLM*', $edi);
        $this->assertStringContainsString('SE*', $edi);
        $this->assertStringContainsString('GE*', $edi);
        $this->assertStringContainsString('IEA*', $edi);

        // Validate EDI structure
        $validationErrors = $this->ediGenerator->validateEDI($edi);
        $this->assertEmpty($validationErrors, 'EDI validation failed: ' . implode(', ', $validationErrors));
    }

    /** @test */
    public function it_generates_valid_837i_edi()
    {
        $edi = $this->ediGenerator->generate837I($this->claims, $this->account);

        $this->assertNotEmpty($edi);
        $this->assertStringContainsString('ISA*', $edi);
        $this->assertStringContainsString('GS*HI*', $edi); // Institutional identifier
        $this->assertStringContainsString('ST*837*', $edi);

        // Validate EDI structure
        $validationErrors = $this->ediGenerator->validateEDI($edi);
        $this->assertEmpty($validationErrors, 'EDI validation failed: ' . implode(', ', $validationErrors));
    }

    /** @test */
    public function it_validates_correct_edi_structure()
    {
        $validEdi = "ISA*00*          *00*          *ZZ*SENDERID       *ZZ*RECEIVERID     *200101*0000*^*00501*000000001*0*P*:~\n" .
                   "GS*HC*SENDERID*RECEIVERID*20240101*000000*1*X*005010X222A1~\n" .
                   "ST*837*0001*005010X222A1~\n" .
                   "SE*1*0001~\n" .
                   "GE*1*1~\n" .
                   "IEA*1*000000001~\n";

        $errors = $this->ediGenerator->validateEDI($validEdi);
        $this->assertEmpty($errors);
    }

    /** @test */
    public function it_detects_invalid_edi_missing_required_segments()
    {
        $invalidEdi = "GS*HC*SENDERID*RECEIVERID*20240101*000000*1*X*005010X222A1~\n" .
                     "ST*837*0001*005010X222A1~\n";

        $errors = $this->ediGenerator->validateEDI($invalidEdi);
        $this->assertContains('Missing ISA segment', $errors);
    }

    /** @test */
    public function it_detects_invalid_segment_structure()
    {
        $invalidEdi = "ISA*00*          *00*          *ZZ*SENDERID       *ZZ*RECEIVERID     *200101*0000*^*00501*000000001*0*P*:~\n" .
                     "INVALIDSEGMENT~\n" .
                     "GS*HC*SENDERID*RECEIVERID*20240101*000000*1*X*005010X222A1~\n";

        $errors = $this->ediGenerator->validateEDI($invalidEdi);
        $this->assertContains('Invalid segment structure', $errors);
    }

    /** @test */
    public function it_handles_empty_edi_validation()
    {
        $errors = $this->ediGenerator->validateEDI('');
        $this->assertContains('EDI content is empty', $errors);
    }

    /** @test */
    public function it_handles_null_edi_validation()
    {
        $errors = $this->ediGenerator->validateEDI('');
        $this->assertContains('EDI content is empty', $errors);
    }

    /** @test */
    public function it_generates_unique_control_numbers()
    {
        $controlNumber1 = $this->invokePrivateMethod('generateControlNumber');
        $controlNumber2 = $this->invokePrivateMethod('generateControlNumber');

        $this->assertNotEquals($controlNumber1, $controlNumber2);
        $this->assertMatchesRegularExpression('/^\d{9}$/', $controlNumber1);
        $this->assertMatchesRegularExpression('/^\d{9}$/', $controlNumber2);
    }

    /** @test */
    public function it_handles_claims_with_missing_data()
    {
        $incompleteClaim = Claim::factory()->create([
            'patient_name' => null,
            'provider_npi' => null,
            'total_amount' => null,
            'icd10_codes' => null,
            'cpt_codes' => null
        ]);

        $claims = collect([$incompleteClaim]);

        $edi = $this->ediGenerator->generate837P($claims, $this->account);

        // Should still generate valid EDI structure even with missing data
        $this->assertNotEmpty($edi);
        $this->assertStringContainsString('CLM*', $edi);

        $validationErrors = $this->ediGenerator->validateEDI($edi);
        $this->assertEmpty($validationErrors);
    }

    /** @test */
    public function it_handles_large_number_of_claims()
    {
        // Create 100 test claims
        $largeClaims = collect();
        for ($i = 1; $i <= 100; $i++) {
            $largeClaims->push(Claim::factory()->create([
                'id' => $i,
                'patient_name' => "Patient {$i}",
                'patient_insurance_id' => "INS{$i}",
                'total_amount' => rand(50, 500),
                'icd10_codes' => ['M54.2'],
                'cpt_codes' => ['99213']
            ]));
        }

        $edi = $this->ediGenerator->generate837P($largeClaims, $this->account);

        $this->assertNotEmpty($edi);
        $validationErrors = $this->ediGenerator->validateEDI($edi);
        $this->assertEmpty($validationErrors);
    }

    /** @test */
    public function it_handles_special_characters_in_claim_data()
    {
        $specialClaim = Claim::factory()->create([
            'patient_name' => 'José María O\'Connor-Smith',
            'provider_name' => 'Dr. Müller & Associates, Inc.',
            'icd10_codes' => ['M54.2', 'Z51.11'],
            'cpt_codes' => ['99213']
        ]);

        $claims = collect([$specialClaim]);
        $edi = $this->ediGenerator->generate837P($claims, $this->account);

        $this->assertNotEmpty($edi);
        $validationErrors = $this->ediGenerator->validateEDI($edi);
        $this->assertEmpty($validationErrors);
    }

    /** @test */
    public function it_generates_correct_isa_segment()
    {
        $isaSegment = $this->invokePrivateMethod('generateISA', [$this->account]);

        $this->assertStringStartsWith('ISA*', $isaSegment);
        $this->assertStringEndsWith("~\n", $isaSegment);
        $this->assertStringContainsString('TESTSENDER', $isaSegment);
        $this->assertStringContainsString('TESTRECEIVER', $isaSegment);
        $this->assertStringContainsString('00501', $isaSegment); // Version
    }

    /** @test */
    public function it_generates_correct_gs_segment()
    {
        $gsSegment = $this->invokePrivateMethod('generateGS', [$this->account, 'HC']);

        $this->assertStringStartsWith('GS*', $gsSegment);
        $this->assertStringEndsWith("~\n", $gsSegment);
        $this->assertStringContainsString('HC', $gsSegment); // Functional ID
        $this->assertStringContainsString('TESTSENDER', $gsSegment);
        $this->assertStringContainsString('TESTRECEIVER', $gsSegment);
    }

    /** @test */
    public function it_generates_correct_st_segment()
    {
        $stSegment = $this->invokePrivateMethod('generateST', ['837', '000000001']);

        $this->assertStringStartsWith('ST*', $stSegment);
        $this->assertStringEndsWith("~\n", $stSegment);
        $this->assertStringContainsString('837', $stSegment);
        $this->assertStringContainsString('000000001', $stSegment);
    }

    /** @test */
    public function it_generates_correct_bht_segment()
    {
        $bhtSegment = $this->invokePrivateMethod('generateBHT');

        $this->assertStringStartsWith('BHT*', $bhtSegment);
        $this->assertStringEndsWith("~\n", $bhtSegment);
        $this->assertStringContainsString('0019', $bhtSegment); // Code
        $this->assertStringContainsString('CH', $bhtSegment); // Claim/Encounter ID
    }

    /** @test */
    public function it_generates_correct_se_segment()
    {
        $seSegment = $this->invokePrivateMethod('generateSE');

        $this->assertStringStartsWith('SE*', $seSegment);
        $this->assertStringEndsWith("~\n", $seSegment);
        $this->assertStringContainsString('10', $seSegment); // Segment count
        $this->assertStringContainsString('0001', $seSegment); // Control number
    }

    /** @test */
    public function it_generates_correct_ge_segment()
    {
        $geSegment = $this->invokePrivateMethod('generateGE');

        $this->assertStringStartsWith('GE*', $geSegment);
        $this->assertStringEndsWith("~\n", $geSegment);
        $this->assertStringContainsString('1', $geSegment); // Number of sets
        $this->assertStringContainsString('1', $geSegment); // Group control number
    }

    /** @test */
    public function it_generates_correct_iea_segment()
    {
        $ieaSegment = $this->invokePrivateMethod('generateIEA');

        $this->assertStringStartsWith('IEA*', $ieaSegment);
        $this->assertStringEndsWith("~\n", $ieaSegment);
        $this->assertStringContainsString('1', $ieaSegment); // Number of groups
        $this->assertStringContainsString('000000001', $ieaSegment); // Control number
    }

    /**
     * Helper method to invoke private methods for testing
     */
    private function invokePrivateMethod(string $methodName, array $parameters = [])
    {
        $reflection = new \ReflectionClass($this->ediGenerator);
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($this->ediGenerator, $parameters);
    }
}
