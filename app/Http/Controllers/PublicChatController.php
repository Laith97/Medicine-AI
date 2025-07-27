<?php

namespace App\Http\Controllers;

use App\Models\Doctor;
use App\Models\ChatSession;
use App\Models\ChatMessage;
use Illuminate\Http\Request;

class PublicChatController extends Controller
{
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
            $welcomeMessage = $this->generateWelcomeMessage($doctor);
            ChatMessage::createBotMessage($session->id, $welcomeMessage);
        }

        return response()->json([
            'success' => true,
            'session_id' => $session->session_id,
            'welcome_message' => $this->generateWelcomeMessage($doctor)
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

        // Generate bot response
        $botResponse = $this->generateBotResponse($request->message, $doctor);
        ChatMessage::createBotMessage($session->id, $botResponse);

        return response()->json([
            'success' => true,
            'bot_response' => $botResponse,
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

    private function generateWelcomeMessage($doctor)
    {
        $messages = [
            "Hello! I'm Dr. {$doctor->user->name}'s AI assistant. How can I help you today?",
            "Welcome! I'm here to help answer your questions about Dr. {$doctor->user->name}'s services. What would you like to know?",
            "Hi there! I'm Dr. {$doctor->user->name}'s virtual assistant. Feel free to ask me about appointments, services, or any health-related questions.",
        ];

        return $messages[array_rand($messages)];
    }

    private function generateBotResponse($message, $doctor)
    {
        $message = strtolower($message);

        // Simple keyword-based responses
        if (str_contains($message, 'appointment') || str_contains($message, 'book') || str_contains($message, 'schedule')) {
            return "I'd be happy to help you book an appointment with Dr. {$doctor->user->name}. You can schedule directly through this page or call our office. What type of consultation are you looking for?";
        }

        if (str_contains($message, 'price') || str_contains($message, 'cost') || str_contains($message, 'fee')) {
            $fee = $doctor->consultation_fee_dollars ? "$" . $doctor->consultation_fee_dollars : "varies";
            return "Dr. {$doctor->user->name}'s consultation fee is {$fee}. This may vary depending on the type of consultation. Would you like to know more about our services?";
        }

        if (str_contains($message, 'location') || str_contains($message, 'address') || str_contains($message, 'where')) {
            $location = $doctor->city ? "in {$doctor->city}" : "at our clinic";
            return "Dr. {$doctor->user->name} practices {$location}. For the exact address and directions, please check the contact information on this page.";
        }

        if (str_contains($message, 'hours') || str_contains($message, 'time') || str_contains($message, 'open')) {
            return "Our office hours vary by day. You can see available appointment slots on this page, or contact us directly for more information about our schedule.";
        }

        if (str_contains($message, 'insurance') || str_contains($message, 'coverage')) {
            return "For insurance coverage and payment options, please contact our office directly. We'll be happy to verify your benefits and discuss payment plans if needed.";
        }

        if (str_contains($message, 'emergency') || str_contains($message, 'urgent')) {
            return "For medical emergencies, please call 911 or go to your nearest emergency room immediately. For urgent but non-emergency concerns, please contact our office directly.";
        }

        if (str_contains($message, 'specialty') || str_contains($message, 'specializes')) {
            $specialty = $doctor->specialty ? $doctor->specialty->name : "general practice";
            return "Dr. {$doctor->user->name} specializes in {$specialty}. Would you like to know more about the specific services we offer?";
        }

        // Default responses
        $defaultResponses = [
            "Thank you for your question! For specific medical advice or detailed information, I recommend scheduling a consultation with Dr. {$doctor->user->name}. Is there anything else I can help you with?",
            "That's a great question! Dr. {$doctor->user->name} would be the best person to provide you with detailed information about that. Would you like to book an appointment?",
            "I understand your concern. For personalized medical advice, please consider scheduling a consultation with Dr. {$doctor->user->name}. Can I help you with anything else?",
        ];

        return $defaultResponses[array_rand($defaultResponses)];
    }
}
