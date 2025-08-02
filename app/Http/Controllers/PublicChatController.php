<?php

namespace App\Http\Controllers;

use App\Models\Doctor;
use App\Models\ChatSession;
use App\Models\ChatMessage;
use App\Services\ChatService;
use Illuminate\Http\Request;

class PublicChatController extends Controller
{
    protected $chatService;

    public function __construct(ChatService $chatService)
    {
        $this->chatService = $chatService;
    }

    public function initializeChat(Request $request, $username)
    {
        $doctor = Doctor::whereHas('landingPage', function ($query) use ($username) {
            $query->where('username', $username);
        })->firstOrFail();

        $visitorData = [
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ];

        $session = ChatSession::findOrCreateForVisitor($doctor->id, $visitorData);

        // Create welcome message if this is a new session
        if ($session->messages()->count() === 0) {
            $welcomeMessage = $this->chatService->generateWelcomeMessage($doctor);
            ChatMessage::createBotMessage($session->id, $welcomeMessage);
        }

        return response()->json([
            'success' => true,
            'session_id' => $session->session_id,
            'welcome_message' => $this->chatService->generateWelcomeMessage($doctor)
        ]);
    }

    public function sendMessage(Request $request, $username)
    {
        $request->validate([
            'message' => 'required|string|max:1000',
            'session_id' => 'required|string',
            'visitor_name' => 'nullable|string|max:100',
            'visitor_email' => 'nullable|email|max:255',
        ]);

        $doctor = Doctor::whereHas('landingPage', function ($query) use ($username) {
            $query->where('username', $username);
        })->firstOrFail();

        $session = ChatSession::where('session_id', $request->session_id)
                             ->where('doctor_id', $doctor->id)
                             ->firstOrFail();

        // Update visitor info if provided
        $updateData = [];
        if ($request->filled('visitor_name') && empty($session->visitor_name)) {
            $updateData['visitor_name'] = $request->visitor_name;
        }
        if ($request->filled('visitor_email') && empty($session->visitor_email)) {
            $updateData['visitor_email'] = $request->visitor_email;
        }

        if (!empty($updateData)) {
            $session->update($updateData);
        }

        // Create visitor message
        ChatMessage::createVisitorMessage($session->id, $request->message);

        // Update session activity
        $session->updateActivity();

        // Detect language and generate bot response
        $language = $this->chatService->detectLanguage($request->message);
        $botResponse = $this->chatService->generateBotResponse($request->message, $doctor, $language);

        // Only create bot response if AI is enabled
        if ($doctor->ai_chat_enabled) {
            ChatMessage::createBotMessage($session->id, $botResponse);
        }

        return response()->json([
            'success' => true,
            'bot_response' => $doctor->ai_chat_enabled ? $botResponse : null,
            'formatted_time' => now()->format('g:i A')
        ]);
    }

    public function getChatHistory(Request $request, $username)
    {
        $request->validate([
            'session_id' => 'required|string',
        ]);

        $doctor = Doctor::whereHas('landingPage', function ($query) use ($username) {
            $query->where('username', $username);
        })->firstOrFail();

        $session = ChatSession::where('session_id', $request->session_id)
                             ->where('doctor_id', $doctor->id)
                             ->firstOrFail();

        $messages = $session->messages()
                           ->orderBy('created_at')
                           ->get()
                           ->map(function ($message) {
                               return [
                                   'message' => $message->message,
                                   'sender_type' => $message->sender_type,
                                   'created_at' => $message->created_at->format('g:i A'),
                               ];
                           });

        return response()->json([
            'success' => true,
            'messages' => $messages
        ]);
    }

    public function checkNewMessages(Request $request, $username)
    {
        $request->validate([
            'session_id' => 'required|string',
            'last_message_id' => 'nullable|integer',
        ]);

        $doctor = Doctor::whereHas('landingPage', function ($query) use ($username) {
            $query->where('username', $username);
        })->firstOrFail();

        $session = ChatSession::where('session_id', $request->session_id)
                             ->where('doctor_id', $doctor->id)
                             ->firstOrFail();

        $query = $session->messages()
                        ->where('sender_type', 'doctor')
                        ->orderBy('created_at');

        if ($request->last_message_id) {
            $query->where('id', '>', $request->last_message_id);
        }

        $newMessages = $query->get()->map(function ($message) {
            return [
                'id' => $message->id,
                'message' => $message->message,
                'sender_type' => $message->sender_type,
                'created_at' => $message->created_at->format('g:i A'),
            ];
        });

        return response()->json([
            'success' => true,
            'new_messages' => $newMessages
        ]);
    }
}
