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
use Illuminate\Support\Facades\Log;

class ProcessAmbientChunksBatch implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = [5, 15, 45];

    public function __construct(public int $sessionId)
    {
        $this->onQueue('processing');
    }

    public function handle(RealTimeAIService $service = null): void
    {
        /** @var AmbientRecordingSession|null $session */
        $session = AmbientRecordingSession::find($this->sessionId);
        if (!$session) {
            return;
        }

        // Resolve service if not passed (container usage when dispatched normally)
        $service = $service ?: app(RealTimeAIService::class);

        try {
            // Process up to 10 unprocessed chunks per run
            $chunks = AmbientRecordingChunk::where('session_id', $session->id)
                ->whereNull('processed_at')
                ->orderBy('recorded_at')
                ->limit(10)
                ->get();

            if ($chunks->isEmpty()) {
                return; // nothing to do
            }

            foreach ($chunks as $chunk) {
                $service->processChunk($session, $chunk);
            }

            // If there may be more, re-dispatch to continue processing soon
            $moreExists = AmbientRecordingChunk::where('session_id', $session->id)
                ->whereNull('processed_at')
                ->exists();
            if ($moreExists) {
                self::dispatch($session->id)->onQueue('processing');
            }
        } catch (\Throwable $e) {
            Log::error('ProcessAmbientChunksBatch failed: '.$e->getMessage(), ['session_id' => $session->id]);
            $this->fail($e);
        }
    }
}
