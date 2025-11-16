<?php

namespace App\Http\Controllers;

use App\Models\UserSmsConfiguration;
use App\Models\User;
use App\Models\Hospital;
use App\Services\SmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UserSmsConfigurationController extends Controller
{
    protected $smsService;

    public function __construct(SmsService $smsService)
    {
        $this->smsService = $smsService;

        $this->middleware(function ($request, $next) {
            $user = Auth::user();

            if (!$user || !($user->isDoctor() || $user->isHospitalAdmin())) {
                abort(403, 'Unauthorized to access SMS configuration');
            }

            return $next($request);
        });
    }

    /**
     * Display the SMS configuration form for the authenticated user
     */
    public function index()
    {
        $user = Auth::user();

        // Get all available providers
        $availableProviders = config('sms.available_providers', []);

        // Get user's current configurations
        $userConfigurations = UserSmsConfiguration::where('user_id', $user->id)->get();
        $userConfigMap = $userConfigurations->keyBy('provider_key');

        // Get hospital configurations if applicable
        $hospitalConfigurations = collect();
        if ($user->hospital_id) {
            $hospitalConfigurations = UserSmsConfiguration::where('hospital_id', $user->hospital_id)->get();
        }
        $hospitalConfigMap = $hospitalConfigurations->keyBy('provider_key');

        return view('sms.config.index', compact(
            'availableProviders',
            'userConfigMap',
            'hospitalConfigMap',
            'user'
        ));
    }

    /**
     * Save the user SMS configuration
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'provider_key' => [
                'required',
                'string',
                Rule::in(array_keys(config('sms.available_providers', [])))
            ],
            'is_active' => 'boolean',
            'use_admin_config' => 'boolean',
            'provider_config' => 'array',
        ]);

        $data = $request->only(['provider_key', 'is_active', 'use_admin_config']);
        $data['is_active'] = $request->has('is_active') ? (bool)$request->is_active : false;
        $data['use_admin_config'] = $request->has('use_admin_config') ? (bool)$request->use_admin_config : false;

        // Handle provider-specific configuration
        $providerConfig = $this->sanitizeProviderConfig($request->provider_config, $request->provider_key);
        $data['provider_config'] = $providerConfig;

        // Check if this user already has a configuration for this provider
        $existingConfig = UserSmsConfiguration::where('user_id', $user->id)
            ->where('provider_key', $request->provider_key)
            ->first();

        if ($existingConfig) {
            $existingConfig->update($data);
        } else {
            $data['user_id'] = $user->id;
            UserSmsConfiguration::create($data);
        }

        return redirect()->route('sms.config.index')->with('success', 'SMS configuration saved successfully.');
    }

    /**
     * Save the hospital SMS configuration (for hospital admins)
     */
    public function storeHospital(Request $request)
    {
        $user = Auth::user();

        if (!$user->isHospitalAdmin()) {
            abort(403, 'Unauthorized to configure hospital SMS');
        }

        if (!$user->hospital_id) {
            abort(403, 'No hospital assigned to this admin');
        }

        $request->validate([
            'provider_key' => [
                'required',
                'string',
                Rule::in(array_keys(config('sms.available_providers', [])))
            ],
            'is_active' => 'boolean',
            'use_admin_config' => 'boolean',
            'provider_config' => 'array',
        ]);

        $data = $request->only(['provider_key', 'is_active', 'use_admin_config']);
        $data['is_active'] = $request->has('is_active') ? (bool)$request->is_active : false;
        $data['use_admin_config'] = $request->has('use_admin_config') ? (bool)$request->use_admin_config : false;

        // Handle provider-specific configuration
        $providerConfig = $this->sanitizeProviderConfig($request->provider_config, $request->provider_key);
        $data['provider_config'] = $providerConfig;

        // Check if this hospital already has a configuration for this provider
        $existingConfig = UserSmsConfiguration::where('hospital_id', $user->hospital_id)
            ->where('provider_key', $request->provider_key)
            ->first();

        if ($existingConfig) {
            $existingConfig->update($data);
        } else {
            $data['hospital_id'] = $user->hospital_id;
            UserSmsConfiguration::create($data);
        }

        return redirect()->route('sms.config.index')->with('success', 'Hospital SMS configuration saved successfully.');
    }

    /**
     * Delete a user SMS configuration
     */
    public function destroy($id)
    {
        $config = UserSmsConfiguration::findOrFail($id);
        $user = Auth::user();

        // Check if user can delete this configuration
        if ($config->user_id !== $user->id &&
            ($config->hospital_id !== $user->hospital_id || !$user->isHospitalAdmin())) {
            abort(403, 'Unauthorized to delete this configuration');
        }

        $config->delete();

        return redirect()->route('sms.config.index')->with('success', 'SMS configuration deleted successfully.');
    }

    /**
     * Test the SMS configuration
     */
    public function testSms(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'provider_key' => [
                'required',
                'string',
                Rule::in(array_keys(config('sms.available_providers', [])))
            ],
            'test_phone' => 'required|string|max:20',
        ]);

        // Create temporary SMS service without provider to allow dynamic provider configuration
        $smsService = new \App\Services\SmsService();

        // Use send method directly which will determine the appropriate configuration based on user
        $result = $smsService->sendTestSms($request->test_phone, $user);

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'message' => 'Test SMS sent successfully!',
                'provider' => $smsService->getActiveProviderForUser($user)
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send test SMS: ' . ($result['message'] ?? 'Unknown error')
            ], 422);
        }
    }

    /**
     * Sanitize provider configuration to prevent saving sensitive data in the wrong format
     */
    private function sanitizeProviderConfig(?array $config, string $providerKey): ?array
    {
        if (!$config) {
            return null;
        }

        // Define required fields for each provider
        $requiredFields = [
            'twilio' => ['account_sid', 'auth_token', 'from_number'],
            'plivo' => ['auth_id', 'auth_token', 'from_number'],
            'messagebird' => ['access_key', 'from_number'],
            'unifonic' => ['app_sid', 'sender_id'],
            'smsgatewayhub' => ['email', 'password', 'device'],
        ];

        if (!isset($requiredFields[$providerKey])) {
            return $config; // Unknown provider, return as is
        }

        // Only keep fields that are relevant to this provider
        $allowedFields = $requiredFields[$providerKey];
        $sanitizedConfig = [];

        foreach ($allowedFields as $field) {
            if (isset($config[$field])) {
                $sanitizedConfig[$field] = $config[$field];
            }
        }

        return $sanitizedConfig;
    }
}