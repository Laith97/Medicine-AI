<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\AIDocumentationIntelligenceService;
use App\Models\ClinicalDocumentationIntelligence;
use App\Models\VoiceTranscription;
use App\Models\Appointment;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AIDocumentationController extends Controller
{
    private AIDocumentationIntelligenceService $aiDocService;

    public function __construct(AIDocumentationIntelligenceService $aiDocService)
    {
        $this->aiDocService = $aiDocService;
    }

    /**
     * Generate documentation from voice transcription
     */
    public function generateFromTranscription(Request $request): JsonResponse
    {
        $request->validate([
            'transcription_id' => 'required|exists:voice_transcriptions,id',
            'appointment_id' => 'nullable|exists:appointments,id'
        ]);

        try {
            $transcription = VoiceTranscription::findOrFail($request->transcription_id);
            
            // Authorization: Ensure the transcription belongs to the authenticated doctor
            if ($transcription->doctor_id !== \Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized: This transcription does not belong to you'
                ], 403);
            }

            $appointment = null;
            if ($request->appointment_id) {
                $appointment = Appointment::findOrFail($request->appointment_id);
                
                // Authorization: Ensure the appointment belongs to the authenticated doctor
                if ($appointment->doctor_id !== \Auth::id()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Unauthorized: This appointment does not belong to you'
                    ], 403);
                }
            }

            $documentation = $this->aiDocService->generateClinicalDocumentation(
                $transcription, 
                $appointment
            );

            return response()->json([
                'success' => true,
                'documentation' => $documentation->load(['patient', 'appointment', 'qualityMetrics', 'suggestedCodes']),
                'message' => 'Clinical documentation generated successfully'
            ]);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('AIDocumentationController Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Failed to generate clinical documentation'
            ], 500);
        }
    }

    /**
     * Get documentation for specific appointment
     */
    public function getDocumentation(int $appointmentId): JsonResponse
    {
        $appointment = Appointment::findOrFail($appointmentId);
        
        // Authorization: Ensure the appointment belongs to the authenticated doctor
        if ($appointment->doctor_id !== \Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to this appointment documentation'
            ], 403);
        }

        $documentation = ClinicalDocumentationIntelligence::where('appointment_id', $appointmentId)
            ->with(['qualityMetrics', 'suggestedCodes'])
            ->first();

        if (!$documentation) {
            return response()->json([
                'success' => false,
                'message' => 'No documentation found for this appointment'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'documentation' => $documentation
        ]);
    }

    /**
     * Validate and approve generated documentation
     */
    public function validateDocumentation(Request $request, int $docId): JsonResponse
    {
        $request->validate([
            'approved_sections' => 'array',
            'rejected_sections' => 'array',
            'modifications' => 'array'
        ]);

        try {
            $documentation = ClinicalDocumentationIntelligence::findOrFail($docId);
            
            // Authorization: Ensure the documentation belongs to the authenticated doctor
            $transcription = VoiceTranscription::find($documentation->generated_from_transcription_id);
            if ($transcription && $transcription->doctor_id !== \Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized: You cannot validate this documentation'
                ], 403);
            }

            // Apply modifications if any
            if ($request->has('modifications')) {
                foreach ($request->modifications as $section => $content) {
                    // Check if the section is one of the documentation fields
                    $allowedFields = [
                        'chief_complaint', 
                        'history_of_present_illness', 
                        'review_of_systems', 
                        'physical_exam_findings', 
                        'assessment', 
                        'plan', 
                        'medications_review'
                    ];
                    
                    if (in_array($section, $allowedFields)) {
                        $documentation->$section = $content;
                    }
                }
            }

            $documentation->validated_by_doctor = true;
            $documentation->validated_at = now();
            $documentation->save();

            return response()->json([
                'success' => true,
                'documentation' => $documentation,
                'message' => 'Documentation validated successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Failed to validate documentation'
            ], 500);
        }
    }

    /**
     * Validate/Approve a suggested medical code
     */
    public function validateCode(Request $request, int $codeId): JsonResponse
    {
        $request->validate([
            'is_validated' => 'required|boolean'
        ]);

        try {
            $code = \App\Models\SuggestedCode::findOrFail($codeId);
            $documentation = $code->clinicalDocumentation;
            
            // Authorization
            $transcription = VoiceTranscription::find($documentation->generated_from_transcription_id);
            if ($transcription && $transcription->doctor_id !== \Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 403);
            }

            $code->is_validated = $request->is_validated;
            $code->validated_by = \Auth::user()->name;
            $code->validated_at = $request->is_validated ? now() : null;
            $code->save();

            return response()->json([
                'success' => true,
                'message' => $request->is_validated ? 'Code approved' : 'Code rejected'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update code status'
            ], 500);
        }
    }

    /**
     * Get pending documentation for validation
     */
    public function getPendingValidation(): JsonResponse
    {
        // Only show pending docs for the authenticated doctor
        $pendingDocs = ClinicalDocumentationIntelligence::where('validated_by_doctor', false)
            ->whereHas('transcription', function($query) {
                $query->where('doctor_id', \Auth::id());
            })
            ->with(['patient', 'appointment'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json([
            'success' => true,
            'pending_documents' => $pendingDocs
        ]);
    }

    /**
     * Export documentation to PDF
     */
    public function export(int $docId)
    {
        try {
            $documentation = ClinicalDocumentationIntelligence::with(['patient', 'appointment', 'suggestedCodes'])
                ->findOrFail($docId);

            // Authorization
            $transcription = VoiceTranscription::find($documentation->generated_from_transcription_id);
            if ($transcription && $transcription->doctor_id !== \Auth::id()) {
                abort(403, 'Unauthorized access to this documentation');
            }

            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('ai.documentation-pdf', compact('documentation'));
            
            $filename = 'Clinical_Documentation_' . $documentation->patient->name . '_' . now()->format('YmdHis') . '.pdf';
            
            return $pdf->download($filename);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('AIDocumentationController Export Error: ' . $e->getMessage());
            return back()->with('error', 'Failed to export documentation: ' . $e->getMessage());
        }
    }
}
