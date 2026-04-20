<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DataMigration extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'status',
        'entity_type',
        'source_type',
        'source_path',
        'source_config',
        'total_records',
        'processed_records',
        'success_records',
        'failed_records',
        'error_log',
        'field_mapping',
        'validation_rules',
        'incremental_sync',
        'last_sync_at',
        'template_name',
        'user_id',
    ];

    protected $casts = [
        'source_config' => 'array',
        'field_mapping' => 'array',
        'validation_rules' => 'array',
        'last_sync_at' => 'datetime',
        'incremental_sync' => 'boolean',
    ];

    public const STATUS_PENDING = 'pending';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_CANCELLED = 'cancelled';

    public const SOURCE_CSV = 'csv';
    public const SOURCE_EXCEL = 'excel';
    public const SOURCE_API = 'api';
    public const SOURCE_SQL_DATABASE = 'sql_database';
    public const SOURCE_HL7 = 'hl7';
    public const SOURCE_FHIR = 'fhir';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function records(): HasMany
    {
        return $this->hasMany(DataMigrationRecord::class);
    }

    public function idMappings(): HasMany
    {
        return $this->hasMany(DataMigrationIdMapping::class);
    }

    public function getProgressPercentage(): float
    {
        if ($this->total_records === 0) {
            return 0;
        }
        return round(($this->processed_records / $this->total_records) * 100, 2);
    }

    public function getRemainingRecords(): int
    {
        return $this->total_records - $this->processed_records;
    }

    public function markAsInProgress(): void
    {
        $this->update(['status' => self::STATUS_IN_PROGRESS]);
    }

    public function markAsCompleted(): void
    {
        $this->update(['status' => self::STATUS_COMPLETED]);
    }

    public function markAsFailed(string $errorLog = null): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'error_log' => $errorLog ?? $this->error_log,
        ]);
    }

    public function addErrorLog(string $error): void
    {
        $currentLog = $this->error_log ?? '';
        $timestamp = now()->format('Y-m-d H:i:s');
        $this->update([
            'error_log' => $currentLog . "\n[{$timestamp}] {$error}",
        ]);
    }

    public function incrementProcessed(int $success = 0, int $failed = 0): void
    {
        $this->increment('processed_records', $success + $failed);
        $this->increment('success_records', $success);
        $this->increment('failed_records', $failed);
    }

    public function getSourceTypeLabel(): string
    {
        return match($this->source_type) {
            self::SOURCE_CSV => 'CSV File',
            self::SOURCE_EXCEL => 'Excel File',
            self::SOURCE_API => 'API Connection',
            self::SOURCE_SQL_DATABASE => 'SQL Database',
            self::SOURCE_HL7 => 'HL7 Protocol',
            self::SOURCE_FHIR => 'FHIR Standard',
            default => 'Unknown',
        };
    }

    public function getStatusBadgeClass(): string
    {
        return match($this->status) {
            self::STATUS_PENDING => 'bg-secondary',
            self::STATUS_IN_PROGRESS => 'bg-primary',
            self::STATUS_COMPLETED => 'bg-success',
            self::STATUS_FAILED => 'bg-danger',
            self::STATUS_CANCELLED => 'bg-warning',
            default => 'bg-secondary',
        };
    }
}