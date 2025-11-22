<?php

namespace App\Services;

use App\Models\Waitlist;
use App\Models\WaitlistEntry;
use App\Models\WaitlistPatientPreference;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class WaitlistBackupService
{
    protected string $backupPath = 'backups/waitlist';

    /**
     * Create a full backup of waitlist data
     */
    public function createFullBackup(string $reason = 'scheduled'): array
    {
        $timestamp = now()->format('Y-m-d_H-i-s');
        $backupId = "waitlist_full_{$timestamp}";

        Log::info('Starting waitlist full backup', ['backup_id' => $backupId, 'reason' => $reason]);

        try {
            DB::beginTransaction();

            $backupData = [
                'metadata' => [
                    'backup_id' => $backupId,
                    'timestamp' => now()->toISOString(),
                    'reason' => $reason,
                    'version' => '1.0',
                ],
                'waitlists' => $this->backupWaitlists(),
                'entries' => $this->backupEntries(),
                'preferences' => $this->backupPreferences(),
            ];

            DB::commit();

            // Save to storage
            $filename = "{$backupId}.json";
            Storage::put("{$this->backupPath}/{$filename}", json_encode($backupData, JSON_PRETTY_PRINT));

            // Create compressed backup
            $this->createCompressedBackup($backupData, $backupId);

            Log::info('Waitlist full backup completed', [
                'backup_id' => $backupId,
                'waitlists_count' => count($backupData['waitlists']),
                'entries_count' => count($backupData['entries']),
                'preferences_count' => count($backupData['preferences']),
            ]);

            return [
                'success' => true,
                'backup_id' => $backupId,
                'filename' => $filename,
                'size' => count($backupData['waitlists']) + count($backupData['entries']) + count($backupData['preferences']),
            ];

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Waitlist full backup failed', [
                'backup_id' => $backupId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Create incremental backup since last backup
     */
    public function createIncrementalBackup(string $reason = 'scheduled'): array
    {
        $lastBackup = $this->getLastBackupTime();
        $timestamp = now()->format('Y-m-d_H-i-s');
        $backupId = "waitlist_incremental_{$timestamp}";

        Log::info('Starting waitlist incremental backup', [
            'backup_id' => $backupId,
            'since' => $lastBackup?->toISOString(),
            'reason' => $reason,
        ]);

        try {
            $backupData = [
                'metadata' => [
                    'backup_id' => $backupId,
                    'timestamp' => now()->toISOString(),
                    'type' => 'incremental',
                    'since' => $lastBackup?->toISOString(),
                    'reason' => $reason,
                    'version' => '1.0',
                ],
                'waitlists' => $this->backupWaitlists($lastBackup),
                'entries' => $this->backupEntries($lastBackup),
                'preferences' => $this->backupPreferences($lastBackup),
            ];

            $filename = "{$backupId}.json";
            Storage::put("{$this->backupPath}/{$filename}", json_encode($backupData, JSON_PRETTY_PRINT));

            Log::info('Waitlist incremental backup completed', [
                'backup_id' => $backupId,
                'waitlists_count' => count($backupData['waitlists']),
                'entries_count' => count($backupData['entries']),
                'preferences_count' => count($backupData['preferences']),
            ]);

            return [
                'success' => true,
                'backup_id' => $backupId,
                'filename' => $filename,
                'size' => count($backupData['waitlists']) + count($backupData['entries']) + count($backupData['preferences']),
            ];

        } catch (\Exception $e) {
            Log::error('Waitlist incremental backup failed', [
                'backup_id' => $backupId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Restore waitlist data from backup
     */
    public function restoreFromBackup(string $backupId, array $options = []): array
    {
        $restoreMode = $options['mode'] ?? 'merge'; // 'merge', 'replace', 'dry_run'

        Log::info('Starting waitlist restore', [
            'backup_id' => $backupId,
            'mode' => $restoreMode,
        ]);

        try {
            $backupData = $this->loadBackup($backupId);

            if ($restoreMode === 'dry_run') {
                return $this->simulateRestore($backupData);
            }

            DB::beginTransaction();

            $results = [
                'waitlists' => $this->restoreWaitlists($backupData['waitlists'], $restoreMode),
                'entries' => $this->restoreEntries($backupData['entries'], $restoreMode),
                'preferences' => $this->restorePreferences($backupData['preferences'], $restoreMode),
            ];

            if ($restoreMode !== 'dry_run') {
                DB::commit();
            }

            Log::info('Waitlist restore completed', [
                'backup_id' => $backupId,
                'mode' => $restoreMode,
                'results' => $results,
            ]);

            return [
                'success' => true,
                'backup_id' => $backupId,
                'mode' => $restoreMode,
                'results' => $results,
            ];

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Waitlist restore failed', [
                'backup_id' => $backupId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Backup waitlist records
     */
    private function backupWaitlists(Carbon $since = null): array
    {
        $query = Waitlist::with(['patient:id,name,email', 'doctor:id,name']);

        if ($since) {
            $query->where('updated_at', '>=', $since);
        }

        return $query->get()->map(function ($waitlist) {
            return $waitlist->toArray();
        })->toArray();
    }

    /**
     * Backup waitlist entries
     */
    private function backupEntries(Carbon $since = null): array
    {
        $query = WaitlistEntry::with(['waitlist:id,patient_id,doctor_id']);

        if ($since) {
            $query->where('updated_at', '>=', $since);
        }

        return $query->get()->map(function ($entry) {
            return $entry->toArray();
        })->toArray();
    }

    /**
     * Backup patient preferences
     */
    private function backupPreferences(Carbon $since = null): array
    {
        $query = WaitlistPatientPreference::with(['patient:id,name,email', 'doctor:id,name']);

        if ($since) {
            $query->where('updated_at', '>=', $since);
        }

        return $query->get()->map(function ($preference) {
            return $preference->toArray();
        })->toArray();
    }

    /**
     * Load backup data from storage
     */
    private function loadBackup(string $backupId): array
    {
        $filename = "{$backupId}.json";
        $path = "{$this->backupPath}/{$filename}";

        if (!Storage::exists($path)) {
            throw new \Exception("Backup file not found: {$filename}");
        }

        $content = Storage::get($path);
        $data = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception("Invalid backup file format: {$filename}");
        }

        return $data;
    }

    /**
     * Create compressed backup
     */
    private function createCompressedBackup(array $data, string $backupId): void
    {
        $jsonData = json_encode($data);
        $compressed = gzcompress($jsonData);

        $filename = "{$backupId}.json.gz";
        Storage::put("{$this->backupPath}/compressed/{$filename}", $compressed);
    }

    /**
     * Get timestamp of last backup
     */
    private function getLastBackupTime(): ?Carbon
    {
        $files = Storage::files($this->backupPath);

        if (empty($files)) {
            return null;
        }

        $backupFiles = array_filter($files, function ($file) {
            return str_contains($file, 'waitlist_') && str_ends_with($file, '.json');
        });

        if (empty($backupFiles)) {
            return null;
        }

        // Extract timestamps and find the latest
        $latestTimestamp = null;
        foreach ($backupFiles as $file) {
            if (preg_match('/waitlist_(?:full|incremental)_(\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2})\.json/', $file, $matches)) {
                $timestamp = Carbon::createFromFormat('Y-m-d_H-i-s', $matches[1]);
                if (!$latestTimestamp || $timestamp->gt($latestTimestamp)) {
                    $latestTimestamp = $timestamp;
                }
            }
        }

        return $latestTimestamp;
    }

    /**
     * Simulate restore operation
     */
    private function simulateRestore(array $backupData): array
    {
        return [
            'mode' => 'dry_run',
            'would_restore' => [
                'waitlists' => count($backupData['waitlists']),
                'entries' => count($backupData['entries']),
                'preferences' => count($backupData['preferences']),
            ],
            'conflicts' => [
                'existing_waitlists' => Waitlist::whereIn('id', collect($backupData['waitlists'])->pluck('id'))->count(),
                'existing_entries' => WaitlistEntry::whereIn('id', collect($backupData['entries'])->pluck('id'))->count(),
                'existing_preferences' => WaitlistPatientPreference::whereIn('id', collect($backupData['preferences'])->pluck('id'))->count(),
            ],
        ];
    }

    /**
     * Restore waitlist records
     */
    private function restoreWaitlists(array $waitlists, string $mode): array
    {
        $results = ['created' => 0, 'updated' => 0, 'skipped' => 0];

        foreach ($waitlists as $waitlistData) {
            $existing = Waitlist::find($waitlistData['id']);

            if ($existing && $mode === 'merge') {
                $existing->update($waitlistData);
                $results['updated']++;
            } elseif (!$existing) {
                Waitlist::create($waitlistData);
                $results['created']++;
            } else {
                $results['skipped']++;
            }
        }

        return $results;
    }

    /**
     * Restore waitlist entries
     */
    private function restoreEntries(array $entries, string $mode): array
    {
        $results = ['created' => 0, 'updated' => 0, 'skipped' => 0];

        foreach ($entries as $entryData) {
            $existing = WaitlistEntry::find($entryData['id']);

            if ($existing && $mode === 'merge') {
                $existing->update($entryData);
                $results['updated']++;
            } elseif (!$existing) {
                WaitlistEntry::create($entryData);
                $results['created']++;
            } else {
                $results['skipped']++;
            }
        }

        return $results;
    }

    /**
     * Restore patient preferences
     */
    private function restorePreferences(array $preferences, string $mode): array
    {
        $results = ['created' => 0, 'updated' => 0, 'skipped' => 0];

        foreach ($preferences as $preferenceData) {
            $existing = WaitlistPatientPreference::find($preferenceData['id']);

            if ($existing && $mode === 'merge') {
                $existing->update($preferenceData);
                $results['updated']++;
            } elseif (!$existing) {
                WaitlistPatientPreference::create($preferenceData);
                $results['created']++;
            } else {
                $results['skipped']++;
            }
        }

        return $results;
    }

    /**
     * Clean up old backups
     */
    public function cleanupOldBackups(int $keepDays = 30): array
    {
        $cutoff = now()->subDays($keepDays);
        $deleted = 0;

        $files = Storage::files($this->backupPath);

        foreach ($files as $file) {
            if (preg_match('/waitlist_(?:full|incremental)_(\d{4}-\d{2}-\d{2})_(\d{2}-\d{2}-\d{2})\.json/', $file, $matches)) {
                $fileDate = Carbon::createFromFormat('Y-m-d_H-i-s', $matches[1] . '_' . $matches[2]);

                if ($fileDate->lt($cutoff)) {
                    Storage::delete($file);
                    $deleted++;
                }
            }
        }

        Log::info('Old waitlist backups cleaned up', [
            'deleted_count' => $deleted,
            'keep_days' => $keepDays,
        ]);

        return [
            'deleted' => $deleted,
            'keep_days' => $keepDays,
        ];
    }
}
