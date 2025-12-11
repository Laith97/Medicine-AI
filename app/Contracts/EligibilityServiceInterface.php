<?php

namespace App\Contracts;

use App\Models\PatientInsurance;

interface EligibilityServiceInterface
{
    /**
     * Check eligibility for a patient insurance
     *
     * @param PatientInsurance $patientInsurance
     * @param string $serviceType
     * @return array
     */
    public function checkEligibility(PatientInsurance $patientInsurance, string $serviceType): array;

    /**
     * Get the provider name
     *
     * @return string
     */
    public function getProviderName(): string;

    /**
     * Check if this service supports the given insurance provider
     *
     * @param \App\Models\InsuranceProvider $provider
     * @return bool
     */
    public function supportsProvider(\App\Models\InsuranceProvider $provider): bool;
}
