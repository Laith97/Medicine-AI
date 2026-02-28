<?php

namespace App\Services;

use App\Models\PatientInsurance;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class ChangeHealthcareEligibilityService extends EligibilityService
{
    protected string $baseUrl = 'https://api.changehealthcare.com/medicalnetwork/eligibility/v3';
    protected string $providerName = 'Change Healthcare';

    /**
     * Check if this service supports the given insurance provider
     *
     * @param \App\Models\InsuranceProvider $provider
     * @return bool
     */
    public function supportsProvider(\App\Models\InsuranceProvider $provider): bool
    {
        // Check if the provider name contains 'change healthcare' or is configured to use Change Healthcare
        return stripos($provider->name, 'change healthcare') !== false ||
               stripos($provider->name, 'changehealthcare') !== false ||
               stripos($provider->api_endpoint ?? '', 'changehealthcare') !== false;
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
     * Perform the actual eligibility check with Change Healthcare API
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
            throw new Exception('Insurance provider not properly configured for Change Healthcare integration');
        }

        // Prepare the request payload
        $payload = $this->buildEligibilityRequestPayload($patientInsurance, $serviceType);

        try {
            // Add proper timeout handling and error recovery
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $provider->api_key,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'X-Change-Healthcare-API-Version' => '3.0',
            ])
            ->timeout(10) // Reduced from 30 to 10 seconds for better UX
            ->connectTimeout(5) // 5 second connection timeout
            ->retry(2, 100) // Retry 2 times with 100ms delay
            ->post($provider->api_endpoint . '/eligibility', $payload);

            if ($response->successful()) {
                return $this->parseEligibilityResponse($response->json());
            } else {
                Log::error('Change Healthcare API error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'patient_insurance_id' => $patientInsurance->id,
                ]);

                throw new Exception('Change Healthcare API returned error: ' . $response->status() . ' - ' . $response->body());
            }

        } catch (\Illuminate\Http\Client\RequestException $e) {
            Log::error('Change Healthcare API request failed (HTTP)', [
                'error' => $e->getMessage(),
                'patient_insurance_id' => $patientInsurance->id,
                'url' => $provider->api_endpoint . '/eligibility'
            ]);

            // Re-throw as generic exception to maintain compatibility
            throw new Exception('Change Healthcare API request failed: ' . $e->getMessage());
        } catch (Exception $e) {
            Log::error('Change Healthcare API request failed', [
                'error' => $e->getMessage(),
                'patient_insurance_id' => $patientInsurance->id,
            ]);

            throw $e;
        }
    }

    /**
     * Build the eligibility request payload for Change Healthcare
     *
     * @param PatientInsurance $patientInsurance
     * @param string $serviceType
     * @return array
     */
    protected function buildEligibilityRequestPayload(PatientInsurance $patientInsurance, string $serviceType): array
    {
        $patient = $patientInsurance->patient;

        return [
            'controlNumber' => 'ELIG' . time() . rand(1000, 9999),
            'tradingPartnerServiceId' => $patientInsurance->insurance_provider_id,
            'subscriber' => [
                'memberId' => $patientInsurance->subscriber_id,
                'firstName' => $patient->name ? explode(' ', $patient->name)[0] : '',
                'lastName' => $patient->name ? (explode(' ', $patient->name)[1] ?? '') : '',
                'dateOfBirth' => $patient->age ? now()->subYears($patient->age)->format('Y-m-d') : null,
                'gender' => $patient->gender,
            ],
            'dependent' => $patientInsurance->relationship_to_subscriber !== 'self' ? [
                'relationship' => $patientInsurance->relationship_to_subscriber,
                'memberId' => $patientInsurance->subscriber_id,
            ] : null,
            'provider' => [
                'npi' => config('services.change_healthcare.npi'),
                'taxonomy' => $serviceType,
            ],
            'service' => [
                'type' => $serviceType,
                'dateOfService' => now()->format('Y-m-d'),
            ],
        ];
    }

    /**
     * Parse the eligibility response from Change Healthcare
     *
     * @param array $response
     * @return array
     */
    protected function parseEligibilityResponse(array $response): array
    {
        // Parse Change Healthcare-specific response structure
        $status = $response['planStatus'][0]['statusCode'] ?? 'unknown';

        $eligibilityStatus = match(strtolower($status)) {
            'active', '1' => 'eligible',
            'inactive', '2' => 'ineligible',
            'pending', '3' => 'pending',
            default => 'error'
        };

        return [
            'status' => $eligibilityStatus,
            'data' => $response,
            'coverage' => $response['planStatus'][0]['planDetails'] ?? null,
            'benefits' => $response['benefitsInformation'] ?? null,
            'deductibles' => $response['deductibleInformation'] ?? null,
        ];
    }
}
