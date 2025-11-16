<?php

namespace App\Services;

use App\Services\PusherConnectionPool;
use App\Services\MultiDeviceSynchronizationService;
use App\Services\PayloadCompressionService;
use App\Services\RealtimePerformanceMonitoringService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use App\Models\User;
use App\Models\Appointment;
use App\Events\AppointmentStatusChangedEvent;

class AppointmentBroadcastService
{
    protected PusherConnectionPool $pusherPool;
    protected PayloadCompressionService $compressionService;
    protected RealtimePerformanceMonitoringService $performanceService;
    protected int $cacheTtl = 3600; // 1 hour

    // Rate limiting configuration
    protected int $maxBroadcastsPerMinute = 60;
    protected int $maxBroadcastsPerHour = 1000;
    protected int $burstLimit = 10; // Max broadcasts in a short burst

    public function __construct(
        PusherConnectionPool $pusherPool,
        PayloadCompressionService $compressionService,
        RealtimePerformanceMonitoringService $performanceService
    ) {
        $this->pusherPool = $pusherPool;
        $this->compressionService = $compressionService;
        $this->performanceService = $performanceService;
    }

    /**
     * Check if broadcast operation is within rate limits
     */
    protected function checkRateLimit(string $key, int $maxAttempts, int $decaySeconds = 60): bool
    {
        $limiterKey = "broadcast:{$key}";

        if (RateLimiter::tooManyAttempts($limiterKey, $maxAttempts)) {
            Log::warning('Broadcast rate limit exceeded', [
                'key' => $key,
                'max_attempts' => $maxAttempts,
                'decay_seconds' => $decaySeconds,
                'available_in' => RateLimiter::availableIn($limiterKey)
            ]);
            return false;
        }

        RateLimiter::hit($limiterKey, $decaySeconds);
        return true;
    }

    /**
     * Check burst rate limit for immediate broadcasts
     */
    protected function checkBurstLimit(string $key): bool
    {
        return $this->checkRateLimit("burst:{$key}", $this->burstLimit, 10); // 10 broadcasts per 10 seconds
    }

    /**
     * Broadcast appointment status change with rate limiting and multi-device sync
     */
    public function broadcastStatusChange(Appointment $appointment, string $oldStatus, string $newStatus, $changedBy = null): bool
    {
        $startTime = microtime(true);

        // Check rate limits
        if (!$this->checkBurstLimit('status_change') ||
            !$this->checkRateLimit('status_change_minute', $this->maxBroadcastsPerMinute) ||
            !$this->checkRateLimit('status_change_hour', $this->maxBroadcastsPerHour, 3600)) {
            Log::warning('Status change broadcast blocked by rate limiting', [
                'appointment_id' => $appointment->id,
                'old_status' => $oldStatus,
                'new_status' => $newStatus
            ]);

            // Record failed broadcast
            $this->performanceService->recordBroadcastMetrics([
                'success' => false,
                'latency' => (microtime(true) - $startTime) * 1000,
                'compressed' => false
            ]);

            return false;
        }

        // Fire the event which handles broadcasting
        event(new AppointmentStatusChangedEvent($appointment, $oldStatus, $newStatus, $changedBy));

        // Trigger multi-device synchronization if user context is available
        if ($changedBy && $changedBy instanceof \App\Models\User) {
            $this->triggerMultiDeviceSync($appointment, $changedBy, [
                'status' => $newStatus,
                'updated_at' => $appointment->updated_at
            ]);
        }

        $latency = (microtime(true) - $startTime) * 1000;

        // Record successful broadcast metrics
        $this->performanceService->recordBroadcastMetrics([
            'success' => true,
            'latency' => $latency,
            'compressed' => true, // Status change events use compression
            'compression_ratio' => 0.7 // Estimated compression ratio
        ]);

        Log::info('Appointment status change broadcasted', [
            'appointment_id' => $appointment->id,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'changed_by' => $changedBy,
            'latency_ms' => round($latency, 2)
        ]);

        return true;
    }

    /**
     * Subscribe user to real-time appointment updates
     */
    public function subscribeToAppointments(User $user, array $filters = []): bool
    {
        $subscriptionKey = "appointment_sub_{$user->id}";

        $subscription = [
            'user_id' => $user->id,
            'filters' => $filters,
            'subscribed_at' => now(),
            'last_activity' => now()
        ];

        Cache::put($subscriptionKey, $subscription, $this->cacheTtl);

        Log::info('User subscribed to appointment updates', [
            'user_id' => $user->id,
            'filters' => $filters
        ]);

        return true;
    }

    /**
     * Unsubscribe user from appointment updates
     */
    public function unsubscribeFromAppointments(User $user): bool
    {
        $subscriptionKey = "appointment_sub_{$user->id}";

        Cache::forget($subscriptionKey);

        Log::info('User unsubscribed from appointment updates', [
            'user_id' => $user->id
        ]);

        return true;
    }

    /**
     * Get today's appointments for a user with real-time subscription
     */
    public function getTodaysAppointments(User $user, array $filters = []): array
    {
        $query = Appointment::with(['doctor.user', 'patient'])
            ->whereDate('appointment_date', today());

        // Apply role-based filtering
        if ($user->role === 'doctor' && $user->doctor) {
            $query->where('doctor_id', $user->doctor->id);
        } elseif (in_array($user->role, ['admin', 'hospital_admin'])) {
            // Admins see all appointments
        } else {
            // Patients see only their appointments
            $query->where('patient_id', $user->id);
        }

        // Apply additional filters
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['doctor_id'])) {
            $query->where('doctor_id', $filters['doctor_id']);
        }

        $appointments = $query->orderBy('appointment_date')->get();

        // Subscribe user to real-time updates
        $this->subscribeToAppointments($user, $filters);

        return [
            'appointments' => $appointments->map(function ($appointment) {
                return [
                    'id' => $appointment->id,
                    'appointment_number' => $appointment->appointment_number,
                    'appointment_date' => $appointment->appointment_date->format('Y-m-d H:i:s'),
                    'status' => $appointment->status,
                    'appointment_type' => $appointment->appointment_type,
                    'doctor' => $appointment->doctor ? [
                        'id' => $appointment->doctor->id,
                        'name' => $appointment->doctor->user->name ?? 'Unknown Doctor'
                    ] : null,
                    'patient' => $appointment->patient ? [
                        'id' => $appointment->patient->id,
                        'name' => $appointment->patient->name
                    ] : [
                        'name' => $appointment->guest_name ?? 'Guest Patient'
                    ],
                    'duration' => $appointment->duration,
                    'reason' => $appointment->reason,
                    'notes' => $appointment->notes
                ];
            }),
            'subscription_channels' => $this->getUserSubscriptionChannels($user),
            'last_updated' => now()->toISOString()
        ];
    }

    /**
     * Get subscription channels for a user
     */
    protected function getUserSubscriptionChannels(User $user): array
    {
        $channels = [];

        // User-specific channel
        $channels[] = "user.{$user->id}";
        $channels[] = "App.User.{$user->id}";

        // Role-based channels
        if ($user->role === 'doctor' && $user->doctor) {
            $channels[] = "doctor.{$user->doctor->id}";
        }

        if (in_array($user->role, ['admin', 'hospital_admin'])) {
            $channels[] = "admin";
        }

        return $channels;
    }

    /**
     * Broadcast appointment list update to subscribed users with rate limiting
     */
    public function broadcastAppointmentListUpdate(array $userIds = null): bool
    {
        // Check rate limits for list updates
        if (!$this->checkBurstLimit('list_update') ||
            !$this->checkRateLimit('list_update_minute', $this->maxBroadcastsPerMinute / 2)) { // Lower limit for list updates
            Log::warning('Appointment list update broadcast blocked by rate limiting');
            return false;
        }

        if ($userIds === null) {
            // Get all subscribed users
            $subscriptions = Cache::get('appointment_subscriptions', []);
            $userIds = array_keys($subscriptions);
        }

        if (empty($userIds)) {
            return true; // No users to broadcast to
        }

        $channels = [];
        foreach ($userIds as $userId) {
            $channels[] = "user.{$userId}";
            $channels[] = "App.User.{$userId}";
        }

        $eventData = [
            'type' => 'appointment_list_update',
            'message' => 'Appointment list has been updated',
            'timestamp' => now()->toISOString(),
            'event_id' => uniqid('appointment_list_', true)
        ];

        // Compress the payload for efficient broadcasting
        $compressedPayload = $this->compressionService->compress($eventData);

        return $this->pusherPool->broadcast($channels, 'appointments.updated', $compressedPayload);
    }

    /**
     * Update user activity timestamp
     */
    public function updateUserActivity(User $user): void
    {
        $subscriptionKey = "appointment_sub_{$user->id}";

        $subscription = Cache::get($subscriptionKey);
        if ($subscription) {
            $subscription['last_activity'] = now();
            Cache::put($subscriptionKey, $subscription, $this->cacheTtl);
        }
    }

    /**
     * Clean up inactive subscriptions
     */
    public function cleanupInactiveSubscriptions(int $inactiveHours = 24): int
    {
        $inactiveTime = now()->subHours($inactiveHours);
        $cleaned = 0;

        // Get all appointment subscriptions
        $subscriptions = Cache::get('appointment_subscriptions', []);

        foreach ($subscriptions as $key => $subscription) {
            if ($subscription['last_activity'] < $inactiveTime) {
                Cache::forget($key);
                unset($subscriptions[$key]);
                $cleaned++;
            }
        }

        Cache::put('appointment_subscriptions', $subscriptions, $this->cacheTtl);

        Log::info('Cleaned up inactive appointment subscriptions', [
            'cleaned_count' => $cleaned,
            'inactive_hours' => $inactiveHours
        ]);

        return $cleaned;
    }

    /**
     * Broadcast appointment creation with rate limiting
     */
    public function broadcastAppointmentCreated(Appointment $appointment): bool
    {
        // Check rate limits for appointment creation broadcasts
        if (!$this->checkBurstLimit('appointment_created') ||
            !$this->checkRateLimit('appointment_created_minute', $this->maxBroadcastsPerMinute)) {
            Log::warning('Appointment creation broadcast blocked by rate limiting', [
                'appointment_id' => $appointment->id
            ]);
            return false;
        }

        $channels = $this->getAppointmentChannels($appointment);

        $eventData = [
            'type' => 'appointment_created',
            'appointment' => $this->formatAppointmentData($appointment),
            'timestamp' => now()->toISOString(),
            'event_id' => uniqid('appointment_created_', true)
        ];

        // Compress appointment data for efficient broadcasting
        $compressedPayload = $this->compressionService->compressAppointmentData($eventData);

        return $this->pusherPool->broadcast($channels, 'appointment.created', $compressedPayload);
    }

    /**
     * Broadcast appointment update (non-status changes) with rate limiting
     */
    public function broadcastAppointmentUpdated(Appointment $appointment, array $changedAttributes): bool
    {
        // Check rate limits for appointment update broadcasts
        if (!$this->checkBurstLimit('appointment_updated') ||
            !$this->checkRateLimit('appointment_updated_minute', $this->maxBroadcastsPerMinute)) {
            Log::warning('Appointment update broadcast blocked by rate limiting', [
                'appointment_id' => $appointment->id,
                'changed_attributes' => array_keys($changedAttributes)
            ]);
            return false;
        }

        $channels = $this->getAppointmentChannels($appointment);

        $eventData = [
            'type' => 'appointment_updated',
            'appointment' => $this->formatAppointmentData($appointment),
            'changed_attributes' => array_keys($changedAttributes),
            'timestamp' => now()->toISOString(),
            'event_id' => uniqid('appointment_updated_', true)
        ];

        // Compress appointment data for efficient broadcasting
        $compressedPayload = $this->compressionService->compressAppointmentData($eventData);

        return $this->pusherPool->broadcast($channels, 'appointment.updated', $compressedPayload);
    }

    /**
     * Broadcast appointment deletion with rate limiting
     */
    public function broadcastAppointmentDeleted(Appointment $appointment): bool
    {
        // Check rate limits for appointment deletion broadcasts
        if (!$this->checkBurstLimit('appointment_deleted') ||
            !$this->checkRateLimit('appointment_deleted_minute', $this->maxBroadcastsPerMinute / 4)) { // Very low limit for deletions
            Log::warning('Appointment deletion broadcast blocked by rate limiting', [
                'appointment_id' => $appointment->id
            ]);
            return false;
        }

        $channels = $this->getAppointmentChannels($appointment);

        $eventData = [
            'type' => 'appointment_deleted',
            'appointment_id' => $appointment->id,
            'appointment_number' => $appointment->appointment_number,
            'timestamp' => now()->toISOString(),
            'event_id' => uniqid('appointment_deleted_', true)
        ];

        // Compress the payload for efficient broadcasting
        $compressedPayload = $this->compressionService->compress($eventData);

        return $this->pusherPool->broadcast($channels, 'appointment.deleted', $compressedPayload);
    }

    /**
     * Get channels for appointment broadcasting
     */
    protected function getAppointmentChannels(Appointment $appointment): array
    {
        $channels = [];

        // Doctor-specific channel
        if ($appointment->doctor) {
            $channels[] = "doctor.{$appointment->doctor->id}";
        }

        // Patient-specific channel
        if ($appointment->patient) {
            $channels[] = "patient.{$appointment->patient->id}";
        }

        // Admin channels
        $channels[] = "admin.appointments";

        // Date-specific channel for appointment boards
        $dateChannel = "appointments." . $appointment->appointment_date->format('Y-m-d');
        $channels[] = $dateChannel;

        return array_unique($channels);
    }

    /**
     * Format appointment data for broadcasting
     */
    protected function formatAppointmentData(Appointment $appointment): array
    {
        return [
            'id' => $appointment->id,
            'appointment_number' => $appointment->appointment_number,
            'appointment_date' => $appointment->appointment_date->format('Y-m-d H:i:s'),
            'status' => $appointment->status,
            'appointment_type' => $appointment->appointment_type,
            'doctor' => $appointment->doctor ? [
                'id' => $appointment->doctor->id,
                'name' => $appointment->doctor->user->name ?? 'Unknown Doctor'
            ] : null,
            'patient' => $appointment->patient ? [
                'id' => $appointment->patient->id,
                'name' => $appointment->patient->name
            ] : [
                'name' => $appointment->guest_name ?? 'Guest Patient'
            ],
            'duration' => $appointment->duration,
            'reason' => $appointment->reason,
            'notes' => $appointment->notes
        ];
    }

    /**
     * Get subscription statistics
     */
    public function getSubscriptionStats(): array
    {
        $subscriptions = Cache::get('appointment_subscriptions', []);

        return [
            'total_active_subscriptions' => count($subscriptions),
            'cache_ttl' => $this->cacheTtl,
            'last_updated' => now()
        ];
    }

    /**
     * Trigger multi-device synchronization for appointment updates
     */
    protected function triggerMultiDeviceSync(Appointment $appointment, $changedBy, array $updateData): void
    {
        try {
            // Get device ID from request or connection context
            $deviceId = $this->getCurrentDeviceId($changedBy);

            if (!$deviceId) {
                Log::debug('No device ID available for multi-device sync', [
                    'appointment_id' => $appointment->id,
                    'user_id' => $changedBy->id ?? null
                ]);
                return;
            }

            // Get expected version for conflict detection
            $expectedVersion = app(MultiDeviceSynchronizationService::class)
                ->getComprehensiveSyncStatus($changedBy->id)['global_state']['latest_version'] ?? 0;

            // Prepare appointment data for sync
            $appointmentData = array_merge([
                'id' => $appointment->id,
                'appointment_number' => $appointment->appointment_number,
                'appointment_date' => $appointment->appointment_date->toISOString(),
                'status' => $appointment->status,
                'updated_at' => $appointment->updated_at->toISOString()
            ], $updateData);

            // Trigger multi-device synchronization
            $syncResult = app(MultiDeviceSynchronizationService::class)
                ->handleMultiDeviceAppointmentUpdate($changedBy, $deviceId, $appointmentData, $expectedVersion);

            Log::info('Multi-device sync triggered for appointment update', [
                'appointment_id' => $appointment->id,
                'user_id' => $changedBy->id,
                'device_id' => $deviceId,
                'sync_result' => $syncResult
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to trigger multi-device sync', [
                'appointment_id' => $appointment->id,
                'user_id' => $changedBy->id ?? null,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get current device ID from request/connection context
     */
    protected function getCurrentDeviceId($user): ?string
    {
        // Try to get from request header
        $deviceId = request()->header('X-Device-ID');

        if ($deviceId) {
            return $deviceId;
        }

        // Try to get from user session/connection
        if ($user && method_exists($user, 'currentAccessToken')) {
            $token = $user->currentAccessToken();
            if ($token && isset($token->meta['device_id'])) {
                return $token->meta['device_id'];
            }
        }

        // Generate a default device ID based on user agent and IP
        $userAgent = request()->userAgent();
        $ip = request()->ip();

        if ($userAgent && $ip) {
            return 'device_' . md5($user->id . $userAgent . $ip);
        }

        return null;
    }
}
