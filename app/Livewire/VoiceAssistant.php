<?php

namespace App\Livewire;

use App\Models\VoiceTranscription;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
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
    public ?int $selectedPatient = null;
    public array $patients = [];
    public bool $isProcessing = false;
    public bool $showConfirmation = false;

    // Chart fields
    public string $symptoms = '';
    public string $medicalHistory = '';
    public string $physicalFindings = '';
    public string $medications = '';
    public string $vitalSigns = '';
    public string $diagnosis = '';
    public string $carePlan = '';

    protected $listeners = [
        'transcriptionReceived' => 'handleTranscription',
        'voiceRecordingStarted' => 'startRecording',
        'voiceRecordingStopped' => 'stopRecording',
    ];

    public function mount()
    {
        $this->sessionId = Str::uuid()->toString();

        try {
            $this->patients = Auth::user()->assignedPatients()
                ->select('id', 'name', 'email', 'age', 'gender')
                ->orderBy('name')
                ->get()
                ->toArray();
        } catch (\Exception $e) {
            // If assignedPatients relationship doesn't exist, use empty array
            $this->patients = [];
            \Log::warning('Could not load assigned patients: ' . $e->getMessage());
        }
    }

    public function startSession()
    {
        $this->sessionId = Str::uuid()->toString();
        $this->isRecording = true;
        $this->transcription = '';
        $this->extractedData = [];
        $this->aiAnalysis = '';
        $this->structuredChart = [];
        $this->resetChartFields();

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
        $this->transcription .= ' ' . $text;
        $this->isProcessing = true;

        // Update the transcription in database
        VoiceTranscription::where('session_id', $this->sessionId)
            ->update(['raw_transcription' => $this->transcription]);

        // Process with AI to extract medical data
        $this->processWithAI($text);
    }

    private function processWithAI($newText)
    {
        try {
            // Use OpenAI to extract medical information
            $response = OpenAI::chat()->create([
                'model' => 'gpt-4o',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are a medical AI assistant helping to extract structured medical information from doctor-patient consultations. Extract and categorize the following information from the transcription:

                        1. Symptoms (patient complaints, pain descriptions, etc.)
                        2. Medical History (past conditions, surgeries, family history)
                        3. Physical Findings (examination results, observations)
                        4. Medications (current medications, allergies)
                        5. Vital Signs (blood pressure, temperature, heart rate, etc.)
                        6. Potential Diagnosis (based on symptoms and findings)
                        7. Care Plan (treatment recommendations, follow-up)

                        Return the response in JSON format with these exact keys: symptoms, medical_history, physical_findings, medications, vital_signs, diagnosis, care_plan.
                        Only include information that is explicitly mentioned in the transcription. If a category has no information, return an empty string.'
                    ],
                    [
                        'role' => 'user',
                        'content' => "Extract medical information from this consultation transcription: \n\n" . $this->transcription
                    ]
                ],
                'temperature' => 0.3,
            ]);

            $aiResponse = $response['choices'][0]['message']['content'] ?? '';

            // Try to parse JSON response
            $extractedData = json_decode($aiResponse, true);

            if ($extractedData) {
                $this->extractedData = $extractedData;
                $this->updateChartFields($extractedData);

                // Generate AI analysis
                $this->generateAIAnalysis();
            }

        } catch (\Exception $e) {
            \Log::error('Voice AI processing error: ' . $e->getMessage());
        }

        $this->isProcessing = false;
    }

    private function updateChartFields($data)
    {
        $this->symptoms = $this->appendToField($this->symptoms, $data['symptoms'] ?? '');
        $this->medicalHistory = $this->appendToField($this->medicalHistory, $data['medical_history'] ?? '');
        $this->physicalFindings = $this->appendToField($this->physicalFindings, $data['physical_findings'] ?? '');
        $this->medications = $this->appendToField($this->medications, $data['medications'] ?? '');
        $this->vitalSigns = $this->appendToField($this->vitalSigns, $data['vital_signs'] ?? '');
        $this->diagnosis = $this->appendToField($this->diagnosis, $data['diagnosis'] ?? '');
        $this->carePlan = $this->appendToField($this->carePlan, $data['care_plan'] ?? '');
    }

    private function appendToField($existing, $new)
    {
        if (empty($new)) return $existing;
        if (empty($existing)) return $new;
        return $existing . "\n" . $new;
    }

    private function generateAIAnalysis()
    {
        try {
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

        } catch (\Exception $e) {
            \Log::error('AI analysis error: ' . $e->getMessage());
        }
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

    public function resetSession()
    {
        $this->sessionId = Str::uuid()->toString();
        $this->isRecording = false;
        $this->isHandsFreeMode = false;
        $this->transcription = '';
        $this->extractedData = [];
        $this->aiAnalysis = '';
        $this->structuredChart = [];
        $this->selectedPatient = null;
        $this->isProcessing = false;
        $this->showConfirmation = false;
        $this->resetChartFields();
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

    public function render()
    {
        return view('livewire.voice-assistant');
    }
}
