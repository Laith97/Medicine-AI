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
use App\Models\UserSmsConfiguration;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class SmsService
{
    protected $provider;
    protected $providerInstance;

    public function __construct(?string $providerKey = null)
    {
        $this->provider = $providerKey ?: $this->getActiveProvider();
        $this->providerInstance = $this->createProviderInstance($this->provider);
    }

    /**
     * Send SMS message with user-specific configuration priority, then country-based provider routing
     *
     * @param string $to
     * @param string $message
     * @param User|null $user Optional user to use their specific SMS configuration
     * @return array ['success' => bool, 'message' => string, 'data' => array]
     */
    public function send(string $to, string $message, ?User $user = null): array
    {
        try {
            // Extract country code from phone number
            $countryCode = $this->extractCountryCode($to);

            // First, determine the provider based on user configuration if user is provided
            $providerKey = null;
            $customConfig = null;

            if ($user) {
                // User-specific configuration has highest priority
                $providerKey = $this->getActiveProviderForUser($user);
                $customConfig = $this->getUserProviderConfig($user, $providerKey);
            } else {
                // If no user provided, use country-based routing or fallback
                if ($countryCode) {
                    $providerKey = SmsProviderCountry::getProviderForCountry($countryCode);
                }

                if (!$providerKey) {
                    $providerKey = $this->getFallbackProvider();
                }
            }

            // Create provider instance with custom config if available
            $providerInstance = $this->createProviderInstance($providerKey, $customConfig);

            if (!$providerInstance) {
                return [
                    'success' => false,
                    'message' => 'No SMS provider available for this destination',
                    'data' => []
                ];
            }

            $result = $providerInstance->send($to, $message);

            // Log the routing decision
            Log::info('SMS sent via user-specific or country-based routing', [
                'to' => $to,
                'country_code' => $countryCode,
                'provider_used' => $providerKey,
                'user_id' => $user ? $user->id : null,
                'success' => $result['success']
            ]);

            return $result;

        } catch (\Exception $e) {
            Log::error('SMS sending failed: ' . $e->getMessage(), [
                'to' => $to,
                'message' => $message,
                'provider' => $providerKey ?? 'unknown',
                'user_id' => $user ? $user->id : null
            ]);

            return [
                'success' => false,
                'message' => 'SMS service error: ' . $e->getMessage(),
                'data' => []
            ];
        }
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
     * Send test SMS
     *
     * @param string $to
     * @param User|null $user Optional user to use their specific SMS configuration
     * @return array
     */
    public function sendTestSms(string $to, ?User $user = null): array
    {
        $message = "Test SMS from MedcuraAI. Provider: {$this->getProviderName()}. Time: " . now()->format('Y-m-d H:i:s');
        return $this->send($to, $message, $user);
    }

    /**
     * Get active provider name
     *
     * @return string
     */
    protected function getActiveProvider(): string
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
     * Get active provider name for a specific user
     *
     * @param User|null $user
     * @return string
     */
    public function getActiveProviderForUser(?User $user): string
    {
        if (!$user) {
            // Use system default if no user
            return $this->getActiveProvider();
        }

        // Check if user is doctor or hospital admin with custom configuration
        if ($user->isDoctor() || $user->isHospitalAdmin()) {
            // Check if user has custom SMS configuration
            $userConfig = UserSmsConfiguration::where('user_id', $user->id)
                ->where('is_active', true)
                ->where('use_admin_config', false)
                ->first();

            if ($userConfig) {
                if ($this->isValidProvider($userConfig->provider_key)) {
                    return $userConfig->provider_key;
                }
            }

            // Check if user belongs to a hospital with custom configuration
            if ($user->hospital_id) {
                $hospitalConfig = UserSmsConfiguration::where('hospital_id', $user->hospital_id)
                    ->where('is_active', true)
                    ->where('use_admin_config', false)
                    ->first();

                if ($hospitalConfig) {
                    if ($this->isValidProvider($hospitalConfig->provider_key)) {
                        return $hospitalConfig->provider_key;
                    }
                }
            }

            // Check if user should use admin config specifically
            $userAdminConfig = UserSmsConfiguration::where('user_id', $user->id)
                ->where('use_admin_config', true)
                ->first();

            $hospitalAdminConfig = UserSmsConfiguration::where('hospital_id', $user->hospital_id)
                ->where('use_admin_config', true)
                ->first();

            if ($userAdminConfig || $hospitalAdminConfig) {
                // Use system default when user is configured to use admin config
                $provider = SystemSetting::get('sms_provider');
                if ($provider && $this->isValidProvider($provider)) {
                    return $provider;
                }
            }
        }

        // Fallback to system default
        $provider = config('sms.default_provider', 'log');
        return $this->isValidProvider($provider) ? $provider : 'log';
    }

    /**
     * Get user-specific provider configuration
     *
     * @param User|null $user
     * @param string $providerKey
     * @return array|null
     */
    protected function getUserProviderConfig(?User $user, string $providerKey): ?array
    {
        if (!$user) {
            return null;
        }

        // Check if user has custom configuration
        if ($user->isDoctor() || $user->isHospitalAdmin()) {
            // Check user-specific config first
            $userConfig = UserSmsConfiguration::where('user_id', $user->id)
                ->where('provider_key', $providerKey)
                ->where('is_active', true)
                ->first();

            if ($userConfig && !$userConfig->use_admin_config) {
                return $userConfig->provider_config;
            }

            // Check hospital-specific config if user belongs to a hospital
            if ($user->hospital_id) {
                $hospitalConfig = UserSmsConfiguration::where('hospital_id', $user->hospital_id)
                    ->where('provider_key', $providerKey)
                    ->where('is_active', true)
                    ->first();

                if ($hospitalConfig && !$hospitalConfig->use_admin_config) {
                    return $hospitalConfig->provider_config;
                }
            }

            // If user is configured to use admin config, return null to use default
            $userAdminConfig = UserSmsConfiguration::where('user_id', $user->id)
                ->where('provider_key', $providerKey)
                ->where('use_admin_config', true)
                ->first();

            $hospitalAdminConfig = UserSmsConfiguration::where('hospital_id', $user->hospital_id)
                ->where('provider_key', $providerKey)
                ->where('use_admin_config', true)
                ->first();

            if ($userAdminConfig || $hospitalAdminConfig) {
                return null; // Use admin/system config
            }
        }

        return null;
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
     * @param array|null $customConfig Custom configuration to override default provider settings
     * @return SmsProviderInterface|null
     */
    protected function createProviderInstance(string $provider, ?array $customConfig = null): ?SmsProviderInterface
    {
        try {
            // Create provider based on type but with potential custom config
            switch ($provider) {
                case 'twilio':
                    return new TwilioProvider($customConfig);
                case 'plivo':
                    return new PlivoProvider($customConfig);
                case 'messagebird':
                    return new MessageBirdProvider($customConfig);
                case 'unifonic':
                    return new UnifonicProvider($customConfig);
                case 'smsgatewayhub':
                    return new SmsGatewayHubProvider($customConfig);
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

        SystemSetting::set(
            'sms_provider',
            $provider,
            'string',
            'Active SMS provider for the system'
        );

        // Update current instance
        $this->provider = $provider;
        $this->providerInstance = $this->createProviderInstance($provider);

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
        return $this->getActiveProvider();
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
