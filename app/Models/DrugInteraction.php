<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DrugInteraction extends Model
{
    use HasFactory;

    protected $fillable = [
        'drug_1',
        'drug_2',
        'description',
        'severity',
        'clinical_consequence',
        'recommendation',
        'evidence_sources',
    ];

    protected $casts = [
        'evidence_sources' => 'array',
    ];

    /**
     * Find interaction between two drugs
     */
    public static function findInteraction(string $drug1, string $drug2): ?self
    {
        return self::where(function ($query) use ($drug1, $drug2) {
            $query->where('drug_1', $drug1)->where('drug_2', $drug2);
        })->orWhere(function ($query) use ($drug1, $drug2) {
            $query->where('drug_1', $drug2)->where('drug_2', $drug1);
        })->first();
    }

    /**
     * Get all interactions for a drug
     */
    public static function getInteractionsForDrug(string $drug): \Illuminate\Database\Eloquent\Collection
    {
        return self::where('drug_1', $drug)
            ->orWhere('drug_2', $drug)
            ->get();
    }
}
