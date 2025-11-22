<?php

namespace App\Services;

use App\Models\Claim;
use App\Models\ClearinghouseSubmission;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;
use Illuminate\Support\Carbon;
use ZipArchive;
use Exception;

class ClaimBackupService
{
    protected $backupPath = 'backups/claims';
    protected $encryptionKey;
    protected $retentionDays = 90; // Keep backups for 90 days

    public function __construct()
    {
        $this->encryptionKey = config('app.key'); // Use Laravel's app key for encryption
    }

    /**
     * Create a comprehensive backup of claims data
     */
    public function createBackup(int $hospitalId, array $options = []): array
    {
        $backupId = uniqid('claim_backup_', true);
        $timestamp = now()->format('Y-m-d_H-i-s');
        $backupPath = "{$this->backupPath}/{$hospitalId}/{$timestamp}_{$backupId}";

        try {
            Log::info('Starting claims backup', [
                'hospital_id' => $hospitalId,
                'backup_id' => $backupId,
                'timestamp' => $timestamp
            ]);

            // Create backup data
            $backupData = $this->gatherBackupData($hospitalId, $options);

            // Encrypt sensitive data
            $encryptedData = $this->encryptBackupData($backupData);

            // Save to storage
            $filePath = $this->saveBackupToStorage($encryptedData, $backupPath);

            // Create backup manifest
            $manifest = $this->createBackupManifest($backupData, $backupId, $hospitalId, $options);
            $this->saveManifestToStorage($manifest, $backupPath);

            // Verify backup integrity
            $isValid = $this->verifyBackupIntegrity($filePath, $manifest);

            if (!$isValid) {
                throw new Exception('Backup integrity verification failed');
            }

            // Clean up old backups
            $this->cleanupOldBackups($hospitalId);

            $result = [
                'success' => true,
                'backup_id' => $backupId,
                'file_path' => $filePath,
                'manifest_path' => $backupPath . '/manifest.json',
                'total_records' => $backupData['summary']['total_records'],
                'file_size' => $this->getFileSize($filePath),
                'created_at' => now(),
                'checksum' => $manifest['checksum'],
            ];

            Log::info('Claims backup completed successfully', $result);

            return $result;

        } catch (Exception $e) {
            Log::error('Claims backup failed', [
                'hospital_id' => $hospitalId,
                'backup_id' => $backupId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Clean up failed backup
            $this->cleanupFailedBackup($backupPath);

            throw $e;
        }
    }

    /**
     * Gather all relevant claims data for backup
     */
    protected function gatherBackupData(int $hospitalId, array $options): array
    {
        $dateRange = $options['date_range'] ?? null;
        $includeAttachments = $options['include_attachments'] ?? false;

        // Get claims data
        $claimsQuery = Claim::with([
            'patient:id,name,email',
            'provider:id,name,npi',
            'clearinghouseSubmission:id,batch_id,status'
        ])->where('hospital_id', $hospitalId);

        if ($dateRange) {
            $claimsQuery->whereBetween('created_at', $dateRange);
        }

        $claims = $claimsQuery->get();

        // Get clearinghouse submissions
        $submissionsQuery = ClearinghouseSubmission::with([
            'clearinghouseAccount:id,name,provider',
            'claims:id,claim_id'
        ])->whereHas('claims', function ($q) use ($hospitalId) {
            $q->where('hospital_id', $hospitalId);
        });

        if ($dateRange) {
            $submissionsQuery->whereBetween('created_at', $dateRange);
        }

        $submissions = $submissionsQuery->get();

        // Get related audit logs (last 90 days)
        $auditLogs = DB::table('audit_logs')
            ->where('hospital_id', $hospitalId)
            ->where('entity_type', 'claim')
            ->where('created_at', '>=', now()->subDays(90))
            ->get();

        return [
            'claims' => $claims->toArray(),
            'clearinghouse_submissions' => $submissions->toArray(),
            'audit_logs' => $auditLogs->toArray(),
            'metadata' => [
                'hospital_id' => $hospitalId,
                'backup_date' => now()->toISOString(),
                'database_version' => $this->getDatabaseVersion(),
                'laravel_version' => app()->version(),
                'options' => $options,
            ],
            'summary' => [
                'total_claims' => $claims->count(),
                'total_submissions' => $submissions->count(),
                'total_audit_logs' => $auditLogs->count(),
                'total_records' => $claims->count() + $submissions->count() + $auditLogs->count(),
                'date_range' => $dateRange,
            ]
        ];
    }

    /**
     * Encrypt sensitive backup data
     */
    protected function encryptBackupData(array $backupData): string
    {
        // Remove sensitive fields that shouldn't be backed up
        $sanitizedData = $this->sanitizeBackupData($backupData);

        // Convert to JSON
        $jsonData = json_encode($sanitizedData, JSON_PRETTY_PRINT);

        // Encrypt the data
        $encrypted = encrypt($jsonData);

        return $encrypted;
    }

    /**
     * Sanitize backup data for security
     */
    protected function sanitizeBackupData(array $data): array
    {
        $sanitized = $data;

        // Sanitize claims data
        if (isset($sanitized['claims'])) {
            foreach ($sanitized['claims'] as &$claim) {
                // Remove or mask sensitive fields
                $sensitiveFields = [
                    'patient_ssn', 'patient_insurance_id', 'credit_card_info',
                    'bank_account_details', 'medical_record_number'
                ];

                foreach ($sensitiveFields as $field) {
                    if (isset($claim[$field])) {
                        $claim[$field] = '[REDACTED]';
                    }
                }

                // Ensure PHI is properly handled
                if (isset($claim['patient'])) {
                    // Keep minimal patient info for restoration purposes
                    $claim['patient'] = array_intersect_key($claim['patient'], array_flip([
                        'id', 'name', 'email', 'date_of_birth'
                    ]));
                }
            }
        }

        return $sanitized;
    }

    /**
     * Save encrypted backup to storage
     */
    protected function saveBackupToStorage(string $encryptedData, string $backupPath): string
    {
        $filePath = $backupPath . '/claims_backup.enc';

        Storage::put($filePath, $encryptedData);

        return $filePath;
    }

    /**
     * Create backup manifest
     */
    protected function createBackupManifest(array $backupData, string $backupId, int $hospitalId, array $options): array
    {
        $manifest = [
            'backup_id' => $backupId,
            'hospital_id' => $hospitalId,
            'created_at' => now()->toISOString(),
            'format_version' => '1.0',
            'encryption_method' => 'laravel_encrypt',
            'summary' => $backupData['summary'],
            'options' => $options,
            'checksum' => $this->generateChecksum($backupData),
            'hipaa_compliance' => [
                'data_classification' => 'protected_health_information',
                'encryption_applied' => true,
                'retention_period_days' => $this->retentionDays,
                'access_restricted' => true,
            ]
        ];

        return $manifest;
    }

    /**
     * Save manifest to storage
     */
    protected function saveManifestToStorage(array $manifest, string $backupPath): void
    {
        $manifestPath = $backupPath . '/manifest.json';
        Storage::put($manifestPath, json_encode($manifest, JSON_PRETTY_PRINT));
    }

    /**
     * Generate checksum for backup integrity
     */
    protected function generateChecksum(array $data): string
    {
        $serialized = serialize($data);
        return hash('sha256', $serialized);
    }

    /**
     * Verify backup integrity
     */
    protected function verifyBackupIntegrity(string $filePath, array $manifest): bool
    {
        if (!Storage::exists($filePath)) {
            return false;
        }

        try {
            $encryptedData = Storage::get($filePath);
            $decryptedData = decrypt($encryptedData);
            $data = json_decode($decryptedData, true);

            $calculatedChecksum = $this->generateChecksum($data);

            return hash_equals($manifest['checksum'], $calculatedChecksum);
        } catch (Exception $e) {
            Log::error('Backup integrity verification failed', [
                'file_path' => $filePath,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Restore claims data from backup
     */
    public function restoreBackup(string $backupId, int $hospitalId, array $options = []): array
    {
        try {
            Log::info('Starting claims restore', [
                'hospital_id' => $hospitalId,
                'backup_id' => $backupId
            ]);

            // Find backup files
            $backupFiles = $this->findBackupFiles($backupId, $hospitalId);

            if (empty($backupFiles)) {
                throw new Exception("Backup {$backupId} not found for hospital {$hospitalId}");
            }

            // Load and verify manifest
            $manifest = $this->loadBackupManifest($backupFiles['manifest']);

            // Verify backup integrity
            if (!$this->verifyBackupIntegrity($backupFiles['data'], $manifest)) {
                throw new Exception('Backup integrity check failed');
            }

            // Load backup data
            $backupData = $this->loadBackupData($backupFiles['data']);

            // Perform restore
            $restoreResult = $this->performRestore($backupData, $hospitalId, $options);

            Log::info('Claims restore completed successfully', [
                'hospital_id' => $hospitalId,
                'backup_id' => $backupId,
                'restored_records' => $restoreResult
            ]);

            return [
                'success' => true,
                'backup_id' => $backupId,
                'restored_records' => $restoreResult,
                'restored_at' => now(),
            ];

        } catch (Exception $e) {
            Log::error('Claims restore failed', [
                'hospital_id' => $hospitalId,
                'backup_id' => $backupId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    /**
     * Find backup files for a specific backup ID
     */
    protected function findBackupFiles(string $backupId, int $hospitalId): array
    {
        $backupDir = "{$this->backupPath}/{$hospitalId}";

        if (!Storage::exists($backupDir)) {
            return [];
        }

        $files = Storage::files($backupDir);

        $backupFiles = [];
        foreach ($files as $file) {
            if (str_contains($file, $backupId)) {
                if (str_ends_with($file, 'manifest.json')) {
                    $backupFiles['manifest'] = $file;
                } elseif (str_ends_with($file, 'claims_backup.enc')) {
                    $backupFiles['data'] = $file;
                }
            }
        }

        return $backupFiles;
    }

    /**
     * Load backup manifest
     */
    protected function loadBackupManifest(string $manifestPath): array
    {
        $manifestContent = Storage::get($manifestPath);
        return json_decode($manifestContent, true);
    }

    /**
     * Load backup data
     */
    protected function loadBackupData(string $dataPath): array
    {
        $encryptedData = Storage::get($dataPath);
        $decryptedData = decrypt($encryptedData);
        return json_decode($decryptedData, true);
    }

    /**
     * Perform the actual restore operation
     */
    protected function performRestore(array $backupData, int $hospitalId, array $options): array
    {
        $restoreCounts = [
            'claims_restored' => 0,
            'submissions_restored' => 0,
            'audit_logs_restored' => 0,
        ];

        DB::beginTransaction();

        try {
            // Restore claims (with conflict resolution)
            if (isset($backupData['claims'])) {
                $restoreCounts['claims_restored'] = $this->restoreClaims(
                    collect($backupData['claims']),
                    $hospitalId,
                    $options
                );
            }

            // Restore clearinghouse submissions
            if (isset($backupData['clearinghouse_submissions'])) {
                $restoreCounts['submissions_restored'] = $this->restoreSubmissions(
                    collect($backupData['clearinghouse_submissions']),
                    $hospitalId,
                    $options
                );
            }

            // Restore audit logs
            if (isset($backupData['audit_logs'])) {
                $restoreCounts['audit_logs_restored'] = $this->restoreAuditLogs(
                    collect($backupData['audit_logs']),
                    $hospitalId,
                    $options
                );
            }

            DB::commit();

            return $restoreCounts;

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Restore claims with conflict resolution
     */
    protected function restoreClaims(Collection $claims, int $hospitalId, array $options): int
    {
        $restored = 0;
        $conflictResolution = $options['conflict_resolution'] ?? 'skip';

        foreach ($claims as $claimData) {
            try {
                // Check for existing claim
                $existingClaim = Claim::where('claim_id', $claimData['claim_id'])
                    ->where('hospital_id', $hospitalId)
                    ->first();

                if ($existingClaim) {
                    if ($conflictResolution === 'skip') {
                        continue;
                    } elseif ($conflictResolution === 'update') {
                        $existingClaim->update($claimData);
                        $restored++;
                    }
                    // For 'overwrite', we would delete and recreate
                } else {
                    Claim::create(array_merge($claimData, ['hospital_id' => $hospitalId]));
                    $restored++;
                }
            } catch (Exception $e) {
                Log::warning('Failed to restore claim', [
                    'claim_id' => $claimData['claim_id'] ?? 'unknown',
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $restored;
    }

    /**
     * Restore clearinghouse submissions
     */
    protected function restoreSubmissions(Collection $submissions, int $hospitalId, array $options): int
    {
        $restored = 0;

        foreach ($submissions as $submissionData) {
            try {
                ClearinghouseSubmission::create($submissionData);
                $restored++;
            } catch (Exception $e) {
                Log::warning('Failed to restore submission', [
                    'submission_id' => $submissionData['id'] ?? 'unknown',
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $restored;
    }

    /**
     * Restore audit logs
     */
    protected function restoreAuditLogs(Collection $auditLogs, int $hospitalId, array $options): int
    {
        $restored = 0;

        foreach ($auditLogs as $logData) {
            try {
                DB::table('audit_logs')->insert($logData);
                $restored++;
            } catch (Exception $e) {
                Log::warning('Failed to restore audit log', [
                    'log_id' => $logData['id'] ?? 'unknown',
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $restored;
    }

    /**
     * Clean up old backups based on retention policy
     */
    protected function cleanupOldBackups(int $hospitalId): void
    {
        $backupDir = "{$this->backupPath}/{$hospitalId}";

        if (!Storage::exists($backupDir)) {
            return;
        }

        $directories = Storage::directories($backupDir);
        $cutoffDate = now()->subDays($this->retentionDays);

        foreach ($directories as $dir) {
            // Extract timestamp from directory name
            $dirName = basename($dir);
            $timestamp = $this->extractTimestampFromDirName($dirName);

            if ($timestamp && $timestamp < $cutoffDate) {
                Log::info('Cleaning up old backup', [
                    'directory' => $dir,
                    'age_days' => $timestamp->diffInDays(now())
                ]);

                Storage::deleteDirectory($dir);
            }
        }
    }

    /**
     * Extract timestamp from backup directory name
     */
    protected function extractTimestampFromDirName(string $dirName): ?Carbon
    {
        // Directory name format: YYYY-MM-DD_HH-MM-SS_backupId
        $parts = explode('_', $dirName);
        if (count($parts) >= 2) {
            $datePart = $parts[0] . ' ' . str_replace('-', ':', $parts[1]);
            try {
                return Carbon::createFromFormat('Y-m-d H:i:s', $datePart);
            } catch (Exception $e) {
                return null;
            }
        }

        return null;
    }

    /**
     * Clean up failed backup
     */
    protected function cleanupFailedBackup(string $backupPath): void
    {
        try {
            if (Storage::exists($backupPath)) {
                Storage::deleteDirectory($backupPath);
            }
        } catch (Exception $e) {
            Log::warning('Failed to cleanup failed backup', [
                'backup_path' => $backupPath,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get database version
     */
    protected function getDatabaseVersion(): string
    {
        try {
            $version = DB::select('SELECT VERSION() as version')[0]->version;
            return $version;
        } catch (Exception $e) {
            return 'unknown';
        }
    }

    /**
     * Get file size in human readable format
     */
    protected function getFileSize(string $filePath): string
    {
        try {
            $bytes = Storage::size($filePath);
            $units = ['B', 'KB', 'MB', 'GB'];
            $bytes = max($bytes, 0);
            $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
            $pow = min($pow, count($units) - 1);
            $bytes /= pow(1024, $pow);

            return round($bytes, 2) . ' ' . $units[$pow];
        } catch (Exception $e) {
            return 'unknown';
        }
    }

    /**
     * List available backups for a hospital
     */
    public function listBackups(int $hospitalId): Collection
    {
        $backupDir = "{$this->backupPath}/{$hospitalId}";

        if (!Storage::exists($backupDir)) {
            return collect();
        }

        $directories = Storage::directories($backupDir);

        $backups = collect();

        foreach ($directories as $dir) {
            try {
                $manifestPath = $dir . '/manifest.json';

                if (Storage::exists($manifestPath)) {
                    $manifest = json_decode(Storage::get($manifestPath), true);

                    $backups->push([
                        'backup_id' => $manifest['backup_id'],
                        'created_at' => $manifest['created_at'],
                        'total_records' => $manifest['summary']['total_records'],
                        'file_size' => $this->getFileSize($dir . '/claims_backup.enc'),
                        'status' => 'available',
                        'path' => $dir,
                    ]);
                }
            } catch (Exception $e) {
                Log::warning('Failed to read backup manifest', [
                    'directory' => $dir,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $backups->sortByDesc('created_at');
    }

    /**
     * Delete a specific backup
     */
    public function deleteBackup(string $backupId, int $hospitalId): bool
    {
        $backupFiles = $this->findBackupFiles($backupId, $hospitalId);

        if (empty($backupFiles)) {
            return false;
        }

        try {
            // Delete data file
            if (isset($backupFiles['data'])) {
                Storage::delete($backupFiles['data']);
            }

            // Delete manifest
            if (isset($backupFiles['manifest'])) {
                Storage::delete($backupFiles['manifest']);
            }

            // Delete directory if empty
            $backupDir = dirname($backupFiles['data']);
            $remainingFiles = Storage::files($backupDir);
            if (empty($remainingFiles)) {
                Storage::deleteDirectory($backupDir);
            }

            Log::info('Backup deleted successfully', [
                'backup_id' => $backupId,
                'hospital_id' => $hospitalId
            ]);

            return true;

        } catch (Exception $e) {
            Log::error('Failed to delete backup', [
                'backup_id' => $backupId,
                'hospital_id' => $hospitalId,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }
}
