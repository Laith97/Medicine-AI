<?php

namespace App\Console\Commands;

use App\Models\AmbientRecordingSession;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class CleanupAmbientArtifacts extends Command
{
    protected $signature = 'ambient:cleanup {--days=14 : Delete audio files older than this many days if session is completed}';
    protected $description = 'Delete old ambient audio files and orphaned artifacts based on retention policy';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $cutoff = now()->subDays($days);
        $count = 0;

        AmbientRecordingSession::whereNotNull('audio_file_path')
            ->where('completed_at', '<', $cutoff)
            ->chunkById(200, function ($sessions) use (&$count) {
                foreach ($sessions as $session) {
                    $path = $session->audio_file_path;
                    if ($path && Storage::disk('local')->exists($path)) {
                        Storage::disk('local')->delete($path);
                        $count++;
                    }
                }
            });

        $this->info("Deleted {$count} old ambient audio files.");
        return self::SUCCESS;
    }
}
