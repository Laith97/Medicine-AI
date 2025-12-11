<?php

namespace App\Services;

use App\Models\DocumentTemplate;
use App\Models\Patient;
use App\Models\User;
use App\Models\Appointment;
use App\Models\Diagnosis;
use App\Models\Prescription;
use App\Services\AuditLoggingService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

class TemplateAutofillService
{
    protected AIWritingAssistantService $aiAssistant;
    protected ComplianceMonitoringService $complianceService;

    public function __construct(
        AIWritingAssistantService $aiAssistant,
        ComplianceMonitoringService $complianceService
    ) {
        $this->aiAssistant = $aiAssistant;
        $this->complianceService = $complianceService;
    }

    /**
     * Auto-fill template with patient data
     */
    public function autofillTemplate(
        DocumentTemplate $template,
        Patient $patient,
        User $user,
        array $additionalContext = []
    ): array {
        try {
            // Gather comprehensive patient data
            $patientData = $this->gatherPatientData($patient, $additionalContext);

            // Extract template placeholders
            $placeholders = $template->placeholders ?? $template->extractPlaceholders();

            // Auto-fill placeholders with patient data
            $filledData = $this->fillPlaceholders($placeholders, $patientData, $template);

            // Validate filled data
            $validationResult = $this->validateFilledData($filledData, $template, $patient);

            // Generate any missing content with AI if needed
            $aiGeneratedContent = $this->generateMissingContent($filledData, $template, $patient, $user);

            // Merge AI-generated content
            $finalData = array_merge($filledData, $aiGeneratedContent);

            // Log autofill activity
            AuditLoggingService::logComplianceAudit('template_autofill', $template->id, [
                'patient_id' => $patient->id,
                'user_id' => $user->id,
                'template_type' => $template->template_type,
                'placeholders_filled' => count($filledData),
                'ai_content_generated' => count($aiGeneratedContent),
                'validation_passed' => $validationResult['is_valid'],
            ]);

            return [
                'filled_data' => $finalData,
                'validation_result' => $validationResult,
                'autofill_metadata' => [
                    'autofilled_at' => now(),
                    'autofilled_by' => $user->id,
                    'patient_id' => $patient->id,
                    'template_version' => $template->updated_at,
                    'data_sources' => array_keys($patientData),
                ],
            ];

        } catch (\Exception $e) {
            Log::error('Template autofill failed', [
                'template_id' => $template->id,
                'patient_id' => $patient->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            throw new \RuntimeException('Failed to autofill template: ' . $e->getMessage());
        }
    }

    /**
     * Gather comprehensive patient data for autofill
     */
    protected function gatherPatientData(Patient $patient, array $additionalContext = []): array
    {
        $data = [
            'patient' => $this->extractPatientInfo($patient),
            'demographics' => $this->extractDemographics($patient),
            'medical_history' => $this->extractMedicalHistory($patient),
            'current_conditions' => $this->extractCurrentConditions($patient),
            'medications' => $this->extractMedications($patient),
            'allergies' => $this->extractAllergies($patient),
            'insurance' => $this->extractInsuranceInfo($patient),
            'emergency_contacts' => $this->extractEmergencyContacts($patient),
        ];

        // Add appointment context if available
        if (isset($additionalContext['appointment_id'])) {
            $appointment = Appointment::find($additionalContext['appointment_id']);
            if ($appointment) {
                $data['appointment'] = $this->extractAppointmentInfo($appointment);
            }
        }

        // Add diagnosis context if available
        if (isset($additionalContext['diagnosis_id'])) {
            $diagnosis = Diagnosis::find($additionalContext['diagnosis_id']);
            if ($diagnosis) {
                $data['diagnosis'] = $this->extractDiagnosisInfo($diagnosis);
            }
        }

        // Add prescription context if available
        if (isset($additionalContext['prescription_id'])) {
            $prescription = Prescription::find($additionalContext['prescription_id']);
            if ($prescription) {
                $data['prescription'] = $this->extractPrescriptionInfo($prescription);
            }
        }

        // Merge additional context
        $data['context'] = $additionalContext;

        return $data;
    }

    /**
     * Extract basic patient information
     */
    protected function extractPatientInfo(Patient $patient): array
    {
        return [
            'first_name' => $patient->first_name,
            'last_name' => $patient->last_name,
            'full_name' => $patient->first_name . ' ' . $patient->last_name,
            'date_of_birth' => $patient->date_of_birth?->format('Y-m-d'),
            'age' => $patient->date_of_birth?->age,
            'gender' => $patient->gender,
            'medical_record_number' => $patient->medical_record_number,
            'patient_id' => $patient->id,
        ];
    }

    /**
     * Extract patient demographics
     */
    protected function extractDemographics(Patient $patient): array
    {
        return [
            'address' => $patient->address ?? '',
            'city' => $patient->city ?? '',
            'state' => $patient->state ?? '',
            'zip_code' => $patient->zip_code ?? '',
            'phone' => $patient->phone ?? '',
            'email' => $patient->email ?? '',
            'ethnicity' => $patient->ethnicity ?? '',
            'language' => $patient->preferred_language ?? 'English',
            'marital_status' => $patient->marital_status ?? '',
        ];
    }

    /**
     * Extract medical history
     */
    protected function extractMedicalHistory(Patient $patient): array
    {
        // This would typically come from a medical history relationship
        // For now, return basic structure
        return [
            'past_conditions' => $patient->medical_history ?? [],
            'surgeries' => $patient->surgeries ?? [],
            'family_history' => $patient->family_history ?? [],
            'last_physical_exam' => $patient->last_physical_exam?->format('Y-m-d'),
        ];
    }

    /**
     * Extract current medical conditions
     */
    protected function extractCurrentConditions(Patient $patient): array
    {
        // Get active diagnoses
        $activeDiagnoses = $patient->diagnoses()
            ->where('status', 'active')
            ->orderBy('created_at', 'desc')
            ->get();

        return [
            'active_diagnoses' => $activeDiagnoses->map(function ($diagnosis) {
                return [
                    'condition' => $diagnosis->condition_name,
                    'icd_code' => $diagnosis->icd_code,
                    'severity' => $diagnosis->severity,
                    'diagnosed_date' => $diagnosis->diagnosed_at?->format('Y-m-d'),
                ];
            })->toArray(),
            'chronic_conditions' => $activeDiagnoses->where('is_chronic', true)->pluck('condition_name')->toArray(),
        ];
    }

    /**
     * Extract current medications
     */
    protected function extractMedications(Patient $patient): array
    {
        $activePrescriptions = $patient->prescriptions()
            ->where('status', 'active')
            ->orderBy('created_at', 'desc')
            ->get();

        return [
            'current_medications' => $activePrescriptions->map(function ($prescription) {
                return [
                    'medication_name' => $prescription->medication_name,
                    'dosage' => $prescription->dosage,
                    'frequency' => $prescription->frequency,
                    'start_date' => $prescription->start_date?->format('Y-m-d'),
                    'prescribing_doctor' => $prescription->doctor?->name,
                ];
            })->toArray(),
        ];
    }

    /**
     * Extract allergies
     */
    protected function extractAllergies(Patient $patient): array
    {
        // This would come from an allergies relationship
        return [
            'drug_allergies' => $patient->drug_allergies ?? [],
            'food_allergies' => $patient->food_allergies ?? [],
            'environmental_allergies' => $patient->environmental_allergies ?? [],
        ];
    }

    /**
     * Extract insurance information
     */
    protected function extractInsuranceInfo(Patient $patient): array
    {
        $insurance = $patient->patientInsurance ?? $patient->insurance;
        if (!$insurance) {
            return ['has_insurance' => false];
        }

        return [
            'has_insurance' => true,
            'provider_name' => $insurance->provider_name ?? '',
            'policy_number' => $insurance->policy_number ?? '',
            'group_number' => $insurance->group_number ?? '',
            'subscriber_name' => $insurance->subscriber_name ?? '',
            'relationship_to_patient' => $insurance->relationship_to_patient ?? '',
        ];
    }

    /**
     * Extract emergency contacts
     */
    protected function extractEmergencyContacts(Patient $patient): array
    {
        // This would come from emergency contacts relationship
        return [
            'primary_contact' => $patient->emergency_contact ?? [],
            'secondary_contact' => $patient->secondary_emergency_contact ?? [],
        ];
    }

    /**
     * Extract appointment information
     */
    protected function extractAppointmentInfo(Appointment $appointment): array
    {
        return [
            'appointment_date' => $appointment->appointment_date?->format('Y-m-d'),
            'appointment_time' => $appointment->appointment_time?->format('H:i'),
            'doctor_name' => $appointment->doctor?->name,
            'appointment_type' => $appointment->appointment_type,
            'chief_complaint' => $appointment->chief_complaint,
            'reason_for_visit' => $appointment->reason_for_visit,
        ];
    }

    /**
     * Extract diagnosis information
     */
    protected function extractDiagnosisInfo(Diagnosis $diagnosis): array
    {
        return [
            'condition_name' => $diagnosis->condition_name,
            'icd_code' => $diagnosis->icd_code,
            'severity' => $diagnosis->severity,
            'diagnosed_at' => $diagnosis->diagnosed_at?->format('Y-m-d'),
            'diagnosed_by' => $diagnosis->doctor?->name,
            'notes' => $diagnosis->notes,
        ];
    }

    /**
     * Extract prescription information
     */
    protected function extractPrescriptionInfo(Prescription $prescription): array
    {
        return [
            'medication_name' => $prescription->medication_name,
            'dosage' => $prescription->dosage,
            'frequency' => $prescription->frequency,
            'duration' => $prescription->duration,
            'instructions' => $prescription->instructions,
            'prescribing_doctor' => $prescription->doctor?->name,
            'prescribed_at' => $prescription->created_at?->format('Y-m-d'),
        ];
    }

    /**
     * Fill placeholders with gathered patient data
     */
    protected function fillPlaceholders(array $placeholders, array $patientData, DocumentTemplate $template): array
    {
        $filledData = [];

        foreach ($placeholders as $key => $config) {
            $value = $this->findValueForPlaceholder($key, $config, $patientData);

            if ($value !== null) {
                $filledData[$key] = $this->formatValueForType($value, $config['type']);
            }
        }

        return $filledData;
    }

    /**
     * Find appropriate value for a placeholder from patient data
     */
    protected function findValueForPlaceholder(string $key, array $config, array $patientData): mixed
    {
        // Direct mapping for common placeholders
        $mappings = [
            'patient_name' => 'patient.full_name',
            'patient_first_name' => 'patient.first_name',
            'patient_last_name' => 'patient.last_name',
            'patient_dob' => 'patient.date_of_birth',
            'patient_age' => 'patient.age',
            'patient_gender' => 'patient.gender',
            'patient_mrn' => 'patient.medical_record_number',
            'patient_address' => 'demographics.address',
            'patient_city' => 'demographics.city',
            'patient_state' => 'demographics.state',
            'patient_zip' => 'demographics.zip_code',
            'patient_phone' => 'demographics.phone',
            'patient_email' => 'demographics.email',
            'appointment_date' => 'appointment.appointment_date',
            'appointment_time' => 'appointment.appointment_time',
            'doctor_name' => 'appointment.doctor_name',
            'chief_complaint' => 'appointment.chief_complaint',
            'current_date' => 'context.current_date',
            'current_time' => 'context.current_time',
        ];

        if (isset($mappings[$key])) {
            return $this->getNestedValue($patientData, $mappings[$key]);
        }

        // Handle dynamic placeholders
        if (str_starts_with($key, 'medication_')) {
            return $this->getMedicationValue($key, $patientData);
        }

        if (str_starts_with($key, 'diagnosis_')) {
            return $this->getDiagnosisValue($key, $patientData);
        }

        if (str_starts_with($key, 'allergy_')) {
            return $this->getAllergyValue($key, $patientData);
        }

        // Use default value if available
        if (isset($config['default'])) {
            return $config['default'];
        }

        return null;
    }

    /**
     * Get nested value from array using dot notation
     */
    protected function getNestedValue(array $array, string $path): mixed
    {
        $keys = explode('.', $path);

        foreach ($keys as $key) {
            if (!isset($array[$key])) {
                return null;
            }
            $array = $array[$key];
        }

        return $array;
    }

    /**
     * Get medication-related placeholder values
     */
    protected function getMedicationValue(string $key, array $patientData): mixed
    {
        $medications = $patientData['medications']['current_medications'] ?? [];

        if ($key === 'medication_list') {
            return collect($medications)->pluck('medication_name')->join(', ');
        }

        if ($key === 'medication_count') {
            return count($medications);
        }

        if (preg_match('/medication_(\d+)_name/', $key, $matches)) {
            $index = (int) $matches[1] - 1;
            return $medications[$index]['medication_name'] ?? null;
        }

        return null;
    }

    /**
     * Get diagnosis-related placeholder values
     */
    protected function getDiagnosisValue(string $key, array $patientData): mixed
    {
        $diagnoses = $patientData['current_conditions']['active_diagnoses'] ?? [];

        if ($key === 'diagnosis_list') {
            return collect($diagnoses)->pluck('condition')->join(', ');
        }

        if ($key === 'primary_diagnosis') {
            return $diagnoses[0]['condition'] ?? null;
        }

        if ($key === 'diagnosis_count') {
            return count($diagnoses);
        }

        return null;
    }

    /**
     * Get allergy-related placeholder values
     */
    protected function getAllergyValue(string $key, array $patientData): mixed
    {
        $allergies = $patientData['allergies'] ?? [];

        if ($key === 'drug_allergies') {
            return implode(', ', $allergies['drug_allergies'] ?? []);
        }

        if ($key === 'food_allergies') {
            return implode(', ', $allergies['food_allergies'] ?? []);
        }

        if ($key === 'all_allergies') {
            $all = array_merge(
                $allergies['drug_allergies'] ?? [],
                $allergies['food_allergies'] ?? [],
                $allergies['environmental_allergies'] ?? []
            );
            return implode(', ', $all);
        }

        return null;
    }

    /**
     * Format value according to placeholder type
     */
    protected function formatValueForType(mixed $value, string $type): mixed
    {
        if ($value === null) {
            return null;
        }

        return match ($type) {
            'date' => $value instanceof \DateTime ? $value->format('Y-m-d') : $value,
            'datetime' => $value instanceof \DateTime ? $value->format('Y-m-d H:i:s') : $value,
            'number' => is_numeric($value) ? (float) $value : $value,
            'boolean' => (bool) $value,
            'array' => is_array($value) ? $value : [$value],
            default => (string) $value,
        };
    }

    /**
     * Validate filled data against template requirements
     */
    protected function validateFilledData(array $filledData, DocumentTemplate $template, Patient $patient): array
    {
        $placeholders = $template->placeholders ?? [];
        $violations = [];

        foreach ($placeholders as $key => $config) {
            if ($config['required'] && !isset($filledData[$key])) {
                $violations[] = "Required placeholder '{$key}' could not be auto-filled";
            }
        }

        // Check for HIPAA compliance
        $hipaaViolations = $this->checkHipaaCompliance($filledData, $patient);
        $violations = array_merge($violations, $hipaaViolations);

        return [
            'is_valid' => empty($violations),
            'violations' => $violations,
            'filled_placeholders' => count($filledData),
            'total_placeholders' => count($placeholders),
            'fill_percentage' => count($placeholders) > 0 ? (count($filledData) / count($placeholders)) * 100 : 0,
        ];
    }

    /**
     * Check HIPAA compliance of filled data
     */
    protected function checkHipaaCompliance(array $filledData, Patient $patient): array
    {
        $violations = [];

        // Check for sensitive data exposure
        $sensitiveFields = ['ssn', 'medical_record_number', 'full_ssn'];

        foreach ($sensitiveFields as $field) {
            if (isset($filledData[$field])) {
                $violations[] = "Sensitive field '{$field}' should not be auto-filled for privacy reasons";
            }
        }

        return $violations;
    }

    /**
     * Generate missing content with AI assistance
     */
    protected function generateMissingContent(array $filledData, DocumentTemplate $template, Patient $patient, User $user): array
    {
        $placeholders = $template->placeholders ?? [];
        $missingContent = [];

        foreach ($placeholders as $key => $config) {
            if (!isset($filledData[$key]) && !$config['required']) {
                // Try to generate content for optional placeholders that make sense
                if ($this->shouldGenerateWithAI($key, $config)) {
                    try {
                        $generatedValue = $this->generatePlaceholderContent($key, $config, $patient, $template, $user);
                        if ($generatedValue) {
                            $missingContent[$key] = $generatedValue;
                        }
                    } catch (\Exception $e) {
                        Log::warning("Failed to generate content for placeholder {$key}", [
                            'error' => $e->getMessage(),
                            'template_id' => $template->id,
                            'patient_id' => $patient->id,
                        ]);
                    }
                }
            }
        }

        return $missingContent;
    }

    /**
     * Determine if a placeholder should be generated with AI
     */
    protected function shouldGenerateWithAI(string $key, array $config): bool
    {
        // Generate AI content for certain types of placeholders
        $aiGeneratableTypes = ['text', 'textarea', 'long_text'];
        $aiGeneratableKeys = [
            'assessment_notes',
            'treatment_plan',
            'follow_up_instructions',
            'clinical_notes',
            'progress_notes',
        ];

        return in_array($config['type'], $aiGeneratableTypes) ||
               in_array($key, $aiGeneratableKeys);
    }

    /**
     * Generate content for a specific placeholder using AI
     */
    protected function generatePlaceholderContent(
        string $key,
        array $config,
        Patient $patient,
        DocumentTemplate $template,
        User $user
    ): ?string {
        $contextData = [
            'placeholder_key' => $key,
            'placeholder_type' => $config['type'],
            'template_type' => $template->template_type,
            'patient_name' => $patient->first_name . ' ' . $patient->last_name,
            'patient_age' => $patient->date_of_birth?->age,
            'patient_gender' => $patient->gender,
        ];

        // Use AI assistant to generate appropriate content
        $result = $this->aiAssistant->generateDocumentContent($template, $contextData, $user, [
            'generation_type' => 'placeholder_fill',
            'placeholder_key' => $key,
        ]);

        return $result['content'] ?? null;
    }

    /**
     * Get autofill suggestions for a template
     */
    public function getAutofillSuggestions(DocumentTemplate $template, Patient $patient): array
    {
        $patientData = $this->gatherPatientData($patient);
        $placeholders = $template->placeholders ?? [];

        $suggestions = [];

        foreach ($placeholders as $key => $config) {
            $suggestedValue = $this->findValueForPlaceholder($key, $config, $patientData);

            if ($suggestedValue !== null) {
                $suggestions[$key] = [
                    'suggested_value' => $this->formatValueForType($suggestedValue, $config['type']),
                    'confidence' => $this->calculateConfidence($key, $suggestedValue, $patientData),
                    'source' => $this->identifyDataSource($key, $patientData),
                ];
            }
        }

        return [
            'template_id' => $template->id,
            'patient_id' => $patient->id,
            'suggestions' => $suggestions,
            'total_suggestions' => count($suggestions),
            'coverage_percentage' => count($placeholders) > 0 ? (count($suggestions) / count($placeholders)) * 100 : 0,
        ];
    }

    /**
     * Calculate confidence score for a suggestion
     */
    protected function calculateConfidence(string $key, mixed $value, array $patientData): float
    {
        // Simple confidence calculation based on data freshness and completeness
        $baseConfidence = 0.8; // Base confidence for any autofilled data

        // Reduce confidence for older data
        if (str_contains($key, 'date') || str_contains($key, 'time')) {
            $baseConfidence *= 0.9;
        }

        // Increase confidence for directly mapped fields
        if (isset($patientData['patient'][$this->extractFieldName($key)])) {
            $baseConfidence = min(1.0, $baseConfidence * 1.2);
        }

        return round($baseConfidence, 2);
    }

    /**
     * Identify the source of autofilled data
     */
    protected function identifyDataSource(string $key, array $patientData): string
    {
        $mappings = [
            'patient_name' => 'Patient Record',
            'patient_dob' => 'Patient Record',
            'appointment_date' => 'Appointment Record',
            'doctor_name' => 'Appointment Record',
            'medication_list' => 'Prescription Records',
            'diagnosis_list' => 'Diagnosis Records',
        ];

        return $mappings[$key] ?? 'Patient Data';
    }

    /**
     * Extract field name from placeholder key
     */
    protected function extractFieldName(string $key): string
    {
        // Convert placeholder key to likely field name
        return str_replace(['patient_', 'appointment_', 'doctor_'], '', $key);
    }
}
