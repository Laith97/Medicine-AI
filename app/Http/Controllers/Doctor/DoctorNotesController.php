<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Models\DoctorNote;
use App\Models\User;
use App\Models\Appointment;
use App\Services\OpenAIClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use OpenAI\Laravel\Facades\OpenAI;

class DoctorNotesController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'doctor']);
    }

    /**
     * Display a listing of the doctor's notes
     */
    public function index(Request $request)
    {
        $doctor = $this->getEffectiveDoctor();

        $query = DoctorNote::byDoctor($doctor->id)
            ->with(['patient', 'appointment'])
            ->orderBy('created_at', 'desc');

        // Apply filters
        if ($request->filled('patient_id')) {
            $query->where('patient_id', $request->patient_id);
        }

        if ($request->filled('note_type')) {
            $query->where('note_type', $request->note_type);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('note_text', 'like', "%{$search}%")
                  ->orWhere('transcript', 'like', "%{$search}%")
                  ->orWhere('title', 'like', "%{$search}%");
            });
        }

        $notes = $query->paginate(15);

        // Get patients for filter dropdown
        $patients = User::where('role', 'patient')
            ->whereHas('appointments', function($q) use ($doctor) {
                $q->where('doctor_id', $doctor->id);
            })
            ->orderBy('name')
            ->get();

        return view('doctor.notes.index', compact('notes', 'patients'));
    }

    /**
     * Show the form for creating a new note
     */
    public function create()
    {
        $doctor = $this->getEffectiveDoctor();

        // Get patients who have appointments with this doctor
        $patients = User::where('role', 'patient')
            ->whereHas('appointments', function($q) use ($doctor) {
                $q->where('doctor_id', $doctor->id);
            })
            ->orderBy('name')
            ->get();

        // Get recent appointments for this doctor
        $appointments = Appointment::where('doctor_id', $doctor->doctor->id ?? null)
            ->with('patient')
            ->where('status', 'completed')
            ->orderBy('appointment_date', 'desc')
            ->limit(20)
            ->get();

        return view('doctor.notes.create', compact('patients', 'appointments'));
    }

    /**
     * Store a newly created note
     */
    public function store(Request $request)
    {
        // Validate based on note type
        $rules = [
            'note_type' => 'required|in:text,voice',
            'patient_id' => 'nullable|exists:users,id',
            'appointment_id' => 'nullable|exists:appointments,id',
            'appointment_date' => 'nullable|date',
            'title' => 'nullable|string|max:255',
            'transcript' => 'nullable|string',
            'audio_file' => 'nullable|string', // base64 audio data
        ];

        // For text notes, require note_text
        // For voice notes, require either note_text or transcript
        if ($request->note_type === 'text') {
            $rules['note_text'] = 'required|string';
        } else {
            $rules['note_text'] = 'nullable|string';
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $doctor = $this->getEffectiveDoctor();
        $audioFilePath = null;

        // Handle audio file if provided
        if ($request->filled('audio_file') && $request->note_type === 'voice') {
            try {
                $audioFilePath = $this->saveAudioFile($request->audio_file);
            } catch (\Exception $e) {
                Log::error('Failed to save audio file', [
                    'error' => $e->getMessage(),
                    'user_id' => $doctor->id,
                    'trace' => $e->getTraceAsString()
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Failed to save audio file: ' . $e->getMessage()
                ], 500);
            }
        }

        // For voice notes, if note_text is empty but transcript is available, use transcript
        $noteText = $request->note_text;
        if ($request->note_type === 'voice' && empty($noteText) && $request->filled('transcript')) {
            $noteText = $request->transcript;
        }

        // Ensure we have content for the note
        if (empty($noteText)) {
            return response()->json([
                'success' => false,
                'message' => 'Note content is required'
            ], 422);
        }

        try {
            $note = DoctorNote::create([
                'doctor_id' => $doctor->id,
                'patient_id' => $request->patient_id,
                'appointment_id' => $request->appointment_id,
                'note_type' => $request->note_type,
                'note_text' => $noteText,
                'transcript' => $request->transcript,
                'audio_file_path' => $audioFilePath,
                'appointment_date' => $request->appointment_date,
                'title' => $request->title,
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Note created successfully',
                    'note' => $note->load(['patient', 'appointment'])
                ]);
            }

            return redirect()->route('doctor.notes.index')
                ->with('success', 'Note created successfully');
        } catch (\Exception $e) {
            Log::error('Failed to create note', [
                'error' => $e->getMessage(),
                'user_id' => $doctor->id,
                'request_data' => $request->except(['audio_file']),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create note: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified note
     */
    public function show(DoctorNote $note)
    {
        $this->authorize('view', $note);

        $note->load(['patient', 'appointment']);

        return view('doctor.notes.show', compact('note'));
    }

    /**
     * Show the form for editing the specified note
     */
    public function edit(DoctorNote $note)
    {
        $this->authorize('update', $note);

        $doctor = $this->getEffectiveDoctor();

        // Get patients who have appointments with this doctor
        $patients = User::where('role', 'patient')
            ->whereHas('appointments', function($q) use ($doctor) {
                $q->where('doctor_id', $doctor->id);
            })
            ->orderBy('name')
            ->get();

        // Get recent appointments for this doctor
        $appointments = Appointment::where('doctor_id', $doctor->doctor->id ?? null)
            ->with('patient')
            ->where('status', 'completed')
            ->orderBy('appointment_date', 'desc')
            ->limit(20)
            ->get();

        return view('doctor.notes.edit', compact('note', 'patients', 'appointments'));
    }

    /**
     * Update the specified note
     */
    public function update(Request $request, DoctorNote $note)
    {
        $this->authorize('update', $note);

        // Validate based on note type
        $rules = [
            'patient_id' => 'nullable|exists:users,id',
            'appointment_id' => 'nullable|exists:appointments,id',
            'appointment_date' => 'nullable|date',
            'title' => 'nullable|string|max:255',
        ];

        // For text notes, require note_text
        // For voice notes, require either note_text or transcript
        if ($note->note_type === 'text') {
            $rules['note_text'] = 'required|string';
        } else {
            $rules['note_text'] = 'nullable|string';
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // For voice notes, if note_text is empty but transcript is available, use transcript
        $noteText = $request->note_text;
        if ($note->note_type === 'voice' && empty($noteText) && $request->filled('transcript')) {
            $noteText = $request->transcript;
        }

        // Ensure we have content for the note
        if (empty($noteText)) {
            return response()->json([
                'success' => false,
                'message' => 'Note content is required'
            ], 422);
        }

        try {
            $note->update([
                'note_text' => $noteText,
                'patient_id' => $request->patient_id,
                'appointment_id' => $request->appointment_id,
                'appointment_date' => $request->appointment_date,
                'title' => $request->title,
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Note updated successfully',
                    'note' => $note->load(['patient', 'appointment'])
                ]);
            }

            return redirect()->route('doctor.notes.index')
                ->with('success', 'Note updated successfully');
        } catch (\Exception $e) {
            Log::error('Failed to update note', [
                'error' => $e->getMessage(),
                'note_id' => $note->id,
                'user_id' => Auth::id(),
                'request_data' => $request->except(['audio_file']),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update note: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified note from storage
     */
    public function destroy(DoctorNote $note)
    {
        $this->authorize('delete', $note);

        // Delete audio file if exists
        if ($note->audio_file_path && Storage::exists($note->audio_file_path)) {
            Storage::delete($note->audio_file_path);
        }

        $note->delete();

        return response()->json([
            'success' => true,
            'message' => 'Note deleted successfully'
        ]);
    }

    /**
     * Transcribe audio using OpenAI Whisper
     */
    public function transcribeAudio(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'audio_file' => 'required|string', // base64 audio data
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Log the base64 data for debugging
            Log::debug('Transcribing audio file', [
                'base64_length' => strlen($request->audio_file),
                'starts_with' => substr($request->audio_file, 0, 50),
                'user_id' => Auth::id()
            ]);

            // Decode base64 audio (more flexible pattern)
            $audioData = base64_decode(preg_replace('#^data:audio/[\w-]+;base64,#i', '', $request->audio_file));

            // Validate that we have audio data
            if (empty($audioData)) {
                Log::error('Transcription failed: Empty audio data', [
                    'user_id' => Auth::id()
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Transcription failed: Empty audio data'
                ], 422);
            }

            // Create temporary file
            $tempFile = tempnam(sys_get_temp_dir(), 'audio_') . '.webm';
            $bytesWritten = file_put_contents($tempFile, $audioData);

            // Validate file was written successfully
            if ($bytesWritten === false || $bytesWritten === 0) {
                Log::error('Failed to write temporary audio file', [
                    'temp_file' => $tempFile,
                    'bytes_written' => $bytesWritten,
                    'user_id' => Auth::id()
                ]);

                unlink($tempFile); // Clean up
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to process audio file'
                ], 500);
            }

            // Validate file size
            $fileSize = filesize($tempFile);
            if ($fileSize === 0) {
                Log::error('Temporary audio file is empty', [
                    'temp_file' => $tempFile,
                    'file_size' => $fileSize,
                    'user_id' => Auth::id()
                ]);

                unlink($tempFile); // Clean up
                return response()->json([
                    'success' => false,
                    'message' => 'Audio file is empty'
                ], 422);
            }

            // Log file info
            Log::debug('Temporary audio file created', [
                'temp_file' => $tempFile,
                'file_size' => $fileSize,
                'user_id' => Auth::id()
            ]);

            // Use OpenAI Whisper API for transcription with auto-language detection
            $response = Http::withToken(config('services.openai.key'))
                ->attach('file', fopen($tempFile, 'r'), 'audio.webm')
                ->post('https://api.openai.com/v1/audio/transcriptions', [
                    'model' => 'whisper-1',
                    // Remove language parameter to enable auto-detection
                    'response_format' => 'text',
                    'prompt' => 'This is a medical consultation recording. Please transcribe accurately including medical terminology, symptoms, diagnoses, and treatment plans.'
                ]);

            // Clean up temporary file
            unlink($tempFile);

            if ($response->successful()) {
                $rawTranscript = $response->body();

                // Validate transcript
                if (empty(trim($rawTranscript))) {
                    Log::warning('Transcription successful but empty', [
                        'user_id' => Auth::id()
                    ]);

                    return response()->json([
                        'success' => true,
                        'transcript' => ''
                    ]);
                }

                // Post-process the transcript for better medical formatting
                $formattedTranscript = $this->formatMedicalTranscript(trim($rawTranscript));

                return response()->json([
                    'success' => true,
                    'transcript' => $formattedTranscript
                ]);
            } else {
                Log::error('OpenAI Whisper API Error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'user_id' => Auth::id()
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Transcription failed. Please try again.'
                ], 500);
            }

        } catch (\Exception $e) {
            Log::error('Transcription Exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id()
            ]);

            // Clean up temporary file if it exists
            if (isset($tempFile) && file_exists($tempFile)) {
                unlink($tempFile);
            }

            return response()->json([
                'success' => false,
                'message' => 'Transcription failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get patients for AJAX dropdown
     */
    public function getPatients(Request $request)
    {
        $doctor = $this->getEffectiveDoctor();

        $query = User::where('role', 'patient')
            ->whereHas('appointments', function($q) use ($doctor) {
                $q->where('doctor_id', $doctor->id);
            });

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $patients = $query->orderBy('name')->limit(20)->get(['id', 'name', 'email']);

        return response()->json($patients);
    }

    /**
     * Save audio file to storage
     */
    private function saveAudioFile($base64Audio)
    {
        // Log the base64 data for debugging
        Log::debug('Saving audio file', [
            'base64_length' => strlen($base64Audio),
            'starts_with' => substr($base64Audio, 0, 50),
            'user_id' => Auth::id()
        ]);

        // Remove data URL prefix if present (more flexible pattern)
        $audioData = base64_decode(preg_replace('#^data:audio/[\w-]+;base64,#i', '', $base64Audio));

        // Validate that we have audio data
        if (empty($audioData)) {
            throw new \Exception('Invalid audio data: decoded data is empty');
        }

        // Generate unique filename
        $filename = 'doctor-notes/' . Auth::id() . '/' . uniqid() . '_' . time() . '.webm';

        // Ensure directory exists
        $directory = dirname($filename);
        if (!Storage::exists($directory)) {
            Storage::makeDirectory($directory, 0755, true);
        }

        // Store file
        $result = Storage::put($filename, $audioData);

        if (!$result) {
            throw new \Exception('Failed to store audio file');
        }

        // Log success
        Log::debug('Audio file saved successfully', [
            'filename' => $filename,
            'file_size' => strlen($audioData),
            'user_id' => Auth::id()
        ]);

        return $filename;
    }

    /**
     * Format medical transcript with better organization and structure
     */
    private function formatMedicalTranscript($rawTranscript)
    {
        try {
            // Use OpenAI to format and organize the medical transcript
            $response = Http::withToken(config('services.openai.key'))
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => 'gpt-4o',
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'You are a medical transcription assistant. Your task is to format and organize medical voice notes into well-structured, professional medical documentation.

IMPORTANT RULES:
1. PRESERVE THE ORIGINAL LANGUAGE - If the transcript is in Arabic, respond in Arabic. If in English, respond in English. DO NOT translate.
2. Maintain all medical terminology and diagnoses exactly as mentioned
3. Organize the content using bullet points and clear sections
4. Use proper medical formatting with sections like:
   - Chief Complaint / الشكوى الرئيسية
   - History of Present Illness / تاريخ المرض الحالي
   - Physical Examination / الفحص السريري
   - Assessment/Diagnosis / التقييم/التشخيص
   - Plan/Treatment / الخطة/العلاج
5. Correct obvious transcription errors while preserving medical accuracy
6. Keep the same language as the input - do not translate between languages'
                        ],
                        [
                            'role' => 'user',
                            'content' => "Please format and organize this medical transcript while preserving the original language and medical accuracy:\n\n" . $rawTranscript
                        ]
                    ],
                    'temperature' => 0.3,
                    'max_tokens' => 2000
                ]);

            if ($response->successful()) {
                $responseData = $response->json();
                if (isset($responseData['choices'][0]['message']['content'])) {
                    $formattedText = trim($responseData['choices'][0]['message']['content']);

                    // Log the formatting for debugging
                    Log::debug('Medical transcript formatted successfully', [
                        'original_length' => strlen($rawTranscript),
                        'formatted_length' => strlen($formattedText),
                        'user_id' => Auth::id()
                    ]);

                    return $formattedText;
                }
            }

            // If formatting fails, return the original transcript with basic formatting
            Log::warning('Medical transcript formatting failed, returning original', [
                'user_id' => Auth::id(),
                'response_status' => $response->status() ?? 'unknown'
            ]);

            return $this->basicMedicalFormatting($rawTranscript);

        } catch (\Exception $e) {
            Log::error('Error formatting medical transcript', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            // Return original transcript with basic formatting as fallback
            return $this->basicMedicalFormatting($rawTranscript);
        }
    }

    /**
     * Apply basic formatting to medical transcript as fallback
     */
    private function basicMedicalFormatting($transcript)
    {
        // Split into sentences and add basic structure
        $sentences = preg_split('/[.!?]+/', $transcript);
        $formatted = [];

        foreach ($sentences as $sentence) {
            $sentence = trim($sentence);
            if (!empty($sentence)) {
                $formatted[] = '• ' . $sentence;
            }
        }

        return implode("\n", $formatted);
    }
}
