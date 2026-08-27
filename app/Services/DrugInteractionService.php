<?php

namespace App\Services;

use App\Models\Prescription;
use App\Models\PatientData;
use App\Models\User;
use App\Models\Diagnosis;
use Illuminate\Support\Facades\Log;

class DrugInteractionService
{
    /**
     * Validate a prescription for drug interactions, allergies, and contraindications
     *
     * @param Prescription $prescription
     * @return array Validation results with warnings and errors
     */
    public function validatePrescription(Prescription $prescription): array
    {
        $warnings = [];
        $errors = [];
        $patient = $prescription->patient;

        // Eager load all necessary relationships to prevent N+1 queries
        if (!$prescription->relationLoaded('patient.patientData')) {
            $prescription->load('patient.patientData');
        }

        if (!$prescription->relationLoaded('patient')) {
            $prescription->load('patient');
        }

        // Get patient's active prescriptions (excluding current if updating)
        $activePrescriptions = Prescription::getActiveForPatient($patient->id)
            ->filter(function ($p) use ($prescription) {
                return $p->id !== $prescription->id;
            });

        // Get patient data for allergies and medical history
        $patientData = $patient->patientData()->latest()->first();

        // Pre-load drug interactions and contraindications to prevent multiple queries
        $this->preloadInteractionData($activePrescriptions, $patient);

        // 1. Check drug-drug interactions
        $drugInteractions = $this->checkDrugDrugInteractions($prescription, $activePrescriptions);
        $warnings = array_merge($warnings, $drugInteractions['warnings']);
        $errors = array_merge($errors, $drugInteractions['errors']);

        // 2. Check drug-allergy interactions
        $allergyInteractions = $this->checkDrugAllergyInteractions($prescription, $patientData);
        $warnings = array_merge($warnings, $allergyInteractions['warnings']);
        $errors = array_merge($errors, $allergyInteractions['errors']);

        // 3. Check contraindications based on patient conditions
        $contraindications = $this->checkContraindications($prescription, $patient);
        $warnings = array_merge($warnings, $contraindications['warnings']);
        $errors = array_merge($errors, $contraindications['errors']);

        return [
            'is_safe' => empty($errors),
            'warnings' => $warnings,
            'errors' => $errors,
            'severity' => $this->calculateSeverity($warnings, $errors)
        ];
    }

    /**
     * Preload interaction data to prevent multiple database queries
     */
    private function preloadInteractionData($activePrescriptions, User $patient): void
    {
        // Preload drug interactions for all drug combinations
        $drugNames = collect($activePrescriptions)->pluck('medication_name')
            ->map(fn($name) => strtolower(trim($name)))
            ->unique()
            ->toArray();

        if (!empty($drugNames)) {
            // Preload interactions for faster lookups
            $interactions = \App\Models\DrugInteraction::whereIn('drug1_name', $drugNames)
                ->orWhereIn('drug2_name', $drugNames)
                ->get()
                ->groupBy(function($interaction) {
                    return $interaction->drug1_name . '|' . $interaction->drug2_name;
                });
        }

        // Preload contraindications
        $contraindications = \App\Models\DrugContraindication::all();
    }

    /**
     * Check for drug-drug interactions
     */
    private function checkDrugDrugInteractions(Prescription $prescription, $activePrescriptions): array
    {
        $warnings = [];
        $errors = [];

        $newDrug = strtolower(trim($prescription->medication_name));

        foreach ($activePrescriptions as $activePrescription) {
            $activeDrug = strtolower(trim($activePrescription->medication_name));

            $interaction = $this->getDrugInteraction($newDrug, $activeDrug);

            if ($interaction) {
                $message = "Potential interaction between '{$prescription->medication_name}' and '{$activePrescription->medication_name}': {$interaction['description']}";

                if ($interaction['severity'] === 'severe') {
                    $errors[] = $message;
                } else {
                    $warnings[] = $message;
                }
            }
        }

        return ['warnings' => $warnings, 'errors' => $errors];
    }

    /**
     * Check for drug-allergy interactions
     */
    private function checkDrugAllergyInteractions(Prescription $prescription, ?PatientData $patientData): array
    {
        $warnings = [];
        $errors = [];

        if (!$patientData || empty($patientData->allergies)) {
            return ['warnings' => $warnings, 'errors' => $errors];
        }

        $medication = strtolower(trim($prescription->medication_name));

        foreach ($patientData->allergies as $allergy) {
            $allergyName = strtolower(trim($allergy));

            // Check for direct matches or common cross-reactivity
            if ($this->isAllergicToDrug($medication, $allergyName)) {
                $errors[] = "Patient is allergic to '{$allergy}'. Prescribing '{$prescription->medication_name}' may cause severe allergic reaction.";
            }
        }

        return ['warnings' => $warnings, 'errors' => $errors];
    }

    /**
     * Check for contraindications based on patient conditions
     */
    private function checkContraindications(Prescription $prescription, User $patient): array
    {
        $warnings = [];
        $errors = [];

        $medication = strtolower(trim($prescription->medication_name));

        // Get patient's recent diagnoses
        $recentDiagnoses = Diagnosis::where('patient_id', $patient->id)
            ->where('created_at', '>=', now()->subMonths(6))
            ->get();

        foreach ($recentDiagnoses as $diagnosis) {
            $condition = strtolower(trim($diagnosis->diagnosis ?? ''));

            $contraindication = $this->getContraindication($medication, $condition);

            if ($contraindication) {
                $message = "Potential contraindication: '{$prescription->medication_name}' may not be suitable for patient with '{$diagnosis->diagnosis}' - {$contraindication['reason']}";

                if ($contraindication['severity'] === 'severe') {
                    $errors[] = $message;
                } else {
                    $warnings[] = $message;
                }
            }
        }

        return ['warnings' => $warnings, 'errors' => $errors];
    }

    /**
     * Get drug interaction information from database
     */
    private function getDrugInteraction(string $drug1, string $drug2): ?array
    {
        $interaction = \App\Models\DrugInteraction::findInteraction(strtolower($drug1), strtolower($drug2));

        if ($interaction) {
            return [
                'description' => $interaction->description,
                'severity' => $interaction->severity,
                'clinical_consequence' => $interaction->clinical_consequence,
                'recommendation' => $interaction->recommendation,
            ];
        }

        // Fallback to some common hardcoded interactions if database is empty
        return $this->getFallbackInteraction($drug1, $drug2);
    }

    /**
     * Fallback hardcoded interactions for common cases
     */
    private function getFallbackInteraction(string $drug1, string $drug2): ?array
    {
        $interactions = [
            // Warfarin interactions
            ['warfarin', 'aspirin', 'increased bleeding risk', 'moderate'],
            ['warfarin', 'ibuprofen', 'increased bleeding risk', 'moderate'],
            ['warfarin', 'amiodarone', 'increased warfarin effect', 'severe'],

            // Statin interactions
            ['simvastatin', 'grapefruit', 'increased statin levels', 'moderate'],
            ['atorvastatin', 'grapefruit', 'increased statin levels', 'moderate'],

            // ACE inhibitor interactions
            ['lisinopril', 'potassium', 'hyperkalemia risk', 'moderate'],
            ['enalapril', 'potassium', 'hyperkalemia risk', 'moderate'],

            // Beta blocker interactions
            ['metoprolol', 'verapamil', 'bradycardia risk', 'severe'],
            ['propranolol', 'verapamil', 'bradycardia risk', 'severe'],

            // Common antibiotic interactions
            ['ciprofloxacin', 'tizanidine', 'severe hypotension', 'severe'],
            ['clarithromycin', 'simvastatin', 'rhabdomyolysis risk', 'severe'],
        ];

        foreach ($interactions as $interaction) {
            if (($drug1 === $interaction[0] && $drug2 === $interaction[1]) ||
                ($drug1 === $interaction[1] && $drug2 === $interaction[0])) {
                return [
                    'description' => $interaction[2],
                    'severity' => $interaction[3]
                ];
            }
        }

        return null;
    }

    /**
     * Check if patient is allergic to a drug
     */
    private function isAllergicToDrug(string $medication, string $allergy): bool
    {
        // Direct match
        if (str_contains($medication, $allergy) || str_contains($allergy, $medication)) {
            return true;
        }

        // Common cross-reactivities (simplified)
        $crossReactivities = [
            'penicillin' => ['amoxicillin', 'ampicillin', 'cephalexin'],
            'sulfa' => ['sulfamethoxazole', 'sulfasalazine', 'sulfadiazine'],
            'aspirin' => ['ibuprofen', 'naproxen', 'diclofenac'],
            'codeine' => ['morphine', 'hydrocodone', 'oxycodone'],
        ];

        foreach ($crossReactivities as $allergen => $reactants) {
            if (str_contains($allergy, $allergen)) {
                foreach ($reactants as $reactant) {
                    if (str_contains($medication, $reactant)) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Get contraindication information from database
     */
    private function getContraindication(string $medication, string $condition): ?array
    {
        // Try exact match first
        $contraindication = \App\Models\DrugContraindication::findContraindication(
            strtolower($medication),
            strtolower($condition)
        );

        if ($contraindication) {
            return [
                'reason' => $contraindication->reason,
                'severity' => $contraindication->severity,
                'alternative_options' => $contraindication->alternative_options,
                'monitoring_required' => $contraindication->monitoring_required,
            ];
        }

        // Try partial matches
        $contraindications = \App\Models\DrugContraindication::where('drug_name', 'LIKE', '%' . strtolower($medication) . '%')
            ->orWhere('condition', 'LIKE', '%' . strtolower($condition) . '%')
            ->get();

        foreach ($contraindications as $contra) {
            if (str_contains(strtolower($condition), strtolower($contra->condition)) &&
                (str_contains(strtolower($medication), strtolower($contra->drug_name)) ||
                 $contra->drug_name === 'nsaids' && $this->isNSAID($medication))) {
                return [
                    'reason' => $contra->reason,
                    'severity' => $contra->severity,
                    'alternative_options' => $contra->alternative_options,
                    'monitoring_required' => $contra->monitoring_required,
                ];
            }
        }

        // Fallback to hardcoded contraindications
        return $this->getFallbackContraindication($medication, $condition);
    }

    /**
     * Fallback hardcoded contraindications
     */
    private function getFallbackContraindication(string $medication, string $condition): ?array
    {
        $contraindications = [
            // Pregnancy contraindications
            ['pregnancy', 'isotretinoin', 'teratogenic effects', 'severe'],
            ['pregnancy', 'methotrexate', 'teratogenic effects', 'severe'],
            ['pregnancy', 'warfarin', 'fetal abnormalities', 'severe'],

            // Kidney disease contraindications
            ['chronic kidney disease', 'nsaids', 'worsen kidney function', 'moderate'],
            ['renal failure', 'metformin', 'lactic acidosis risk', 'severe'],

            // Liver disease contraindications
            ['liver disease', 'acetaminophen', 'hepatotoxicity risk', 'moderate'],
            ['cirrhosis', 'nsaids', 'gastrointestinal bleeding', 'moderate'],

            // Heart conditions
            ['heart failure', 'pioglitazone', 'fluid retention', 'moderate'],
            ['arrhythmia', 'flecainide', 'proarrhythmic effects', 'severe'],

            // Diabetes
            ['diabetes', 'corticosteroids', 'worsen glycemic control', 'moderate'],
        ];

        foreach ($contraindications as $contraindication) {
            $conditionKey = $contraindication[0];
            $drugKey = $contraindication[1];

            if (str_contains($condition, $conditionKey) &&
                (str_contains($medication, $drugKey) || $drugKey === 'nsaids' && $this->isNSAID($medication))) {
                return [
                    'reason' => $contraindication[2],
                    'severity' => $contraindication[3]
                ];
            }
        }

        return null;
    }

    /**
     * Check if medication is an NSAID
     */
    private function isNSAID(string $medication): bool
    {
        $nsaids = [
            'ibuprofen', 'naproxen', 'diclofenac', 'aspirin', 'indomethacin',
            'meloxicam', 'celecoxib', 'ketorolac', 'piroxicam', 'sulindac'
        ];

        foreach ($nsaids as $nsaid) {
            if (str_contains(strtolower($medication), $nsaid)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Calculate overall severity level
     */
    private function calculateSeverity(array $warnings, array $errors): string
    {
        if (!empty($errors)) {
            return 'high';
        }

        if (!empty($warnings)) {
            return 'medium';
        }

        return 'low';
    }

    /**
     * Get comprehensive drug safety report
     */
    public function getDrugSafetyReport(Prescription $prescription): array
    {
        $validation = $this->validatePrescription($prescription);

        return [
            'prescription' => $prescription,
            'validation' => $validation,
            'recommendations' => $this->generateRecommendations($validation),
            'alternative_suggestions' => $this->suggestAlternatives($prescription, $validation)
        ];
    }

    /**
     * Generate recommendations based on validation results
     */
    private function generateRecommendations(array $validation): array
    {
        $recommendations = [];

        if (!empty($validation['errors'])) {
            $recommendations[] = "CRITICAL: Address severe interactions before prescribing";
            $recommendations[] = "Consider consulting pharmacist or specialist";
        }

        if (!empty($validation['warnings'])) {
            $recommendations[] = "Monitor patient closely for adverse effects";
            $recommendations[] = "Consider dose adjustments or alternative medications";
        }

        if ($validation['is_safe']) {
            $recommendations[] = "Prescription appears safe based on available data";
        }

        return $recommendations;
    }

    /**
     * Suggest alternative medications if needed
     */
    private function suggestAlternatives(Prescription $prescription, array $validation): array
    {
        // This would be more sophisticated in a real system
        // For now, return empty array as this requires extensive drug database
        return [];
    }
}
