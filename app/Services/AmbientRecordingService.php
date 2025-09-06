<?php

namespace App\Services;

use App\Models\AmbientRecordingSession;
use App\Models\AmbientRecordingChunk;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class AmbientRecordingService
{
    /**
     * Start a new ambient recording session
     */
    public function start(int $doctorId, int $patientId, ?int $appointmentId = null, ?string $language = null, bool $diarization = false): AmbientRecordingSession
    {
        return AmbientRecordingSession::create([
            'doctor_id' => $doctorId,
            'patient_id' => $patientId,
            'appointment_id' => $appointmentId,
            'session_uuid' => (string) Str::uuid(),
            'status' => 'active',
            'started_at' => now(),
            'language' => $language,
            'diarization_enabled' => $diarization,
        ]);
    }

    /** Pause a running session */
    public function pause(AmbientRecordingSession $session): AmbientRecordingSession
    {
        $session->update([
            'status' => 'paused',
            'paused_at' => now(),
        ]);
        return $session;
    }

    /** Resume a paused session */
    public function resume(AmbientRecordingSession $session): AmbientRecordingSession
    {
        $session->update([
            'status' => 'active',
            'paused_at' => null,
        ]);
        return $session;
    }

    /** Complete a session and merge stored chunks to a single robust file */
    public function complete(AmbientRecordingSession $session): AmbientRecordingSession
    {
        // Merge chunks to storage/app/ambient/{uuid}.webm
        $basePath = "ambient/{$session->session_uuid}";
        $webmPath = $basePath . '.webm';
        $fullWebm = storage_path('app/'.$webmPath);
        $dir = dirname($fullWebm);
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        // Stream merge chunks (container may be invalid for some browsers)
        $handle = fopen($fullWebm, 'w');
        foreach ($session->chunks()->orderBy('recorded_at')->cursor() as $chunk) {
            fwrite($handle, $chunk->chunk_data);
        }
        fclose($handle);

        // Try to transcode to m4a using ffmpeg for robust playback/transcription
        $finalPath = $webmPath; // default
        $ffmpeg = trim((string) shell_exec('ffmpeg -version 2>NUL 1>NUL & echo %ERRORLEVEL%')); // windows note: won't output version here
        $ffmpegBin = 'ffmpeg';
        // Best-effort: attempt transcode; if not available, keep webm
        try {
            $m4aPath = $basePath . '.m4a';
            $fullM4a = storage_path('app/'.$m4aPath);
            // -y overwrite, -i input, -vn drop video, -c:a aac encode
            $cmd = escapeshellcmd($ffmpegBin) . ' -y -i ' . escapeshellarg($fullWebm) . ' -vn -c:a aac ' . escapeshellarg($fullM4a) . ' 2>&1';
            @shell_exec($cmd);
            if (is_file($fullM4a) && filesize($fullM4a) > 0) {
                $finalPath = $m4aPath;
                // Optionally remove original
                // @unlink($fullWebm);
            }
        } catch (\Throwable $e) {
            // ignore, keep webm
        }

        $session->update([
            'status' => 'completed',
            'completed_at' => now(),
            'audio_file_path' => $finalPath,
        ]);

        return $session;
    }

    /** Store an incoming audio chunk */
    public function storeChunk(AmbientRecordingSession $session, string $binary, int $duration, ?string $recordedAt = null): AmbientRecordingChunk
    {
        $chunk = new AmbientRecordingChunk([
            'duration' => $duration,
            'recorded_at' => $recordedAt ? \Carbon\Carbon::parse($recordedAt) : now(),
        ]);
        $chunk->chunk_data = $binary; // BLOB
        $session->chunks()->save($chunk);
        $session->increment('audio_duration', $duration);

        // Batch processing: dispatch one batch job per short window (avoid queue flood)
        $lockKey = 'ambient:batch-dispatch:session:'.$session->id;
        if (Cache::add($lockKey, 1, now()->addSeconds(2))) {
            \App\Jobs\ProcessAmbientChunksBatch::dispatch($session->id)->onQueue('processing');
        }

        return $chunk;
    }
}
