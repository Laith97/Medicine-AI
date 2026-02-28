<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\User;
use App\Models\Diagnosis;
use Rubix\ML\Classifiers\RandomForest;
use Rubix\ML\Datasets\Labeled;
use Rubix\ML\Datasets\Unlabeled;
use Rubix\ML\Persisters\Filesystem;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class PredictiveAnalyticsService
{
    private FeatureExtractor $featureExtractor;

    public function __construct(FeatureExtractor $featureExtractor)
    {
        $this->featureExtractor = $featureExtractor;
    }

    /**
     * Get the path for no-show model
     */
    private function getNoShowModelPath(): string
    {
        return config('predictive-analytics.models.no_show.path', 'app/models/no_show_model.rbx');
    }

    /**
     * Get the path for hospitalization model
     */
    private function getHospitalizationModelPath(): string
    {
        return config('predictive-analytics.models.hospitalization.path', 'app/models/hospitalization_model.rbx');
    }

    /**
     * Train the ML models using historical data
     */
    public function trainModels()
    {
        // Query historical appointments with patient data
        $appointments = Appointment::with(['patient', 'patient.patientDiagnoses'])
            ->whereNotNull('patient_id')
            ->where('appointment_date', '<', now())
            ->get();

        $noShowSamples = [];
        $noShowLabels = [];
        $hospitalizationSamples = [];
        $hospitalizationLabels = [];

        foreach ($appointments as $appointment) {
            $patient = $appointment->patient;
            if (!$patient) continue;

            // Build features
            $features = $this->featureExtractor->extractFeatures($patient, $appointment);

            // No-show label
            $noShowLabel = in_array($appointment->status, ['missed', 'no_show']) ? '1' : '0';
            $noShowSamples[] = $features;
            $noShowLabels[] = $noShowLabel;

            // Hospitalization label (MVP: simple rule based on diagnoses)
            $hospitalizationLabel = $this->featureExtractor->hasHighRiskCondition($patient) ? '1' : '0';
            $hospitalizationSamples[] = $features;
            $hospitalizationLabels[] = $hospitalizationLabel;
        }

        // Train no-show model
        if (!empty($noShowSamples)) {
            $noShowDataset = new Labeled($noShowSamples, $noShowLabels);
            $noShowClassifier = new RandomForest();
            $noShowClassifier->train($noShowDataset);

            // Save model
            file_put_contents(storage_path($this->getNoShowModelPath()), serialize($noShowClassifier));
        }

        // Train hospitalization model
        if (!empty($hospitalizationSamples)) {
            $hospitalizationDataset = new Labeled($hospitalizationSamples, $hospitalizationLabels);
            $hospitalizationClassifier = new RandomForest();
            $hospitalizationClassifier->train($hospitalizationDataset);

            // Save model
            file_put_contents(storage_path($this->getHospitalizationModelPath()), serialize($hospitalizationClassifier));
        }
    }

    /**
     * Predict risks for given patient and appointment
     *
     * @param User $patient
     * @param Appointment $appointment
     * @return array
     */
    public function predictRisks(User $patient, Appointment $appointment): array
    {
        $features = $this->featureExtractor->extractFeatures($patient, $appointment);
        return $this->predictRisksFromFeatures($features);
    }

    /**
     * Predict risks for given features array
     *
     * NOTE: IDE warnings about Rubix ML methods are expected due to dynamic method resolution.
     * The code uses method_exists() checks and will work correctly at runtime.
     *
     * @param array $features
     * @return array
     */
    public function predictRisksFromFeatures(array $features): array
    {
        // Debug: Log features being used
        Log::info('ML Risk Assessment - Features', [
            'features' => $features,
            'feature_breakdown' => [
                'no_show_count' => $features[0] ?? 'N/A',
                'cancellation_count' => $features[1] ?? 'N/A',
                'last_visit_days' => $features[2] ?? 'N/A',
                'visit_frequency' => $features[3] ?? 'N/A',
                'age' => $features[4] ?? 'N/A',
                'gender' => $features[5] ?? 'N/A',
                'chronic_conditions' => $features[6] ?? 'N/A',
                'medication_count' => $features[7] ?? 'N/A',
                'lead_time' => $features[8] ?? 'N/A',
            ]
        ]);

        // Try ML prediction first
        $mlNoShowRisk = $this->predictNoShowRisk($features);
        $mlHospitalizationRisk = $this->predictHospitalizationRisk($features);

        Log::info('ML Risk Assessment - ML Predictions', [
            'ml_no_show_risk' => $mlNoShowRisk,
            'ml_hospitalization_risk' => $mlHospitalizationRisk
        ]);

        // Check if ML models are adequately trained (have seen positive examples)
        $trainingDataCheck = $this->checkTrainingDataAdequacy();

        Log::info('ML Risk Assessment - Training Data Check', $trainingDataCheck);

        // Use rule-based fallback if ML models are not adequately trained
        // or if predictions are suspiciously low (indicating poor training)
        $useFallback = !$trainingDataCheck['adequate'] ||
                       ($mlNoShowRisk < 0.001 && $mlHospitalizationRisk < 0.001);

        Log::info('ML Risk Assessment - Prediction Method', [
            'use_fallback' => $useFallback,
            'reason' => $useFallback ?
                ($trainingDataCheck['adequate'] ? 'ML predictions too low' : 'Training data inadequate') :
                'Using ML predictions'
        ]);

        if ($useFallback) {
            // Rule-based risk calculation
            $ruleBasedRisks = $this->calculateRuleBasedRisks($features);
            $noShowRisk = $ruleBasedRisks['no_show_risk'];
            $hospitalizationRisk = $ruleBasedRisks['hospitalization_risk'];

            Log::info('ML Risk Assessment - Rule-based Results', [
                'no_show_risk' => $noShowRisk,
                'hospitalization_risk' => $hospitalizationRisk,
                'rule_based_breakdown' => $ruleBasedRisks
            ]);
        } else {
            $noShowRisk = $mlNoShowRisk;
            $hospitalizationRisk = $mlHospitalizationRisk;

            Log::info('ML Risk Assessment - ML Results', [
                'no_show_risk' => $noShowRisk,
                'hospitalization_risk' => $hospitalizationRisk
            ]);
        }

        $finalResult = [
            'no_show_risk' => round($noShowRisk, 4),
            'hospitalization_risk' => round($hospitalizationRisk, 4),
        ];

        Log::info('ML Risk Assessment - Final Result', $finalResult);

        return $finalResult;
    }

    /**
     * Predict no-show risk using ML model
     */
    private function predictNoShowRisk(array $features): float
    {
        try {
            $noShowClassifier = unserialize(file_get_contents(storage_path($this->getNoShowModelPath())));
            $dataset = new Unlabeled([$features]);

            if (method_exists($noShowClassifier, 'proba')) {
                $probabilities = $noShowClassifier->proba($dataset);
                return $probabilities[0][1] ?? 0.0;
            } else {
                $prediction = $noShowClassifier->predict($dataset);
                return $prediction[0] ? 1.0 : 0.0;
            }
        } catch (\Exception $e) {
            return 0.0;
        }
    }

    /**
     * Predict hospitalization risk using ML model
     */
    private function predictHospitalizationRisk(array $features): float
    {
        try {
            $hospitalizationClassifier = unserialize(file_get_contents(storage_path($this->getHospitalizationModelPath())));
            $dataset = new Unlabeled([$features]);

            if (method_exists($hospitalizationClassifier, 'proba')) {
                $probabilities = $hospitalizationClassifier->proba($dataset);
                return $probabilities[0][1] ?? 0.0;
            } else {
                $prediction = $hospitalizationClassifier->predict($dataset);
                return $prediction[0] ? 1.0 : 0.0;
            }
        } catch (\Exception $e) {
            return 0.0;
        }
    }

    /**
     * Check if training data is adequate for reliable ML predictions
     */
    private function checkTrainingDataAdequacy(): array
    {
        // Query recent historical appointments
        $appointments = Appointment::with(['patient', 'patient.patientDiagnoses'])
            ->whereNotNull('patient_id')
            ->where('appointment_date', '<', now())
            ->get();

        $totalAppointments = $appointments->count();
        $noShowCount = 0;
        $highRiskCount = 0;

        foreach ($appointments as $appointment) {
            if (in_array($appointment->status, ['missed', 'no_show'])) {
                $noShowCount++;
            }
            if ($appointment->patient && $this->featureExtractor->hasHighRiskCondition($appointment->patient)) {
                $highRiskCount++;
            }
        }

        // Minimum requirements for adequate training
        $minAppointments = 50;
        $minNoShowRate = 0.02; // At least 2% no-show rate
        $minHighRiskRate = 0.05; // At least 5% high-risk patients

        $adequate = $totalAppointments >= $minAppointments &&
                   ($noShowCount / max($totalAppointments, 1)) >= $minNoShowRate &&
                   ($highRiskCount / max($totalAppointments, 1)) >= $minHighRiskRate;

        return [
            'adequate' => $adequate,
            'total_appointments' => $totalAppointments,
            'no_show_count' => $noShowCount,
            'high_risk_count' => $highRiskCount,
            'no_show_rate' => $noShowCount / max($totalAppointments, 1),
            'high_risk_rate' => $highRiskCount / max($totalAppointments, 1),
        ];
    }

    /**
     * Calculate rule-based risk scores when ML models are not adequately trained
     */
    private function calculateRuleBasedRisks(array $features): array
    {
        // Features: [no_show_count, cancellation_count, last_visit_days, visit_frequency, age, gender, chronic_conditions, medication_count, lead_time]

        $noShowCount = $features[0] ?? 0;
        $cancellationCount = $features[1] ?? 0;
        $lastVisitDays = $features[2] ?? 365;
        $visitFrequency = $features[3] ?? 0;
        $age = $features[4] ?? 30;
        $gender = $features[5] ?? 0;
        $chronicConditions = $features[6] ?? 0;
        $medicationCount = $features[7] ?? 0;
        $leadTime = $features[8] ?? 7;

        // No-show risk calculation
        $noShowRisk = 0.0;

        if ($noShowCount > 0) {
            $noShowRisk += min($noShowCount * 0.2, 0.5);
        }

        if ($cancellationCount > 0) {
            $noShowRisk += min($cancellationCount * 0.1, 0.3);
        }

        if ($lastVisitDays > 365) {
            $noShowRisk += 0.15;
        } elseif ($lastVisitDays > 180) {
            $noShowRisk += 0.08;
        }

        if ($age < 25 || $age > 70) {
            $noShowRisk += 0.05;
        }

        if ($leadTime < 2) {
            $noShowRisk += 0.1; // Last-minute appointments higher risk
        }

        // Hospitalization risk calculation
        $hospitalizationRisk = 0.0;

        if ($chronicConditions >= 3) {
            $hospitalizationRisk += 0.4;
        } elseif ($chronicConditions >= 2) {
            $hospitalizationRisk += 0.25;
        } elseif ($chronicConditions >= 1) {
            $hospitalizationRisk += 0.15;
        }

        if ($medicationCount >= 5) {
            $hospitalizationRisk += 0.2; // Polypharmacy risk
        } elseif ($medicationCount >= 3) {
            $hospitalizationRisk += 0.1;
        }

        if ($age > 65) {
            $hospitalizationRisk += 0.2;
        } elseif ($age > 50) {
            $hospitalizationRisk += 0.1;
        }

        if ($gender === 1) {
            $hospitalizationRisk += 0.05;
        }

        if ($visitFrequency > 12) {
            $hospitalizationRisk += 0.15; // Frequent visits indicate health issues
        }

        $noShowRisk = min($noShowRisk, 1.0);
        $hospitalizationRisk = min($hospitalizationRisk, 1.0);

        return [
            'no_show_risk' => $noShowRisk,
            'hospitalization_risk' => $hospitalizationRisk,
        ];
    }

}
