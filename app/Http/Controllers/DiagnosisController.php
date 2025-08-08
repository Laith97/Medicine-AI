<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Diagnosis;
use App\Models\DiagnosisFollowUp;
use App\Models\User;
use App\Models\Review;
use App\Services\SmsService;
use App\Mail\PatientAccountCreated;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
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
        if (!Auth::user()->isDoctor()) {
            abort(403, 'Access denied. Doctor access required.');
        }

        // Get doctor's assigned patients
        $patients = Auth::user()->assignedPatients()
            ->select('id', 'name', 'email', 'phone', 'age', 'gender')
            ->orderBy('name')
            ->get();

        return view('diagnosis.create', compact('patients'));
    }

    /**
     * Store a new manual diagnosis
     */
    public function store(Request $request)
    {
        if (!Auth::user()->isDoctor()) {
            abort(403, 'Access denied. Doctor access required.');
        }

        $validator = Validator::make($request->all(), [
            'existing_patient' => 'nullable|exists:users,id',
            'patient_name' => 'required_without:existing_patient|string|max:255',
            'patient_email' => 'required_without:existing_patient|email|max:255',
            'patient_phone' => 'nullable|string|max:20',
            'patient_age' => 'required_without:existing_patient|integer|min:1|max:150',
            'patient_gender' => 'required_without:existing_patient|in:male,female,other',
            'diagnosis_text' => 'required_without:voice_file|string',
            'voice_file' => 'required_without:diagnosis_text|file|mimes:mp3,wav,m4a,ogg|max:10240', // 10MB max
            'patient_data' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            // Handle voice input if provided
            $voiceTranscript = null;
            $voiceFilePath = null;
            $diagnosisText = $request->diagnosis_text;

            if ($request->hasFile('voice_file')) {
                $voiceFile = $request->file('voice_file');
                $voiceFilePath = $voiceFile->store('diagnosis_voices', 'private');

                // Transcribe voice to text using OpenAI Whisper
                $voiceTranscript = $this->transcribeVoice($voiceFile);

                // If no manual text provided, use transcript
                if (empty($diagnosisText)) {
                    $diagnosisText = $voiceTranscript;
                }
            }

            // Get or create patient
            if ($request->existing_patient) {
                // Use existing patient
                $patient = Auth::user()->assignedPatients()->findOrFail($request->existing_patient);
                $isNewPatient = false;
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
                'voice_transcript' => $voiceTranscript,
                'voice_file_path' => $voiceFilePath,
                'patient_data' => $request->patient_data,
            ]);

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
                        \Log::warning('Failed to send SMS notification to patient', [
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

            return redirect()->route('diagnosis.show', $diagnosis)
                ->with('success', 'Diagnosis created successfully!' . ($isNewPatient ? ' Patient has been notified via email and SMS.' : ''));

        } catch (\Exception $e) {
            \Log::error('Diagnosis creation failed: ' . $e->getMessage());
            return back()->with('error', 'Failed to create diagnosis. Please try again.')->withInput();
        }
    }

    /**
     * Display diagnosis for doctors
     */
    public function show(Diagnosis $diagnosis)
    {
        // Check if user can view this diagnosis
        if (Auth::user()->isDoctor() && $diagnosis->doctor_id !== Auth::id()) {
            abort(403, 'Access denied.');
        }

        if (Auth::user()->isPatient() && $diagnosis->patient_id !== Auth::id()) {
            abort(403, 'Access denied.');
        }

        // Mark as viewed if patient is viewing
        if (Auth::user()->isPatient()) {
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
        if (!Auth::user()->isPatient() || $diagnosis->patient_id !== Auth::id()) {
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

            // Create follow-up record
            $followUp = DiagnosisFollowUp::create([
                'diagnosis_id' => $diagnosis->id,
                'patient_id' => Auth::id(),
                'question' => $request->question,
                'ai_response' => $aiResponse['response'],
                'usage_data' => $aiResponse['usage_data'],
            ]);

            // Increment follow-up count
            $diagnosis->incrementFollowUpCount();

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
            \Log::error('Follow-up question failed: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to process your question. Please try again.'], 500);
        }
    }

    /**
     * Store patient review for diagnosis
     */
    public function storeReview(Request $request, Diagnosis $diagnosis)
    {
        if (!Auth::user()->isPatient() || $diagnosis->patient_id !== Auth::id()) {
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
            \Log::error('Review creation failed: ' . $e->getMessage());
            return back()->with('error', 'Failed to submit review. Please try again.');
        }
    }

    /**
     * List diagnoses for doctors
     */
    public function index()
    {
        if (!Auth::user()->isDoctor()) {
            abort(403, 'Access denied. Doctor access required.');
        }

        $diagnoses = Auth::user()->doctorDiagnoses()
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
        if (!Auth::user()->isPatient()) {
            abort(403, 'Access denied. Patient access required.');
        }

        $diagnoses = Auth::user()->patientDiagnoses()
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
        // First check if patient exists and is already assigned to this doctor
        $patient = Auth::user()->assignedPatients()
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
            $response = OpenAI::audio()->transcribe([
                'model' => 'whisper-1',
                'file' => fopen($voiceFile->getPathname(), 'r'),
                'response_format' => 'text',
            ]);

            return $response;
        } catch (\Exception $e) {
            \Log::error('Voice transcription failed: ' . $e->getMessage());
            return 'Voice transcription failed. Please provide text diagnosis.';
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
                'model' => 'gpt-4',
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
            \Log::error('AI follow-up response failed: ' . $e->getMessage());
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
            \Log::error('Failed to send diagnosis notifications: ' . $e->getMessage());
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
            \Log::error('Failed to send follow-up notifications: ' . $e->getMessage());
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
            $admins = \App\Models\User::where('role', 'admin')->get();
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
            \Log::error('Failed to send review notifications: ' . $e->getMessage());
        }
    }

}
