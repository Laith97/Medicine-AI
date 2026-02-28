<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EarlyWarningScore extends Model
{
    use HasFactory;

    protected $table = 'clinical_early_warning_scores';

    protected $fillable = [
        'patient_id',
        'algorithm_type',
        'score',
        'risk_level',
        'contributing_factors',
        'calculated_at',
    ];

    protected $casts = [
        'score' => 'decimal:2',
        'contributing_factors' => 'array',
        'calculated_at' => 'datetime',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'patient_id');
    }
}
