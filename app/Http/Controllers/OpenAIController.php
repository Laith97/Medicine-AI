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
        })->except(['getVisitDetails', 'getPatientVisits', 'dashboard']); // Exclude dashboard for patients
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

        // Get Diagnosis records (current system)
        if ($user->isDoctor()) {
            $diagnosisRecords = Diagnosis::with(['patient', 'doctor', 'aiAssistantResults'])
                ->where('doctor_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->get();

            \Log::info('Found ' . $diagnosisRecords->count() . ' Diagnosis records');

            // Transform Diagnosis records
            foreach ($diagnosisRecords as $diagnosis) {
                $patient = $diagnosis->patient;
                if (!$patient) continue;

                $patientData = $diagnosis->patient_data ?? [];

                $allCases->push((object)[
                    'id' => $diagnosis->id,
                    'name' => $patient->name,
                    'age' => $patient->age ?? 'N/A',
                    'gender' => $patient->gender ?? 'N/A',
                    'height' => 'N/A',
                    'weight' => 'N/A',
                    'symptoms' => $patientData['symptoms'] ?? 'N/A',
                    'type' => 'diagnosis',
                    'ai_response' => $diagnosis->diagnosis_text ?? 'No diagnosis available',
                    'created_at' => $diagnosis->created_at,
                    'updated_at' => $diagnosis->updated_at,
                    'visit_number' => 1,
                    'total_visits' => 1,
                    'patient_key' => 'diagnosis_' . $diagnosis->id,
                    'source_model' => 'Diagnosis',
                    'source_id' => $diagnosis->id,
                    'patient_id' => $patient->id,
                    'category' => 'diagnosed',
                ]);
            }
        }

        // Get PatientAnalysis records (legacy format)
        $patientAnalysisRecords = PatientAnalysis::with('user')
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        \Log::info('Found ' . $patientAnalysisRecords->count() . ' PatientAnalysis records');

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
                'category' => 'diagnosed',
            ]);
        }

        // Get completed appointments without diagnosis
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


        // Sort all cases by creation date (newest first)
        $allCases = $allCases->sortByDesc('created_at');

        // Filter out cases without diagnoses (only keep those with actual diagnosis text)
        $allCases = $allCases->filter(function($case) {
            // For Appointment records, only show completed ones with actual responses
            if ($case->source_model === 'Appointment') {
                return $case->ai_response &&
                       $case->ai_response !== 'Appointment completed - pending diagnosis' &&
                       $case->ai_response !== 'Appointment scheduled' &&
                       trim($case->ai_response) !== '';
            }
            // For Diagnosis and PatientAnalysis records, always include them
            // (even if ai_response is "No diagnosis available", they're still valid records)
            return true;
        });

        \Log::info('Total cases after filtering: ' . $allCases->count());

        // Group records by patient for calculating total visits
        $patientGroups = [];

        // Handle Diagnosis records grouping by patient
        $diagnosisRecords = Diagnosis::with(['patient'])
            ->where('doctor_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

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

        // Handle Appointment records grouping by patient
        foreach ($completedAppointmentsWithoutDiagnosis as $record) {
            $patient = $record->patient;
            if ($patient) {
                // Create a unique key for appointment records based on patient
                $patientKey = 'pending_' . $patient->id;

                if (!isset($patientGroups[$patientKey])) {
                    $patientGroups[$patientKey] = [];
                }
                $patientGroups[$patientKey][] = $record;
            }
        }

        // Update total_visits for all records in the unified collection
        foreach ($allCases as $case) {
            if ($case->source_model === 'Diagnosis' && isset($case->patient_id)) {
                $patientKey = 'diagnosis_' . $case->patient_id;
                if (isset($patientGroups[$patientKey])) {
                    $case->total_visits = count($patientGroups[$patientKey]);
                }
            } elseif ($case->source_model === 'Appointment' && isset($case->patient_id)) {
                $patientKey = 'pending_' . $case->patient_id;
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

        // Unified total patients metric (assigned OR have appointments) - consistent
        // with the main dashboard and Patients page cards.
        $totalPatients = $user->getEffectiveDoctorUser()->getDoctorPatientsCount();

        // Paginate patientGroups for professional pagination (15 per page)
        $perPage = 15;
        $currentPage = (int) request()->get('page', 1);
        $totalGroups = count($patientGroups);
        $offset = ($currentPage - 1) * $perPage;
        $paginatedGroups = array_slice($patientGroups, $offset, $perPage, true);
        $patientGroups = new \Illuminate\Pagination\LengthAwarePaginator(
            $paginatedGroups,
            $totalGroups,
            $perPage,
            $currentPage,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        // Handle AJAX requests for dynamic content loading
        if (request()->ajax()) {
            return response()->view('cases', compact('records', 'patientGroups', 'totalPatients'))->header('Content-Type', 'text/html');
        }

        return view('cases', compact('records', 'patientGroups', 'totalPatients'));
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

            // Debug: Log all available patient keys for this user
            $allAnalysisKeys = PatientAnalysis::where('user_id', $user->id)->pluck('patient_key')->filter()->unique();
            $allDataKeys = \App\Models\PatientData::where('user_id', $user->id)->pluck('patient_key')->filter()->unique();
            \Log::info('Available PatientAnalysis keys: ' . $allAnalysisKeys->implode(', '));
            \Log::info('Available PatientData keys: ' . $allDataKeys->implode(', '));

            // Try multiple matching strategies
            $patientAnalysisRecords = collect();
            $patientDataRecords = collect();

            // Strategy 1: Exact patient_key match
            $patientAnalysisRecords = PatientAnalysis::with('user')
                ->where('user_id', $user->id)
                ->where('patient_key', $patientKey)
                ->orderBy('created_at', 'desc')
                ->get();

            $patientDataRecords = \App\Models\PatientData::where('user_id', $user->id)
                ->where('patient_key', $patientKey)
                ->orderBy('created_at', 'desc')
                ->get();

            // Strategy 2: If no exact match and key looks like diagnosis_X, try patient_id match
            if ($patientAnalysisRecords->isEmpty() && $patientDataRecords->isEmpty() && str_starts_with($patientKey, 'diagnosis_')) {
                $patientId = str_replace('diagnosis_', '', $patientKey);
                \Log::info('Trying patient_id match for: ' . $patientId);
                
                // Try to find records by patient name from diagnosis
                if ($user->isDoctor()) {
                    $diagnosis = Diagnosis::where('doctor_id', $user->id)
                        ->where('patient_id', $patientId)
                        ->with('patient')
                        ->first();
                    
                    if ($diagnosis && $diagnosis->patient) {
                        \Log::info('Found diagnosis patient: ' . $diagnosis->patient->name);
                        
                        // Try to find PatientAnalysis by name/age/gender
                        $patientAnalysisRecords = PatientAnalysis::where('user_id', $user->id)
                            ->where('name', $diagnosis->patient->name)
                            ->where('age', $diagnosis->patient->age)
                            ->where('gender', $diagnosis->patient->gender)
                            ->orderBy('created_at', 'desc')
                            ->get();
                            
                        $patientDataRecords = \App\Models\PatientData::where('user_id', $user->id)
                            ->where('name', $diagnosis->patient->name)
                            ->where('age', $diagnosis->patient->age)
                            ->where('gender', $diagnosis->patient->gender)
                            ->orderBy('created_at', 'desc')
                            ->get();
                    }
                }
            }

            \Log::info('Found ' . $patientAnalysisRecords->count() . ' PatientAnalysis records');
            \Log::info('Found ' . $patientDataRecords->count() . ' PatientData records');

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

            // Transform PatientData records
            foreach ($patientDataRecords as $record) {
                $symptomsString = $this->extractPatientDataSymptoms($record);

                $allCases->push((object)[
                    'id' => $record->id,
                    'visit_number' => $record->visit_number ?? 1,
                    'date' => $record->created_at->format('M d, Y'),
                    'symptoms' => $symptomsString,
                    'diagnosis' => $record->ai_response ? substr($record->ai_response, 0, 100) . (strlen($record->ai_response) > 100 ? '...' : '') : 'No diagnosis available',
                    'source_model' => 'PatientData',
                ]);
            }

            foreach ($diagnosisRecords as $record) {
                $symptomsString = $this->extractDiagnosisSymptoms($record);

                $allCases->push((object)[
                    'id' => $record->id,
                    'visit_number' => 1,
                    'date' => $record->created_at->format('M d, Y'),
                    'symptoms' => $symptomsString,
                    'diagnosis' => $record->diagnosis_text,
                    'diagnosis_preview' => $record->diagnosis_text ? substr($record->diagnosis_text, 0, 100) . (strlen($record->diagnosis_text) > 100 ? '...' : '') : 'No diagnosis available',
                    'type' => $record->type ?? 'manual',
                    'source_model' => 'Diagnosis',
                ]);
            }

            // Sort by date (newest first)
            $visits = $allCases->sortByDesc('date')->values();

            // Get medical info from the most recent record found
            $latestAnalysis = $patientAnalysisRecords->first();
            $latestPatientData = $patientDataRecords->first();
            $latestDiagnosis = $diagnosisRecords->first();

            // Merge data from all sources, prioritizing PatientData > PatientAnalysis > Diagnosis
            $patientMedicalInfo = [
                'symptoms' => [],
                'past_medical_history' => [],
                'allergies' => [],
                'past_medications' => []
            ];

            // Get symptoms from the best available source
            if ($latestPatientData && $latestPatientData->symptoms) {
                $symptoms = is_string($latestPatientData->symptoms) ? json_decode($latestPatientData->symptoms, true) : $latestPatientData->symptoms;
                $patientMedicalInfo['symptoms'] = is_array($symptoms) ? $symptoms : [$symptoms];
            } elseif ($latestAnalysis && $latestAnalysis->symptoms) {
                $symptoms = is_string($latestAnalysis->symptoms) ? json_decode($latestAnalysis->symptoms, true) : $latestAnalysis->symptoms;
                $patientMedicalInfo['symptoms'] = is_array($symptoms) ? $symptoms : [$symptoms];
            } elseif ($latestDiagnosis && isset($latestDiagnosis->patient_data['symptoms'])) {
                $symptoms = $latestDiagnosis->patient_data['symptoms'];
                $patientMedicalInfo['symptoms'] = is_array($symptoms) ? $symptoms : [$symptoms];
            }

            // Get past medical history
            if ($latestPatientData && $latestPatientData->past_medical_history) {
                $history = is_string($latestPatientData->past_medical_history) ? json_decode($latestPatientData->past_medical_history, true) : $latestPatientData->past_medical_history;
                $patientMedicalInfo['past_medical_history'] = is_array($history) ? $history : [$history];
            } elseif ($latestAnalysis && $latestAnalysis->past_medical_history) {
                $history = is_string($latestAnalysis->past_medical_history) ? json_decode($latestAnalysis->past_medical_history, true) : $latestAnalysis->past_medical_history;
                $patientMedicalInfo['past_medical_history'] = is_array($history) ? $history : [$history];
            }

            // Get allergies
            if ($latestPatientData && $latestPatientData->allergies) {
                $allergies = is_string($latestPatientData->allergies) ? json_decode($latestPatientData->allergies, true) : $latestPatientData->allergies;
                $patientMedicalInfo['allergies'] = is_array($allergies) ? $allergies : [$allergies];
            } elseif ($latestAnalysis && $latestAnalysis->allergies) {
                $allergies = is_string($latestAnalysis->allergies) ? json_decode($latestAnalysis->allergies, true) : $latestAnalysis->allergies;
                $patientMedicalInfo['allergies'] = is_array($allergies) ? $allergies : [$allergies];
            }

            // Get past medications
            if ($latestPatientData && $latestPatientData->past_medications) {
                $medications = is_string($latestPatientData->past_medications) ? json_decode($latestPatientData->past_medications, true) : $latestPatientData->past_medications;
                $patientMedicalInfo['past_medications'] = is_array($medications) ? $medications : [$medications];
            } elseif ($latestAnalysis && $latestAnalysis->past_medications) {
                $medications = is_string($latestAnalysis->past_medications) ? json_decode($latestAnalysis->past_medications, true) : $latestAnalysis->past_medications;
                $patientMedicalInfo['past_medications'] = is_array($medications) ? $medications : [$medications];
            }

            \Log::info('Final medical info: ' . json_encode($patientMedicalInfo));
            \Log::info('Found ' . $visits->count() . ' visits for patient key: ' . $patientKey);

            return response()->json([
                'success' => true,
                'visits' => $visits,
                'patient_medical_info' => $patientMedicalInfo,
                'debug' => [
                    'patient_key' => $patientKey,
                    'analysis_records' => $patientAnalysisRecords->count(),
                    'data_records' => $patientDataRecords->count(),
                    'diagnosis_records' => $diagnosisRecords->count()
                ]
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
     * Get all diagnoses for a patient (for modal display)
     */
    public function getPatientDiagnoses($patientId)
    {
        try {
            $user = auth()->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authentication required'
                ], 401);
            }

            // Get all diagnoses for this patient
            $diagnoses = Diagnosis::with(['doctor', 'followUps', 'aiAssistantResults'])
                ->where('patient_id', $patientId)
                ->where('doctor_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->get();

            // Get patient info
            $patient = \App\Models\User::find($patientId);

            if (!$patient) {
                return response()->json([
                    'success' => false,
                    'message' => 'Patient not found'
                ], 404);
            }

            // Transform diagnoses data
            $diagnosesData = $diagnoses->map(function($diagnosis) {
                $patientData = is_array($diagnosis->patient_data) ? $diagnosis->patient_data : [];

                return [
                    'id' => $diagnosis->id,
                    'type' => $diagnosis->type,
                    'diagnosis_text' => $diagnosis->diagnosis_text,
                    'patient_data' => $patientData,
                    'symptoms' => $patientData['symptoms'] ?? [],
                    'created_at' => $diagnosis->created_at->format('M d, Y g:i A'),
                    'doctor' => [
                        'name' => $diagnosis->doctor->name ?? 'N/A'
                    ],
                    'follow_ups' => $diagnosis->followUps->map(function($followUp) {
                        return [
                            'id' => $followUp->id,
                            'question' => $followUp->question,
                            'answer' => $followUp->answer,
                            'created_at' => $followUp->created_at->format('M d, Y')
                        ];
                    }),
                    'ai_results' => $diagnosis->aiAssistantResults->map(function($result) {
                        return [
                            'id' => $result->id,
                            'result_type' => $result->result_type,
                            'result_data' => is_array($result->result_data) ? $result->result_data : [],
                            'created_at' => $result->created_at->format('M d, Y')
                        ];
                    })
                ];
            });

            return response()->json([
                'success' => true,
                'patient' => [
                    'id' => $patient->id,
                    'name' => $patient->name,
                    'age' => $patient->age,
                    'gender' => $patient->gender,
                    'email' => $patient->email,
                    'phone' => $patient->phone
                ],
                'diagnoses' => $diagnosesData,
                'total_diagnoses' => $diagnoses->count()
            ]);

        } catch (\Exception $e) {
            \Log::error('Error in getPatientDiagnoses: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving diagnoses',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Helper method to extract symptoms from PatientData record
     */
    private function extractPatientDataSymptoms($record)
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

        // Patients see a different dashboard
        if ($user->isPatient()) {
            return $this->patientDashboard($user);
        }

        // Get trial/subscription info
        $trialInfo = [
            'is_in_trial' => $user->isInTrialPeriod(),
            'trial_status' => $user->getTrialStatus(),
            'has_active_subscription' => $user->hasActiveSubscription(),
            'trial_days_remaining' => $user->getTrialDaysRemaining(),
        ];

        // Get all cases (diagnoses)
        $allCases = collect();

        // Get Diagnosis records
        if ($user->isDoctor()) {
            $diagnosisRecords = Diagnosis::with(['patient', 'doctor'])
                ->where('doctor_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->get();

            foreach ($diagnosisRecords as $record) {
                $patientData = is_array($record->patient_data) ? $record->patient_data : [];
                $patientKey = $record->patient_key;

                if (!$patientKey && $record->patient) {
                    $patientKey = Diagnosis::generatePatientKey(
                        $record->patient->name,
                        $record->patient->age,
                        $record->patient->gender,
                        $record->doctor_id
                    );
                }

                $allCases->push((object)[
                    'id' => $record->id,
                    'name' => $record->patient->name ?? 'Unknown Patient',
                    'age' => $patientData['patient_age'] ?? $record->patient->age ?? 'N/A',
                    'gender' => $patientData['patient_gender'] ?? $record->patient->gender ?? 'N/A',
                    'symptoms' => $patientData['symptoms'] ?? 'N/A',
                    'diagnosis_text' => $record->diagnosis_text ?? 'No diagnosis',
                    'created_at' => $record->created_at,
                    'patient_key' => $patientKey,
                    'source_model' => 'Diagnosis',
                    'patient_id' => $record->patient_id,
                ]);
            }
        }

        // Get PatientAnalysis records (legacy)
        $patientAnalysisRecords = PatientAnalysis::with('user')
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        foreach ($patientAnalysisRecords as $record) {
            $symptomsString = '';
            if ($record->symptoms) {
                if (is_string($record->symptoms)) {
                    $decoded = json_decode($record->symptoms, true);
                    $symptomsString = json_last_error() === JSON_ERROR_NONE && is_array($decoded)
                        ? implode(', ', $decoded)
                        : $record->symptoms;
                } elseif (is_array($record->symptoms)) {
                    $symptomsString = implode(', ', $record->symptoms);
                }
            }

            $allCases->push((object)[
                'id' => $record->id,
                'name' => $record->name,
                'age' => $record->age,
                'gender' => $record->gender,
                'symptoms' => $symptomsString,
                'diagnosis_text' => $record->ai_response ?? 'No diagnosis',
                'created_at' => $record->created_at,
                'patient_key' => $record->patient_key,
                'source_model' => 'PatientAnalysis',
                'patient_id' => null,
            ]);
        }

        $records = $allCases->sortByDesc('created_at')->values();

        // Doctor-specific metrics
        $doctorMetrics = null;
        $effectiveDoctor = $user->getEffectiveDoctor();
        if ($user->isDoctor() && $effectiveDoctor) {
            $doctorId = $effectiveDoctor->id;

            // Today's appointments
            $todayAppointments = Appointment::with(['patient'])
                ->where('doctor_id', $doctorId)
                ->whereDate('appointment_date', today())
                ->orderBy('appointment_date')
                ->get();

            // Pending appointments count
            $pendingCount = Appointment::where('doctor_id', $doctorId)
                ->where('status', 'pending')
                ->count();

            // This week's appointments
            $weekAppointments = Appointment::where('doctor_id', $doctorId)
                ->whereBetween('appointment_date', [now()->startOfWeek(), now()->endOfWeek()])
                ->count();

            // This month's completed appointments
            $monthCompleted = Appointment::where('doctor_id', $doctorId)
                ->whereMonth('appointment_date', now()->month)
                ->whereYear('appointment_date', now()->year)
                ->where('status', 'completed')
                ->count();

            // Revenue this month
            $monthRevenue = Appointment::where('doctor_id', $doctorId)
                ->whereMonth('appointment_date', now()->month)
                ->whereYear('appointment_date', now()->year)
                ->where('status', 'completed')
                ->sum('fee');

            // Total patients (unified: assigned OR have appointments with this doctor)
            // Consistent with the Patients page and Cases overview metrics.
            $effectiveDoctorUser = $user->getEffectiveDoctorUser();
            $totalPatients = $effectiveDoctorUser->getDoctorPatientsCount();

            // New patients this month (first contact with the doctor this month)
            $newPatientsThisMonth = 0;
            $patientIds = $effectiveDoctorUser->doctorPatientsQuery()->pluck('users.id')->all();
            if (!empty($patientIds)) {
                $newPatientIds = collect()
                    ->merge(Appointment::whereIn('patient_id', $patientIds)
                        ->where('doctor_id', $doctorId)
                        ->whereMonth('appointment_date', now()->month)
                        ->whereYear('appointment_date', now()->year)
                        ->pluck('patient_id'))
                    ->merge(Diagnosis::whereIn('patient_id', $patientIds)
                        ->where('doctor_id', $user->id)
                        ->whereMonth('created_at', now()->month)
                        ->whereYear('created_at', now()->year)
                        ->pluck('patient_id'))
                    ->merge(User::whereIn('id', $patientIds)
                        ->where('primary_doctor_id', $effectiveDoctorUser->id)
                        ->whereMonth('created_at', now()->month)
                        ->whereYear('created_at', now()->year)
                        ->pluck('id'));

                $newPatientsThisMonth = $newPatientIds->unique()->count();
            }

            // Average rating
            $avgRating = $effectiveDoctor->reviews()->avg('rating') ?? 0;
            $totalReviews = $effectiveDoctor->reviews()->count();

            // Recent reviews
            $recentReviews = Review::with(['patient', 'appointment'])
                ->where('doctor_id', $doctorId)
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();

            // Upcoming appointments (next 7 days)
            $upcomingAppointments = Appointment::with(['patient'])
                ->where('doctor_id', $doctorId)
                ->whereDate('appointment_date', '>=', today())
                ->whereDate('appointment_date', '<=', now()->addDays(7))
                ->whereIn('status', ['pending', 'confirmed'])
                ->orderBy('appointment_date')
                ->limit(10)
                ->get();

            // Pending diagnoses (completed appointments without diagnosis)
            $completedWithoutDiagnosis = Appointment::where('doctor_id', $doctorId)
                ->where('status', 'completed')
                ->whereDoesntHave('diagnosis')
                ->count();

            // Recent diagnoses
            $recentDiagnoses = Diagnosis::with(['patient'])
                ->where('doctor_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();

            // Chart 1: Appointments trend (last 14 days)
            $appointmentsTrendLabels = [];
            $appointmentsTrendData = [];
            for ($i = 13; $i >= 0; $i--) {
                $date = now()->subDays($i);
                $appointmentsTrendLabels[] = $date->format('M d');
                $appointmentsTrendData[] = Appointment::where('doctor_id', $doctorId)
                    ->whereDate('appointment_date', $date)
                    ->count();
            }

            // Chart 2: Appointment status breakdown (all time)
            $statusCounts = Appointment::where('doctor_id', $doctorId)
                ->selectRaw('status, count(*) as count')
                ->groupBy('status')
                ->pluck('count', 'status')
                ->toArray();

            $statusBreakdown = [
                'pending' => $statusCounts['pending'] ?? 0,
                'confirmed' => $statusCounts['confirmed'] ?? 0,
                'completed' => $statusCounts['completed'] ?? 0,
                'cancelled' => $statusCounts['cancelled'] ?? 0,
                'no_show' => $statusCounts['no_show'] ?? 0,
            ];

            // Chart 3: Revenue by month (last 6 months)
            $revenueLabels = [];
            $revenueData = [];
            for ($i = 5; $i >= 0; $i--) {
                $month = now()->subMonths($i);
                $revenueLabels[] = $month->format('M');
                $revenueData[] = Appointment::where('doctor_id', $doctorId)
                    ->whereMonth('appointment_date', $month->month)
                    ->whereYear('appointment_date', $month->year)
                    ->where('status', 'completed')
                    ->sum('fee');
            }

            // Chart 4: Diagnoses trend (last 14 days)
            $diagnosisTrendLabels = [];
            $diagnosisTrendData = [];
            for ($i = 13; $i >= 0; $i--) {
                $date = now()->subDays($i);
                $diagnosisTrendLabels[] = $date->format('M d');
                $diagnosisTrendData[] = Diagnosis::where('doctor_id', $user->id)
                    ->whereDate('created_at', $date)
                    ->count();
            }

            $doctorMetrics = [
                'today_appointments' => $todayAppointments->count(),
                'today_appointments_list' => $todayAppointments,
                'pending_count' => $pendingCount,
                'week_appointments' => $weekAppointments,
                'month_completed' => $monthCompleted,
                'month_revenue' => $monthRevenue,
                'total_patients' => $totalPatients,
                'new_patients_this_month' => $newPatientsThisMonth,
                'avg_rating' => round($avgRating, 1),
                'total_reviews' => $totalReviews,
                'recent_reviews' => $recentReviews,
                'upcoming_appointments' => $upcomingAppointments,
                'completed_without_diagnosis' => $completedWithoutDiagnosis,
                'recent_diagnoses' => $recentDiagnoses,
                // Chart data
                'appointments_trend_labels' => $appointmentsTrendLabels,
                'appointments_trend_data' => $appointmentsTrendData,
                'status_breakdown' => $statusBreakdown,
                'revenue_labels' => $revenueLabels,
                'revenue_data' => $revenueData,
                'diagnosis_trend_labels' => $diagnosisTrendLabels,
                'diagnosis_trend_data' => $diagnosisTrendData,
            ];
        }

        return view('dashboard', compact(
            'trialInfo',
            'records',
            'doctorMetrics'
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
    
    /**
     * Patient-specific dashboard
     */
    private function patientDashboard($user)
    {
        // Get patient's appointments
        $appointments = Appointment::with(['doctor.user'])
            ->where('patient_id', $user->id)
            ->orderBy('appointment_date', 'desc')
            ->get();
            
        // Get patient's diagnoses
        $diagnoses = Diagnosis::with(['doctor.user'])
            ->where('patient_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();
            
        // Get patient's reviews
        $reviews = Review::with(['doctor.user'])
            ->where('patient_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();
            
        // Patient statistics
        $stats = [
            'total_appointments' => $appointments->count(),
            'upcoming_appointments' => $appointments->where('status', 'confirmed')
                ->where('appointment_date', '>', now())->count(),
            'completed_appointments' => $appointments->where('status', 'completed')->count(),
            'total_diagnoses' => $diagnoses->count(),
        ];
        
        return view('patient-dashboard', compact(
            'appointments',
            'diagnoses',
            'reviews',
            'stats'
        ));
    }
}
