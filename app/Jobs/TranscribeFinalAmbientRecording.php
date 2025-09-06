<?php

namespace App\Jobs;

use App\Models\AmbientRecordingSession;
use App\Services\OpenAIClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class TranscribeFinalAmbientRecording implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = [10, 30, 90];

    public int $sessionId;

    public function __construct(int $sessionId)
    {
        $this->sessionId = $sessionId;
    }

    public function handle(OpenAIClient $openai): void
    {
        /** @var AmbientRecordingSession $session */
        $session = AmbientRecordingSession::find($this->sessionId);
        if (!$session || !$session->audio_file_path) return;

        $fullPath = storage_path('app/' . $session->audio_file_path);
        if (!is_file($fullPath)) return;

        try {
            $binary = file_get_contents($fullPath);
            $finalText = $openai->transcribeAudioWithHints(
                $binary,
                basename($fullPath),
                $session->language,
                (bool) $session->diarization_enabled
            );
            if ($finalText) {
                $session->transcription = trim($finalText);
                $session->save();

                // Cleanup: delete per-chunk rows for this session to reduce storage
                try {
                    $session->chunks()->delete();
                } catch (\Throwable $e) {
                    Log::warning('Failed deleting session chunks after final transcript', ['session_id' => $session->id, 'error' => $e->getMessage()]);
                }

                // Broadcast final transcript for UI
                event(new \\App\\Events\\AmbientSessionUpdated(
                    $session->session_uuid,
                    $session->doctor_id,
                    [
                        'type' => 'final_transcription',
                        'text' => $finalText,
                    ]
                ));
            } else {
                // Broadcast failure for UI feedback
                event(new \\App\\Events\\AmbientSessionUpdated(
                    $session->session_uuid,
                    $session->doctor_id,
                    [
                        'type' => 'final_transcription_error',
                        'message' => 'Transcription failed. Please retry.',
                    ]
                ));
            }
        } catch (\Throwable $e) {
            Log::error('Final transcription job failed: '.$e->getMessage(), ['session_id' => $session->id]);
            $this->fail($e);
        }
    }
}
