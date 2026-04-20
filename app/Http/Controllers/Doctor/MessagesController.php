<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Models\AiMessageSuggestion;
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

    public function index(): View
    {
        $threads = MessageThread::forDoctor(Auth::id())
            ->orderBy('last_message_at', 'desc')
            ->paginate(20);

        return view('doctor.messages.index', compact('threads'));
    }

    public function show(MessageThread $thread): View
    {
        if ($thread->doctor_id !== Auth::id()) {
            abort(403);
        }

        $thread->load('messages.attachments', 'doctor', 'patient');

        $pendingSuggestion = $thread->aiSuggestions()->pending()->first();

        return view('doctor.messages.show', compact('thread', 'pendingSuggestion'));
    }

    public function generateSuggestion(MessageThread $thread): RedirectResponse
    {
        if ($thread->doctor_id !== Auth::id()) {
            abort(403);
        }

        if ($thread->isArchived()) {
            return redirect()->back()->with('error', 'This conversation is archived.');
        }

        if (!$thread->messages()->byPatient()->exists()) {
            return redirect()->back()->with('error', 'No patient messages to generate a suggestion for.');
        }

        if ($thread->aiSuggestions()->pending()->exists()) {
            return redirect()->back()->with('error', 'A suggestion is already pending.');
        }

        $suggestion = $this->messagingService->generateAiSuggestion($thread, Auth::user());

        if (!$suggestion) {
            return redirect()->back()->with('error', 'Failed to generate suggestion. Please try again or write your own reply.');
        }

        return redirect()->back()->with('success', 'AI suggestion generated.');
    }

    public function reply(Request $request, MessageThread $thread): RedirectResponse
    {
        if ($thread->doctor_id !== Auth::id()) {
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
                'doctor',
                $validated['body'],
                $attachments
            );

            // Reject any pending AI suggestions since doctor wrote their own reply
            $thread->aiSuggestions()->pending()->update(['status' => 'rejected']);

        } catch (\InvalidArgumentException $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        } catch (\Exception $e) {
            Log::error('Failed to send doctor reply', [
                'thread_id' => $thread->id,
                'error' => $e->getMessage(),
            ]);
            return redirect()->back()->withInput()->with('error', 'Failed to send reply. Please try again.');
        }

        return redirect()->back()->with('success', 'Reply sent to patient.');
    }

    public function approveSuggestion(Request $request, MessageThread $thread, AiMessageSuggestion $suggestion): RedirectResponse
    {
        if ($thread->doctor_id !== Auth::id() || $suggestion->thread_id !== $thread->id) {
            abort(403);
        }

        if (!$suggestion->isPending()) {
            return redirect()->back()->with('error', 'Suggestion already processed.');
        }

        try {
            $this->messagingService->approveSuggestion($suggestion, Auth::user());
        } catch (\Exception $e) {
            Log::error('Failed to approve AI suggestion', [
                'suggestion_id' => $suggestion->id,
                'error' => $e->getMessage(),
            ]);
            return redirect()->back()->with('error', 'Failed to send suggestion. Please try again.');
        }

        return redirect()->back()->with('success', 'Reply sent to patient.');
    }

    public function rejectSuggestion(Request $request, MessageThread $thread, AiMessageSuggestion $suggestion): RedirectResponse
    {
        if ($thread->doctor_id !== Auth::id() || $suggestion->thread_id !== $thread->id) {
            abort(403);
        }

        if (!$suggestion->isPending()) {
            return redirect()->back()->with('error', 'Suggestion already processed.');
        }

        $this->messagingService->rejectSuggestion($suggestion);

        return redirect()->back()->with('success', 'Suggestion rejected. Write your own reply below.');
    }

    public function attachment(MessageAttachment $attachment): JsonResponse
    {
        $message = $attachment->message;
        $thread = $message->thread;

        if ($thread->doctor_id !== Auth::id()) {
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
