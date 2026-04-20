<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DataMigrationIdMapping extends Model
{
    use HasFactory;

    protected $fillable = [
        'data_migration_id',
        'source_type',
        'source_id',
        'medcura_type',
        'medcura_id',
        'is_duplicate',
    ];

    public function dataMigration(): BelongsTo
    {
        return $this->belongsTo(DataMigration::class);
    }

    /**
     * Get MedCura ID for a given source ID
     */
    public static function getMedCuraId(int $migrationId, string $sourceType, string $sourceId): ?string
    {
        return static::where('data_migration_id', $migrationId)
            ->where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->value('medcura_id');
    }

    /**
     * Create or update mapping
     */
    public static function createMapping(
        int $migrationId,
        string $sourceType,
        string $sourceId,
        string $medcuraType,
        string $medcuraId,
        bool $isDuplicate = false
    ): self {
        return static::updateOrCreate(
            [
                'data_migration_id' => $migrationId,
                'source_type' => $sourceType,
                'source_id' => $sourceId,
            ],
            [
                'medcura_type' => $medcuraType,
                'medcura_id' => $medcuraId,
                'is_duplicate' => $isDuplicate,
            ]
        );
    }
}