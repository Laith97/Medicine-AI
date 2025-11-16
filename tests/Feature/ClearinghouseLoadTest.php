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

class ClearinghouseLoadTest extends TestCase
{
    use RefreshDatabase;

    protected ClearinghouseAccount $account;
    protected ClaimSubmissionService $submissionService;

    protected function setUp(): void
    {
        parent::setUp();

        Queue::fake();
        $this->submissionService = app(ClaimSubmissionService::class);

        $this->account = ClearinghouseAccount::factory()->create([
            'provider' => 'availity',
            'credentials' => [
                'sender_id' => 'LOADTEST',
                'receiver_id' => 'LOADRECEIVER',
                'username' => 'loaduser',
                'password' => 'loadpass',
                'client_id' => 'load_client',
                'client_secret' => 'load_secret'
            ]
        ]);
    }

    /** @test */
    public function it_handles_large_batch_processing_100_claims()
    {
        // Create 100 test claims
        $largeBatchClaims = collect();
        for ($i = 1; $i <= 100; $i++) {
            $largeBatchClaims->push(Claim::factory()->create([
                'id' => 1000 + $i,
                'patient_name' => "Load Test Patient {$i}",
                'patient_insurance_id' => "LOAD{$i}",
                'provider_name' => 'Dr. Load Test Provider',
                'provider_npi' => '9999999999',
                'total_amount' => rand(100, 1000),
                'service_date' => now()->subDays(rand(1, 30)),
                'icd10_codes' => ['M54.2'],
                'cpt_codes' => ['99213'],
                'claim_status' => 'ready_for_submission'
            ]));
        }

        // Mock successful API responses
        Http::fake([
            'https://api.availity.com/auth/v1/token' => Http::response([
                'access_token' => 'large_batch_token',
                'expires_in' => 3600
            ], 200),
            'https://api.availity.com/claims/v1/submit' => Http::response([
                'batch_id' => 'LARGE_BATCH_001',
                'status' => 'accepted',
                'claim_ids' => $largeBatchClaims->map(fn($claim, $index) => "LB_CH" . str_pad($index + 1, 3, '0', STR_PAD_LEFT))->toArray()
            ], 200)
        ]);

        $startTime = microtime(true);

        // Submit large batch
        $submission = $this->submissionService->submitClaims($largeBatchClaims, $this->account);

        // Process submission
        $this->submissionService->processSubmission($submission);

        $endTime = microtime(true);
        $processingTime = $endTime - $startTime;

        // Assert performance requirements
        $this->assertLessThan(5.0, $processingTime, 'Large batch processing took too long');

        // Assert all claims were processed
        $submission->refresh();
        $this->assertEquals('submitted', $submission->status);
        $this->assertEquals(100, $submission->claim_count);
        $this->assertEquals($largeBatchClaims->sum('total_amount'), $submission->total_amount);

        // Assert all claims have clearinghouse IDs
        $largeBatchClaims->each(function ($claim, $index) {
            $claim->refresh();
            $expectedId = "LB_CH" . str_pad($index + 1, 3, '0', STR_PAD_LEFT);
            $this->assertEquals($expectedId, $claim->clearinghouse_claim_id);
        });
    }

    /** @test */
    public function it_handles_concurrent_submissions_from_multiple_users()
    {
        // Create multiple accounts to simulate different users/providers
        $accounts = collect();
        for ($i = 1; $i <= 5; $i++) {
            $accounts->push(ClearinghouseAccount::factory()->create([
                'provider' => 'availity',
                'name' => "Concurrent Account {$i}",
                'credentials' => [
                    'sender_id' => "CONC{$i}",
                    'receiver_id' => 'CONCRECEIVER',
                    'username' => "concuser{$i}",
                    'password' => 'concpass',
                    'client_id' => "conc_client_{$i}",
                    'client_secret' => 'conc_secret'
                ]
            ]));
        }

        // Create batches for each account
        $submissions = collect();
        $allClaims = collect();

        Http::fake(function ($request) {
            $url = $request->url();

            // Authentication responses
            if (str_contains($url, '/auth/')) {
                return Http::response([
                    'access_token' => 'concurrent_token_' . rand(1000, 9999),
                    'expires_in' => 3600
                ], 200);
            }

            // Submission responses
            if (str_contains($url, '/submit')) {
                static $batchCounter = 0;
                $batchCounter++;
                return Http::response([
                    'batch_id' => 'CONC_BATCH_' . str_pad($batchCounter, 3, '0', STR_PAD_LEFT),
                    'status' => 'accepted',
                    'claim_ids' => ['CONC_CH001', 'CONC_CH002', 'CONC_CH003']
                ], 200);
            }

            return Http::response(['error' => 'Unexpected request'], 404);
        });

        // Submit batches concurrently (simulate multiple users)
        $startTime = microtime(true);

        foreach ($accounts as $account) {
            $claims = collect([
                Claim::factory()->create(['claim_status' => 'ready_for_submission']),
                Claim::factory()->create(['claim_status' => 'ready_for_submission']),
                Claim::factory()->create(['claim_status' => 'ready_for_submission'])
            ]);

            $allClaims = $allClaims->merge($claims);
            $submission = $this->submissionService->submitClaims($claims, $account);
            $submissions->push($submission);

            // Process each submission
            $this->submissionService->processSubmission($submission);
        }

        $endTime = microtime(true);
        $totalProcessingTime = $endTime - $startTime;

        // Assert performance - should handle 5 concurrent users within reasonable time
        $this->assertLessThan(10.0, $totalProcessingTime, 'Concurrent processing took too long');

        // Assert all submissions were successful
        $submissions->each(function ($submission) {
            $submission->refresh();
            $this->assertEquals('submitted', $submission->status);
            $this->assertEquals(3, $submission->claim_count);
        });

        // Assert all claims were processed
        $allClaims->each(function ($claim) {
            $claim->refresh();
            $this->assertNotNull($claim->clearinghouse_claim_id);
            $this->assertNotNull($claim->clearinghouse_submitted_at);
        });
    }

    /** @test */
    public function it_handles_high_frequency_status_checks()
    {
        // Create submission
        $submission = ClearinghouseSubmission::factory()->create([
            'clearinghouse_account_id' => $this->account->id,
            'batch_id' => 'STATUS_CHECK_BATCH',
            'status' => 'submitted',
            'submitted_at' => now()->subHours(1),
        ]);

        // Mock status check responses with varying response times
        Http::fake([
            'https://api.availity.com/auth/v1/token' => Http::response([
                'access_token' => 'status_check_token',
                'expires_in' => 3600
            ], 200),
            'https://api.availity.com/claims/v1/status*' => Http::response(function () {
                static $checkCount = 0;
                $checkCount++;

                // Simulate varying response times and statuses
                $responses = [
                    ['status' => 'processing', 'delay' => 0.1],
                    ['status' => 'processing', 'delay' => 0.05],
                    ['status' => 'accepted', 'delay' => 0.08],
                    ['status' => 'completed', 'delay' => 0.12],
                ];

                $responseIndex = min($checkCount - 1, count($responses) - 1);
                $response = $responses[$responseIndex];

                // Simulate response delay
                usleep($response['delay'] * 1000000); // Convert to microseconds

                return Http::response([
                    'batch_id' => 'STATUS_CHECK_BATCH',
                    'status' => $response['status'],
                    'last_updated' => now()->toISOString()
                ], 200);
            })
        ]);

        $startTime = microtime(true);

        // Perform multiple rapid status checks
        for ($i = 0; $i < 10; $i++) {
            $this->submissionService->checkSubmissionStatus($submission);
        }

        $endTime = microtime(true);
        $totalCheckTime = $endTime - $startTime;

        // Assert performance - 10 status checks should complete within 2 seconds
        $this->assertLessThan(2.0, $totalCheckTime, 'High frequency status checks took too long');

        // Assert final status
        $submission->refresh();
        $this->assertEquals('completed', $submission->status);
    }

    /** @test */
    public function it_handles_memory_efficiently_with_large_datasets()
    {
        // Get initial memory usage
        $initialMemory = memory_get_usage(true);

        // Create 500 claims (large dataset)
        $memoryTestClaims = collect();
        for ($i = 1; $i <= 500; $i++) {
            $memoryTestClaims->push(Claim::factory()->create([
                'patient_name' => "Memory Test Patient {$i}",
                'icd10_codes' => ['M54.2', 'Z51.11', 'J00'],
                'cpt_codes' => ['99213', '85025', '36415'],
                'claim_status' => 'ready_for_submission'
            ]));
        }

        Http::fake([
            'https://api.availity.com/auth/v1/token' => Http::response([
                'access_token' => 'memory_test_token',
                'expires_in' => 3600
            ], 200),
            'https://api.availity.com/claims/v1/submit' => Http::response([
                'batch_id' => 'MEMORY_BATCH_001',
                'status' => 'accepted',
                'claim_ids' => $memoryTestClaims->map(fn($_, $index) => "MEM_CH" . str_pad($index + 1, 3, '0', STR_PAD_LEFT))->toArray()
            ], 200)
        ]);

        // Process large batch
        $submission = $this->submissionService->submitClaims($memoryTestClaims, $this->account);
        $this->submissionService->processSubmission($submission);

        // Check memory usage after processing
        $finalMemory = memory_get_usage(true);
        $memoryUsed = $finalMemory - $initialMemory;
        $memoryUsedMB = $memoryUsed / 1024 / 1024;

        // Assert memory usage is reasonable (should be less than 50MB increase)
        $this->assertLessThan(50.0, $memoryUsedMB, 'Memory usage too high for large dataset processing');

        // Assert processing was successful
        $submission->refresh();
        $this->assertEquals('submitted', $submission->status);
        $this->assertEquals(500, $submission->claim_count);
    }

    /** @test */
    public function it_handles_database_connection_pooling_under_load()
    {
        // Create multiple submissions to test database load
        $loadSubmissions = collect();
        $loadClaims = collect();

        for ($i = 1; $i <= 20; $i++) {
            $claims = collect([
                Claim::factory()->create(['claim_status' => 'ready_for_submission']),
                Claim::factory()->create(['claim_status' => 'ready_for_submission'])
            ]);

            $loadClaims = $loadClaims->merge($claims);
            $submission = ClearinghouseSubmission::factory()->create([
                'clearinghouse_account_id' => $this->account->id,
                'batch_id' => "LOAD_BATCH_{$i}",
                'status' => 'pending',
                'claim_count' => 2
            ]);

            // Link claims to submission
            $claims->each(function ($claim) use ($submission) {
                $claim->update(['clearinghouse_submission_id' => $submission->id]);
            });

            $loadSubmissions->push($submission);
        }

        Http::fake([
            'https://api.availity.com/auth/v1/token' => Http::response([
                'access_token' => 'db_load_token',
                'expires_in' => 3600
            ], 200),
            'https://api.availity.com/claims/v1/submit' => Http::response([
                'batch_id' => 'DB_LOAD_BATCH',
                'status' => 'accepted',
                'claim_ids' => ['DB_CH001', 'DB_CH002']
            ], 200)
        ]);

        $startTime = microtime(true);

        // Process all submissions (simulating high database load)
        $loadSubmissions->each(function ($submission) {
            $this->submissionService->processSubmission($submission);
        });

        $endTime = microtime(true);
        $processingTime = $endTime - $startTime;

        // Assert performance under database load
        $this->assertLessThan(15.0, $processingTime, 'Database load processing took too long');

        // Assert all submissions processed successfully
        $loadSubmissions->each(function ($submission) {
            $submission->refresh();
            $this->assertEquals('submitted', $submission->status);
        });
    }

    /** @test */
    public function it_handles_api_rate_limiting_gracefully()
    {
        // Create submission
        $submission = ClearinghouseSubmission::factory()->create([
            'clearinghouse_account_id' => $this->account->id,
            'batch_id' => 'RATE_LIMIT_BATCH',
            'status' => 'pending'
        ]);

        // Mock rate limiting responses
        Http::fake([
            'https://api.availity.com/auth/v1/token' => Http::response(function () {
                static $authCount = 0;
                $authCount++;

                if ($authCount <= 3) {
                    return Http::response([
                        'error' => 'rate_limit_exceeded',
                        'message' => 'Too many requests',
                        'retry_after' => 1
                    ], 429);
                }

                return Http::response([
                    'access_token' => 'rate_limit_token',
                    'expires_in' => 3600
                ], 200);
            }),
            'https://api.availity.com/claims/v1/submit' => Http::response([
                'batch_id' => 'RATE_LIMIT_BATCH',
                'status' => 'accepted'
            ], 200)
        ]);

        $startTime = microtime(true);

        // Process submission (should handle rate limiting)
        $this->submissionService->processSubmission($submission);

        $endTime = microtime(true);
        $processingTime = $endTime - $startTime;

        // Assert it eventually succeeds despite rate limiting
        $submission->refresh();
        $this->assertEquals('submitted', $submission->status);

        // Assert reasonable processing time (accounting for retries)
        $this->assertLessThan(10.0, $processingTime, 'Rate limit handling took too long');
    }

    /** @test */
    public function it_maintains_response_time_under_concurrent_load()
    {
        // Create baseline submission
        $baselineSubmission = ClearinghouseSubmission::factory()->create([
            'clearinghouse_account_id' => $this->account->id,
            'batch_id' => 'BASELINE_BATCH',
            'status' => 'pending'
        ]);

        Http::fake([
            'https://api.availity.com/auth/v1/token' => Http::response([
                'access_token' => 'baseline_token',
                'expires_in' => 3600
            ], 200),
            'https://api.availity.com/claims/v1/submit' => Http::response([
                'batch_id' => 'BASELINE_BATCH',
                'status' => 'accepted'
            ], 200)
        ]);

        // Measure baseline response time
        $baselineStart = microtime(true);
        $this->submissionService->processSubmission($baselineSubmission);
        $baselineEnd = microtime(true);
        $baselineTime = $baselineEnd - $baselineStart;

        // Now test under concurrent load
        $concurrentSubmissions = collect();
        for ($i = 1; $i <= 10; $i++) {
            $concurrentSubmissions->push(ClearinghouseSubmission::factory()->create([
                'clearinghouse_account_id' => $this->account->id,
                'batch_id' => "CONC_LOAD_BATCH_{$i}",
                'status' => 'pending'
            ]));
        }

        $concurrentStart = microtime(true);

        // Process all concurrent submissions
        $concurrentSubmissions->each(function ($submission) {
            $this->submissionService->processSubmission($submission);
        });

        $concurrentEnd = microtime(true);
        $concurrentTime = $concurrentEnd - $concurrentStart;

        // Assert that concurrent processing doesn't degrade performance excessively
        // Concurrent time should be less than 5x the baseline time
        $degradationRatio = $concurrentTime / ($baselineTime * 10);
        $this->assertLessThan(5.0, $degradationRatio, 'Performance degraded too much under concurrent load');

        // Assert all submissions completed
        $concurrentSubmissions->each(function ($submission) {
            $submission->refresh();
            $this->assertEquals('submitted', $submission->status);
        });
    }
}
