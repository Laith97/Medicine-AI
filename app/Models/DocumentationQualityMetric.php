<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DocumentationQualityMetric extends Model
{
    use HasFactory;

    protected $fillable = [
        'clinical_doc_id',
        'metric_type',
        'score',
        'details'
    ];

    protected $casts = [
        'score' => 'decimal:2',
        'details' => 'array'
    ];

    public function clinicalDocumentation(): BelongsTo
    {
        return $this->belongsTo(ClinicalDocumentationIntelligence::class, 'clinical_doc_id');
    }
}
