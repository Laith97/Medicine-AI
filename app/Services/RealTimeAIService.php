<?php

namespace App\Services;

use App\Events\AmbientSessionUpdated;
use App\Events\RealTimeInsightCreated;
use App\Models\AmbientRecordingSession;
use App\Models\AmbientRecordingChunk;
use App\Models\RealTimeInsight;
use Illuminate\Support\Facades\Log;

class RealTimeAIService
{
    public function __construct(
        protected OpenAIClient $openai
    ) {}

    /**
     * Process a new audio chunk: transcribe, extract insights, and broadcast.
     * NOTE: This is a placeholder; Whisper and GPT logic can be added.
     */
    public function processChunk(AmbientRecordingSession $session, AmbientRecordingChunk $chunk): void
    {
        try {
            // 1) Transcribe audio chunk (Whisper placeholder)
            $partialTranscript = $this->transcribeChunk($chunk);

            if ($partialTranscript) {
                // Append to session transcription buffer
                $session->transcription = trim(($session->transcription ?? '') . "\n" . $partialTranscript);
                $session->save();

                // Broadcast partial transcript update
                event(new AmbientSessionUpdated(
                    $session->session_uuid,
                    $session->doctor_id,
                    [
                        'type' => 'transcription',
                        'text' => $partialTranscript,
                    ]
                ));
            }

            // 2) Extract real-time insights (placeholder)
            $insights = $this->extractInsights($partialTranscript);
            foreach ($insights as $insight) {
                $record = RealTimeInsight::create([
                    'session_id' => $session->id,
                    'insight_type' => $insight['type'],
                    'insight_data' => $insight['data'],
                    'confidence' => $insight['confidence'] ?? 0.80,
                    'timestamp' => now(),
                ]);

                event(new RealTimeInsightCreated(
                    $session->session_uuid,
                    $session->doctor_id,
                    [
                        'id' => $record->id,
                        'type' => $record->insight_type,
                        'data' => $record->insight_data,
                        'confidence' => (float) $record->confidence,
                    ]
                ));
            }

            // 3) Mark chunk processed
            $chunk->processed_at = now();
            $chunk->save();
        } catch (\Throwable $e) {
            Log::error('RealTimeAIService processChunk error: '.$e->getMessage(), ['chunk_id' => $chunk->id]);
        }
    }

    protected function transcribeChunk(AmbientRecordingChunk $chunk): ?string
    {
        // Prefer gpt-4o-mini-transcribe; fallback handled in client
        $filename = 'chunk_' . $chunk->id . '.webm';
        // Pass session hints if available
        $session = $chunk->session;
        $language = $session?->language;
        $diarization = (bool) ($session?->diarization_enabled);
        // Retry simple loop (service-level backoff) in addition to job retry
        $attempts = 0;
        $lastError = null;
        while ($attempts < 2) { // light retry here
            try {
                return $this->openai->transcribeAudioWithHints($chunk->chunk_data, $filename, $language, $diarization);
            } catch (\Throwable $e) {
                $lastError = $e->getMessage();
                usleep(200000); // 200ms backoff
            }
            $attempts++;
        }
        \Log::warning('Chunk transcription failed after retries', ['chunk_id' => $chunk->id, 'error' => $lastError]);
        return null;
    }

    protected function extractInsights(?string $text): array
    {
        if (!$text) return [];

        // Call GPT to extract structured insights; fall back to simple keyword detection on failure
        try {
            $prompt = "You are assisting a doctor during a live consultation. From the following partial transcript, extract at most 3 concise insights that would help a doctor document the note in real time.\n\n".
                "For each insight, provide: type (one of: symptom, diagnosis, medication, test, alert), data (JSON object with brief fields), and confidence (0-1).\n\n".
                "Text:".PHP_EOL.$text;

            $resp = app(\App\Services\OpenAIClient::class)->postToOpenAI('/chat/completions', [
                'model' => config('openai.realtime_insight_model', 'gpt-4o-mini'),
                'messages' => [
                    ['role' => 'system', 'content' => 'You extract structured medical insights suitable for real-time display. Return pure JSON array only.'],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'temperature' => 0.2,
                'response_format' => [ 'type' => 'json_object' ],
                'max_tokens' => 400,
            ]);

            $content = $resp->json('choices.0.message.content');
            if (is_string($content)) {
                $parsed = json_decode($content, true);
                // Support both array or wrapped object { insights: [...] }
                $items = [];
                if (is_array($parsed)) {
                    $items = isset($parsed['insights']) && is_array($parsed['insights']) ? $parsed['insights'] : (array_is_list($parsed) ? $parsed : []);
                }
                $clean = [];
                foreach ($items as $it) {
                    if (!isset($it['type']) || !isset($it['data'])) continue;
                    $clean[] = [
                        'type' => (string) $it['type'],
                        'data' => is_array($it['data']) ? $it['data'] : ['raw' => (string) $it['data']],
                        'confidence' => isset($it['confidence']) ? (float) $it['confidence'] : 0.75,
                    ];
                }
                if ($clean) return $clean;
            }
        } catch (\Throwable $e) {
            \Log::warning('extractInsights GPT failed: '.$e->getMessage());
        }

        // Fallback: simple keyword detector
        $insights = [];
        $lower = strtolower($text);
        if (str_contains($lower, 'pain')) {
            $insights[] = [
                'type' => 'symptom',
                'data' => ['keyword' => 'pain', 'context' => $text],
                'confidence' => 0.70,
            ];
        }
        return $insights;
    }
}
