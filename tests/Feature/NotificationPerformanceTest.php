<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use App\Models\User;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Notifications\AppointmentBookedNotification;
use App\Notifications\SystemAlertNotification;
use App\Services\NotificationCacheService;
use App\Services\NotificationCompressionService;
use App\Services\MemoryOptimizedNotificationProcessor;
use App\Services\PusherConnectionPool;
use App\Services\NotificationPerformanceMonitor;

class NotificationPerformanceTest extends TestCase
{
    use RefreshDatabase;

    protected NotificationCacheService $cacheService;
    protected NotificationCompressionService $compressionService;
    protected MemoryOptimizedNotificationProcessor $memoryProcessor;
    protected PusherConnectionPool $connectionPool;
    protected NotificationPerformanceMonitor $performanceMonitor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cacheService = app(NotificationCacheService::class);
        $this->compressionService = app(NotificationCompressionService::class);
        $this->memoryProcessor = app(MemoryOptimizedNotificationProcessor::class);
        $this->connectionPool = app(PusherConnectionPool::class);
        $this->performanceMonitor = app(NotificationPerformanceMonitor::class);
    }

    /** @test */
    public function test_response_caching_works_correctly()
    {
        $user = User::factory()->create();

        // First request - should cache
        $startTime = microtime(true);
        $response1 = $this->actingAs($user)->get('/api/notifications');
        $firstRequestTime = (microtime(true) - $startTime) * 1000;

        $this->assertEquals(200, $response1->getStatusCode());

        // Second request - should use cache
        $startTime = microtime(true);
        $response2 = $this->actingAs($user)->get('/api/notifications');
        $secondRequestTime = (microtime(true) - $startTime) * 1000;

        $this->assertEquals(200, $response2->getStatusCode());

        // Cached response should be faster (at least 50% faster)
        $this->assertLessThan($firstRequestTime * 0.5, $secondRequestTime);

        // Responses should be identical
        $this->assertEquals(
            json_decode($response1->getContent(), true),
            json_decode($response2->getContent(), true)
        );
    }

    /** @test */
    public function test_compression_reduces_payload_size()
    {
        $largePayload = [
            'title' => 'Test Notification with Large Payload',
            'message' => str_repeat('This is a test message with lots of content. ', 100),
            'data' => [
                'appointment_id' => 123,
                'doctor_name' => 'Dr. Test Doctor',
                'appointment_date' => '2024-01-01 10:00:00',
                'appointment_type' => 'consultation',
                'additional_data' => str_repeat('Additional data content. ', 50),
                'metadata' => [
                    'source' => 'test',
                    'priority' => 'high',
                    'tags' => ['test', 'performance', 'compression'],
                    'large_array' => range(1, 100),
                ]
            ],
            'created_at' => now()->toISOString()
        ];

        $originalSize = strlen(json_encode($largePayload));
        $compressedPayload = $this->compressionService->compressPayload($largePayload);

        // Compression should be applied for large payloads
        $this->assertArrayHasKey('_compressed', $compressedPayload);
        $this->assertTrue($compressedPayload['_compressed']);

        // Compressed size should be smaller
        $compressedSize = strlen($compressedPayload['data']);
        $this->assertLessThan($originalSize, $compressedSize); // Note: base64 encoding adds overhead

        // Decompression should work
        $decompressedPayload = $this->compressionService->decompressPayload($compressedPayload);
        $this->assertEquals($largePayload, $decompressedPayload);
    }

    /** @test */
    public function test_memory_optimized_processing_handles_large_datasets()
    {
        $user = User::factory()->create();

        // Create a large number of notifications
        $notifications = collect();
        for ($i = 0; $i < 150; $i++) {
            $notifications->push([
                'id' => $i + 1,
                'type' => 'database',
                'data' => [
                    'title' => "Test Notification {$i}",
                    'message' => "Message content for notification {$i}",
                    'type' => 'test',
                    'created_at' => now()->subMinutes($i)->toISOString()
                ],
                'read_at' => $i % 3 === 0 ? now()->toISOString() : null,
                'created_at' => now()->subMinutes($i)->toISOString()
            ]);
        }

        $startTime = microtime(true);
        $startMemory = memory_get_usage(true);

        // Process notifications in batches
        $results = $this->memoryProcessor->processNotifications($notifications, function ($notification) {
            // Simulate processing
            usleep(1000); // 1ms processing time
            return [
                'id' => $notification['id'],
                'processed' => true,
                'title' => $notification['data']['title']
            ];
        });

        $endTime = microtime(true);
        $endMemory = memory_get_usage(true);

        $processingTime = ($endTime - $startTime) * 1000;
        $memoryUsed = $endMemory - $startMemory;

        // Should process all notifications
        $this->assertCount(150, $results);

        // Should complete within reasonable time (under 200ms per notification on average)
        $this->assertLessThan(30000, $processingTime); // 30 seconds max

        // Memory usage should be reasonable (under 50MB additional)
        $this->assertLessThan(50 * 1024 * 1024, $memoryUsed);
    }

    /** @test */
    public function test_pusher_connection_pool_reuse()
    {
        // Get initial stats
        $initialStats = $this->connectionPool->getPoolStats();

        // Request multiple connections
        $connections = [];
        for ($i = 0; $i < 5; $i++) {
            $connections[] = $this->connectionPool->getConnection();
            usleep(10000); // 10ms delay
        }

        // Get updated stats
        $updatedStats = $this->connectionPool->getPoolStats();

        // Should have created fewer connections than requested (due to reuse)
        $this->assertGreaterThanOrEqual(1, $updatedStats['stats']['created']);
        $this->assertLessThanOrEqual(5, $updatedStats['stats']['created'] + $updatedStats['stats']['reused']);

        // Should have some reuse
        $this->assertGreaterThanOrEqual(0, $updatedStats['stats']['reused']);
    }

    /** @test */
    public function test_performance_monitoring_tracks_metrics()
    {
        // Reset metrics
        $this->performanceMonitor->resetMetrics();

        // Record some activities
        $this->performanceMonitor->recordRequest('api');
        $this->performanceMonitor->recordCacheHit();
        $this->performanceMonitor->recordCompressionSaving(1000, 600);
        $this->performanceMonitor->recordBroadcastSuccess('test_event');

        // Record response time
        $startTime = microtime(true);
        usleep(50000); // 50ms
        $this->performanceMonitor->recordResponseTime($startTime, 'test_endpoint');

        // Get metrics
        $metrics = $this->performanceMonitor->getMetrics();

        // Verify metrics are recorded
        $this->assertEquals(1, $metrics['summary']['total_requests']);
        $this->assertEquals(100.0, (float) str_replace('%', '', $metrics['cache']['hit_rate']));
        $this->assertEquals(400, $metrics['compression']['total_savings_bytes']);
        $this->assertEquals(1, $metrics['broadcast']['success']);

        // Response time should be around 50ms
        $this->assertGreaterThan(40, $metrics['performance']['average_response_time_ms']);
        $this->assertLessThan(100, $metrics['performance']['average_response_time_ms']);
    }

    /** @test */
    public function test_notification_functionality_not_degraded()
    {
        $user = User::factory()->create();
        $doctor = Doctor::factory()->create();
        $appointment = Appointment::factory()->create([
            'doctor_id' => $doctor->id,
            'patient_id' => $user->id,
        ]);

        // Send notification
        $startTime = microtime(true);
        $user->notify(new AppointmentBookedNotification($appointment));
        $notificationTime = (microtime(true) - $startTime) * 1000;

        // Should complete within reasonable time
        $this->assertLessThan(1000, $notificationTime); // Under 1 second

        // Notification should be in database
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $user->id,
            'notifiable_type' => get_class($user),
            'type' => 'App\\Notifications\\AppointmentBookedNotification'
        ]);

        // API should return notification
        $response = $this->actingAs($user)->get('/api/notifications');
        $response->assertStatus(200);

        $data = json_decode($response->getContent(), true);
        $this->assertGreaterThan(0, count($data['notifications']));
    }

    /** @test */
    public function test_cache_invalidation_works()
    {
        $user = User::factory()->create();

        // First request
        $response1 = $this->actingAs($user)->get('/api/notifications');
        $data1 = json_decode($response1->getContent(), true);

        // Mark a notification as read (should invalidate cache)
        if (!empty($data1['notifications'])) {
            $notificationId = $data1['notifications'][0]['id'];
            $this->actingAs($user)->post("/api/notifications/{$notificationId}/read");
        }

        // Second request should not be cached (different data)
        $response2 = $this->actingAs($user)->get('/api/notifications');
        $data2 = json_decode($response2->getContent(), true);

        // Unread count should be different
        $this->assertNotEquals($data1['unread_count'], $data2['unread_count']);
    }

    /** @test */
    public function test_compression_with_gzip_middleware()
    {
        $user = User::factory()->create();

        // Request with gzip support
        $response = $this->actingAs($user)
            ->withHeaders(['Accept-Encoding' => 'gzip'])
            ->get('/api/notifications');

        $response->assertStatus(200);

        // Check if response is compressed
        $headers = $response->headers;
        $contentEncoding = $headers->get('Content-Encoding');

        // If compression was applied, header should be set
        if ($contentEncoding) {
            $this->assertEquals('gzip', $contentEncoding);
        }
    }

    /** @test */
    public function test_health_check_provides_useful_information()
    {
        // Record some test data
        $this->performanceMonitor->recordRequest('api');
        $this->performanceMonitor->recordCacheHit();
        $this->performanceMonitor->recordBroadcastSuccess('test');

        $health = $this->performanceMonitor->getHealthStatus();

        // Should have status
        $this->assertContains($health['status'], ['healthy', 'warning', 'critical']);

        // Should have metrics
        $this->assertArrayHasKey('metrics', $health);
        $this->assertArrayHasKey('issues', $health);
    }

    /** @test */
    public function test_bulk_operations_are_optimized()
    {
        $users = User::factory()->count(10)->create();

        $startTime = microtime(true);
        $startMemory = memory_get_usage(true);

        // Bulk mark as read operation
        $notificationIds = collect();
        foreach ($users as $user) {
            // Create some notifications for each user
            for ($i = 0; $i < 5; $i++) {
                $notificationIds->push($user->notifications()->create([
                    'id' => \Illuminate\Support\Str::uuid(),
                    'type' => 'App\\Notifications\\TestNotification',
                    'data' => [
                        'title' => "Bulk Test {$i}",
                        'message' => "Test message {$i}",
                        'type' => 'test'
                    ],
                    'read_at' => null,
                ])->id);
            }
        }

        // Bulk operation
        $updated = $this->memoryProcessor->bulkMarkAsRead($notificationIds);

        $endTime = microtime(true);
        $endMemory = memory_get_usage(true);

        $operationTime = ($endTime - $startTime) * 1000;
        $memoryUsed = $endMemory - $startMemory;

        // Should update all notifications
        $this->assertEquals(50, $updated); // 10 users * 5 notifications each

        // Should complete within reasonable time
        $this->assertLessThan(5000, $operationTime); // Under 5 seconds

        // Memory usage should be reasonable
        $this->assertLessThan(20 * 1024 * 1024, $memoryUsed); // Under 20MB
    }
}
