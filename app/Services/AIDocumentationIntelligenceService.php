<?php

namespace App\Services;

use App\Models\ClinicalDocumentationIntelligence;
use App\Models\Appointment;
use App\Models\VoiceTranscription;
use App\Services\AIAssistant;
use Illuminate\Support\Facades\Log;
use OpenAI\Laravel\Facades\OpenAI;

class AIDocumentationIntelligenceService
{
    private AIAssistant $aiAssistant;

    public function __construct(AIAssistant $aiAssistant)
    {
        $this->aiAssistant = $aiAssistant;
    }

    /**
     * Generate comprehensive clinical documentation from voice transcription
     */
    public function generateClinicalDocumentation(
        VoiceTranscription $transcription, 
        ?Appointment $appointment = null,
        ?int $patientId = null
    ): ClinicalDocumentationIntelligence {
        
        $patientId = $patientId ?? ($appointment ? $appointment->patient_id : $transcription->patient_id);
        
        if (!$patientId) {
            throw new \Exception('Patient ID is required for clinical documentation');
        }

        // Extract clinical information from transcription
        $clinicalData = $this->extractClinicalInformation($transcription->transcription_text);
        
        // Generate structured documentation (using extracted data)
        $documentation = $clinicalData; 
        
        // Validate completeness and compliance
        $validationResults = $this->validateDocumentation($documentation);
        
        // Generate coding suggestions
        $codingSuggestions = $this->generateCodingSuggestions($documentation);
        
        // Save to database
        $docRecord = ClinicalDocumentationIntelligence::create([
            'patient_id' => $patientId,
            'appointment_id' => $appointment ? $appointment->id : null,
            'ai_session_id' => uniqid('aidoc_'),
            'note_type' => $this->determineNoteType($appointment),
            'chief_complaint' => $documentation['chief_complaint'] ?? '',
            'history_of_present_illness' => $documentation['history_of_present_illness'] ?? '',
            'review_of_systems' => $documentation['review_of_systems'] ?? [],
            'physical_exam_findings' => $documentation['physical_exam_findings'] ?? '',
            'assessment' => $documentation['assessment'] ?? '',
            'plan' => $documentation['plan'] ?? '',
            'medications_review' => $documentation['medications_review'] ?? '',
            'overall_confidence' => $documentation['confidence_score'] ?? 0.0,
            'section_confidences' => $documentation['section_confidences'] ?? [],
            'completeness_score' => $validationResults['completeness_score'],
            'compliance_flags' => $validationResults['compliance_flags'],
            'missing_elements' => $validationResults['missing_elements'],
            'suggested_codes' => $codingSuggestions,
            'generated_from_transcription_id' => $transcription->id
        ]);

        // Log quality metrics
        $this->logQualityMetrics($docRecord, $validationResults);

        // Save suggested codes to the dedicated table as well
        $this->saveSuggestedCodes($docRecord, $codingSuggestions);

        return $docRecord;
    }

    /**
     * Extract clinical information from voice transcription
     */
    private function extractClinicalInformation(string $transcription): array
    {
        $prompt = "
        Analyze this clinical conversation and extract structured clinical information:
        
        TRANSCRIPTION: {$transcription}
        
        Extract the following in JSON format:
        {
          'chief_complaint': 'Main reason for visit',
          'history_of_present_illness': 'Detailed history of current condition',
          'review_of_systems': {
            'constitutional': ['fever', 'weight loss'],
            'cardiovascular': [],
            'respiratory': [],
            'gi': [],
            'gu': [],
            'musculoskeletal': [],
            'skin': [],
            'neurological': [],
            'psychiatric': [],
            'endocrine': [],
            'hematologic_lymphatic': [],
            'allergic_immunologic': []
          },
          'physical_exam_findings': 'Notable physical exam findings',
          'assessment': 'Clinical assessment/diagnosis',
          'plan': 'Treatment plan and follow-up',
          'medications_review': 'Medication reconciliation notes',
          'confidence_score': 0.0-1.0,
          'section_confidences': {
            'chief_complaint': 0.0-1.0,
            'history_of_present_illness': 0.0-1.0,
            'assessment': 0.0-1.0,
            'plan': 0.0-1.0
          }
        }
        
        Be precise and medical-accurate. Only extract information that is clearly stated.
        ";

        try {
            $response = OpenAI::chat()->create([
                'model' => 'gpt-4o',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are a clinical documentation specialist. Extract accurate medical information from clinical conversations. Respond ONLY with valid JSON.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'max_tokens' => 2000,
                'temperature' => 0.1
            ]);

            $content = $response->choices[0]->message->content;
            
            // Clean content if it has markdown blocks
            $cleanContent = trim($content);
            if (strpos($cleanContent, '```json') === 0) {
                $cleanContent = substr($cleanContent, 7);
            }
            if (strpos($cleanContent, '```') === 0) {
                $cleanContent = substr($cleanContent, 3);
            }
            if (str_ends_with($cleanContent, '```')) {
                $cleanContent = substr($cleanContent, 0, -3);
            }
            $cleanContent = trim($cleanContent);

            $parsed = json_decode($cleanContent, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Invalid JSON response from AI: ' . json_last_error_msg());
            }

            return $parsed;

        } catch (\Exception $e) {
            Log::error('AI Documentation Extraction Failed', [
                'error' => $e->getMessage(),
                'transcription' => substr($transcription, 0, 100) . '...'
            ]);

            // Return fallback structure
            return [
                'chief_complaint' => 'Extracted from conversation',
                'history_of_present_illness' => 'See voice transcription for details',
                'review_of_systems' => [],
                'physical_exam_findings' => 'See voice transcription for details',
                'assessment' => 'See voice transcription for details',
                'plan' => 'See voice transcription for details',
                'medications_review' => 'See voice transcription for details',
                'confidence_score' => 0.5,
                'section_confidences' => [
                    'chief_complaint' => 0.5,
                    'history_of_present_illness' => 0.5,
                    'assessment' => 0.5,
                    'plan' => 0.5
                ]
            ];
        }
    }

    /**
     * Validate documentation completeness and compliance
     */
    private function validateDocumentation(array $documentation): array
    {
        $missingElements = [];
        $complianceFlags = [];
        
        // Check required elements based on note type
        if (empty($documentation['chief_complaint'])) {
            $missingElements[] = 'chief_complaint';
        }
        
        if (empty($documentation['history_of_present_illness'])) {
            $missingElements[] = 'history_of_present_illness';
        }
        
        if (empty($documentation['assessment'])) {
            $missingElements[] = 'assessment';
        }
        
        if (empty($documentation['plan'])) {
            $missingElements[] = 'plan';
        }

        // Check for compliance issues
        if (isset($documentation['assessment']) && 
            stripos($documentation['assessment'], 'rule out') !== false) {
            $complianceFlags[] = 'Assessment contains uncertain language - requires clarification';
        }

        // Calculate completeness score
        $requiredFields = ['chief_complaint', 'history_of_present_illness', 'assessment', 'plan'];
        $presentFields = array_filter($requiredFields, function($field) use ($documentation) {
            return !empty($documentation[$field]);
        });
        
        $completenessScore = count($presentFields) / count($requiredFields);

        return [
            'completeness_score' => round($completenessScore, 2),
            'compliance_flags' => $complianceFlags,
            'missing_elements' => $missingElements
        ];
    }

    /**
     * Generate ICD-10 and CPT code suggestions
     */
    private function generateCodingSuggestions(array $documentation): array
    {
        $prompt = "
        Based on this clinical documentation, suggest appropriate ICD-10 and CPT codes:
        
        CHIEF COMPLAINT: {$documentation['chief_complaint']}
        ASSESSMENT: {$documentation['assessment']}
        PLAN: {$documentation['plan']}
        
        Respond with JSON:
        {
          'icd10_codes': [
            {
              'code': 'A00.0',
              'description': 'Cholera due to Vibrio cholerae 01, biovar cholerae',
              'confidence': 0.0-1.0,
              'justification': 'Why this code fits'
            }
          ],
          'cpt_codes': [
            {
              'code': '99213',
              'description': 'Office or other outpatient visit for the evaluation and management of an established patient',
              'confidence': 0.0-1.0,
              'justification': 'Why this code fits'
            }
          ]
        }
        ";

        try {
            $response = OpenAI::chat()->create([
                'model' => 'gpt-4o',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are a medical coding specialist. Suggest accurate ICD-10 and CPT codes based on clinical documentation. Respond ONLY with valid JSON.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'max_tokens' => 1000,
                'temperature' => 0.1
            ]);

            $content = $response->choices[0]->message->content;
            
            // Clean content
            $cleanContent = trim($content);
            if (strpos($cleanContent, '```json') === 0) {
                $cleanContent = substr($cleanContent, 7);
            }
            if (strpos($cleanContent, '```') === 0) {
                $cleanContent = substr($cleanContent, 3);
            }
            if (str_ends_with($cleanContent, '```')) {
                $cleanContent = substr($cleanContent, 0, -3);
            }
            $cleanContent = trim($cleanContent);

            $parsed = json_decode($cleanContent, true);

            if (json_last_error() === JSON_ERROR_NONE) {
                return $parsed;
            }

        } catch (\Exception $e) {
            Log::error('Coding Suggestions Generation Failed', ['error' => $e->getMessage()]);
        }

        return ['icd10_codes' => [], 'cpt_codes' => []];
    }

    /**
     * Determine appropriate note type based on appointment
     */
    private function determineNoteType(?Appointment $appointment): string
    {
        if (!$appointment) {
            return 'progress';
        }

        $appointmentType = strtolower($appointment->appointment_type ?? '');
        
        if (strpos($appointmentType, 'follow') !== false) {
            return 'followup';
        } elseif (strpos($appointmentType, 'consult') !== false) {
            return 'consultation';
        } elseif (strpos($appointmentType, 'discharge') !== false) {
            return 'discharge';
        } else {
            return 'progress';
        }
    }

    /**
     * Log quality metrics for the documentation
     */
    private function logQualityMetrics(ClinicalDocumentationIntelligence $doc, array $validationResults): void
    {
        // Log completeness metric
        \App\Models\DocumentationQualityMetric::create([
            'clinical_doc_id' => $doc->id,
            'metric_type' => 'completeness',
            'score' => $validationResults['completeness_score'],
            'details' => [
                'missing_elements' => $validationResults['missing_elements'],
                'total_required_fields' => 4,
                'present_fields' => 4 - count($validationResults['missing_elements'])
            ]
        ]);

        // Log compliance metric if there are flags
        if (!empty($validationResults['compliance_flags'])) {
            \App\Models\DocumentationQualityMetric::create([
                'clinical_doc_id' => $doc->id,
                'metric_type' => 'compliance',
                'score' => 0.5, // Needs attention
                'details' => [
                    'flags' => $validationResults['compliance_flags'],
                    'requires_review' => true
                ]
            ]);
        }
    }

    /**
     * Save suggested codes to the dedicated table
     */
    private function saveSuggestedCodes(ClinicalDocumentationIntelligence $doc, array $codingSuggestions): void
    {
        if (isset($codingSuggestions['icd10_codes'])) {
            foreach ($codingSuggestions['icd10_codes'] as $code) {
                \App\Models\SuggestedCode::create([
                    'clinical_doc_id' => $doc->id,
                    'code_type' => 'ICD-10',
                    'code_value' => $code['code'],
                    'code_description' => $code['description'],
                    'confidence_score' => $code['confidence'] ?? 0.0
                ]);
            }
        }

        if (isset($codingSuggestions['cpt_codes'])) {
            foreach ($codingSuggestions['cpt_codes'] as $code) {
                \App\Models\SuggestedCode::create([
                    'clinical_doc_id' => $doc->id,
                    'code_type' => 'CPT',
                    'code_value' => $code['code'],
                    'code_description' => $code['description'],
                    'confidence_score' => $code['confidence'] ?? 0.0
                ]);
            }
        }
    }
}
