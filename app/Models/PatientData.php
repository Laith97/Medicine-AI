<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PatientData extends Model
{
    use HasFactory;

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
        'assigned_patient_id',
        'previous_record_id',
        'visit_number',
        'patient_key',
        // New enhanced medical fields
        'chief_complaint',
        'symptom_duration',
        'past_medical_history',
        'medication_history',
        'allergies',
        'past_medications',
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

    protected $casts = [
        'allergies' => 'array',
        'past_medications' => 'array',
        'symptoms' => 'array',
        'test_results' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the assigned patient (User) for this data
     */
    public function assignedPatient()
    {
        return $this->belongsTo(User::class, 'assigned_patient_id');
    }

    /**
     * Get the previous record for this patient
     */
    public function previousRecord()
    {
        return $this->belongsTo(PatientData::class, 'previous_record_id');
    }

    /**
     * Get all subsequent records for this patient
     */
    public function subsequentRecords()
    {
        return $this->hasMany(PatientData::class, 'previous_record_id');
    }

    /**
     * Get the patient's insurance records
     */
    public function patientInsurances()
    {
        return $this->hasMany(PatientInsurance::class, 'patient_id');
    }
}
