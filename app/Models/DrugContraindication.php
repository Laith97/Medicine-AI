<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DrugContraindication extends Model
{
    use HasFactory;

    protected $fillable = [
        'drug_name',
        'condition',
        'reason',
        'severity',
        'alternative_options',
        'monitoring_required',
        'evidence_sources',
    ];

    protected $casts = [
        'evidence_sources' => 'array',
    ];

    /**
     * Find contraindications for a drug and condition
     */
    public static function findContraindication(string $drug, string $condition): ?self
    {
        return self::where('drug_name', $drug)
            ->where('condition', $condition)
            ->first();
    }

    /**
     * Get all contraindications for a drug
     */
    public static function getContraindicationsForDrug(string $drug): \Illuminate\Database\Eloquent\Collection
    {
        return self::where('drug_name', $drug)->get();
    }

    /**
     * Get all contraindications for a condition
     */
    public static function getContraindicationsForCondition(string $condition): \Illuminate\Database\Eloquent\Collection
    {
        return self::where('condition', $condition)->get();
    }
}
