<?php

namespace App\Http\Controllers;

use App\Models\VoiceTranscription;
use App\Models\User;
use App\Models\Diagnosis;
use App\Models\AiAssistantResult;
use App\Models\VoiceAssistantPerformanceMetric;
use App\Models\Appointment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use OpenAI\Laravel\Facades\OpenAI;
use App\Models\Patient;
use Illuminate\Support\Facades\Storage;
use App\Helpers\OpenAIHelper;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Notifications\VoiceTranscriptionCompletedNotification;
use App\Notifications\SystemAlertNotification;
use App\Jobs\ProcessVoiceTranscriptionJob;
use Google\Cloud\Speech\V1\SpeechClient;
use Google\Cloud\Speech\V1\RecognitionConfig;
use Google\Cloud\Speech\V1\AudioEncoding;
use Google\Cloud\Speech\V1\SpeechAdaptation;
use Google\Cloud\Speech\V1\PhraseSet;
use Google\Cloud\Speech\V1\RecognitionAudio;

class VoiceAssistantController extends Controller
{
    private $cachedMedicalPhraseSet = null;

    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $user = Auth::user();

            // Handle sub-users - they inherit access from their parent doctor
            if ($user->parent_user_id) { // Assuming sub-users have a parent_user_id
                $parentUser = $user->parentUser;
                if (!$parentUser || $parentUser->role !== 'doctor' || !$parentUser->doctor || !$parentUser->doctor->is_active) {
                    abort(403, 'Access denied. Parent doctor profile required.');
                }
            } else {
                // Handle main users (doctors)
                if ($user->role !== 'doctor' || !$user->doctor) {
                    abort(403, 'Access denied. Doctor profile required.');
                }

                if (!$user->doctor->is_active) {
                    abort(403, 'Access denied. Your doctor account has been deactivated.');
                }
            }

            return $next($request);
        });
    }

    /**
     * Generate patient key for consistent patient identification
     */
    private function generatePatientKey($patient)
    {
        try {
            // Use the same logic as Diagnosis model
            return Diagnosis::generatePatientKey(
                $patient->name ?? null,
                $patient->age ?? null,
                $patient->gender ?? null,
                Auth::id()
            );
        } catch (\Exception $e) {
            \Log::error('Failed to generate patient key', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);
            throw $e; // Re-throw to be handled by calling function
        }
    }

    public function training()
    {
        return view('voice-assistant.training');
    }

    public function performance()
    {
        // Get performance metrics for the current doctor
        $doctorId = Auth::id();
        $days = request('days', 30);

        $successRates = VoiceAssistantPerformanceMetric::getSuccessRates($doctorId, $days);
        $performanceTrends = VoiceAssistantPerformanceMetric::getPerformanceTrends($doctorId, $days);
        $errorStatistics = VoiceAssistantPerformanceMetric::getErrorStatistics($doctorId, $days);

        // Get recent sessions for detailed view
        $recentSessions = VoiceAssistantPerformanceMetric::where('doctor_id', $doctorId)
            ->with('doctor')
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        return view('voice-assistant.performance', compact(
            'successRates',
            'performanceTrends',
            'errorStatistics',
            'recentSessions',
            'days'
        ));
    }

    public function index()
    {
        // Load patients for the dropdown with visit history
        $patients = [];
        $patientGroups = [];

        \Log::info('Voice Assistant - Starting index method', [
            'user_id' => Auth::id(),
            'is_doctor' => Auth::user()->role === 'doctor',
            'primary_doctor_id' => Auth::user()->primary_doctor_id ?? 'null'
        ]);

        try {
            // First, get assigned patients (patients where primary_doctor_id matches)
            $assignedPatients = Auth::user()->getEffectiveAssignedPatients()
                ->select('id', 'name', 'email', 'age', 'date_of_birth', 'gender')
                ->get();

            // Then, get patients who have confirmed or completed appointments with this doctor
            // but are not already in the assigned patients list
            $effectiveDoctorId = Auth::user()->getEffectiveDoctorUser()->id ?? Auth::id();
            $appointmentPatients = User::where('role', 'patient')
                ->whereHas('appointments', function($query) use ($effectiveDoctorId) {
                    $query->where('doctor_id', $effectiveDoctorId)
                          ->whereIn('status', ['confirmed', 'completed']);
                })
                ->whereNotIn('id', $assignedPatients->pluck('id'))
                ->select('id', 'name', 'email', 'age', 'date_of_birth', 'gender')
                ->get();

            // Merge the two collections
            $basePatients = $assignedPatients->merge($appointmentPatients)->unique('id')->sortBy('name');

            \Log::info('Voice Assistant - Loaded patients from assigned + appointments', [
                'assigned_count' => $assignedPatients->count(),
                'appointment_count' => $appointmentPatients->count(),
                'total_count' => $basePatients->count(),
                'patient_names' => $basePatients->pluck('name')->toArray()
            ]);
        } catch (\Exception $e) {
            \Log::warning('Could not load patients, using minimal fallback: ' . $e->getMessage());

            // Minimal fallback: just get patients with confirmed or completed appointments
            try {
                $effectiveDoctorId = Auth::user()->getEffectiveDoctorUser()->id ?? Auth::id();
                $basePatients = User::where('role', 'patient')
                    ->whereHas('appointments', function($query) use ($effectiveDoctorId) {
                        $query->where('doctor_id', $effectiveDoctorId)
                              ->whereIn('status', ['confirmed', 'completed']);
                    })
                    ->select('id', 'name', 'email', 'age', 'date_of_birth', 'gender')
                    ->orderBy('name')
                    ->get();

                \Log::info('Voice Assistant - Loaded patients using appointment fallback', [
                    'count' => $basePatients->count(),
                ]);
            } catch (\Exception $e2) {
                $basePatients = collect();
                \Log::error('Could not load patients at all: ' . $e2->getMessage());
            }
        }

        // Load guest patients from confirmed or completed appointments
        $effectiveDoctorId = Auth::user()->getEffectiveDoctorUser()->id ?? Auth::id();
        $guestAppointments = \App\Models\Appointment::where('doctor_id', $effectiveDoctorId)
            ->whereIn('status', ['confirmed', 'completed'])
            ->whereNotNull('guest_name')
            ->whereNotNull('guest_email')
            ->select('guest_name', 'guest_email', 'guest_date_of_birth', 'guest_gender', 'guest_phone', 'appointment_date')
            ->orderBy('appointment_date', 'desc')
            ->get();

        \Log::info('Voice Assistant - Loaded guest patients from confirmed appointments', [
            'count' => $guestAppointments->count(),
            'guest_names' => $guestAppointments->pluck('guest_name')->toArray()
        ]);

        // Convert guest appointments to patient-like objects
        $guestPatients = $guestAppointments->map(function($appointment) {
            // Calculate age from date of birth if available
            $age = null;
            if ($appointment->guest_date_of_birth) {
                $age = $appointment->guest_date_of_birth->age;
            }

            return (object)[
                'id' => 'guest_' . md5($appointment->guest_email . $appointment->guest_name), // Create unique ID for guest
                'name' => $appointment->guest_name,
                'email' => $appointment->guest_email,
                'age' => $age,
                'gender' => $appointment->guest_gender,
                'phone' => $appointment->guest_phone,
                'is_guest' => true,
                'last_appointment' => $appointment->appointment_date
            ];
        });

        // Merge registered patients with guest patients, removing duplicates by email
        $allPatients = $basePatients->concat($guestPatients)->unique('email')->values();

        \Log::info('Voice Assistant - Total patients after merging guests', [
            'total_count' => $allPatients->count(),
            'registered_count' => $basePatients->count(),
            'guest_count' => $guestPatients->count()
        ]);

        // Load available appointments for each patient (for appointment completion)
        $patientAppointments = [];
        $effectiveDoctorId = Auth::user()->parent_user_id ? Auth::user()->parent_user_id : Auth::id();
        $loggedInUserId = Auth::id();

        // Define appointment collections before using them
        $allAppointments = collect();
        $todaysAppointments = collect();

        // Load appointments for all patients at once to avoid N+1 queries
        $effectiveDoctorIdForAppointment = Auth::user()->getEffectiveDoctorUser()->id ?? Auth::id();

        // Get all appointments for the doctor's patients
        $appointmentsQuery = \App\Models\Appointment::where('doctor_id', $effectiveDoctorIdForAppointment)
            ->whereIn('patient_id', $basePatients->pluck('id'))
            ->whereIn('status', ['confirmed', 'pending', 'completed'])
            ->with(['patient']) // Eager load patient relationship
            ->get();

        // Group appointments by patient_id
        $allAppointments = $appointmentsQuery->groupBy('patient_id');

        // Also get today's appointments specifically
        $todaysAppointmentsQuery = \App\Models\Appointment::where('doctor_id', $effectiveDoctorIdForAppointment)
            ->whereIn('patient_id', $basePatients->pluck('id'))
            ->whereDate('appointment_date', Carbon::today())
            ->whereIn('status', ['confirmed', 'pending', 'completed'])
            ->with(['patient'])
            ->get();

        $todaysAppointments = $todaysAppointmentsQuery->groupBy('patient_id');

        Log::info('Voice Assistant - Doctor ID debug', [
            'effective_doctor_id' => $effectiveDoctorId,
            'logged_in_user_id' => $loggedInUserId,
            'user_is_doctor' => Auth::user()->role === 'doctor',
        ]);

        foreach ($allPatients as $patient) {
            $appointments = collect(); // Start with empty collection

            // Get appointments for this patient from pre-fetched data
            $activeAppointments = $allAppointments->get($patient->id, collect());

            if ($activeAppointments->isNotEmpty()) {
                $appointments = $activeAppointments;
            } else {
                // If no active appointments found, use today's appointments
                $todaysPatientAppointments = $todaysAppointments->get($patient->id, collect());
                if ($todaysPatientAppointments->isNotEmpty()) {
                    $appointments = $todaysPatientAppointments;
                }
            }

            $appointments = $appointments->map(function($appointment) {
                return [
                    'id' => $appointment->id,
                    'appointment_date' => $appointment->appointment_date,
                    'appointment_date_formatted' => $appointment->appointment_date->format('M j, Y g:i A'),
                    'appointment_type' => $appointment->appointment_type ?? 'General',
                    'status' => $appointment->status,
                    'reason' => $appointment->reason,
                ];
            });

            // Debug logging without sensitive patient names
            Log::info('Voice Assistant - Final appointments for patient', [
                'patient_id' => $patient->id,
                'effective_doctor_id' => $effectiveDoctorId,
                'logged_in_user_id' => $loggedInUserId,
                'appointment_count' => $appointments->count(),
            ]);

            $patientAppointments[$patient->id] = $appointments;
        }

        // Fetch all diagnosis records for the current doctor and their patients at once
        $allDiagnoses = Diagnosis::whereIn('patient_id', $basePatients->pluck('id'))
            ->where('doctor_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy('patient_id'); // Group by patient_id for easy lookup

        // Process patients and build patient groups with visit history
        foreach ($allPatients as $patient) {
            // Skip guest patients for diagnosis-based processing (they don't have diagnosis records)
            if (isset($patient->is_guest) && $patient->is_guest) {
                // For guest patients, create minimal patient group entry
                $patientKey = 'guest_' . $patient->id;
                $patientGroups[$patientKey] = [
                    'patient' => $patient,
                    'visits' => collect(),
                    'visit_count' => 0,
                    'last_visit' => isset($patient->last_appointment) ? $patient->last_appointment : null,
                    'category' => 'guest',
                    'has_appointments' => true,
                    'appointment_details' => null,
                ];

                // Add to patients array for dropdown
                $patients[] = [
                    'id' => $patient->id,
                    'name' => $patient->name,
                    'email' => $patient->email,
                    'age' => $patient->age,
                    'gender' => $patient->gender,
                    'patient_key' => $patientKey,
                    'visit_count' => 0,
                    'last_visit' => isset($patient->last_appointment) ? $patient->last_appointment->format('M d, Y') : null,
                    'is_guest' => true,
                ];
                continue;
            }

            // Generate patient key if not exists
            $patientKey = $this->generatePatientKey($patient);

            // Get visit history from pre-fetched Diagnosis records
            $patientsDiagnoses = $allDiagnoses->get($patient->id, collect());
            $visitCount = $patientsDiagnoses->count();
            $lastVisit = $patientsDiagnoses->first() ? $patientsDiagnoses->first()->created_at : null;

            // Add to patient groups for modal compatibility
            $patientGroups[$patientKey] = [
                'patient' => $patient,
                'visits' => $patientsDiagnoses->map(function($visit) {
                    return (object)[
                        'id' => $visit->id,
                        'visit_number' => 1, // Diagnosis records don't have visit numbers yet
                        'date' => $visit->created_at->format('M d, Y'),
                        'diagnosis' => substr($visit->diagnosis_text ?? 'No diagnosis available', 0, 100) .
                                      (strlen($visit->diagnosis_text ?? '') > 100 ? '...' : ''),
                        'source_model' => 'Diagnosis',
                    ];
                }),
                'visit_count' => $visitCount,
                'last_visit' => $lastVisit,
                'category' => 'diagnosed',
                'has_appointments' => false,
                'appointment_details' => null,
            ];

            // Add to patients array for dropdown
            $patients[] = [
                'id' => $patient->id,
                'name' => $patient->name,
                'email' => $patient->email,
                'age' => $patient->age,
                'gender' => $patient->gender,
                'patient_key' => $patientKey,
                'visit_count' => $visitCount,
                'last_visit' => $lastVisit ? $lastVisit->format('M d, Y') : null,
            ];
        }

        // Generate initial session ID
        $sessionId = Str::uuid()->toString();

        // Pass patients as records for JavaScript compatibility
        $records = $patients;

        return view('voice-assistant.index', compact('patients', 'sessionId', 'records', 'patientGroups', 'patientAppointments'));
    }

    public function history()
    {
        $transcriptions = VoiceTranscription::where('doctor_id', Auth::id())
            ->with('patient:id,name,email,age,gender') // Limit patient data loaded for security
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('voice-assistant.history', compact('transcriptions'));
    }

    public function show(VoiceTranscription $transcription)
    {
        // Ensure the transcription belongs to the authenticated doctor
        if ($transcription->doctor_id !== Auth::id()) {
            abort(403, 'Unauthorized access to transcription.');
        }

        return view('voice-assistant.show', compact('transcription'));
    }

    public function recordedVoices()
    {
        $transcriptions = VoiceTranscription::where('doctor_id', Auth::id())
            ->with('patient')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('voice-assistant.recorded-voices', compact('transcriptions'));
    }

    public function startSession(Request $request)
    {
        $selectedPatient = $request->input('selectedPatient');

        // Validate patient ID
        if (!$selectedPatient || !is_numeric($selectedPatient) || $selectedPatient <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Please select a valid patient first.'
            ]);
        }

        // Verify that the patient belongs to the current authenticated doctor
        $patient = User::find($selectedPatient);
        if (!$patient) {
            return response()->json([
                'success' => false,
                'message' => 'Patient not found.'
            ], 404);
        }

        $effectiveDoctorId = Auth::user()->getEffectiveDoctorUser()->id ?? Auth::id();

        // Check if patient belongs to doctor either by primary_doctor_id or through appointments
        $hasAccess = false;

        // Check if patient is assigned to this doctor
        if ($patient->primary_doctor_id == $effectiveDoctorId) {
            $hasAccess = true;
        } else {
            // Check if patient has appointments with this doctor
            $hasAccess = $patient->appointments()
                ->where('doctor_id', $effectiveDoctorId)
                ->whereIn('status', ['confirmed', 'completed', 'pending'])
                ->exists();
        }

        if (!$hasAccess) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to patient.'
            ], 403);
        }

        $sessionId = Str::uuid()->toString();

        // Create initial transcription record
        $transcription = VoiceTranscription::create([
            'doctor_id' => Auth::id(),
            'patient_id' => $selectedPatient,
            'session_id' => $sessionId,
            'raw_transcription' => '',
            'status' => 'active',
            'session_started_at' => now(),
        ]);

        // Get AssemblyAI configuration for direct client-side streaming
        // IMPORTANT: Skip AssemblyAI for Arabic - it doesn't support Arabic script properly
        $assemblyConfig = null;
        $lang = $request->input('language', 'en');
        
        // Only use AssemblyAI for English sessions
        if ($lang === 'en') {
            try {
                // Check if AssemblyAI API key is configured before attempting to use the service
                $assemblyApiKey = config('services.assemblyai.api_key');
                if (!empty($assemblyApiKey)) {
                    $assemblyService = new \App\Services\AssemblyAIService();
                    
                    $streamingParams = [
                        'sample_rate' => 16000,
                        'keyterms_prompt' => ['medical', 'diagnosis', 'symptoms', 'medication'],
                        'format_turns' => true,
                        'speech_model' => 'universal-streaming-english'
                    ];
                    
                    // Get token WITHOUT extra params (v3 endpoint handles token generation)
                    $token = $assemblyService->getTemporaryToken(600);

                    if ($token) {
                        $assemblyConfig = [
                            'token' => $token,
                            'websocket_url' => $assemblyService->getWebSocketUrl($token, $streamingParams),
                            'sample_rate' => 16000
                        ];
                    }
                } else {
                    \Log::warning('AssemblyAI API key not configured, skipping WebSocket setup');
                }
            } catch (\Exception $e) {
                \Log::error('Failed to generate AssemblyAI token for direct streaming: ' . $e->getMessage());
            }
        } else {
            \Log::info('Non-English language selected, skipping AssemblyAI (will use GPT-4o post-processing)', [
                'language' => $lang
            ]);
        }

        return response()->json([
            'success' => true,
            'sessionId' => $sessionId,
            'assemblyConfig' => $assemblyConfig,
            'transcriptionId' => $transcription->id,
            'message' => 'Session started successfully.'
        ]);
    }

    public function stopSession(Request $request)
    {
        $sessionId = $request->input('sessionId');

        // Input validation
        if (empty($sessionId) || !is_string($sessionId) || strlen($sessionId) > 255) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid session ID provided.'
            ], 400);
        }

        // Sanitize the session ID by removing any potential malicious characters
        $sessionId = preg_replace('/[^a-zA-Z0-9\-]/', '', $sessionId);

        if (empty($sessionId)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid session ID format.'
            ], 400);
        }

        try {
            // Verify that the session belongs to the current authenticated doctor
            $transcription = VoiceTranscription::where('session_id', $sessionId)
                ->where('doctor_id', Auth::id())
                ->first();

            if (!$transcription) {
                return response()->json([
                    'success' => false,
                    'message' => 'Session not found or unauthorized access.'
                ], 404);
            }

            // Update transcription record only if it's still active
            $result = VoiceTranscription::where('session_id', $sessionId)
                ->where('doctor_id', Auth::id())
                ->where('status', 'active')
                ->update([
                    'status' => 'completed',
                    'session_ended_at' => now(),
                ]);

            if ($result === 0) {
                // Session might already be completed
                return response()->json([
                    'success' => true,
                    'message' => 'Session already completed or not found.'
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Session stopped successfully.'
            ]);
        } catch (\Exception $e) {
            \Log::error('Stop session error: ' . $e->getMessage(), [
                'session_id' => $sessionId,
                'doctor_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while stopping the session.'
            ], 500);
        }
    }

    public function handleTranscription(Request $request)
    {
        $text = trim($request->input('text', ''));
        $sessionId = $request->input('sessionId');

        // Input validation
        if (empty($text)) {
            return response()->json([
                'success' => false,
                'message' => 'No transcription text provided.'
            ]);
        }

        if (empty($sessionId) || !is_string($sessionId) || strlen($sessionId) > 255) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid session ID provided.'
            ], 400);
        }

        // Sanitize the session ID by removing any potential malicious characters
        $sessionId = preg_replace('/[^a-zA-Z0-9\-]/', '', $sessionId);

        if (empty($sessionId)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid session ID format.'
            ], 400);
        }

        // Verify that the session belongs to the current authenticated doctor
        $transcription = VoiceTranscription::where('session_id', $sessionId)
            ->where('doctor_id', Auth::id())
            ->first();

        if (!$transcription) {
            return response()->json([
                'success' => false,
                'message' => 'Session not found or unauthorized access.'
            ], 404);
        }

        // Update the transcription in database
        $transcription->update([
            'raw_transcription' => $text,
            'updated_at' => now()
        ]);

        return response()->json([
            'success' => true,
            'transcription' => $text,
            'message' => 'Transcription updated successfully.'
        ]);
    }

    public function processWithAI(Request $request)
    {
        $transcription = trim($request->input('transcription', ''));
        $sessionId = $request->input('sessionId');

        // Input validation
        if (empty($sessionId) || !is_string($sessionId) || strlen($sessionId) > 255) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid session ID provided.'
            ], 400);
        }

        // Sanitize the session ID by removing any potential malicious characters
        $sessionId = preg_replace('/[^a-zA-Z0-9\-]/', '', $sessionId);

        if (empty($sessionId)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid session ID format.'
            ], 400);
        }

        \Log::info('Voice Assistant - processWithAI called', [
            'session_id' => $sessionId,
            'transcription_length' => strlen($transcription),
            'transcription_preview' => substr($transcription, 0, 200)
        ]);

        // FIXED: Accept shorter transcriptions for medical content
        if (strlen($transcription) < 3) {
            \Log::warning('Voice Assistant - Transcription too short', [
                'session_id' => $sessionId,
                'length' => strlen($transcription)
            ]);

            // Return fallback data structure instead of error
            $fallbackData = [
                'symptoms' => '',
                'medical_history' => '',
                'physical_findings' => '',
                'medications' => '',
                'vital_signs' => '',
                'diagnosis' => '',
                'care_plan' => ''
            ];

            return response()->json([
                'success' => true,
                'extractedData' => $fallbackData,
                'message' => 'Transcription too short, using fallback data structure.'
            ]);
        }

        try {
            // Verify that the session belongs to the current authenticated doctor
            $transcriptionRecord = VoiceTranscription::where('session_id', $sessionId)
                ->where('doctor_id', Auth::id())
                ->first();

            if (!$transcriptionRecord) {
                return response()->json([
                    'success' => false,
                    'message' => 'Session not found or unauthorized access.'
                ], 404);
            }

            // OPTIMIZATION: Check cache for similar transcriptions first
            $cacheKey = 'voice_ai_extraction_' . md5($transcription);
            $cachedResult = Cache::get($cacheKey);

            if ($cachedResult) {
                \Log::info('Voice Assistant - Using cached AI extraction result');
                $extractedData = $cachedResult;
            } else {
                // FIXED: Enhanced medical extraction with better error handling
                $response = OpenAI::chat()->create([
                    'model' => 'gpt-4o',
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'You are a medical AI assistant specializing in extracting structured medical information from doctor-patient consultations. Extract and categorize information from the transcription into the following categories:

                            1. symptoms: Patient complaints, pain descriptions, discomfort, functional limitations
                            2. medical_history: Past conditions, surgeries, family history, previous treatments
                            3. physical_findings: Examination results, observations, clinical signs
                            4. medications: Current medications, dosages, allergies, drug interactions
                            5. vital_signs: Blood pressure, temperature, heart rate, respiratory rate, oxygen saturation, weight, height
                            6. diagnosis: Potential diagnoses, differential diagnoses, clinical impressions
                            7. care_plan: Treatment recommendations, follow-up instructions, referrals, lifestyle modifications

                            IMPORTANT:
                            - Extract information in both Arabic and English if present
                            - Be comprehensive but accurate - only include explicitly mentioned information
                            - For vital signs, include units and normal/abnormal indicators
                            - For medications, include dosage, frequency, and route if mentioned
                            - For symptoms, include severity, duration, and quality descriptors

                            CRITICAL: Return ONLY a valid JSON object with these exact keys. Do not include any other text.
                            JSON keys must be: symptoms, medical_history, physical_findings, medications, vital_signs, diagnosis, care_plan.
                            If a category has no information, return an empty string "" for that key.'
                        ],
                        [
                            'role' => 'user',
                            'content' => "Extract medical information from this consultation transcription:\n\n" . $transcription . "\n\nReturn only valid JSON."
                        ]
                    ],
                    'temperature' => 0.1, // Even lower temperature for consistency
                    'max_tokens' => 1500,
                ]);

                $aiResponse = $response['choices'][0]['message']['content'] ?? '';

                // FIXED: More robust JSON extraction
                $extractedData = $this->extractJsonFromResponse($aiResponse);

                if ($extractedData) {
                    // Cache successful extractions for 1 hour
                    Cache::put($cacheKey, $extractedData, 3600);
                }
            }

            \Log::info('Voice Assistant - OpenAI response received', [
                'response_length' => strlen($aiResponse ?? ''),
                'response_preview' => substr($aiResponse ?? '', 0, 300)
            ]);

            // FIXED: More robust JSON extraction
            $extractedData = $this->extractJsonFromResponse($aiResponse ?? '');

            if ($extractedData) {
                // Validate and clean the extracted data
                $extractedData = $this->validateAndCleanExtractedData($extractedData);

                // Update the transcription record with extracted data
                $transcriptionRecord->update([
                    'extracted_data' => $extractedData
                ]);

                \Log::info('Voice Assistant - Medical data extraction successful', [
                    'session_id' => $sessionId,
                    'extracted_fields' => array_keys(array_filter($extractedData))
                ]);

                return response()->json([
                    'success' => true,
                    'extractedData' => $extractedData,
                    'message' => 'Medical data extracted successfully.'
                ]);
            } else {
                \Log::warning('Voice Assistant - Failed to extract JSON, using fallback', [
                    'session_id' => $sessionId,
                    'ai_response' => $aiResponse
                ]);

                // Return fallback data instead of error
                $fallbackData = $this->generateFallbackData($transcription);

                return response()->json([
                    'success' => true,
                    'extractedData' => $fallbackData,
                    'message' => 'Used fallback extraction method.'
                ]);
            }
        } catch (\Exception $e) {
            \Log::error('Voice AI processing error: ' . $e->getMessage(), [
                'session_id' => $sessionId,
                'transcription_length' => strlen($transcription),
                'error_type' => get_class($e)
            ]);

            // Return fallback data instead of error to prevent frontend failure
            $fallbackData = $this->generateFallbackData($transcription);

            return response()->json([
                'success' => true,
                'extractedData' => $fallbackData,
                'message' => 'Used fallback extraction due to AI error: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Extract JSON from AI response with multiple fallback methods
     */
    private function extractJsonFromResponse($response)
    {
        // Method 1: Direct JSON decode
        $jsonData = json_decode($response, true);
        if ($jsonData && is_array($jsonData)) {
            return $jsonData;
        }

        // Method 2: Extract JSON block
        $jsonStart = strpos($response, '{');
        if ($jsonStart !== false) {
            $jsonEnd = strrpos($response, '}');
            if ($jsonEnd !== false) {
                $jsonString = substr($response, $jsonStart, $jsonEnd - $jsonStart + 1);
                $jsonData = json_decode($jsonString, true);
                if ($jsonData && is_array($jsonData)) {
                    return $jsonData;
                }
            }
        }

        // Method 3: Try to parse as key-value pairs
        if (strpos($response, 'symptoms') !== false) {
            return $this->parseKeyValueResponse($response);
        }

        return null;
    }

    /**
     * Parse response as key-value pairs
     */
    private function parseKeyValueResponse($response)
    {
        $data = [
            'symptoms' => '',
            'medical_history' => '',
            'physical_findings' => '',
            'medications' => '',
            'vital_signs' => '',
            'diagnosis' => '',
            'care_plan' => ''
        ];

        // Try to extract information using regex
        $patterns = [
            'symptoms' => '/symptoms["\s:]+([^"}]+)/i',
            'medical_history' => '/medical[_ ]history["\s:]+([^"}]+)/i',
            'physical_findings' => '/physical[_ ]findings["\s:]+([^"}]+)/i',
            'medications' => '/medications["\s:]+([^"}]+)/i',
            'vital_signs' => '/vital[_ ]signs["\s:]+([^"}]+)/i',
            'diagnosis' => '/diagnosis["\s:]+([^"}]+)/i',
            'care_plan' => '/care[_ ]plan["\s:]+([^"}]+)/i'
        ];

        foreach ($patterns as $key => $pattern) {
            if (preg_match($pattern, $response, $matches)) {
                $data[$key] = trim($matches[1], ' "\',.:;');
            }
        }

        return $data;
    }

    /**
     * Validate and clean extracted data
     */
    private function validateAndCleanExtractedData($data)
    {
        $requiredKeys = ['symptoms', 'medical_history', 'physical_findings', 'medications', 'vital_signs', 'diagnosis', 'care_plan'];
        $cleanedData = [];

        foreach ($requiredKeys as $key) {
            $value = $data[$key] ?? '';
            $cleanedData[$key] = is_string($value) ? trim($value) : '';
        }

        return $cleanedData;
    }

    /**
     * Generate fallback data using basic text analysis
     */
    private function generateFallbackData($transcription)
    {
        $transcription = strtolower($transcription);
        
        $data = [
            'symptoms' => $this->extractKeywords($transcription, ['pain', 'hurt', 'ache', 'fever', 'cough', 'nausea', 'dizzy', 'tired', 'weak', 'shortness', 'breath']),
            'medical_history' => $this->extractKeywords($transcription, ['diabetes', 'hypertension', 'heart', 'surgery', 'allergy', 'asthma', 'cancer']),
            'physical_findings' => $this->extractKeywords($transcription, ['blood pressure', 'temperature', 'heart rate', 'exam', 'examination', 'normal', 'abnormal']),
            'medications' => $this->extractKeywords($transcription, ['medication', 'medicine', 'drug', 'take', 'prescription', 'pill', 'tablet']),
            'vital_signs' => $this->extractKeywords($transcription, ['blood pressure', 'pulse', 'temperature', 'weight', 'bpm', 'mmhg']),
            'diagnosis' => '',
            'care_plan' => ''
        ];

        // Add diagnosis if medical keywords found
        if (!empty($data['symptoms']) || !empty($data['medical_history'])) {
            $data['diagnosis'] = 'Pending detailed analysis based on symptoms and history';
        }

        return $data;
    }

    /**
     * Extract medical keywords from text
     */
    private function extractKeywords($text, $keywords)
    {
        $found = [];
        foreach ($keywords as $keyword) {
            if (strpos($text, $keyword) !== false) {
                $found[] = $keyword;
            }
        }
        
        return !empty($found) ? 'Keywords found: ' . implode(', ', $found) : '';
    }

    public function generateAIAnalysis(Request $request)
    {
        $sessionId = $request->input('sessionId');
        $transcription = $request->input('transcription', '');
        $extractedData = $request->input('extractedData', []);
        $selectedPatient = $request->input('selectedPatient');

        // Input validation
        if (empty($sessionId) || !is_string($sessionId) || strlen($sessionId) > 255) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid session ID provided.'
            ], 400);
        }

        // Sanitize the session ID by removing any potential malicious characters
        $sessionId = preg_replace('/[^a-zA-Z0-9\-]/', '', $sessionId);

        if (empty($sessionId)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid session ID format.'
            ], 400);
        }

        if (empty($transcription)) {
            return response()->json([
                'success' => false,
                'message' => 'No transcription available. Please record some audio first.'
            ]);
        }

        if (!$selectedPatient || !is_numeric($selectedPatient) || $selectedPatient <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Please select a valid patient first.'
            ]);
        }

        try {
            // Verify that the session belongs to the current authenticated doctor
            $transcriptionRecord = VoiceTranscription::where('session_id', $sessionId)
                ->where('doctor_id', Auth::id())
                ->first();

            if (!$transcriptionRecord) {
                return response()->json([
                    'success' => false,
                    'message' => 'Session not found or unauthorized access.'
                ], 404);
            }

            // Verify that the patient belongs to the current authenticated doctor
            $patient = User::find($selectedPatient);
            if (!$patient) {
                return response()->json([
                    'success' => false,
                    'message' => 'Patient not found.'
                ], 404);
            }

            $effectiveDoctorId = Auth::user()->getEffectiveDoctorUser()->id ?? Auth::id();

            // Check if patient belongs to doctor either by primary_doctor_id or through appointments
            $hasAccess = false;

            // Check if patient is assigned to this doctor
            if ($patient->primary_doctor_id == $effectiveDoctorId) {
                $hasAccess = true;
            } else {
                // Check if patient has appointments with this doctor
                $hasAccess = $patient->appointments()
                    ->where('doctor_id', $effectiveDoctorId)
                    ->whereIn('status', ['confirmed', 'completed', 'pending'])
                    ->exists();
            }

            if (!$hasAccess) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access to patient.'
                ], 403);
            }

            // Get the user's specialty and use the existing preparePrompt function logic
            $specialty = Auth::user()->setting->specialty ?? 'Internal Medicine';
            $criterion = Auth::user()->setting->criterion ?? 'CDC';

            // Get patient data for AI analysis
            $patientAge = $patient ? $patient->age : null;
            $patientGender = $patient ? $patient->gender : null;

            // Prepare input data similar to the existing OpenAI controller
            $inputData = [
                'patient_name' => $patient ? $patient->name : 'Unknown',
                'patient_age' => $patientAge,
                'patient_gender' => $patientGender,
                'symptoms' => $extractedData['symptoms'] ?? '',
                'past_medical_history' => $extractedData['medical_history'] ?? '',
                'physical_findings' => $extractedData['physical_findings'] ?? '',
                'medication_history' => $extractedData['medications'] ?? '',
                'vital_signs' => $extractedData['vital_signs'] ?? '',
                'chief_complaint' => $extractedData['symptoms'] ?? '',
                'physician_notes' => $extractedData['diagnosis'] ?? '',
                'additional_notes' => $extractedData['care_plan'] ?? '',
            ];

            // OPTIMIZATION: Check cache for similar AI analysis requests
            $analysisCacheKey = 'voice_ai_analysis_' . md5(json_encode($inputData) . $criterion);
            $cachedAnalysis = Cache::get($analysisCacheKey);

            if ($cachedAnalysis) {
                \Log::info('Voice Assistant - Using cached AI analysis result');
                $aiAnalysis = $cachedAnalysis;
            } else {
                // Use the same prompt structure as the existing OpenAI controller
                $prompt = $this->prepareVoicePrompt($inputData, $criterion);

                $response = OpenAI::chat()->create([
                    'model' => 'gpt-4o',
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => $prompt
                        ]
                    ],
                    'temperature' => 0.3,
                ]);

                $aiAnalysis = $response['choices'][0]['message']['content'] ?? '';

                // Cache successful analysis for 2 hours
                if (!empty($aiAnalysis)) {
                    Cache::put($analysisCacheKey, $aiAnalysis, 7200);
                }
            }

            // Update database - ensure only the owner can update
            VoiceTranscription::where('session_id', $sessionId)
                ->where('doctor_id', Auth::id()) // Ensure only the owner can update
                ->update([
                    'ai_analysis' => $aiAnalysis,
                    'structured_chart' => [
                        'symptoms' => $extractedData['symptoms'] ?? '',
                        'medical_history' => $extractedData['medical_history'] ?? '',
                        'physical_findings' => $extractedData['physical_findings'] ?? '',
                        'medications' => $extractedData['medications'] ?? '',
                        'vital_signs' => $extractedData['vital_signs'] ?? '',
                        'diagnosis' => $extractedData['diagnosis'] ?? '',
                        'care_plan' => $extractedData['care_plan'] ?? '',
                    ]
                ]);

            return response()->json([
                'success' => true,
                'aiAnalysis' => $aiAnalysis,
                'message' => 'AI analysis generated successfully.'
            ]);
        } catch (\Exception $e) {
            \Log::error('AI analysis error: ' . $e->getMessage(), [
                'session_id' => $sessionId,
                'user_id' => Auth::id(),
                'selected_patient' => $selectedPatient
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to generate AI analysis: ' . $e->getMessage()
            ]);
        }
    }

    public function createAiAssistantResult(Request $request)
    {
        $selectedPatient = $request->input('selectedPatient');
        $aiAnalysis = $request->input('aiAnalysis', '');
        $transcription = $request->input('transcription', '');
        $sessionId = $request->input('sessionId');
        $extractedData = $request->input('extractedData', []);

        // Validate inputs
        if (empty($sessionId) || !is_string($sessionId) || strlen($sessionId) > 255) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid session ID provided.'
            ], 400);
        }

        // Sanitize the session ID by removing any potential malicious characters
        $sessionId = preg_replace('/[^a-zA-Z0-9\-]/', '', $sessionId);

        if (empty($sessionId)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid session ID format.'
            ], 400);
        }

        if (!$selectedPatient || !is_numeric($selectedPatient) || $selectedPatient <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Please select a valid patient first.'
            ]);
        }

        if (empty($aiAnalysis)) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot create AI result without AI analysis.'
            ]);
        }

        // Verify that the patient belongs to the current authenticated doctor
        $patient = User::find($selectedPatient);
        if (!$patient) {
            return response()->json([
                'success' => false,
                'message' => 'Patient not found.'
            ], 404);
        }

        $effectiveDoctorId = Auth::user()->getEffectiveDoctorUser()->id ?? Auth::id();

        // Check if patient belongs to doctor either by primary_doctor_id or through appointments
        $hasAccess = false;

        // Check if patient is assigned to this doctor
        if ($patient->primary_doctor_id == $effectiveDoctorId) {
            $hasAccess = true;
        } else {
            // Check if patient has appointments with this doctor
            $hasAccess = $patient->appointments()
                ->where('doctor_id', $effectiveDoctorId)
                ->whereIn('status', ['confirmed', 'completed', 'pending'])
                ->exists();
        }

        if (!$hasAccess) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to patient.'
            ], 403);
        }

        // Verify that the session belongs to the current authenticated doctor
        $transcriptionRecord = VoiceTranscription::where('session_id', $sessionId)
            ->where('doctor_id', Auth::id())
            ->first();

        if (!$transcriptionRecord) {
            return response()->json([
                'success' => false,
                'message' => 'Session not found or unauthorized access.'
            ], 404);
        }

        try {
            // Create AI assistant result record
            $aiResult = AiAssistantResult::create([
                'doctor_id' => Auth::id(),
                'patient_id' => $selectedPatient,
                'source' => 'voice_assistant',
                'ai_analysis' => $aiAnalysis,
                'voice_transcript' => $transcription,
                'session_id' => $sessionId,
                'patient_data' => [
                    'symptoms' => $extractedData['symptoms'] ?? '',
                    'medical_history' => $extractedData['medical_history'] ?? '',
                    'physical_findings' => $extractedData['physical_findings'] ?? '',
                    'medications' => $extractedData['medications'] ?? '',
                    'vital_signs' => $extractedData['vital_signs'] ?? '',
                    'care_plan' => $extractedData['care_plan'] ?? '',
                    'session_id' => $sessionId,
                ],
                'status' => 'pending',
            ]);

            // Update the voice transcription record
            VoiceTranscription::where('session_id', $sessionId)
                ->where('doctor_id', Auth::id()) // Ensure only the owner can update
                ->update([
                    'ai_assistant_result_id' => $aiResult->id,
                    'status' => 'ai_analysis_complete',
                ]);

            return response()->json([
                'success' => true,
                'aiResultId' => $aiResult->id,
                'message' => 'AI analysis completed! Now write your professional diagnosis.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create AI result: ' . $e->getMessage()
            ]);
        }
    }

    public function createManualDiagnosis(Request $request)
    {
        $manualDiagnosisText = $request->input('manualDiagnosisText', '');
        $aiResultId = $request->input('aiResultId');
        $selectedPatient = $request->input('selectedPatient');
        $transcription = $request->input('transcription', '');
        $sessionId = $request->input('sessionId');
        $extractedData = $request->input('extractedData', []);

        // Validate inputs
        if (empty($manualDiagnosisText)) {
            return response()->json([
                'success' => false,
                'message' => 'Please enter your diagnosis text.'
            ]);
        }

        if (!empty($sessionId)) {
            if (!is_string($sessionId) || strlen($sessionId) > 255) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid session ID provided.'
                ], 400);
            }

            // Sanitize the session ID by removing any potential malicious characters
            $sessionId = preg_replace('/[^a-zA-Z0-9\-]/', '', $sessionId);

            if (empty($sessionId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid session ID format.'
                ], 400);
            }
        }

        if (!$selectedPatient || !is_numeric($selectedPatient) || $selectedPatient <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Please select a valid patient first.'
            ]);
        }

        try {
            // Get the AI assistant result if provided
            $aiResult = null;
            if ($aiResultId) {
                $aiResult = AiAssistantResult::where('id', $aiResultId)
                    ->where('doctor_id', Auth::id())
                    ->first();

                if (!$aiResult) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Unauthorized access to AI assistant result.'
                    ], 403);
                }
            }

            // Get the patient
            $patient = User::findOrFail($selectedPatient);

            // Verify that the patient belongs to the current authenticated doctor
            $effectiveDoctorId = Auth::user()->getEffectiveDoctorUser()->id ?? Auth::id();

            // Check if patient belongs to doctor either by primary_doctor_id or through appointments
            $hasAccess = false;

            // Check if patient is assigned to this doctor
            if ($patient->primary_doctor_id == $effectiveDoctorId) {
                $hasAccess = true;
            } else {
                // Check if patient has appointments with this doctor
                $hasAccess = $patient->appointments()
                    ->where('doctor_id', $effectiveDoctorId)
                    ->whereIn('status', ['confirmed', 'completed', 'pending'])
                    ->exists();
            }

            if (!$hasAccess) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access to patient.'
                ], 403);
            }

            // Prepare patient data - use AI result data if available, otherwise use extracted data
            $patientData = $aiResult ? $aiResult->patient_data : $extractedData;

            // Create the manual diagnosis
            $diagnosis = Diagnosis::create([
                'doctor_id' => Auth::id(),
                'patient_id' => $patient->id,
                'type' => 'voice_assistant',
                'diagnosis_text' => $manualDiagnosisText,
                'voice_transcript' => $transcription,
                'patient_data' => $patientData,
            ]);

            // Link the AI result to this diagnosis if AI result exists
            if ($aiResult) {
                $aiResult->linkToDiagnosis($diagnosis->id);
            }

            // Update the voice transcription record only if session ID is provided
            if (!empty($sessionId)) {
                VoiceTranscription::where('session_id', $sessionId)
                    ->where('doctor_id', Auth::id()) // Ensure only the owner can update
                    ->update([
                        'diagnosis_id' => $diagnosis->id,
                        'status' => 'diagnosis_created',
                    ]);
            }

            // Send voice transcription completion notifications
            $this->sendVoiceTranscriptionNotifications($diagnosis, $transcription);

            $message = $aiResult
                ? 'Manual diagnosis created successfully and linked to AI analysis! Patient can now view it from their account.'
                : 'Manual diagnosis created successfully! Patient can now view it from their account.';

            return response()->json([
                'success' => true,
                'diagnosisId' => $diagnosis->id,
                'message' => $message,
                'redirectUrl' => route('diagnosis.show', $diagnosis)
            ]);

        } catch (\Exception $e) {
            \Log::error('Manual diagnosis creation failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to create diagnosis: ' . $e->getMessage()
            ]);
        }
    }

    public function createNewPatient(Request $request)
    {
        $request->validate([
            'newPatientName' => 'required|string|max:255',
            'newPatientEmail' => 'required|email|unique:users,email',
            'newPatientAge' => 'required|integer|min:1|max:150',
            'newPatientGender' => 'required|in:male,female,other',
            'newPatientPhone' => 'nullable|string|max:20',
        ]);

        try {
            // Generate a secure random password for the new patient
            $temporaryPassword = Str::random(16); // Generate a 16-character random password

            // Create new patient user
            $patient = User::create([
                'name' => $request->input('newPatientName'),
                'email' => $request->input('newPatientEmail'),
                'password' => Hash::make($temporaryPassword), // Secure random password
                'role' => 'patient',
                'age' => $request->input('newPatientAge'),
                'gender' => $request->input('newPatientGender'),
                'phone' => $request->input('newPatientPhone'),
                'primary_doctor_id' => Auth::id(), // Assign current doctor as primary
                'email_verified_at' => now(), // Auto-verify for doctor-created accounts
                'requires_password_reset' => true, // Require password change on first login
                'date_of_birth' => null, // Will be calculated if needed later
            ]);

            return response()->json([
                'success' => true,
                'patient' => [
                    'id' => $patient->id,
                    'name' => $patient->name,
                    'email' => $patient->email,
                    'age' => $patient->age,
                    'gender' => $patient->gender,
                ],
                'temporaryPassword' => $temporaryPassword, // Include temporary password for doctor to share
                'message' => 'New patient created successfully! Temporary password is "' . $temporaryPassword . '" - please inform the patient to change it on first login.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create patient: ' . $e->getMessage()
            ]);
        }
    }

    public function resetSession()
    {
        $sessionId = Str::uuid()->toString();

        return response()->json([
            'success' => true,
            'sessionId' => $sessionId,
            'message' => 'Session reset successfully.'
        ]);
    }

    private function prepareVoicePrompt($inputData, $criterion)
    {
        // Get the user's specialty
        $specialty = Auth::user()->setting->specialty ?? 'Internal Medicine';

        $specialtyInstruction = "You are a senior consultant physician specialized in {$specialty} with 20+ years of clinical experience. Your expertise in this field should guide your analysis and recommendations.

        As a {$specialty} specialist:
        1. Prioritize diagnoses that are most relevant to your specialty, with special attention to life-threatening conditions
        2. Provide specialty-specific insights that a general practitioner might miss
        3. Recommend specialized tests and procedures appropriate for your field
        4. Suggest evidence-based treatment approaches that reflect current best practices in {$specialty}
        5. Highlight any red flags or warning signs particularly important in your specialty
        6. Use precise medical terminology and references that would be familiar to specialists in your field
        7. Be precise, specific, and actionable in your recommendations, as expected from a specialist

        Focus particularly on aspects of the case that relate to your specialty, but maintain a holistic view of the patient's condition.";

        return "You are MedCuraAI, an advanced clinical decision support system powered by cutting-edge artificial intelligence. You function as a senior attending physician with 25+ years of clinical experience across multiple specialties, board certifications, and extensive research background. Your role is to provide comprehensive, evidence-based medical analysis that rivals the expertise of top-tier academic medical centers.

        🎯 CRITICAL CLINICAL MANDATE:
        Your analysis must demonstrate the highest standards of medical practice, incorporating:
        - Evidence-based medicine principles with current clinical guidelines
        - Systematic clinical reasoning using established diagnostic frameworks
        - Risk stratification and patient safety prioritization above all else
        - Never downplay serious symptoms or be overly reassuring
        - Use medical terminology for doctors while remaining clear and structured
        - Never hallucinate facts - only base output on input data or medically standard information

        $specialtyInstruction

        🔶 MANDATORY OUTPUT FORMAT:
        You MUST return your analysis in exactly TWO levels as specified below:

        🟢 LEVEL 1: QUICK CLINICAL SUMMARY

        📋 PATIENT SUMMARY:
        Name: {$inputData['patient_name']} | Age: " . ($inputData['patient_age'] ?? 'N/A') . " | Gender: " . ($inputData['patient_gender'] ?? 'N/A') . "
        Key Symptoms: {$inputData['symptoms']}
        Relevant History: {$inputData['past_medical_history']}
        Physical Findings: {$inputData['physical_findings']}
        Current Medications: {$inputData['medication_history']}
        Vital Signs: {$inputData['vital_signs']}

        🚨 CASE URGENCY:
        **{EMERGENCY / URGENT / ROUTINE}**
        {One-line justification for triage level}

        🔍 TOP 3 DIFFERENTIAL DIAGNOSES:
        | Rank | Diagnosis | Probability (%) | Clinical Reasoning |
        |------|-----------|-----------------|-------------------|
        | 1 | {Primary diagnosis} | {%} | {Key supporting evidence} |
        | 2 | {Secondary diagnosis} | {%} | {Key supporting evidence} |
        | 3 | {Tertiary diagnosis} | {%} | {Key supporting evidence} |

        🧪 RECOMMENDED TESTS:
        • {Test 1} - {Brief rationale}
        • {Test 2} - {Brief rationale}
        • {Test 3} - {Brief rationale}

        💊 INITIAL MANAGEMENT PLAN:
        **Immediate Actions:**
        • {Action 1} - {Brief rationale}
        • {Action 2} - {Brief rationale}

        **Medications:**
        • {Drug} {dose} {route} {frequency} - {indication}

        **Referrals:**
        • {Specialty} - {urgency and reason}

        ⚠️ WARNING SIGNS:
        • {Red flag 1} - {action required}
        • {Red flag 2} - {action required}

        ---

        🔵 DETAILED MEDICAL REPORT (Click to Expand)

        **COMPREHENSIVE PATHOPHYSIOLOGICAL ANALYSIS:**
        {Detailed explanation of underlying disease mechanisms and clinical reasoning}

        **ADVANCED DIFFERENTIAL DIAGNOSIS:**
        {Extended differential with Bayesian analysis, likelihood ratios, and detailed clinical evidence}

        **COMPREHENSIVE DIAGNOSTIC WORKUP:**

        **Laboratory Studies:**
        • {Test name} - {Clinical indication, expected findings, interpretation guidelines}

        **Imaging Studies:**
        • {Imaging modality} - {Clinical indication, expected findings, limitations}

        **DETAILED PHARMACOLOGICAL MANAGEMENT:**

        **Primary Medications:**
        • {Drug name} {dose} {route} {frequency}
          - Indication: {specific indication}
          - Mechanism: {brief pharmacology}
          - Monitoring: {required monitoring parameters}
          - Contraindications: {relevant contraindications}
          - Duration: {treatment duration}

        **MULTIDISCIPLINARY CARE PLAN:**

        **Specialist Consultations:**
        • {Specialty} - {Indication, urgency, specific questions}

        **Follow-up Strategy:**
        • Immediate (24-48 hours): {specific instructions}
        • Short-term (1-2 weeks): {follow-up requirements}
        • Long-term: {ongoing care coordination}

        **PROGNOSTIC ASSESSMENT:**
        {Short and long-term prognosis with influencing factors}

        **EVIDENCE-BASED REFERENCES:**
        1. {Guideline name} - {Organization, year, specific recommendations}
        2. {Additional guidelines with clinical relevance}

        CRITICAL INSTRUCTION: Base your entire analysis on the comprehensive clinical data provided. If data is missing, acknowledge it briefly but don't let it overwhelm the output. Prioritize patient safety above all else.

        PATIENT DATA FOR ANALYSIS: " . json_encode($inputData);
    }

    /**
     * Complete an appointment with diagnosis and doctor notes
     */
    public function completeAppointmentWithDiagnosis(Request $request)
    {
        $request->validate([
            'appointment_id' => 'required|exists:appointments,id',
            'diagnosis_id' => 'required|exists:diagnoses,id',
            'doctor_notes' => 'nullable|string|max:5000',
        ]);

        try {
            $appointment = \App\Models\Appointment::findOrFail($request->appointment_id);
            $diagnosis = \App\Models\Diagnosis::findOrFail($request->diagnosis_id);

            // Ensure the appointment belongs to the authenticated doctor
            if ($appointment->doctor_id !== Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access to appointment.'
                ], 403);
            }

            // Ensure the diagnosis belongs to the authenticated doctor
            if ($diagnosis->doctor_id !== Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access to diagnosis.'
                ], 403);
            }

            // Ensure the appointment and diagnosis are for the same patient
            if ($appointment->patient_id !== $diagnosis->patient_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Appointment and diagnosis must be for the same patient.'
                ], 400);
            }

            // Update appointment status and add doctor notes
            $appointment->update([
                'status' => 'completed',
                'doctor_notes' => $request->doctor_notes,
                'completed_at' => now(),
            ]);

            // Link the diagnosis to the appointment
            // Check if appointments table has diagnosis_id field, if not, use notes
            if (Schema::hasColumn('appointments', 'diagnosis_id')) {
                $appointment->update(['diagnosis_id' => $diagnosis->id]);
            } else {
                // Fallback: store the diagnosis reference in the appointment notes
                $existingNotes = $appointment->doctor_notes ?? '';
                $diagnosisLink = "\n\n--- Diagnosis Reference ---\nDiagnosis ID: {$diagnosis->id}\nCreated: {$diagnosis->created_at->format('M j, Y g:i A')}\nLink: " . route('diagnosis.show', $diagnosis);

                $appointment->update([
                    'doctor_notes' => $existingNotes . $diagnosisLink,
                ]);
            }

            // Send notifications about appointment completion
            $this->sendAppointmentCompletionNotifications($appointment, $diagnosis);

            return redirect()->route('doctor.appointments.completed', $appointment)
                ->with('success', 'Appointment completed successfully with diagnosis linked. Review the completion summary below.');

        } catch (\Exception $e) {
            \Log::error('Appointment completion failed: ' . $e->getMessage(), [
                'appointment_id' => $request->appointment_id,
                'diagnosis_id' => $request->diagnosis_id,
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to complete appointment: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send notifications for voice transcription completion
     */
    private function sendVoiceTranscriptionNotifications(Diagnosis $diagnosis, string $transcription)
    {
        try {
            // Verify that the diagnosis belongs to the current authenticated doctor
            if ($diagnosis->doctor_id !== Auth::id()) {
                \Log::warning('Unauthorized access attempt to send notifications for diagnosis', [
                    'diagnosis_id' => $diagnosis->id,
                    'diagnosis_doctor_id' => $diagnosis->doctor_id,
                    'current_user_id' => Auth::id()
                ]);
                return; // Don't send notifications for unauthorized diagnosis
            }

            // Send notification to patient about new voice diagnosis
            if ($diagnosis->patient && $diagnosis->patient->wantsNotification('voice_transcription_completed')) {
                // Get the voice transcription record to pass to the notification
                $voiceTranscription = VoiceTranscription::where('session_id', $diagnosis->voice_transcript ? json_decode($diagnosis->voice_transcript, true)['session_id'] ?? null : null)->first();

                if ($voiceTranscription) {
                    // Verify that the transcription also belongs to the current doctor
                    if ($voiceTranscription->doctor_id === Auth::id()) {
                        $diagnosis->patient->notifyIfWants(new \App\Notifications\VoiceTranscriptionCompletedNotification($voiceTranscription));
                    }
                }
            }

            // Send notification to doctor about voice transcription completion
            if ($diagnosis->doctor && $diagnosis->doctor->user) {
                $doctor = $diagnosis->doctor->user;

                // Only send notification to the current authenticated doctor
                if ($doctor->id === Auth::id() && $doctor->wantsNotification('voice_transcription_completed')) {
                    $doctor->notifyIfWants(new \App\Notifications\SystemAlertNotification(
                        'Voice Diagnosis Completed',
                        "Voice transcription diagnosis completed for patient {$diagnosis->patient->name}. Diagnosis ID: {$diagnosis->id}",
                        'success',
                        [
                            'link' => route('diagnosis.show', $diagnosis),
                            'link_text' => 'View Diagnosis',
                            'related_type' => 'diagnosis',
                            'related_id' => $diagnosis->id
                        ]
                    ));
                }
            }

        } catch (\Exception $e) {
            // Log notification errors but don't break the diagnosis process
            \Log::error('Failed to send voice transcription notifications: ' . $e->getMessage());
        }
    }

    /**
     * Save diagnosis and optionally complete appointment
     */
    public function saveDiagnosisAndComplete(Request $request)
    {
        $request->validate([
            'diagnosisText' => 'required|string|max:10000',
            'selectedPatient' => 'required|exists:users,id',
            'transcription' => 'required|string|max:50000',
            'sessionId' => 'required|string',
            'completionType' => 'required|in:save_only,complete_appointment',
            'appointmentId' => 'nullable|exists:appointments,id',
            'doctorNotes' => 'nullable|string|max:5000',
        ]);

        try {
            // Get the patient
            $patient = User::findOrFail($request->selectedPatient);

            // Ensure the patient belongs to the authenticated doctor or has confirmed appointments with them
            $isAssignedPatient = Auth::user()->canAccessPatient($patient);
            if (!$isAssignedPatient) {
                return response()->json([
                    'success' => false,
                    'message' => 'Patient not assigned to this doctor or no confirmed appointments exist.'
                ], 403);
            }

            // Create the diagnosis record
            $diagnosis = Diagnosis::create([
                'doctor_id' => Auth::id(),
                'patient_id' => $patient->id,
                'type' => 'voice_assistant',
                'diagnosis_text' => $request->diagnosisText,
                'voice_transcript' => $request->transcription,
                'patient_data' => [
                    'transcription' => $request->transcription,
                    'session_id' => $request->sessionId,
                    'completion_type' => $request->completionType,
                ],
            ]);

            // Update the voice transcription record - add authorization check
            VoiceTranscription::where('session_id', $request->sessionId)
                ->where('doctor_id', Auth::id()) // Ensure only the owner can update
                ->update([
                    'diagnosis_id' => $diagnosis->id,
                    'status' => 'diagnosis_created',
                ]);

            // Prepare success response
            $message = 'Diagnosis saved successfully!';
            $redirectUrl = route('diagnosis.show', $diagnosis);

            // Handle appointment completion if requested
            if ($request->completionType === 'complete_appointment' && $request->appointmentId) {
                try {
                    $appointment = \App\Models\Appointment::findOrFail($request->appointmentId);

                    // Debug logging
                    Log::info('Voice Assistant - Appointment validation', [
                        'appointment_id' => $appointment->id,
                        'appointment_doctor_id' => $appointment->doctor_id,
                        'appointment_patient_id' => $appointment->patient_id,
                        'auth_id' => Auth::id(),
                        'effective_doctor_id' => $effectiveDoctorId,
                        'patient_id' => $patient->id,
                        'appointment_status' => $appointment->status,
                        'appointment_date' => $appointment->appointment_date
                    ]);

                    // Ensure the appointment belongs to the authenticated doctor (more flexible)
                    $appointmentDoctorId = $appointment->doctor_id;
                    $isAppointmentDoctor = $appointmentDoctorId === Auth::id() ||
                                         $appointmentDoctorId === $effectiveDoctorId ||
                                         (Auth::user()->doctor && $appointmentDoctorId === Auth::user()->doctor->id);

                    if (!$isAppointmentDoctor) {
                        Log::warning('Voice Assistant - Appointment doctor authorization failed', [
                            'appointment_id' => $appointment->id,
                            'appointment_doctor_id' => $appointment->doctor_id,
                            'auth_id' => Auth::id(),
                            'effective_doctor_id' => $effectiveDoctorId,
                            'user_doctor_id' => Auth::user()->doctor ? Auth::user()->doctor->id : 'null'
                        ]);

                        return response()->json([
                            'success' => false,
                            'message' => 'Unauthorized access to appointment.'
                        ], 403);
                    }

                    // Ensure the appointment is for the same patient
                    if ($appointment->patient_id !== $patient->id) {
                        Log::warning('Voice Assistant - Appointment patient mismatch', [
                            'appointment_id' => $appointment->id,
                            'appointment_patient_id' => $appointment->patient_id,
                            'diagnosis_patient_id' => $patient->id
                        ]);

                        return response()->json([
                            'success' => false,
                            'message' => 'Appointment and diagnosis must be for the same patient.'
                        ], 400);
                    }

                    // Update appointment status and add doctor notes
                    $updateData = [
                        'status' => 'completed',
                        'completed_at' => now(),
                        'diagnosis_id' => $diagnosis->id,
                    ];

                    // Only add doctor notes if provided
                    if ($request->has('doctorNotes') && !empty($request->doctorNotes)) {
                        $updateData['doctor_notes'] = $request->doctorNotes;
                    }

                    $appointment->update($updateData);

                    // Send appointment completion notifications
                    $this->sendAppointmentCompletionNotifications($appointment, $diagnosis);

                    $message = 'Diagnosis saved and appointment completed successfully!';
                } catch (\Exception $appointmentException) {
                    Log::error('Voice Assistant - Appointment completion failed', [
                        'appointment_id' => $request->appointmentId,
                        'error' => $appointmentException->getMessage()
                    ]);

                    return response()->json([
                        'success' => false,
                        'message' => 'Failed to complete appointment: ' . $appointmentException->getMessage()
                    ], 500);
                }
            }

            // Send voice transcription completion notifications
            $this->sendVoiceTranscriptionNotifications($diagnosis, $request->transcription);

            // If appointment was completed, redirect to completion page
            if ($request->completionType === 'complete_appointment' && $request->appointmentId) {
                $appointment = \App\Models\Appointment::findOrFail($request->appointmentId);
                return redirect()->route('doctor.appointments.completed', $appointment)
                    ->with('success', $message . ' Review the completion summary below.');
            }

            return response()->json([
                'success' => true,
                'diagnosisId' => $diagnosis->id,
                'message' => $message,
                'redirectUrl' => $redirectUrl
            ]);

        } catch (\Exception $e) {
            \Log::error('Diagnosis save and complete failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to save diagnosis: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send notifications for appointment completion
     */
    private function sendAppointmentCompletionNotifications(\App\Models\Appointment $appointment, Diagnosis $diagnosis)
    {
        try {
            // Send notification to patient about appointment completion
            if ($appointment->patient && $appointment->patient->wantsNotification('appointment_completed')) {
                $appointment->patient->notifyIfWants(new \App\Notifications\SystemAlertNotification(
                    'Appointment Completed',
                    "Your appointment on {$appointment->appointment_date->format('M j, Y g:i A')} has been completed. Diagnosis and notes have been added.\n\n" .
                    "🔍 **Next Steps:**\n" .
                    "• View your AI analytics insights\n" .
                    "• Check prescription management\n" .
                    "• Review completion summary",
                    'success',
                    [
                        'link' => route('appointments.show', $appointment->id),
                        'link_text' => 'View Appointment',
                        'related_type' => 'appointment',
                        'related_id' => $appointment->id,
                        'additional_links' => [
                            [
                                'url' => route('doctor.analytics.index'),
                                'text' => 'View AI Analytics',
                                'icon' => 'fas fa-brain'
                            ],
                            [
                                'url' => route('doctor.appointments.show', $appointment->id) . '#prescriptions',
                                'text' => 'Manage Prescriptions',
                                'icon' => 'fas fa-prescription-bottle'
                            ],
                            [
                                'url' => route('doctor.appointments.completed', $appointment->id),
                                'text' => 'Completion Summary',
                                'icon' => 'fas fa-clipboard-check'
                            ]
                        ]
                    ]
                ));
            }

            // Send notification to doctor about appointment completion
            if ($appointment->doctor && $appointment->doctor->user) {
                $doctor = $appointment->doctor->user;

                if ($doctor->wantsNotification('appointment_completed')) {
                    $doctor->notifyIfWants(new \App\Notifications\SystemAlertNotification(
                        'Appointment Completed',
                        "Appointment completed for patient {$appointment->patient->name}. Diagnosis linked and notes added.\n\n" .
                        "🔍 **AI Features Available:**\n" .
                        "• Review AI analytics insights\n" .
                        "• Manage patient prescriptions\n" .
                        "• Access completion summary",
                        'success',
                        [
                            'link' => route('appointments.show', $appointment->id),
                            'link_text' => 'View Appointment',
                            'related_type' => 'appointment',
                            'related_id' => $appointment->id,
                            'additional_links' => [
                                [
                                    'url' => route('doctor.analytics.index'),
                                    'text' => 'AI Analytics Dashboard',
                                    'icon' => 'fas fa-chart-line'
                                ],
                                [
                                    'url' => route('doctor.appointments.show', $appointment->id) . '#prescriptions',
                                    'text' => 'Prescription Management',
                                    'icon' => 'fas fa-prescription-bottle-medical'
                                ],
                                [
                                    'url' => route('doctor.appointments.completed', $appointment->id),
                                    'text' => 'Completion Summary',
                                    'icon' => 'fas fa-file-medical'
                                ]
                            ]
                        ]
                    ));
                }
            }

        } catch (\Exception $e) {
            // Log notification errors but don't break the appointment completion process
            \Log::error('Failed to send appointment completion notifications: ' . $e->getMessage());
        }
    }

    /**
     * Complete consultation with diagnosis (unified method)
     */
    public function completeConsultation(Request $request)
    {
        $request->validate([
            'diagnosisText' => 'required|string|max:10000',
            'selectedPatient' => 'required|exists:users,id',
            'transcription' => 'required|string|max:50000',
            'sessionId' => 'required|string',
            'completionType' => 'required|in:save_only,complete_appointment',
            'appointmentId' => 'nullable|exists:appointments,id',
            'doctorNotes' => 'nullable|string|max:5000',
            'aiResultId' => 'nullable|exists:ai_assistant_results,id',
            'extractedData' => 'nullable|array',
        ]);

        try {
            // Get the patient
            $patient = User::findOrFail($request->selectedPatient);

            // Debug logging
            $effectiveDoctorId = Auth::user()->parent_user_id ? Auth::user()->parent_user_id : Auth::id();
            Log::info('Voice Assistant - Complete consultation debug', [
                'patient_id' => $patient->id,
                'patient_name' => $patient->name,
                'patient_primary_doctor_id' => $patient->primary_doctor_id,
                'effective_doctor_id' => $effectiveDoctorId,
                'auth_id' => Auth::id(),
                'appointment_id' => $request->appointmentId,
                'completion_type' => $request->completionType
            ]);

            // Ensure the patient belongs to the authenticated doctor or has confirmed appointments with them
            $isAssignedPatient = Auth::user()->canAccessPatient($patient);

            if (!$isAssignedPatient) {
                Log::warning('Voice Assistant - Patient assignment check failed', [
                    'patient_id' => $patient->id,
                    'patient_primary_doctor_id' => $patient->primary_doctor_id,
                    'effective_doctor_id' => $effectiveDoctorId,
                    'auth_id' => Auth::id()
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Patient not assigned to this doctor or no confirmed appointments exist.'
                ], 403);
            }

            // Get AI result if provided
            $aiResult = null;
            if ($request->aiResultId) {
                $aiResult = \App\Models\AiAssistantResult::findOrFail($request->aiResultId);
                if ($aiResult->doctor_id !== Auth::id()) {
                    $aiResult = null; // Don't use AI result if it doesn't belong to this doctor
                }
            }

            // Prepare patient data - use AI result data if available, otherwise use extracted data
            $patientData = $aiResult ? $aiResult->patient_data : ($request->extractedData ?? []);

            // Create the diagnosis record
            $diagnosis = Diagnosis::create([
                'doctor_id' => Auth::id(),
                'patient_id' => $patient->id,
                'type' => 'voice_assistant',
                'diagnosis_text' => $request->diagnosisText,
                'voice_transcript' => $request->transcription,
                'patient_data' => $patientData,
            ]);

            // Link the AI result to this diagnosis if AI result exists
            if ($aiResult) {
                $aiResult->linkToDiagnosis($diagnosis->id);
            }

            // Update the voice transcription record - add authorization check
            VoiceTranscription::where('session_id', $request->sessionId)
                ->where('doctor_id', Auth::id()) // Ensure only the owner can update
                ->update([
                    'diagnosis_id' => $diagnosis->id,
                    'status' => 'diagnosis_created',
                ]);

            // Prepare success response
            $message = 'Diagnosis saved successfully!';
            $redirectUrl = route('diagnosis.show', $diagnosis);

            // Handle appointment completion if requested
            if ($request->completionType === 'complete_appointment' && $request->appointmentId) {
                try {
                    $appointment = \App\Models\Appointment::findOrFail($request->appointmentId);
                    
                    // Debug logging
                    Log::info('Voice Assistant - Appointment validation', [
                        'appointment_id' => $appointment->id,
                        'appointment_doctor_id' => $appointment->doctor_id,
                        'appointment_patient_id' => $appointment->patient_id,
                        'auth_id' => Auth::id(),
                        'effective_doctor_id' => $effectiveDoctorId,
                        'patient_id' => $patient->id,
                        'appointment_status' => $appointment->status,
                        'appointment_date' => $appointment->appointment_date
                    ]);

                    // Ensure the appointment belongs to the authenticated doctor (more flexible)
                    $appointmentDoctorId = $appointment->doctor_id;
                    $isAppointmentDoctor = $appointmentDoctorId === Auth::id() || 
                                         $appointmentDoctorId === $effectiveDoctorId ||
                                         (Auth::user()->doctor && $appointmentDoctorId === Auth::user()->doctor->id);
                    
                    if (!$isAppointmentDoctor) {
                        Log::warning('Voice Assistant - Appointment doctor authorization failed', [
                            'appointment_id' => $appointment->id,
                            'appointment_doctor_id' => $appointment->doctor_id,
                            'auth_id' => Auth::id(),
                            'effective_doctor_id' => $effectiveDoctorId,
                            'user_doctor_id' => Auth::user()->doctor ? Auth::user()->doctor->id : 'null'
                        ]);
                        
                        return response()->json([
                            'success' => false,
                            'message' => 'Unauthorized access to appointment.'
                        ], 403);
                    }

                    // Ensure the appointment is for the same patient
                    if ($appointment->patient_id !== $patient->id) {
                        Log::warning('Voice Assistant - Appointment patient mismatch', [
                            'appointment_id' => $appointment->id,
                            'appointment_patient_id' => $appointment->patient_id,
                            'diagnosis_patient_id' => $patient->id
                        ]);
                        
                        return response()->json([
                            'success' => false,
                            'message' => 'Appointment and diagnosis must be for the same patient.'
                        ], 400);
                    }

                    // Update appointment status and add doctor notes
                    $appointment->update([
                        'status' => 'completed',
                        'doctor_notes' => $request->doctorNotes,
                        'completed_at' => now(),
                        'diagnosis_id' => $diagnosis->id,
                    ]);
                    
                    // Send appointment completion notifications
                    $this->sendAppointmentCompletionNotifications($appointment, $diagnosis);
                    
                    $message = 'Diagnosis saved and appointment completed successfully!';
                } catch (\Exception $appointmentException) {
                    Log::error('Voice Assistant - Appointment completion failed', [
                        'appointment_id' => $request->appointmentId,
                        'error' => $appointmentException->getMessage()
                    ]);
                    
                    return response()->json([
                        'success' => false,
                        'message' => 'Failed to complete appointment: ' . $appointmentException->getMessage()
                    ], 500);
                }
            }

            // Send voice transcription completion notifications
            $this->sendVoiceTranscriptionNotifications($diagnosis, $request->transcription);

            return response()->json([
                'success' => true,
                'diagnosisId' => $diagnosis->id,
                'message' => $message,
                'redirectUrl' => $redirectUrl
            ]);

        } catch (\Exception $e) {
            \Log::error('Consultation completion failed: ' . $e->getMessage(), [
                'session_id' => $request->sessionId,
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to complete consultation: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Save diagnosis only without completing an appointment
     */
    public function saveDiagnosisOnly(Request $request)
    {
        $request->validate([
            'diagnosis_id' => 'required|exists:diagnoses,id',
            'doctor_notes' => 'nullable|string|max:5000',
        ]);

        try {
            $diagnosis = \App\Models\Diagnosis::findOrFail($request->diagnosis_id);

            // Ensure the diagnosis belongs to the authenticated doctor
            if ($diagnosis->doctor_id !== Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access to diagnosis.'
                ], 403);
            }

            // Update diagnosis with additional notes if provided
            if ($request->doctor_notes) {
                $existingNotes = $diagnosis->diagnosis_text;
                $diagnosis->update([
                    'diagnosis_text' => $existingNotes . "\n\n--- Additional Notes ---\n" . $request->doctor_notes
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Diagnosis saved successfully!',
                'diagnosis' => [
                    'id' => $diagnosis->id,
                    'updated_at' => $diagnosis->updated_at,
                ]
            ]);
    
        } catch (\Exception $e) {
            \Log::error('Diagnosis save failed: ' . $e->getMessage(), [
                'diagnosis_id' => $request->diagnosis_id,
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);
    
            return response()->json([
                'success' => false,
                'message' => 'Failed to save diagnosis: ' . $e->getMessage()
            ], 500);
        }
    }
    
        /**
         * HYBRID METHOD: Process audio file on server for enhanced accuracy
         * This endpoint handles server-side audio processing for better transcription accuracy
         */
        public function processAudioServer(Request $request)
        {
            $startTime = microtime(true);
            $sessionId = $request->input('session_id');
            
            \Log::info("VoiceAssistant: processAudioServer called", [
                'session_id' => $sessionId,
                'has_file' => $request->hasFile('audio_file'),
                'all_input' => $request->except(['audio_file', 'transcription']) // Log inputs except large fields
            ]);

            $transcription = $request->input('transcription', '');
            $language = $request->input('language', 'en'); // Default to English
            $hasLiveTranscription = $request->input('has_live_transcription', false);

            // Validate required parameters
            if (empty($sessionId) || !is_string($sessionId) || strlen($sessionId) > 255) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid session ID provided.'
                ], 400);
            }

            // Sanitize the session ID by removing any potential malicious characters
            $sessionId = preg_replace('/[^a-zA-Z0-9\-]/', '', $sessionId);

            if (empty($sessionId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid session ID format.'
                ], 400);
            }

            // Verify that the session belongs to the current authenticated doctor
            $transcriptionRecord = VoiceTranscription::where('session_id', $sessionId)
                ->where('doctor_id', Auth::id())
                ->first();

            if (!$transcriptionRecord) {
                return response()->json([
                    'success' => false,
                    'message' => 'Session not found or unauthorized access.'
                ], 404);
            }

            // Initialize performance metrics
            $doctorId = Auth::id();
            $metrics = [
                'doctor_id' => $doctorId,
                'session_id' => $sessionId,
                'processing_type' => 'hybrid',
                'live_transcription_success' => !empty($transcription),
                'live_transcript_length' => strlen($transcription),
                'browser_info' => $request->header('User-Agent'),
                'device_type' => $this->detectDeviceType($request),
                'network_type' => $request->input('network_type', 'unknown'),
                'connection_speed' => $request->input('connection_speed'),
            ];

            \Log::info('HYBRID METHOD - Server audio processing started', [
                'session_id' => $sessionId,
                'transcription_length' => isset($transcription) ? strlen($transcription) : 0,
                'has_live_transcription' => $hasLiveTranscription,
                'user_id' => Auth::id(),
                'request_has_file' => $request->hasFile('audio_file')
            ]);

            try {
                // Check if audio file is provided - validate input type
                $hasAudioRecordingInput = $request->input('has_audio_recording', false);
                $hasAudioRecording = filter_var($hasAudioRecordingInput, FILTER_VALIDATE_BOOLEAN);

                if (!$request->hasFile('audio_file')) {
                    \Log::warning("VoiceAssistant: No audio file in request", ['session_id' => $sessionId]);
                    if ($hasAudioRecording) {
                        $metrics['error_type'] = 'audio_upload';
                        $metrics['error_message'] = 'Client indicated audio recording exists but no file provided';
                        $this->recordPerformanceMetrics($metrics);

                        return response()->json([
                            'success' => false,
                            'message' => 'Audio file expected but not received'
                        ], 400);
                    } else {
                        // No audio expected, skip processing
                        $metrics['audio_processing_skipped'] = true;
                        $metrics['reason'] = 'No audio recording expected';
                        $this->recordPerformanceMetrics($metrics);

                        return response()->json([
                            'success' => true,
                            'message' => 'No audio processing needed',
                            'improved_transcription' => $transcription,
                            'server_extracted_data' => []
                        ]);
                    }
                }

                $audioFile = $request->file('audio_file');
                \Log::info("VoiceAssistant: Audio file received", [
                    'original_name' => $audioFile->getClientOriginalName(),
                    'mime_type' => $audioFile->getMimeType(),
                    'size' => $audioFile->getSize(),
                    'path' => $audioFile->getPathname()
                ]);

                // Enhanced audio file validation with detailed error reporting
                $validationResult = $this->validateAudioFile($audioFile);
                if (!$validationResult['valid']) {
                    \Log::error("VoiceAssistant: Audio file validation failed", ['errors' => $validationResult['errors']]);
                    $errorMessage = implode('; ', $validationResult['errors']);
                    $metrics['error_type'] = 'audio_validation';
                    $metrics['error_message'] = $errorMessage;
                    $this->recordPerformanceMetrics($metrics);

                    \Log::warning('HYBRID METHOD - Audio file validation failed', [
                        'session_id' => $sessionId,
                        'errors' => $validationResult['errors'],
                        'file_size' => $audioFile ? $audioFile->getSize() : 0,
                        'mime_type' => $audioFile ? $audioFile->getMimeType() : 'unknown',
                        'original_name' => $audioFile ? $audioFile->getClientOriginalName() : 'unknown'
                    ]);

                    return response()->json([
                        'success' => false,
                        'message' => 'Audio file validation failed: ' . $errorMessage,
                        'errors' => $validationResult['errors']
                    ], 400);
                }

                $fileSize = $audioFile->getSize();

                \Log::info('HYBRID METHOD - Audio file validation passed', [
                    'session_id' => $sessionId,
                    'file_size' => $fileSize,
                    'mime_type' => $audioFile->getMimeType(),
                    'original_name' => $audioFile->getClientOriginalName(),
                    'extension' => $audioFile->getClientOriginalExtension()
                ]);
    
                // Record enhanced audio file metrics
                if ($audioFile && $audioFile->getSize() > 0) {
                    $metrics['audio_file_size'] = $audioFile->getSize();
                    $metrics['audio_format'] = $audioFile->getClientOriginalExtension();
                } else {
                    $metrics['audio_file_size'] = 0;
                    $metrics['audio_format'] = 'none';
                }
    
                // Get additional audio quality parameters from request
                $audioQuality = $request->input('audio_quality', []);
                if (!empty($audioQuality)) {
                    $metrics['audio_sample_rate'] = $audioQuality['sample_rate'] ?? null;
                    $metrics['audio_channels'] = $audioQuality['channels'] ?? null;
                    $metrics['average_audio_level'] = $audioQuality['average_level'] ?? null;
                }
    
                // Estimate audio duration from file size and format
                $metrics['audio_duration'] = ($audioFile && $audioFile->getSize() > 0) ? $this->estimateAudioDuration($audioFile) : 0;
    
                // Store audio file permanently and process it
                $serverTranscription = '';
                $permanentPath = null;
                $audioStoredSuccessfully = false;

                if ($audioFile && $fileSize > 0) {
                    try {
                        // Get file size before moving the file (temporary file will be deleted after move)
                        $fileSizeBeforeMove = $audioFile->getSize();

                        $storeStartTime = microtime(true);
                        $permanentPath = $this->storeAudioFilePermanently($audioFile, $sessionId);
                        $storeEndTime = microtime(true);

                        $metrics['audio_storage_success'] = true;
                        $metrics['audio_storage_time'] = round(($storeEndTime - $storeStartTime) * 1000, 3);

                        \Log::info('HYBRID METHOD - Audio file stored successfully', [
                            'session_id' => $sessionId,
                            'permanent_path' => $permanentPath,
                            'storage_time_ms' => $metrics['audio_storage_time']
                        ]);

                        $audioStoredSuccessfully = true;

                        // Update the voice transcription record with audio file path immediately
                        // Use the file size we captured before moving
                        $this->updateVoiceTranscriptionWithAudio($sessionId, $permanentPath, $fileSizeBeforeMove, $audioFile->getClientOriginalExtension(), $metrics['audio_duration']);

                    } catch (\Exception $storageException) {
                        \Log::error('HYBRID METHOD - Audio file storage failed', [
                            'session_id' => $sessionId,
                            'error' => $storageException->getMessage(),
                            'file_size' => $fileSize
                        ]);

                        $metrics['audio_storage_success'] = false;
                        $metrics['audio_storage_error'] = $storageException->getMessage();

                        // Continue with processing even if storage fails - we can still process the audio
                        $permanentPath = null;
                    }

                    // Process audio with server-side speech recognition if we have a stored file
                    $fullAudioPath = storage_path('app/public/' . $permanentPath);
                    \Log::info('VoiceAssistant: Checking if audio file exists for STT', [
                        'permanent_path' => $permanentPath,
                        'full_path' => $fullAudioPath,
                        'exists' => file_exists($fullAudioPath)
                    ]);

                    if ($permanentPath && file_exists($fullAudioPath)) {
                        try {
                            $sttStartTime = microtime(true);
                            $serverTranscription = $this->processAudioWithServerSTT(storage_path('app/public/' . $permanentPath), $language);
                            $sttEndTime = microtime(true);

                            $metrics['server_processing_success'] = !empty($serverTranscription);
                            $metrics['server_processing_time'] = round(($sttEndTime - $sttStartTime) * 1000, 3);
                            $metrics['server_transcript_length'] = strlen($serverTranscription);

                            \Log::info('HYBRID METHOD - Server STT completed', [
                                'session_id' => $sessionId,
                                'transcription_length' => strlen($serverTranscription),
                                'processing_time_ms' => $metrics['server_processing_time'],
                                'transcription_preview' => substr($serverTranscription, 0, 100)
                            ]);

                        } catch (\Exception $sttException) {
                            \Log::error('HYBRID METHOD - Server STT failed', [
                                'session_id' => $sessionId,
                                'error' => $sttException->getMessage(),
                                'permanent_path' => $permanentPath
                            ]);

                            $metrics['server_processing_success'] = false;
                            $metrics['server_processing_error'] = $sttException->getMessage();
                            $serverTranscription = ''; // Ensure empty string for fallback
                        }
                    } else {
                        $metrics['server_processing_success'] = false;
                        $metrics['server_processing_time'] = 0;
                        $metrics['server_transcript_length'] = 0;
                        $metrics['reason'] = 'Audio file not available for server processing';
                    }
                } else {
                    $metrics['server_processing_success'] = false;
                    $metrics['server_processing_time'] = 0;
                    $metrics['server_transcript_length'] = 0;
                    $metrics['reason'] = 'No audio file to process';
                }
    
                // Compare results and return the better one
                $improvedTranscription = $this->selectBestTranscription($transcription, $serverTranscription);
    
                // Calculate improvement metrics
                if (!empty($serverTranscription) && !empty($transcription)) {
                    $metrics['transcript_improvement_ratio'] = round(strlen($serverTranscription) / max(strlen($transcription), 1), 2);
                    $metrics['server_better_than_live'] = strlen($serverTranscription) > strlen($transcription);
                }
    
                // Extract medical data from improved transcription
                $extractionStartTime = microtime(true);
                $serverExtractedData = [];
                if ($improvedTranscription && strlen($improvedTranscription) > 5) {
                    $serverExtractedData = $this->extractMedicalDataFromText($improvedTranscription);
                    $extractionEndTime = microtime(true);
    
                    $metrics['medical_extraction_success'] = !empty($serverExtractedData);
                    $metrics['medical_extraction_time'] = round(($extractionEndTime - $extractionStartTime) * 1000, 3);
    
                    // Count extracted data fields
                    if (is_array($serverExtractedData)) {
                        $metrics['extracted_symptoms_count'] = !empty($serverExtractedData['symptoms']) ? 1 : 0;
                        $metrics['extracted_medical_history_count'] = !empty($serverExtractedData['medical_history']) ? 1 : 0;
                        $metrics['extracted_physical_findings_count'] = !empty($serverExtractedData['physical_findings']) ? 1 : 0;
                        $metrics['extracted_medications_count'] = !empty($serverExtractedData['medications']) ? 1 : 0;
                        $metrics['extracted_vital_signs_count'] = !empty($serverExtractedData['vital_signs']) ? 1 : 0;
                    }
                }
    
                // Calculate overall success and total time
                $endTime = microtime(true);
                $metrics['overall_success'] = $metrics['live_transcription_success'] || $metrics['server_processing_success'];
                $metrics['total_processing_time'] = round(($endTime - $startTime) * 1000, 3);

                // Determine processing status and messages
                $processingStatus = $this->determineProcessingStatus($metrics, $transcription, $serverTranscription, $improvedTranscription);
                $successMessage = $this->generateSuccessMessage($processingStatus, $metrics);

                // Record the performance metrics
                $this->recordPerformanceMetrics($metrics);

                \Log::info('HYBRID METHOD - Server processing completed', [
                    'session_id' => $sessionId,
                    'live_length' => strlen($transcription),
                    'server_length' => strlen($serverTranscription),
                    'improved_length' => strlen($improvedTranscription),
                    'data_extracted' => !empty($serverExtractedData),
                    'processing_time_ms' => $metrics['total_processing_time'],
                    'status' => $processingStatus
                ]);

                // Prepare speaker data for response (enhanced with AI-based speaker detection)
                $speakerData = $this->extractSpeakerDataFromTranscription($improvedTranscription);

                return response()->json([
                    'success' => true,
                    'message' => $successMessage,
                    'improved_transcription' => $improvedTranscription,
                    'server_extracted_data' => $serverExtractedData,
                    'speakers' => $speakerData['speakers'],
                    'medical_terms' => $speakerData['medical_terms'],
                    'processing_method' => $processingStatus['method'],
                    'processing_status' => $processingStatus,
                    'improvement_ratio' => $metrics['transcript_improvement_ratio'] ?? 1,
                    'performance_metrics' => [
                        'processing_time_ms' => $metrics['total_processing_time'],
                        'server_improved' => $metrics['server_better_than_live'] ?? false,
                        'audio_stored' => $audioStoredSuccessfully,
                        'extraction_success' => !empty($serverExtractedData),
                        'speakers_detected' => count($speakerData['speakers']),
                        'medical_terms_found' => count($speakerData['medical_terms'])
                    ]
                ]);
    
            } catch (\Exception $e) {
                $endTime = microtime(true);
                $metrics['overall_success'] = false;
                $metrics['total_processing_time'] = round(($endTime - $startTime) * 1000, 3);
                $metrics['error_type'] = 'server_processing';
                $metrics['error_message'] = 'Internal server error';

                $this->recordPerformanceMetrics($metrics);

                \Log::error('HYBRID METHOD - Server processing failed', [
                    'error' => $e->getMessage(),
                    'session_id' => $request->input('session_id'),
                    'user_id' => Auth::id(),
                    'processing_time_ms' => $metrics['total_processing_time'],
                    'trace' => $e->getTraceAsString()
                ]);

                // Provide fallback response with live transcription if available
                $fallbackTranscription = $transcription ?: '';
                $fallbackMessage = 'Server processing encountered an error. ';

                if (!empty($fallbackTranscription)) {
                    $fallbackMessage .= 'Using live transcription as fallback.';
                } else {
                    $fallbackMessage .= 'No transcription available. Please try recording again.';
                }

                // Try to extract basic medical data from live transcription as fallback
                $fallbackExtractedData = [];
                if (!empty($fallbackTranscription) && strlen($fallbackTranscription) > 5) {
                    try {
                        $fallbackExtractedData = $this->extractMedicalDataFromText($fallbackTranscription);
                        if (!empty($fallbackExtractedData)) {
                            $fallbackMessage .= ' Basic medical data extracted from live transcription.';
                        }
                    } catch (\Exception $fallbackException) {
                        \Log::warning('HYBRID METHOD - Fallback extraction also failed', [
                            'session_id' => $request->input('session_id'),
                            'error' => $fallbackException->getMessage()
                        ]);
                    }
                }

                return response()->json([
                    'success' => false,
                    'message' => $fallbackMessage,
                    'error_details' => 'An internal error occurred during audio processing. Please contact support if the problem persists.',
                    'improved_transcription' => $fallbackTranscription,
                    'server_extracted_data' => $fallbackExtractedData,
                    'fallback_used' => true,
                    'processing_method' => 'fallback'
                ], 500);
            }
        }
    
        /**
         * Validate audio file format and size with detailed error reporting
         */
        private function validateAudioFile($file)
        {
            $errors = [];

            if (!$file->isValid()) {
                \Log::error("VoiceAssistant: File upload failed or corrupted", ['error' => $file->getErrorMessage()]);
                $errors[] = 'File upload failed or file is corrupted';
                return ['valid' => false, 'errors' => $errors];
            }

            $fileSize = $file->getSize();

            // Validate file size constraints
            if ($fileSize === 0) {
                $errors[] = 'Audio file is empty';
                return ['valid' => false, 'errors' => $errors];
            }

            // More reasonable minimum size for audio files (44 bytes for WAV header minimum)
            if ($fileSize < 44) {
                $errors[] = 'Audio file is too small to be valid (minimum 44 bytes)';
                return ['valid' => false, 'errors' => $errors];
            }

            // Maximum size: 100MB for longer recordings
            $maxSize = 100 * 1024 * 1024; // 100MB
            if ($fileSize > $maxSize) {
                $errors[] = 'Audio file is too large (maximum 100MB allowed)';
                return ['valid' => false, 'errors' => $errors];
            }

            $mimeType = $file->getMimeType() ?? '';
            $extension = strtolower($file->getClientOriginalExtension() ?? '');

            // Expanded list of supported audio formats
            $allowedMimeTypes = [
                // Common audio formats
                'audio/wav', 'audio/x-wav',
                'audio/mp3', 'audio/mpeg', 'audio/mpeg3',
                'audio/webm', 'audio/webm;codecs=opus',
                'audio/mp4', 'audio/x-m4a',
                'audio/ogg', 'audio/oga',
                'audio/flac',
                'audio/aac',
                // Video formats that may contain audio
                'video/webm', 'video/mp4',
                // Additional formats
                'application/octet-stream' // For files that might not have proper MIME detection
            ];

            $allowedExtensions = ['wav', 'mp3', 'webm', 'mp4', 'm4a', 'ogg', 'oga', 'flac', 'aac'];

            $mimeTypeValid = in_array($mimeType, $allowedMimeTypes);
            $extensionValid = in_array($extension, $allowedExtensions);

            if (!$mimeTypeValid && !$extensionValid) {
                $errors[] = "Unsupported audio format. MIME type: {$mimeType}, Extension: {$extension}. Supported formats: " . implode(', ', $allowedExtensions);
                return ['valid' => false, 'errors' => $errors];
            }

            // Log warning for MIME/extension mismatch but allow if either is valid
            if (!$mimeTypeValid || !$extensionValid) {
                \Log::warning('Audio file validation warning: MIME/extension mismatch', [
                    'mime_type' => $mimeType,
                    'extension' => $extension,
                    'mime_valid' => $mimeTypeValid,
                    'extension_valid' => $extensionValid
                ]);
            }

            return ['valid' => true, 'errors' => []];
        }

        /**
         * Legacy method for backward compatibility
         */
        private function isValidAudioFile($file)
        {
            $result = $this->validateAudioFile($file);
            return $result['valid'];
        }
    
        /**
         * Store audio file temporarily for processing
         */
        private function storeAudioFile($file, $sessionId)
        {
            // Validate the file extension against an allowlist to prevent malicious file types
            $allowedExtensions = ['mp3', 'wav', 'ogg', 'm4a', 'aac', 'flac', 'mp4', 'webm', '3gp'];
            $originalExtension = strtolower($file->getClientOriginalExtension());

            if (!in_array($originalExtension, $allowedExtensions)) {
                throw new \Exception("Invalid file extension: {$originalExtension}. Allowed extensions: " . implode(', ', $allowedExtensions));
            }

            // Sanitize the filename to prevent directory traversal attacks
            $sessionId = preg_replace('/[^a-zA-Z0-9_-]/', '', $sessionId);
            if (empty($sessionId)) {
                throw new \Exception('Invalid session ID provided');
            }

            $filename = "session_{$sessionId}_" . time() . '.' . $originalExtension;

            // Validate filename to prevent directory traversal
            if (strpos($filename, '..') !== false || strpos($filename, '/') !== false || strpos($filename, '\\') !== false) {
                throw new \Exception('Invalid filename detected');
            }

            // Use Laravel's Storage facade for secure file operations
            $storagePath = 'temp/audio_processing';

            // Validate storage path to prevent directory traversal
            if (strpos($storagePath, '..') !== false || strpos($storagePath, '/') !== false || strpos($storagePath, '\\') !== false) {
                throw new \Exception('Invalid storage path detected');
            }

            // Ensure the directory exists
            if (!\Storage::exists($storagePath)) {
                \Storage::makeDirectory($storagePath);
            }

            // Store the file using Laravel's Storage facade
            try {
                $path = \Storage::putFileAs($storagePath, $file, $filename, 'private');

                if (!$path) {
                    throw new \Exception('Failed to store audio file in temporary storage');
                }

                // Verify the file was actually stored
                if (!\Storage::exists($path)) {
                    throw new \Exception('Audio file storage verification failed');
                }

                // Return the full path for processing
                return storage_path('app/' . $path);

            } catch (\Exception $e) {
                \Log::error('Audio file storage failed', [
                    'session_id' => $sessionId,
                    'filename' => $filename,
                    'error' => $e->getMessage()
                ]);
                throw new \Exception('Failed to store audio file: ' . $e->getMessage());
            }
        }

        /**
         * Store audio file permanently for long-term access
         */
        private function storeAudioFilePermanently($file, $sessionId)
        {
            try {
                \Log::info("VoiceAssistant: Attempting to store audio file", ['session_id' => $sessionId]);
                
                // Validate the file extension against an allowlist to prevent malicious file types
                $allowedExtensions = ['mp3', 'wav', 'ogg', 'm4a', 'aac', 'flac', 'mp4', 'webm', '3gp'];
                $originalExtension = strtolower($file->getClientOriginalExtension());

                if (!in_array($originalExtension, $allowedExtensions)) {
                    throw new \Exception("Invalid file extension: {$originalExtension}. Allowed extensions: " . implode(', ', $allowedExtensions));
                }

                // Sanitize the filename to prevent directory traversal attacks
                $sessionId = preg_replace('/[^a-zA-Z0-9_-]/', '', $sessionId);
                if (empty($sessionId)) {
                    throw new \Exception('Invalid session ID provided');
                }

                $filename = "session_{$sessionId}_" . time() . '_' . uniqid() . '.' . $originalExtension;

                // Validate filename to prevent directory traversal
                if (strpos($filename, '..') !== false || strpos($filename, '/') !== false || strpos($filename, '\\') !== false) {
                    throw new \Exception('Invalid filename detected');
                }

                // Use Laravel's Storage facade for secure file operations
                $storagePath = 'audio/voice_transcriptions';

                // Validate storage path to prevent directory traversal
                if (strpos($storagePath, '..') !== false) {
                    throw new \Exception('Invalid storage path detected');
                }

                // Ensure the directory exists
                if (!\Storage::disk('public')->exists($storagePath)) {
                    \Storage::disk('public')->makeDirectory($storagePath);
                }

                // Store the file using Laravel's Storage facade on the public disk
                $path = \Storage::disk('public')->putFileAs($storagePath, $file, $filename);

                if (!$path) {
                    \Log::error("VoiceAssistant: Failed to store audio file in permanent storage", ['path' => $storagePath, 'filename' => $filename]);
                    return false;
                }
                
                \Log::info("VoiceAssistant: Audio file stored successfully", ['path' => $path]);
                return $path;

            } catch (\Exception $e) {
                \Log::error("VoiceAssistant: Exception during audio storage", ['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
                return false;
            }
        }

        /**
         * Update voice transcription record with audio file information
         */
        private function updateVoiceTranscriptionWithAudio($sessionId, $audioPath, $fileSize, $fileExtension, $estimatedDuration)
        {
            try {
                if (!$audioPath || $audioPath === false) {
                    return false;
                }

                // If it's a relative path from storage, get the absolute path
                if (is_string($audioPath) && strpos($audioPath, 'audio/') === 0) {
                    $audioPath = storage_path('app/public/' . $audioPath);
                }

                // Validate the audio path to prevent path traversal attacks
                $resolvedAudioPath = realpath($audioPath); // Resolve any relative paths
                $storagePath = realpath(storage_path('app')); // Base path for validation
                
                // On Windows, realpath might return different separators, so we normalize
                if ($resolvedAudioPath) {
                    $resolvedAudioPath = str_replace('\\', '/', $resolvedAudioPath);
                }
                if ($storagePath) {
                    $storagePath = str_replace('\\', '/', $storagePath);
                }

                if (!$resolvedAudioPath || strpos($resolvedAudioPath, $storagePath) !== 0) {
                    \Log::error('Invalid audio file path for update', [
                        'audio_path' => $audioPath,
                        'resolved_path' => $resolvedAudioPath,
                        'storage_path' => $storagePath
                    ]);
                    throw new \Exception('Invalid audio file path');
                }

                $audioPath = $resolvedAudioPath;

                $transcriptionRecord = VoiceTranscription::where('session_id', $sessionId)->first();

                if (!$transcriptionRecord) {
                    \Log::warning('HYBRID METHOD - Voice transcription record not found for audio update', [
                        'session_id' => $sessionId
                    ]);
                    return false;
                }

                $updateData = [
                    'audio_file' => $audioPath,
                    'audio_file_size' => $fileSize,
                    'audio_format' => $fileExtension,
                    'audio_duration' => $estimatedDuration,
                    'updated_at' => now()
                ];
    
                $updated = $transcriptionRecord->update($updateData);
    
                if ($updated) {
                    \Log::info('HYBRID METHOD - Voice transcription record updated with audio info', [
                        'session_id' => $sessionId,
                        'record_id' => $transcriptionRecord->id,
                        'audio_path' => $audioPath,
                        'file_size' => $fileSize,
                        'file_extension' => $fileExtension
                    ]);
                } else {
                    \Log::warning('HYBRID METHOD - Failed to update voice transcription record', [
                        'session_id' => $sessionId,
                        'record_id' => $transcriptionRecord->id
                    ]);
                }
    
                return $updated;
    
            } catch (\Exception $e) {
                \Log::error('HYBRID METHOD - Error updating voice transcription with audio', [
                    'session_id' => $sessionId,
                    'error' => $e->getMessage(),
                    'audio_path' => $audioPath
                ]);
                return false;
            }
        }
    
        /**
         * Process audio with advanced medical speech recognition (Google Cloud Speech-to-Text Healthcare)
         */
        private function processAudioWithServerSTT($audioPath, $language = 'en')
        {
            try {
                if (!$audioPath || $audioPath === false) {
                    return '';
                }

                // Validate the audio path to prevent path traversal attacks
                $resolvedAudioPath = realpath($audioPath);
                $storagePath = realpath(storage_path('app'));
                
                if ($resolvedAudioPath) {
                    $resolvedAudioPath = str_replace('\\', '/', $resolvedAudioPath);
                }
                if ($storagePath) {
                    $storagePath = str_replace('\\', '/', $storagePath);
                }

                if (!$resolvedAudioPath || strpos($resolvedAudioPath, $storagePath) !== 0) {
                    \Log::error('Invalid audio file path for STT', [
                        'audio_path' => $audioPath,
                        'resolved_path' => $resolvedAudioPath,
                        'storage_path' => $storagePath
                    ]);
                    throw new \Exception('Invalid audio file path');
                }

                $audioPath = $resolvedAudioPath;

                if (!file_exists($audioPath)) {
                    throw new \Exception('Audio file not found');
                }

                // For Arabic: Use GPT-4o Audio for transcription + diarization
                if (isset($language) && strpos($language, 'ar') === 0) {
                    \Log::info('HYBRID METHOD - Arabic detected, using GPT-4o Audio');
                    $result = $this->processWithGPT4oAudio($audioPath, $language);
                    \Log::info('HYBRID METHOD - GPT-4o result', ['result' => $result]);
                    if ($result['success']) {
                        \Log::info('HYBRID METHOD - Returning GPT-4o transcription', ['length' => strlen($result['transcription'])]);
                        return $result['transcription'];
                    }
                    // Fallback to Whisper if GPT-4o fails
                    \Log::info('HYBRID METHOD - GPT-4o failed, falling back to Whisper');
                    $result = $this->processWithOpenAIWhisper($audioPath);
                    return $result['success'] ? $result['transcription'] : '';
                }

                // For English and other supported languages, use AssemblyAI as primary
                $fileInfo = pathinfo($audioPath);
                $fileSize = filesize($audioPath);
    
                \Log::info('HYBRID METHOD - Starting server-side transcription', [
                    'audio_path' => $audioPath,
                    'file_size' => $fileSize,
                    'extension' => $fileInfo['extension'],
                    'language' => $language
                ]);
    
                // Try AssemblyAI first for English (best quality for English medical transcription)
                $result = $this->processWithAssemblyAI($audioPath, $language);
    
                if ($result['success']) {
                    \Log::info('HYBRID METHOD - AssemblyAI successful, skipping fallback', [
                        'transcription_length' => strlen($result['transcription']),
                        'speakers_detected' => count($result['speakers'] ?? [])
                    ]);
                    return $result['transcription'];
                }
    
                // Only fallback to GPT-4o if AssemblyAI fails
                \Log::info('HYBRID METHOD - AssemblyAI failed, falling back to GPT-4o Audio');
                $result = $this->processWithGPT4oAudio($audioPath, $language);
                
                if ($result['success']) {
                    return $result['transcription'];
                }

                // Final fallback to Whisper
                \Log::info('HYBRID METHOD - GPT-4o failed, falling back to Whisper');
                $result = $this->processWithOpenAIWhisper($audioPath);
     
                return $result['success'] ? $result['transcription'] : '';
    
            } catch (\Exception $e) {
                \Log::error('HYBRID METHOD - Server STT processing failed', [
                    'error' => $e->getMessage(),
                    'audio_path' => $audioPath
                ]);
    
                $result = $this->processWithOpenAIWhisper($audioPath);
                return $result['success'] ? $result['transcription'] : '';
            }
        }
    
        /**
         * Process audio with Google Cloud Speech-to-Text Healthcare API
         */
        private function processWithGoogleHealthcareSTT($audioPath)
        {
            try {
                // Validate the audio path to prevent path traversal attacks
                $audioPath = realpath($audioPath); // Resolve any relative paths
                $storagePath = storage_path('app'); // Base path for validation
                if (!$audioPath || strpos($audioPath, $storagePath) !== 0) {
                    throw new \Exception('Invalid audio file path');
                }

                // Check if Google Cloud SDK is available
                if (!class_exists('\Google\Cloud\Speech\V1\SpeechClient')) {
                    throw new \Exception('Google Cloud Speech SDK not installed');
                }

                // Check if Google Cloud credentials are available
                $credentialsPath = env('GOOGLE_CLOUD_CREDENTIALS');
                if (!$credentialsPath || !file_exists($credentialsPath)) {
                    throw new \Exception('Google Cloud credentials not configured');
                }

                // Initialize Google Cloud client with error handling
                try {
                    $client = new \Google\Cloud\Speech\V1\SpeechClient([
                        'credentials' => $credentialsPath
                    ]);
                } catch (\Exception $e) {
                    \Log::error('Google Cloud Speech client initialization failed', [
                        'error' => $e->getMessage(),
                        'credentials_path' => $credentialsPath
                    ]);
                    throw new \Exception('Google Cloud Speech service unavailable: ' . $e->getMessage());
                }

                // Read audio file
                $audioContent = file_get_contents($audioPath);
                if (!$audioContent) {
                    throw new \Exception('Could not read audio file');
                }
    
                // Configure recognition with healthcare features with error handling
                try {
                    $config = new \Google\Cloud\Speech\V1\RecognitionConfig([
                        'encoding' => \Google\Cloud\Speech\V1\AudioEncoding::LINEAR16,
                        'sample_rate_hertz' => 16000,
                        'language_code' => 'ar-SA', // Primary Arabic, will auto-detect
                        'alternative_language_codes' => ['en-US'],
                        'enable_automatic_punctuation' => true,
                        'enable_word_time_offsets' => true,
                        'enable_speaker_diarization' => true,
                        'diarization_speaker_count' => 2, // Doctor and patient
                        'min_speaker_count' => 1,
                        'max_speaker_count' => 3,
                        'model' => 'medical_dictation', // Healthcare model
                        'use_enhanced' => true,
                        // Healthcare-specific features
                        'adaptation' => new \Google\Cloud\Speech\V1\SpeechAdaptation([
                            'phrase_sets' => [
                                new \Google\Cloud\Speech\V1\PhraseSet([
                                    'phrases' => $this->getMedicalPhraseSet()
                                ])
                            ]
                        ])
                    ]);
                } catch (\Exception $e) {
                    \Log::error('Google Speech RecognitionConfig creation failed', [
                        'error' => $e->getMessage()
                    ]);
                    throw new \Exception('Failed to configure speech recognition: ' . $e->getMessage());
                }

                try {
                    $audio = new \Google\Cloud\Speech\V1\RecognitionAudio([
                        'content' => $audioContent
                    ]);
                } catch (\Exception $e) {
                    \Log::error('Google Speech RecognitionAudio creation failed', [
                        'error' => $e->getMessage()
                    ]);
                    throw new \Exception('Failed to create audio object for recognition: ' . $e->getMessage());
                }
    
                // Perform recognition
                $response = $client->recognize($config, $audio);
    
                // Process results with speaker diarization
                $transcription = '';
                $speakers = [];
                $medicalTerms = [];
    
                foreach ($response->getResults() as $result) {
                    $alternative = $result->getAlternatives()[0];
                    $transcript = $alternative->getTranscript();
    
                    // Extract speaker information
                    $words = $alternative->getWords();
                    $currentSpeaker = null;
                    $speakerText = [];
    
                    foreach ($words as $word) {
                        $speakerTag = $word->getSpeakerTag();
    
                        if ($currentSpeaker !== $speakerTag) {
                            if ($currentSpeaker !== null && !empty($speakerText)) {
                                $speakers[] = [
                                    'speaker' => $currentSpeaker,
                                    'text' => implode(' ', $speakerText),
                                    'start_time' => $word->getStartTime()->getSeconds(),
                                    'end_time' => $word->getEndTime()->getSeconds()
                                ];
                            }
                            $currentSpeaker = $speakerTag;
                            $speakerText = [];
                        }
    
                        $wordText = $word->getWord();
                        $speakerText[] = $wordText;
    
                        // Check for medical terms
                        if ($this->isMedicalTerm($wordText)) {
                            $medicalTerms[] = $wordText;
                        }
                    }
    
                    // Add final speaker segment
                    if ($currentSpeaker !== null && !empty($speakerText)) {
                        $speakers[] = [
                            'speaker' => $currentSpeaker,
                            'text' => implode(' ', $speakerText)
                        ];
                    }
    
                    $transcription .= $transcript . ' ';
                }
    
                $client->close();
    
                return [
                    'success' => true,
                    'transcription' => trim($transcription),
                    'speakers' => $speakers,
                    'medical_terms' => array_unique($medicalTerms),
                    'method' => 'google_healthcare'
                ];
    
            } catch (\Exception $e) {
                Log::error('Google Healthcare STT failed', [
                    'error' => $e->getMessage(),
                    'audio_path' => $audioPath
                ]);
    
                return [
                    'success' => false,
                    'error' => $e->getMessage()
                ];
            }
        }
    
        /**
         * Use GPT-4o to identify speakers from transcribed text
         */
        private function diarizeTranscriptWithGPT4o($transcription, $language = 'ar')
        {
            try {
                $response = OpenAI::chat()->create([
                    'model' => 'gpt-4o',
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'You are a medical conversation analyst. Analyze this medical conversation and identify different speakers (Doctor and Patient). Return ONLY a JSON array: [{"speaker_tag": 1, "text": "...", "start_time": 0}]. Speaker 1 = Doctor, Speaker 2 = Patient.'
                        ],
                        [
                            'role' => 'user',
                            'content' => "Identify speakers in this conversation:\n\n" . $transcription
                        ]
                    ],
                    'temperature' => 0.1
                ]);

                $content = $response['choices'][0]['message']['content'] ?? '';
                $segments = json_decode($content, true);

                if (is_array($segments)) {
                    $formatted = "";
                    foreach ($segments as $seg) {
                        $speaker = $seg['speaker_tag'] ?? 1;
                        $text = $seg['text'] ?? '';
                        if ($text) {
                            $formatted .= "[Speaker $speaker]: $text\n";
                        }
                    }
                    return trim($formatted);
                }

                return null;
            } catch (\Exception $e) {
                \Log::error('GPT-4o diarization failed', ['error' => $e->getMessage()]);
                return null;
            }
        }

        /**
         * Process audio using GPT-4o Audio Preview (Transcribe + Diarize)
         */
        private function processWithGPT4oAudio($audioPath, $language = 'ar')
        {
            try {
                $openAI = app(\App\Services\OpenAIClient::class);
                $segments = $openAI->transcribeAndDiarizeWithGPT4o($audioPath, $language);

                if (!$segments || !is_array($segments)) {
                    throw new \Exception('GPT-4o Audio returned invalid or empty segments');
                }

                // Format the transcription with speaker tags
                $fullTranscript = "";
                $speakers = [];
                
                foreach ($segments as $segment) {
                    // Handle if segment is a JSON string (parse it)
                    if (is_string($segment)) {
                        $segment = json_decode($segment, true);
                        if (!$segment) continue;
                    }
                    
                    $speakerTag = $segment['speaker_tag'] ?? 1;
                    $text = $segment['text'] ?? '';
                    $startTime = $segment['start_time'] ?? 0;
                    
                    if (empty($text)) continue;

                    $fullTranscript .= "[Speaker $speakerTag]: $text\n";
                    
                    $speakers[] = [
                        'speaker_tag' => $speakerTag,
                        'text' => $text,
                        'start_time' => $startTime
                    ];
                }

                return [
                    'success' => true,
                    'transcription' => trim($fullTranscript),
                    'speakers' => $speakers,
                    'medical_terms' => [], // Extraction happens in next phase
                    'method' => 'gpt-4o-audio'
                ];

            } catch (\Exception $e) {
                \Log::error('GPT-4o Audio processing failed', [
                    'error' => $e->getMessage()
                ]);
                return ['success' => false, 'error' => $e->getMessage()];
            }
        }

        /**
         * Fallback to OpenAI Whisper
         */
        private function processWithOpenAIWhisper($audioPath)
        {
            try {
                // Validate the audio path to prevent path traversal attacks
                $audioPath = realpath($audioPath); // Resolve any relative paths
                $storagePath = storage_path('app'); // Base path for validation
                if (!$audioPath || strpos($audioPath, $storagePath) !== 0) {
                    throw new \Exception('Invalid audio file path');
                }

                // Prepare parameters for OpenAI Whisper transcription
                $transcribeParams = [
                    'model' => 'whisper-1',
                    'file' => fopen($audioPath, 'r'), // Open file for reading
                    'response_format' => 'text',
                    'language' => null
                ];

                // Perform transcription using OpenAI Whisper API
                $response = OpenAI::audio()->transcribe($transcribeParams);
                $transcription = is_string($response) ? $response : '';

                return [
                    'success' => true,
                    'transcription' => trim($transcription),
                    'speakers' => [],
                    'medical_terms' => [],
                    'method' => 'openai_whisper'
                ];
    
            } catch (\Exception $e) {
                \Log::error('OpenAI Whisper fallback failed', [
                    'error' => $e->getMessage()
                ]);
                return [
                    'success' => false,
                    'transcription' => '',
                    'error' => $e->getMessage()
                ];
            }
        }

        /**
         * Process audio with AssemblyAI
         */
        private function processWithAssemblyAI($audioPath, $language = 'en')
        {
            try {
                $assemblyAIService = new \App\Services\AssemblyAIService();

                // Check if API key is configured before proceeding
                $apiKey = config('services.assemblyai.api_key');
                if (empty($apiKey)) {
                    \Log::warning('AssemblyAI API key not configured, skipping AssemblyAI processing');
                    return ['success' => false, 'error' => 'AssemblyAI API key not configured'];
                }

                // 1. Upload file to AssemblyAI
                $uploadUrl = $assemblyAIService->uploadFile($audioPath);

                if (!$uploadUrl) {
                    throw new \Exception('Failed to upload audio file to AssemblyAI');
                }

                // 2. Submit for transcription with speaker diarization enabled
                $config = [
                    'speaker_labels' => true,  // Enable speaker diarization
                    'speakers_expected' => 2   // Expect 2 speakers (doctor and patient)
                ];
                
                if (!empty($language) && $language !== 'auto') {
                    $langCode = substr($language, 0, 2);
                    $config['language_code'] = $langCode;
                } else {
                    $config['language_detection'] = true;
                }

                $submission = $assemblyAIService->processTranscript($uploadUrl, $config);
                if (!$submission || !isset($submission['id'])) {
                    throw new \Exception('Failed to submit audio for transcription');
                }

                $transcriptId = $submission['id'];

                // 3. Poll for result
                $maxRetries = 30;
                $retryCount = 0;
                $transcription = '';
                $speakers = [];

                while ($retryCount < $maxRetries) {
                    $result = $assemblyAIService->getTranscript($transcriptId);

                    if (!$result) {
                        throw new \Exception('Failed to retrieve transcript from AssemblyAI');
                    }

                    if ($result['status'] === 'completed') {
                        $transcription = $result['text'] ?? '';

                        // Extract speaker-separated utterances
                        if (isset($result['utterances']) && is_array($result['utterances'])) {
                            foreach ($result['utterances'] as $utterance) {
                                $speakerLabel = $utterance['speaker'] ?? 'A';
                                $speakers[] = [
                                    'speaker' => $speakerLabel,
                                    'text' => $utterance['text'] ?? '',
                                    'start_time' => ($utterance['start'] ?? 0) / 1000,
                                    'end_time' => ($utterance['end'] ?? 0) / 1000,
                                    'role' => $speakerLabel === 'A' ? 'doctor' : 'patient'
                                ];
                            }
                            
                            // Format transcription with speaker labels
                            $formattedTranscription = '';
                            foreach ($speakers as $segment) {
                                $speakerNum = $segment['speaker'] === 'A' ? '1' : '2';
                                $formattedTranscription .= "[Speaker {$speakerNum}]: {$segment['text']}\n";
                            }
                            
                            if (!empty($formattedTranscription)) {
                                $transcription = trim($formattedTranscription);
                            }
                        }

                        break;
                    } elseif ($result['status'] === 'error') {
                        throw new \Exception('AssemblyAI processing error: ' . ($result['error'] ?? 'Unknown error'));
                    }

                    $retryCount++;
                    sleep(2);
                }

                if (empty($transcription)) {
                    throw new \Exception('AssemblyAI transcription timed out or returned empty');
                }

                return [
                    'success' => true,
                    'transcription' => $transcription,
                    'speakers' => $speakers,
                    'medical_terms' => [],
                    'method' => 'assemblyai'
                ];

            } catch (\Exception $e) {
                \Log::error('AssemblyAI processing failed', [
                    'error' => $e->getMessage(),
                    'audio_path' => $audioPath
                ]);
                return ['success' => false, 'error' => $e->getMessage()];
            }
        }
    
        /**
         * Get medical phrase set for Google Speech adaptation
         */
        private function getMedicalPhraseSet()
        {
            // Return cached phrase set if available to improve performance
            if ($this->cachedMedicalPhraseSet !== null) {
                return $this->cachedMedicalPhraseSet;
            }

            $this->cachedMedicalPhraseSet = [
                // Arabic medical terms
                'ألم', 'صداع', 'حمى', 'سعال', 'غثيان', 'قيء', 'إسهال', 'إمساك',
                'ضغط دم', 'سكري', 'ضغط', 'قلب', 'رئة', 'كبد', 'كلى', 'معدة',
                'دواء', 'حقنة', 'جراحة', 'تشخيص', 'علاج', 'فحص', 'تحاليل', 'أشعة',
                'طبيب', 'مريض', 'مستشفى', 'عيادة', 'صيدلية', 'تمريض',

                // English medical terms
                'pain', 'headache', 'fever', 'cough', 'nausea', 'vomiting', 'diarrhea', 'constipation',
                'blood pressure', 'diabetes', 'hypertension', 'heart', 'lung', 'liver', 'kidney', 'stomach',
                'medicine', 'injection', 'surgery', 'diagnosis', 'treatment', 'examination', 'tests', 'x-ray',
                'doctor', 'patient', 'hospital', 'clinic', 'pharmacy', 'nursing',

                // Medical procedures and conditions
                'electrocardiogram', 'echocardiogram', 'endoscopy', 'colonoscopy', 'biopsy',
                'myocardial infarction', 'cerebrovascular accident', 'chronic obstructive pulmonary disease',
                'gastroesophageal reflux disease', 'hypertensive emergency',

                // Vital signs
                'temperature', 'pulse', 'respiration', 'blood pressure', 'oxygen saturation',
                'heart rate', 'respiratory rate', 'body mass index'
            ];

            return $this->cachedMedicalPhraseSet;
        }
    
        /**
         * Check if a word is a medical term
         */
        private function isMedicalTerm($word)
        {
            $medicalTerms = [
                'pain', 'headache', 'fever', 'cough', 'nausea', 'vomiting', 'diarrhea', 'constipation',
                'hypertension', 'diabetes', 'myocardial', 'infarction', 'stroke', 'copd', 'gerd',
                'electrocardiogram', 'echocardiogram', 'endoscopy', 'colonoscopy', 'biopsy',
                'ألم', 'صداع', 'حمى', 'سعال', 'غثيان', 'قيء', 'إسهال', 'إمساك',
                'ضغط', 'سكري', 'قلب', 'رئة', 'كبد', 'كلى', 'معدة'
            ];
    
            $lowerWord = strtolower(trim($word));
            return in_array($lowerWord, $medicalTerms);
        }
    
        /**
         * Extract speaker data from transcription using AI analysis
         */
        private function extractSpeakerDataFromTranscription($transcription)
        {
            try {
                // Use AI to analyze transcription and separate speakers
                $response = OpenAI::chat()->create([
                    'model' => 'gpt-4o',
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'You are a medical transcription specialist. Analyze this doctor-patient conversation and separate the speakers. Return ONLY valid JSON with speaker segments.
    
                            Rules:
                            - Speaker 1 is typically the DOCTOR (asks questions, gives medical advice, uses medical terminology)
                            - Speaker 2 is typically the PATIENT (describes symptoms, answers questions, expresses concerns)
                            - Identify medical terms used
                            - Estimate timing for each speaker segment
                            - Return JSON: {"speakers": [{"speaker": 1, "text": "...", "start_time": 0, "role": "doctor/patient"}], "medical_terms": ["term1", "term2"]}'
                        ],
                        [
                            'role' => 'user',
                            'content' => "Analyze this medical conversation and separate speakers:\n\n" . $transcription
                        ]
                    ],
                    'temperature' => 0.1,
                    'max_tokens' => 1000
                ]);
    
                $aiResponse = $response['choices'][0]['message']['content'] ?? '';
                $speakerData = json_decode($aiResponse, true);
    
                if ($speakerData && isset($speakerData['speakers'])) {
                    return [
                        'speakers' => $speakerData['speakers'],
                        'medical_terms' => $speakerData['medical_terms'] ?? []
                    ];
                }
    
            } catch (\Exception $e) {
                Log::error('Speaker extraction failed', ['error' => $e->getMessage()]);
            }
    
            // Fallback: basic speaker separation based on content patterns
            return $this->fallbackSpeakerSeparation($transcription);
        }
    
        /**
         * Fallback speaker separation based on content analysis
         */
        private function fallbackSpeakerSeparation($transcription)
        {
            $sentences = preg_split('/[.!?]+/', $transcription);
            $speakers = [];
            $medicalTerms = [];
    
            $currentSpeaker = 1; // Start with doctor
            $speakerText = [];
            $startTime = 0;
    
            // Pre-compile medical keywords for performance
            $medicalKeywords = $this->getMedicalPhraseSet(); // Use the full medical phrase set
            $lowercaseMedicalKeywords = array_map('strtolower', $medicalKeywords);
            $medicalKeywordsSet = array_flip($lowercaseMedicalKeywords); // For O(1) lookup

            foreach ($sentences as $index => $sentence) {
                $sentence = trim($sentence);
                if (empty($sentence)) continue;

                // Simple heuristic: questions and medical terms suggest doctor
                $hasQuestion = strpos($sentence, '?') !== false;

                // Optimized medical term check using pre-compiled keywords
                $hasMedicalTerms = false;
                $lowerSentence = strtolower($sentence);
                foreach ($lowercaseMedicalKeywords as $medicalKeyword) {
                    if (strpos($lowerSentence, $medicalKeyword) !== false) {
                        $hasMedicalTerms = true;
                        break;
                    }
                }

                if ($hasQuestion || $hasMedicalTerms) {
                    // Likely doctor speaking
                    if ($currentSpeaker === 2 && !empty($speakerText)) {
                        // Save previous speaker segment - build text more efficiently
                        $speakerSegment = [
                            'speaker' => 2,
                            'text' => implode('. ', $speakerText),
                            'start_time' => $startTime,
                            'role' => 'patient'
                        ];
                        $speakers[] = $speakerSegment;
                        $speakerText = [];
                        $startTime = $index * 5; // Rough estimate
                    }
                    $currentSpeaker = 1;
                } else {
                    // Likely patient speaking
                    if ($currentSpeaker === 1 && !empty($speakerText)) {
                        // Save previous speaker segment - build text more efficiently
                        $speakerSegment = [
                            'speaker' => 1,
                            'text' => implode('. ', $speakerText),
                            'start_time' => $startTime,
                            'role' => 'doctor'
                        ];
                        $speakers[] = $speakerSegment;
                        $speakerText = [];
                        $startTime = $index * 5; // Rough estimate
                    }
                    $currentSpeaker = 2;
                }

                $speakerText[] = $sentence;

                // Extract medical terms more efficiently - only check words that are in our medical dictionary
                $words = preg_split('/\s+/', $sentence); // Split by any whitespace
                foreach ($words as $word) {
                    $cleanWord = strtolower(trim(preg_replace('/[^\w]/', '', $word))); // Remove punctuation
                    if (isset($medicalKeywordsSet[$cleanWord]) && !in_array($cleanWord, $medicalTerms)) {
                        $medicalTerms[] = $cleanWord;
                    }
                }
            }

            // Add final speaker segment
            if (!empty($speakerText)) {
                $speakerSegment = [
                    'speaker' => $currentSpeaker,
                    'text' => implode('. ', $speakerText),
                    'start_time' => $startTime,
                    'role' => $currentSpeaker === 1 ? 'doctor' : 'patient'
                ];
                $speakers[] = $speakerSegment;
            }

            return [
                'speakers' => $speakers,
                'medical_terms' => array_unique($medicalTerms)
            ];
        }
    
        /**
         * Check if text contains medical terms
         */
        private function containsMedicalTerms($text)
        {
            $medicalKeywords = [
                'pain', 'headache', 'fever', 'cough', 'nausea', 'vomiting', 'diarrhea',
                'blood pressure', 'diabetes', 'hypertension', 'heart', 'lung', 'liver',
                'kidney', 'stomach', 'medicine', 'treatment', 'diagnosis', 'symptoms',
                'ألم', 'صداع', 'حمى', 'سعال', 'غثيان', 'ضغط', 'سكري', 'قلب', 'دواء'
            ];
    
            $lowerText = strtolower($text);
            foreach ($medicalKeywords as $keyword) {
                if (strpos($lowerText, $keyword) !== false) {
                    return true;
                }
            }
    
            return false;
        }
    
        /**
         * Select the best transcription between live and server results
         */
        private function selectBestTranscription($liveTranscription, $serverTranscription)
        {
            // Log the decision-making process
            \Log::debug('HYBRID METHOD - Selecting best transcription', [
                'live_transcription_length' => strlen($liveTranscription ?? ''),
                'server_transcription_length' => strlen($serverTranscription ?? ''),
                'has_live_transcription' => !empty($liveTranscription),
                'has_server_transcription' => !empty($serverTranscription)
            ]);

            // If no server transcription, use live
            if (empty($serverTranscription)) {
                \Log::debug('HYBRID METHOD - Using live transcription (no server available)');
                return $liveTranscription;
            }

            // If no live transcription, use server
            if (empty($liveTranscription)) {
                \Log::debug('HYBRID METHOD - Using server transcription (no live available)');
                return $serverTranscription;
            }

            // Compare lengths and content quality
            $liveLength = strlen($liveTranscription);
            $serverLength = strlen($serverTranscription);

            \Log::debug('HYBRID METHOD - Transcription comparison', [
                'live_length' => $liveLength,
                'server_length' => $serverLength,
                'length_ratio' => $serverLength > 0 ? $liveLength / $serverLength : 0
            ]);

            // Prefer server if it's significantly longer (likely more accurate)
            if ($serverLength > $liveLength * 1.2) {
                \Log::debug('HYBRID METHOD - Using server transcription (significantly longer)');
                return $serverTranscription;
            }

            // If live is longer or similar, prefer live (real-time context)
            if ($liveLength >= $serverLength) {
                \Log::debug('HYBRID METHOD - Using live transcription (longer or similar length)');
                return $liveTranscription;
            }

            // Fallback to server
            \Log::debug('HYBRID METHOD - Using server transcription (fallback)');
            return $serverTranscription;
        }
    
        /**
         * Extract medical data from transcription using AI
         */
        private function extractMedicalDataFromText($transcription)
        {
            try {
                $response = OpenAI::chat()->create([
                    'model' => 'gpt-4o',
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'Extract structured medical information from this consultation text. Return ONLY valid JSON with these exact keys: symptoms, medical_history, physical_findings, medications, vital_signs, diagnosis, care_plan. If no information for a category, return empty string.'
                        ],
                        [
                            'role' => 'user',
                            'content' => "Extract medical data from: " . $transcription
                        ]
                    ],
                    'temperature' => 0.1,
                    'max_tokens' => 1000
                ]);
    
                $aiResponse = $response['choices'][0]['message']['content'] ?? '';
                $jsonData = json_decode($aiResponse, true);
    
                return $jsonData && is_array($jsonData) ? $jsonData : [];
    
            } catch (\Exception $e) {
                \Log::error('HYBRID METHOD - Medical data extraction failed', [
                    'error' => $e->getMessage(),
                    'transcription_preview' => substr($transcription, 0, 100)
                ]);
    
                return [];
            }
        }
    
        /**
         * Record performance metrics for voice assistant usage
         */
        private function recordPerformanceMetrics(array $metrics): void
        {
            try {
                // Ensure doctor_id is in metrics
                if (!isset($metrics['doctor_id'])) {
                    $metrics['doctor_id'] = Auth::id();
                }
                VoiceAssistantPerformanceMetric::recordMetric($metrics);
            } catch (\Exception $e) {
                \Log::error('Failed to record performance metrics', [
                    'error' => $e->getMessage(),
                    'metrics' => $metrics
                ]);
            }
        }
    
        /**
         * Detect device type from request
         */
        private function detectDeviceType(Request $request): string
        {
            $userAgent = $request->header('User-Agent', '');
    
            if (stripos($userAgent, 'mobile') !== false || stripos($userAgent, 'android') !== false || stripos($userAgent, 'iphone') !== false) {
                return 'mobile';
            } elseif (stripos($userAgent, 'tablet') !== false || stripos($userAgent, 'ipad') !== false) {
                return 'tablet';
            } else {
                return 'desktop';
            }
        }
    
        /**
         * Estimate audio duration from file properties
         */
        private function estimateAudioDuration($file): ?float
        {
            $fileSize = $file->getSize();
            $extension = strtolower($file->getClientOriginalExtension());

            // Rough estimation based on common audio formats and bitrates
            // These are approximations for typical medical consultation recordings
            $estimates = [
                'wav' => 176400,  // ~176 kB/s for 16-bit 44.1kHz mono WAV
                'mp3' => 128000 / 8, // 128 kbps MP3
                'webm' => 64000 / 8,  // ~64 kbps WebM/Opus
                'mp4' => 128000 / 8,  // 128 kbps AAC
            ];

            $bytesPerSecond = $estimates[$extension] ?? 100000; // Fallback estimate

            if ($bytesPerSecond > 0) {
                return round($fileSize / $bytesPerSecond, 2);
            }

            return null;
        }

        /**
         * Determine the processing status based on results
         */
        private function determineProcessingStatus($metrics, $liveTranscription, $serverTranscription, $improvedTranscription)
        {
            $status = [
                'method' => 'live', // default
                'server_processed' => $metrics['server_processing_success'] ?? false,
                'audio_stored' => $metrics['audio_storage_success'] ?? false,
                'extraction_success' => !empty($metrics['medical_extraction_success'] ?? false),
                'improvement_detected' => false,
                'fallback_used' => false
            ];
    
            // Determine which method was used based on what was attempted, not just what succeeded
            $serverAttempted = isset($metrics['server_processing_success']); // Server processing was attempted if this key exists
    
            if ($serverAttempted) {
                $status['method'] = 'server';
                // Check if server transcription was better than live
                if (!empty($serverTranscription) && strlen($serverTranscription) > strlen($liveTranscription) * 1.1) {
                    $status['improvement_detected'] = true;
                }
            } elseif (!empty($liveTranscription)) {
                $status['method'] = 'live';
            } else {
                $status['method'] = 'none';
                $status['fallback_used'] = true;
            }
    
            // Check if fallback was used (when both server and live failed but we have improved transcription)
            if (empty($liveTranscription) && empty($serverTranscription) && !empty($improvedTranscription)) {
                $status['fallback_used'] = true;
            }
    
            return $status;
        }

        /**
         * Generate appropriate success message based on processing status
         */
        private function generateSuccessMessage($processingStatus, $metrics)
        {
            $messages = [];

            if ($processingStatus['audio_stored']) {
                $messages[] = 'Audio file stored successfully';
            }

            if ($processingStatus['server_processed']) {
                if ($processingStatus['improvement_detected']) {
                    $messages[] = 'Server processing improved transcription quality';
                } else {
                    $messages[] = 'Server processing completed';
                }
            } elseif ($processingStatus['method'] === 'live') {
                $messages[] = 'Using live transcription';
            }

            if ($processingStatus['extraction_success']) {
                $messages[] = 'Medical data extracted successfully';
            }

            if ($processingStatus['fallback_used']) {
                $messages[] = 'Fallback methods used due to processing limitations';
            }

            if (empty($messages)) {
                return 'Audio processing completed with basic transcription';
            }

            return implode('. ', $messages) . '.';
        }
    }
