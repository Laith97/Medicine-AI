<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DiagnosisFollowUp extends Model
{
    use HasFactory;

    protected $fillable = [
        'diagnosis_id',
        'patient_id',
        'question',
        'ai_response',
        'usage_data',
    ];

    protected $casts = [
        'usage_data' => 'array',
    ];

    /**
     * Get the diagnosis this follow-up belongs to
     */
    public function diagnosis()
    {
        return $this->belongsTo(Diagnosis::class);
    }

    /**
     * Get the patient who asked the follow-up question
     */
    public function patient()
    {
        return $this->belongsTo(User::class, 'patient_id');
    }
}
