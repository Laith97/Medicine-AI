<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Models\ChatSession;
use App\Models\ChatMessage;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'doctor']);
    }

    public function index()
    {
        $chatSessions = $this->getEffectiveDoctor()->chatSessions()
                           ->with(['latestMessage', 'unreadMessages'])
                           ->orderBy('last_activity_at', 'desc')
                           ->paginate(20);

        return view('doctor.chat.index', compact('chatSessions'));
    }

    public function show(Request $request, $sessionId)
    {
        $session = ChatSession::where('id', $sessionId)
                             ->where('doctor_id', $this->getEffectiveDoctor()->id)
                             ->firstOrFail();

        $messages = $session->messages()
                           ->orderBy('created_at')
                           ->get()
                           ->map(function ($message) {
                               return [
                                   'id' => $message->id,
                                   'message' => $message->message,
                                   'sender_type' => $message->sender_type,
                                   'created_at' => $message->created_at->toISOString(),
                                   'formatted_time' => $message->formatted_time,
                               ];
                           });

        // Mark visitor messages as read
        $session->markMessagesAsRead();

        return response()->json([
            'success' => true,
            'messages' => $messages,
            'session' => [
                'id' => $session->id,
                'visitor_name' => $session->display_name,
                'visitor_email' => $session->visitor_email,
                'created_at' => $session->created_at->diffForHumans(),
            ]
        ]);
    }

    public function sendMessage(Request $request, $sessionId)
    {
        $request->validate([
            'message' => 'required|string|max:1000',
        ]);

        $session = ChatSession::where('id', $sessionId)
                             ->where('doctor_id', $this->getEffectiveDoctor()->id)
                             ->firstOrFail();

        $message = ChatMessage::createDoctorMessage(
            $session->id,
            $request->message
        );

        // Update session activity
        $session->updateActivity();

        return response()->json([
            'success' => true,
            'message' => [
                'id' => $message->id,
                'message' => $message->message,
                'sender_type' => $message->sender_type,
                'created_at' => $message->created_at->toISOString(),
                'formatted_time' => $message->formatted_time,
            ]
        ]);
    }

    public function getUnreadCount()
    {
        $count = ChatMessage::whereHas('chatSession', function ($query) {
                    $query->where('doctor_id', $this->getEffectiveDoctor()->id);
                })
                ->where('sender_type', 'visitor')
                ->where('is_read', false)
                ->count();

        return response()->json([
            'success' => true,
            'unread_count' => $count
        ]);
    }

    public function markAllAsRead()
    {
        ChatMessage::whereHas('chatSession', function ($query) {
                $query->where('doctor_id', $this->getEffectiveDoctor()->id);
            })
            ->where('sender_type', 'visitor')
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json([
            'success' => true,
            'message' => 'All messages marked as read'
        ]);
    }

    public function settings()
    {

        $doctor = $this->getEffectiveDoctor();

        return view('doctor.chat.settings', compact('doctor'));
    }

    public function updateSettings(Request $request)
    {
        $request->validate([
            'ai_chat_enabled' => 'boolean',
            'ai_welcome_message' => 'nullable|string|max:500',
            'ai_fallback_message' => 'nullable|string|max:500',
        ]);

        $doctor = $this->getEffectiveDoctor();

        $aiSettings = $doctor->ai_chat_settings ?? [];
        $aiSettings['welcome_message'] = $request->ai_welcome_message;
        $aiSettings['fallback_message'] = $request->ai_fallback_message;

        $doctor->update([
            'ai_chat_enabled' => $request->boolean('ai_chat_enabled'),
            'ai_chat_settings' => $aiSettings,
        ]);

        return redirect()->route('doctor.chat.settings')
                        ->with('success', 'Chat settings updated successfully!');
    }
}
