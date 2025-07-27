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
        $doctor = Auth::user();

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
                $q->where('doctor_id', $doctor->doctor->id ?? null);
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
        $doctor = Auth::user();

        // Get patients who have appointments with this doctor
        $patients = User::where('role', 'patient')
            ->whereHas('appointments', function($q) use ($doctor) {
                $q->where('doctor_id', $doctor->doctor->id ?? null);
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
        $validator = Validator::make($request->all(), [
            'note_type' => 'required|in:text,voice',
            'note_text' => 'required|string',
            'patient_id' => 'nullable|exists:users,id',
            'appointment_id' => 'nullable|exists:appointments,id',
            'appointment_date' => 'nullable|date',
            'title' => 'nullable|string|max:255',
            'transcript' => 'nullable|string',
            'audio_file' => 'nullable|string', // base64 audio data
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $doctor = Auth::user();
        $audioFilePath = null;

        // Handle audio file if provided
        if ($request->filled('audio_file') && $request->note_type === 'voice') {
            try {
                $audioFilePath = $this->saveAudioFile($request->audio_file);
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to save audio file: ' . $e->getMessage()
                ], 500);
            }
        }

        $note = DoctorNote::create([
            'doctor_id' => $doctor->id,
            'patient_id' => $request->patient_id,
            'appointment_id' => $request->appointment_id,
            'note_type' => $request->note_type,
            'note_text' => $request->note_text,
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

        $doctor = Auth::user();

        // Get patients who have appointments with this doctor
        $patients = User::where('role', 'patient')
            ->whereHas('appointments', function($q) use ($doctor) {
                $q->where('doctor_id', $doctor->doctor->id ?? null);
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

        $validator = Validator::make($request->all(), [
            'note_text' => 'required|string',
            'patient_id' => 'nullable|exists:users,id',
            'appointment_id' => 'nullable|exists:appointments,id',
            'appointment_date' => 'nullable|date',
            'title' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $note->update([
            'note_text' => $request->note_text,
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
            // Decode base64 audio
            $audioData = base64_decode(preg_replace('#^data:audio/\w+;base64,#i', '', $request->audio_file));

            // Create temporary file
            $tempFile = tempnam(sys_get_temp_dir(), 'audio_') . '.webm';
            file_put_contents($tempFile, $audioData);

            // Use OpenAI Whisper API for transcription
            $response = Http::withToken(config('services.openai.key'))
                ->attach('file', fopen($tempFile, 'r'), 'audio.webm')
                ->post('https://api.openai.com/v1/audio/transcriptions', [
                    'model' => 'whisper-1',
                    'language' => 'en', // You can make this configurable
                    'response_format' => 'text'
                ]);

            // Clean up temporary file
            unlink($tempFile);

            if ($response->successful()) {
                $transcript = $response->body();

                return response()->json([
                    'success' => true,
                    'transcript' => trim($transcript)
                ]);
            } else {
                \Log::error('OpenAI Whisper API Error', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Transcription failed. Please try again.'
                ], 500);
            }

        } catch (\Exception $e) {
            \Log::error('Transcription Exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

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
        $doctor = Auth::user();

        $query = User::where('role', 'patient')
            ->whereHas('appointments', function($q) use ($doctor) {
                $q->where('doctor_id', $doctor->doctor->id ?? null);
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
        // Remove data URL prefix if present
        $audioData = base64_decode(preg_replace('#^data:audio/\w+;base64,#i', '', $base64Audio));

        // Generate unique filename
        $filename = 'doctor-notes/' . Auth::id() . '/' . uniqid() . '_' . time() . '.webm';

        // Store file
        Storage::put($filename, $audioData);

        return $filename;
    }
}
