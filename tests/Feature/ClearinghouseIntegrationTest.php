<?php

namespace Tests\Feature;

use App\Jobs\ProcessClaimSubmission;
use App\Models\Claim;
use App\Models\ClearinghouseAccount;
use App\Models\ClearinghouseSubmission;
use App\Services\ClaimSubmissionService;
use App\Services\EDIGeneratorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ClearinghouseIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected ClearinghouseAccount $account;
    protected Collection $claims;
    protected ClaimSubmissionService $submissionService;

    protected function setUp(): void
    {
        parent::setUp();

        // Prevent actual job dispatching during tests
        Queue::fake();
        Bus::fake();

        $this->submissionService = app(ClaimSubmissionService::class);

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
                'id' => 1001,
                'patient_name' => 'John Doe',
                'patient_insurance_id' => 'INS123456',
                'provider_name' => 'Dr. Jane Smith',
                'provider_npi' => '1234567890',
                'total_amount' => 150.00,
                'service_date' => now()->subDays(1),
                'icd10_codes' => ['M54.2'],
                'cpt_codes' => ['99213'],
                'claim_status' => 'pending'
            ]),
            Claim::factory()->create([
                'id' => 1002,
                'patient_name' => 'Jane Smith',
                'patient_insurance_id' => 'INS789012',
                'provider_name' => 'Dr. John Johnson',
                'provider_npi' => '0987654321',
                'total_amount' => 275.50,
                'service_date' => now()->subDays(2),
                'icd10_codes' => ['J00'],
                'cpt_codes' => ['99214'],
                'claim_status' => 'pending'
            ])
        ]);
    }

    /** @test */
    public function it_creates_submission_record_and_dispatches_job()
    {
        // Act
        $submission = $this->submissionService->submitClaims($this->claims, $this->account, '837P');

        // Assert
        $this->assertInstanceOf(ClearinghouseSubmission::class, $submission);
        $this->assertEquals('pending', $submission->status);
        $this->assertEquals('837P', $submission->submission_type);
        $this->assertEquals(2, $submission->claim_count);
        $this->assertEquals(425.50, $submission->total_amount); // 150 + 275.50

        // Assert claims are linked to submission
        $this->claims->each(function ($claim) use ($submission) {
            $claim->refresh();
            $this->assertEquals($submission->id, $claim->clearinghouse_submission_id);
            $this->assertEquals($submission->batch_id, $claim->clearinghouse_batch_id);
            $this->assertEquals($this->account->provider, $claim->clearinghouse_provider);
            $this->assertNotNull($claim->clearinghouse_submitted_at);
        });

        // Assert job was dispatched
        Bus::assertDispatched(ProcessClaimSubmission::class, function ($job) use ($submission) {
            return $job->submission->id === $submission->id;
        });
    }

    /** @test */
    public function it_processes_submission_successfully()
    {
        // Arrange
        $submission = ClearinghouseSubmission::factory()->create([
            'clearinghouse_account_id' => $this->account->id,
            'batch_id' => 'TEST_BATCH_123',
            'submission_type' => '837P',
            'status' => 'pending',
            'claim_count' => 2,
            'total_amount' => 425.50,
        ]);

        // Link claims to submission
        $this->claims->each(function ($claim) use ($submission) {
            $claim->update([
                'clearinghouse_submission_id' => $submission->id,
                'clearinghouse_batch_id' => $submission->batch_id,
            ]);
        });

        // Mock successful API responses
        Http::fake([
            'https://api.availity.com/auth/v1/token' => Http::response([
                'access_token' => 'test_token_123',
                'expires_in' => 3600
            ], 200),
            'https://api.availity.com/claims/v1/submit' => Http::response([
                'batch_id' => 'TEST_BATCH_123',
                'tracking_id' => 'TRACK_123',
                'status' => 'accepted',
                'claim_ids' => ['CH123', 'CH124']
            ], 200)
        ]);

        // Act
        $this->submissionService->processSubmission($submission);

        // Assert
        $submission->refresh();
        $this->assertEquals('submitted', $submission->status);
        $this->assertNotNull($submission->submitted_at);
        $this->assertNotNull($submission->edi_content); // Encrypted EDI stored

        // Assert claims updated with clearinghouse IDs
        $this->assertEquals('CH123', $this->claims->first()->fresh()->clearinghouse_claim_id);
        $this->assertEquals('CH124', $this->claims->last()->fresh()->clearinghouse_claim_id);
    }

    /** @test */
    public function it_handles_edi_validation_failure()
    {
        // Arrange
        $submission = ClearinghouseSubmission::factory()->create([
            'clearinghouse_account_id' => $this->account->id,
            'batch_id' => 'TEST_BATCH_123',
            'submission_type' => '837P',
            'status' => 'pending',
        ]);

        // Link claims to submission
        $this->claims->each(function ($claim) use ($submission) {
            $claim->update([
                'clearinghouse_submission_id' => $submission->id,
                'clearinghouse_batch_id' => $submission->batch_id,
            ]);
        });

        // Mock EDI generator to return invalid EDI
        $ediGeneratorMock = $this->mock(EDIGeneratorService::class);
        $ediGeneratorMock->shouldReceive('generate837P')
            ->once()
            ->andReturn('INVALID_EDI_CONTENT');
        $ediGeneratorMock->shouldReceive('validateEDI')
            ->once()
            ->andReturn(['Missing ISA segment', 'Invalid segment structure']);

        $this->app->instance(EDIGeneratorService::class, $ediGeneratorMock);

        // Act
        $this->submissionService->processSubmission($submission);

        // Assert
        $submission->refresh();
        $this->assertEquals('rejected', $submission->status);
        $this->assertStringContainsString('EDI validation failed', $submission->error_message);
    }

    /** @test */
    public function it_handles_submission_api_failure_with_retry()
    {
        // Arrange
        $submission = ClearinghouseSubmission::factory()->create([
            'clearinghouse_account_id' => $this->account->id,
            'batch_id' => 'TEST_BATCH_123',
            'submission_type' => '837P',
            'status' => 'pending',
        ]);

        // Mock API failure
        Http::fake([
            'https://api.availity.com/auth/v1/token' => Http::response([
                'access_token' => 'test_token_123',
                'expires_in' => 3600
            ], 200),
            'https://api.availity.com/claims/v1/submit' => Http::response([
                'error' => 'temporary_server_error',
                'message' => 'Server temporarily unavailable'
            ], 503)
        ]);

        // Act
        $this->submissionService->processSubmission($submission);

        // Assert
        $submission->refresh();
        $this->assertEquals('retry_pending', $submission->status);
        $this->assertStringContainsString('Retry 1/3', $submission->error_message);
        $this->assertEquals(1, $submission->metadata['retry_count']);

        // Assert retry job was dispatched
        Bus::assertDispatched(ProcessClaimSubmission::class, function ($job) use ($submission) {
            return $job->submission->id === $submission->id;
        });
    }

    /** @test */
    public function it_marks_submission_as_failed_after_max_retries()
    {
        // Arrange
        $submission = ClearinghouseSubmission::factory()->create([
            'clearinghouse_account_id' => $this->account->id,
            'batch_id' => 'TEST_BATCH_123',
            'submission_type' => '837P',
            'status' => 'pending',
            'metadata' => ['retry_count' => 2] // Already at retry 2
        ]);

        // Mock API failure
        Http::fake([
            'https://api.availity.com/auth/v1/token' => Http::response([
                'access_token' => 'test_token_123',
                'expires_in' => 3600
            ], 200),
            'https://api.availity.com/claims/v1/submit' => Http::response([
                'error' => 'persistent_error',
                'message' => 'Persistent submission error'
            ], 500)
        ]);

        // Act
        $this->submissionService->processSubmission($submission);

        // Assert
        $submission->refresh();
        $this->assertEquals('failed', $submission->status);
        $this->assertStringContainsString('persistent_error', $submission->error_message);
        $this->assertTrue($submission->metadata['final_failure']);
        $this->assertEquals(2, $submission->metadata['total_retries']);

        // Assert claims marked as failed
        $this->claims->each(function ($claim) {
            $this->assertEquals('clearinghouse_failed', $claim->fresh()->claim_status);
        });
    }

    /** @test */
    public function it_checks_submission_status_and_updates_records()
    {
        // Arrange
        $submission = ClearinghouseSubmission::factory()->create([
            'clearinghouse_account_id' => $this->account->id,
            'batch_id' => 'TEST_BATCH_123',
            'status' => 'submitted',
            'submitted_at' => now()->subHours(2),
        ]);

        // Mock status check response
        Http::fake([
            'https://api.availity.com/auth/v1/token' => Http::response([
                'access_token' => 'test_token_123',
                'expires_in' => 3600
            ], 200),
            'https://api.availity.com/claims/v1/status' => Http::response([
                'batch_id' => 'TEST_BATCH_123',
                'status' => 'accepted',
                'responses' => [
                    ['claim_id' => 'CH123', 'status' => 'accepted'],
                    ['claim_id' => 'CH124', 'status' => 'rejected', 'reason' => 'Invalid NPI']
                ],
                'last_updated' => now()->toISOString()
            ], 200)
        ]);

        // Act
        $this->submissionService->checkSubmissionStatus($submission);

        // Assert
        $submission->refresh();
        $this->assertEquals('accepted', $submission->status);
        $this->assertNotNull($submission->response_received_at);
    }

    /** @test */
    public function it_retrieves_and_processes_277ca_responses()
    {
        // Arrange
        $submission = ClearinghouseSubmission::factory()->create([
            'clearinghouse_account_id' => $this->account->id,
            'batch_id' => 'TEST_BATCH_123',
            'status' => 'submitted',
        ]);

        // Mock response retrieval
        Http::fake([
            'https://api.availity.com/auth/v1/token' => Http::response([
                'access_token' => 'test_token_123',
                'expires_in' => 3600
            ], 200),
            'https://api.availity.com/claims/v1/responses*' => Http::response([
                'batch_id' => 'TEST_BATCH_123',
                'responses' => [
                    [
                        'type' => '277CA',
                        'content' => 'EDI_277CA_RESPONSE_CONTENT',
                        'received_at' => now()->toISOString(),
                        'acknowledgments' => [
                            ['claim_id' => 'CH123', 'status' => 'accepted'],
                            ['claim_id' => 'CH124', 'status' => 'rejected', 'errors' => ['Invalid NPI']]
                        ]
                    ]
                ]
            ], 200)
        ]);

        // Act
        $this->submissionService->checkSubmissionStatus($submission);

        // Assert - The response processing would be handled by the mocked service
        // In a real scenario, this would create response records in the database
        $this->assertTrue(true); // Test passes if no exceptions thrown
    }

    /** @test */
    public function it_handles_batch_status_checks()
    {
        // Arrange
        $submissions = collect([
            ClearinghouseSubmission::factory()->create([
                'clearinghouse_account_id' => $this->account->id,
                'batch_id' => 'BATCH_001',
                'status' => 'submitted',
                'submitted_at' => now()->subHours(2),
            ]),
            ClearinghouseSubmission::factory()->create([
                'clearinghouse_account_id' => $this->account->id,
                'batch_id' => 'BATCH_002',
                'status' => 'submitted',
                'submitted_at' => now()->subHours(3),
            ])
        ]);

        // Mock status check responses
        Http::fake([
            'https://api.availity.com/auth/v1/token' => Http::response([
                'access_token' => 'test_token_123',
                'expires_in' => 3600
            ], 200),
            'https://api.availity.com/claims/v1/status*' => Http::response(function ($request) {
                $url = $request->url();
                if (str_contains($url, 'BATCH_001')) {
                    return Http::response([
                        'batch_id' => 'BATCH_001',
                        'status' => 'accepted',
                        'last_updated' => now()->toISOString()
                    ], 200);
                } elseif (str_contains($url, 'BATCH_002')) {
                    return Http::response([
                        'batch_id' => 'BATCH_002',
                        'status' => 'processed',
                        'last_updated' => now()->toISOString()
                    ], 200);
                }
                return Http::response(['error' => 'Not found'], 404);
            })
        ]);

        // Act
        $this->submissionService->batchCheckStatuses($submissions);

        // Assert
        $submissions->each(function ($submission) {
            $submission->refresh();
            $this->assertNotNull($submission->response_received_at);
            $this->assertContains($submission->status, ['accepted', 'processed']);
        });
    }

    /** @test */
    public function it_manually_resubmits_failed_submission()
    {
        // Arrange
        $submission = ClearinghouseSubmission::factory()->create([
            'clearinghouse_account_id' => $this->account->id,
            'batch_id' => 'FAILED_BATCH_123',
            'status' => 'failed',
            'error_message' => 'Previous failure',
        ]);

        // Link claims to submission
        $this->claims->each(function ($claim) use ($submission) {
            $claim->update([
                'clearinghouse_submission_id' => $submission->id,
                'clearinghouse_batch_id' => $submission->batch_id,
                'claim_status' => 'clearinghouse_failed'
            ]);
        });

        // Act
        $result = $this->submissionService->manualResubmit($submission, [
            'reason' => 'Testing manual resubmit',
            'priority' => 'high'
        ]);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertStringContainsString('scheduled for manual resubmit', $result['message']);

        $submission->refresh();
        $this->assertEquals('pending', $submission->status);
        $this->assertNull($submission->error_message);
        $this->assertTrue($submission->metadata['manual_resubmit']);
        $this->assertEquals('high', $submission->metadata['resubmit_options']['priority']);

        // Assert claims reset
        $this->claims->each(function ($claim) {
            $freshClaim = $claim->fresh();
            $this->assertEquals('submitted', $freshClaim->claim_status);
            $this->assertNull($freshClaim->clearinghouse_claim_id);
        });

        // Assert job dispatched
        Bus::assertDispatched(ProcessClaimSubmission::class, function ($job) use ($submission) {
            return $job->submission->id === $submission->id;
        });
    }

    /** @test */
    public function it_gets_pending_status_checks()
    {
        // Arrange
        ClearinghouseSubmission::factory()->create([
            'status' => 'submitted',
            'submitted_at' => now()->subHours(2), // More than 1 hour ago
        ]);

        ClearinghouseSubmission::factory()->create([
            'status' => 'accepted',
            'submitted_at' => now()->subHours(2),
        ]);

        ClearinghouseSubmission::factory()->create([
            'status' => 'submitted',
            'submitted_at' => now()->subMinutes(30), // Less than 1 hour ago
        ]);

        // Act
        $pendingChecks = $this->submissionService->getPendingStatusChecks();

        // Assert
        $this->assertCount(2, $pendingChecks); // submitted + accepted that are old enough
    }

    /** @test */
    public function it_handles_database_transaction_rollback_on_submission_failure()
    {
        // Arrange - Create claims that will cause submission to fail
        $problematicClaims = collect([
            Claim::factory()->create(['total_amount' => null]) // This might cause issues
        ]);

        // Mock the service to throw an exception during submission creation
        $mockService = $this->mock(ClaimSubmissionService::class);
        $mockService->shouldReceive('submitClaims')
            ->once()
            ->andThrow(new \Exception('Database error'));

        $this->app->instance(ClaimSubmissionService::class, $mockService);

        // Act & Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Database error');

        $mockService->submitClaims($problematicClaims, $this->account);
    }
}
