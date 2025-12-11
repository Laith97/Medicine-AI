<?php

namespace Tests\Feature;

use App\Jobs\ProcessClaimSubmission;
use App\Models\Claim;
use App\Models\ClearinghouseAccount;
use App\Models\ClearinghouseSubmission;
use App\Services\ClaimSubmissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ClearinghouseEndToEndTest extends TestCase
{
    use RefreshDatabase;

    protected ClearinghouseAccount $account;
    protected Collection $testClaims;
    protected ClaimSubmissionService $submissionService;

    protected function setUp(): void
    {
        parent::setUp();

        // Don't fake queues for end-to-end testing - we want to test the full flow
        // But we can still fake HTTP calls
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

        // Create comprehensive test claims
        $this->testClaims = collect([
            Claim::factory()->create([
                'patient_name' => 'John Michael Doe',
                'patient_insurance_id' => 'INS123456789',
                'provider_name' => 'Dr. Sarah Johnson MD',
                'provider_npi' => '1234567890',
                'total_amount' => 250.00,
                'service_date' => now()->subDays(5),
                'icd10_codes' => ['M54.2', 'Z51.11'],
                'cpt_codes' => ['99213', '85025', '36415'],
                'claim_status' => 'ready_for_submission'
            ]),
            Claim::factory()->create([
                'patient_name' => 'Maria Gonzalez',
                'patient_insurance_id' => 'INS987654321',
                'provider_name' => 'Dr. Robert Chen',
                'provider_npi' => '0987654321',
                'total_amount' => 450.75,
                'service_date' => now()->subDays(3),
                'icd10_codes' => ['J00', 'Z23'],
                'cpt_codes' => ['99214', '93000'],
                'claim_status' => 'ready_for_submission'
            ]),
            Claim::factory()->create([
                'patient_name' => 'William O\'Connor Jr.',
                'patient_insurance_id' => 'INS555666777',
                'provider_name' => 'Dr. Emily Müller',
                'provider_npi' => '1122334455',
                'total_amount' => 125.50,
                'service_date' => now()->subDays(1),
                'icd10_codes' => ['M79.3'],
                'cpt_codes' => ['99212'],
                'claim_status' => 'ready_for_submission'
            ])
        ]);
    }

    /** @test */
    public function it_processes_complete_clearinghouse_workflow_from_submission_to_response()
    {
        // Mock all external API calls for end-to-end testing
        Http::fake([
            // Authentication
            'https://api.availity.com/auth/v1/token' => Http::response([
                'access_token' => 'e2e_test_token_12345',
                'token_type' => 'Bearer',
                'expires_in' => 3600,
                'session_id' => 'e2e_session_123'
            ], 200),

            // Initial submission
            'https://api.availity.com/claims/v1/submit' => Http::response([
                'batch_id' => 'E2E_BATCH_001',
                'tracking_id' => 'E2E_TRACK_001',
                'status' => 'accepted',
                'message' => 'Batch submitted successfully',
                'claim_ids' => ['E2E_CH001', 'E2E_CH002', 'E2E_CH003'],
                'estimated_processing_time' => '2-4 hours'
            ], 200),

            // Status check - initially processing
            'https://api.availity.com/claims/v1/status*' => Http::response(function ($request) {
                $url = $request->url();
                static $callCount = 0;
                $callCount++;

                if ($callCount === 1) {
                    // First status check - still processing
                    return Http::response([
                        'batch_id' => 'E2E_BATCH_001',
                        'status' => 'processing',
                        'message' => 'Batch is being processed',
                        'last_updated' => now()->subMinutes(30)->toISOString()
                    ], 200);
                } elseif ($callCount === 2) {
                    // Second status check - completed
                    return Http::response([
                        'batch_id' => 'E2E_BATCH_001',
                        'status' => 'completed',
                        'message' => 'Batch processing completed',
                        'responses' => [
                            ['claim_id' => 'E2E_CH001', 'status' => 'accepted', 'amount' => 250.00],
                            ['claim_id' => 'E2E_CH002', 'status' => 'accepted', 'amount' => 450.75],
                            ['claim_id' => 'E2E_CH003', 'status' => 'rejected', 'reason' => 'Invalid NPI', 'amount' => 0.00]
                        ],
                        'last_updated' => now()->toISOString()
                    ], 200);
                }
            }),

            // Response retrieval - 277CA acknowledgments
            'https://api.availity.com/claims/v1/responses*' => Http::response(function ($request) {
                $queryParams = $request->query();
                $type = $queryParams['type'] ?? null;

                if ($type === '277CA') {
                    return Http::response([
                        'batch_id' => 'E2E_BATCH_001',
                        'responses' => [
                            [
                                'type' => '277CA',
                                'content' => 'ISA*00*...*IEA*1*000000001~',
                                'received_at' => now()->toISOString(),
                                'acknowledgments' => [
                                    ['claim_id' => 'E2E_CH001', 'status' => 'accepted', 'reference' => 'REF001'],
                                    ['claim_id' => 'E2E_CH002', 'status' => 'accepted', 'reference' => 'REF002'],
                                    ['claim_id' => 'E2E_CH003', 'status' => 'rejected', 'reference' => 'REF003', 'errors' => ['Invalid NPI']]
                                ]
                            ]
                        ]
                    ], 200);
                } elseif ($type === '835') {
                    return Http::response([
                        'batch_id' => 'E2E_BATCH_001',
                        'responses' => [
                            [
                                'type' => '835',
                                'content' => 'ISA*00*...*IEA*1*000000001~',
                                'received_at' => now()->toISOString(),
                                'payments' => [
                                    ['claim_id' => 'E2E_CH001', 'paid_amount' => 200.00, 'reference' => 'PAY001'],
                                    ['claim_id' => 'E2E_CH002', 'paid_amount' => 360.60, 'reference' => 'PAY002']
                                ]
                            ]
                        ]
                    ], 200);
                }

                return Http::response(['error' => 'No responses found'], 404);
            })
        ]);

        // Step 1: Submit claims
        $submission = $this->submissionService->submitClaims($this->testClaims, $this->account, '837P');

        // Assert submission was created
        $this->assertInstanceOf(ClearinghouseSubmission::class, $submission);
        $this->assertEquals('pending', $submission->status);
        $this->assertEquals(3, $submission->claim_count);
        $this->assertEquals(826.25, $submission->total_amount); // 250 + 450.75 + 125.50

        // Step 2: Process the submission (normally done by job queue)
        $this->submissionService->processSubmission($submission);

        // Assert submission was processed successfully
        $submission->refresh();
        $this->assertEquals('submitted', $submission->status);
        $this->assertNotNull($submission->submitted_at);
        $this->assertNotNull($submission->edi_content);

        // Assert claims were updated with clearinghouse IDs
        $this->testClaims->each(function ($claim, $index) {
            $claim->refresh();
            $expectedId = ['E2E_CH001', 'E2E_CH002', 'E2E_CH003'][$index];
            $this->assertEquals($expectedId, $claim->clearinghouse_claim_id);
            $this->assertEquals('availity', $claim->clearinghouse_provider);
        });

        // Step 3: Check submission status (first check - still processing)
        $this->submissionService->checkSubmissionStatus($submission);
        $submission->refresh();
        $this->assertEquals('submitted', $submission->status); // Still submitted, not yet completed

        // Step 4: Check submission status again (second check - completed)
        $this->submissionService->checkSubmissionStatus($submission);
        $submission->refresh();
        $this->assertEquals('completed', $submission->status);
        $this->assertNotNull($submission->response_received_at);

        // Step 5: Verify final claim statuses
        $this->testClaims->get(0)->refresh();
        $this->assertEquals('paid', $this->testClaims->get(0)->claim_status); // Accepted and paid

        $this->testClaims->get(1)->refresh();
        $this->assertEquals('paid', $this->testClaims->get(1)->claim_status); // Accepted and paid

        $this->testClaims->get(2)->refresh();
        $this->assertEquals('denied', $this->testClaims->get(2)->claim_status); // Rejected
    }

    /** @test */
    public function it_handles_end_to_end_workflow_with_retry_scenarios()
    {
        Http::fake([
            // Authentication succeeds
            'https://api.availity.com/auth/v1/token' => Http::response([
                'access_token' => 'retry_test_token',
                'expires_in' => 3600
            ], 200),

            // First submission attempt fails with 503
            'https://api.availity.com/claims/v1/submit' => Http::response(function () {
                static $attemptCount = 0;
                $attemptCount++;

                if ($attemptCount === 1) {
                    return Http::response([
                        'error' => 'service_temporarily_unavailable',
                        'message' => 'Clearinghouse temporarily unavailable'
                    ], 503);
                } elseif ($attemptCount === 2) {
                    return Http::response([
                        'error' => 'rate_limit_exceeded',
                        'message' => 'Too many requests'
                    ], 429);
                } else {
                    // Third attempt succeeds
                    return Http::response([
                        'batch_id' => 'RETRY_BATCH_001',
                        'tracking_id' => 'RETRY_TRACK_001',
                        'status' => 'accepted',
                        'claim_ids' => ['RETRY_CH001', 'RETRY_CH002']
                    ], 200);
                }
            })
        ]);

        // Submit claims
        $submission = $this->submissionService->submitClaims($this->testClaims->take(2), $this->account);

        // First processing attempt (should fail and schedule retry)
        $this->submissionService->processSubmission($submission);
        $submission->refresh();
        $this->assertEquals('retry_pending', $submission->status);
        $this->assertEquals(1, $submission->metadata['retry_count']);

        // Second processing attempt (should also fail and schedule another retry)
        $this->submissionService->processSubmission($submission);
        $submission->refresh();
        $this->assertEquals('retry_pending', $submission->status);
        $this->assertEquals(2, $submission->metadata['retry_count']);

        // Third processing attempt (should succeed)
        $this->submissionService->processSubmission($submission);
        $submission->refresh();
        $this->assertEquals('submitted', $submission->status);
        $this->assertEquals(2, $submission->metadata['total_retries']); // Final retry count
    }

    /** @test */
    public function it_handles_end_to_end_workflow_with_permanent_failure()
    {
        Http::fake([
            'https://api.availity.com/auth/v1/token' => Http::response([
                'access_token' => 'failure_test_token',
                'expires_in' => 3600
            ], 200),

            // All submission attempts fail with permanent errors
            'https://api.availity.com/claims/v1/submit' => Http::response([
                'error' => 'invalid_credentials',
                'message' => 'Authentication failed - invalid API credentials'
            ], 401)
        ]);

        // Submit claims
        $submission = $this->submissionService->submitClaims($this->testClaims->take(2), $this->account);

        // First attempt fails
        $this->submissionService->processSubmission($submission);
        $submission->refresh();
        $this->assertEquals('retry_pending', $submission->status);

        // Second attempt fails
        $this->submissionService->processSubmission($submission);
        $submission->refresh();
        $this->assertEquals('retry_pending', $submission->status);

        // Third attempt fails permanently
        $this->submissionService->processSubmission($submission);
        $submission->refresh();
        $this->assertEquals('failed', $submission->status);
        $this->assertTrue($submission->metadata['final_failure']);
        $this->assertStringContainsString('invalid_credentials', $submission->error_message);

        // Assert claims marked as failed
        $this->testClaims->take(2)->each(function ($claim) {
            $this->assertEquals('clearinghouse_failed', $claim->fresh()->claim_status);
        });
    }

    /** @test */
    public function it_processes_multiple_batches_concurrently()
    {
        // Create multiple accounts and submissions to test concurrent processing
        $account2 = ClearinghouseAccount::factory()->create([
            'provider' => 'change_healthcare',
            'credentials' => [
                'username' => 'user2',
                'password' => 'pass2',
                'client_id' => 'client2',
                'client_secret' => 'secret2'
            ]
        ]);

        $claimsBatch1 = $this->testClaims->take(2);
        $claimsBatch2 = $this->testClaims->skip(2)->take(1);

        Http::fake([
            'https://api.availity.com/auth/v1/token' => Http::response([
                'access_token' => 'concurrent_token_1',
                'expires_in' => 3600
            ], 200),
            'https://api.availity.com/claims/v1/submit' => Http::response([
                'batch_id' => 'CONCURRENT_BATCH_1',
                'status' => 'accepted',
                'claim_ids' => ['CONC_CH001', 'CONC_CH002']
            ], 200),

            'https://api.changehealthcare.com/auth/token' => Http::response([
                'access_token' => 'concurrent_token_2',
                'expires_in' => 3600
            ], 200),
            'https://api.changehealthcare.com/claims/submit' => Http::response([
                'batch_id' => 'CONCURRENT_BATCH_2',
                'status' => 'accepted',
                'claim_ids' => ['CONC_CH003']
            ], 200)
        ]);

        // Submit both batches
        $submission1 = $this->submissionService->submitClaims($claimsBatch1, $this->account);
        $submission2 = $this->submissionService->submitClaims($claimsBatch2, $account2);

        // Process both submissions
        $this->submissionService->processSubmission($submission1);
        $this->submissionService->processSubmission($submission2);

        // Assert both were processed successfully
        $submission1->refresh();
        $submission2->refresh();

        $this->assertEquals('submitted', $submission1->status);
        $this->assertEquals('submitted', $submission2->status);

        $this->assertEquals('CONCURRENT_BATCH_1', $submission1->batch_id);
        $this->assertEquals('CONCURRENT_BATCH_2', $submission2->batch_id);
    }

    /** @test */
    public function it_handles_end_to_end_workflow_with_edi_validation_failures()
    {
        // Create claims that will produce invalid EDI
        $invalidClaims = collect([
            Claim::factory()->create([
                'patient_name' => '', // Empty patient name
                'provider_npi' => '', // Empty NPI
                'icd10_codes' => [], // No diagnosis codes
                'cpt_codes' => [] // No procedure codes
            ])
        ]);

        // Submit claims
        $submission = $this->submissionService->submitClaims($invalidClaims, $this->account);

        // Process submission (EDI validation should fail)
        $this->submissionService->processSubmission($submission);

        // Assert submission was rejected due to EDI validation
        $submission->refresh();
        $this->assertEquals('rejected', $submission->status);
        $this->assertStringContainsString('EDI validation failed', $submission->error_message);
    }

    /** @test */
    public function it_maintains_data_integrity_throughout_end_to_end_workflow()
    {
        Http::fake([
            'https://api.availity.com/auth/v1/token' => Http::response([
                'access_token' => 'integrity_test_token',
                'expires_in' => 3600
            ], 200),
            'https://api.availity.com/claims/v1/submit' => Http::response([
                'batch_id' => 'INTEGRITY_BATCH_001',
                'status' => 'accepted',
                'claim_ids' => ['INT_CH001', 'INT_CH002', 'INT_CH003']
            ], 200)
        ]);

        // Capture original claim data
        $originalData = $this->testClaims->map(function ($claim) {
            return [
                'id' => $claim->id,
                'patient_name' => $claim->patient_name,
                'total_amount' => $claim->total_amount,
                'status' => $claim->claim_status
            ];
        });

        // Process through complete workflow
        $submission = $this->submissionService->submitClaims($this->testClaims, $this->account);
        $this->submissionService->processSubmission($submission);

        // Verify data integrity
        $this->testClaims->each(function ($claim, $index) use ($originalData) {
            $claim->refresh();
            $original = $originalData[$index];

            // Core claim data should remain unchanged
            $this->assertEquals($original['patient_name'], $claim->patient_name);
            $this->assertEquals($original['total_amount'], $claim->total_amount);

            // Status should be updated appropriately
            $this->assertNotEquals($original['status'], $claim->claim_status);

            // Clearinghouse fields should be populated
            $this->assertNotNull($claim->clearinghouse_submission_id);
            $this->assertNotNull($claim->clearinghouse_batch_id);
            $this->assertNotNull($claim->clearinghouse_provider);
            $this->assertNotNull($claim->clearinghouse_submitted_at);
            $this->assertNotNull($claim->clearinghouse_claim_id);
        });

        // Verify submission data integrity
        $submission->refresh();
        $this->assertEquals(3, $submission->claim_count);
        $this->assertEquals($this->testClaims->sum('total_amount'), $submission->total_amount);
        $this->assertEquals('submitted', $submission->status);
    }
}
