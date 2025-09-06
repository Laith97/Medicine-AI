<?php

namespace App\Jobs;

use App\Models\AmbientRecordingSession;
use App\Models\AmbientRecordingChunk;
use App\Services\RealTimeAIService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessAmbientChunk implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Retry/backoff policy for transient failures.
     */
    public $tries = 3;
    public $backoff = [5, 15, 45];

    public int $chunkId;

    public function __construct(int $chunkId)
    {
        $this->chunkId = $chunkId;
    }

    public function handle(RealTimeAIService $ai): void
    {
        /** @var AmbientRecordingChunk $chunk */
        $chunk = AmbientRecordingChunk::find($this->chunkId);
        if (!$chunk) return;

        // Skip if already processed (idempotence)
        if ($chunk->processed_at) {
            return;
        }

        /** @var AmbientRecordingSession $session */
        $session = $chunk->session;
        if (!$session) return;

        // Process the chunk and emit insights/transcription updates
        $ai->processChunk($session, $chunk);
    }
}
