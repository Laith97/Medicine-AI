<?php

namespace App\Http\Controllers\Doctor;

use App\Exceptions\Handlers\KioskExceptionHandler;
use App\Exceptions\KioskConfigurationException;
use App\Exceptions\KioskSecurityException;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\KioskBackupService;
use App\Services\KioskPerformanceMonitor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class KioskController extends Controller
{
    /**
     * Display the kiosk setup page.
     */
    public function setup()
    {
        $user = Auth::user();
        $effectiveDoctorUser = $user->getEffectiveDoctorUser();
        if (!$effectiveDoctorUser) {
            throw new \Exception('No effective doctor user found for this account.');
        }

        // Get the effective doctor profile to get the actual doctor ID
        $effectiveDoctor = $user->getEffectiveDoctor();
        if (!$effectiveDoctor) {
            throw new \Exception('No effective doctor profile found for this account.');
        }

        // Get or create kiosk configuration for this doctor
        $kioskConfig = DB::table('doctor_kiosk_configs')
            ->where('doctor_id', $effectiveDoctor->id)
            ->first();

        return view('doctor.kiosk.setup', compact('kioskConfig'));
    }

    /**
     * Store or update kiosk configuration.
     */
    public function storeSetup(Request $request)
    {
        try {
            // Sanitize input data
            $sanitizedData = $this->sanitizeKioskSetupData($request->all());

            // Comprehensive validation with custom rules
            $validator = Validator::make($sanitizedData, [
                'clinic_name' => [
                    'required',
                    'string',
                    'max:255',
                    'regex:/^[a-zA-Z0-9\s\-\.\'&]+$/',
                    function ($attribute, $value, $fail) {
                        if (strlen(trim($value)) < 2) {
                            $fail('Clinic name must be at least 2 characters long.');
                        }
                    }
                ],
                'clinic_address' => [
                    'required',
                    'string',
                    'max:500',
                    'min:10',
                    function ($attribute, $value, $fail) {
                        if (!preg_match('/\d+/', $value)) {
                            $fail('Clinic address must contain a street number.');
                        }
                    }
                ],
                'contact_phone' => [
                    'required',
                    'string',
                    'max:20',
                    'regex:/^[\+]?[1-9][\d]{0,15}$/',
                    function ($attribute, $value, $fail) {
                        $cleaned = preg_replace('/[^\d]/', '', $value);
                        if (strlen($cleaned) < 10 || strlen($cleaned) > 15) {
                            $fail('Phone number must be between 10 and 15 digits.');
                        }
                    }
                ],
                'kiosk_display_name' => [
                    'required',
                    'string',
                    'max:255',
                    'regex:/^[a-zA-Z0-9\s\-\.]+$/',
                    function ($attribute, $value, $fail) {
                        if (strlen(trim($value)) < 3) {
                            $fail('Kiosk display name must be at least 3 characters long.');
                        }
                    }
                ],
                'primary_color' => [
                    'nullable',
                    'string',
                    'regex:/^#[a-fA-F0-9]{6}$/',
                    function ($attribute, $value, $fail) {
                        if ($value && !preg_match('/^#[a-fA-F0-9]{6}$/', $value)) {
                            $fail('Primary color must be a valid hex color code (e.g., #FF0000).');
                        }
                    }
                ],
                'secondary_color' => [
                    'nullable',
                    'string',
                    'regex:/^#[a-fA-F0-9]{6}$/',
                    function ($attribute, $value, $fail) {
                        if ($value && !preg_match('/^#[a-fA-F0-9]{6}$/', $value)) {
                            $fail('Secondary color must be a valid hex color code (e.g., #FF0000).');
                        }
                    }
                ],
                'auto_approve_appointments' => 'boolean',
                'require_payment_upfront' => 'boolean',
                'voice_instructions_enabled' => 'boolean',
                'high_contrast_mode' => 'boolean',
            ], [
                'clinic_name.regex' => 'Clinic name can only contain letters, numbers, spaces, hyphens, periods, apostrophes, and ampersands.',
                'clinic_address.min' => 'Clinic address must be at least 10 characters long.',
                'contact_phone.regex' => 'Please enter a valid phone number.',
                'kiosk_display_name.regex' => 'Kiosk display name can only contain letters, numbers, spaces, hyphens, and periods.',
            ]);

            if ($validator->fails()) {
                Log::warning('Kiosk setup validation failed', [
                    'user_id' => Auth::id(),
                    'errors' => $validator->errors()->toArray(),
                    'ip' => $request->ip(),
                ]);

                return redirect()->back()
                    ->withErrors($validator)
                    ->withInput();
            }

            $user = Auth::user();

            // Check rate limiting for kiosk configuration updates
            $this->checkRateLimit($user->id, 'kiosk_setup', 5, 60); // 5 updates per hour

            // Generate a unique kiosk token for security
            $kioskToken = $this->generateSecureKioskToken();

            DB::beginTransaction();

            // Get the effective doctor user (handles both main users and sub-users)
            $effectiveDoctorUser = $user->getEffectiveDoctorUser();
            if (!$effectiveDoctorUser) {
                throw new \Exception('No effective doctor user found for this account.');
            }

            // Get the effective doctor profile to get the actual doctor ID
            $effectiveDoctor = $user->getEffectiveDoctor();
            if (!$effectiveDoctor) {
                throw new \Exception('No effective doctor profile found for this account.');
            }

            // Update or insert kiosk configuration
            $configData = [
                'clinic_name' => $sanitizedData['clinic_name'],
                'clinic_address' => $sanitizedData['clinic_address'],
                'contact_phone' => $this->formatPhoneNumber($sanitizedData['contact_phone']),
                'kiosk_display_name' => $sanitizedData['kiosk_display_name'],
                'primary_color' => $sanitizedData['primary_color'] ?: '#2563eb',
                'secondary_color' => $sanitizedData['secondary_color'] ?: '#6b7280',
                'auto_approve_appointments' => $request->boolean('auto_approve_appointments'),
                'require_payment_upfront' => $request->boolean('require_payment_upfront'),
                'voice_instructions_enabled' => $request->boolean('voice_instructions_enabled'),
                'high_contrast_mode' => $request->boolean('high_contrast_mode'),
                'kiosk_token' => $kioskToken,
                'updated_at' => now(),
                'created_at' => now(),
            ];

            DB::table('doctor_kiosk_configs')->updateOrInsert(
                ['doctor_id' => $effectiveDoctor->id],
                $configData
            );

            // Log the configuration change
            Log::info('Kiosk configuration updated', [
                'user_id' => $user->id,
                'doctor_id' => $effectiveDoctor->id,
                'clinic_name' => $sanitizedData['clinic_name'],
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            DB::commit();

            return redirect()->route('doctor.kiosk.setup')
                ->with('success', 'Kiosk configuration saved successfully!')
                ->with('kiosk_token', $kioskToken);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Kiosk setup failed', [
                'user_id' => Auth::id(),
                'doctor_id' => $effectiveDoctor->id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'ip' => $request->ip(),
            ]);

            return redirect()->back()
                ->with('error', 'Failed to save kiosk configuration. Please try again.')
                ->withInput();
        }
    }

    /**
     * Display kiosk management dashboard.
     */
    public function management()
    {
        $user = Auth::user();
        $effectiveDoctorUser = $user->getEffectiveDoctorUser();
        if (!$effectiveDoctorUser) {
            throw new \Exception('No effective doctor user found for this account.');
        }

        // Get the effective doctor profile to get the actual doctor ID
        $effectiveDoctor = $user->getEffectiveDoctor();
        if (!$effectiveDoctor) {
            throw new \Exception('No effective doctor profile found for this account.');
        }

        // Get kiosk configuration
        $kioskConfig = DB::table('doctor_kiosk_configs')
            ->where('doctor_id', $effectiveDoctor->id)
            ->first();

        if (!$kioskConfig) {
            return redirect()->route('doctor.kiosk.setup')
                ->with('warning', 'Please set up your kiosk configuration first.');
        }

        // Since kiosks aren't directly associated with doctors in the current schema,
        // we can only show information based on appointments created via kiosks for this doctor
        // Get appointments associated with this doctor that were created via kiosks
        $appointmentKioskIds = DB::table('appointments')
            ->where('doctor_id', $effectiveDoctor->id)
            ->whereNotNull('kiosk_id')
            ->pluck('kiosk_id')
            ->unique();

        // Get sessions for kiosks that created appointments for this doctor
        $recentSessions = DB::table('kiosk_sessions')
            ->whereIn('kiosk_id', $appointmentKioskIds)
            ->latest()
            ->limit(10)
            ->get();

        // Get kiosk usage statistics for this doctor's appointments
        $stats = [
            'total_sessions' => DB::table('kiosk_sessions')
                ->whereIn('kiosk_id', $appointmentKioskIds)
                ->count(),
            'today_sessions' => DB::table('kiosk_sessions')
                ->whereIn('kiosk_id', $appointmentKioskIds)
                ->whereDate('created_at', today())
                ->count(),
            'appointments_created' => DB::table('appointments')
                ->where('doctor_id', $effectiveDoctor->id)
                ->whereNotNull('kiosk_id')
                ->count(),
            'payments_processed' => DB::table('kiosk_payments')
                ->join('kiosk_sessions', 'kiosk_payments.kiosk_session_id', '=', 'kiosk_sessions.session_id')
                ->whereIn('kiosk_sessions.kiosk_id', $appointmentKioskIds)
                ->where('kiosk_payments.status', 'succeeded')
                ->count(),
        ];

        return view('doctor.kiosk.management', compact('kioskConfig', 'recentSessions', 'stats'));
    }

    /**
     * Get kiosk access URL for the doctor.
     */
    public function getAccessUrl()
    {
        try {
            $user = Auth::user();
            $effectiveDoctorUser = $user->getEffectiveDoctorUser();
            if (!$effectiveDoctorUser) {
                throw new \Exception('No effective doctor user found for this account.');
            }

            // Get the effective doctor profile to get the actual doctor ID
            $effectiveDoctor = $user->getEffectiveDoctor();
            if (!$effectiveDoctor) {
                throw new \Exception('No effective doctor profile found for this account.');
            }

            // Check rate limiting for URL generation
            $this->checkRateLimit($user->id, 'kiosk_url_generation', 10, 60); // 10 requests per hour

            $kioskConfig = DB::table('doctor_kiosk_configs')
                ->where('doctor_id', $effectiveDoctor->id)
                ->first();

            if (!$kioskConfig) {
                throw new KioskConfigurationException(
                    'Kiosk not configured. Please set up your kiosk first.',
                    [
                        'user_id' => $user->id,
                        'setup_url' => route('doctor.kiosk.setup')
                    ]
                );
            }

            // Validate kiosk token exists and is not empty
            if (empty($kioskConfig->kiosk_token)) {
                Log::error('Kiosk configuration missing token', [
                    'user_id' => $user->id,
                    'doctor_id' => $effectiveDoctor->id,
                    'config_id' => $kioskConfig->id ?? null,
                ]);

                return response()->json([
                    'success' => false,
                    'error' => 'Kiosk configuration is invalid. Please reconfigure your kiosk.'
                ], 500);
            }

            // Generate a secure URL with token
            $kioskUrl = route('kiosk.welcome') . '?token=' . urlencode($kioskConfig->kiosk_token) . '&doctor=' . $effectiveDoctor->id;

            // Log access URL generation
            Log::info('Kiosk access URL generated', [
                'user_id' => $user->id,
                'doctor_id' => $effectiveDoctor->id,
                'clinic_name' => $kioskConfig->clinic_name,
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            return response()->json([
                'success' => true,
                'kiosk_url' => $kioskUrl,
                'qr_code_url' => $this->generateQRCode($kioskUrl),
                'clinic_name' => $kioskConfig->clinic_name,
                'expires_in' => '24 hours', // Token validity period
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to generate kiosk access URL', [
                'user_id' => Auth::id(),
                'doctor_id' => $effectiveDoctor->id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'ip' => request()->ip(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to generate kiosk access URL. Please try again.'
            ], 500);
        }
    }

    /**
     * Generate QR code URL for kiosk access.
     */
    private function generateQRCode($url)
    {
        // This would integrate with a QR code service
        // For now, return a placeholder
        return "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=" . urlencode($url);
    }

    /**
     * Deactivate kiosk.
     */
    public function deactivate()
    {
        try {
            $user = Auth::user();
            $effectiveDoctorUser = $user->getEffectiveDoctorUser();
            if (!$effectiveDoctorUser) {
                throw new \Exception('No effective doctor user found for this account.');
            }

            // Get the effective doctor profile to get the actual doctor ID
            $effectiveDoctor = $user->getEffectiveDoctor();
            if (!$effectiveDoctor) {
                throw new \Exception('No effective doctor profile found for this account.');
            }

            // Check rate limiting for deactivation
            $this->checkRateLimit($user->id, 'kiosk_deactivation', 5, 60); // 5 deactivations per hour

            $kioskConfig = DB::table('doctor_kiosk_configs')
                ->where('doctor_id', $effectiveDoctor->id)
                ->first();

            if (!$kioskConfig) {
                return redirect()->route('doctor.kiosk.setup')
                    ->with('warning', 'No kiosk configuration found to deactivate.');
            }

            if (!($kioskConfig->is_active ?? true)) {
                return redirect()->route('doctor.kiosk.management')
                    ->with('info', 'Kiosk is already deactivated.');
            }

            DB::table('doctor_kiosk_configs')
                ->where('doctor_id', $effectiveDoctor->id)
                ->update([
                    'is_active' => false,
                    'updated_at' => now()
                ]);

            // Log deactivation
            Log::info('Kiosk deactivated', [
                'user_id' => $user->id,
                'doctor_id' => $effectiveDoctor->id,
                'clinic_name' => $kioskConfig->clinic_name,
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            return redirect()->route('doctor.kiosk.setup')
                ->with('success', 'Kiosk has been deactivated successfully.');

        } catch (\Exception $e) {
            Log::error('Kiosk deactivation failed', [
                'user_id' => Auth::id(),
                'doctor_id' => $effectiveDoctor->id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'ip' => request()->ip(),
            ]);

            return redirect()->back()
                ->with('error', 'Failed to deactivate kiosk. Please try again.');
        }
    }

    /**
     * Activate kiosk.
     */
    public function activate()
    {
        try {
            $user = Auth::user();
            $effectiveDoctorUser = $user->getEffectiveDoctorUser();
            if (!$effectiveDoctorUser) {
                throw new \Exception('No effective doctor user found for this account.');
            }

            // Get the effective doctor profile to get the actual doctor ID
            $effectiveDoctor = $user->getEffectiveDoctor();
            if (!$effectiveDoctor) {
                throw new \Exception('No effective doctor profile found for this account.');
            }

            // Check rate limiting for activation
            $this->checkRateLimit($user->id, 'kiosk_activation', 5, 60); // 5 activations per hour

            $kioskConfig = DB::table('doctor_kiosk_configs')
                ->where('doctor_id', $effectiveDoctor->id)
                ->first();

            if (!$kioskConfig) {
                return redirect()->route('doctor.kiosk.setup')
                    ->with('warning', 'Please set up your kiosk configuration first.');
            }

            if ($kioskConfig->is_active ?? false) {
                return redirect()->route('doctor.kiosk.management')
                    ->with('info', 'Kiosk is already activated.');
            }

            // Validate that all required configuration is present
            $requiredFields = ['clinic_name', 'clinic_address', 'contact_phone', 'kiosk_display_name'];
            $missingFields = [];

            foreach ($requiredFields as $field) {
                if (empty($kioskConfig->$field)) {
                    $missingFields[] = $field;
                }
            }

            if (!empty($missingFields)) {
                return redirect()->route('doctor.kiosk.setup')
                    ->with('warning', 'Please complete your kiosk configuration before activating. Missing: ' . implode(', ', $missingFields));
            }

            DB::table('doctor_kiosk_configs')
                ->where('doctor_id', $effectiveDoctor->id)
                ->update([
                    'is_active' => true,
                    'updated_at' => now()
                ]);

            // Log activation
            Log::info('Kiosk activated', [
                'user_id' => $user->id,
                'doctor_id' => $effectiveDoctor->id,
                'clinic_name' => $kioskConfig->clinic_name,
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            return redirect()->route('doctor.kiosk.management')
                ->with('success', 'Kiosk has been activated successfully.');

        } catch (\Exception $e) {
            Log::error('Kiosk activation failed', [
                'user_id' => Auth::id(),
                'doctor_id' => $effectiveDoctor->id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'ip' => request()->ip(),
            ]);

            return redirect()->back()
                ->with('error', 'Failed to activate kiosk. Please try again.');
        }
    }

    /**
     * Get kiosk usage analytics.
     */
    public function analytics()
    {
        try {
            $user = Auth::user();
            $effectiveDoctorUser = $user->getEffectiveDoctorUser();
            if (!$effectiveDoctorUser) {
                throw new \Exception('No effective doctor user found for this account.');
            }

            // Get the effective doctor profile to get the actual doctor ID
            $effectiveDoctor = $user->getEffectiveDoctor();
            if (!$effectiveDoctor) {
                throw new \Exception('No effective doctor profile found for this account.');
            }

            // Check rate limiting for analytics requests
            $this->checkRateLimit($user->id, 'kiosk_analytics', 20, 60); // 20 requests per hour

            $cacheKey = "kiosk_analytics_{$user->id}";
            $cacheDuration = 300; // 5 minutes

            $analytics = cache()->remember($cacheKey, $cacheDuration, function () use ($effectiveDoctor) {
                // Get kiosk IDs that have created appointments for this doctor
                $appointmentKioskIds = DB::table('appointments')
                    ->where('doctor_id', $effectiveDoctor->id)
                    ->whereNotNull('kiosk_id')
                    ->pluck('kiosk_id')
                    ->unique();

                // Get daily usage for the last 30 days with optimized query
                $dailyUsage = DB::table('kiosk_sessions')
                    ->selectRaw('DATE(created_at) as date, COUNT(*) as sessions')
                    ->whereIn('kiosk_id', $appointmentKioskIds)
                    ->where('created_at', '>=', now()->subDays(30))
                    ->groupByRaw('DATE(created_at)')
                    ->orderByRaw('DATE(created_at)')
                    ->get()
                    ->keyBy('date');

                // Fill in missing dates with zero sessions
                $usageData = [];
                for ($i = 29; $i >= 0; $i--) {
                    $date = now()->subDays($i)->format('Y-m-d');
                    $usageData[] = [
                        'date' => $date,
                        'sessions' => $dailyUsage->get($date)->sessions ?? 0
                    ];
                }

                // Get appointment creation rate with optimized query
                $appointmentStats = DB::table('kiosk_sessions')
                    ->whereIn('kiosk_id', $appointmentKioskIds)
                    ->where('created_at', '>=', now()->subDays(30))
                    ->selectRaw('
                        COUNT(*) as total_sessions,
                        COUNT(CASE WHEN appointment_created_at IS NOT NULL THEN 1 END) as appointments_created,
                        COUNT(CASE WHEN payment_status = "completed" THEN 1 END) as payments_completed,
                        AVG(CASE WHEN duration_minutes IS NOT NULL THEN duration_minutes END) as avg_session_duration
                    ')
                    ->first();

                $conversionRate = $appointmentStats->total_sessions > 0
                    ? round(($appointmentStats->appointments_created / $appointmentStats->total_sessions) * 100, 2)
                    : 0;

                $paymentRate = $appointmentStats->total_sessions > 0
                    ? round(($appointmentStats->payments_completed / $appointmentStats->total_sessions) * 100, 2)
                    : 0;

                // Get peak usage hours
                $peakHours = DB::table('kiosk_sessions')
                    ->selectRaw('HOUR(created_at) as hour, COUNT(*) as sessions')
                    ->whereIn('kiosk_id', $appointmentKioskIds)
                    ->where('created_at', '>=', now()->subDays(30))
                    ->groupByRaw('HOUR(created_at)')
                    ->orderByRaw('sessions DESC')
                    ->limit(5)
                    ->get();

                return [
                    'daily_usage' => $usageData,
                    'summary' => [
                        'total_sessions' => $appointmentStats->total_sessions,
                        'appointments_created' => $appointmentStats->appointments_created,
                        'conversion_rate' => $conversionRate,
                        'payments_completed' => $appointmentStats->payments_completed,
                        'payment_rate' => $paymentRate,
                        'avg_session_duration' => round($appointmentStats->avg_session_duration ?? 0, 1),
                    ],
                    'peak_hours' => $peakHours,
                    'generated_at' => now()->toISOString(),
                ];
            });

            // Log analytics access
            Log::info('Kiosk analytics accessed', [
                'user_id' => $user->id,
                'doctor_id' => $effectiveDoctor->id,
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            return response()->json([
                'success' => true,
                'data' => $analytics,
            ]);

        } catch (\Exception $e) {
            Log::error('Kiosk analytics failed', [
                'user_id' => Auth::id(),
                'doctor_id' => $effectiveDoctor->id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'ip' => request()->ip(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to load analytics. Please try again.',
                'data' => null
            ], 500);
        }
    }

    /**
     * Regenerate kiosk access token.
     */
    public function regenerateToken()
    {
        try {
            $user = Auth::user();
            $effectiveDoctorUser = $user->getEffectiveDoctorUser();
            if (!$effectiveDoctorUser) {
                throw new \Exception('No effective doctor user found for this account.');
            }

            // Get the effective doctor profile to get the actual doctor ID
            $effectiveDoctor = $user->getEffectiveDoctor();
            if (!$effectiveDoctor) {
                throw new \Exception('No effective doctor profile found for this account.');
            }

            // Check rate limiting for token regeneration
            $this->checkRateLimit($user->id, 'kiosk_token_regeneration', 3, 60); // 3 regenerations per hour

            $newToken = $this->generateSecureKioskToken();

            DB::table('doctor_kiosk_configs')
                ->where('doctor_id', $effectiveDoctor->id)
                ->update(['kiosk_token' => $newToken]);

            // Log token regeneration
            Log::info('Kiosk access token regenerated', [
                'user_id' => $user->id,
                'doctor_id' => $effectiveDoctor->id,
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            return response()->json([
                'success' => true,
                'new_token' => $newToken,
                'message' => 'Kiosk access token regenerated successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Token regeneration failed', [
                'user_id' => Auth::id(),
                'doctor_id' => $effectiveDoctor->id ?? null,
                'error' => $e->getMessage(),
                'ip' => request()->ip(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to regenerate token. Please try again.'
            ], 500);
        }
    }

    /**
     * Sanitize kiosk setup input data.
     */
    private function sanitizeKioskSetupData(array $data): array
    {
        return [
            'clinic_name' => trim(strip_tags($data['clinic_name'] ?? '')),
            'clinic_address' => trim(strip_tags($data['clinic_address'] ?? '')),
            'contact_phone' => trim(preg_replace('/[^\d\+\-\(\)\s]/', '', $data['contact_phone'] ?? '')),
            'kiosk_display_name' => trim(strip_tags($data['kiosk_display_name'] ?? '')),
            'primary_color' => trim(strip_tags($data['primary_color'] ?? '')),
            'secondary_color' => trim(strip_tags($data['secondary_color'] ?? '')),
        ];
    }

    /**
     * Check rate limiting for kiosk operations.
     */
    private function checkRateLimit(int $userId, string $operation, int $maxAttempts, int $decayMinutes): void
    {
        $key = "kiosk:{$operation}:{$userId}";
        $attempts = cache()->get($key, 0);

        if ($attempts >= $maxAttempts) {
            throw new KioskSecurityException(
                'Rate limit exceeded. Please try again later.',
                [
                    'user_id' => $userId,
                    'operation' => $operation,
                    'attempts' => $attempts,
                    'max_attempts' => $maxAttempts,
                    'decay_minutes' => $decayMinutes,
                ]
            );
        }

        cache()->put($key, $attempts + 1, now()->addMinutes($decayMinutes));
    }

    /**
     * Generate a secure kiosk access token.
     */
    private function generateSecureKioskToken(): string
    {
        do {
            $token = 'kiosk_' . Str::random(28) . '_' . time();
            $exists = DB::table('doctor_kiosk_configs')
                ->where('kiosk_token', $token)
                ->exists();
        } while ($exists);

        return $token;
    }

    /**
     * Format phone number for consistent storage.
     */
    private function formatPhoneNumber(string $phone): string
    {
        // Remove all non-digit characters except +
        $cleaned = preg_replace('/[^\d\+]/', '', $phone);

        // Ensure it starts with + if it doesn't already
        if (!str_starts_with($cleaned, '+') && strlen($cleaned) > 10) {
            $cleaned = '+' . $cleaned;
        }

        return $cleaned;
    }

    /**
     * Get kiosk performance metrics.
     */
    public function performance()
    {
        try {
            $user = Auth::user();
            $effectiveDoctorUser = $user->getEffectiveDoctorUser();
            if (!$effectiveDoctorUser) {
                throw new \Exception('No effective doctor user found for this account.');
            }

            // Get the effective doctor profile to get the actual doctor ID
            $effectiveDoctor = $user->getEffectiveDoctor();
            if (!$effectiveDoctor) {
                throw new \Exception('No effective doctor profile found for this account.');
            }

            // Check rate limiting for performance metrics access
            $this->checkRateLimit($user->id, 'kiosk_performance_access', 10, 60); // 10 requests per hour

            // Get all kiosks associated with this doctor's appointments
            $kioskIds = DB::table('appointments')
                ->where('doctor_id', $effectiveDoctor->id)
                ->whereNotNull('kiosk_id')
                ->distinct()
                ->pluck('kiosk_id')
                ->toArray();

            $performanceData = [];

            foreach ($kioskIds as $kioskId) {
                $performanceData[] = KioskPerformanceMonitor::getPerformanceReport($kioskId);
            }

            // Get system-wide summary
            $systemSummary = KioskPerformanceMonitor::getSystemPerformanceSummary();

            // Log performance metrics access
            Log::info('Kiosk performance metrics accessed', [
                'user_id' => $user->id,
                'doctor_id' => $effectiveDoctor->id,
                'kiosk_count' => count($kioskIds),
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'kiosk_performance' => $performanceData,
                    'system_summary' => $systemSummary,
                    'generated_at' => now()->toISOString(),
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Kiosk performance metrics failed', [
                'user_id' => Auth::id(),
                'doctor_id' => $effectiveDoctor->id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'ip' => request()->ip(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to load performance metrics. Please try again.',
                'data' => null
            ], 500);
        }
    }

    /**
     * Clear performance metrics for maintenance.
     */
    public function clearPerformanceMetrics()
    {
        try {
            $user = Auth::user();
            $effectiveDoctorUser = $user->getEffectiveDoctorUser();
            if (!$effectiveDoctorUser) {
                throw new \Exception('No effective doctor user found for this account.');
            }

            // Get the effective doctor profile to get the actual doctor ID
            $effectiveDoctor = $user->getEffectiveDoctor();
            if (!$effectiveDoctor) {
                throw new \Exception('No effective doctor profile found for this account.');
            }

            // Only allow clearing metrics for kiosks associated with this doctor
            $kioskIds = DB::table('appointments')
                ->where('doctor_id', $effectiveDoctor->id)
                ->whereNotNull('kiosk_id')
                ->distinct()
                ->pluck('kiosk_id')
                ->toArray();

            foreach ($kioskIds as $kioskId) {
                KioskPerformanceMonitor::clearMetrics($kioskId);
            }

            Log::info('Kiosk performance metrics cleared', [
                'user_id' => $user->id,
                'doctor_id' => $effectiveDoctor->id,
                'kiosks_cleared' => count($kioskIds),
                'ip' => request()->ip(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Performance metrics cleared successfully.',
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to clear performance metrics', [
                'user_id' => Auth::id(),
                'doctor_id' => $effectiveDoctor->id ?? null,
                'error' => $e->getMessage(),
                'ip' => request()->ip(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to clear performance metrics.',
            ], 500);
        }
    }

    /**
     * Create a backup of kiosk data.
     */
    public function createBackup(Request $request)
    {
        try {
            $user = Auth::user();
            $effectiveDoctorUser = $user->getEffectiveDoctorUser();
            if (!$effectiveDoctorUser) {
                throw new \Exception('No effective doctor user found for this account.');
            }

            // Get the effective doctor profile to get the actual doctor ID
            $effectiveDoctor = $user->getEffectiveDoctor();
            if (!$effectiveDoctor) {
                throw new \Exception('No effective doctor profile found for this account.');
            }

            // Check rate limiting for backup creation
            $this->checkRateLimit($user->id, 'kiosk_backup_creation', 5, 60); // 5 backups per hour

            $request->validate([
                'backup_type' => 'required|in:full,config,sessions',
                'kiosk_id' => 'nullable|exists:kiosks,id',
            ]);

            $backupType = $request->backup_type;
            $kioskId = $request->kiosk_id;

            // If kiosk_id is specified, ensure it belongs to this doctor's appointments
            if ($kioskId) {
                $hasAccess = DB::table('appointments')
                    ->where('doctor_id', $effectiveDoctor->id)
                    ->where('kiosk_id', $kioskId)
                    ->exists();

                if (!$hasAccess) {
                    throw new KioskSecurityException('Access denied to specified kiosk data.');
                }
            }

            $result = KioskBackupService::createBackup($kioskId, $backupType);

            Log::info('Kiosk backup created via API', [
                'user_id' => $user->id,
                'doctor_id' => $effectiveDoctor->id,
                'backup_id' => $result['backup_id'],
                'backup_type' => $backupType,
                'kiosk_id' => $kioskId,
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Kiosk backup created successfully.',
                'data' => $result,
            ]);

        } catch (\Exception $e) {
            Log::error('Kiosk backup creation failed', [
                'user_id' => Auth::id(),
                'doctor_id' => $effectiveDoctor->id ?? null,
                'error' => $e->getMessage(),
                'ip' => request()->ip(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create backup. Please try again.',
            ], 500);
        }
    }

    /**
     * List available backups.
     */
    public function listBackups()
    {
        try {
            $user = Auth::user();
            $effectiveDoctorUser = $user->getEffectiveDoctorUser();
            if (!$effectiveDoctorUser) {
                throw new \Exception('No effective doctor user found for this account.');
            }

            // Get the effective doctor profile to get the actual doctor ID
            $effectiveDoctor = $user->getEffectiveDoctor();
            if (!$effectiveDoctor) {
                throw new \Exception('No effective doctor profile found for this account.');
            }

            // Get kiosk IDs this doctor has access to
            $kioskIds = DB::table('appointments')
                ->where('doctor_id', $effectiveDoctor->id)
                ->whereNotNull('kiosk_id')
                ->distinct()
                ->pluck('kiosk_id')
                ->toArray();

            $allBackups = KioskBackupService::listBackups();

            // Filter backups to only include those for kiosks this doctor has access to
            $accessibleBackups = array_filter($allBackups, function($backup) use ($kioskIds) {
                return !$backup['kiosk_id'] || in_array($backup['kiosk_id'], $kioskIds);
            });

            return response()->json([
                'success' => true,
                'data' => array_values($accessibleBackups),
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to list kiosk backups', [
                'user_id' => Auth::id(),
                'doctor_id' => $effectiveDoctor->id ?? null,
                'error' => $e->getMessage(),
                'ip' => request()->ip(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load backups list.',
            ], 500);
        }
    }

    /**
     * Restore from a backup.
     */
    public function restoreBackup(Request $request)
    {
        try {
            $user = Auth::user();
            $effectiveDoctorUser = $user->getEffectiveDoctorUser();
            if (!$effectiveDoctorUser) {
                throw new \Exception('No effective doctor user found for this account.');
            }

            // Get the effective doctor profile to get the actual doctor ID
            $effectiveDoctor = $user->getEffectiveDoctor();
            if (!$effectiveDoctor) {
                throw new \Exception('No effective doctor profile found for this account.');
            }

            // Check rate limiting for backup restoration
            $this->checkRateLimit($user->id, 'kiosk_backup_restoration', 2, 60); // 2 restorations per hour

            $request->validate([
                'backup_id' => 'required|string',
                'overwrite_existing' => 'boolean',
                'skip_validation' => 'boolean',
            ]);

            $backupId = $request->backup_id;

            // Verify backup exists and user has access
            $backups = KioskBackupService::listBackups();
            $backup = collect($backups)->firstWhere('backup_id', $backupId);

            if (!$backup) {
                return response()->json([
                    'success' => false,
                    'message' => 'Backup not found.',
                ], 404);
            }

            // Check access if backup is for a specific kiosk
            if ($backup['kiosk_id']) {
                $hasAccess = DB::table('appointments')
                    ->where('doctor_id', $effectiveDoctor->id)
                    ->where('kiosk_id', $backup['kiosk_id'])
                    ->exists();

                if (!$hasAccess) {
                    throw new KioskSecurityException('Access denied to backup data.');
                }
            }

            $options = [
                'overwrite_existing' => $request->boolean('overwrite_existing', false),
                'skip_validation' => $request->boolean('skip_validation', false),
            ];

            $result = KioskBackupService::restoreBackup($backupId, $options);

            Log::info('Kiosk backup restored via API', [
                'user_id' => $user->id,
                'doctor_id' => $effectiveDoctor->id,
                'backup_id' => $backupId,
                'options' => $options,
                'results' => $result['results'],
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Backup restored successfully.',
                'data' => $result,
            ]);

        } catch (\Exception $e) {
            Log::error('Kiosk backup restoration failed', [
                'user_id' => Auth::id(),
                'doctor_id' => $effectiveDoctor->id ?? null,
                'backup_id' => $request->backup_id ?? null,
                'error' => $e->getMessage(),
                'ip' => request()->ip(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to restore backup. Please try again.',
            ], 500);
        }
    }

    /**
     * Delete a backup.
     */
    public function deleteBackup(Request $request)
    {
        try {
            $user = Auth::user();
            $effectiveDoctorUser = $user->getEffectiveDoctorUser();
            if (!$effectiveDoctorUser) {
                throw new \Exception('No effective doctor user found for this account.');
            }

            // Get the effective doctor profile to get the actual doctor ID
            $effectiveDoctor = $user->getEffectiveDoctor();
            if (!$effectiveDoctor) {
                throw new \Exception('No effective doctor profile found for this account.');
            }

            $request->validate([
                'backup_id' => 'required|string',
            ]);

            $backupId = $request->backup_id;

            // Verify backup exists and user has access
            $backups = KioskBackupService::listBackups();
            $backup = collect($backups)->firstWhere('backup_id', $backupId);

            if (!$backup) {
                return response()->json([
                    'success' => false,
                    'message' => 'Backup not found.',
                ], 404);
            }

            // Check access if backup is for a specific kiosk
            if ($backup['kiosk_id']) {
                $hasAccess = DB::table('appointments')
                    ->where('doctor_id', $effectiveDoctor->id)
                    ->where('kiosk_id', $backup['kiosk_id'])
                    ->exists();

                if (!$hasAccess) {
                    throw new KioskSecurityException('Access denied to backup.');
                }
            }

            $deleted = KioskBackupService::deleteBackup($backupId);

            if ($deleted) {
                Log::info('Kiosk backup deleted via API', [
                    'user_id' => $user->id,
                    'doctor_id' => $effectiveDoctor->id,
                    'backup_id' => $backupId,
                    'ip' => $request->ip(),
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Backup deleted successfully.',
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to delete backup.',
                ], 500);
            }

        } catch (\Exception $e) {
            Log::error('Kiosk backup deletion failed', [
                'user_id' => Auth::id(),
                'doctor_id' => $effectiveDoctor->id ?? null,
                'backup_id' => $request->backup_id ?? null,
                'error' => $e->getMessage(),
                'ip' => request()->ip(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete backup.',
            ], 500);
        }
    }
}
