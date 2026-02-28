<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TreatmentPathway extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'condition_code',
        'pathway_type',
        'description',
        'steps',
        'success_rates',
        'contraindications',
        'evidence_level',
        'version',
        'is_active'
    ];

    protected $casts = [
        'steps' => 'array',
        'success_rates' => 'array',
        'contraindications' => 'array',
        'is_active' => 'boolean'
    ];

    public function recommendations(): HasMany
    {
        return $this->hasMany(TreatmentOptimizationRecommendation::class);
    }

    public function scopeByCondition($query, $conditionCode)
    {
        return $query->where('condition_code', $conditionCode);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('pathway_type', $type);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
