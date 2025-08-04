<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AiAssistantResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'doctor_id',
        'patient_id',
        'diagnosis_id',
        'source',
        'ai_analysis',
        'patient_data',
        'voice_transcript',
        'voice_file_path',
        'session_id',
        'usage_data',
        'status',
    ];

    protected $casts = [
        'patient_data' => 'array',
        'usage_data' => 'array',
    ];

    /**
     * Get the doctor who requested the AI analysis
     */
    public function doctor()
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    /**
     * Get the patient for this AI analysis
     */
    public function patient()
    {
        return $this->belongsTo(User::class, 'patient_id');
    }

    /**
     * Get the diagnosis this AI result is linked to
     */
    public function diagnosis()
    {
        return $this->belongsTo(Diagnosis::class);
    }

    /**
     * Check if this result is from AI diagnosis page
     */
    public function isFromAiDiagnosis()
    {
        return $this->source === 'ai_diagnosis';
    }

    /**
     * Check if this result is from voice assistant
     */
    public function isFromVoiceAssistant()
    {
        return $this->source === 'voice_assistant';
    }

    /**
     * Check if this result is linked to a diagnosis
     */
    public function isLinkedToDiagnosis()
    {
        return $this->status === 'linked_to_diagnosis' && $this->diagnosis_id !== null;
    }

    /**
     * Link this AI result to a diagnosis
     */
    public function linkToDiagnosis($diagnosisId)
    {
        $this->update([
            'diagnosis_id' => $diagnosisId,
            'status' => 'linked_to_diagnosis'
        ]);
    }
}
