<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Models\MessageAttachment;
use App\Models\MessageThread;
use App\Services\MessagingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class MessagesController extends Controller
{
    public function __construct(
        private MessagingService $messagingService
    ) {}

    public function index(Request $request): View
    {
        $threads = MessageThread::forPatient(Auth::id())
            ->orderBy('last_message_at', 'desc')
            ->paginate(20);

        // Pre-fill compose modal from query params (e.g., from "Ask Follow-up" button)
        $prefill = [
            'doctor_id' => $request->query('doctor_id'),
            'diagnosis_id' => $request->query('diagnosis_id'),
            'subject' => $request->query('subject', 'Follow-up Question'),
        ];

        return view('patient.messages.index', compact('threads', 'prefill'));
    }

    public function show(MessageThread $thread): View
    {
        if ($thread->patient_id !== Auth::id()) {
            abort(403);
        }

        $thread->load('messages.attachments', 'doctor', 'patient');

        return view('patient.messages.show', compact('thread'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'doctor_id' => 'required|integer',
            'subject' => 'required|string|max:255',
            'body' => 'required|string|max:5000',
            'diagnosis_id' => 'nullable|integer',
            'attachments.*' => 'file|max:10240|mimes:jpeg,png,gif,webp,pdf,doc,docx,txt',
        ]);

        $patient = Auth::user();
        $eligibleDoctors = $this->messagingService->getEligibleDoctorsForPatient($patient->id);

        if (!$eligibleDoctors->contains('id', $validated['doctor_id'])) {
            abort(403);
        }

        $doctor = $eligibleDoctors->firstWhere('id', $validated['doctor_id']);

        try {
            $attachments = $request->hasFile('attachments') ? $request->file('attachments') : [];
            $thread = $this->messagingService->createThread(
                $patient,
                $doctor,
                $validated['subject'],
                $validated['body'],
                $validated['diagnosis_id'] ?? null,
                $attachments
            );
        } catch (\InvalidArgumentException $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        } catch (\Exception $e) {
            Log::error('Failed to create message thread', [
                'patient_id' => $patient->id,
                'error' => $e->getMessage(),
            ]);
            return redirect()->back()->withInput()->with('error', 'Failed to send message. Please try again.');
        }

        return redirect()->route('patient.messages.show', $thread)
            ->with('success', 'Message sent successfully.');
    }

    public function reply(Request $request, MessageThread $thread): RedirectResponse
    {
        if ($thread->patient_id !== Auth::id()) {
            abort(403);
        }

        if ($thread->isArchived()) {
            return redirect()->back()->with('error', 'This conversation is archived.');
        }

        $validated = $request->validate([
            'body' => 'required|string|max:5000',
            'attachments.*' => 'file|max:10240|mimes:jpeg,png,gif,webp,pdf,doc,docx,txt',
        ]);

        try {
            $attachments = $request->hasFile('attachments') ? $request->file('attachments') : [];
            $this->messagingService->addMessage(
                $thread,
                Auth::user(),
                'patient',
                $validated['body'],
                $attachments
            );
        } catch (\InvalidArgumentException $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        } catch (\Exception $e) {
            Log::error('Failed to send reply', [
                'thread_id' => $thread->id,
                'error' => $e->getMessage(),
            ]);
            return redirect()->back()->withInput()->with('error', 'Failed to send reply. Please try again.');
        }

        return redirect()->back()->with('success', 'Reply sent.');
    }

    public function attachment(MessageAttachment $attachment): JsonResponse
    {
        $message = $attachment->message;
        $thread = $message->thread;

        if ($thread->patient_id !== Auth::id()) {
            abort(403);
        }

        $path = $this->messagingService->getAttachment($attachment, Auth::user());

        if (!$path) {
            return response()->json(['message' => 'File not found.'], 404);
        }

        return response()->download($path, $attachment->original_name, [
            'Content-Type' => $attachment->mime_type,
        ]);
    }
}
