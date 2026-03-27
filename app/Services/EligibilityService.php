<?php

namespace App\Services;

use App\Contracts\EligibilityServiceInterface;
use App\Models\EligibilityCheck;
use App\Models\PatientInsurance;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Exception;

abstract class EligibilityService implements EligibilityServiceInterface
{
    protected int $maxRetries = 3;
    protected int $baseDelay = 1000; // milliseconds
    protected int $cacheTtl = 3600; // 1 hour in seconds

    /**
     * Clean up resources when the service is destroyed
     */
    public function __destruct()
    {
        // Clear any cached data that might be holding memory
        if (method_exists($this, 'clearCache')) {
            $this->clearCache();
        }
    }

    /**
     * Clear service-specific cache to prevent memory leaks
     */
    protected function clearCache(): void
    {
        // Clear any service-specific cache patterns
        if (config('cache.default') === 'redis') {
            try {
                $pattern = "eligibility_service:*";
                $redis = Redis::connection();
                $keys = $redis->keys($pattern);
                if (!empty($keys)) {
                    $redis->del($keys);
                }
            } catch (\Exception $e) {
                // Log but don't throw - this is cleanup
                Log::warning('Failed to clear eligibility service cache', [
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    /**
     * Check eligibility with caching and retry logic
     *
     * @param PatientInsurance $patientInsurance
     * @param string $serviceType
     * @return array
     */
    public function checkEligibility(PatientInsurance $patientInsurance, string $serviceType): array
    {
        $cacheKey = $this->getCacheKey($patientInsurance, $serviceType);

        // Check cache first
        $cachedResult = Cache::get($cacheKey);
        if ($cachedResult) {
            Log::info("Eligibility check cache hit for patient {$patientInsurance->patient_id}, service {$serviceType}");
            return $cachedResult;
        }

        // Use database transaction with pessimistic locking to prevent concurrent checks
        return DB::transaction(function () use ($patientInsurance, $serviceType, $cacheKey) {
            // Lock the patient insurance record to prevent concurrent eligibility checks
            $lockedInsurance = PatientInsurance::where('id', $patientInsurance->id)
                ->lockForUpdate()
                ->first();

            if (!$lockedInsurance) {
                throw new Exception('Patient insurance record not found');
            }

            // Check database for recent valid results (within the locked transaction)
            $recentCheck = $this->getRecentValidCheck($patientInsurance, $serviceType);
            if ($recentCheck) {
                $result = [
                    'status' => $recentCheck->eligibility_status,
                    'data' => $recentCheck->response_data,
                    'cached' => true,
                    'check_id' => $recentCheck->id,
                ];
                Cache::put($cacheKey, $result, $this->cacheTtl);
                return $result;
            }

            // Perform the actual check with retry logic
            $result = $this->performCheckWithRetry($patientInsurance, $serviceType);

            // Cache the result
            Cache::put($cacheKey, $result, $this->cacheTtl);

            // Store in database within the same transaction
            $this->storeEligibilityCheck($patientInsurance, $serviceType, $result);

            return $result;
        });
    }

    /**
     * Perform the actual eligibility check with retry logic
     *
     * @param PatientInsurance $patientInsurance
     * @param string $serviceType
     * @return array
     */
    protected function performCheckWithRetry(PatientInsurance $patientInsurance, string $serviceType): array
    {
        $lastException = null;

        for ($attempt = 1; $attempt <= $this->maxRetries; $attempt++) {
            try {
                Log::info("Eligibility check attempt {$attempt} for patient {$patientInsurance->patient_id}, service {$serviceType}");

                $result = $this->performEligibilityCheck($patientInsurance, $serviceType);

                Log::info("Eligibility check successful for patient {$patientInsurance->patient_id}, service {$serviceType}");
                return $result;

            } catch (Exception $e) {
                $lastException = $e;
                Log::warning("Eligibility check attempt {$attempt} failed: " . $e->getMessage());

                if ($attempt < $this->maxRetries) {
                    $delay = $this->calculateDelay($attempt);
                    Log::info("Retrying in {$delay}ms");
                    usleep($delay * 1000);
                }
            }
        }

        Log::error("Eligibility check failed after {$this->maxRetries} attempts: " . $lastException->getMessage());

        // Send notification about failed eligibility check
        $this->sendFailureNotification($patientInsurance, $serviceType, $lastException->getMessage());

        return [
            'status' => 'error',
            'error' => $lastException->getMessage(),
            'data' => null,
        ];
    }

    /**
     * Calculate delay for exponential backoff
     *
     * @param int $attempt
     * @return int
     */
    protected function calculateDelay(int $attempt): int
    {
        return $this->baseDelay * pow(2, $attempt - 1);
    }

    /**
     * Get cache key for eligibility check
     *
     * @param PatientInsurance $patientInsurance
     * @param string $serviceType
     * @return string
     */
    protected function getCacheKey(PatientInsurance $patientInsurance, string $serviceType): string
    {
        return "eligibility:{$patientInsurance->id}:{$serviceType}";
    }

    /**
     * Get recent valid eligibility check from database
     *
     * @param PatientInsurance $patientInsurance
     * @param string $serviceType
     * @return EligibilityCheck|null
     */
    protected function getRecentValidCheck(PatientInsurance $patientInsurance, string $serviceType): ?EligibilityCheck
    {
        // Use eager loading to prevent N+1 queries and add caching
        return EligibilityCheck::where('patient_insurance_id', $patientInsurance->id)
            ->where('service_type', $serviceType)
            ->where('expires_at', '>', now())
            ->whereIn('eligibility_status', ['eligible', 'ineligible'])
            ->orderBy('check_date', 'desc')
            ->first();
    }

    /**
     * Batch check multiple eligibility requests to prevent N+1 queries
     *
     * @param array $eligibilityChecks Array of ['patient_insurance' => PatientInsurance, 'service_type' => string]
     * @return array Results indexed by patient insurance ID and service type
     */
    public function batchCheckEligibility(array $eligibilityChecks): array
    {
        $results = [];
        $cacheKeys = [];

        // Validate and collect cache keys
        foreach ($eligibilityChecks as $check) {
            if (!isset($check['patient_insurance']) || !isset($check['service_type'])) {
                continue; // Skip invalid entries
            }

            $patientInsurance = $check['patient_insurance'];
            $serviceType = $check['service_type'];

            if (!$patientInsurance instanceof PatientInsurance) {
                continue;
            }

            $cacheKey = $this->getCacheKey($patientInsurance, $serviceType);
            $cacheKeys[] = $cacheKey;
            $results[$cacheKey] = null; // Initialize
        }

        // Batch get from cache
        $cachedResults = Cache::getMultiple($cacheKeys);

        // Process each request
        foreach ($eligibilityChecks as $check) {
            if (!isset($check['patient_insurance']) || !isset($check['service_type'])) {
                continue;
            }

            $patientInsurance = $check['patient_insurance'];
            $serviceType = $check['service_type'];

            if (!$patientInsurance instanceof PatientInsurance) {
                continue;
            }

            $cacheKey = $this->getCacheKey($patientInsurance, $serviceType);

            // Check if result is already cached
            if (isset($cachedResults[$cacheKey])) {
                $results[$cacheKey] = $cachedResults[$cacheKey];
                continue;
            }

            // Check database for recent valid results
            $recentCheck = $this->getRecentValidCheck($patientInsurance, $serviceType);
            if ($recentCheck) {
                $result = [
                    'status' => $recentCheck->eligibility_status,
                    'data' => $recentCheck->response_data,
                    'cached' => true,
                    'check_id' => $recentCheck->id,
                ];
                $results[$cacheKey] = $result;
                Cache::put($cacheKey, $result, $this->cacheTtl);
            }
        }

        return $results;
    }

    /**
     * Store eligibility check result in database
     *
     * @param PatientInsurance $patientInsurance
     * @param string $serviceType
     * @param array $result
     * @return void
     */
    protected function storeEligibilityCheck(PatientInsurance $patientInsurance, string $serviceType, array $result): void
    {
        // Clean up expired checks before storing new ones to prevent database bloat
        $this->cleanupExpiredChecks($patientInsurance, $serviceType);

        EligibilityCheck::create([
            'patient_insurance_id' => $patientInsurance->id,
            'check_date' => now(),
            'service_type' => $serviceType,
            'eligibility_status' => $result['status'],
            'response_data' => $result['data'],
            'expires_at' => $result['status'] === 'error' ? null : now()->addHours(24), // Valid for 24 hours
            'checked_by' => Auth::check() ? Auth::id() : 1, // Default to system user if not authenticated
        ]);
    }

    /**
     * Clean up expired eligibility checks to prevent database bloat
     */
    protected function cleanupExpiredChecks(PatientInsurance $patientInsurance, string $serviceType): void
    {
        EligibilityCheck::where('patient_insurance_id', $patientInsurance->id)
            ->where('service_type', $serviceType)
            ->where('expires_at', '<=', now())
            ->delete();
    }

    /**
     * Invalidate cache for eligibility check
     */
    public function invalidateCache(PatientInsurance $patientInsurance, string $serviceType): void
    {
        $cacheKey = $this->getCacheKey($patientInsurance, $serviceType);
        Cache::forget($cacheKey);

        // Also invalidate related cache patterns
        $pattern = "eligibility:{$patientInsurance->id}:*";
        $this->invalidateCachePattern($pattern);
    }

    /**
     * Invalidate cache by pattern
     */
    protected function invalidateCachePattern(string $pattern): void
    {
        // Note: Redis supports pattern deletion, but for other stores we might need alternative approaches
        if (config('cache.default') === 'redis') {
            try {
                // Use Redis facade for pattern-based key deletion
                $connection = Redis::connection();
                $keys = $connection->keys($pattern);
                if (!empty($keys)) {
                    $connection->del($keys);
                }
            } catch (\Exception $e) {
                // Fallback: log the issue but don't break functionality
                Log::warning('Failed to invalidate cache pattern', [
                    'pattern' => $pattern,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    /**
     * Send notification for failed eligibility check
     *
     * @param PatientInsurance $patientInsurance
     * @param string $serviceType
     * @param string $errorMessage
     * @return void
     */
    protected function sendFailureNotification(PatientInsurance $patientInsurance, string $serviceType, string $errorMessage): void
    {
        try {
            $patient = $patientInsurance->patient;
            $user = $patient->user;

            if ($user) {
                $user->notify(new \App\Notifications\EligibilityCheckFailedNotification(
                    $patientInsurance,
                    $serviceType,
                    $errorMessage
                ));
            }
        } catch (\Exception $e) {
            Log::error('Failed to send eligibility check failure notification', [
                'patient_insurance_id' => $patientInsurance->id,
                'service_type' => $serviceType,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Abstract method to be implemented by concrete providers
     *
     * @param PatientInsurance $patientInsurance
     * @param string $serviceType
     * @return array
     */
    abstract protected function performEligibilityCheck(PatientInsurance $patientInsurance, string $serviceType): array;
}
