<?php

namespace App\Services;

use App\Models\PatientInsurance;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class WaystarEligibilityService extends EligibilityService
{
    protected string $baseUrl = 'https://api.waystar.com/v2';
    protected string $providerName = 'Waystar';

    /**
     * Check if this service supports the given insurance provider
     *
     * @param \App\Models\InsuranceProvider $provider
     * @return bool
     */
    public function supportsProvider(\App\Models\InsuranceProvider $provider): bool
    {
        // Check if the provider name contains 'waystar' or is configured to use Waystar
        return stripos($provider->name, 'waystar') !== false ||
               stripos($provider->api_endpoint ?? '', 'waystar') !== false;
    }

    /**
     * Get the provider name
     *
     * @return string
     */
    public function getProviderName(): string
    {
        return $this->providerName;
    }

    /**
     * Perform the actual eligibility check with Waystar API
     *
     * @param PatientInsurance $patientInsurance
     * @param string $serviceType
     * @return array
     */
    protected function performEligibilityCheck(PatientInsurance $patientInsurance, string $serviceType): array
    {
        // Load insurance provider with patient insurance to prevent N+1 queries
        if (!$patientInsurance->relationLoaded('insuranceProvider')) {
            $patientInsurance->load('insuranceProvider');
        }

        $provider = $patientInsurance->insuranceProvider;

        if (!$provider || !$provider->api_endpoint || !$provider->api_key) {
            throw new Exception('Insurance provider not properly configured for Waystar integration');
        }

        // Prepare the request payload
        $payload = $this->buildEligibilityRequestPayload($patientInsurance, $serviceType);

        try {
            // Add proper timeout handling and error recovery
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $provider->api_key,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'X-Waystar-API-Version' => '2.0',
            ])
            ->timeout(10) // Reduced from 30 to 10 seconds for better UX
            ->connectTimeout(5) // 5 second connection timeout
            ->retry(2, 100) // Retry 2 times with 100ms delay
            ->post($provider->api_endpoint . '/eligibility/verification', $payload);

            if ($response->successful()) {
                return $this->parseEligibilityResponse($response->json());
            } else {
                Log::error('Waystar API error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'patient_insurance_id' => $patientInsurance->id,
                ]);

                throw new Exception('Waystar API returned error: ' . $response->status() . ' - ' . $response->body());
            }

        } catch (\Illuminate\Http\Client\RequestException $e) {
            Log::error('Waystar API request failed (HTTP)', [
                'error' => $e->getMessage(),
                'patient_insurance_id' => $patientInsurance->id,
                'url' => $provider->api_endpoint . '/eligibility/verification'
            ]);

            // Re-throw as generic exception to maintain compatibility
            throw new Exception('Waystar API request failed: ' . $e->getMessage());
        } catch (Exception $e) {
            Log::error('Waystar API request failed', [
                'error' => $e->getMessage(),
                'patient_insurance_id' => $patientInsurance->id,
            ]);

            throw $e;
        }
    }

    /**
     * Build the eligibility request payload for Waystar
     *
     * @param PatientInsurance $patientInsurance
     * @param string $serviceType
     * @return array
     */
    protected function buildEligibilityRequestPayload(PatientInsurance $patientInsurance, string $serviceType): array
    {
        $patient = $patientInsurance->patient;

        return [
            'payerId' => $patientInsurance->insurance_provider_id,
            'subscriber' => [
                'memberId' => $patientInsurance->subscriber_id,
                'firstName' => $patient->name ? explode(' ', $patient->name)[0] : '',
                'lastName' => $patient->name ? (explode(' ', $patient->name)[1] ?? '') : '',
                'dateOfBirth' => $patient->age ? now()->subYears($patient->age)->format('Y-m-d') : null,
                'gender' => $patient->gender,
            ],
            'patient' => [
                'relationship' => $patientInsurance->relationship_to_subscriber,
                'memberId' => $patientInsurance->subscriber_id,
            ],
            'provider' => [
                'npi' => config('services.waystar.npi'),
                'taxonomy' => $serviceType,
            ],
            'service' => [
                'type' => $serviceType,
                'dateOfService' => now()->format('Y-m-d'),
            ],
            'requestType' => 'eligibility',
        ];
    }

    /**
     * Parse the eligibility response from Waystar
     *
     * @param array $response
     * @return array
     */
    protected function parseEligibilityResponse(array $response): array
    {
        // Parse Waystar-specific response structure
        $status = $response['eligibilityStatus'] ?? 'unknown';

        $eligibilityStatus = match(strtolower($status)) {
            'active', 'eligible', 'covered' => 'eligible',
            'inactive', 'ineligible', 'not covered' => 'ineligible',
            'pending', 'pending verification' => 'pending',
            default => 'error'
        };

        return [
            'status' => $eligibilityStatus,
            'data' => $response,
            'coverage' => $response['coverageDetails'] ?? null,
            'benefits' => $response['benefitInformation'] ?? null,
            'deductibles' => $response['deductibleInfo'] ?? null,
        ];
    }
}
