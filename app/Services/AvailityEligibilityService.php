<?php

namespace App\Services;

use App\Models\PatientInsurance;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class AvailityEligibilityService extends EligibilityService
{
    protected string $baseUrl = 'https://api.availity.com/v1';
    protected string $providerName = 'Availity';

    /**
     * Check if this service supports the given insurance provider
     *
     * @param \App\Models\InsuranceProvider $provider
     * @return bool
     */
    public function supportsProvider(\App\Models\InsuranceProvider $provider): bool
    {
        // Check if the provider name contains 'availity' or is configured to use Availity
        return stripos($provider->name, 'availity') !== false ||
               stripos($provider->api_endpoint ?? '', 'availity') !== false;
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
     * Perform the actual eligibility check with Availity API
     *
     * @param PatientInsurance $patientInsurance
     * @param string $serviceType
     * @return array
     */
    protected function performEligibilityCheck(PatientInsurance $patientInsurance, string $serviceType): array
    {
        $provider = $patientInsurance->insuranceProvider;

        if (!$provider || !$provider->api_endpoint || !$provider->api_key) {
            throw new Exception('Insurance provider not properly configured for Availity integration');
        }

        // Prepare the request payload
        $payload = $this->buildEligibilityRequestPayload($patientInsurance, $serviceType);

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $provider->api_key,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->timeout(30) // 30 second timeout
            ->post($provider->api_endpoint . '/eligibility/check', $payload);

            if ($response->successful()) {
                return $this->parseEligibilityResponse($response->json());
            } else {
                Log::error('Availity API error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'patient_insurance_id' => $patientInsurance->id,
                ]);

                throw new Exception('Availity API returned error: ' . $response->status() . ' - ' . $response->body());
            }

        } catch (Exception $e) {
            Log::error('Availity API request failed', [
                'error' => $e->getMessage(),
                'patient_insurance_id' => $patientInsurance->id,
            ]);

            throw $e;
        }
    }

    /**
     * Build the eligibility request payload for Availity
     *
     * @param PatientInsurance $patientInsurance
     * @param string $serviceType
     * @return array
     */
    protected function buildEligibilityRequestPayload(PatientInsurance $patientInsurance, string $serviceType): array
    {
        $patient = $patientInsurance->patient;

        return [
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
                'npi' => config('services.availity.npi'),
                'taxonomy' => $serviceType,
            ],
            'service' => [
                'type' => $serviceType,
                'dateOfService' => now()->format('Y-m-d'),
            ],
        ];
    }

    /**
     * Parse the eligibility response from Availity
     *
     * @param array $response
     * @return array
     */
    protected function parseEligibilityResponse(array $response): array
    {
        // This is a simplified parsing - in reality, Availity has complex response structures
        $status = $response['eligibility']['status'] ?? 'unknown';

        $eligibilityStatus = match($status) {
            'active', 'eligible' => 'eligible',
            'inactive', 'ineligible' => 'ineligible',
            'pending' => 'pending',
            default => 'error'
        };

        return [
            'status' => $eligibilityStatus,
            'data' => $response,
            'coverage' => $response['coverage'] ?? null,
            'benefits' => $response['benefits'] ?? null,
        ];
    }
}
