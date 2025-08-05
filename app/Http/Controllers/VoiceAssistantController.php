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
use Illuminate\Support\Str;
use OpenAI\Laravel\Facades\OpenAI;

class VoiceAssistantController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (!Auth::user()->isDoctor() || !Auth::user()->doctor) {
                abort(403, 'Access denied. Doctor profile required.');
            }
            return $next($request);
        });
    }

    public function index()
    {
        // Load patients for the dropdown
        $patients = [];
        try {
            $patients = Auth::user()->assignedPatients()
                ->select('id', 'name', 'email', 'age', 'gender')
                ->orderBy('name')
                ->get()
                ->toArray();
        } catch (\Exception $e) {
            \Log::warning('Could not load assigned patients, trying fallback: ' . $e->getMessage());

            // Fallback: load all patients with role 'patient' for this doctor
            try {
                $patients = User::where('role', 'patient')
                    ->where('primary_doctor_id', Auth::id())
                    ->select('id', 'name', 'email', 'age', 'gender')
                    ->orderBy('name')
                    ->get()
                    ->toArray();
            } catch (\Exception $e2) {
                $patients = [];
                \Log::error('Could not load patients at all: ' . $e2->getMessage());
            }
        }

        // Generate initial session ID
        $sessionId = Str::uuid()->toString();

        return view('voice-assistant.index', compact('patients', 'sessionId'));
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

        if (strlen($transcription) < 10) {
            return response()->json([
                'success' => false,
                'message' => 'Transcription too short for processing.'
            ]);
        }

        try {
            // Use OpenAI to extract medical information with improved prompt
            $response = OpenAI::chat()->create([
                'model' => 'gpt-4o',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are a medical AI assistant helping to extract structured medical information from doctor-patient consultations. Extract and categorize the following information from the transcription:

                        1. Symptoms: Patient complaints, pain descriptions, discomfort, functional limitations
                        2. Medical History: Past conditions, surgeries, family history, previous treatments
                        3. Physical Findings: Examination results, observations, clinical signs
                        4. Medications: Current medications, dosages, allergies, drug interactions
                        5. Vital Signs: Blood pressure, temperature, heart rate, respiratory rate, oxygen saturation, weight, height
                        6. Diagnosis: Potential diagnoses, differential diagnoses, clinical impressions
                        7. Care Plan: Treatment recommendations, follow-up instructions, referrals, lifestyle modifications

                        IMPORTANT:
                        - Extract information in both Arabic and English if present
                        - Be comprehensive but accurate - only include explicitly mentioned information
                        - For vital signs, include units and normal/abnormal indicators
                        - For medications, include dosage, frequency, and route if mentioned
                        - For symptoms, include severity, duration, and quality descriptors

                        Return the response in JSON format with these exact keys: symptoms, medical_history, physical_findings, medications, vital_signs, diagnosis, care_plan.
                        If a category has no information, return an empty string.'
                    ],
                    [
                        'role' => 'user',
                        'content' => "Extract medical information from this consultation transcription (may contain Arabic and English): \n\n" . $transcription
                    ]
                ],
                'temperature' => 0.2, // Lower temperature for more consistent extraction
                'max_tokens' => 1500, // Limit tokens to avoid timeout
            ]);

            $aiResponse = $response['choices'][0]['message']['content'] ?? '';

            // Clean the response to extract JSON
            $jsonStart = strpos($aiResponse, '{');
            $jsonEnd = strrpos($aiResponse, '}');

            if ($jsonStart !== false && $jsonEnd !== false) {
                $jsonString = substr($aiResponse, $jsonStart, $jsonEnd - $jsonStart + 1);
                $extractedData = json_decode($jsonString, true);

                if ($extractedData && is_array($extractedData)) {
                    // Update the transcription record with extracted data
                    $transcriptionRecord = VoiceTranscription::where('session_id', $sessionId)->first();
                    if ($transcriptionRecord) {
                        $transcriptionRecord->update([
                            'extracted_data' => $extractedData
                        ]);
                    }

                    return response()->json([
                        'success' => true,
                        'extractedData' => $extractedData,
                        'message' => 'Medical data extracted successfully.'
                    ]);
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'Failed to parse AI response.'
                    ]);
                }
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to extract JSON from AI response.'
                ]);
            }
        } catch (\Exception $e) {
            \Log::error('Voice AI processing error: ' . $e->getMessage(), [
                'session_id' => $sessionId,
                'transcription_length' => strlen($transcription)
            ]);

            return response()->json([
                'success' => false,
                'message' => 'AI processing failed: ' . $e->getMessage()
            ]);
        }
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

            // Prepare input data similar to the existing OpenAI controller
            $inputData = [
                'patient_name' => $selectedPatient ? User::find($selectedPatient)->name : 'Unknown',
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

        if (!$aiResultId) {
            return response()->json([
                'success' => false,
                'message' => 'AI result not found. Please generate AI analysis first.'
            ]);
        }

        try {
            // Get the AI assistant result
            $aiResult = AiAssistantResult::findOrFail($aiResultId);

            // Get the patient
            $patient = User::findOrFail($selectedPatient);

            // Create the manual diagnosis
            $diagnosis = Diagnosis::create([
                'doctor_id' => Auth::id(),
                'patient_id' => $patient->id,
                'type' => 'voice_assistant',
                'diagnosis_text' => $manualDiagnosisText,
                'voice_transcript' => $transcription,
                'patient_data' => $aiResult->patient_data,
            ]);

            // Link the AI result to this diagnosis
            $aiResult->linkToDiagnosis($diagnosis->id);

            // Update the voice transcription record
            VoiceTranscription::where('session_id', $sessionId)
                ->update([
                    'diagnosis_id' => $diagnosis->id,
                    'status' => 'diagnosis_created',
                ]);

            // Send voice transcription completion notifications
            $this->sendVoiceTranscriptionNotifications($diagnosis, $transcription);

            return response()->json([
                'success' => true,
                'diagnosisId' => $diagnosis->id,
                'message' => 'Manual diagnosis created successfully and linked to AI analysis! Patient can now view it from their account.',
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
        Name: {$inputData['patient_name']} | Key Symptoms: {$inputData['symptoms']}
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
     * Send notifications for voice transcription completion
     */
    private function sendVoiceTranscriptionNotifications(Diagnosis $diagnosis, string $transcription)
    {
        try {
            // Send notification to patient about new voice diagnosis
            if ($diagnosis->patient && $diagnosis->patient->wantsNotification('voice_transcription_completed')) {
                $diagnosis->patient->notifyIfWants(new \App\Notifications\VoiceTranscriptionCompletedNotification($diagnosis, $transcription));
            }

            // Send notification to doctor about voice transcription completion
            if ($diagnosis->doctor && $diagnosis->doctor->user) {
                $doctor = $diagnosis->doctor->user;

                if ($doctor->wantsNotification('voice_transcription_completed')) {
                    $doctor->notifyIfWants(new \App\Notifications\SystemAlertNotification(
                        'Voice Diagnosis Completed',
                        "Voice transcription diagnosis completed for patient {$diagnosis->patient->name}. Diagnosis ID: {$diagnosis->id}",
                        'success',
                        route('diagnosis.show', $diagnosis)
                    ));
                }
            }

        } catch (\Exception $e) {
            // Log notification errors but don't break the diagnosis process
            \Log::error('Failed to send voice transcription notifications: ' . $e->getMessage());
        }
    }

}
