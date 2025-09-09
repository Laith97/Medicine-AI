<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Appointment;
use App\Models\PatientRiskScore;
use App\Services\PredictiveAnalyticsService;
use App\Services\FeatureExtractor;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class PredictionController extends Controller
{
    private PredictiveAnalyticsService $predictiveService;
    private FeatureExtractor $featureExtractor;

    public function __construct(PredictiveAnalyticsService $predictiveService, FeatureExtractor $featureExtractor)
    {
        $this->predictiveService = $predictiveService;
        $this->featureExtractor = $featureExtractor;
    }

    /**
     * Store a new prediction
     */
    public function store(Request $request): JsonResponse
    {
        try {
            // Validate input
            $validator = Validator::make($request->all(), [
                'patient_id' => 'required|integer|exists:users,id',
                'appointment_id' => 'required|integer|exists:appointments,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $patientId = $request->input('patient_id');
            $appointmentId = $request->input('appointment_id');

            // Retrieve patient and appointment
            $patient = User::find($patientId);
            $appointment = Appointment::find($appointmentId);

            if (!$patient) {
                return response()->json([
                    'success' => false,
                    'message' => 'Patient not found'
                ], 404);
            }

            if (!$appointment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Appointment not found'
                ], 404);
            }

            // Predict risks
            $predictions = $this->predictiveService->predictRisks($patient, $appointment);

            // Create and save PatientRiskScore
            $riskScore = new PatientRiskScore();
            $riskScore->patient_id = $patientId;
            $riskScore->appointment_id = $appointmentId;
            $riskScore->no_show_risk = $predictions['no_show_risk'];
            $riskScore->hospitalization_risk = $predictions['hospitalization_risk'];
            $riskScore->save();

            return response()->json([
                'success' => true,
                'data' => [
                    'patient_id' => $patientId,
                    'appointment_id' => $appointmentId,
                    'no_show_risk' => $predictions['no_show_risk'],
                    'hospitalization_risk' => $predictions['hospitalization_risk'],
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Prediction failed: ' . $e->getMessage()
            ], 500);
        }
    }

}
