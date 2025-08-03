<?php

namespace App\Livewire;

use App\Models\VoiceTranscription;
use App\Models\User;
use App\Models\Diagnosis;
use App\Models\AiAssistantResult;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Livewire\Component;
use OpenAI\Laravel\Facades\OpenAI;

class VoiceAssistant extends Component
{
    public string $sessionId = '';
    public bool $isRecording = false;
    public bool $isHandsFreeMode = false;
    public string $transcription = '';
    public array $extractedData = [];
    public string $aiAnalysis = '';
    public array $structuredChart = [];
    public $selectedPatient = null;
    public array $patients = [];
    public bool $isProcessing = false;
    public string $processingStage = '';
    public bool $showConfirmation = false;

    // Chart fields
    public string $symptoms = '';
    public string $medicalHistory = '';
    public string $physicalFindings = '';
    public string $medications = '';
    public string $vitalSigns = '';
    public string $diagnosis = '';
    public string $carePlan = '';

    // New patient creation fields
    public bool $showNewPatientForm = false;
    public string $newPatientName = '';
    public string $newPatientEmail = '';
    public int $newPatientAge = 0;
    public string $newPatientGender = '';
    public string $newPatientPhone = '';

    // AI result and manual diagnosis
    public bool $showDiagnosisApproval = false;
    public bool $showManualDiagnosisForm = false;
    public string $manualDiagnosisText = '';
    public $aiResultId = null;
    public bool $diagnosisCreated = false;

    protected $listeners = [
        'transcriptionReceived' => 'handleTranscription',
        'voiceRecordingStarted' => 'startRecording',
        'voiceRecordingStopped' => 'stopRecording',
    ];

    public function updatedSelectedPatient($value)
    {
        \Log::info('Patient selection changed', [
            'selectedPatient' => $value,
            'canStartRecording' => $this->canStartRecording()
        ]);
    }

    public function testLivewire()
    {
        session()->flash('success', 'Livewire is working! Button clicked successfully.');
        \Log::info('Test Livewire method called');
    }

    public function getCanStartRecordingProperty()
    {
        return !empty($this->selectedPatient) && !$this->isRecording;
    }

    public function mount()
    {
        $this->sessionId = Str::uuid()->toString();

        try {
            $this->patients = Auth::user()->assignedPatients()
                ->select('id', 'name', 'email', 'age', 'gender')
                ->orderBy('name')
                ->get()
                ->toArray();

            \Log::info('Patients loaded in mount', [
                'count' => count($this->patients),
                'patients' => $this->patients
            ]);
        } catch (\Exception $e) {
            \Log::warning('Could not load assigned patients, trying fallback: ' . $e->getMessage());

            // Fallback: load all patients with role 'patient' for this doctor
            try {
                $this->patients = User::where('role', 'patient')
                    ->where('primary_doctor_id', Auth::id())
                    ->select('id', 'name', 'email', 'age', 'gender')
                    ->orderBy('name')
                    ->get()
                    ->toArray();

                \Log::info('Patients loaded via fallback', [
                    'count' => count($this->patients)
                ]);
            } catch (\Exception $e2) {
                $this->patients = [];
                \Log::error('Could not load patients at all: ' . $e2->getMessage());
            }
        }
    }

    public function startSession()
    {
        if (!$this->selectedPatient) {
            session()->flash('error', 'Please select a patient first.');
            return;
        }

        $this->sessionId = Str::uuid()->toString();
        $this->isRecording = true;
        $this->transcription = '';
        $this->extractedData = [];
        $this->aiAnalysis = '';
        $this->structuredChart = [];
        $this->resetChartFields();

        // Debug log
        \Log::info('Start session called', [
            'selectedPatient' => $this->selectedPatient,
            'isRecording' => $this->isRecording
        ]);

        // Create initial transcription record
        VoiceTranscription::create([
            'doctor_id' => Auth::id(),
            'patient_id' => $this->selectedPatient,
            'session_id' => $this->sessionId,
            'raw_transcription' => '',
            'status' => 'active',
            'session_started_at' => now(),
        ]);

        $this->dispatch('startVoiceRecording');
    }

    public function stopSession()
    {
        $this->isRecording = false;

        // Update transcription record
        VoiceTranscription::where('session_id', $this->sessionId)
            ->update([
                'status' => 'completed',
                'session_ended_at' => now(),
            ]);

        $this->dispatch('stopVoiceRecording');
    }

    public function toggleHandsFreeMode()
    {
        $this->isHandsFreeMode = !$this->isHandsFreeMode;

        if ($this->isHandsFreeMode && !$this->isRecording) {
            $this->startSession();
        }
    }

    public function handleTranscription($text)
    {
        // Clean and validate the input
        $cleanText = trim($text);
        if (empty($cleanText)) {
            return;
        }

        // Avoid duplicate processing of the same text
        if ($this->transcription === $cleanText) {
            return;
        }

        $this->transcription = $cleanText;
        $this->isProcessing = true;
        $this->processingStage = 'Processing voice transcription...';

        try {
            // Update the transcription in database
            VoiceTranscription::where('session_id', $this->sessionId)
                ->update([
                    'raw_transcription' => $this->transcription,
                    'updated_at' => now()
                ]);

            // Process with AI to extract medical data (run in background to avoid timeout)
            $this->processWithAI($cleanText);

        } catch (\Exception $e) {
            \Log::error('Handle transcription error: ' . $e->getMessage());
            $this->processingStage = 'Transcription processing failed.';
        }

        $this->isProcessing = false;
        $this->processingStage = '';
    }

    private function processWithAI($newText)
    {
        // Skip processing if transcription is too short or hasn't changed significantly
        if (strlen($this->transcription) < 10) {
            $this->isProcessing = false;
            return;
        }

        // Set longer timeout for AI processing
        set_time_limit(120); // 2 minutes

        $this->processingStage = 'Analyzing medical content with AI...';

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
                        'content' => "Extract medical information from this consultation transcription (may contain Arabic and English): \n\n" . $this->transcription
                    ]
                ],
                'temperature' => 0.2, // Lower temperature for more consistent extraction
                'max_tokens' => 1500, // Limit tokens to avoid timeout
            ]);

            $aiResponse = $response['choices'][0]['message']['content'] ?? '';

            $this->processingStage = 'Parsing AI response and extracting medical data...';

            // Clean the response to extract JSON
            $jsonStart = strpos($aiResponse, '{');
            $jsonEnd = strrpos($aiResponse, '}');

            if ($jsonStart !== false && $jsonEnd !== false) {
                $jsonString = substr($aiResponse, $jsonStart, $jsonEnd - $jsonStart + 1);
                $extractedData = json_decode($jsonString, true);

                if ($extractedData && is_array($extractedData)) {
                    $this->processingStage = 'Updating medical chart fields...';
                    $this->extractedData = $extractedData;
                    $this->updateChartFields($extractedData);

                    $this->processingStage = 'Medical data extraction completed!';

                    \Log::info('Medical data extracted successfully', [
                        'session_id' => $this->sessionId,
                        'extracted_fields' => array_keys($extractedData)
                    ]);
                } else {
                    $this->processingStage = 'Failed to parse AI response.';
                    \Log::warning('Failed to parse AI response as JSON', [
                        'response' => $aiResponse
                    ]);
                }
            }

        } catch (\Exception $e) {
            \Log::error('Voice AI processing error: ' . $e->getMessage(), [
                'session_id' => $this->sessionId,
                'transcription_length' => strlen($this->transcription)
            ]);
        }

        $this->isProcessing = false;
    }

    private function updateChartFields($data)
    {
        // Update fields with smart merging to avoid duplicates
        $this->symptoms = $this->smartAppendToField($this->symptoms, $data['symptoms'] ?? '');
        $this->medicalHistory = $this->smartAppendToField($this->medicalHistory, $data['medical_history'] ?? '');
        $this->physicalFindings = $this->smartAppendToField($this->physicalFindings, $data['physical_findings'] ?? '');
        $this->medications = $this->smartAppendToField($this->medications, $data['medications'] ?? '');
        $this->vitalSigns = $this->smartAppendToField($this->vitalSigns, $data['vital_signs'] ?? '');
        $this->diagnosis = $this->smartAppendToField($this->diagnosis, $data['diagnosis'] ?? '');
        $this->carePlan = $this->smartAppendToField($this->carePlan, $data['care_plan'] ?? '');

        // Log the updated fields for debugging
        \Log::info('Chart fields updated', [
            'session_id' => $this->sessionId,
            'symptoms_length' => strlen($this->symptoms),
            'medical_history_length' => strlen($this->medicalHistory),
            'physical_findings_length' => strlen($this->physicalFindings),
            'medications_length' => strlen($this->medications),
            'vital_signs_length' => strlen($this->vitalSigns),
            'diagnosis_length' => strlen($this->diagnosis),
            'care_plan_length' => strlen($this->carePlan),
        ]);
    }

    private function smartAppendToField($existing, $new)
    {
        if (empty($new)) return $existing;
        if (empty($existing)) return $new;

        // Clean and normalize text
        $existing = trim($existing);
        $new = trim($new);

        // Avoid duplicating similar content
        $existingWords = explode(' ', strtolower($existing));
        $newWords = explode(' ', strtolower($new));
        $commonWords = array_intersect($existingWords, $newWords);

        // If more than 70% of words are common, replace instead of append
        if (count($commonWords) > 0.7 * count($newWords)) {
            return $new; // Replace with newer, potentially more complete information
        }

        return $existing . "\n\n" . $new;
    }

    private function generateAIAnalysis()
    {
        // Set longer timeout for AI analysis
        set_time_limit(120); // 2 minutes

        try {
            $this->processingStage = 'Preparing medical analysis parameters...';

            // Get the user's specialty and use the existing preparePrompt function logic
            $specialty = Auth::user()->setting->specialty ?? 'Internal Medicine';

            $criterion = Auth::user()->setting->criterion ?? 'CDC';

            // Prepare input data similar to the existing OpenAI controller
            $inputData = [
                'patient_name' => $this->selectedPatient ? User::find($this->selectedPatient)->name : 'Unknown',
                'symptoms' => $this->symptoms,
                'past_medical_history' => $this->medicalHistory,
                'physical_findings' => $this->physicalFindings,
                'medication_history' => $this->medications,
                'vital_signs' => $this->vitalSigns,
                'chief_complaint' => $this->symptoms,
                'physician_notes' => $this->diagnosis,
                'additional_notes' => $this->carePlan,
            ];

            $this->processingStage = 'Generating comprehensive medical analysis...';

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

            $this->aiAnalysis = $response['choices'][0]['message']['content'] ?? '';

            // Add debugging
            Log::info('AI Analysis generated', [
                'aiAnalysisLength' => strlen($this->aiAnalysis),
                'aiAnalysisEmpty' => empty($this->aiAnalysis),
                'diagnosisApproved' => $this->diagnosisApproved
            ]);

            $this->processingStage = 'Saving analysis results to database...';

            // Update database
            VoiceTranscription::where('session_id', $this->sessionId)
                ->update([
                    'extracted_data' => $this->extractedData,
                    'ai_analysis' => $this->aiAnalysis,
                    'structured_chart' => [
                        'symptoms' => $this->symptoms,
                        'medical_history' => $this->medicalHistory,
                        'physical_findings' => $this->physicalFindings,
                        'medications' => $this->medications,
                        'vital_signs' => $this->vitalSigns,
                        'diagnosis' => $this->diagnosis,
                        'care_plan' => $this->carePlan,
                    ]
                ]);

            $this->processingStage = 'AI analysis completed successfully!';

            // Add debugging after database update
            Log::info('AI Analysis saved to database', [
                'sessionId' => $this->sessionId
            ]);

        } catch (\Exception $e) {
            $this->processingStage = 'AI analysis failed. Please try again.';
            \Log::error('AI analysis error: ' . $e->getMessage());
        }
    }

    public function generateAnalysis()
    {
        if (empty($this->transcription)) {
            session()->flash('error', 'No transcription available. Please record some audio first.');
            return;
        }

        if (!$this->selectedPatient) {
            session()->flash('error', 'Please select a patient first.');
            return;
        }

        // Force UI update by setting processing state first
        $this->isProcessing = true;
        $this->processingStage = 'Initializing AI analysis...';

        // Force Livewire to update the UI immediately
        $this->dispatch('$refresh');

        // Add a small delay to ensure UI updates
        usleep(100000); // 0.1 second delay

        // Set longer timeout for AI processing
        set_time_limit(120); // 2 minutes

        try {
            // First, extract structured data from transcription if not already done
            if (empty($this->extractedData)) {
                $this->processingStage = 'Extracting medical data from transcription...';
                $this->processWithAI($this->transcription);
            }

            // Generate comprehensive AI analysis
            $this->processingStage = 'Generating comprehensive medical analysis...';
            $this->generateAIAnalysis();

            $this->processingStage = 'Analysis completed successfully!';
            session()->flash('success', 'AI analysis generated successfully!');
        } catch (\Exception $e) {
            \Log::error('Generate analysis error: ' . $e->getMessage());
            $this->processingStage = 'Analysis failed. Please try again.';
            session()->flash('error', 'Failed to generate analysis. Please try again.');
        }

        $this->isProcessing = false;
        $this->processingStage = '';
    }

    public function confirmAndSave()
    {
        $this->showConfirmation = true;
    }

    public function finalizeSession()
    {
        // Mark as confirmed and final
        VoiceTranscription::where('session_id', $this->sessionId)
            ->update([
                'is_confirmed' => true,
                'is_final' => true,
                'structured_chart' => [
                    'symptoms' => $this->symptoms,
                    'medical_history' => $this->medicalHistory,
                    'physical_findings' => $this->physicalFindings,
                    'medications' => $this->medications,
                    'vital_signs' => $this->vitalSigns,
                    'diagnosis' => $this->diagnosis,
                    'care_plan' => $this->carePlan,
                ]
            ]);

        // Optionally create a diagnosis record using existing system
        if ($this->selectedPatient && !empty($this->diagnosis)) {
            // You can integrate with the existing diagnosis system here
        }

        session()->flash('success', 'Voice consultation has been saved successfully!');
        $this->resetSession();
    }

    private function resetChartFields()
    {
        $this->symptoms = '';
        $this->medicalHistory = '';
        $this->physicalFindings = '';
        $this->medications = '';
        $this->vitalSigns = '';
        $this->diagnosis = '';
        $this->carePlan = '';
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

    // New Patient Creation Methods
    public function showNewPatientForm()
    {
        $this->showNewPatientForm = true;
        $this->resetNewPatientFields();

        // Debug log
        \Log::info('Show new patient form called', [
            'showNewPatientForm' => $this->showNewPatientForm
        ]);

        // Add a flash message to confirm the method was called
        session()->flash('info', 'New patient form opened');
    }

    public function hideNewPatientForm()
    {
        $this->showNewPatientForm = false;
        $this->resetNewPatientFields();
    }

    public function createNewPatient()
    {
        $this->validate([
            'newPatientName' => 'required|string|max:255',
            'newPatientEmail' => 'required|email|unique:users,email',
            'newPatientAge' => 'required|integer|min:1|max:150',
            'newPatientGender' => 'required|in:male,female,other',
            'newPatientPhone' => 'nullable|string|max:20',
        ]);

        // Create new patient user
        $patient = User::create([
            'name' => $this->newPatientName,
            'email' => $this->newPatientEmail,
            'password' => Hash::make('patient123'), // Default password
            'role' => 'patient',
            'age' => $this->newPatientAge,
            'gender' => $this->newPatientGender,
            'phone' => $this->newPatientPhone,
            'primary_doctor_id' => Auth::id(), // Assign current doctor as primary
            'email_verified_at' => now(), // Auto-verify for doctor-created accounts
        ]);

        // Add to patients list
        $this->patients[] = [
            'id' => $patient->id,
            'name' => $patient->name,
            'email' => $patient->email,
            'age' => $patient->age,
            'gender' => $patient->gender,
        ];

        // Select the new patient
        $this->selectedPatient = $patient->id;

        // Hide form and reset fields
        $this->showNewPatientForm = false;
        $this->resetNewPatientFields();

        session()->flash('success', 'New patient created successfully! Default password is "patient123" - please inform the patient to change it.');
    }

    private function resetNewPatientFields()
    {
        $this->newPatientName = '';
        $this->newPatientEmail = '';
        $this->newPatientAge = 0;
        $this->newPatientGender = '';
        $this->newPatientPhone = '';
    }

    // Diagnosis Approval Methods
    public function showDiagnosisApproval()
    {
        // Add debugging
        Log::info('showDiagnosisApproval method called', [
            'aiAnalysis' => !empty($this->aiAnalysis),
            'selectedPatient' => $this->selectedPatient,
            'diagnosisApproved' => $this->diagnosisApproved,
            'showDiagnosisApproval' => $this->showDiagnosisApproval
        ]);

        if (empty($this->aiAnalysis) || !$this->selectedPatient) {
            session()->flash('error', 'Please generate AI analysis and select a patient first.');
            Log::info('showDiagnosisApproval method - conditions not met', [
                'aiAnalysisEmpty' => empty($this->aiAnalysis),
                'selectedPatient' => $this->selectedPatient
            ]);
            return;
        }

        $this->showDiagnosisApproval = true;

        // Add debugging for state change
        Log::info('showDiagnosisApproval state changed', [
            'showDiagnosisApproval' => $this->showDiagnosisApproval
        ]);
    }

    public function createAiAssistantResult()
    {
        if (!$this->selectedPatient || empty($this->aiAnalysis)) {
            session()->flash('error', 'Cannot create AI result without patient selection and AI analysis.');
            return;
        }

        // Create AI assistant result record
        $aiResult = AiAssistantResult::create([
            'doctor_id' => Auth::id(),
            'patient_id' => $this->selectedPatient,
            'source' => 'voice_assistant',
            'ai_analysis' => $this->aiAnalysis,
            'voice_transcript' => $this->transcription,
            'session_id' => $this->sessionId,
            'patient_data' => [
                'symptoms' => $this->symptoms,
                'medical_history' => $this->medicalHistory,
                'physical_findings' => $this->physicalFindings,
                'medications' => $this->medications,
                'vital_signs' => $this->vitalSigns,
                'care_plan' => $this->carePlan,
                'session_id' => $this->sessionId,
            ],
            'status' => 'pending',
        ]);

        // Update the voice transcription record
        VoiceTranscription::where('session_id', $this->sessionId)
            ->update([
                'ai_assistant_result_id' => $aiResult->id,
                'status' => 'ai_analysis_complete',
            ]);

        $this->aiResultId = $aiResult->id;
        $this->showDiagnosisApproval = false;
        $this->showManualDiagnosisForm = true;

        session()->flash('success', 'AI analysis completed! Now write your professional diagnosis.');
    }

    public function createManualDiagnosis()
    {
        if (empty($this->manualDiagnosisText)) {
            session()->flash('error', 'Please enter your diagnosis text.');
            return;
        }

        if (!$this->aiResultId) {
            session()->flash('error', 'AI result not found. Please generate AI analysis first.');
            return;
        }

        try {
            // Get the AI assistant result
            $aiResult = AiAssistantResult::findOrFail($this->aiResultId);

            // Get the patient
            $patient = User::findOrFail($this->selectedPatient);

            // Create the manual diagnosis
            $diagnosis = Diagnosis::create([
                'doctor_id' => Auth::id(),
                'patient_id' => $patient->id,
                'diagnosis_text' => $this->manualDiagnosisText,
                'voice_transcript' => $this->transcription,
                'patient_data' => $aiResult->patient_data,
            ]);

            // Link the AI result to this diagnosis
            $aiResult->linkToDiagnosis($diagnosis->id);

            // Update the voice transcription record
            VoiceTranscription::where('session_id', $this->sessionId)
                ->update([
                    'diagnosis_id' => $diagnosis->id,
                    'status' => 'diagnosis_created',
                ]);

            $this->diagnosisCreated = true;
            $this->showManualDiagnosisForm = false;

            session()->flash('success', 'Manual diagnosis created successfully and linked to AI analysis! Patient can now view it from their account.');

            // Redirect to diagnosis view
            return redirect()->route('diagnosis.show', $diagnosis);

        } catch (\Exception $e) {
            Log::error('Manual diagnosis creation failed: ' . $e->getMessage());
            session()->flash('error', 'Failed to create diagnosis. Please try again.');
        }
    }

    public function rejectDiagnosis()
    {
        $this->showDiagnosisApproval = false;
        session()->flash('info', 'AI analysis not used. You can continue editing or generate a new analysis.');
    }

    // Fix the Start Recording button issue
    public function canStartRecording()
    {
        return $this->selectedPatient !== null && !$this->isRecording;
    }

    // Updated resetSession to include new fields
    public function resetSession()
    {
        // Add debugging
        Log::info('resetSession called', [
            'showDiagnosisApproval' => $this->showDiagnosisApproval,
            'showManualDiagnosisForm' => $this->showManualDiagnosisForm,
            'aiAnalysisEmpty' => empty($this->aiAnalysis)
        ]);

        // Stop any ongoing recording first
        if ($this->isRecording) {
            $this->stopSession();
        }

        $this->sessionId = Str::uuid()->toString();
        $this->isRecording = false;
        $this->isHandsFreeMode = false;
        $this->transcription = '';
        $this->extractedData = [];
        $this->aiAnalysis = '';
        $this->structuredChart = [];
        $this->isProcessing = false;
        $this->processingStage = '';
        $this->showConfirmation = false;
        $this->showDiagnosisApproval = false;
        $this->showManualDiagnosisForm = false;
        $this->manualDiagnosisText = '';
        $this->aiResultId = null;
        $this->diagnosisCreated = false;
        $this->resetChartFields();

        // Don't reset selectedPatient and new patient fields to maintain user selection
        // $this->selectedPatient = null;
        // $this->resetNewPatientFields();

        session()->flash('success', 'Session reset successfully!');

        // Dispatch event to stop voice recording on frontend
        $this->dispatch('stopVoiceRecording');
    }

    public function render()
    {
        return view('livewire.voice-assistant');
    }
}
