<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

class RealtimeCacheService
{
    protected int $cacheTtl = 300; // 5 minutes for frequently accessed data
    protected int $longCacheTtl = 3600; // 1 hour for less frequently changing data

    /**
     * Cache keys
     */
    const CACHE_KEY_TODAY_APPOINTMENTS = 'realtime:today_appointments';
    const CACHE_KEY_DOCTOR_APPOINTMENTS = 'realtime:doctor_appointments:';
    const CACHE_KEY_PATIENT_APPOINTMENTS = 'realtime:patient_appointments:';
    const CACHE_KEY_APPOINTMENT_DETAIL = 'realtime:appointment:';
    const CACHE_KEY_APPOINTMENT_STATUS_COUNTS = 'realtime:status_counts';
    const CACHE_KEY_USER_SUBSCRIPTIONS = 'realtime:user_subscriptions';

    /**
     * Get today's appointments from cache or database
     */
    public function getTodaysAppointments(): Collection
    {
        return Cache::remember(
            self::CACHE_KEY_TODAY_APPOINTMENTS,
            $this->cacheTtl,
            function () {
                return Appointment::with(['doctor.user', 'patient'])
                    ->whereDate('appointment_date', today())
                    ->orderBy('appointment_date')
                    ->get();
            }
        );
    }

    /**
     * Get appointments for a specific doctor
     */
    public function getDoctorAppointments(int $doctorId, ?string $date = null): Collection
    {
        $cacheKey = self::CACHE_KEY_DOCTOR_APPOINTMENTS . $doctorId . ($date ? ":{$date}" : '');

        return Cache::remember(
            $cacheKey,
            $this->cacheTtl,
            function () use ($doctorId, $date) {
                $query = Appointment::with(['doctor.user', 'patient'])
                    ->where('doctor_id', $doctorId);

                if ($date) {
                    $query->whereDate('appointment_date', $date);
                } else {
                    $query->whereDate('appointment_date', '>=', today());
                }

                return $query->orderBy('appointment_date')->get();
            }
        );
    }

    /**
     * Get appointments for a specific patient
     */
    public function getPatientAppointments(int $patientId): Collection
    {
        $cacheKey = self::CACHE_KEY_PATIENT_APPOINTMENTS . $patientId;

        return Cache::remember(
            $cacheKey,
            $this->cacheTtl,
            function () use ($patientId) {
                return Appointment::with(['doctor.user', 'patient'])
                    ->where('patient_id', $patientId)
                    ->whereDate('appointment_date', '>=', today()->subDays(30))
                    ->orderBy('appointment_date')
                    ->get();
            }
        );
    }

    /**
     * Get detailed appointment data
     */
    public function getAppointmentDetail(int $appointmentId): ?Appointment
    {
        $cacheKey = self::CACHE_KEY_APPOINTMENT_DETAIL . $appointmentId;

        return Cache::remember(
            $cacheKey,
            $this->longCacheTtl,
            function () use ($appointmentId) {
                return Appointment::with(['doctor.user', 'patient', 'prescriptions', 'review'])
                    ->find($appointmentId);
            }
        );
    }

    /**
     * Get appointment status counts for dashboard
     */
    public function getAppointmentStatusCounts(): array
    {
        return Cache::remember(
            self::CACHE_KEY_APPOINTMENT_STATUS_COUNTS,
            $this->cacheTtl,
            function () {
                return [
                    'today' => [
                        'total' => Appointment::whereDate('appointment_date', today())->count(),
                        'pending' => Appointment::whereDate('appointment_date', today())->where('status', 'pending')->count(),
                        'confirmed' => Appointment::whereDate('appointment_date', today())->where('status', 'confirmed')->count(),
                        'completed' => Appointment::whereDate('appointment_date', today())->where('status', 'completed')->count(),
                        'cancelled' => Appointment::whereDate('appointment_date', today())->where('status', 'cancelled')->count(),
                        'no_show' => Appointment::whereDate('appointment_date', today())->where('status', 'no_show')->count(),
                    ],
                    'upcoming' => [
                        'total' => Appointment::whereDate('appointment_date', '>', today())->count(),
                        'this_week' => Appointment::whereBetween('appointment_date', [today(), today()->endOfWeek()])->count(),
                        'next_week' => Appointment::whereBetween('appointment_date', [today()->endOfWeek()->addDay(), today()->endOfWeek()->addDays(7)])->count(),
                    ]
                ];
            }
        );
    }

    /**
     * Update appointment in cache after changes
     */
    public function updateAppointmentInCache(Appointment $appointment): void
    {
        // Update detailed appointment cache
        $detailCacheKey = self::CACHE_KEY_APPOINTMENT_DETAIL . $appointment->id;
        Cache::put($detailCacheKey, $appointment->load(['doctor.user', 'patient', 'prescriptions', 'review']), $this->longCacheTtl);

        // Invalidate related caches
        $this->invalidateAppointmentCache($appointment);

        Log::info('Appointment updated in cache', [
            'appointment_id' => $appointment->id,
            'status' => $appointment->status
        ]);
    }

    /**
     * Remove appointment from cache
     */
    public function removeAppointmentFromCache(Appointment $appointment): void
    {
        $detailCacheKey = self::CACHE_KEY_APPOINTMENT_DETAIL . $appointment->id;
        Cache::forget($detailCacheKey);

        $this->invalidateAppointmentCache($appointment);

        Log::info('Appointment removed from cache', [
            'appointment_id' => $appointment->id
        ]);
    }

    /**
     * Invalidate caches related to an appointment
     */
    public function invalidateAppointmentCache(Appointment $appointment): void
    {
        // Invalidate today's appointments if this appointment is today
        if ($appointment->appointment_date->isToday()) {
            Cache::forget(self::CACHE_KEY_TODAY_APPOINTMENTS);
        }

        // Invalidate doctor-specific cache
        if ($appointment->doctor_id) {
            $doctorCacheKey = self::CACHE_KEY_DOCTOR_APPOINTMENTS . $appointment->doctor_id;
            Cache::forget($doctorCacheKey);

            // Also invalidate date-specific doctor cache
            $doctorDateCacheKey = self::CACHE_KEY_DOCTOR_APPOINTMENTS . $appointment->doctor_id . ':' . $appointment->appointment_date->format('Y-m-d');
            Cache::forget($doctorDateCacheKey);
        }

        // Invalidate patient-specific cache
        if ($appointment->patient_id) {
            $patientCacheKey = self::CACHE_KEY_PATIENT_APPOINTMENTS . $appointment->patient_id;
            Cache::forget($patientCacheKey);
        }

        // Invalidate status counts
        Cache::forget(self::CACHE_KEY_APPOINTMENT_STATUS_COUNTS);

        Log::info('Appointment-related caches invalidated', [
            'appointment_id' => $appointment->id
        ]);
    }

    /**
     * Cache user subscription data
     */
    public function cacheUserSubscription(User $user, array $subscriptionData): void
    {
        $cacheKey = self::CACHE_KEY_USER_SUBSCRIPTIONS . ":{$user->id}";
        Cache::put($cacheKey, $subscriptionData, $this->longCacheTtl);

        Log::info('User subscription cached', [
            'user_id' => $user->id,
            'subscription_type' => $subscriptionData['type'] ?? 'unknown'
        ]);
    }

    /**
     * Get cached user subscription
     */
    public function getUserSubscription(User $user): ?array
    {
        $cacheKey = self::CACHE_KEY_USER_SUBSCRIPTIONS . ":{$user->id}";
        return Cache::get($cacheKey);
    }

    /**
     * Remove user subscription from cache
     */
    public function removeUserSubscription(User $user): void
    {
        $cacheKey = self::CACHE_KEY_USER_SUBSCRIPTIONS . ":{$user->id}";
        Cache::forget($cacheKey);

        Log::info('User subscription removed from cache', [
            'user_id' => $user->id
        ]);
    }

    /**
     * Warm up frequently accessed caches
     */
    public function warmupCaches(): void
    {
        Log::info('Starting cache warmup for real-time services');

        // Warm up today's appointments
        $this->getTodaysAppointments();

        // Warm up status counts
        $this->getAppointmentStatusCounts();

        Log::info('Cache warmup completed');
    }

    /**
     * Clear all real-time caches
     */
    public function clearAllCaches(): void
    {
        $keys = [
            self::CACHE_KEY_TODAY_APPOINTMENTS,
            self::CACHE_KEY_APPOINTMENT_STATUS_COUNTS,
        ];

        foreach ($keys as $key) {
            Cache::forget($key);
        }

        // Clear user subscription caches (this is a pattern-based clear)
        $subscriptionKeys = Cache::get('realtime:user_subscriptions_keys', []);
        foreach ($subscriptionKeys as $key) {
            Cache::forget($key);
        }

        Log::info('All real-time caches cleared');
    }

    /**
     * Get cache statistics
     */
    public function getCacheStats(): array
    {
        return [
            'cache_ttl' => $this->cacheTtl,
            'long_cache_ttl' => $this->longCacheTtl,
            'cache_store' => Cache::getStore() ? get_class(Cache::getStore()) : 'unknown',
            'today_appointments_cached' => Cache::has(self::CACHE_KEY_TODAY_APPOINTMENTS),
            'status_counts_cached' => Cache::has(self::CACHE_KEY_APPOINTMENT_STATUS_COUNTS),
            'last_updated' => now()
        ];
    }
}
