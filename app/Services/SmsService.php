<?php

namespace App\Services;

use App\Contracts\SmsProviderInterface;
use App\Services\SmsProviders\TwilioProvider;
use App\Services\SmsProviders\PlivoProvider;
use App\Services\SmsProviders\MessageBirdProvider;
use App\Services\SmsProviders\UnifonicProvider;
use App\Services\SmsProviders\SmsGatewayHubProvider;
use App\Models\SystemSetting;
use App\Models\SmsProviderCountry;
use App\Models\Doctor;
use App\Models\Hospital;
use Illuminate\Support\Facades\Log;

class SmsService
{
    protected $provider;
    protected $providerInstance;

    public function __construct($providerInstance = null)
    {
        if ($providerInstance) {
            $this->providerInstance = $providerInstance;
            $this->provider = 'mock';
        } else {
            $this->provider = $this->getSystemProvider();
            $this->providerInstance = $this->createProviderInstance($this->provider);
        }
    }

    /**
     * Send SMS message with hierarchical provider routing
     *
     * @param string $to
     * @param string $message
     * @param array $options Optional: ['doctor_id', 'hospital_id', 'context', 'context_id']
     * @return array ['success' => bool, 'message' => string, 'data' => array]
     */
    public function send(string $to, string $message, array $options = []): array
    {
        try {
            // Determine provider using hierarchy
            $providerKey = $this->determineProvider($to, $options);

            // Create provider instance
            $providerInstance = $this->createProviderInstance($providerKey);

            if (!$providerInstance) {
                return [
                    'success' => false,
                    'message' => 'No SMS provider available for this destination',
                    'data' => []
                ];
            }

            $result = $providerInstance->send($to, $message);

            // Log the send
            $this->logSend($to, $message, $providerKey, $result['success'], $options);

            if ($result['success']) {
                return $result;
            }

            // If primary provider failed, try fallback
            return $this->fallbackSend($to, $message, $options, $providerKey);

        } catch (\Exception $e) {
            Log::error('SMS sending failed: ' . $e->getMessage(), [
                'to' => $to,
                'message' => $message,
                'provider' => $providerKey ?? 'unknown',
                'options' => $options
            ]);

            return [
                'success' => false,
                'message' => 'SMS service error: ' . $e->getMessage(),
                'data' => []
            ];
        }
    }

    /**
     * Determine provider using hierarchical overrides: doctor > hospital > system/country-based
     *
     * @param string $to
     * @param array $options
     * @return string
     */
    protected function determineProvider(string $to, array $options): string
    {
        // Doctor level override
        if (isset($options['doctor_id'])) {
            $provider = $this->getDoctorProvider($options['doctor_id']);
            if ($provider) {
                return $provider;
            }
        }

        // Hospital level override
        if (isset($options['hospital_id'])) {
            $provider = $this->getHospitalProvider($options['hospital_id']);
            if ($provider) {
                return $provider;
            }
        }

        // System level with country-based routing
        return $this->getSystemProviderForCountry($to);
    }

    /**
     * Get doctor's SMS provider
     *
     * @param int $doctorId
     * @return string|null
     */
    protected function getDoctorProvider(int $doctorId): ?string
    {
        try {
            $doctor = Doctor::find($doctorId);
            return $doctor && $doctor->sms_provider ? $doctor->sms_provider : null;
        } catch (\Exception $e) {
            Log::warning('Failed to get doctor SMS provider', [
                'doctor_id' => $doctorId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Get hospital's SMS provider
     *
     * @param int $hospitalId
     * @return string|null
     */
    protected function getHospitalProvider(int $hospitalId): ?string
    {
        try {
            $hospital = Hospital::find($hospitalId);
            return $hospital && $hospital->sms_provider ? $hospital->sms_provider : null;
        } catch (\Exception $e) {
            Log::warning('Failed to get hospital SMS provider', [
                'hospital_id' => $hospitalId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Get system provider with country-based routing
     *
     * @param string $to
     * @return string
     */
    protected function getSystemProviderForCountry(string $to): string
    {
        // Extract country code from phone number
        $countryCode = $this->extractCountryCode($to);

        // Get provider for this country
        $providerKey = null;
        if ($countryCode) {
            $providerKey = SmsProviderCountry::getProviderForCountry($countryCode);
        }

        // If no country-specific provider found, use fallback provider
        if (!$providerKey) {
            $providerKey = $this->getFallbackProvider();
        }

        return $providerKey ?: $this->getSystemProvider();
    }

    /**
     * Handle fallback sending when primary provider fails
     *
     * @param string $to
     * @param string $message
     * @param array $options
     * @param string $failedProvider
     * @return array
     */
    protected function fallbackSend(string $to, string $message, array $options, string $failedProvider): array
    {
        $fallbackProviders = $this->getFallbackProviders($options, $failedProvider, $to);

        foreach ($fallbackProviders as $providerKey) {
            try {
                $providerInstance = $this->createProviderInstance($providerKey);
                if ($providerInstance) {
                    $result = $providerInstance->send($to, $message);

                    // Log the fallback attempt
                    $this->logSend($to, $message, $providerKey, $result['success'], array_merge($options, [
                        'fallback_from' => $failedProvider,
                        'is_fallback' => true
                    ]));

                    if ($result['success']) {
                        return $result;
                    }
                }
            } catch (\Exception $e) {
                Log::warning('Fallback SMS provider failed', [
                    'to' => $to,
                    'provider' => $providerKey,
                    'fallback_from' => $failedProvider,
                    'error' => $e->getMessage()
                ]);
            }
        }

        // All providers failed
        return [
            'success' => false,
            'message' => 'All SMS providers failed, including fallbacks',
            'data' => ['fallback_attempted' => true]
        ];
    }

    /**
     * Get provider hierarchy for given options
     *
     * @param array $options
     * @param string $to
     * @return array
     */
    protected function getProviderHierarchy(array $options, string $to): array
    {
        $providers = [];

        // Doctor level
        if (isset($options['doctor_id'])) {
            $provider = $this->getDoctorProvider($options['doctor_id']);
            if ($provider) {
                $providers[] = $provider;
            }
        }

        // Hospital level
        if (isset($options['hospital_id'])) {
            $provider = $this->getHospitalProvider($options['hospital_id']);
            if ($provider) {
                $providers[] = $provider;
            }
        }

        // System level
        $systemProvider = $this->getSystemProviderForCountry($to);
        if ($systemProvider) {
            $providers[] = $systemProvider;
        }

        return array_unique($providers);
    }

    /**
     * Get fallback providers in hierarchy order
     *
     * @param array $options
     * @param string $failedProvider
     * @param string $to
     * @return array
     */
    protected function getFallbackProviders(array $options, string $failedProvider, string $to): array
    {
        $hierarchy = $this->getProviderHierarchy($options, $to);
        $failedIndex = array_search($failedProvider, $hierarchy);

        if ($failedIndex !== false) {
            return array_slice($hierarchy, $failedIndex + 1);
        }

        return [];
    }

    /**
     * Log SMS send attempt
     *
     * @param string $to
     * @param string $message
     * @param string $provider
     * @param bool $success
     * @param array $options
     */
    protected function logSend(string $to, string $message, string $provider, bool $success, array $options = []): void
    {
        Log::info('SMS send attempt', [
            'to' => $to,
            'provider' => $provider,
            'success' => $success,
            'context' => $options['context'] ?? null,
            'context_id' => $options['context_id'] ?? null,
            'doctor_id' => $options['doctor_id'] ?? null,
            'hospital_id' => $options['hospital_id'] ?? null,
            'is_fallback' => $options['is_fallback'] ?? false,
            'fallback_from' => $options['fallback_from'] ?? null,
            'timestamp' => now()->toISOString()
        ]);
    }

    /**
     * Log configuration changes
     *
     * @param string $changeType
     * @param array $details
     */
    public function logConfigurationChange(string $changeType, array $details): void
    {
        Log::info('SMS configuration change', array_merge([
            'change_type' => $changeType,
            'timestamp' => now()->toISOString()
        ], $details));
    }

    /**
     * Send SMS message (legacy method for backward compatibility)
     *
     * @param string $to
     * @param string $message
     * @return bool
     */
    public function sendLegacy(string $to, string $message): bool
    {
        $result = $this->send($to, $message);
        return $result['success'];
    }

    /**
     * Send SMS message
     *
     * @param string $to
     * @param string $message
     * @return array
     */
    public function sendSms(string $to, string $message): array
    {
        return $this->providerInstance->send($to, $message);
    }

    /**
     * Send test SMS
     *
     * @param string $to
     * @return array
     */
    public function sendTestSms(string $to): array
    {
        $message = "Test SMS from MedcuraAI. Provider: {$this->getProviderName()}. Time: " . now()->format('Y-m-d H:i:s');
        return $this->send($to, $message);
    }

    /**
     * Get system provider name
     *
     * @return string
     */
    protected function getSystemProvider(): string
    {
        // First check system settings (database)
        $provider = SystemSetting::get('sms_provider');

        if ($provider && $this->isValidProvider($provider)) {
            return $provider;
        }

        // Fallback to config/env
        $provider = config('sms.default_provider', 'log');

        return $this->isValidProvider($provider) ? $provider : 'log';
    }

    /**
     * Get system provider name (public method)
     *
     * @return string
     */
    public function getSystemProviderPublic(): string
    {
        return $this->getSystemProvider();
    }

    /**
     * Check if provider is valid
     *
     * @param string $provider
     * @return bool
     */
    protected function isValidProvider(string $provider): bool
    {
        return in_array($provider, ['twilio', 'plivo', 'messagebird', 'unifonic', 'smsgatewayhub', 'log']);
    }

    /**
     * Create provider instance
     *
     * @param string $provider
     * @return SmsProviderInterface|null
     */
    protected function createProviderInstance(string $provider): ?SmsProviderInterface
    {
        try {
            switch ($provider) {
                case 'twilio':
                    return new TwilioProvider();
                case 'plivo':
                    return new PlivoProvider();
                case 'messagebird':
                    return new MessageBirdProvider();
                case 'unifonic':
                    return new UnifonicProvider();
                case 'smsgatewayhub':
                    return new SmsGatewayHubProvider();
                case 'log':
                return new class implements SmsProviderInterface {
                    public function send(string $to, string $message): array
                    {
                        Log::info('SMS would be sent', [
                            'to' => $to,
                            'message' => $message,
                            'provider' => 'log'
                        ]);
                        return [
                            'success' => true,
                            'message' => 'SMS logged successfully',
                            'data' => ['logged_at' => now()->toISOString()]
                        ];
                    }

                    public function getName(): string
                    {
                        return 'Log Only';
                    }

                    public function isConfigured(): bool
                    {
                        return true;
                    }

                    public function getConfigRequirements(): array
                    {
                        return [];
                    }

                    public function getKey(): string
                    {
                        return 'log';
                    }

                    public function getMessageStatus(string $messageId): array
                    {
                        return ['status' => 'unknown', 'message_id' => $messageId];
                    }

                    public function sendBulkSms(array $recipients, string $message): array
                    {
                        foreach ($recipients as $to) {
                            $this->send($to, $message);
                        }
                        return ['success' => true, 'sent' => count($recipients)];
                    }

                    public function getDeliveryReport(string $messageId): array
                    {
                        return ['message_id' => $messageId, 'status' => 'delivered'];
                    }
                };
                default:
                    return null;
            }
        } catch (\Exception $e) {
            Log::error('Failed to create SMS provider instance', [
                'provider' => $provider,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Get current provider name
     *
     * @return string
     */
    public function getProviderName(): string
    {
        return $this->providerInstance ? $this->providerInstance->getName() : 'Unknown';
    }

    /**
     * Check if current provider is configured
     *
     * @return bool
     */
    public function isProviderConfigured(): bool
    {
        return $this->providerInstance ? $this->providerInstance->isConfigured() : false;
    }

    /**
     * Get all available providers with their status
     *
     * @return array
     */
    public function getAvailableProviders(): array
    {
        $providers = [];
        $availableProviders = config('sms.available_providers', []);

        foreach ($availableProviders as $key => $name) {
            try {
                $instance = $this->createProviderInstance($key);
                $providers[$key] = [
                    'name' => $name,
                    'configured' => $instance ? $instance->isConfigured() : false,
                    'requirements' => $instance && method_exists($instance, 'getRequiredConfig') ? $instance->getRequiredConfig() : [],
                    'active' => $key === $this->provider
                ];
            } catch (\Exception $e) {
                // If provider class has issues, mark as not configured
                $providers[$key] = [
                    'name' => $name,
                    'configured' => false,
                    'requirements' => [],
                    'active' => false,
                    'error' => $e->getMessage()
                ];
            }
        }

        return $providers;
    }

    /**
     * Set active provider
     *
     * @param string $provider
     * @return bool
     */
    public function setActiveProvider(string $provider): bool
    {
        if (!$this->isValidProvider($provider)) {
            return false;
        }

        $oldProvider = $this->provider;

        SystemSetting::set(
            'sms_provider',
            $provider,
            'string',
            'Active SMS provider for the system'
        );

        // Update current instance
        $this->provider = $provider;
        $this->providerInstance = $this->createProviderInstance($provider);

        // Log configuration change
        $this->logConfigurationChange('system_provider_changed', [
            'old_provider' => $oldProvider,
            'new_provider' => $provider
        ]);

        return true;
    }

    /**
     * Extract country code from phone number
     *
     * @param string $phoneNumber
     * @return string|null
     */
    protected function extractCountryCode(string $phoneNumber): ?string
    {
        // Remove all non-numeric characters
        $cleanNumber = preg_replace('/[^0-9]/', '', $phoneNumber);

        // Country code mapping for common patterns
        $countryCodeMap = [
            // Jordan
            '962' => 'JO',
            // Saudi Arabia
            '966' => 'SA',
            // UAE
            '971' => 'AE',
            // Kuwait
            '965' => 'KW',
            // Qatar
            '974' => 'QA',
            // Bahrain
            '973' => 'BH',
            // Oman
            '968' => 'OM',
            // Lebanon
            '961' => 'LB',
            // Syria
            '963' => 'SY',
            // Iraq
            '964' => 'IQ',
            // Egypt
            '20' => 'EG',
            // United States/Canada
            '1' => 'US',
            // United Kingdom
            '44' => 'GB',
            // Germany
            '49' => 'DE',
            // France
            '33' => 'FR',
            // Italy
            '39' => 'IT',
            // Spain
            '34' => 'ES',
            // Turkey
            '90' => 'TR',
            // India
            '91' => 'IN',
            // Pakistan
            '92' => 'PK',
            // Bangladesh
            '880' => 'BD',
            // China
            '86' => 'CN',
            // Japan
            '81' => 'JP',
            // South Korea
            '82' => 'KR',
            // Australia
            '61' => 'AU',
            // Brazil
            '55' => 'BR',
            // Mexico
            '52' => 'MX',
            // Russia
            '7' => 'RU',
        ];

        // Try to match country codes (longest first)
        $sortedCodes = array_keys($countryCodeMap);
        usort($sortedCodes, function($a, $b) {
            return strlen($b) - strlen($a);
        });

        foreach ($sortedCodes as $code) {
            if (str_starts_with($cleanNumber, $code)) {
                return $countryCodeMap[$code];
            }
        }

        return null;
    }

    /**
     * Get fallback provider for countries without specific assignments
     *
     * @return string|null
     */
    protected function getFallbackProvider(): ?string
    {
        // Get all configured providers
        $availableProviders = $this->getAvailableProviders();

        // Get providers that have country assignments
        $assignedProviders = array_keys(SmsProviderCountry::getActiveAssignments());

        // Find providers that are configured but don't have country assignments
        $fallbackProviders = [];
        foreach ($availableProviders as $key => $provider) {
            if ($provider['configured'] && !in_array($key, $assignedProviders)) {
                $fallbackProviders[] = $key;
            }
        }

        // Return the first available fallback provider
        if (!empty($fallbackProviders)) {
            return $fallbackProviders[0];
        }

        // If no fallback provider available, use the default from config
        return $this->getSystemProvider();
    }

    /**
     * Get all active providers with their country assignments
     *
     * @return array
     */
    public function getActiveProvidersWithCountries(): array
    {
        $assignments = SmsProviderCountry::getActiveAssignments();
        $availableProviders = $this->getAvailableProviders();

        $result = [];
        foreach ($availableProviders as $key => $provider) {
            if ($provider['configured']) {
                $result[$key] = [
                    'name' => $provider['name'],
                    'configured' => true,
                    'countries' => $assignments[$key] ?? [],
                    'is_fallback' => !isset($assignments[$key])
                ];
            }
        }

        return $result;
    }

    /**
     * Assign countries to a provider
     *
     * @param string $providerKey
     * @param array $countries
     * @return bool
     */
    public function assignCountriesToProvider(string $providerKey, array $countries): bool
    {
        try {
            if (!$this->isValidProvider($providerKey)) {
                return false;
            }

            SmsProviderCountry::assignCountriesToProvider($providerKey, $countries);
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to assign countries to provider', [
                'provider' => $providerKey,
                'countries' => $countries,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Remove country assignments for a provider
     *
     * @param string $providerKey
     * @return bool
     */
    public function removeProviderCountryAssignments(string $providerKey): bool
    {
        try {
            SmsProviderCountry::removeProviderAssignments($providerKey);
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to remove provider country assignments', [
                'provider' => $providerKey,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Send appointment reminder
     *
     * @param object $user
     * @param array $appointmentData
     * @return array
     */
    public function sendAppointmentReminder($user, array $appointmentData): array
    {
        $message = "Reminder: You have an appointment with {$appointmentData['doctor_name']} on {$appointmentData['appointment_date']} at {$appointmentData['appointment_time']} at {$appointmentData['clinic_name']}. Please arrive 15 minutes early.";
        return $this->sendSms($user->phone, $message);
    }

    /**
     * Send prescription notification
     *
     * @param object $user
     * @param array $prescriptionData
     * @return array
     */
    public function sendPrescriptionNotification($user, array $prescriptionData): array
    {
        $message = "Your prescription for {$prescriptionData['medication_name']} {$prescriptionData['dosage']} ({$prescriptionData['frequency']} for {$prescriptionData['duration']}) is ready for pickup at {$prescriptionData['pharmacy_name']}.";
        return $this->sendSms($user->phone, $message);
    }

    /**
     * Send test results notification
     *
     * @param object $user
     * @param array $testData
     * @return array
     */
    public function sendTestResultsNotification($user, array $testData): array
    {
        $message = "Your {$testData['test_name']} results are now available. Please contact {$testData['doctor_name']} or visit {$testData['portal_url']} to view your results.";
        return $this->sendSms($user->phone, $message);
    }

    /**
     * Send emergency alert
     *
     * @param object $user
     * @param array $alertData
     * @return array
     */
    public function sendEmergencyAlert($user, array $alertData): array
    {
        $message = "URGENT: {$alertData['message']} Doctor: {$alertData['doctor_phone']}";
        return $this->sendSms($user->phone, $message);
    }

    /**
     * Send medication reminder
     *
     * @param object $user
     * @param array $medicationData
     * @return array
     */
    public function sendMedicationReminder($user, array $medicationData): array
    {
        $message = "Medication Reminder: Time to take your {$medicationData['medication_name']} {$medicationData['dosage']}. Instructions: {$medicationData['instructions']}";
        return $this->sendSms($user->phone, $message);
    }

    /**
     * Send follow-up reminder
     *
     * @param object $user
     * @param array $followUpData
     * @return array
     */
    public function sendFollowUpReminder($user, array $followUpData): array
    {
        $message = "Follow-up Reminder: Please schedule your follow-up appointment with {$followUpData['doctor_name']} by {$followUpData['follow_up_date']} for: {$followUpData['reason']}. Call: {$followUpData['phone_number']}";
        return $this->sendSms($user->phone, $message);
    }

    /**
     * Validate phone number
     *
     * @param string $phoneNumber
     * @return bool
     */
    public function validatePhoneNumber(string $phoneNumber): bool
    {
        return preg_match('/^\+?[1-9]\d{6,14}$/', $phoneNumber);
    }

    /**
     * Format phone number
     *
     * @param string $phoneNumber
     * @return string
     */
    public function formatPhoneNumber(string $phoneNumber): string
    {
        $clean = preg_replace('/\D/', '', $phoneNumber);
        if (strlen($clean) == 10) {
            if (str_contains($phoneNumber, '(') || str_contains($phoneNumber, '-')) {
                return '+1' . $clean;
            }
            return '+' . $clean;
        }
        if (strlen($clean) == 11 && str_starts_with($clean, '1')) {
            return '+' . $clean;
        }
        return '+' . $clean;
    }

    /**
     * Get SMS status
     *
     * @param string $messageId
     * @return array
     */
    public function getSmsStatus(string $messageId): array
    {
        return $this->providerInstance->getMessageStatus($messageId);
    }

    /**
     * Send bulk SMS
     *
     * @param array $recipients
     * @param string $message
     * @return array
     */
    public function sendBulkSms(array $recipients, string $message): array
    {
        return $this->providerInstance->sendBulkSms($recipients, $message);
    }

    /**
     * Get delivery report
     *
     * @param string $messageId
     * @return array
     */
    public function getDeliveryReport(string $messageId): array
    {
        return $this->providerInstance->getDeliveryReport($messageId);
    }

    /**
     * Generate temporary password
     */
    public static function generateTempPassword(): string
    {
        $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $password = '';

        for ($i = 0; $i < 8; $i++) {
            $password .= $characters[rand(0, strlen($characters) - 1)];
        }

        return $password;
    }
}
