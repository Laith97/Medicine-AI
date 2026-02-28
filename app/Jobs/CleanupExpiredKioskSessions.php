<?php

namespace App\Jobs;

use App\Models\KioskSession;
use App\Models\Notification;
use App\Notifications\KioskSessionTimeout;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification as NotificationFacade;

class CleanupExpiredKioskSessions implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Starting cleanup of expired kiosk sessions');

        // Find expired active sessions (older than 30 minutes)
        $expiredSessions = KioskSession::active()
            ->where('start_time', '<', now()->subMinutes(30))
            ->with(['kiosk', 'checkins', 'payments'])
            ->get();

        $cleanedCount = 0;

        foreach ($expiredSessions as $session) {
            try {
                // End the session as abandoned
                $session->end('abandoned');

                // Send notification about session timeout
                $this->sendSessionTimeoutNotification($session);

                $cleanedCount++;

                Log::info('Cleaned up expired kiosk session', [
                    'session_id' => $session->session_id,
                    'kiosk_id' => $session->kiosk_id,
                    'duration_minutes' => $session->getDurationInMinutes(),
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to cleanup kiosk session', [
                    'session_id' => $session->session_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info("Completed cleanup of expired kiosk sessions: {$cleanedCount} sessions cleaned up");

        // Also check for offline kiosks and send notifications
        $this->checkOfflineKiosks();
    }

    /**
     * Send notification about session timeout
     */
    private function sendSessionTimeoutNotification(KioskSession $session): void
    {
        try {
            // Get admin users to notify
            $adminUsers = \App\Models\User::where('role', 'admin')
                ->orWhere('role', 'hospital_admin')
                ->get();

            if ($adminUsers->isNotEmpty()) {
                NotificationFacade::send($adminUsers, new KioskSessionTimeout($session));
            }
        } catch (\Exception $e) {
            Log::error('Failed to send session timeout notification', [
                'session_id' => $session->session_id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Check for offline kiosks and send notifications
     */
    private function checkOfflineKiosks(): void
    {
        try {
            $offlineKiosks = \App\Models\Kiosk::active()
                ->where('last_ping', '<', now()->subMinutes(10))
                ->get();

            foreach ($offlineKiosks as $kiosk) {
                // Check if we already sent a notification recently (within last hour)
                $recentNotification = Notification::where('type', 'kiosk_offline')
                    ->where('data->kiosk_id', $kiosk->id)
                    ->where('created_at', '>', now()->subHour())
                    ->exists();

                if (!$recentNotification) {
                    // Send offline notification
                    $adminUsers = \App\Models\User::where('role', 'admin')
                        ->orWhere('role', 'hospital_admin')
                        ->get();

                    if ($adminUsers->isNotEmpty()) {
                        NotificationFacade::send($adminUsers, new \App\Notifications\KioskOffline($kiosk));
                    }

                    Log::warning('Kiosk detected as offline', [
                        'kiosk_id' => $kiosk->id,
                        'serial_number' => $kiosk->serial_number,
                        'last_ping' => $kiosk->last_ping,
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to check offline kiosks', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
