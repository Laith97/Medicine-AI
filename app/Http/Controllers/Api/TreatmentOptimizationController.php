<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TreatmentOptimizationRecommendation;
use App\Services\TreatmentOptimizationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TreatmentOptimizationController extends Controller
{
    private TreatmentOptimizationService $optimizationService;

    public function __construct(TreatmentOptimizationService $optimizationService)
    {
        $this->optimizationService = $optimizationService;
    }

    /**
     * Get recommendations for a patient and appointment
     */
    public function index(Request $request, $patientId, $appointmentId)
    {
        $recommendations = TreatmentOptimizationRecommendation::where('patient_id', $patientId)
            ->where('appointment_id', $appointmentId)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($recommendations);
    }

    /**
     * Generate new treatment optimization recommendations
     */
    public function store(Request $request)
    {
        $request->validate([
            'patient_id' => 'required|exists:users,id',
            'appointment_id' => 'required|exists:appointments,id',
            'conditions' => 'required|array',
            'demographics' => 'nullable|array'
        ]);

        $recommendation = $this->optimizationService->generateTreatmentOptimization(
            $request->patient_id,
            $request->appointment_id,
            $request->conditions,
            $request->demographics ?? []
        );

        return response()->json($recommendation, 201);
    }

    /**
     * Validate and implement a recommendation
     */
    public function validateRecommendation(Request $request, $id)
    {
        $recommendation = TreatmentOptimizationRecommendation::findOrFail($id);
        
        $recommendation->update([
            'validated_by_doctor' => true,
            'validated_at' => now(),
            'implemented' => true
        ]);

        return response()->json([
            'message' => 'Recommendation validated and implemented successfully',
            'recommendation' => $recommendation
        ]);
    }

    /**
     * Reject a recommendation
     */
    public function rejectRecommendation(Request $request, $id)
    {
        $recommendation = TreatmentOptimizationRecommendation::findOrFail($id);
        
        $recommendation->update([
            'validated_by_doctor' => false,
            'validated_at' => now(),
            'implemented' => false
        ]);

        return response()->json([
            'message' => 'Recommendation rejected',
            'recommendation' => $recommendation
        ]);
    }
}
