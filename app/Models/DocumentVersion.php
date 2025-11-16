<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentVersion extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_id',
        'version_number',
        'content',
        'content_hash',
        'changes_summary',
        'change_reason',
        'changed_by',
        'metadata',
        'compliance_data',
        'storage_path',
        'is_archived',
        'archived_at',
        'archived_by',
    ];

    protected $casts = [
        'metadata' => 'array',
        'compliance_data' => 'array',
        'is_archived' => 'boolean',
        'archived_at' => 'datetime',
    ];

    /**
     * Get the document this version belongs to.
     */
    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    /**
     * Get the user who made this version change.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    /**
     * Get the user who archived this version.
     */
    public function archiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'archived_by');
    }

    /**
     * Scope for active (non-archived) versions
     */
    public function scopeActive($query)
    {
        return $query->where('is_archived', false);
    }

    /**
     * Scope for archived versions
     */
    public function scopeArchived($query)
    {
        return $query->where('is_archived', true);
    }

    /**
     * Scope for versions by user
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('changed_by', $userId);
    }

    /**
     * Scope for versions within date range
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Get the content for this version
     */
    public function getContent(): string
    {
        if ($this->content) {
            return $this->content;
        }

        if ($this->storage_path && \Storage::exists($this->storage_path)) {
            return \Storage::get($this->storage_path);
        }

        throw new \RuntimeException("Content not available for version {$this->id}");
    }

    /**
     * Check if content is stored externally
     */
    public function hasExternalContent(): bool
    {
        return !empty($this->storage_path);
    }

    /**
     * Get version size in bytes
     */
    public function getContentSize(): int
    {
        if ($this->content) {
            return strlen($this->content);
        }

        if ($this->storage_path && \Storage::exists($this->storage_path)) {
            return \Storage::size($this->storage_path);
        }

        return 0;
    }

    /**
     * Get change impact level
     */
    public function getChangeImpact(): string
    {
        $metadata = $this->metadata ?? [];
        $linesChanged = $metadata['lines_changed'] ?? 0;
        $changeType = $metadata['change_type'] ?? 'unknown';

        if ($changeType === 'complete_rewrite' || $linesChanged > 100) {
            return 'high';
        } elseif ($changeType === 'major_edit' || $linesChanged > 20) {
            return 'medium';
        } else {
            return 'low';
        }
    }

    /**
     * Check if version is compliant
     */
    public function isCompliant(): bool
    {
        $complianceData = $this->compliance_data ?? [];
        return $complianceData['version_compliant'] ?? false;
    }

    /**
     * Get compliance violations for this version
     */
    public function getComplianceViolations(): array
    {
        $complianceData = $this->compliance_data ?? [];
        return $complianceData['violations'] ?? [];
    }

    /**
     * Mark version as archived
     */
    public function archive(User $user): bool
    {
        $this->update([
            'is_archived' => true,
            'archived_at' => now(),
            'archived_by' => $user->id,
        ]);

        return true;
    }

    /**
     * Check if this is the current version of the document
     */
    public function isCurrentVersion(): bool
    {
        return $this->document && $this->document->current_version === $this->version_number;
    }

    /**
     * Get the next version number for a document
     */
    public static function getNextVersionNumber(int $documentId): int
    {
        return static::where('document_id', $documentId)->max('version_number') + 1;
    }

    /**
     * Get version statistics for a document
     */
    public static function getDocumentVersionStats(int $documentId): array
    {
        $versions = static::where('document_id', $documentId)->get();

        return [
            'total_versions' => $versions->count(),
            'active_versions' => $versions->where('is_archived', false)->count(),
            'archived_versions' => $versions->where('is_archived', true)->count(),
            'compliant_versions' => $versions->filter->isCompliant()->count(),
            'most_active_user' => $versions->groupBy('changed_by')->keys()->first(),
            'average_change_size' => $versions->avg(function ($version) {
                return $version->metadata['lines_changed'] ?? 0;
            }),
        ];
    }
}
