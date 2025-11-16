<?php

namespace App\Services;

use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\User;
use App\Services\AuditLoggingService;
use App\Services\ComplianceMonitoringService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class DocumentVersionControlService
{
    protected ComplianceMonitoringService $complianceService;

    public function __construct(ComplianceMonitoringService $complianceService)
    {
        $this->complianceService = $complianceService;
    }

    /**
     * Create a new version of a document
     */
    public function createVersion(
        Document $document,
        string $newContent,
        User $user,
        string $changeReason,
        array $metadata = []
    ): DocumentVersion {
        try {
            // Get current content for comparison
            $currentContent = $this->getDocumentContent($document);

            // Calculate changes
            $changes = $this->calculateContentChanges($currentContent, $newContent);

            // Create version record
            $version = DocumentVersion::create([
                'document_id' => $document->id,
                'version_number' => $this->getNextVersionNumber($document),
                'content' => $this->shouldStoreContentInVersion($document) ? $newContent : null,
                'content_hash' => hash('sha256', $newContent),
                'changes_summary' => $changes['summary'],
                'change_reason' => $changeReason,
                'changed_by' => $user->id,
                'metadata' => array_merge($metadata, [
                    'change_type' => $this->determineChangeType($changes),
                    'content_length' => strlen($newContent),
                    'word_count' => str_word_count($newContent),
                    'lines_changed' => $changes['lines_changed'],
                    'characters_changed' => $changes['characters_changed'],
                ]),
                'compliance_data' => [
                    'version_compliant' => true, // Will be validated
                    'audit_trail_maintained' => true,
                    'change_authorized' => $this->isChangeAuthorized($document, $user, $changeReason),
                ],
            ]);

            // Store content if needed (could be in files, external storage, etc.)
            if ($this->shouldStoreContentExternally($document)) {
                $this->storeVersionContent($version, $newContent);
            }

            // Update document with new content and version info
            $this->updateDocumentWithVersion($document, $version, $newContent);

            // Log version creation
            AuditLoggingService::logComplianceAudit('document_version_created', $document->id, [
                'version_id' => $version->id,
                'version_number' => $version->version_number,
                'changed_by' => $user->id,
                'change_reason' => $changeReason,
                'changes_summary' => $changes['summary'],
            ]);

            // Trigger compliance monitoring
            $this->complianceService->monitorDocumentVersioning($document, $version);

            return $version;

        } catch (\Exception $e) {
            Log::error('Document version creation failed', [
                'document_id' => $document->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            throw new \RuntimeException('Failed to create document version: ' . $e->getMessage());
        }
    }

    /**
     * Restore a document to a specific version
     */
    public function restoreVersion(Document $document, DocumentVersion $version, User $user, string $reason): DocumentVersion
    {
        try {
            // Get the content for the version to restore
            $restoreContent = $this->getVersionContent($version);

            // Create a new version with the restored content
            $restoreVersion = $this->createVersion(
                $document,
                $restoreContent,
                $user,
                "Restored to version {$version->version_number}: {$reason}",
                [
                    'restored_from_version' => $version->id,
                    'original_version_number' => $version->version_number,
                    'restore_reason' => $reason,
                ]
            );

            // Log restoration
            AuditLoggingService::logComplianceAudit('document_version_restored', $document->id, [
                'restored_version_id' => $version->id,
                'new_version_id' => $restoreVersion->id,
                'restored_by' => $user->id,
                'restore_reason' => $reason,
            ]);

            return $restoreVersion;

        } catch (\Exception $e) {
            Log::error('Document version restoration failed', [
                'document_id' => $document->id,
                'version_id' => $version->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            throw new \RuntimeException('Failed to restore document version: ' . $e->getMessage());
        }
    }

    /**
     * Compare two versions of a document
     */
    public function compareVersions(DocumentVersion $version1, DocumentVersion $version2): array
    {
        $content1 = $this->getVersionContent($version1);
        $content2 = $this->getVersionContent($version2);

        $differences = $this->calculateDetailedDifferences($content1, $content2);

        return [
            'version_1' => [
                'id' => $version1->id,
                'version_number' => $version1->version_number,
                'created_at' => $version1->created_at,
                'changed_by' => $version1->changed_by,
                'change_reason' => $version1->change_reason,
            ],
            'version_2' => [
                'id' => $version2->id,
                'version_number' => $version2->version_number,
                'created_at' => $version2->created_at,
                'changed_by' => $version2->changed_by,
                'change_reason' => $version2->change_reason,
            ],
            'differences' => $differences,
            'summary' => [
                'lines_added' => $differences['lines_added'],
                'lines_removed' => $differences['lines_removed'],
                'lines_modified' => $differences['lines_modified'],
                'total_changes' => $differences['total_changes'],
            ],
        ];
    }

    /**
     * Get version history for a document
     */
    public function getVersionHistory(Document $document, array $options = []): Collection
    {
        $query = DocumentVersion::where('document_id', $document->id)
            ->with('user')
            ->orderBy('version_number', 'desc');

        // Apply filters
        if (isset($options['user_id'])) {
            $query->where('changed_by', $options['user_id']);
        }

        if (isset($options['date_from'])) {
            $query->where('created_at', '>=', $options['date_from']);
        }

        if (isset($options['date_to'])) {
            $query->where('created_at', '<=', $options['date_to']);
        }

        if (isset($options['change_type'])) {
            $query->where('metadata->change_type', $options['change_type']);
        }

        $versions = $query->get();

        // Add additional metadata
        return $versions->map(function ($version) use ($document) {
            $isCurrent = $version->version_number === $document->current_version;
            $isLatest = $version->version_number === $this->getLatestVersionNumber($document);

            return array_merge($version->toArray(), [
                'is_current_version' => $isCurrent,
                'is_latest_version' => $isLatest,
                'time_since_change' => $version->created_at->diffForHumans(),
                'change_impact' => $this->assessChangeImpact($version),
            ]);
        });
    }

    /**
     * Get audit trail for document changes
     */
    public function getAuditTrail(Document $document, array $options = []): array
    {
        $versions = $this->getVersionHistory($document, $options);

        $auditTrail = [
            'document_id' => $document->id,
            'total_versions' => $versions->count(),
            'current_version' => $document->current_version,
            'version_history' => $versions->map(function ($version) {
                return [
                    'version_number' => $version['version_number'],
                    'timestamp' => $version['created_at'],
                    'user' => $version['user']?->name ?? 'Unknown',
                    'change_reason' => $version['change_reason'],
                    'change_type' => $version['metadata']['change_type'] ?? 'unknown',
                    'compliance_status' => $version['compliance_data']['version_compliant'] ?? false,
                    'content_hash' => $version['content_hash'],
                ];
            })->toArray(),
        ];

        // Add compliance summary
        $auditTrail['compliance_summary'] = $this->generateComplianceSummary($versions);

        // Add change patterns
        $auditTrail['change_patterns'] = $this->analyzeChangePatterns($versions);

        return $auditTrail;
    }

    /**
     * Validate version integrity
     */
    public function validateVersionIntegrity(DocumentVersion $version): array
    {
        $issues = [];

        // Check content hash integrity
        $currentContent = $this->getVersionContent($version);
        $calculatedHash = hash('sha256', $currentContent);

        if ($calculatedHash !== $version->content_hash) {
            $issues[] = 'Content hash mismatch - version integrity compromised';
        }

        // Check metadata consistency
        if (empty($version->changed_by)) {
            $issues[] = 'Missing user information for version change';
        }

        if (empty($version->change_reason)) {
            $issues[] = 'Missing change reason for version';
        }

        // Check compliance data
        $complianceData = $version->compliance_data ?? [];
        if (!isset($complianceData['version_compliant'])) {
            $issues[] = 'Missing compliance validation for version';
        }

        // Check version number sequence
        $expectedVersion = $this->getExpectedVersionNumber($version);
        if ($version->version_number !== $expectedVersion) {
            $issues[] = "Version number {$version->version_number} is out of sequence (expected {$expectedVersion})";
        }

        return [
            'is_integrity_maintained' => empty($issues),
            'issues' => $issues,
            'validation_timestamp' => now(),
            'content_hash_valid' => $calculatedHash === $version->content_hash,
            'metadata_complete' => !empty($version->changed_by) && !empty($version->change_reason),
        ];
    }

    /**
     * Archive old versions
     */
    public function archiveOldVersions(Document $document, int $keepVersions = 10, User $user): array
    {
        $versions = $this->getVersionHistory($document);
        $versionsToArchive = $versions->skip($keepVersions);

        $archivedCount = 0;
        $errors = [];

        foreach ($versionsToArchive as $version) {
            try {
                $this->archiveVersion($version, $user);
                $archivedCount++;
            } catch (\Exception $e) {
                $errors[] = "Failed to archive version {$version['version_number']}: {$e->getMessage()}";
            }
        }

        // Log archiving activity
        AuditLoggingService::logComplianceAudit('document_versions_archived', $document->id, [
            'archived_by' => $user->id,
            'versions_archived' => $archivedCount,
            'versions_kept' => $keepVersions,
            'errors' => $errors,
        ]);

        return [
            'success' => empty($errors),
            'versions_archived' => $archivedCount,
            'errors' => $errors,
            'archived_at' => now(),
        ];
    }

    /**
     * Export version history
     */
    public function exportVersionHistory(Document $document, string $format = 'json', array $options = []): string
    {
        $history = $this->getAuditTrail($document, $options);

        switch ($format) {
            case 'json':
                return json_encode($history, JSON_PRETTY_PRINT);
            case 'csv':
                return $this->exportAsCsv($history);
            case 'pdf':
                return $this->exportAsPdf($history);
            default:
                throw new \InvalidArgumentException("Unsupported export format: {$format}");
        }
    }

    /**
     * Get version statistics
     */
    public function getVersionStatistics(Document $document): array
    {
        $versions = $this->getVersionHistory($document);

        $stats = [
            'total_versions' => $versions->count(),
            'current_version' => $document->current_version,
            'oldest_version' => $versions->min('version_number'),
            'newest_version' => $versions->max('version_number'),
            'average_changes_per_version' => $versions->avg(function ($version) {
                return $version['metadata']['lines_changed'] ?? 0;
            }),
        ];

        // User activity stats
        $userStats = $versions->groupBy('changed_by')->map(function ($userVersions) {
            return [
                'versions_count' => $userVersions->count(),
                'last_activity' => $userVersions->max('created_at'),
                'change_types' => $userVersions->pluck('metadata.change_type')->filter()->unique()->values(),
            ];
        });

        $stats['user_activity'] = $userStats;

        // Change type distribution
        $changeTypes = $versions->pluck('metadata.change_type')->filter()->countBy();
        $stats['change_type_distribution'] = $changeTypes;

        // Time-based statistics
        $stats['version_frequency'] = $this->calculateVersionFrequency($versions);

        return $stats;
    }

    /**
     * Get next version number for a document
     */
    protected function getNextVersionNumber(Document $document): int
    {
        return ($document->current_version ?? 0) + 1;
    }

    /**
     * Get latest version number
     */
    protected function getLatestVersionNumber(Document $document): int
    {
        return DocumentVersion::where('document_id', $document->id)
            ->max('version_number') ?? 1;
    }

    /**
     * Get expected version number for validation
     */
    protected function getExpectedVersionNumber(DocumentVersion $version): int
    {
        $previousVersion = DocumentVersion::where('document_id', $version->document_id)
            ->where('version_number', '<', $version->version_number)
            ->max('version_number') ?? 0;

        return $previousVersion + 1;
    }

    /**
     * Calculate content changes between versions
     */
    protected function calculateContentChanges(string $oldContent, string $newContent): array
    {
        $oldLines = explode("\n", $oldContent);
        $newLines = explode("\n", $newContent);

        $oldLineCount = count($oldLines);
        $newLineCount = count($newLines);
        $lineDifference = $newLineCount - $oldLineCount;

        $oldChars = strlen($oldContent);
        $newChars = strlen($newContent);
        $charDifference = $newChars - $oldChars;

        // Simple diff calculation (could be enhanced with proper diff library)
        $changes = abs($lineDifference) + abs($charDifference) / 100;

        return [
            'summary' => $this->generateChangeSummary($lineDifference, $charDifference),
            'lines_changed' => abs($lineDifference),
            'characters_changed' => abs($charDifference),
            'change_magnitude' => $changes,
        ];
    }

    /**
     * Calculate detailed differences between contents
     */
    protected function calculateDetailedDifferences(string $content1, string $content2): array
    {
        // This is a simplified diff - in production, use a proper diff library
        $lines1 = explode("\n", $content1);
        $lines2 = explode("\n", $content2);

        $maxLines = max(count($lines1), count($lines2));
        $differences = [
            'lines_added' => 0,
            'lines_removed' => 0,
            'lines_modified' => 0,
            'total_changes' => 0,
            'detailed_changes' => [],
        ];

        for ($i = 0; $i < $maxLines; $i++) {
            $line1 = $lines1[$i] ?? '';
            $line2 = $lines2[$i] ?? '';

            if (empty($line1) && !empty($line2)) {
                $differences['lines_added']++;
                $differences['detailed_changes'][] = ['type' => 'added', 'line' => $i + 1, 'content' => $line2];
            } elseif (!empty($line1) && empty($line2)) {
                $differences['lines_removed']++;
                $differences['detailed_changes'][] = ['type' => 'removed', 'line' => $i + 1, 'content' => $line1];
            } elseif ($line1 !== $line2) {
                $differences['lines_modified']++;
                $differences['detailed_changes'][] = [
                    'type' => 'modified',
                    'line' => $i + 1,
                    'old_content' => $line1,
                    'new_content' => $line2,
                ];
            }
        }

        $differences['total_changes'] = $differences['lines_added'] + $differences['lines_removed'] + $differences['lines_modified'];

        return $differences;
    }

    /**
     * Generate change summary
     */
    protected function generateChangeSummary(int $lineDiff, int $charDiff): string
    {
        $summary = [];

        if ($lineDiff > 0) {
            $summary[] = "{$lineDiff} lines added";
        } elseif ($lineDiff < 0) {
            $summary[] = abs($lineDiff) . " lines removed";
        }

        if ($charDiff > 0) {
            $summary[] = "{$charDiff} characters added";
        } elseif ($charDiff < 0) {
            $summary[] = abs($charDiff) . " characters removed";
        }

        return empty($summary) ? 'No changes detected' : implode(', ', $summary);
    }

    /**
     * Determine change type based on changes
     */
    protected function determineChangeType(array $changes): string
    {
        $magnitude = $changes['change_magnitude'] ?? 0;

        if ($magnitude < 10) {
            return 'minor_edit';
        } elseif ($magnitude < 100) {
            return 'moderate_edit';
        } elseif ($magnitude < 500) {
            return 'major_edit';
        } else {
            return 'complete_rewrite';
        }
    }

    /**
     * Check if change is authorized
     */
    protected function isChangeAuthorized(Document $document, User $user, string $reason): bool
    {
        // This would implement authorization logic based on user roles, document type, etc.
        // For now, return true - implement based on business rules
        return true;
    }

    /**
     * Assess change impact
     */
    protected function assessChangeImpact(DocumentVersion $version): string
    {
        $changeType = $version->metadata['change_type'] ?? 'unknown';
        $linesChanged = $version->metadata['lines_changed'] ?? 0;

        if ($changeType === 'complete_rewrite' || $linesChanged > 100) {
            return 'high';
        } elseif ($changeType === 'major_edit' || $linesChanged > 20) {
            return 'medium';
        } else {
            return 'low';
        }
    }

    /**
     * Generate compliance summary
     */
    protected function generateComplianceSummary(Collection $versions): array
    {
        $compliantVersions = $versions->filter(function ($version) {
            return ($version['compliance_data']['version_compliant'] ?? false) === true;
        });

        return [
            'compliant_versions' => $compliantVersions->count(),
            'total_versions' => $versions->count(),
            'compliance_rate' => $versions->count() > 0 ? ($compliantVersions->count() / $versions->count()) * 100 : 0,
            'last_compliant_version' => $compliantVersions->max('version_number'),
        ];
    }

    /**
     * Analyze change patterns
     */
    protected function analyzeChangePatterns(Collection $versions): array
    {
        $patterns = [
            'most_active_user' => $versions->groupBy('changed_by')->map->count()->sortDesc()->keys()->first(),
            'most_common_change_type' => $versions->pluck('metadata.change_type')->filter()->countBy()->sortDesc()->keys()->first(),
            'average_time_between_changes' => $this->calculateAverageTimeBetweenChanges($versions),
            'change_frequency_trend' => $this->calculateChangeFrequencyTrend($versions),
        ];

        return $patterns;
    }

    /**
     * Calculate average time between changes
     */
    protected function calculateAverageTimeBetweenChanges(Collection $versions): ?float
    {
        if ($versions->count() < 2) {
            return null;
        }

        $timestamps = $versions->pluck('created_at')->sort()->values();
        $intervals = [];

        for ($i = 1; $i < $timestamps->count(); $i++) {
            $intervals[] = $timestamps[$i]->diffInMinutes($timestamps[$i - 1]);
        }

        return collect($intervals)->avg();
    }

    /**
     * Calculate change frequency trend
     */
    protected function calculateChangeFrequencyTrend(Collection $versions): string
    {
        if ($versions->count() < 3) {
            return 'insufficient_data';
        }

        $recentVersions = $versions->take(5);
        $olderVersions = $versions->skip(5)->take(5);

        $recentAvgInterval = $this->calculateAverageTimeBetweenChanges($recentVersions);
        $olderAvgInterval = $this->calculateAverageTimeBetweenChanges($olderVersions);

        if ($recentAvgInterval === null || $olderAvgInterval === null) {
            return 'stable';
        }

        $ratio = $olderAvgInterval / $recentAvgInterval;

        if ($ratio > 1.5) {
            return 'increasing';
        } elseif ($ratio < 0.67) {
            return 'decreasing';
        } else {
            return 'stable';
        }
    }

    /**
     * Calculate version frequency statistics
     */
    protected function calculateVersionFrequency(Collection $versions): array
    {
        if ($versions->isEmpty()) {
            return ['average_days_between_versions' => null];
        }

        $dates = $versions->pluck('created_at')->sort();
        $intervals = [];

        for ($i = 1; $i < $dates->count(); $i++) {
            $intervals[] = $dates[$i]->diffInDays($dates[$i - 1]);
        }

        return [
            'average_days_between_versions' => !empty($intervals) ? collect($intervals)->avg() : null,
            'min_days_between_versions' => !empty($intervals) ? min($intervals) : null,
            'max_days_between_versions' => !empty($intervals) ? max($intervals) : null,
        ];
    }

    /**
     * Get document content (placeholder implementation)
     */
    protected function getDocumentContent(Document $document): string
    {
        // This would depend on how content is stored
        return $document->content ?? '';
    }

    /**
     * Get version content
     */
    protected function getVersionContent(DocumentVersion $version): string
    {
        if ($version->content) {
            return $version->content;
        }

        // If content is stored externally, retrieve it
        return $this->retrieveVersionContent($version);
    }

    /**
     * Should store content in version record
     */
    protected function shouldStoreContentInVersion(Document $document): bool
    {
        // For small documents, store content directly
        // For large documents, store externally
        $content = $this->getDocumentContent($document);
        return strlen($content) < 10000; // 10KB threshold
    }

    /**
     * Should store content externally
     */
    protected function shouldStoreContentExternally(Document $document): bool
    {
        return !$this->shouldStoreContentInVersion($document);
    }

    /**
     * Store version content externally
     */
    protected function storeVersionContent(DocumentVersion $version, string $content): void
    {
        $path = "document_versions/{$version->document_id}/v{$version->version_number}.txt";
        Storage::put($path, $content);

        // Update version with storage path
        $version->update(['storage_path' => $path]);
    }

    /**
     * Retrieve version content from external storage
     */
    protected function retrieveVersionContent(DocumentVersion $version): string
    {
        if ($version->storage_path && Storage::exists($version->storage_path)) {
            return Storage::get($version->storage_path);
        }

        throw new \RuntimeException("Version content not found for version {$version->id}");
    }

    /**
     * Update document with new version
     */
    protected function updateDocumentWithVersion(Document $document, DocumentVersion $version, string $content): void
    {
        $document->update([
            'current_version' => $version->version_number,
            'content' => $content, // Update document content
            'updated_at' => now(),
            'metadata' => array_merge($document->metadata ?? [], [
                'last_version_id' => $version->id,
                'last_version_created' => $version->created_at,
                'version_history_count' => $version->version_number,
            ]),
        ]);
    }

    /**
     * Archive a version
     */
    protected function archiveVersion(DocumentVersion $version, User $user): void
    {
        $version->update([
            'is_archived' => true,
            'archived_at' => now(),
            'archived_by' => $user->id,
        ]);

        // Optionally move content to archive storage
        if ($version->storage_path) {
            $archivePath = str_replace('document_versions/', 'document_versions/archive/', $version->storage_path);
            Storage::move($version->storage_path, $archivePath);
            $version->update(['storage_path' => $archivePath]);
        }
    }

    /**
     * Export version history as CSV
     */
    protected function exportAsCsv(array $history): string
    {
        $csv = "Version,Timestamp,User,Change Reason,Change Type,Compliance Status\n";

        foreach ($history['version_history'] as $version) {
            $csv .= sprintf(
                "%s,%s,%s,%s,%s,%s\n",
                $version['version_number'],
                $version['timestamp'],
                $version['user'],
                $version['change_reason'],
                $version['change_type'],
                $version['compliance_status'] ? 'Compliant' : 'Non-compliant'
            );
        }

        return $csv;
    }

    /**
     * Export version history as PDF (placeholder)
     */
    protected function exportAsPdf(array $history): string
    {
        // This would implement PDF generation
        // For now, return JSON as fallback
        return json_encode($history);
    }
}
