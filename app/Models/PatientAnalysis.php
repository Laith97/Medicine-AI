<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PatientAnalysis extends Model
{
    protected $table = 'patient_data';

    protected $fillable = [
        'name',
        'age',
        'gender',
        'weight',
        'height',
        'temperature',
        'blood_pressure',
        'blood_sugar',
        'symptoms',
        'test_results',
        'preliminary_diagnosis',
        'ai_response',
        'user_id',
        'previous_record_id',
        'visit_number',
        'patient_key',
        // New enhanced medical fields
        'chief_complaint',
        'symptom_duration',
        'past_medical_history',
        'medication_history',
        'allergies',
        'family_history',
        'social_history',
        'pain_scale',
        'visit_type',
        'heart_rate',
        'respiratory_rate',
        'oxygen_saturation',
        'physician_notes',
        'additional_notes',

        // Head-to-Toe Assessment fields
        // General Appearance
        'consciousness_level',
        'mood_behavior',
        'speech_clarity',
        'hygiene_level',

        // HEENT
        'scalp_condition',
        'pupil_reactivity',
        'vision_issues',
        'hearing_issues',
        'oral_findings',

        // Neurological
        'orientation_level',
        'limb_strength',
        'reflexes',
        'sensation_findings',

        // Neck and Chest
        'trachea_position',
        'jvd_present',
        'lung_sounds',
        'heart_sounds',
        'capillary_refill_time',

        // Abdomen
        'abdominal_shape',
        'bowel_sounds',
        'abdominal_tenderness',
        'nausea_or_vomiting',
        'appetite_level',

        // Genitourinary
        'urination_issues',
        'catheter_present',
        'urine_characteristics',

        // Musculoskeletal
        'range_of_motion',
        'gait_stability',
        'assistive_devices',

        // Skin
        'skin_color',
        'skin_temperature',
        'skin_lesions',
        'pressure_ulcers',

        // Pain Assessment
        'pain_description',
    ];


    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the previous record for this patient
     */
    public function previousRecord()
    {
        return $this->belongsTo(PatientAnalysis::class, 'previous_record_id');
    }

    /**
     * Get all subsequent records for this patient
     */
    public function subsequentRecords()
    {
        return $this->hasMany(PatientAnalysis::class, 'previous_record_id');
    }

    /**
     * Get all records for the same patient (by patient_key or by name, age, gender)
     */
    public function getPatientHistory()
    {
        if ($this->patient_key) {
            return PatientAnalysis::where('patient_key', $this->patient_key)
                ->where('user_id', $this->user_id)
                ->orderBy('visit_number', 'asc')
                ->get();
        }

        return PatientAnalysis::where('name', $this->name)
            ->where('age', $this->age)
            ->where('gender', $this->gender)
            ->where('user_id', $this->user_id)
            ->orderBy('created_at', 'asc')
            ->get();
    }

    /**
     * Generate a unique patient key based on name, age, gender and user_id
     */
    public static function generatePatientKey($name, $age, $gender, $userId)
    {
        return md5($name . '-' . $age . '-' . $gender . '-' . $userId);
    }

}
