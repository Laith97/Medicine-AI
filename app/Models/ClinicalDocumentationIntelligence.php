<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ClinicalDocumentationIntelligence extends Model
{
    use HasFactory;

    protected $table = 'clinical_documentation_intelligence';

    protected $fillable = [
        'patient_id',
        'appointment_id', 
        'ai_session_id',
        'note_type',
        'chief_complaint',
        'history_of_present_illness',
        'review_of_systems',
        'physical_exam_findings',
        'assessment',
        'plan',
        'medications_review',
        'overall_confidence',
        'section_confidences',
        'completeness_score',
        'compliance_flags',
        'missing_elements',
        'suggested_codes',
        'validated_by_doctor',
        'validated_at',
        'generated_from_transcription_id'
    ];

    protected $casts = [
        'review_of_systems' => 'array',
        'section_confidences' => 'array',
        'compliance_flags' => 'array',
        'missing_elements' => 'array',
        'suggested_codes' => 'array',
        'overall_confidence' => 'decimal:2',
        'completeness_score' => 'decimal:2',
        'validated_by_doctor' => 'boolean',
        'validated_at' => 'datetime'
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'patient_id');
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function transcription(): BelongsTo
    {
        return $this->belongsTo(VoiceTranscription::class, 'generated_from_transcription_id');
    }

    public function qualityMetrics(): HasMany
    {
        return $this->hasMany(DocumentationQualityMetric::class, 'clinical_doc_id');
    }

    public function suggestedCodes(): HasMany
    {
        return $this->hasMany(SuggestedCode::class, 'clinical_doc_id');
    }

    // Scopes for filtering
    public function scopeValidated($query)
    {
        return $query->where('validated_by_doctor', true);
    }

    public function scopePendingValidation($query)
    {
        return $query->where('validated_by_doctor', false);
    }

    public function scopeByNoteType($query, $noteType)
    {
        return $query->where('note_type', $noteType);
    }
}
