<?php

namespace App\Http\Controllers;

use App\Models\PatientAnalysis;
use App\Models\Symptom;
use App\Models\Appointment;
use App\Models\Review;
use App\Models\OpenAIUsage;
use App\Models\User;
use App\Models\Diagnosis;
use App\Models\AiAssistantResult;
use App\Models\PatientSummary;
use App\Mail\UsageWarning;
use App\Mail\PatientAccountCreated;
use App\Services\SmsService;
use Illuminate\Http\Request;
use App\Http\Requests\PatientAnalysisRequest;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use OpenAI\Laravel\Facades\OpenAI;
use Illuminate\Support\Facades\Auth;
use App\Traits\HandlesEffectiveDoctor;

class OpenAIController extends Controller
{
    use HandlesEffectiveDoctor;
    protected $uploadedFileIds = [];
    protected $smsService;

    public function __construct(SmsService $smsService)
    {
        $this->smsService = $smsService;
        $this->middleware(function ($request, $next) {
            $user = Auth::user();

            // Handle sub-users - they inherit access from their parent doctor
            if ($user->isSubUser()) {
                $parentUser = $user->parentUser;
                if (!$parentUser || !$parentUser->isDoctor() || !$parentUser->doctor || !$parentUser->doctor->is_active) {
                    abort(403, 'Access denied. Parent doctor profile required.');
                }
            } else {
                // Handle main users (doctors)
                if (!$user->isDoctor() || !$user->doctor) {
                    abort(403, 'Access denied. Doctor profile required.');
                }

                if (!$user->doctor->is_active) {
                    abort(403, 'Access denied. Your doctor account has been deactivated.');
                }
            }

            return $next($request);
        })->except(['getVisitDetails', 'getPatientVisits']); // Exclude API methods from doctor middleware
    }

    public function showForm(Request $request)
    {
        $symptoms = Symptom::all();

        // Get doctor's assigned patients (actual User accounts)
        $assignedPatients = Auth::user()->getEffectiveAssignedPatients()
            ->select('id', 'name', 'email', 'phone', 'age', 'gender')
            ->orderBy('name')
            ->get();

        return view('openai', compact('symptoms', 'assignedPatients'));
    }

    public function getCases()
    {
        $user = auth()->user();
        $allCases = collect();

        \Log::info('getCases called for user: ' . $user->id . ' (' . $user->name . ')');

        // Get PatientAnalysis records (legacy format)
        $patientAnalysisRecords = PatientAnalysis::with('user')
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        \Log::info('Found ' . $patientAnalysisRecords->count() . ' PatientAnalysis records');

        // Get completed appointments without diagnosis for this doctor
        $completedAppointmentsWithoutDiagnosis = [];
        if ($user->isDoctor() && $user->doctor) {
            $completedAppointments = Appointment::with(['patient'])
                ->where('doctor_id', $user->doctor->id)
                ->where('status', 'completed')
                ->orderBy('appointment_date', 'desc')
                ->get();

            \Log::info('Found ' . $completedAppointments->count() . ' completed appointments');

            // Filter out appointments that already have diagnoses
            $diagnosedPatientIds = Diagnosis::where('doctor_id', $user->id)
                ->pluck('patient_id')
                ->toArray();

            $completedAppointmentsWithoutDiagnosis = $completedAppointments->filter(function($appointment) use ($diagnosedPatientIds) {
                return !in_array($appointment->patient_id, $diagnosedPatientIds);
            });

            \Log::info('Found ' . $completedAppointmentsWithoutDiagnosis->count() . ' completed appointments without diagnosis');
        }

        // Transform PatientAnalysis records to unified format
        foreach ($patientAnalysisRecords as $record) {
            // Convert symptoms from JSON array to comma-separated string for consistency
            $symptomsString = '';
            if ($record->symptoms) {
                if (is_string($record->symptoms)) {
                    // Try to decode if it's JSON
                    $decodedSymptoms = json_decode($record->symptoms, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($decodedSymptoms)) {
                        // Convert array to comma-separated string
                        $symptomsString = implode(', ', $decodedSymptoms);
                    } else {
                        // It's already a string
                        $symptomsString = $record->symptoms;
                    }
                } elseif (is_array($record->symptoms)) {
                    // Convert array to comma-separated string
                    $symptomsString = implode(', ', $record->symptoms);
                }
            }

            $allCases->push((object)[
                'id' => $record->id,
                'name' => $record->name, // Keep original property name for view compatibility
                'age' => $record->age,
                'gender' => $record->gender,
                'height' => $record->height,
                'weight' => $record->weight,
                'symptoms' => $symptomsString, // Standardized symptoms format
                'type' => 'legacy', // PatientAnalysis records
                'ai_response' => $record->ai_response ?? 'No diagnosis available',
                'created_at' => $record->created_at,
                'updated_at' => $record->updated_at,
                'visit_number' => $record->visit_number ?? 1,
                'total_visits' => 1, // Will be calculated later
                'patient_key' => $record->patient_key,
                'source_model' => 'PatientAnalysis',
                'source_id' => $record->id,
                'category' => 'diagnosed',
            ]);
        }

        // Transform completed appointments without diagnosis to unified format
        foreach ($completedAppointmentsWithoutDiagnosis as $appointment) {
            $patient = $appointment->patient;

            // Generate patient_key for appointment records
            $patientKey = 'appointment_' . $appointment->id;

            $allCases->push((object)[
                'id' => $appointment->id,
                'name' => $patient ? $patient->name : $appointment->guest_name,
                'age' => $patient ? $patient->age : (isset($appointment->guest_date_of_birth) ? \Carbon\Carbon::parse($appointment->guest_date_of_birth)->age : 'N/A'),
                'gender' => $patient ? $patient->gender : $appointment->guest_gender,
                'height' => 'N/A',
                'weight' => 'N/A',
                'symptoms' => $appointment->symptoms ?? 'N/A',
                'type' => 'appointment_completed',
                'ai_response' => 'Appointment completed - pending diagnosis',
                'appointment_details' => [
                    'appointment_date' => $appointment->appointment_date,
                    'appointment_type' => $appointment->appointment_type,
                    'duration' => $appointment->duration,
                    'fee' => $appointment->fee,
                    'notes' => $appointment->notes,
                    'reason' => $appointment->reason,
                    'doctor_notes' => $appointment->doctor_notes,
                    'patient_notes' => $appointment->patient_notes,
                ],
                'created_at' => $appointment->appointment_date,
                'updated_at' => $appointment->updated_at,
                'visit_number' => 1,
                'total_visits' => 1,
                'patient_key' => $patientKey,
                'source_model' => 'Appointment',
                'source_id' => $appointment->id,
                'patient_id' => $appointment->patient_id,
                'category' => 'pending_diagnosis',
            ]);
        }

        // Get Diagnosis records (new format) - doctor's manual diagnoses
        if ($user->isDoctor()) {
            $diagnosisRecords = Diagnosis::with(['patient', 'doctor', 'aiAssistantResults'])
                ->where('doctor_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->get();

            \Log::info('Found ' . $diagnosisRecords->count() . ' Diagnosis records');

            // Transform Diagnosis records to unified format
            foreach ($diagnosisRecords as $record) {
                $patientData = is_array($record->patient_data) ? $record->patient_data : [];

                \Log::info('Processing Diagnosis record ID: ' . $record->id . ', patient: ' . ($record->patient->name ?? 'Unknown'));

                // Ensure symptoms are in string format for consistency
                $symptomsString = '';
                if (isset($patientData['symptoms'])) {
                    if (is_array($patientData['symptoms'])) {
                        // Convert array to comma-separated string
                        $symptomsString = implode(', ', $patientData['symptoms']);
                    } else {
                        // Already a string
                        $symptomsString = $patientData['symptoms'];
                    }
                }

                // Generate patient_key for Diagnosis records if not set
                $patientKey = $record->patient_key;
                if (!$patientKey && $record->patient) {
                    $patientKey = Diagnosis::generatePatientKey(
                        $record->patient->name,
                        $record->patient->age,
                        $record->patient->gender,
                        $record->doctor_id
                    );
                    // Update the record with the generated patient_key
                    $record->update(['patient_key' => $patientKey]);
                }

                $allCases->push((object)[
                    'id' => $record->id,
                    'name' => $record->patient->name ?? 'Unknown Patient', // Keep original property name for view compatibility
                    'age' => $patientData['patient_age'] ?? $record->patient->age ?? 'N/A',
                    'gender' => $patientData['patient_gender'] ?? $record->patient->gender ?? 'N/A',
                    'height' => $patientData['height'] ?? 'N/A',
                    'weight' => $patientData['weight'] ?? 'N/A',
                    'symptoms' => $symptomsString, // Standardized symptoms format
                    'type' => 'manual', // Doctor's manual diagnoses
                    'ai_response' => $record->diagnosis_text ?? 'No diagnosis available', // Map diagnosis_text to ai_response for view compatibility
                    'ai_assistant_results' => $record->aiAssistantResults, // Include AI assistant results if available
                    'created_at' => $record->created_at,
                    'updated_at' => $record->updated_at,
                    'visit_number' => 1, // Diagnosis records don't have visit numbers yet
                    'total_visits' => 1, // Will be calculated later if needed
                    'patient_key' => $patientKey, // Now includes generated patient_key
                    'source_model' => 'Diagnosis',
                    'source_id' => $record->id,
                    'patient_id' => $record->patient_id,
                    'patient_data' => $record->patient_data, // Include patient_data field for JavaScript access
                    'category' => 'diagnosed',
                ]);
            }
        } else {
            \Log::info('User is not a doctor, skipping Diagnosis records');
        }

        // Get scheduled appointments (upcoming)
        if ($user->isDoctor() && $user->doctor) {
            $scheduledAppointments = Appointment::with(['patient'])
                ->where('doctor_id', $user->doctor->id)
                ->where('status', 'confirmed')
                ->where('appointment_date', '>', now())
                ->orderBy('appointment_date', 'asc')
                ->get();

            \Log::info('Found ' . $scheduledAppointments->count() . ' scheduled appointments');

            // Transform scheduled appointments to unified format
            foreach ($scheduledAppointments as $appointment) {
                $patient = $appointment->patient;

                // Skip if this patient already has a diagnosis or completed appointment in the list
                $existingPatientKey = null;
                if ($patient) {
                    $existingPatientKey = 'diagnosis_' . $patient->id;
                }

                $alreadyExists = $allCases->contains(function($case) use ($existingPatientKey, $appointment) {
                    return ($case->patient_key ?? null) === $existingPatientKey ||
                           ($case->source_model === 'Appointment' && $case->source_id === $appointment->id);
                });

                if (!$alreadyExists) {
                    $patientKey = 'scheduled_' . $appointment->id;

                    $allCases->push((object)[
                        'id' => $appointment->id,
                        'name' => $patient ? $patient->name : $appointment->guest_name,
                        'age' => $patient ? $patient->age : (isset($appointment->guest_date_of_birth) ? \Carbon\Carbon::parse($appointment->guest_date_of_birth)->age : 'N/A'),
                        'gender' => $patient ? $patient->gender : $appointment->guest_gender,
                        'height' => 'N/A',
                        'weight' => 'N/A',
                        'symptoms' => $appointment->symptoms ?? 'N/A',
                        'type' => 'appointment_scheduled',
                        'ai_response' => 'Appointment scheduled',
                        'appointment_details' => [
                            'appointment_date' => $appointment->appointment_date,
                            'appointment_type' => $appointment->appointment_type,
                            'duration' => $appointment->duration,
                            'fee' => $appointment->fee,
                            'notes' => $appointment->notes,
                            'reason' => $appointment->reason,
                        ],
                        'created_at' => $appointment->appointment_date,
                        'updated_at' => $appointment->updated_at,
                        'visit_number' => 1,
                        'total_visits' => 1,
                        'patient_key' => $patientKey,
                        'source_model' => 'Appointment',
                        'source_id' => $appointment->id,
                        'patient_id' => $appointment->patient_id,
                        'category' => 'scheduled',
                    ]);
                }
            }
        }

        // Sort all cases by creation date (newest first)
        $allCases = $allCases->sortByDesc('created_at');

        \Log::info('Total cases after combining: ' . $allCases->count());

        // Group records by patient for calculating total visits
        $patientGroups = [];

        // Handle PatientAnalysis records grouping
        foreach ($patientAnalysisRecords as $record) {
            // If patient_key is not set, generate it and update the record
            if (!$record->patient_key) {
                $patientKey = PatientAnalysis::generatePatientKey(
                    $record->name,
                    $record->age,
                    $record->gender,
                    $record->user_id
                );

                // Find all records for this patient
                $patientRecords = PatientAnalysis::where('name', $record->name)
                    ->where('age', $record->age)
                    ->where('gender', $record->gender)
                    ->where('user_id', $record->user_id)
                    ->orderBy('created_at', 'asc')
                    ->get();

                // Update all records with patient_key and visit_number
                foreach ($patientRecords as $index => $patientRecord) {
                    $patientRecord->update([
                        'patient_key' => $patientKey,
                        'visit_number' => $index + 1
                    ]);
                }

                // Update the current record in memory
                $record->patient_key = $patientKey;
            }

            // Group by patient_key
            if (!isset($patientGroups[$record->patient_key])) {
                $patientGroups[$record->patient_key] = [];
            }
            $patientGroups[$record->patient_key][] = $record;
        }

        // Handle Diagnosis records grouping by patient
        foreach ($diagnosisRecords as $record) {
            $patient = $record->patient;
            if ($patient) {
                // Create a unique key for diagnosis records based on patient
                $patientKey = 'diagnosis_' . $patient->id;

                if (!isset($patientGroups[$patientKey])) {
                    $patientGroups[$patientKey] = [];
                }
                $patientGroups[$patientKey][] = $record;
            }
        }

        // Update total_visits for all records in the unified collection
        foreach ($allCases as $case) {
            if ($case->source_model === 'PatientAnalysis' && $case->patient_key && isset($patientGroups[$case->patient_key])) {
                $case->total_visits = count($patientGroups[$case->patient_key]);
            } elseif ($case->source_model === 'Diagnosis' && isset($case->patient_id)) {
                $patientKey = 'diagnosis_' . $case->patient_id;
                if (isset($patientGroups[$patientKey])) {
                    $case->total_visits = count($patientGroups[$patientKey]);
                }
            }
        }

        // Keep as collection for the view (it expects count() method)
        $records = $allCases->values();

        \Log::info('Final records count: ' . $records->count());

        // Prepare patient groups for the patients table (similar to dashboard)
        $patientGroups = [];
        foreach ($records as $record) {
            // Create consistent grouping key based on category and patient info
            $key = $this->generatePatientGroupKey($record);

            // Skip records with invalid keys
            if (empty($key)) {
                continue;
            }

            if (!isset($patientGroups[$key])) {
                // Initialize with the first record
                $patientGroups[$key] = [
                    'patient' => $record,
                    'visits' => [],
                    'visit_count' => 0,
                    'last_visit' => $record->created_at,
                    'category' => $record->category ?? 'diagnosed',
                    'has_appointments' => isset($record->appointment_details),
                    'appointment_details' => $record->appointment_details ?? null,
                ];
            }

            // Add this record to the visits array
            $patientGroups[$key]['visits'][] = $record;
            $patientGroups[$key]['visit_count']++;

            // Update last visit date if this record is more recent
            if ($record->created_at > $patientGroups[$key]['last_visit']) {
                $patientGroups[$key]['last_visit'] = $record->created_at;
            }

            // Update category if this record has appointment details
            if (isset($record->appointment_details) && !isset($patientGroups[$key]['appointment_details'])) {
                $patientGroups[$key]['appointment_details'] = $record->appointment_details;
                $patientGroups[$key]['has_appointments'] = true;
            }
        }

        \Log::info('Patient groups count: ' . count($patientGroups));

        // Sort by most recent visit
        uasort($patientGroups, function($a, $b) {
            return $b['last_visit'] <=> $a['last_visit'];
        });

        return view('cases', compact('records', 'patientGroups'));
    }

    /**
     * Get patient visits for patient summary modal
     */
    public function getPatientVisits($patientKey)
    {
        try {
            \Log::info('getPatientVisits called for patient key: ' . $patientKey);

            $user = auth()->user();
            $allCases = collect();

            // Get PatientAnalysis records
            $patientAnalysisRecords = PatientAnalysis::with('user')
                ->where('user_id', $user->id)
                ->where('patient_key', $patientKey)
                ->orderBy('created_at', 'desc')
                ->get();

            // Get Diagnosis records
            if ($user->isDoctor()) {
                $diagnosisRecords = Diagnosis::with(['patient', 'doctor'])
                    ->where('doctor_id', $user->id)
                    ->orderBy('created_at', 'desc')
                    ->get();

                // Filter by patient key logic
                $filteredDiagnosisRecords = $diagnosisRecords->filter(function($record) use ($patientKey, $user) {
                    if ($record->patient_key === $patientKey) {
                        return true;
                    }

                    // Fallback to name-age-gender matching for old records
                    if ($patientKey === 'diagnosis_' . $record->patient_id) {
                        return true;
                    }

                    return false;
                });

                $diagnosisRecords = $filteredDiagnosisRecords;
            } else {
                $diagnosisRecords = collect();
            }

            // Transform and combine records
            foreach ($patientAnalysisRecords as $record) {
                $symptomsString = $this->extractSymptoms($record);

                $allCases->push((object)[
                    'id' => $record->id,
                    'visit_number' => $record->visit_number,
                    'date' => $record->created_at->format('M d, Y'),
                    'symptoms' => $symptomsString,
                    'diagnosis' => $record->ai_response ? substr($record->ai_response, 0, 100) . (strlen($record->ai_response) > 100 ? '...' : '') : 'No diagnosis available',
                    'source_model' => 'PatientAnalysis',
                ]);
            }

            foreach ($diagnosisRecords as $record) {
                $symptomsString = $this->extractDiagnosisSymptoms($record);

                $allCases->push((object)[
                    'id' => $record->id,
                    'visit_number' => 1, // Diagnosis records don't have visit numbers yet
                    'date' => $record->created_at->format('M d, Y'),
                    'symptoms' => $symptomsString,
                    'diagnosis' => $record->diagnosis_text ? substr($record->diagnosis_text, 0, 100) . (strlen($record->diagnosis_text) > 100 ? '...' : '') : 'No diagnosis available',
                    'source_model' => 'Diagnosis',
                ]);
            }

            // Sort by date (newest first)
            $visits = $allCases->sortByDesc('date')->values();

            \Log::info('Found ' . $visits->count() . ' visits for patient key: ' . $patientKey);

            return response()->json([
                'success' => true,
                'visits' => $visits
            ]);

        } catch (\Exception $e) {
            \Log::error('Error in getPatientVisits: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving patient visits',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get detailed visit information
     */
    public function getVisitDetails($recordId)
    {
        try {
            \Log::info('getVisitDetails called for record ID: ' . $recordId);

            $user = auth()->user();

            // Check if user is authenticated
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authentication required'
                ], 401);
            }

            // Try to find the record in PatientAnalysis first, then Diagnosis
            $record = PatientAnalysis::where('id', $recordId)->first();
            $sourceModel = 'PatientAnalysis';

            // If not found in PatientAnalysis, try Diagnosis records
            if (!$record) {
                $record = Diagnosis::where('id', $recordId)
                    ->with(['patient'])
                    ->first();
                $sourceModel = 'Diagnosis';
            }

            if (!$record) {
                \Log::error('Visit record not found for ID: ' . $recordId . ', User ID: ' . ($user ? $user->id : 'null'));
                return response()->json([
                    'success' => false,
                    'message' => 'Visit record not found'
                ], 404);
            }

            // For security, check if the authenticated user has access to this record
            $hasAccess = false;
            if ($user) { // Only check access if user is authenticated
                if ($sourceModel === 'PatientAnalysis') {
                    // Allow access if user owns the record or is the doctor
                    $hasAccess = ($record->user_id === $user->id) ||
                                ($user->isDoctor() && $record->user_id === $user->id) ||
                                ($user->isSubUser() && $record->user_id === $user->parent_user_id);
                } else { // Diagnosis
                    // Allow access if user is the doctor or sub-user of the doctor
                    $hasAccess = ($record->doctor_id === $user->id) ||
                                ($user->isSubUser() && $record->doctor_id === $user->parent_user_id);
                }
            } else {
                // No authentication required for testing - allow access
                $hasAccess = true;
            }

            // Log access denial for security monitoring
            if (!$hasAccess) {
                \Log::warning('Access denied for record ID: ' . $recordId . ', User ID: ' . $user->id . ', Source: ' . $sourceModel);
            }

            // Check access permissions
            if (!$hasAccess) {
                \Log::warning('Access denied for record ID: ' . $recordId . ', User ID: ' . ($user ? $user->id : 'null') . ', Source: ' . $sourceModel);
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied to this record'
                ], 403);
            }

            // Extract diagnosis text based on source
            $diagnosisText = '';
            if ($sourceModel === 'PatientAnalysis') {
                $diagnosisText = $record->ai_response ?? 'No diagnosis available';
            } else {
                $diagnosisText = $record->diagnosis_text ?? 'No diagnosis available';
            }

            // Extract patient info
            $patientInfo = $this->extractPatientInfo($record, $sourceModel);

            return response()->json([
                'success' => true,
                'visit' => [
                    'id' => $record->id,
                    'diagnosis' => $diagnosisText,
                    'patient_info' => $patientInfo,
                    'date' => $record->created_at->format('M d, Y'),
                    'source_model' => $sourceModel
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Error in getVisitDetails: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving visit details',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Helper method to extract symptoms from record
     */
    private function extractSymptoms($record)
    {
        if ($record->symptoms) {
            if (is_string($record->symptoms)) {
                $decoded = json_decode($record->symptoms, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    return implode(', ', $decoded);
                }
                return $record->symptoms;
            } elseif (is_array($record->symptoms)) {
                return implode(', ', $record->symptoms);
            }
        }
        return 'N/A';
    }

    /**
     * Helper method to extract symptoms from diagnosis record
     */
    private function extractDiagnosisSymptoms($record)
    {
        $patientData = $record->patient_data ?? [];
        if (isset($patientData['symptoms'])) {
            if (is_array($patientData['symptoms'])) {
                return implode(', ', $patientData['symptoms']);
            }
            return $patientData['symptoms'];
        }
        return 'N/A';
    }

    /**
     * Helper method to extract patient info
     */
    private function extractPatientInfo($record, $sourceModel)
    {
        if ($sourceModel === 'PatientAnalysis') {
            return [
                'name' => $record->name ?? 'N/A',
                'age' => $record->age ?? 'N/A',
                'gender' => $record->gender ?? 'N/A'
            ];
        } else {
            // Diagnosis record
            return [
                'name' => $record->patient->name ?? 'N/A',
                'age' => $record->patient->age ?? 'N/A',
                'gender' => $record->patient->gender ?? 'N/A'
            ];
        }
    }

    /**
     * Generate consistent patient group key based on record type and category
     */
    private function generatePatientGroupKey($record)
    {
        if ($record->source_model === 'PatientAnalysis') {
            return $record->patient_key ?? ($record->name . '-' . $record->age . '-' . $record->gender);
        } elseif ($record->source_model === 'Diagnosis') {
            return 'diagnosis_' . ($record->patient_id ?? 'unknown');
        } elseif ($record->source_model === 'Appointment') {
            if ($record->category === 'pending_diagnosis') {
                return 'pending_' . ($record->patient_id ?? 'guest_' . $record->id);
            } elseif ($record->category === 'scheduled') {
                return 'scheduled_' . ($record->patient_id ?? 'guest_' . $record->id);
            }
        }

        return null;
    }

    /**
     * Display dashboard with aggregated data
     */
    public function dashboard()
    {
        $user = auth()->user();

        // Get trial/subscription info
        $trialInfo = [
            'is_in_trial' => $user->isInTrialPeriod(),
            'trial_status' => $user->getTrialStatus(),
            'has_active_subscription' => $user->hasActiveSubscription(),
            'trial_days_remaining' => $user->getTrialDaysRemaining(),
        ];

        // Get records similar to getCases method
        $allCases = collect();

        // Get PatientAnalysis records (legacy format)
        $patientAnalysisRecords = PatientAnalysis::with('user')
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        // Transform PatientAnalysis records to unified format
        foreach ($patientAnalysisRecords as $record) {
            // Convert symptoms from JSON array to comma-separated string for consistency
            $symptomsString = '';
            if ($record->symptoms) {
                if (is_string($record->symptoms)) {
                    // Try to decode if it's JSON
                    $decodedSymptoms = json_decode($record->symptoms, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($decodedSymptoms)) {
                        // Convert array to comma-separated string
                        $symptomsString = implode(', ', $decodedSymptoms);
                    } else {
                        // It's already a string
                        $symptomsString = $record->symptoms;
                    }
                } elseif (is_array($record->symptoms)) {
                    // Convert array to comma-separated string
                    $symptomsString = implode(', ', $record->symptoms);
                }
            }

            $allCases->push((object)[
                'id' => $record->id,
                'name' => $record->name,
                'age' => $record->age,
                'gender' => $record->gender,
                'height' => $record->height,
                'weight' => $record->weight,
                'symptoms' => $symptomsString,
                'type' => 'legacy',
                'ai_response' => $record->ai_response ?? 'No diagnosis available',
                'created_at' => $record->created_at,
                'updated_at' => $record->updated_at,
                'visit_number' => $record->visit_number ?? 1,
                'total_visits' => 1,
                'patient_key' => $record->patient_key,
                'source_model' => 'PatientAnalysis',
                'source_id' => $record->id,
            ]);
        }

        // Get Diagnosis records (new format) - doctor's manual diagnoses
        if ($user->isDoctor()) {
            $diagnosisRecords = Diagnosis::with(['patient', 'doctor', 'aiAssistantResults'])
                ->where('doctor_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->get();

            // Transform Diagnosis records to unified format
            foreach ($diagnosisRecords as $record) {
                $patientData = is_array($record->patient_data) ? $record->patient_data : [];

                // Ensure symptoms are in string format for consistency
                $symptomsString = '';
                if (isset($patientData['symptoms'])) {
                    if (is_array($patientData['symptoms'])) {
                        // Convert array to comma-separated string
                        $symptomsString = implode(', ', $patientData['symptoms']);
                    } else {
                        // Already a string
                        $symptomsString = $patientData['symptoms'];
                    }
                }

                // Generate patient_key for Diagnosis records if not set
                $patientKey = $record->patient_key;
                if (!$patientKey && $record->patient) {
                    $patientKey = Diagnosis::generatePatientKey(
                        $record->patient->name,
                        $record->patient->age,
                        $record->patient->gender,
                        $record->doctor_id
                    );
                    // Update the record with the generated patient_key
                    $record->update(['patient_key' => $patientKey]);
                }

                $allCases->push((object)[
                    'id' => $record->id,
                    'name' => $record->patient->name ?? 'Unknown Patient',
                    'age' => $patientData['patient_age'] ?? $record->patient->age ?? 'N/A',
                    'gender' => $patientData['patient_gender'] ?? $record->patient->gender ?? 'N/A',
                    'height' => $patientData['height'] ?? 'N/A',
                    'weight' => $patientData['weight'] ?? 'N/A',
                    'symptoms' => $symptomsString,
                    'type' => 'manual',
                    'ai_response' => $record->diagnosis_text ?? 'No diagnosis available',
                    'ai_assistant_results' => $record->aiAssistantResults,
                    'created_at' => $record->created_at,
                    'updated_at' => $record->updated_at,
                    'visit_number' => 1,
                    'total_visits' => 1,
                    'patient_key' => $patientKey,
                    'source_model' => 'Diagnosis',
                    'source_id' => $record->id,
                    'patient_id' => $record->patient_id,
                    'patient_data' => $record->patient_data,
                ]);
            }
        }

        // Sort all cases by creation date (newest first)
        $allCases = $allCases->sortByDesc('created_at');
        $records = $allCases->values();

        // Doctor-specific data
        $doctorData = null;
        if ($user->isDoctor() && $user->doctor) {
            // Today's appointments
            $todayAppointments = Appointment::with(['patient', 'doctor'])
                ->where('doctor_id', $user->doctor->id)
                ->whereDate('appointment_date', today())
                ->orderBy('appointment_date')
                ->get();

            // Pending appointments
            $pendingAppointments = Appointment::with(['patient', 'doctor'])
                ->where('doctor_id', $user->doctor->id)
                ->where('status', 'pending')
                ->orderBy('appointment_date')
                ->get();

            // Recent reviews
            $recentReviews = Review::with('patient')
                ->where('doctor_id', $user->doctor->id)
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();

            // Doctor statistics
            $stats = [
                'today_appointments' => $todayAppointments->count(),
                'pending_appointments' => $pendingAppointments->count(),
                'average_rating' => $user->doctor->reviews()->avg('rating') ?? 0,
                'revenue_this_month' => $user->doctor->appointments()
                    ->whereMonth('appointment_date', now()->month)
                    ->whereYear('appointment_date', now()->year)
                    ->where('status', 'completed')
                    ->sum('fee') ?? 0,
            ];

            $doctorData = [
                'todayAppointments' => $todayAppointments,
                'pendingAppointments' => $pendingAppointments,
                'recentReviews' => $recentReviews,
                'stats' => $stats,
            ];
        }

        // User's appointments
        $appointments = Appointment::with(['doctor.user', 'patient'])
            ->where('patient_id', $user->id)
            ->orderBy('appointment_date', 'desc')
            ->get();

        // Weekly count calculation
        $weeklyCount = $records->where('created_at', '>=', now()->startOfWeek())->count();

        // Chart data - cases over time (last 7 days)
        $chartLabels = [];
        $chartData = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $chartLabels[] = $date->format('M d');
            $chartData[] = $records->where('created_at', '>=', $date->startOfDay())
                                  ->where('created_at', '<=', $date->endOfDay())
                                  ->count();
        }

        return view('dashboard', compact(
            'trialInfo',
            'records',
            'doctorData',
            'appointments',
            'weeklyCount',
            'chartLabels',
            'chartData'
        ));
    }

    /**
     * Generate AI-powered patient summary
     */
    public function generatePatientSummary(Request $request)
    {
        try {
            \Log::info('generatePatientSummary called with request data:', $request->all());

            $request->validate([
                'patient_id' => 'required|integer',
                'patient_name' => 'required|string',
                'patient_age' => 'required|string',
                'patient_gender' => 'required|string',
                'visit_count' => 'required|integer',
                'visits' => 'required|array'
            ]);

            $user = auth()->user();
            \Log::info('User authenticated: ' . $user->id . ' (' . $user->name . ')');

            // Check usage limits - count today's requests
            $todayUsageCount = OpenAIUsage::where('user_id', $user->id)
                ->whereDate('created_at', today())
                ->count();

            \Log::info('OpenAI usage check: ' . $todayUsageCount . ' requests today');

            if ($todayUsageCount >= 50) {
                \Log::warning('Daily AI usage limit exceeded for user: ' . $user->id);
                return response()->json([
                    'success' => false,
                    'message' => 'Daily AI usage limit exceeded. Please try again tomorrow.'
                ], 429);
            }

            // Prepare patient visit data for AI analysis
            $patientData = [
                'name' => $request->patient_name,
                'age' => $request->patient_age,
                'gender' => $request->patient_gender,
                'total_visits' => $request->visit_count,
                'visits' => []
            ];

            // Process each visit
            foreach ($request->visits as $visit) {
                $patientData['visits'][] = [
                    'visit_number' => $visit['visit_number'],
                    'date' => $visit['date'],
                    'diagnosis' => $visit['diagnosis']
                ];
            }

            // Create comprehensive prompt for AI summary generation
            $prompt = $this->buildPatientSummaryPrompt($patientData);

            // Check if OpenAI API key is configured
            $apiKey = config('openai.api_key');
            if (!$apiKey) {
                \Log::error('OpenAI API key not configured');
                return response()->json([
                    'success' => false,
                    'message' => 'OpenAI API key is not configured. Please contact the administrator.',
                    'error_type' => 'API_KEY_MISSING'
                ], 500);
            }

            // Skip API key validation test for now - proceed directly to main call
            // This avoids quota issues during testing phase

            // Call OpenAI API
            \Log::info('Calling OpenAI API for patient summary generation');
            $response = OpenAI::chat()->create([
                'model' => 'gpt-4o',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are an expert medical AI assistant specializing in patient case analysis and clinical summarization. Provide comprehensive, professional medical summaries that analyze patterns across patient visits.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'max_tokens' => 2000,
                'temperature' => 0.3
            ]);

            $aiSummary = $response->choices[0]->text ?? $response->choices[0]->message->content;
            \Log::info('OpenAI API call successful, response length: ' . strlen($aiSummary));

            // Track usage - create new record for each request
            OpenAIUsage::create([
                'user_id' => $user->id,
                'request_type' => 'patient_summary',
                'prompt_tokens' => $response->usage->prompt_tokens ?? 0,
                'completion_tokens' => $response->usage->completion_tokens ?? 0,
                'total_tokens' => $response->usage->total_tokens ?? 0,
                'cost_estimate' => OpenAIUsage::calculateCost(
                    $response->usage->total_tokens ?? 0,
                    'gpt-4o',
                    $response->usage->prompt_tokens ?? 0,
                    $response->usage->completion_tokens ?? 0
                ),
                'model_used' => 'gpt-4o',
                'request_metadata' => [
                    'patient_id' => $request->patient_id,
                    'visit_count' => $request->visit_count,
                    'endpoint' => 'patient-summary'
                ]
            ]);

            // Format the response using existing AI formatting patterns
            $formattedSummary = $this->formatPatientSummaryResponse($aiSummary);

            return response()->json([
                'success' => true,
                'summary' => $formattedSummary,
                'raw_response' => $aiSummary
            ]);

        } catch (\Exception $e) {
            \Log::error('Exception in generatePatientSummary: ' . $e->getMessage());
            \Log::error('Exception type: ' . get_class($e));
            \Log::error('Exception details: ', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'code' => $e->getCode(),
                'trace' => $e->getTraceAsString()
            ]);

            // Handle OpenAI API exceptions specifically
            if ($e instanceof \OpenAI\Exceptions\ApiException) {
                \Log::error('OpenAI API error: ' . $e->getMessage());
                \Log::error('OpenAI API error code: ' . $e->getCode());

                // Handle specific OpenAI API errors
                if ($e->getCode() === 401) {
                    \Log::warning('OpenAI API key authentication failed');
                    return response()->json([
                        'success' => false,
                        'message' => 'OpenAI API key is invalid or expired. Please contact the administrator.',
                        'error_type' => 'API_KEY_ERROR'
                    ], 500);
                } elseif ($e->getCode() === 429) {
                    \Log::warning('OpenAI API rate limit exceeded');
                    return response()->json([
                        'success' => false,
                        'message' => 'OpenAI API rate limit exceeded. Please try again later.',
                        'error_type' => 'RATE_LIMIT_ERROR'
                    ], 429);
                } elseif ($e->getCode() === 400) {
                    \Log::warning('OpenAI API bad request: ' . $e->getMessage());
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid request to OpenAI API. Please check your input data.',
                        'error_type' => 'BAD_REQUEST_ERROR'
                    ], 400);
                } elseif (str_contains(strtolower($e->getMessage()), 'quota') || str_contains(strtolower($e->getMessage()), 'billing')) {
                    \Log::warning('OpenAI API quota exceeded: ' . $e->getMessage());
                    return response()->json([
                        'success' => false,
                        'message' => 'OpenAI API quota exceeded. Please check your OpenAI account billing or contact the administrator.',
                        'error_type' => 'QUOTA_EXCEEDED'
                    ], 402);
                } else {
                    \Log::error('Unhandled OpenAI API error: ' . $e->getMessage());
                    return response()->json([
                        'success' => false,
                        'message' => 'OpenAI API error occurred. Please try again.',
                        'error_type' => 'API_ERROR'
                    ], 500);
                }
            }

            // Handle other general exceptions
            if (str_contains(strtolower($e->getMessage()), 'quota') || str_contains(strtolower($e->getMessage()), 'billing')) {
                \Log::warning('OpenAI API quota exceeded (caught in general exception): ' . $e->getMessage());
                return response()->json([
                    'success' => false,
                    'message' => 'OpenAI API quota exceeded. Please check your OpenAI account billing or contact the administrator.',
                    'error_type' => 'QUOTA_EXCEEDED'
                ], 402);
            }

            \Log::error('General exception in generatePatientSummary: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred. Please try again.',
                'error' => $e->getMessage()
            ], 500);
        } catch (\Exception $e) {
            \Log::error('Error generating patient summary: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to generate patient summary. Please try again.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Build comprehensive prompt for patient summary generation
     */
    private function buildPatientSummaryPrompt($patientData)
    {
        $prompt = "Please analyze the following patient case and provide a comprehensive clinical summary:\n\n";

        $prompt .= "PATIENT INFORMATION:\n";
        $prompt .= "- Name: {$patientData['name']}\n";
        $prompt .= "- Age: {$patientData['age']}\n";
        $prompt .= "- Gender: {$patientData['gender']}\n";
        $prompt .= "- Total Visits: {$patientData['total_visits']}\n\n";

        $prompt .= "VISIT HISTORY (chronological order):\n";

        foreach ($patientData['visits'] as $visit) {
            $prompt .= "\nVisit #{$visit['visit_number']} - {$visit['date']}:\n";
            $prompt .= "Diagnosis/Assessment: {$visit['diagnosis']}\n";
        }

        $prompt .= "\n\nPlease provide a comprehensive clinical summary that includes:\n\n";

        $prompt .= "📋 PATIENT CASE SUMMARY:\n";
        $prompt .= "- Overall health trajectory and progression\n";
        $prompt .= "- Key medical issues identified across visits\n";
        $prompt .= "- Patterns or trends in symptoms and diagnoses\n\n";

        $prompt .= "🔬 KEY MEDICAL ISSUES IDENTIFIED:\n";
        $prompt .= "- Primary conditions and their evolution\n";
        $prompt .= "- Secondary complications or related issues\n";
        $prompt .= "- Risk factors and contributing factors\n\n";

        $prompt .= "📈 IMPORTANT TRENDS IN SYMPTOMS OR TEST RESULTS:\n";
        $prompt .= "- Symptom progression or improvement patterns\n";
        $prompt .= "- Response to treatments or interventions\n";
        $prompt .= "- Changes in clinical status over time\n\n";

        $prompt .= "💊 TREATMENT EFFECTIVENESS BASED ON VISIT PROGRESSION:\n";
        $prompt .= "- Effectiveness of prescribed treatments\n";
        $prompt .= "- Adjustments made to treatment plans\n";
        $prompt .= "- Patient response and outcomes\n\n";

        $prompt .= "🩺 RECOMMENDATIONS FOR FUTURE CARE:\n";
        $prompt .= "- Suggested follow-up schedule\n";
        $prompt .= "- Additional testing or monitoring needed\n";
        $prompt .= "- Preventive measures or lifestyle recommendations\n\n";

        $prompt .= "Please ensure the summary is:\n";
        $prompt .= "- Concise yet comprehensive\n";
        $prompt .= "- Professional and medically accurate\n";
        $prompt .= "- Focused on clinical insights and patterns\n";
        $prompt .= "- Easy to understand for healthcare providers\n";
        $prompt .= "- All section headers must begin with the exact emoji provided (📋, 🔬, 📈, 💊, 🩺)\n\n";

        $prompt .= "Format the response using clear section headers and bullet points for readability.";

        return $prompt;
    }

    /**
     * Format patient summary response using existing AI formatting patterns
     */
    private function formatPatientSummaryResponse($aiResponse)
    {
        if (!$aiResponse) return '';

        // Clean up the response
        $cleanedResponse = trim($aiResponse);

        // Apply the same formatting as other AI responses
        $formattedResponse = $cleanedResponse;

        // Normalize whitespace in section headers before formatting
        $formattedResponse = preg_replace_callback('/^(📋|🔬|📈|💊|🩺)\s*(.*?)\s*:$/mi', function($matches) {
            $emoji = $matches[1];
            $text = preg_replace('/\s+/', ' ', trim($matches[2]));
            return $emoji . ' ' . $text . ':';
        }, $formattedResponse);

        // Convert markdown-style headers to HTML sections
        $formattedResponse = preg_replace('/^📋 PATIENT CASE SUMMARY:$/mi', '<div class="medcura-section patient-summary"><h4 class="section-header">📋 PATIENT CASE SUMMARY</h4><div class="section-content">', $formattedResponse);
        $formattedResponse = preg_replace('/^🔬 KEY MEDICAL ISSUES IDENTIFIED:$/mi', '</div></div><div class="medcura-section differential-diagnoses"><h4 class="section-header">🔬 KEY MEDICAL ISSUES IDENTIFIED</h4><div class="section-content">', $formattedResponse);
        $formattedResponse = preg_replace('/^📈 IMPORTANT TRENDS IN SYMPTOMS OR TEST RESULTS:$/mi', '</div></div><div class="medcura-section recommended-tests"><h4 class="section-header">📈 IMPORTANT TRENDS IN SYMPTOMS OR TEST RESULTS</h4><div class="section-content">', $formattedResponse);
        $formattedResponse = preg_replace('/^💊 TREATMENT EFFECTIVENESS BASED ON VISIT PROGRESSION:$/mi', '</div></div><div class="medcura-section management-plan"><h4 class="section-header">💊 TREATMENT EFFECTIVENESS BASED ON VISIT PROGRESSION</h4><div class="section-content">', $formattedResponse);
        $formattedResponse = preg_replace('/^🩺 RECOMMENDATIONS FOR FUTURE CARE:$/mi', '</div></div><div class="medcura-section warning-signs"><h4 class="section-header">🩺 RECOMMENDATIONS FOR FUTURE CARE</h4><div class="section-content">', $formattedResponse);

        // Close the final section
        $formattedResponse .= '</div></div>';

        // Convert bullet points
        $formattedResponse = preg_replace('/^-\s+/m', '<li class="bullet-item">', $formattedResponse);
        $formattedResponse = preg_replace('/<\/li>\s*\n\s*<li/m', '</li><li', $formattedResponse);

        // Handle paragraphs
        $lines = explode("\n", $formattedResponse);
        $processedLines = [];

        foreach ($lines as $line) {
            $line = trim($line);

            // Skip empty lines
            if (empty($line)) continue;

            // If line doesn't start with HTML tag and isn't a bullet, wrap in paragraph
            if (!preg_match('/^<.*>/', $line) && !preg_match('/^•/', $line)) {
                $line = '<p>' . $line . '</p>';
            }

            $processedLines[] = $line;
        }

        return implode("\n", $processedLines);
    }
}
