<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use App\Models\Appointment;
use App\Models\User;
use App\Events\AppointmentStatusChangedEvent;
use Illuminate\Support\Facades\Cache;

class AppointmentBroadcastService
{
    // Rate limiting configuration
    protected int $maxBroadcastsPerMinute = 60;
    protected int $maxBroadcastsPerHour = 1000;
    protected int $burstLimit = 10; // Max broadcasts in a short burst
    protected int $cacheTtl = 3600; // 1 hour

    public function __construct()
    {
        // No dependencies needed - using Laravel's built-in broadcasting
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
     * Broadcast appointment status change with rate limiting
     */
    public function broadcastStatusChange(Appointment $appointment, string $oldStatus, string $newStatus, $changedBy = null): bool
    {
        try {
            // Check rate limits
            if (!$this->checkBurstLimit('status_change')) {
                Log::warning('Broadcast rate limit exceeded for status_change', [
                    'appointment_id' => $appointment->id,
                    'burst_limit' => $this->burstLimit
                ]);
                return false;
            }

            if (!$this->checkRateLimit('status_change_minute', $this->maxBroadcastsPerMinute)) {
                Log::warning('Broadcast rate limit exceeded for status_change_minute', [
                    'appointment_id' => $appointment->id,
                    'max_per_minute' => $this->maxBroadcastsPerMinute
                ]);
                return false;
            }

            if (!$this->checkRateLimit('status_change_hour', $this->maxBroadcastsPerHour, 3600)) {
                Log::warning('Broadcast rate limit exceeded for status_change_hour', [
                    'appointment_id' => $appointment->id,
                    'max_per_hour' => $this->maxBroadcastsPerHour
                ]);
                return false;
            }

            // Fire the event which handles broadcasting
            event(new AppointmentStatusChangedEvent($appointment, $oldStatus, $newStatus, $changedBy));

            Log::info('Appointment status change broadcasted', [
                'appointment_id' => $appointment->id,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'changed_by' => $changedBy
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to broadcast appointment status change', [
                'appointment_id' => $appointment->id,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'error' => $e->getMessage()
            ]);
            return false;
        }
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

        // Broadcast using Laravel's built-in event system
        foreach ($channels as $channel) {
            event(new \App\Events\NotificationBroadcast($channel, $eventData, 'appointment_list_update'));
        }

        Log::info('Appointment list update broadcasted', [
            'channels_count' => count($channels),
            'user_ids_count' => count($userIds)
        ]);

        return true;
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
}
