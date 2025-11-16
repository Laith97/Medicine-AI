<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Services\DataWarehouse\KPICalculationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class AnalyticsLoadTest extends TestCase
{
    use RefreshDatabase;

    protected KPICalculationService $kpiService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->kpiService = app(KPICalculationService::class);
    }

    /** @test */
    public function handles_concurrent_dashboard_access_under_load()
    {
        $users = $this->createTestUsers(50); // Simulate 50 concurrent users
        $this->populateLargeTestDataset();

        $startTime = microtime(true);
        $responses = [];
        $errors = 0;

        // Simulate concurrent access to executive dashboard
        for ($i = 0; $i < 50; $i++) {
            try {
                $response = $this->actingAs($users[$i % count($users)])
                    ->getJson('/api/analytics/executive-dashboard');

                $responses[] = $response;
                if (!$response->isSuccessful()) {
                    $errors++;
                }
            } catch (\Exception $e) {
                $errors++;
            }
        }

        $endTime = microtime(true);
        $totalTime = ($endTime - $startTime) * 1000; // milliseconds
        $averageResponseTime = $totalTime / 50;

        // Performance assertions
        $this->assertLessThan(5, $errors, "Should have less than 5 errors under load");
        $this->assertLessThan(3000, $averageResponseTime,
            "Average response time should be under 3 seconds, got {$averageResponseTime}ms");

        // Verify responses are valid
        foreach ($responses as $response) {
            if ($response->isSuccessful()) {
                $response->assertJsonStructure([
                    'status',
                    'data' => [
                        'summary',
                        'charts',
                        'alerts'
                    ]
                ]);
            }
        }
    }

    /** @test */
    public function handles_large_data_volume_kpi_calculations()
    {
        // Create large dataset (10,000 records)
        $this->populateLargeTestDataset(10000);

        $startTime = microtime(true);

        // Calculate KPIs for a date with large dataset
        $date = Carbon::create(2024, 11, 15);
        $kpiResults = $this->kpiService->calculateDailyKPIs($date, 1);

        $endTime = microtime(true);
        $calculationTime = ($endTime - $startTime) * 1000; // milliseconds

        // Performance assertions
        $this->assertLessThan(5000, $calculationTime,
            "KPI calculation should complete in under 5 seconds, took {$calculationTime}ms");

        // Data integrity assertions
        $this->assertIsArray($kpiResults);
        $this->assertArrayHasKey('total_revenue', $kpiResults);
        $this->assertGreaterThan(0, $kpiResults['total_revenue']);
        $this->assertArrayHasKey('total_appointments', $kpiResults);
        $this->assertGreaterThan(0, $kpiResults['total_appointments']);
    }

    /** @test */
    public function maintains_performance_under_memory_pressure()
    {
        $users = $this->createTestUsers(25);
        $this->populateLargeTestDataset(5000);

        $memoryUsage = [];
        $responseTimes = [];

        for ($i = 0; $i < 25; $i++) {
            $startTime = microtime(true);
            $startMemory = memory_get_usage();

            $response = $this->actingAs($users[$i])
                ->getJson('/api/analytics/executive-dashboard');

            $endTime = microtime(true);
            $endMemory = memory_get_usage();

            $responseTimes[] = ($endTime - $startTime) * 1000;
            $memoryUsage[] = $endMemory - $startMemory;

            $response->assertStatus(200);
        }

        $averageResponseTime = array_sum($responseTimes) / count($responseTimes);
        $averageMemoryUsage = array_sum($memoryUsage) / count($memoryUsage);
        $maxMemoryUsage = max($memoryUsage);

        // Performance assertions
        $this->assertLessThan(2500, $averageResponseTime,
            "Average response time should be under 2.5 seconds, got {$averageResponseTime}ms");

        // Memory assertions (in bytes)
        $this->assertLessThan(50 * 1024 * 1024, $maxMemoryUsage,
            "Maximum memory usage should be under 50MB, used {$maxMemoryUsage} bytes");
    }

    /** @test */
    public function handles_database_connection_pooling_under_load()
    {
        $users = $this->createTestUsers(30);
        $this->populateLargeTestDataset(2000);

        $startTime = microtime(true);
        $successfulRequests = 0;
        $failedRequests = 0;

        // Simulate rapid successive requests
        for ($i = 0; $i < 30; $i++) {
            try {
                $response = $this->actingAs($users[$i % count($users)])
                    ->getJson('/api/analytics/revenue-analytics');

                if ($response->isSuccessful()) {
                    $successfulRequests++;
                } else {
                    $failedRequests++;
                }
            } catch (\Exception $e) {
                $failedRequests++;
            }
        }

        $endTime = microtime(true);
        $totalTime = ($endTime - $startTime) * 1000;

        // Connection pooling assertions
        $this->assertGreaterThan(25, $successfulRequests,
            "Should successfully handle at least 25 concurrent requests");
        $this->assertLessThan(5, $failedRequests,
            "Should have less than 5 failed requests");

        $this->assertLessThan(10000, $totalTime,
            "All requests should complete within 10 seconds");
    }

    /** @test */
    public function caches_effectively_under_load()
    {
        $this->populateLargeTestDataset(1000);
        $user = User::factory()->create();
        $user->assignRole('admin');

        // First request - should calculate and cache
        $startTime1 = microtime(true);
        $response1 = $this->actingAs($user)
            ->getJson('/api/analytics/executive-dashboard');
        $endTime1 = microtime(true);

        $firstRequestTime = ($endTime1 - $startTime1) * 1000;

        // Wait a moment for caching
        usleep(100000); // 0.1 seconds

        // Second request - should use cache
        $startTime2 = microtime(true);
        $response2 = $this->actingAs($user)
            ->getJson('/api/analytics/executive-dashboard');
        $endTime2 = microtime(true);

        $secondRequestTime = ($endTime2 - $startTime2) * 1000;

        // Both responses should be successful
        $response1->assertStatus(200);
        $response2->assertStatus(200);

        // Cached request should be significantly faster
        $this->assertLessThan($firstRequestTime * 0.5, $secondRequestTime,
            "Cached request should be at least 50% faster");

        // Verify cache exists
        $cacheKey = "daily_kpis_1_" . Carbon::yesterday()->format('Ymd');
        $this->assertTrue(Cache::has($cacheKey), 'KPI data should be cached');
    }

    /** @test */
    public function handles_real_time_broadcasting_under_load()
    {
        $users = $this->createTestUsers(20);
        $this->populateLargeTestDataset();

        // Subscribe users to real-time updates
        $streamingService = app(\App\Services\RealtimeStreamingService::class);

        foreach ($users as $user) {
            $streamingService->subscribeToDashboard($user, 'executive', ['revenue']);
        }

        $startTime = microtime(true);

        // Simulate broadcasting KPI updates under load
        for ($i = 0; $i < 20; $i++) {
            $result = $streamingService->broadcastKPIUpdate(
                'revenue',
                ['value' => 100000 + ($i * 1000), 'change' => 10.5],
                1
            );
            $this->assertTrue($result);
        }

        $endTime = microtime(true);
        $broadcastTime = ($endTime - $startTime) * 1000;

        // Broadcasting should be fast
        $this->assertLessThan(2000, $broadcastTime,
            "Broadcasting 20 updates should take less than 2 seconds");

        // Check subscription stats
        $stats = $streamingService->getSubscriptionStats();
        $this->assertGreaterThanOrEqual(20, $stats['total_active_subscriptions']);
    }

    /** @test */
    public function maintains_data_consistency_under_concurrent_writes()
    {
        $this->populateLargeTestDataset(500);

        // Simulate concurrent KPI calculations
        $results = [];
        $startTime = microtime(true);

        for ($i = 0; $i < 5; $i++) {
            $date = Carbon::create(2024, 11, 15);
            $result = $this->kpiService->calculateDailyKPIs($date, 1);
            $results[] = $result;
        }

        $endTime = microtime(true);
        $totalTime = ($endTime - $startTime) * 1000;

        // All results should be consistent
        $firstResult = $results[0];
        foreach ($results as $result) {
            $this->assertEquals($firstResult['total_revenue'], $result['total_revenue']);
            $this->assertEquals($firstResult['total_appointments'], $result['total_appointments']);
        }

        // Performance check
        $this->assertLessThan(10000, $totalTime,
            "Concurrent calculations should complete within 10 seconds");
    }

    /** @test */
    public function handles_export_functionality_under_load()
    {
        $users = $this->createTestUsers(10);
        $this->populateLargeTestDataset();

        $responses = [];
        $startTime = microtime(true);

        // Simulate concurrent export requests
        for ($i = 0; $i < 10; $i++) {
            $response = $this->actingAs($users[$i])
                ->postJson('/api/analytics/export', [
                    'dashboard' => 'executive'
                ]);

            $responses[] = $response;
        }

        $endTime = microtime(true);
        $totalTime = ($endTime - $startTime) * 1000;

        // All exports should be accepted
        foreach ($responses as $response) {
            $response->assertStatus(200)
                ->assertJson([
                    'status' => 'success',
                    'data' => [
                        'status' => 'processing'
                    ]
                ]);
        }

        // Should handle concurrent exports efficiently
        $this->assertLessThan(5000, $totalTime,
            "Concurrent exports should complete within 5 seconds");
    }

    // Helper methods

    private function createTestUsers(int $count): array
    {
        $users = [];
        for ($i = 0; $i < $count; $i++) {
            $user = User::factory()->create();
            $user->assignRole('admin'); // Give admin access for testing
            $users[] = $user;
        }
        return $users;
    }

    private function populateLargeTestDataset(int $recordCount = 1000)
    {
        // Create large dataset for testing
        $financialRecords = [];
        $appointmentRecords = [];

        for ($i = 0; $i < $recordCount; $i++) {
            $financialRecords[] = [
                'date_key' => 20241115,
                'hospital_key' => 1,
                'transaction_type' => 'Payment',
                'amount' => rand(50, 500),
                'payment_method' => rand(0, 1) ? 'Insurance' : 'Credit Card'
            ];

            $appointmentRecords[] = [
                'date_key' => 20241115,
                'hospital_key' => 1,
                'status' => 'Completed',
                'patient_satisfaction_score' => rand(35, 50) / 10, // 3.5 to 5.0
                'wait_time_minutes' => rand(5, 60),
                'consultation_duration_minutes' => rand(15, 90)
            ];
        }

        // Insert in chunks to avoid memory issues
        foreach (array_chunk($financialRecords, 100) as $chunk) {
            DB::table('fact_financial_transactions')->insert($chunk);
        }

        foreach (array_chunk($appointmentRecords, 100) as $chunk) {
            DB::table('fact_appointments')->insert($chunk);
        }
    }
}
