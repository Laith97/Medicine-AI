<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\TelehealthAIInsights;
use App\Notifications\TelehealthAlertNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelehealthAIController extends Controller
{
    public function detectEmotion(Request $request)
    {
        $request->validate([
            'appointment_id' => 'required|integer',
            'patient_id' => 'nullable|integer',
            'frame_base64' => 'required|string',
        ]);

        try {
            $response = Http::post('http://localhost:5001/detect_emotion', [
                'frame_base64' => $request->frame_base64,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                TelehealthAIInsights::create([
                    'appointment_id' => $request->appointment_id,
                    'patient_id' => $request->patient_id,
                    'emotion' => $data['emotion'] ?? null,
                    'emotion_confidence' => $data['confidence'] ?? null,
                ]);

                // Send notification if emotion is stressed and confidence > 0.8
                if (($data['emotion'] ?? null) === 'stressed' && ($data['confidence'] ?? 0) > 0.8) {
                    $appointment = Appointment::find($request->appointment_id);
                    if ($appointment && $appointment->doctor) {
                        $appointment->doctor->notify(new TelehealthAlertNotification(
                            "Patient appears stressed, consider follow-up questions.",
                            $request->appointment_id
                        ));
                    }
                }

                return response()->json([
                    'emotion' => $data['emotion'] ?? null,
                    'confidence' => $data['confidence'] ?? null,
                ]);
            } else {
                return response()->json(['error' => 'Service unavailable'], 503);
            }
        } catch (\Exception $e) {
            Log::error('Emotion detection service error: ' . $e->getMessage());
            return response()->json(['error' => 'Service unavailable'], 503);
        }
    }

    public function trackEngagement(Request $request)
    {
        $request->validate([
            'appointment_id' => 'required|integer',
            'patient_id' => 'nullable|integer',
            'frame_base64' => 'required|string',
        ]);

        try {
            $response = Http::post('http://localhost:5002/track_engagement', [
                'frame_base64' => $request->frame_base64,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                TelehealthAIInsights::create([
                    'appointment_id' => $request->appointment_id,
                    'patient_id' => $request->patient_id,
                    'attention_score' => $data['attention_score'] ?? null,
                    'eye_contact' => $data['eye_contact'] ?? null,
                ]);

                // Send notification if attention_score < 0.5
                if (($data['attention_score'] ?? 1) < 0.5) {
                    $appointment = Appointment::find($request->appointment_id);
                    if ($appointment && $appointment->doctor) {
                        $appointment->doctor->notify(new TelehealthAlertNotification(
                            "Patient engagement low, attention score below threshold.",
                            $request->appointment_id
                        ));
                    }
                }

                return response()->json([
                    'attention_score' => $data['attention_score'] ?? null,
                    'eye_contact' => $data['eye_contact'] ?? null,
                    'participation' => $data['participation'] ?? null,
                ]);
            } else {
                return response()->json(['error' => 'Service unavailable'], 503);
            }
        } catch (\Exception $e) {
            Log::error('Engagement tracking service error: ' . $e->getMessage());
            return response()->json(['error' => 'Service unavailable'], 503);
        }
    }

    public function getEmotionSummary($appointment_id)
    {
        $validated = validator(['appointment_id' => $appointment_id], [
            'appointment_id' => 'required|integer|exists:appointments,id',
        ]);

        if ($validated->fails()) {
            return response()->json(['error' => 'Invalid appointment ID'], 400);
        }

        $emotions = TelehealthAIInsights::forAppointment($appointment_id)
            ->withEmotionData()
            ->selectRaw('emotion, COUNT(*) as count')
            ->groupBy('emotion')
            ->get()
            ->pluck('count', 'emotion')
            ->toArray();

        return response()->json(['emotion_distribution' => $emotions]);
    }

    public function getEngagementSummary($appointment_id)
    {
        $validated = validator(['appointment_id' => $appointment_id], [
            'appointment_id' => 'required|integer|exists:appointments,id',
        ]);

        if ($validated->fails()) {
            return response()->json(['error' => 'Invalid appointment ID'], 400);
        }

        $engagement = TelehealthAIInsights::forAppointment($appointment_id)
            ->withEngagementData()
            ->selectRaw('AVG(attention_score) as avg_attention_score, AVG(eye_contact) as avg_eye_contact')
            ->first();

        return response()->json([
            'summary_metrics' => [
                'avg_attention_score' => $engagement->avg_attention_score ? round($engagement->avg_attention_score, 4) : null,
                'avg_eye_contact' => $engagement->avg_eye_contact ? round($engagement->avg_eye_contact, 4) : null,
            ]
        ]);
    }
}
