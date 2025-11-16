<?php

namespace App\Http\Controllers;

use App\Models\VoiceTranscription;
use App\Models\User;
use App\Models\Diagnosis;
use App\Models\AiAssistantResult;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
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

    public function index()
    {
        // Load patients for the dropdown with visit history
        $patients = [];
        $patientGroups = [];

        Log::info('Voice Assistant - Starting index method', [
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
                
            Log::info('Voice Assistant - Loaded patients using getEffectiveAssignedPatients', [
                'count' => $basePatients->count(),
                'patient_names' => $basePatients->pluck('name')->toArray()
            ]);
        } catch (\Exception $e) {
            Log::warning('Could not load assigned patients, trying fallback: ' . $e->getMessage());

            // Fallback: load all patients with role 'patient' for this doctor
            try {
                $effectiveDoctorId = Auth::user()->getEffectiveDoctorUser()->id ?? Auth::id();
                Log::info('Voice Assistant - Using fallback patient loading', [
                    'effective_doctor_id' => $effectiveDoctorId
                ]);
                
                $basePatients = User::where('role', 'patient')
                    ->where('primary_doctor_id', $effectiveDoctorId)
                    ->select('id', 'name', 'email', 'age', 'gender')
                    ->orderBy('name')
                    ->get();
                    
                Log::info('Voice Assistant - Loaded patients using fallback', [
                    'count' => $basePatients->count(),
                    'patient_names' => $basePatients->pluck('name')->toArray()
                ]);
            } catch (\Exception $e2) {
                $basePatients = collect();
                Log::error('Could not load patients at all: ' . $e2->getMessage());
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
            
            // Search for ACTIVE appointments only (pending/confirmed, today or future)
            $searchAttempts = [
                ['doctor_id' => $effectiveDoctorId, 'label' => 'effective_doctor_id'],
                ['doctor_id' => $loggedInUserId, 'label' => 'logged_in_user_id'],
            ];
            
            // If the patient belongs to this doctor, also try with patient's primary_doctor_id
            if ($patient->primary_doctor_id && in_array($patient->primary_doctor_id, [$effectiveDoctorId, $loggedInUserId])) {
                $searchAttempts[] = ['doctor_id' => $patient->primary_doctor_id, 'label' => 'patient_primary_doctor_id'];
            }
            
            foreach ($searchAttempts as $attempt) {
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

        Log::info('Voice Assistant - processWithAI called', [
            'session_id' => $sessionId,
            'transcription_length' => strlen($transcription),
            'transcription_preview' => substr($transcription, 0, 200)
        ]);

        // FIXED: Accept shorter transcriptions for medical content
        if (strlen($transcription) < 3) {
            Log::warning('Voice Assistant - Transcription too short', [
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
            
            Log::info('Voice Assistant - OpenAI response received', [
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
                
                Log::info('Voice Assistant - Medical data extraction successful', [
                    'session_id' => $sessionId,
                    'extracted_fields' => array_keys(array_filter($extractedData))
                ]);

                return response()->json([
                    'success' => true,
                    'extractedData' => $extractedData,
                    'message' => 'Medical data extracted successfully.'
                ]);
            } else {
                Log::warning('Voice Assistant - Failed to extract JSON, using fallback', [
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
            Log::error('Voice AI processing error: ' . $e->getMessage(), [
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
            Log::error('AI analysis error: ' . $e->getMessage());

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

    /**
     * HYBRID METHOD: Process audio file on server for enhanced accuracy
     * This endpoint handles server-side audio processing for better transcription accuracy
     */
    public function processAudioServer(Request $request)
    {
        try {
            $sessionId = $request->input('session_id');
            $transcription = $request->input('transcription', '');
            $hasLiveTranscription = $request->input('has_live_transcription', false);

            Log::info('HYBRID METHOD - Server audio processing started', [
                'session_id' => $sessionId,
                'transcription_length' => strlen($transcription),
                'has_live_transcription' => $hasLiveTranscription,
                'user_id' => Auth::id()
            ]);

            // Check if audio file is provided
            if (!$request->hasFile('audio_file')) {
                return response()->json([
                    'success' => false,
                    'message' => 'No audio file provided for server processing'
                ], 400);
            }

            $audioFile = $request->file('audio_file');
            
            // Validate audio file
            if (!$this->isValidAudioFile($audioFile)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid audio file format. Supported: wav, mp3, webm, mp4'
                ], 400);
            }

            // Store audio file temporarily
            $tempPath = $this->storeAudioFile($audioFile, $sessionId);
            
            // Process audio with server-side speech recognition
            $serverTranscription = $this->processAudioWithServerSTT($tempPath);
            
            // Clean up temporary file
            if (file_exists($tempPath)) {
                unlink($tempPath);
            }

            // Compare results and return the better one
            $improvedTranscription = $this->selectBestTranscription($transcription, $serverTranscription);
            
            // Extract medical data from improved transcription
            $serverExtractedData = [];
            if ($improvedTranscription && strlen($improvedTranscription) > 5) {
                $serverExtractedData = $this->extractMedicalDataFromText($improvedTranscription);
            }

            Log::info('HYBRID METHOD - Server processing completed', [
                'session_id' => $sessionId,
                'live_length' => strlen($transcription),
                'server_length' => strlen($serverTranscription),
                'improved_length' => strlen($improvedTranscription),
                'data_extracted' => !empty($serverExtractedData)
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Server-side processing completed',
                'improved_transcription' => $improvedTranscription,
                'server_extracted_data' => $serverExtractedData,
                'processing_method' => strlen($serverTranscription) > strlen($transcription) ? 'server' : 'live',
                'improvement_ratio' => $serverTranscription ? (strlen($serverTranscription) / max(strlen($transcription), 1)) : 1
            ]);

        } catch (\Exception $e) {
            Log::error('HYBRID METHOD - Server processing failed', [
                'error' => $e->getMessage(),
                'session_id' => $request->input('session_id'),
                'user_id' => Auth::id()
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
            $response = OpenAI::audio()->transcribe(
                'whisper-1', 
                fopen($audioPath, 'r'), 
                [
                    'response_format' => 'text',
                    'language' => 'auto' // Auto-detect language
                ]
            );

            $transcription = is_string($response) ? $response : '';
            
            Log::info('HYBRID METHOD - OpenAI Whisper transcription completed', [
                'transcription_length' => strlen($transcription),
                'preview' => substr($transcription, 0, 100)
            ]);

            return trim($transcription);

        } catch (\Exception $e) {
            Log::error('HYBRID METHOD - OpenAI Whisper error', [
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
            Log::error('HYBRID METHOD - Medical data extraction failed', [
                'error' => $e->getMessage(),
                'transcription_preview' => substr($transcription, 0, 100)
            ]);
            
            return [];
        }
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
}