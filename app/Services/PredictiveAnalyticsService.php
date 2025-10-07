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
        $noShowRisk = 0.0;
        $hospitalizationRisk = 0.0;

        // Load and predict no-show risk
        try {
            $noShowClassifier = unserialize(file_get_contents(storage_path($this->getNoShowModelPath())));

            // Try different probability methods
            if (method_exists($noShowClassifier, 'predict_proba')) {
                /** @phan-suppress-next-line PhanUndeclaredMethod */
                /** @noinspection PhpUndefinedMethod */
                /** @noinspection PhpUndefinedClassInspection */
                $probabilities = $noShowClassifier->predict_proba(new Unlabeled([$features]));
            } elseif (method_exists($noShowClassifier, 'proba')) {
                /** @phan-suppress-next-line PhanUndeclaredMethod */
                /** @noinspection PhpUndefinedMethod */
                /** @noinspection PhpUndefinedClassInspection */
                $probabilities = $noShowClassifier->proba(new Unlabeled([$features]));
            } else {
                // Fallback: use predict and assume binary classification
                /** @phan-suppress-next-line PhanUndeclaredMethod */
                /** @noinspection PhpUndefinedMethod */
                /** @noinspection PhpUndefinedClassInspection */
                $prediction = $noShowClassifier->predict(new Unlabeled([$features]));
                $probabilities = [[0.5, $prediction[0] ? 1.0 : 0.0]];
            }

            $noShowRisk = $probabilities[0][1] ?? 0.0; // Probability of positive class (no-show)
        } catch (\Exception $e) {
            // Model not found or error, return default
            $noShowRisk = 0.0;
        }

        // Load and predict hospitalization risk
        try {
            $hospitalizationClassifier = unserialize(file_get_contents(storage_path($this->getHospitalizationModelPath())));

            // Try different probability methods
            if (method_exists($hospitalizationClassifier, 'predict_proba')) {
                /** @phan-suppress-next-line PhanUndeclaredMethod */
                /** @noinspection PhpUndefinedMethod */
                /** @noinspection PhpUndefinedClassInspection */
                $probabilities = $hospitalizationClassifier->predict_proba(new Unlabeled([$features]));
            } elseif (method_exists($hospitalizationClassifier, 'proba')) {
                /** @phan-suppress-next-line PhanUndeclaredMethod */
                /** @noinspection PhpUndefinedMethod */
                $probabilities = $hospitalizationClassifier->proba(new Unlabeled([$features]));
            } else {
                // Fallback: use predict and assume binary classification
                /** @phan-suppress-next-line PhanUndeclaredMethod */
                /** @noinspection PhpUndefinedMethod */
                $prediction = $hospitalizationClassifier->predict(new Unlabeled([$features]));
                $probabilities = [[0.5, $prediction[0] ? 1.0 : 0.0]];
            }

            $hospitalizationRisk = $probabilities[0][1] ?? 0.0; // Probability of positive class (high risk)
        } catch (\Exception $e) {
            // Model not found or error, return default
            $hospitalizationRisk = 0.0;
        }

        return [
            'no_show_risk' => round($noShowRisk, 4),
            'hospitalization_risk' => round($hospitalizationRisk, 4),
        ];
    }

}
