<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SuggestedCode extends Model
{
    use HasFactory;

    protected $fillable = [
        'clinical_doc_id',
        'code_type',
        'code_value',
        'code_description',
        'confidence_score',
        'is_validated',
        'validated_by',
        'validated_at'
    ];

    protected $casts = [
        'confidence_score' => 'decimal:2',
        'is_validated' => 'boolean',
        'validated_at' => 'datetime'
    ];

    public function clinicalDocumentation(): BelongsTo
    {
        return $this->belongsTo(ClinicalDocumentationIntelligence::class, 'clinical_doc_id');
    }
}
