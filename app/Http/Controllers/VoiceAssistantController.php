<?php

namespace App\Http\Controllers;

use App\Models\VoiceTranscription;
use App\Models\User;
use App\Models\Diagnosis;
use App\Models\AiAssistantResult;
use App\Models\VoiceAssistantPerformanceMetric;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use OpenAI\Laravel\Facades\OpenAI;

class VoiceAssistantController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $user = Auth::user();

            // Handle sub-users - they inherit access from their parent doctor
            if ($user->isSubUser()) {
                $parentUser = $user->parentUser;
                if (!$parentUser || !$parentUser->isDoctor() || !$parentUser->doctor || !$parentUser->doctor->is_active) {
                    abort(403, 'Access denied. Parent doctor profile required.');
                }
            } else {
                // Handle main users (doctors)
                if (!$user->isDoctor() || !$user->doctor) {
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
        // Use the same logic as Diagnosis model
        return Diagnosis::generatePatientKey(
            $patient->name,
            $patient->age,
            $patient->gender,
            Auth::id()
        );
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
            'user_email' => Auth::user()->email,
            'is_doctor' => Auth::user()->isDoctor(),
            'primary_doctor_id' => Auth::user()->primary_doctor_id ?? 'null'
        ]);

        try {
            $basePatients = Auth::user()->getEffectiveAssignedPatients()
                ->select('id', 'name', 'email', 'age', 'gender')
                ->orderBy('name')
                ->get();
                
            \Log::info('Voice Assistant - Loaded patients using getEffectiveAssignedPatients', [
                'count' => $basePatients->count(),
                'patient_names' => $basePatients->pluck('name')->toArray()
            ]);
        } catch (\Exception $e) {
            \Log::warning('Could not load assigned patients, trying fallback: ' . $e->getMessage());

            // Fallback: load all patients with role 'patient' for this doctor
            try {
                $effectiveDoctorId = Auth::user()->getEffectiveDoctorUser()->id ?? Auth::id();
                \Log::info('Voice Assistant - Using fallback patient loading', [
                    'effective_doctor_id' => $effectiveDoctorId
                ]);
                
                $basePatients = User::where('role', 'patient')
                    ->where('primary_doctor_id', $effectiveDoctorId)
                    ->select('id', 'name', 'email', 'age', 'gender')
                    ->orderBy('name')
                    ->get();
                    
                \Log::info('Voice Assistant - Loaded patients using fallback', [
                    'count' => $basePatients->count(),
                    'patient_names' => $basePatients->pluck('name')->toArray()
                ]);
            } catch (\Exception $e2) {
                $basePatients = collect();
                \Log::error('Could not load patients at all: ' . $e2->getMessage());
            }
        }

        // Load available appointments for each patient (for appointment completion)
        $patientAppointments = [];
        $effectiveDoctorId = Auth::user()->getEffectiveDoctorUser()->id ?? Auth::id();
        $loggedInUserId = Auth::id();
        
        Log::info('Voice Assistant - Doctor ID debug', [
            'effective_doctor_id' => $effectiveDoctorId,
            'logged_in_user_id' => $loggedInUserId,
            'user_is_doctor' => Auth::user()->isDoctor(),
            'user_email' => Auth::user()->email
        ]);
        
        foreach ($basePatients as $patient) {
            $appointments = collect(); // Start with empty collection
            
            // First, try to find ALL appointments for this patient to see what exists
            $allPatientAppointments = \App\Models\Appointment::where('patient_id', $patient->id)
                ->orderBy('appointment_date', 'desc')
                ->get();
            
            Log::info('Voice Assistant - All appointments for patient (debug)', [
                'patient_id' => $patient->id,
                'patient_name' => $patient->name,
                'total_appointments' => $allPatientAppointments->count(),
                'appointments' => $allPatientAppointments->map(function($apt) {
                    return [
                        'id' => $apt->id,
                        'doctor_id' => $apt->doctor_id,
                        'status' => $apt->status,
                        'appointment_date' => $apt->appointment_date->format('Y-m-d H:i:s')
                    ];
                })->toArray()
            ]);
            
            // Now try multiple approaches to find appointments for completion
            $searchAttempts = [
                ['doctor_id' => $effectiveDoctorId, 'label' => 'effective_doctor_id'],
                ['doctor_id' => $loggedInUserId, 'label' => 'logged_in_user_id'],
            ];
            
            // If the patient belongs to this doctor, also try with patient's primary_doctor_id
            if ($patient->primary_doctor_id && in_array($patient->primary_doctor_id, [$effectiveDoctorId, $loggedInUserId])) {
                $searchAttempts[] = ['doctor_id' => $patient->primary_doctor_id, 'label' => 'patient_primary_doctor_id'];
            }
            
            // Also try to get the doctor's ID from the Doctor model
            try {
                $doctor = Auth::user()->doctor;
                if ($doctor && $doctor->id && !in_array($doctor->id, array_column($searchAttempts, 'doctor_id'))) {
                    $searchAttempts[] = ['doctor_id' => $doctor->id, 'label' => 'auth_user_doctor_id'];
                }
            } catch (\Exception $e) {
                Log::warning('Voice Assistant - Could not get doctor ID: ' . $e->getMessage());
            }
            
            foreach ($searchAttempts as $attempt) {
                // Search for ACTIVE appointments only (pending/confirmed, today or future)
                $query = \App\Models\Appointment::where('patient_id', $patient->id)
                    ->where('doctor_id', $attempt['doctor_id'])
                    ->whereIn('status', ['pending', 'confirmed'])
                    ->where('appointment_date', '>=', now()->startOfDay()) // Only today or future appointments
                    ->orderBy('appointment_date', 'asc');
                
                $foundAppointments = $query->get();
                
                if ($foundAppointments->isNotEmpty()) {
                    Log::info('Voice Assistant - Found active appointments with ' . $attempt['label'], [
                        'patient_id' => $patient->id,
                        'patient_name' => $patient->name,
                        'search_type' => $attempt['label'],
                        'doctor_id' => $attempt['doctor_id'],
                        'appointment_count' => $foundAppointments->count()
                    ]);
                    $appointments = $foundAppointments;
                    break;
                }
            }
            
            // If no active appointments found, also try today's appointments regardless of time
            if ($appointments->isEmpty()) {
                foreach ($searchAttempts as $attempt) {
                    $query = \App\Models\Appointment::where('patient_id', $patient->id)
                        ->where('doctor_id', $attempt['doctor_id'])
                        ->whereIn('status', ['pending', 'confirmed'])
                        ->whereDate('appointment_date', today()) // Today's appointments
                        ->orderBy('appointment_date', 'asc');
                    
                    $foundAppointments = $query->get();
                    
                    if ($foundAppointments->isNotEmpty()) {
                        Log::info('Voice Assistant - Found today appointments with ' . $attempt['label'], [
                            'patient_id' => $patient->id,
                            'patient_name' => $patient->name,
                            'search_type' => $attempt['label'],
                            'doctor_id' => $attempt['doctor_id'],
                            'appointment_count' => $foundAppointments->count()
                        ]);
                        $appointments = $foundAppointments;
                        break;
                    }
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

            // Debug logging
            Log::info('Voice Assistant - Final appointments for patient', [
                'patient_id' => $patient->id,
                'patient_name' => $patient->name,
                'patient_primary_doctor_id' => $patient->primary_doctor_id,
                'effective_doctor_id' => $effectiveDoctorId,
                'logged_in_user_id' => $loggedInUserId,
                'appointment_count' => $appointments->count(),
                'appointments' => $appointments->toArray()
            ]);

            $patientAppointments[$patient->id] = $appointments;
        }

        // Process patients and build patient groups with visit history
        foreach ($basePatients as $patient) {
            // Generate patient key if not exists
            $patientKey = $this->generatePatientKey($patient);

            // Get visit history from Diagnosis records
            $visits = Diagnosis::where('patient_id', $patient->id)
                ->where('doctor_id', Auth::id())
                ->orderBy('created_at', 'desc')
                ->get();

            $visitCount = $visits->count();
            $lastVisit = $visits->first() ? $visits->first()->created_at : null;

            // Add to patient groups for modal compatibility
            $patientGroups[$patientKey] = [
                'patient' => $patient,
                'visits' => $visits->map(function($visit) {
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
            ->with('patient')
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

        if (!$selectedPatient) {
            return response()->json([
                'success' => false,
                'message' => 'Please select a patient first.'
            ]);
        }

        $sessionId = Str::uuid()->toString();

        // Create initial transcription record
        VoiceTranscription::create([
            'doctor_id' => Auth::id(),
            'patient_id' => $selectedPatient,
            'session_id' => $sessionId,
            'raw_transcription' => '',
            'status' => 'active',
            'session_started_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'sessionId' => $sessionId,
            'message' => 'Session started successfully.'
        ]);
    }

    public function stopSession(Request $request)
    {
        $sessionId = $request->input('sessionId');

        // Update transcription record
        VoiceTranscription::where('session_id', $sessionId)
            ->update([
                'status' => 'completed',
                'session_ended_at' => now(),
            ]);

        return response()->json([
            'success' => true,
            'message' => 'Session stopped successfully.'
        ]);
    }

    public function handleTranscription(Request $request)
    {
        $text = trim($request->input('text', ''));
        $sessionId = $request->input('sessionId');

        if (empty($text)) {
            return response()->json([
                'success' => false,
                'message' => 'No transcription text provided.'
            ]);
        }

        // Update the transcription in database
        $transcription = VoiceTranscription::where('session_id', $sessionId)->first();
        if ($transcription) {
            $transcription->update([
                'raw_transcription' => $text,
                'updated_at' => now()
            ]);
        }

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

            $aiResponse = $response['choices'][0]['message']['content'] ?? '';
            
            \Log::info('Voice Assistant - OpenAI response received', [
                'response_length' => strlen($aiResponse),
                'response_preview' => substr($aiResponse, 0, 300)
            ]);

            // FIXED: More robust JSON extraction
            $extractedData = $this->extractJsonFromResponse($aiResponse);
            
            if ($extractedData) {
                // Validate and clean the extracted data
                $extractedData = $this->validateAndCleanExtractedData($extractedData);
                
                // Update the transcription record with extracted data
                $transcriptionRecord = VoiceTranscription::where('session_id', $sessionId)->first();
                if ($transcriptionRecord) {
                    $transcriptionRecord->update([
                        'extracted_data' => $extractedData
                    ]);
                }
                
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

        if (empty($transcription)) {
            return response()->json([
                'success' => false,
                'message' => 'No transcription available. Please record some audio first.'
            ]);
        }

        if (!$selectedPatient) {
            return response()->json([
                'success' => false,
                'message' => 'Please select a patient first.'
            ]);
        }

        try {
            // Get the user's specialty and use the existing preparePrompt function logic
            $specialty = Auth::user()->setting->specialty ?? 'Internal Medicine';
            $criterion = Auth::user()->setting->criterion ?? 'CDC';

            // Get patient data for AI analysis
            $patient = User::find($selectedPatient);
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

            $aiAnalysis = $response['choices'][0]['message']['content'] ?? '';

            // Update database
            VoiceTranscription::where('session_id', $sessionId)
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
            \Log::error('AI analysis error: ' . $e->getMessage());

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

        if (!$selectedPatient || empty($aiAnalysis)) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot create AI result without patient selection and AI analysis.'
            ]);
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

        if (empty($manualDiagnosisText)) {
            return response()->json([
                'success' => false,
                'message' => 'Please enter your diagnosis text.'
            ]);
        }

        try {
            // Get the AI assistant result if provided
            $aiResult = null;
            if ($aiResultId) {
                $aiResult = AiAssistantResult::findOrFail($aiResultId);
            }

            // Get the patient
            $patient = User::findOrFail($selectedPatient);

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

            // Update the voice transcription record
            VoiceTranscription::where('session_id', $sessionId)
                ->update([
                    'diagnosis_id' => $diagnosis->id,
                    'status' => 'diagnosis_created',
                ]);

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
            // Create new patient user
            $patient = User::create([
                'name' => $request->input('newPatientName'),
                'email' => $request->input('newPatientEmail'),
                'password' => Hash::make('patient123'), // Default password
                'role' => 'patient',
                'age' => $request->input('newPatientAge'),
                'gender' => $request->input('newPatientGender'),
                'phone' => $request->input('newPatientPhone'),
                'primary_doctor_id' => Auth::id(), // Assign current doctor as primary
                'email_verified_at' => now(), // Auto-verify for doctor-created accounts
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
                'message' => 'New patient created successfully! Default password is "patient123" - please inform the patient to change it.'
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
            // Send notification to patient about new voice diagnosis
            if ($diagnosis->patient && $diagnosis->patient->wantsNotification('voice_transcription_completed')) {
                // Get the voice transcription record to pass to the notification
                $voiceTranscription = VoiceTranscription::where('session_id', $diagnosis->voice_transcript ? json_decode($diagnosis->voice_transcript, true)['session_id'] ?? null : null)->first();

                if ($voiceTranscription) {
                    $diagnosis->patient->notifyIfWants(new \App\Notifications\VoiceTranscriptionCompletedNotification($voiceTranscription));
                }
            }

            // Send notification to doctor about voice transcription completion
            if ($diagnosis->doctor && $diagnosis->doctor->user) {
                $doctor = $diagnosis->doctor->user;

                if ($doctor->wantsNotification('voice_transcription_completed')) {
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

            // Ensure the patient belongs to the authenticated doctor
            $effectiveDoctorId = Auth::user()->getEffectiveDoctorUser()->id ?? Auth::id();
            if ($patient->primary_doctor_id !== $effectiveDoctorId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Patient not assigned to this doctor.'
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

            // Update the voice transcription record
            VoiceTranscription::where('session_id', $request->sessionId)
                ->update([
                    'diagnosis_id' => $diagnosis->id,
                    'status' => 'diagnosis_created',
                ]);

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

            // If appointment was completed, redirect to completion page
            if ($request->completionType === 'complete_appointment' && $request->appointmentId) {
                $appointment = \App\Models\Appointment::findOrFail($request->appointmentId);
                return redirect()->route('doctor.appointments.completed', $appointment)
                    ->with('success', $message . ' Review the completion summary below.');
            }

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
            $effectiveDoctorId = Auth::user()->getEffectiveDoctorUser()->id ?? Auth::id();
            Log::info('Voice Assistant - Complete consultation debug', [
                'patient_id' => $patient->id,
                'patient_name' => $patient->name,
                'patient_primary_doctor_id' => $patient->primary_doctor_id,
                'effective_doctor_id' => $effectiveDoctorId,
                'auth_id' => Auth::id(),
                'appointment_id' => $request->appointmentId,
                'completion_type' => $request->completionType
            ]);

            // Ensure the patient belongs to the authenticated doctor (more flexible check)
            $isAssignedPatient = $patient->primary_doctor_id === $effectiveDoctorId || 
                                $patient->primary_doctor_id === Auth::id() ||
                                Auth::user()->canAccessPatient($patient);
            
            if (!$isAssignedPatient) {
                Log::warning('Voice Assistant - Patient assignment check failed', [
                    'patient_id' => $patient->id,
                    'patient_primary_doctor_id' => $patient->primary_doctor_id,
                    'effective_doctor_id' => $effectiveDoctorId,
                    'auth_id' => Auth::id()
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Patient not assigned to this doctor.'
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

            // Update the voice transcription record
            VoiceTranscription::where('session_id', $request->sessionId)
                ->update([
                    'diagnosis_id' => $diagnosis->id,
                    'status' => 'diagnosis_created',
                ]);

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
                    'user_id' => Auth::id()
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
            $transcription = $request->input('transcription', '');
            $hasLiveTranscription = $request->input('has_live_transcription', false);
    
            // Initialize performance metrics
            $metrics = [
                'doctor_id' => Auth::id(),
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
                'transcription_length' => strlen($transcription),
                'has_live_transcription' => $hasLiveTranscription,
                'user_id' => Auth::id()
            ]);
    
            try {
                // Check if audio file is provided
                if (!$request->hasFile('audio_file')) {
                    $metrics['error_type'] = 'audio_upload';
                    $metrics['error_message'] = 'No audio file provided';
                    $this->recordPerformanceMetrics($metrics);
    
                    return response()->json([
                        'success' => false,
                        'message' => 'No audio file provided for server processing'
                    ], 400);
                }
    
                $audioFile = $request->file('audio_file');
    
                // Validate audio file
                if (!$this->isValidAudioFile($audioFile)) {
                    $metrics['error_type'] = 'audio_validation';
                    $metrics['error_message'] = 'Invalid audio file format';
                    $this->recordPerformanceMetrics($metrics);
    
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid audio file format. Supported: wav, mp3, webm, mp4'
                    ], 400);
                }
    
                // Record enhanced audio file metrics
                $metrics['audio_file_size'] = $audioFile->getSize();
                $metrics['audio_format'] = $audioFile->getClientOriginalExtension();
    
                // Get additional audio quality parameters from request
                $audioQuality = $request->input('audio_quality', []);
                if (!empty($audioQuality)) {
                    $metrics['audio_sample_rate'] = $audioQuality['sample_rate'] ?? null;
                    $metrics['audio_channels'] = $audioQuality['channels'] ?? null;
                    $metrics['average_audio_level'] = $audioQuality['average_level'] ?? null;
                }
    
                // Estimate audio duration from file size and format
                $metrics['audio_duration'] = $this->estimateAudioDuration($audioFile);
    
                // Store audio file temporarily
                $storeStartTime = microtime(true);
                $tempPath = $this->storeAudioFile($audioFile, $sessionId);
    
                // Process audio with server-side speech recognition
                $sttStartTime = microtime(true);
                $serverTranscription = $this->processAudioWithServerSTT($tempPath);
                $sttEndTime = microtime(true);
    
                $metrics['server_processing_success'] = !empty($serverTranscription);
                $metrics['server_processing_time'] = round(($sttEndTime - $sttStartTime) * 1000, 3); // Convert to milliseconds
                $metrics['server_transcript_length'] = strlen($serverTranscription);
    
                // Clean up temporary file
                if (file_exists($tempPath)) {
                    unlink($tempPath);
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
    
                // Record the performance metrics
                $this->recordPerformanceMetrics($metrics);
    
                \Log::info('HYBRID METHOD - Server processing completed', [
                    'session_id' => $sessionId,
                    'live_length' => strlen($transcription),
                    'server_length' => strlen($serverTranscription),
                    'improved_length' => strlen($improvedTranscription),
                    'data_extracted' => !empty($serverExtractedData),
                    'processing_time_ms' => $metrics['total_processing_time']
                ]);
    
                return response()->json([
                    'success' => true,
                    'message' => 'Server-side processing completed',
                    'improved_transcription' => $improvedTranscription,
                    'server_extracted_data' => $serverExtractedData,
                    'processing_method' => strlen($serverTranscription) > strlen($transcription) ? 'server' : 'live',
                    'improvement_ratio' => $metrics['transcript_improvement_ratio'] ?? 1,
                    'performance_metrics' => [
                        'processing_time_ms' => $metrics['total_processing_time'],
                        'server_improved' => $metrics['server_better_than_live'] ?? false
                    ]
                ]);
    
            } catch (\Exception $e) {
                $endTime = microtime(true);
                $metrics['overall_success'] = false;
                $metrics['total_processing_time'] = round(($endTime - $startTime) * 1000, 3);
                $metrics['error_type'] = 'server_processing';
                $metrics['error_message'] = $e->getMessage();
    
                $this->recordPerformanceMetrics($metrics);
    
                \Log::error('HYBRID METHOD - Server processing failed', [
                    'error' => $e->getMessage(),
                    'session_id' => $request->input('session_id'),
                    'user_id' => Auth::id(),
                    'processing_time_ms' => $metrics['total_processing_time']
                ]);
    
                return response()->json([
                    'success' => false,
                    'message' => 'Server processing failed: ' . $e->getMessage(),
                    'improved_transcription' => $transcription, // Fallback to live transcription
                    'server_extracted_data' => []
                ]);
            }
        }
    
        /**
         * Validate audio file format and size
         */
        private function isValidAudioFile($file)
        {
            if (!$file->isValid()) {
                return false;
            }
    
            $maxSize = 50 * 1024 * 1024; // 50MB
            if ($file->getSize() > $maxSize) {
                return false;
            }
    
            $allowedMimeTypes = [
                'audio/wav',
                'audio/mp3',
                'audio/mpeg',
                'audio/webm',
                'audio/mp4',
                'video/webm',
                'video/mp4'
            ];
    
            $allowedExtensions = ['wav', 'mp3', 'webm', 'mp4'];
            
            $mimeType = $file->getMimeType();
            $extension = $file->getClientOriginalExtension();
    
            return in_array($mimeType, $allowedMimeTypes) &&
                   in_array(strtolower($extension), $allowedExtensions);
        }
    
        /**
         * Store audio file temporarily for processing
         */
        private function storeAudioFile($file, $sessionId)
        {
            $tempDir = storage_path('app/temp/audio_processing');
            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0755, true);
            }
    
            $filename = "session_{$sessionId}_" . time() . '.' . $file->getClientOriginalExtension();
            $tempPath = $tempDir . '/' . $filename;
            
            $file->move($tempDir, $filename);
            
            return $tempPath;
        }
    
        /**
         * Process audio with server-side speech-to-text (OpenAI Whisper)
         */
        private function processAudioWithServerSTT($audioPath)
        {
            try {
                // Check if audio file exists
                if (!file_exists($audioPath)) {
                    throw new \Exception('Audio file not found');
                }
    
                // Use OpenAI Whisper API for server-side transcription
                $response = OpenAI::audio()->transcribe('whisper-1', fopen($audioPath, 'r'), [
                    'response_format' => 'text',
                    'language' => 'auto' // Auto-detect language
                ]);
    
                $transcription = is_string($response) ? $response : '';
                
                \Log::info('HYBRID METHOD - OpenAI Whisper transcription completed', [
                    'transcription_length' => strlen($transcription),
                    'preview' => substr($transcription, 0, 100)
                ]);
    
                return trim($transcription);
    
            } catch (\Exception $e) {
                \Log::error('HYBRID METHOD - OpenAI Whisper error', [
                    'error' => $e->getMessage(),
                    'audio_path' => $audioPath
                ]);
                
                return '';
            }
        }
    
        /**
         * Select the best transcription between live and server results
         */
        private function selectBestTranscription($liveTranscription, $serverTranscription)
        {
            // If no server transcription, use live
            if (empty($serverTranscription)) {
                return $liveTranscription;
            }
    
            // If no live transcription, use server
            if (empty($liveTranscription)) {
                return $serverTranscription;
            }
    
            // Compare lengths and content quality
            $liveLength = strlen($liveTranscription);
            $serverLength = strlen($serverTranscription);
            
            // Prefer server if it's significantly longer (likely more accurate)
            if ($serverLength > $liveLength * 1.2) {
                return $serverTranscription;
            }
            
            // If live is longer or similar, prefer live (real-time context)
            if ($liveLength >= $serverLength) {
                return $liveTranscription;
            }
            
            // Fallback to server
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
    }
