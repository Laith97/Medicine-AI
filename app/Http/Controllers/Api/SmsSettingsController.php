<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use App\Models\Hospital;
use App\Services\SmsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SmsSettingsController extends Controller
{
    protected SmsService $smsService;
    protected array $validProviders;

    public function __construct(SmsService $smsService)
    {
        $this->smsService = $smsService;
        $this->validProviders = array_keys(config('sms.available_providers', []));
    }

    /**
     * Get doctor's SMS settings
     * GET /api/doctor/sms-settings
     */
    public function getDoctorSettings(Request $request): JsonResponse
    {
        $user = $request->user();
        $doctor = $user->doctor;

        if (!$doctor) {
            return response()->json([
                'success' => false,
                'message' => 'Doctor profile not found',
            ], 404);
        }

        $currentSetting = $doctor->sms_provider;
        $effectiveProvider = $this->getEffectiveProvider($doctor, null);
        $availableProviders = $this->getAvailableProviders();

        return response()->json([
            'success' => true,
            'data' => [
                'current_setting' => $currentSetting,
                'effective_provider' => $effectiveProvider,
                'available_providers' => $availableProviders,
                'is_inherited' => $currentSetting === null,
                'hospital_id' => null,
                'hospital_name' => null,
            ],
        ]);
    }

    /**
     * Update doctor's SMS settings
     * PUT /api/doctor/sms-settings
     */
    public function updateDoctorSettings(Request $request): JsonResponse
    {
        $user = $request->user();
        $doctor = $user->doctor;

        if (!$doctor) {
            return response()->json([
                'success' => false,
                'message' => 'Doctor profile not found',
            ], 404);
        }

        $request->validate([
            'sms_provider' => [
                'nullable',
                'string',
                function ($attribute, $value, $fail) {
                    if ($value !== null && !in_array($value, $this->validProviders)) {
                        $fail('The selected SMS provider is invalid. Available providers: ' . implode(', ', $this->validProviders));
                    }
                },
            ],
        ]);

        $oldProvider = $doctor->sms_provider;
        $newProvider = $request->input('sms_provider');

        $doctor->sms_provider = $newProvider;
        $doctor->save();

        // Log the configuration change
        $this->smsService->logConfigurationChange('doctor_provider_changed', [
            'doctor_id' => $doctor->id,
            'old_provider' => $oldProvider,
            'new_provider' => $newProvider,
            'user_id' => $user->id,
        ]);

        $effectiveProvider = $this->getEffectiveProvider($doctor, null);
        $availableProviders = $this->getAvailableProviders();

        return response()->json([
            'success' => true,
            'message' => $newProvider === null 
                ? 'SMS provider reset to inherit from hospital/system settings' 
                : 'SMS settings updated successfully',
            'data' => [
                'current_setting' => $newProvider,
                'effective_provider' => $effectiveProvider,
                'available_providers' => $availableProviders,
                'is_inherited' => $newProvider === null,
                'hospital_id' => null,
                'hospital_name' => null,
            ],
        ]);
    }

    /**
     * Get hospital's SMS settings
     * GET /api/hospital/{id}/sms-settings
     */
    public function getHospitalSettings(Request $request, int $hospitalId): JsonResponse
    {
        $hospital = Hospital::find($hospitalId);

        if (!$hospital) {
            return response()->json([
                'success' => false,
                'message' => 'Hospital not found',
            ], 404);
        }

        $currentSetting = $hospital->sms_provider;
        $effectiveProvider = $currentSetting ?? config('sms.default_provider', 'log');
        $availableProviders = $this->getAvailableProviders();

        return response()->json([
            'success' => true,
            'data' => [
                'current_setting' => $currentSetting,
                'effective_provider' => $effectiveProvider,
                'available_providers' => $availableProviders,
                'is_inherited' => $currentSetting === null,
                'hospital_id' => $hospital->id,
                'hospital_name' => $hospital->name,
            ],
        ]);
    }

    /**
     * Update hospital's SMS settings
     * PUT /api/hospital/{id}/sms-settings
     */
    public function updateHospitalSettings(Request $request, int $hospitalId): JsonResponse
    {
        $hospital = Hospital::find($hospitalId);

        if (!$hospital) {
            return response()->json([
                'success' => false,
                'message' => 'Hospital not found',
            ], 404);
        }

        $request->validate([
            'sms_provider' => [
                'nullable',
                'string',
                function ($attribute, $value, $fail) {
                    if ($value !== null && !in_array($value, $this->validProviders)) {
                        $fail('The selected SMS provider is invalid. Available providers: ' . implode(', ', $this->validProviders));
                    }
                },
            ],
        ]);

        $oldProvider = $hospital->sms_provider;
        $newProvider = $request->input('sms_provider');

        $hospital->sms_provider = $newProvider;
        $hospital->save();

        // Log the configuration change
        $this->smsService->logConfigurationChange('hospital_provider_changed', [
            'hospital_id' => $hospital->id,
            'old_provider' => $oldProvider,
            'new_provider' => $newProvider,
            'user_id' => $request->user()->id,
        ]);

        $effectiveProvider = $newProvider ?? config('sms.default_provider', 'log');
        $availableProviders = $this->getAvailableProviders();

        return response()->json([
            'success' => true,
            'message' => $newProvider === null 
                ? 'SMS provider reset to inherit from system settings' 
                : 'SMS settings updated successfully',
            'data' => [
                'current_setting' => $newProvider,
                'effective_provider' => $effectiveProvider,
                'available_providers' => $availableProviders,
                'is_inherited' => $newProvider === null,
                'hospital_id' => $hospital->id,
                'hospital_name' => $hospital->name,
            ],
        ]);
    }

    /**
     * Get the effective provider for a doctor considering hierarchy
     */
    protected function getEffectiveProvider(Doctor $doctor, ?int $hospitalId): string
    {
        // Doctor level override
        if ($doctor->sms_provider) {
            return $doctor->sms_provider;
        }

        // Hospital level override
        if ($hospitalId) {
            $hospital = Hospital::find($hospitalId);
            if ($hospital && $hospital->sms_provider) {
                return $hospital->sms_provider;
            }
        }

        // System level
        return $this->smsService->getSystemProvider();
    }

    /**
     * Get available providers with their display names
     */
    protected function getAvailableProviders(): array
    {
        return config('sms.available_providers', []);
    }
}