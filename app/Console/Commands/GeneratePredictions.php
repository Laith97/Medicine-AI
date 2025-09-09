<?php

namespace App\Console\Commands;

use App\Models\Appointment;
use App\Models\PatientRiskScore;
use App\Services\PredictiveAnalyticsService;
use App\Services\FeatureExtractor;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class GeneratePredictions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'predictions:generate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate risk predictions for upcoming appointments';

    private PredictiveAnalyticsService $analyticsService;
    private FeatureExtractor $featureExtractor;

    public function __construct(PredictiveAnalyticsService $analyticsService, FeatureExtractor $featureExtractor)
    {
        parent::__construct();
        $this->analyticsService = $analyticsService;
        $this->featureExtractor = $featureExtractor;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting prediction generation for upcoming appointments...');

        // Get appointments for next 7 days that don't have risk scores
        $endDate = Carbon::now()->addDays(7);
        $appointments = Appointment::with(['patient', 'patient.patientDiagnoses'])
            ->where('appointment_date', '>=', now())
            ->where('appointment_date', '<=', $endDate)
            ->whereNotNull('patient_id')
            ->whereDoesntHave('patientRiskScore')
            ->get();

        $totalAppointments = $appointments->count();
        $this->info("Found {$totalAppointments} appointments to process");

        if ($totalAppointments === 0) {
            $this->info('No appointments to process. Exiting.');
            return;
        }

        $processed = 0;
        $successes = 0;
        $failures = 0;

        // Process in chunks of 100
        $appointments->chunk(100)->each(function ($chunk) use (&$processed, &$successes, &$failures, $totalAppointments) {
            foreach ($chunk as $appointment) {
                $processed++;

                try {
                    $this->processAppointment($appointment);
                    $successes++;
                    $this->info("Processed appointment {$processed}/{$totalAppointments}: Success");
                } catch (\Exception $e) {
                    $failures++;
                    $this->error("Failed to process appointment {$appointment->id}: " . $e->getMessage());
                    Log::error('Prediction generation failed for appointment ' . $appointment->id, [
                        'error' => $e->getMessage(),
                        'appointment_id' => $appointment->id,
                        'patient_id' => $appointment->patient_id,
                    ]);
                }
            }
        });

        $this->info("Prediction generation completed:");
        $this->info("- Total appointments processed: {$processed}");
        $this->info("- Successful predictions: {$successes}");
        $this->info("- Failed predictions: {$failures}");

        return $successes > 0 ? 0 : 1;
    }

    /**
     * Process a single appointment
     */
    private function processAppointment(Appointment $appointment)
    {
        $patient = $appointment->patient;

        if (!$patient) {
            throw new \Exception('Patient not found for appointment ' . $appointment->id);
        }

        // Get predictions
        $predictions = $this->analyticsService->predictRisks($patient, $appointment);

        // Create risk score record
        $riskScore = new PatientRiskScore();
        $riskScore->patient_id = $patient->id;
        $riskScore->appointment_id = $appointment->id;
        $riskScore->no_show_risk = $predictions['no_show_risk'];
        $riskScore->hospitalization_risk = $predictions['hospitalization_risk'];
        $riskScore->save();
    }

}
