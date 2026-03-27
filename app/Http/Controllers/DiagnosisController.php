<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Appointment;
use App\Models\Diagnosis;
use App\Models\DiagnosisFollowUp;
use App\Models\User;
use App\Models\Review;
use App\Services\SmsService;
use App\Mail\PatientAccountCreated;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use OpenAI\Laravel\Facades\OpenAI;

class DiagnosisController extends Controller
{
    protected $smsService;

    public function __construct(SmsService $smsService)
    {
        $this->smsService = $smsService;
    }

    /**
     * Display diagnosis form for doctors
     */
    public function create()
    {
        /** @var User $user */
        $user = Auth::user();
        if (!$user->isDoctor()) {
            abort(403, 'Access denied. Doctor access required.');
        }

        // Get doctor's assigned patients (patients where primary_doctor_id matches)
        $assignedPatients = $user->assignedPatients()
            ->select('id', 'name', 'email', 'phone', 'age', 'gender')
            ->get();

        // Get patients who have confirmed or completed appointments with this doctor
        // but are not already in the assigned patients list
        $appointmentPatients = User::where('role', 'patient')
            ->whereHas('appointments', function($query) use ($user) {
                $query->where('doctor_id', $user->id)
                      ->whereIn('status', ['confirmed', 'completed']);
            })
            ->whereNotIn('id', $assignedPatients->pluck('id'))
            ->select('id', 'name', 'email', 'phone', 'age', 'gender')
            ->get();

        // Merge the two collections
        $patients = $assignedPatients->merge($appointmentPatients)->unique('id')->sortBy('name')->values();

        // Load guest patients from confirmed or completed appointments
        $guestAppointments = \App\Models\Appointment::where('doctor_id', $user->id)
            ->whereIn('status', ['confirmed', 'completed'])
            ->whereNotNull('guest_name')
            ->whereNotNull('guest_email')
            ->select('guest_name', 'guest_email', 'guest_date_of_birth', 'guest_gender', 'guest_phone', 'appointment_date')
            ->orderBy('appointment_date', 'desc')
            ->get();

        // Convert guest appointments to patient-like objects
        $guestPatients = $guestAppointments->map(function($appointment) {
            // Calculate age from date of birth if available
            $age = null;
            if ($appointment->guest_date_of_birth) {
                $age = $appointment->guest_date_of_birth->age;
            }

            return (object)[
                'id' => 'guest_' . sha1($appointment->guest_email . $appointment->guest_name . $appointment->id), // Create unique ID for guest
                'name' => $appointment->guest_name,
                'email' => $appointment->guest_email,
                'age' => $age,
                'gender' => $appointment->guest_gender,
                'phone' => $appointment->guest_phone,
                'is_guest' => true,
                'last_appointment' => $appointment->appointment_date
            ];
        });

        // Merge registered patients with guest patients, removing duplicates by email
        $allPatients = $patients->concat($guestPatients)->unique('email')->values();

        return view('diagnosis.create', compact('allPatients'));
    }

    /**
     * Store a new manual diagnosis
     */
    public function store(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();
        if (!$user->isDoctor()) {
            abort(403, 'Access denied. Doctor access required.');
        }

        $validator = Validator::make($request->all(), [
            'existing_patient' => 'nullable|exists:users,id',
            'patient_name' => 'required_without:existing_patient|string|max:255',
            'patient_email' => 'required_without:existing_patient|email|max:255',
            'patient_phone' => 'nullable|string|max:20',
            'patient_gender' => 'required_without:existing_patient|in:male,female,other',
            'diagnosis_text' => 'nullable|string',
            'voice_files' => 'nullable|array',
            'voice_files.*' => 'file|mimetypes:audio/mpeg,audio/mp3,audio/wav,audio/wave,audio/x-wav,audio/m4a,audio/mp4,audio/x-m4a,audio/aac,audio/ogg,audio/webm,application/ogg,video/mp4|max:10240', // 10MB max each
            'patient_data' => 'nullable|array',
        ]);

        // Optional: Log voice files details for debugging (can be removed in production)
        if ($request->hasFile('voice_files')) {
            foreach ($request->file('voice_files') as $index => $voiceFileDebug) {
                Log::debug('Voice file received', [
                    'index' => $index,
                    'original_name' => $voiceFileDebug->getClientOriginalName(),
                    'mime_type' => $voiceFileDebug->getMimeType(),
                    'extension' => $voiceFileDebug->getClientOriginalExtension(),
                    'size' => $voiceFileDebug->getSize()
                ]);
            }
        }

        // Custom validation to ensure at least one input method is provided
        if (empty(trim($request->input('diagnosis_text', ''))) && !$request->hasFile('voice_files')) {
            $validator->after(function ($validator) {
                $validator->errors()->add('input_method', 'Please provide either diagnosis text or voice recording.');
            });
        }

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            // Handle voice input if provided
            $voiceTranscripts = [];
            $voiceFilePaths = [];
            $diagnosisText = $request->diagnosis_text;
            $transcriptionFailed = false;
            $transcriptionFailedCount = 0;

            if ($request->hasFile('voice_files')) {
                $voiceFiles = $request->file('voice_files');

                foreach ($voiceFiles as $index => $voiceFile) {
                    // Store the voice file
                    $voiceFilePath = $voiceFile->store('diagnosis_voices', 'local');
                    $voiceFilePaths[] = $voiceFilePath;

                    // Transcribe voice to text using OpenAI Whisper
                    $voiceTranscript = $this->transcribeVoice($voiceFile);
                    $voiceTranscripts[] = $voiceTranscript;

                    // Check if transcription failed
                    if (strpos($voiceTranscript, 'Voice transcription') !== false ||
                        strpos($voiceTranscript, 'audio file format') !== false ||
                        strpos($voiceTranscript, 'temporarily unavailable') !== false) {
                        $transcriptionFailed = true;
                        $transcriptionFailedCount++;
                        Log::warning('Voice transcription failed during diagnosis creation', [
                            'file_index' => $index,
                            'file_name' => $voiceFile->getClientOriginalName(),
                            'transcript_result' => $voiceTranscript
                        ]);
                    }
                }

                // If no manual text provided and at least one transcription succeeded, combine transcripts
                if (empty($diagnosisText) && count($voiceTranscripts) > $transcriptionFailedCount) {
                    $successfulTranscripts = array_filter($voiceTranscripts, function($transcript) {
                        return strpos($transcript, 'Voice transcription') === false &&
                               strpos($transcript, 'audio file format') === false &&
                               strpos($transcript, 'temporarily unavailable') === false;
                    });
                    $diagnosisText = implode("\n\n", $successfulTranscripts);
                } elseif (empty($diagnosisText) && $transcriptionFailedCount > 0) {
                    // If no text and all transcriptions failed, use a placeholder
                    $diagnosisText = '[Voice recordings uploaded - transcription failed. Please review the audio files and add text manually if needed.]';
                }
            }

            // Get or create patient
            if ($request->existing_patient) {
                // Check if this is a guest patient (starts with 'guest_')
                if (str_starts_with($request->existing_patient, 'guest_')) {
                    // This is a guest patient - create a new patient account for them
                    $guestEmail = $request->input('patient_email');
                    $guestName = $request->input('patient_name');

                    // Check if a patient with this email already exists
                    $existingPatient = User::where('email', $guestEmail)->where('role', 'patient')->first();

                    if ($existingPatient) {
                        // Use existing patient if found
                        $patient = $existingPatient;
                        $isNewPatient = false;
                    } else {
                        // Create new patient account for the guest
                        $tempPassword = SmsService::generateTempPassword();

                        $patient = User::create([
                            'name' => $guestName,
                            'email' => $guestEmail,
                            'phone' => $request->input('patient_phone'),
                            'age' => $request->input('patient_age'),
                            'gender' => $request->input('patient_gender'),
                            'role' => 'patient',
                            'primary_doctor_id' => Auth::id(),
                            'password' => Hash::make($tempPassword),
                        ]);

                        $isNewPatient = true;

                        // Send welcome email to the newly created patient
                        try {
                            // Create a temporary diagnosis object for the email
                            $tempDiagnosis = (object)['id' => null];
                            Mail::to($patient->email)->send(
                                new PatientAccountCreated($patient, Auth::user(), $tempDiagnosis, $tempPassword)
                            );
                        } catch (\Exception $e) {
                            Log::warning('Failed to send welcome email to converted guest patient: ' . $e->getMessage());
                        }
                    }
                } else {
                    // Use existing registered patient
                    // First try to find in assigned patients
                    $patient = $user->assignedPatients()->find($request->existing_patient);

                    if (!$patient) {
                        // Check if patient has confirmed or completed appointments with this doctor
                        $patient = User::where('role', 'patient')
                            ->where('id', $request->existing_patient)
                            ->whereHas('appointments', function($query) use ($user) {
                                $query->where('doctor_id', $user->id)
                                      ->whereIn('status', ['confirmed', 'completed']);
                            })
                            ->first();
                    }

                    if (!$patient) {
                        abort(404, 'Patient not found or you do not have access to this patient.');
                    }

                    $isNewPatient = false;
                }
            } else {
                // Create new patient
                $patient = $this->findOrCreatePatient($request);
                $isNewPatient = $patient->wasRecentlyCreated;
            }

            // Create diagnosis
            $diagnosis = Diagnosis::create([
                'doctor_id' => Auth::id(),
                'patient_id' => $patient->id,
                'type' => 'manual',
                'diagnosis_text' => $diagnosisText,
                'voice_transcripts' => $voiceTranscripts,
                'voice_files' => $voiceFilePaths,
                'patient_data' => $request->patient_data,
            ]);

            // Log diagnosis creation
            \App\Services\AuditLoggingService::logDiagnosisCreated(
                Auth::id(),
                $patient->id,
                $diagnosis->id
            );

            // Send notifications if new patient
            if ($isNewPatient) {
                $tempPassword = SmsService::generateTempPassword();
                info('Creating new patient account', [
                    'email' => $patient->email,
                    'name' => $patient->name,
                    'password' => $tempPassword,
                ]);
                $patient->update(['password' => Hash::make($tempPassword)]);

                // Send email notification
                Mail::to($patient->email)->send(
                    new PatientAccountCreated($patient, Auth::user(), $diagnosis, $tempPassword)
                );

                // Send SMS notification if phone provided
                if ($patient->phone) {
                    $smsMessage = "Hello {$patient->name}, Dr. " . Auth::user()->name . " has created your medical account. Check your email for login details. Diagnosis ID: {$diagnosis->id}";
                    $result = $this->smsService->send($patient->phone, $smsMessage);

                    if (!$result['success']) {
                        Log::warning('Failed to send SMS notification to patient', [
                            'patient_id' => $patient->id,
                            'phone' => $patient->phone,
                            'error' => $result['message']
                        ]);
                    }
                }

                $diagnosis->update(['patient_notified' => true]);
            }

            // Send diagnosis notifications
            $this->sendDiagnosisNotifications($diagnosis, $isNewPatient);
            $successMessage = 'Diagnosis created successfully!';

            if ($isNewPatient) {
                $successMessage .= ' Patient has been notified via email and SMS.';
            }

            if ($transcriptionFailed) {
                $fileWord = count($voiceFilePaths) > 1 ? 'files' : 'file';
                $successMessage .= " Note: Voice transcription failed for {$transcriptionFailedCount} {$fileWord} - please review the audio files and add text manually if needed.";
            }

            return redirect()->route('diagnosis.show', $diagnosis)
                ->with('success', $successMessage);

        } catch (\Exception $e) {
            Log::error('Diagnosis creation failed: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'request_data' => $request->except(['voice_files']),
                'has_voice_files' => $request->hasFile('voice_files'),
                'voice_files_count' => $request->hasFile('voice_files') ? count($request->file('voice_files')) : 0,
                'voice_files_info' => $request->hasFile('voice_files') ? collect($request->file('voice_files'))->map(function($file) {
                    return [
                        'name' => $file->getClientOriginalName(),
                        'size' => $file->getSize(),
                        'type' => $file->getMimeType()
                    ];
                }) : null,
                'error_trace' => $e->getTraceAsString()
            ]);
            return back()->with('error', 'Failed to create diagnosis: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Display diagnosis for doctors
     */
    public function show(Diagnosis $diagnosis)
    {
        // Check if user can view this diagnosis
        /** @var User $user */
        $user = Auth::user();
        if ($user->isDoctor() && $diagnosis->doctor_id !== $user->id) {
            abort(403, 'Access denied.');
        }

        if ($user->isPatient() && $diagnosis->patient_id !== $user->id) {
            abort(403, 'Access denied.');
        }

        // Log doctor access to patient diagnosis
        if ($user->isDoctor() && $diagnosis->patient_id) {
            \App\Services\AuditLoggingService::logDoctorAccessPatient(
                Auth::id(),
                $diagnosis->patient_id,
                ['diagnosis_id' => $diagnosis->id]
            );
        }

        // Mark as viewed if patient is viewing
        if ($user->isPatient()) {
            $diagnosis->markAsViewed();
        }

        $diagnosis->load(['doctor', 'patient', 'followUps', 'aiAssistantResults']);

        return view('diagnosis.show', compact('diagnosis'));
    }

    /**
     * Display patient's diagnosis view
     */
    public function patientView(Diagnosis $diagnosis)
    {
        /** @var User $user */
        $user = Auth::user();
        if (!$user->isPatient() || $diagnosis->patient_id !== $user->id) {
            abort(403, 'Access denied.');
        }

        $diagnosis->markAsViewed();
        $diagnosis->load(['doctor', 'followUps', 'aiAssistantResults']);

        return view('diagnosis.patient-view', compact('diagnosis'));
    }

    /**
     * Store follow-up question from patient or doctor
     */
    public function storeFollowUp(Request $request, Diagnosis $diagnosis)
    {
        /** @var User $user */
        $user = Auth::user();

        // Check if user can submit follow-up for this diagnosis
        $isAuthorized = ($user->isPatient() && $diagnosis->patient_id === $user->id) ||
                       ($user->isDoctor() && $diagnosis->doctor_id === $user->id);

        if (!$isAuthorized) {
            abort(403, 'Access denied.');
        }

        if (!$diagnosis->canAskFollowUp()) {
            return response()->json([
                'error' => 'You have reached the maximum number of follow-up questions (5) for this diagnosis.'
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'question' => 'required|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 400);
        }

        try {
            // Get AI response for the follow-up question
            $aiResponse = $this->getAiFollowUpResponse($diagnosis, $request->question);

            // Atomically increment follow-up count if under limit
            // This prevents race conditions where two concurrent requests could both pass the check
            if (!$diagnosis->incrementFollowUpCount()) {
                return response()->json([
                    'error' => 'You have reached the maximum number of follow-up questions (5) for this diagnosis.'
                ], 400);
            }

            // Create follow-up record
            $followUp = DiagnosisFollowUp::create([
                'diagnosis_id' => $diagnosis->id,
                'patient_id' => $diagnosis->patient_id,
                'question' => $request->question,
                'ai_response' => $aiResponse['response'],
                'usage_data' => $aiResponse['usage_data'],
            ]);

            // Send follow-up notifications
            $this->sendFollowUpNotifications($diagnosis, $followUp);

            return response()->json([
                'success' => true,
                'followUp' => [
                    'id' => $followUp->id,
                    'question' => $followUp->question,
                    'ai_response' => $followUp->ai_response,
                    'created_at' => $followUp->created_at->format('M j, Y \a\t g:i A'),
                ],
                'remaining_questions' => 5 - $diagnosis->fresh()->follow_up_count,
            ]);

        } catch (\Exception $e) {
            Log::error('Follow-up question failed: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to process your question. Please try again.'], 500);
        }
    }

    /**
     * Store patient review for diagnosis
     */
    public function storeReview(Request $request, Diagnosis $diagnosis)
    {
        /** @var User $user */
        $user = Auth::user();
        if (!$user->isPatient() || $diagnosis->patient_id !== $user->id) {
            abort(403, 'Access denied.');
        }

        if ($diagnosis->patient_reviewed) {
            return back()->with('error', 'You have already reviewed this diagnosis.');
        }

        $validator = Validator::make($request->all(), [
            'rating' => 'required|integer|min:1|max:5',
            'review_text' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator);
        }

        try {
            // Get the doctor's ID from the diagnosis doctor user
            $doctorUser = $diagnosis->doctor;
            $doctorId = $doctorUser->doctor ? $doctorUser->doctor->id : null;

            if (!$doctorId) {
                return back()->with('error', 'Unable to find doctor information for this review.');
            }

            // Create review
            $review = Review::create([
                'doctor_id' => $doctorId,
                'patient_id' => Auth::id(),
                'rating' => $request->rating,
                'comment' => $request->review_text,
                'is_approved' => true,
                'source' => 'medcura',
            ]);

            // Mark diagnosis as reviewed
            $diagnosis->markAsReviewed();

            // Send review notifications
            $this->sendReviewNotifications($review);

            return back()->with('success', 'Thank you for your review!');

        } catch (\Exception $e) {
            Log::error('Review creation failed: ' . $e->getMessage());
            return back()->with('error', 'Failed to submit review. Please try again.');
        }
    }

    /**
     * List diagnoses for doctors
     */
    public function index()
    {
        /** @var User $user */
        $user = Auth::user();
        if (!$user->isDoctor()) {
            abort(403, 'Access denied. Doctor access required.');
        }

        $diagnoses = $user->doctorDiagnoses()
            ->with(['patient'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('diagnosis.index', compact('diagnoses'));
    }

    /**
     * List patient's diagnoses
     */
    public function patientIndex()
    {
        /** @var User $user */
        $user = Auth::user();
        if (!$user->isPatient()) {
            abort(403, 'Access denied. Patient access required.');
        }

        $diagnoses = $user->patientDiagnoses()
            ->with(['doctor', 'aiAssistantResults'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('diagnosis.patient-index', compact('diagnoses'));
    }

    /**
     * Find or create patient
     */
    private function findOrCreatePatient(Request $request)
    {
        // Validate required fields
        $request->validate([
            'patient_name' => 'required|string|max:255',
            'patient_email' => 'required|email|max:255',
            'patient_phone' => 'nullable|string|max:20',
            'patient_age' => 'nullable|integer|min:0|max:150',
            'patient_gender' => 'required|in:male,female,other',
        ]);

        // First check if patient exists and is already assigned to this doctor
        /** @var User $user */
        $user = Auth::user();
        $patient = $user->assignedPatients()
            ->where('email', $request->patient_email)
            ->first();

        if (!$patient) {
            // Check if patient exists but is assigned to another doctor
            $existingPatient = User::where('email', $request->patient_email)
                ->where('role', 'patient')
                ->first();

            if ($existingPatient) {
                // Patient exists but belongs to another doctor - not allowed
                throw new \Exception('This patient is already registered with another doctor. Please use a different email address.');
            }

            // Create new patient and assign to current doctor
            $tempPass = Hash::make('temporary');

            $patient = User::create([
                'name' => $request->patient_name,
                'email' => $request->patient_email,
                'phone' => $request->patient_phone,
                'age' => $request->patient_age,
                'gender' => $request->patient_gender,
                'role' => 'patient',
                'primary_doctor_id' => Auth::id(), // Assign to current doctor
                'password' => $tempPass, // Will be updated with real temp password
            ]);
        }

        return $patient;
    }

    /**
     * Transcribe voice file using OpenAI Whisper
     */
    private function transcribeVoice($voiceFile)
    {
        try {
            // Log file details for debugging
            Log::info('Attempting voice transcription', [
                'original_name' => $voiceFile->getClientOriginalName(),
                'mime_type' => $voiceFile->getMimeType(),
                'size' => $voiceFile->getSize(),
                'extension' => $voiceFile->getClientOriginalExtension(),
                'path' => $voiceFile->getPathname()
            ]);

            // Check file size (max 25MB for Whisper)
            if ($voiceFile->getSize() > 25 * 1024 * 1024) {
                throw new \Exception('File too large. Maximum size is 25MB.');
            }

            // Check if file exists and is readable
            if (!file_exists($voiceFile->getPathname()) || !is_readable($voiceFile->getPathname())) {
                throw new \Exception('Voice file is not accessible.');
            }

            // Validate file format - OpenAI Whisper supported formats
            $supportedMimes = [
                'audio/mp3', 'audio/mpeg', 'audio/wav', 'audio/wave',
                'audio/m4a', 'audio/mp4', 'audio/ogg',
                'audio/webm', 'application/ogg', 'video/mp4'
            ];

            $mimeType = $voiceFile->getMimeType();
            $extension = strtolower($voiceFile->getClientOriginalExtension());

            // Additional check for common extensions even if MIME type detection fails
            $supportedExtensions = ['mp3', 'wav', 'm4a', 'mp4', 'ogg', 'webm', 'flac'];

            if (!in_array($mimeType, $supportedMimes) && !in_array($extension, $supportedExtensions)) {
                throw new \Exception('Invalid file format. Supported formats: MP3, WAV, M4A, OGG, WebM');
            }

            // Handle video/mp4 MIME type files (browser-recorded M4A files)
            $fileToTranscribe = $voiceFile->getPathname();
            $tempFile = null;

            if ($mimeType === 'video/mp4' && $extension === 'm4a') {
                // Create a temporary copy with .m4a extension to help OpenAI recognize it as audio
                $tempFile = sys_get_temp_dir() . '/temp_audio_' . uniqid() . '.m4a';
                copy($voiceFile->getPathname(), $tempFile);
                $fileToTranscribe = $tempFile;

                Log::info('Created temporary file for video/mp4 transcription', [
                    'original_file' => $voiceFile->getPathname(),
                    'temp_file' => $tempFile,
                    'mime_type' => $mimeType
                ]);
            }

            // For WebM files that might have issues, let's try without language specification first
            $transcribeParams = [
                'model' => 'whisper-1',
                'file' => fopen($fileToTranscribe, 'r'),
                'response_format' => 'text',
            ];

            // Only add language for non-WebM files to avoid potential issues
            if (!strpos($mimeType, 'webm') && $extension !== 'webm') {
                $transcribeParams['language'] = 'en';
            }

            $response = OpenAI::audio()->transcribe($transcribeParams);

            // Clean up temporary file if created
            if ($tempFile && file_exists($tempFile)) {
                unlink($tempFile);
            }

            // Extract the actual transcribed text from the response object
            $transcriptText = $response->text ?? (string)$response ?? '';

            Log::info('Voice transcription successful', [
                'transcript_length' => strlen($transcriptText),
                'file_name' => $voiceFile->getClientOriginalName(),
                'response_type' => gettype($response),
                'response_class' => is_object($response) ? get_class($response) : 'not_object'
            ]);

            return $transcriptText;
        } catch (\Exception $e) {
            // Clean up temporary file if it was created
            if (isset($tempFile) && $tempFile && file_exists($tempFile)) {
                unlink($tempFile);
            }

            Log::error('Voice transcription failed: ' . $e->getMessage(), [
                'file_name' => $voiceFile->getClientOriginalName() ?? 'unknown',
                'mime_type' => $voiceFile->getMimeType() ?? 'unknown',
                'size' => $voiceFile->getSize() ?? 0,
                'extension' => $voiceFile->getClientOriginalExtension() ?? 'unknown',
                'exact_openai_error' => $e->getMessage(),
                'error_trace' => $e->getTraceAsString(),
                'used_temp_file' => isset($tempFile) && $tempFile ? true : false
            ]);

            // Return a more informative error message based on the error type
            if (strpos($e->getMessage(), 'Invalid file format') !== false || strpos($e->getMessage(), 'Supported formats') !== false) {
                return 'The audio file format is not supported by OpenAI. Please try recording again or upload a different audio file (MP3, WAV work best).';
            } elseif (strpos($e->getMessage(), 'too large') !== false) {
                return 'The audio file is too large. Please record a shorter message (max 25MB).';
            } else {
                // Avoid exposing sensitive error details to the user
                return "Voice transcription temporarily unavailable. Please type your diagnosis manually.";
            }
        }
    }

    /**
     * Get AI response for follow-up question
     */
    private function getAiFollowUpResponse(Diagnosis $diagnosis, string $question)
    {
        try {
            // Build context with original diagnosis and AI analysis
            $context = "Original diagnosis from doctor: " . $diagnosis->diagnosis_text;

            // Include AI assistant results if available
            if ($diagnosis->aiAssistantResults && $diagnosis->aiAssistantResults->count() > 0) {
                $context .= "\n\nAI Medical Analysis:\n";
                foreach ($diagnosis->aiAssistantResults as $index => $result) {
                    $context .= "\nAI Analysis " . ($index + 1) . ":\n" . $result->ai_analysis;
                }
            }

            if ($diagnosis->patient_data) {
                $context .= "\n\nPatient data: " . json_encode($diagnosis->patient_data);
            }

            $prompt = "You are a medical AI assistant helping a patient understand their diagnosis.

            Context: {$context}

            Patient's follow-up question: {$question}

            Please provide a helpful, accurate, and reassuring response based on the medical context provided above. Keep it concise but informative.
            Reference specific details from the diagnosis and AI analysis when relevant to answer the patient's question.
            Always remind the patient to consult with their doctor for serious concerns.";

            $response = OpenAI::chat()->create([
                'model' => 'gpt-4o',
                'messages' => [
                    ['role' => 'system', 'content' => 'You are a helpful medical AI assistant.'],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'max_tokens' => 500,
            ]);

            $aiResponse = $response['choices'][0]['message']['content'] ?? 'I apologize, but I cannot provide a response at this time. Please consult with your doctor.';

            return [
                'response' => $aiResponse,
                'usage_data' => [
                    'tokens_used' => $response['usage']['total_tokens'] ?? 0,
                    'model' => 'gpt-4',
                    'timestamp' => now(),
                ],
            ];

        } catch (\Exception $e) {
            Log::error('AI follow-up response failed: ' . $e->getMessage());
            return [
                'response' => 'I apologize, but I cannot provide a response at this time due to a technical issue. Please consult with your doctor for any concerns.',
                'usage_data' => null,
            ];
        }
    }

    /**
     * Send notifications for diagnosis submission
     */
    private function sendDiagnosisNotifications(Diagnosis $diagnosis, bool $isNewPatient = false)
    {
        try {
            // Send notification to patient about new diagnosis
            if ($diagnosis->patient && $diagnosis->patient->wantsNotification('diagnosis_submitted')) {
                $diagnosis->patient->notifyIfWants(new \App\Notifications\DiagnosisSubmittedNotification($diagnosis));
            }

            // Send notification to doctor about diagnosis submission (if it's a new patient)
            if ($isNewPatient && $diagnosis->doctor && $diagnosis->doctor->user) {
                $doctor = $diagnosis->doctor->user;

                if ($doctor->wantsNotification('diagnosis_submitted')) {
                    $doctor->notifyIfWants(new \App\Notifications\SystemAlertNotification(
                        'New Patient Diagnosis',
                        "New diagnosis submitted for patient {$diagnosis->patient->name}. Diagnosis ID: {$diagnosis->id}",
                        'success',
                        [
                            'link' => route('diagnosis.show', $diagnosis),
                            'link_text' => 'View Diagnosis',
                            'related_type' => 'diagnosis',
                            'related_id' => $diagnosis->id
                        ]
                    ));
                }
            }

        } catch (\Exception $e) {
            // Log notification errors but don't break the diagnosis process
            Log::error('Failed to send diagnosis notifications: ' . $e->getMessage());
        }
    }

    /**
     * Send notifications for follow-up questions
     */
    private function sendFollowUpNotifications(Diagnosis $diagnosis, DiagnosisFollowUp $followUp)
    {
        try {
            // Send notification to doctor about patient follow-up
            if ($diagnosis->doctor && $diagnosis->doctor->user) {
                $doctor = $diagnosis->doctor->user;

                if ($doctor->wantsNotification('diagnosis_submitted')) {
                    $doctor->notifyIfWants(new \App\Notifications\SystemAlertNotification(
                        'Patient Follow-up Question',
                        "Patient {$diagnosis->patient->name} asked a follow-up question for diagnosis #{$diagnosis->id}",
                        'info',
                        [
                            'link' => route('diagnosis.show', $diagnosis),
                            'link_text' => 'View Diagnosis',
                            'related_type' => 'diagnosis',
                            'related_id' => $diagnosis->id
                        ]
                    ));
                }
            }

            // Send notification to patient about AI response (if applicable)
            if ($followUp->ai_response && $diagnosis->patient->wantsNotification('diagnosis_submitted')) {
                $diagnosis->patient->notifyIfWants(new \App\Notifications\SystemAlertNotification(
                    'AI Response to Your Question',
                    "Dr. {$diagnosis->doctor->user->name} has provided an AI response to your follow-up question.",
                    'info',
                    [
                        'link' => route('diagnosis.patient-view', $diagnosis),
                        'link_text' => 'View Response',
                        'related_type' => 'diagnosis',
                        'related_id' => $diagnosis->id
                    ]
                ));
            }

        } catch (\Exception $e) {
            // Log notification errors but don't break the follow-up process
            Log::error('Failed to send follow-up notifications: ' . $e->getMessage());
        }
    }

    /**
     * Send notifications for patient reviews
     */
    private function sendReviewNotifications(Review $review)
    {
        try {
            // Send notification to doctor about new review
            if ($review->doctor && $review->doctor->user) {
                $doctor = $review->doctor->user;

                if ($doctor->wantsNotification('review_submitted')) {
                    $doctor->notifyIfWants(new \App\Notifications\ReviewSubmittedNotification($review));
                }
            }

            // Send notification to admin about new review (for approval)
            $admins = User::where('role', 'admin')->get();
            foreach ($admins as $admin) {
                if ($admin->wantsNotification('review_submitted')) {
                    $admin->notifyIfWants(new \App\Notifications\SystemAlertNotification(
                        'New Review Submitted',
                        "New review submitted by {$review->getPatientDisplayNameAttribute()} for Dr. {$review->doctor->user->name}. Rating: {$review->rating}/5",
                        'info',
                        [
                            'link' => route('admin.reviews.show', $review->id),
                            'link_text' => 'View Review',
                            'related_type' => 'review',
                            'related_id' => $review->id
                        ]
                    ));
                }
            }

        } catch (\Exception $e) {
            // Log notification errors but don't break the review process
            Log::error('Failed to send review notifications: ' . $e->getMessage());
        }
    }

    public function serveVoiceFile(Diagnosis $diagnosis)
    {
        // Check if user has access to this diagnosis
        if (!$this->canAccessDiagnosis($diagnosis)) {
            abort(403, 'Access denied.');
        }

        // Get file index from query parameter (default to 0 for backward compatibility)
        $fileIndex = request('file', 0);

        // Check if diagnosis has voice files
        if (!$diagnosis->voice_files || !is_array($diagnosis->voice_files) || !isset($diagnosis->voice_files[$fileIndex])) {
            abort(404, 'Voice file not found.');
        }

        $voiceFilePath = $diagnosis->voice_files[$fileIndex];

        // Handle empty/corrupted paths from migration
        if (empty(trim($voiceFilePath))) {
            // Try to find a file in the old location based on diagnosis creation time
            $oldFiles = Storage::files('public/audio/voice_transcriptions');
            $diagnosisTime = $diagnosis->created_at->timestamp;

            // Look for files created around the diagnosis time (± 1 hour)
            foreach ($oldFiles as $oldFile) {
                $fileName = basename($oldFile);
                // Extract timestamp from filename (format: session_uuid_timestamp_hash.ext)
                if (preg_match('/_(\d+)_\w+\./', $fileName, $matches)) {
                    $fileTime = (int) $matches[1];
                    if (abs($fileTime - $diagnosisTime) < 3600) { // Within 1 hour
                        $voiceFilePath = $oldFile;
                        break;
                    }
                }
            }

            if (empty($voiceFilePath) || !Storage::exists($voiceFilePath)) {
                abort(404, 'Voice file not found.');
            }
        } else {
            // Check if the file exists in the new location
            if (!Storage::exists($voiceFilePath)) {
                abort(404, 'Voice file not found.');
            }
        }

        // Get the file path and headers
        $filePath = Storage::path($voiceFilePath);
        $mimeType = Storage::mimeType($voiceFilePath);

        // Set appropriate headers for audio streaming
        $headers = [
            'Content-Type' => $mimeType,
            'Accept-Ranges' => 'bytes',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ];

        return response()->file($filePath, $headers);
    }

    /**
     * Create diagnosis from appointment page
     */
    public function createFromAppointment(Request $request, Appointment $appointment)
    {
        /** @var User $user */
        $user = Auth::user();
        if (!$user->isDoctor()) {
            abort(403, 'Access denied. Doctor access required.');
        }

        // Check if the appointment belongs to the doctor
        if ($appointment->doctor_id !== $user->id) {
            abort(403, 'Access denied. You can only create diagnoses for your own appointments.');
        }

        // Check if appointment is completed
        if ($appointment->status !== 'completed') {
            return back()->withErrors(['error' => 'Diagnosis can only be created for completed appointments.']);
        }

        // Prepare validation rules with conditional logic for voice files
        $validationRules = [
            'diagnosis_text' => 'required|string|min:10',
            'patient_data' => 'nullable|array',
            'patient_data.height' => 'nullable|numeric|min:50|max:250',
            'patient_data.weight' => 'nullable|numeric|min:20|max:500',
            'patient_data.blood_pressure' => 'nullable|string|max:20',
            'patient_data.temperature' => 'nullable|numeric|min:30|max:45',
        ];

        // Only add voice file validation if files were actually uploaded
        if ($request->hasFile('voice_files')) {
            $validationRules['voice_files'] = 'nullable|array';
            $validationRules['voice_files.*'] = 'file|mimetypes:audio/mpeg,audio/mp3,audio/wav,audio/wave,audio/x-wav,audio/m4a,audio/mp4,audio/x-m4a,audio/aac,audio/ogg,audio/webm,application/ogg,video/mp4|max:10240'; // 10MB max each
        } else {
            // If no files uploaded, just validate it's an array (which it will be from the form)
            $validationRules['voice_files'] = 'nullable|array';
        }

        $validator = Validator::make($request->all(), $validationRules);

        if ($validator->fails()) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            return back()->withErrors($validator)->withInput();
        }

        try {
            // Handle voice input if provided
            $voiceTranscripts = [];
            $voiceFilePaths = [];
            $diagnosisText = $request->diagnosis_text;

            if ($request->hasFile('voice_files')) {
                $voiceFiles = $request->file('voice_files');

                foreach ($voiceFiles as $index => $voiceFile) {
                    // Store the voice file
                    $voiceFilePath = $voiceFile->store('diagnosis_voices', 'local');
                    $voiceFilePaths[] = $voiceFilePath;

                    // Transcribe voice to text using OpenAI Whisper
                    $voiceTranscript = $this->transcribeVoice($voiceFile);
                    $voiceTranscripts[] = $voiceTranscript;
                }

                // Combine transcripts with manual text if both exist
                if (!empty($voiceTranscripts)) {
                    $diagnosisText .= "\n\nVoice Notes:\n" . implode("\n\n", array_filter($voiceTranscripts));
                }
            }

            // Create diagnosis linked to the appointment's patient
            $diagnosis = Diagnosis::create([
                'doctor_id' => Auth::id(),
                'patient_id' => $appointment->patient_id,
                'type' => 'appointment',
                'diagnosis_text' => $diagnosisText,
                'voice_transcripts' => $voiceTranscripts,
                'voice_files' => $voiceFilePaths,
                'patient_data' => $request->patient_data,
            ]);

            // Link diagnosis to appointment (optional - could add appointment_id to diagnosis model later)
            // For now, we'll use the diagnosis text to maintain the relationship

            // Log diagnosis creation
            \App\Services\AuditLoggingService::logDiagnosisCreated(
                Auth::id(),
                $appointment->patient_id,
                $diagnosis->id,
                ['appointment_id' => $appointment->id]
            );

            // Send diagnosis notifications
            $this->sendDiagnosisNotifications($diagnosis);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Diagnosis created successfully! The page will reload to show the updated information.',
                    'diagnosis_id' => $diagnosis->id,
                    'view_diagnosis_url' => route('diagnosis.show', $diagnosis)
                ]);
            }

            return redirect()->route('doctor.appointments.show', $appointment)
                ->with('success', 'Diagnosis created successfully! <a href="' . route('diagnosis.show', $diagnosis) . '" class="alert-link">View Diagnosis</a>');

        } catch (\Exception $e) {
            Log::error('Diagnosis creation from appointment failed: ' . $e->getMessage(), [
                'appointment_id' => $appointment->id,
                'patient_id' => $appointment->patient_id,
                'doctor_id' => Auth::id(),
                'error_trace' => $e->getTraceAsString()
            ]);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create diagnosis: ' . $e->getMessage()
                ], 500);
            }

            return back()->with('error', 'Failed to create diagnosis: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Check if the current user can access the given diagnosis
     */
    private function canAccessDiagnosis(Diagnosis $diagnosis)
    {
        /** @var User $user */
        $user = Auth::user();

        // Doctors can access their own diagnoses
        if ($user->isDoctor() && $diagnosis->doctor_id == $user->id) {
            return true;
        }

        // Patients can access their own diagnoses
        if ($user->isPatient() && $diagnosis->patient_id == $user->id) {
            return true;
        }

        return false;
    }
}