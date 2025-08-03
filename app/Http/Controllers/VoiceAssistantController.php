<?php

namespace App\Http\Controllers;

use App\Models\VoiceTranscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VoiceAssistantController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (!Auth::user()->isDoctor() || !Auth::user()->doctor) {
                abort(403, 'Access denied. Doctor profile required.');
            }
            return $next($request);
        });
    }

    public function index()
    {
        return view('voice-assistant.index');
    }

    public function history()
    {
        $transcriptions = VoiceTranscription::where('doctor_id', Auth::id())
            ->with('patient')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('voice-assistant.history', compact('transcriptions'));
    }

    public function show(VoiceTranscription $transcription)
    {
        // Ensure the transcription belongs to the authenticated doctor
        if ($transcription->doctor_id !== Auth::id()) {
            abort(403, 'Unauthorized access to transcription.');
        }

        return view('voice-assistant.show', compact('transcription'));
    }
}
