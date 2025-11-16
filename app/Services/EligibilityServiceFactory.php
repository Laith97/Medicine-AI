<?php

namespace App\Services;

use App\Contracts\EligibilityServiceInterface;
use App\Models\InsuranceProvider;
use InvalidArgumentException;

class EligibilityServiceFactory
{
    /**
     * Available eligibility services
     *
     * @var array<string, class-string<EligibilityServiceInterface>>
     */
    protected array $services = [
        'availity' => AvailityEligibilityService::class,
        // Add more services here as they are implemented
        'change_healthcare' => ChangeHealthcareEligibilityService::class,
        'waystar' => WaystarEligibilityService::class,
    ];

    /**
     * Get the appropriate eligibility service for a provider
     *
     * @param InsuranceProvider $provider
     * @return EligibilityServiceInterface
     */
    public function getServiceForProvider(InsuranceProvider $provider): EligibilityServiceInterface
    {
        foreach ($this->services as $key => $serviceClass) {
            $service = app($serviceClass);
            if ($service->supportsProvider($provider)) {
                return $service;
            }
        }

        throw new InvalidArgumentException("No eligibility service found for provider: {$provider->name}");
    }

    /**
     * Get all available services
     *
     * @return array<string, EligibilityServiceInterface>
     */
    public function getAllServices(): array
    {
        $services = [];
        foreach ($this->services as $key => $serviceClass) {
            $services[$key] = app($serviceClass);
        }
        return $services;
    }

    /**
     * Register a new eligibility service
     *
     * @param string $key
     * @param class-string<EligibilityServiceInterface> $serviceClass
     * @return void
     */
    public function registerService(string $key, string $serviceClass): void
    {
        $this->services[$key] = $serviceClass;
    }
}
