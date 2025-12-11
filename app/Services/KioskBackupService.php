<?php

namespace App\Services;

use App\Models\Kiosk;
use App\Models\KioskSession;
use App\Models\KioskCheckin;
use App\Models\KioskPayment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class KioskBackupService
{
    /**
     * Create a backup of kiosk data
     */
    public static function createBackup(int $kioskId = null, string $backupType = 'full'): array
    {
        try {
            $timestamp = now()->format('Y-m-d_H-i-s');
            $backupId = 'kiosk_backup_' . ($kioskId ? $kioskId . '_' : 'all_') . $timestamp;

            $backupData = [
                'backup_id' => $backupId,
                'backup_type' => $backupType,
                'kiosk_id' => $kioskId,
                'created_at' => now()->toISOString(),
                'version' => '1.0',
                'data' => []
            ];

            DB::beginTransaction();

            if ($backupType === 'full' || !$kioskId) {
                // Backup all kiosk-related data
                $backupData['data'] = [
                    'kiosks' => self::backupKiosks($kioskId),
                    'kiosk_sessions' => self::backupKioskSessions($kioskId),
                    'kiosk_checkins' => self::backupKioskCheckins($kioskId),
                    'kiosk_payments' => self::backupKioskPayments($kioskId),
                    'doctor_kiosk_configs' => self::backupDoctorKioskConfigs($kioskId),
                    'performance_metrics' => self::backupPerformanceMetrics($kioskId),
                ];
            } elseif ($backupType === 'config') {
                // Backup only configuration data
                $backupData['data'] = [
                    'kiosks' => self::backupKiosks($kioskId),
                    'doctor_kiosk_configs' => self::backupDoctorKioskConfigs($kioskId),
                ];
            } elseif ($backupType === 'sessions') {
                // Backup only session data
                $backupData['data'] = [
                    'kiosk_sessions' => self::backupKioskSessions($kioskId),
                    'kiosk_checkins' => self::backupKioskCheckins($kioskId),
                    'kiosk_payments' => self::backupKioskPayments($kioskId),
                ];
            }

            DB::commit();

            // Save backup to storage
            $fileName = $backupId . '.json';
            Storage::disk('backups')->put($fileName, json_encode($backupData, JSON_PRETTY_PRINT));

            // Log successful backup
            Log::info('Kiosk backup created successfully', [
                'backup_id' => $backupId,
                'backup_type' => $backupType,
                'kiosk_id' => $kioskId,
                'file_size' => strlen(json_encode($backupData)),
            ]);

            return [
                'success' => true,
                'backup_id' => $backupId,
                'file_name' => $fileName,
                'backup_type' => $backupType,
                'created_at' => $backupData['created_at'],
                'record_counts' => self::getRecordCounts($backupData['data']),
            ];

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Kiosk backup failed', [
                'kiosk_id' => $kioskId,
                'backup_type' => $backupType,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Restore kiosk data from backup
     */
    public static function restoreBackup(string $backupId, array $options = []): array
    {
        try {
            $fileName = $backupId . '.json';

            if (!Storage::disk('backups')->exists($fileName)) {
                throw new \Exception("Backup file not found: {$fileName}");
            }

            $backupData = json_decode(Storage::disk('backups')->get($fileName), true);

            if (!$backupData) {
                throw new \Exception("Invalid backup file format");
            }

            DB::beginTransaction();

            $restoreResults = [
                'kiosks_restored' => 0,
                'sessions_restored' => 0,
                'checkins_restored' => 0,
                'payments_restored' => 0,
                'configs_restored' => 0,
            ];

            // Restore data based on backup type and options
            $restoreOptions = array_merge([
                'overwrite_existing' => false,
                'skip_validation' => false,
            ], $options);

            if (isset($backupData['data']['kiosks'])) {
                $restoreResults['kiosks_restored'] = self::restoreKiosks($backupData['data']['kiosks'], $restoreOptions);
            }

            if (isset($backupData['data']['doctor_kiosk_configs'])) {
                $restoreResults['configs_restored'] = self::restoreDoctorKioskConfigs($backupData['data']['doctor_kiosk_configs'], $restoreOptions);
            }

            if (isset($backupData['data']['kiosk_sessions'])) {
                $restoreResults['sessions_restored'] = self::restoreKioskSessions($backupData['data']['kiosk_sessions'], $restoreOptions);
            }

            if (isset($backupData['data']['kiosk_checkins'])) {
                $restoreResults['checkins_restored'] = self::restoreKioskCheckins($backupData['data']['kiosk_checkins'], $restoreOptions);
            }

            if (isset($backupData['data']['kiosk_payments'])) {
                $restoreResults['payments_restored'] = self::restoreKioskPayments($backupData['data']['kiosk_payments'], $restoreOptions);
            }

            DB::commit();

            Log::info('Kiosk backup restored successfully', [
                'backup_id' => $backupId,
                'restore_results' => $restoreResults,
                'options' => $restoreOptions,
            ]);

            return [
                'success' => true,
                'backup_id' => $backupId,
                'restored_at' => now()->toISOString(),
                'results' => $restoreResults,
            ];

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Kiosk backup restoration failed', [
                'backup_id' => $backupId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * List available backups
     */
    public static function listBackups(int $kioskId = null): array
    {
        $files = Storage::disk('backups')->files();

        $backups = [];
        foreach ($files as $file) {
            if (str_starts_with($file, 'kiosk_backup_') && str_ends_with($file, '.json')) {
                try {
                    $backupData = json_decode(Storage::disk('backups')->get($file), true);

                    if ($backupData) {
                        // Filter by kiosk if specified
                        if ($kioskId && $backupData['kiosk_id'] != $kioskId) {
                            continue;
                        }

                        $backups[] = [
                            'backup_id' => $backupData['backup_id'],
                            'file_name' => $file,
                            'backup_type' => $backupData['backup_type'],
                            'kiosk_id' => $backupData['kiosk_id'],
                            'created_at' => $backupData['created_at'],
                            'record_counts' => self::getRecordCounts($backupData['data']),
                            'file_size' => Storage::disk('backups')->size($file),
                        ];
                    }
                } catch (\Exception $e) {
                    Log::warning('Failed to read backup file', [
                        'file' => $file,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        // Sort by creation date (newest first)
        usort($backups, function($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });

        return $backups;
    }

    /**
     * Delete a backup
     */
    public static function deleteBackup(string $backupId): bool
    {
        try {
            $fileName = $backupId . '.json';

            if (!Storage::disk('backups')->exists($fileName)) {
                return false;
            }

            Storage::disk('backups')->delete($fileName);

            Log::info('Kiosk backup deleted', ['backup_id' => $backupId]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to delete kiosk backup', [
                'backup_id' => $backupId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Clean up old backups (keep only recent ones)
     */
    public static function cleanupOldBackups(int $keepDays = 30, int $maxBackups = 50): array
    {
        $backups = self::listBackups();
        $deletedCount = 0;
        $errors = [];

        // Sort by date (oldest first)
        usort($backups, function($a, $b) {
            return strtotime($a['created_at']) - strtotime($b['created_at']);
        });

        $cutoffDate = now()->subDays($keepDays);

        foreach ($backups as $backup) {
            $backupDate = Carbon::parse($backup['created_at']);

            // Delete if older than cutoff or if we have too many backups
            if ($backupDate->isBefore($cutoffDate) || count($backups) - $deletedCount > $maxBackups) {
                try {
                    if (self::deleteBackup($backup['backup_id'])) {
                        $deletedCount++;
                    }
                } catch (\Exception $e) {
                    $errors[] = [
                        'backup_id' => $backup['backup_id'],
                        'error' => $e->getMessage(),
                    ];
                }
            }
        }

        return [
            'deleted_count' => $deletedCount,
            'errors' => $errors,
            'remaining_backups' => count($backups) - $deletedCount,
        ];
    }

    // Private helper methods for data backup/restore

    private static function backupKiosks(?int $kioskId): array
    {
        $query = Kiosk::query();
        if ($kioskId) {
            $query->where('id', $kioskId);
        }
        return $query->get()->toArray();
    }

    private static function backupKioskSessions(?int $kioskId): array
    {
        $query = KioskSession::with(['kiosk', 'checkins', 'payments']);
        if ($kioskId) {
            $query->where('kiosk_id', $kioskId);
        }
        return $query->get()->toArray();
    }

    private static function backupKioskCheckins(?int $kioskId): array
    {
        $query = KioskCheckin::with(['appointment', 'kioskSession']);
        if ($kioskId) {
            $query->whereHas('kioskSession', function($q) use ($kioskId) {
                $q->where('kiosk_id', $kioskId);
            });
        }
        return $query->get()->toArray();
    }

    private static function backupKioskPayments(?int $kioskId): array
    {
        $query = KioskPayment::with(['appointment', 'kioskSession']);
        if ($kioskId) {
            $query->whereHas('kioskSession', function($q) use ($kioskId) {
                $q->where('kiosk_id', $kioskId);
            });
        }
        return $query->get()->toArray();
    }

    private static function backupDoctorKioskConfigs(?int $kioskId): array
    {
        $query = DB::table('doctor_kiosk_configs');
        if ($kioskId) {
            // This is a bit tricky since configs are linked to doctors, not kiosks
            // We'll backup all configs for now
        }
        return $query->get()->toArray();
    }

    private static function backupPerformanceMetrics(?int $kioskId): array
    {
        if ($kioskId) {
            return [KioskPerformanceMonitor::exportMetrics($kioskId)];
        }

        // For full backup, we'd need to get all kiosk IDs and export their metrics
        // This is simplified for now
        return [];
    }

    private static function restoreKiosks(array $kioskData, array $options): int
    {
        $restored = 0;
        foreach ($kioskData as $kiosk) {
            try {
                if ($options['overwrite_existing']) {
                    Kiosk::updateOrCreate(
                        ['id' => $kiosk['id']],
                        $kiosk
                    );
                } else {
                    Kiosk::create($kiosk);
                }
                $restored++;
            } catch (\Exception $e) {
                Log::warning('Failed to restore kiosk', [
                    'kiosk_id' => $kiosk['id'] ?? null,
                    'error' => $e->getMessage(),
                ]);
            }
        }
        return $restored;
    }

    private static function restoreDoctorKioskConfigs(array $configData, array $options): int
    {
        $restored = 0;
        foreach ($configData as $config) {
            try {
                DB::table('doctor_kiosk_configs')->updateOrInsert(
                    ['doctor_id' => $config['doctor_id']],
                    $config
                );
                $restored++;
            } catch (\Exception $e) {
                Log::warning('Failed to restore kiosk config', [
                    'doctor_id' => $config['doctor_id'] ?? null,
                    'error' => $e->getMessage(),
                ]);
            }
        }
        return $restored;
    }

    private static function restoreKioskSessions(array $sessionData, array $options): int
    {
        $restored = 0;
        foreach ($sessionData as $session) {
            try {
                KioskSession::updateOrCreate(
                    ['session_id' => $session['session_id']],
                    $session
                );
                $restored++;
            } catch (\Exception $e) {
                Log::warning('Failed to restore kiosk session', [
                    'session_id' => $session['session_id'] ?? null,
                    'error' => $e->getMessage(),
                ]);
            }
        }
        return $restored;
    }

    private static function restoreKioskCheckins(array $checkinData, array $options): int
    {
        $restored = 0;
        foreach ($checkinData as $checkin) {
            try {
                KioskCheckin::updateOrCreate(
                    ['id' => $checkin['id']],
                    $checkin
                );
                $restored++;
            } catch (\Exception $e) {
                Log::warning('Failed to restore kiosk checkin', [
                    'checkin_id' => $checkin['id'] ?? null,
                    'error' => $e->getMessage(),
                ]);
            }
        }
        return $restored;
    }

    private static function restoreKioskPayments(array $paymentData, array $options): int
    {
        $restored = 0;
        foreach ($paymentData as $payment) {
            try {
                KioskPayment::updateOrCreate(
                    ['id' => $payment['id']],
                    $payment
                );
                $restored++;
            } catch (\Exception $e) {
                Log::warning('Failed to restore kiosk payment', [
                    'payment_id' => $payment['id'] ?? null,
                    'error' => $e->getMessage(),
                ]);
            }
        }
        return $restored;
    }

    private static function getRecordCounts(array $data): array
    {
        $counts = [];
        foreach ($data as $table => $records) {
            $counts[$table] = is_array($records) ? count($records) : 0;
        }
        return $counts;
    }
}
