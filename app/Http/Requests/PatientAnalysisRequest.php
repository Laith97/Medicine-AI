<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PatientAnalysisRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Basic patient info
            'name' => 'required_if:patient_selection,new|string|max:255',
            'age' => 'required_if:patient_selection,new|integer|min:0|max:150',
            'gender' => 'required_if:patient_selection,new|in:male,female',

            // Physical attributes
            'weight' => 'nullable|numeric|min:0|max:1000',
            'height' => 'nullable|numeric|min:0|max:300',

            // Vitals
            'temperature' => 'nullable|numeric|min:30|max:50',
            'blood_pressure' => 'nullable|string|max:20',
            'blood_sugar' => 'nullable|numeric|min:0|max:1000',
            'heart_rate' => 'nullable|integer|min:20|max:300',
            'respiratory_rate' => 'nullable|integer|min:5|max:60',
            'oxygen_saturation' => 'nullable|integer|min:50|max:100',
            'pain_scale' => 'nullable|integer|min:0|max:10',

            // Medical history
            'chief_complaint' => 'nullable|string|max:1000',
            'symptom_duration' => 'nullable|string|max:255',
            'past_medical_history' => 'nullable|string|max:2000',
            'medication_history' => 'nullable|string|max:2000',
            'allergies' => 'nullable|string|max:500',
            'family_history' => 'nullable|string|max:1000',
            'social_history' => 'nullable|string|max:1000',
            'visit_type' => 'nullable|in:Initial,Follow-up,Emergency',

            // Clinical data
            'current_symptoms' => 'nullable|array',
            'current_symptoms.*' => 'integer|exists:symptoms,id',
            'custom_symptoms' => 'nullable|string',
            'test_results' => 'nullable|string|max:5000',
            'preliminary_diagnosis' => 'nullable|string|max:2000',

            // Notes
            'physician_notes' => 'nullable|string|max:2000',
            'additional_notes' => 'nullable|string|max:2000',

            // Head-to-Toe Assessment fields
            // General Appearance
            'consciousness_level' => 'nullable|in:Alert,Drowsy,Unresponsive',
            'mood_behavior' => 'nullable|in:Calm,Anxious,Aggressive,Confused',
            'speech_clarity' => 'nullable|in:Clear,Slurred,Incoherent',
            'hygiene_level' => 'nullable|in:Good,Fair,Poor',

            // HEENT
            'scalp_condition' => 'nullable|string|max:255',
            'pupil_reactivity' => 'nullable|in:PERRLA,Unequal,Non-reactive',
            'vision_issues' => 'nullable|boolean',
            'hearing_issues' => 'nullable|boolean',
            'oral_findings' => 'nullable|string|max:1000',

            // Neurological
            'orientation_level' => 'nullable|in:Oriented x4,Oriented x3,Oriented x2,Disoriented',
            'limb_strength' => 'nullable|in:Equal,Weak Left,Weak Right,Paralyzed',
            'reflexes' => 'nullable|in:Normal,Hyperreflexia,Hyporeflexia',
            'sensation_findings' => 'nullable|string|max:1000',

            // Neck and Chest
            'trachea_position' => 'nullable|in:Midline,Deviated',
            'jvd_present' => 'nullable|boolean',
            'lung_sounds' => 'nullable|in:Clear,Crackles,Wheezes,Diminished',
            'heart_sounds' => 'nullable|in:Normal,Murmur,Irregular',
            'capillary_refill_time' => 'nullable|in:< 2s,2–3s,> 3s',

            // Abdomen
            'abdominal_shape' => 'nullable|in:Flat,Distended,Scarred',
            'bowel_sounds' => 'nullable|in:Normal,Hyperactive,Hypoactive,Absent',
            'abdominal_tenderness' => 'nullable|boolean',
            'nausea_or_vomiting' => 'nullable|boolean',
            'appetite_level' => 'nullable|in:Good,Poor,None',

            // Genitourinary
            'urination_issues' => 'nullable|boolean',
            'catheter_present' => 'nullable|boolean',
            'urine_characteristics' => 'nullable|string|max:1000',

            // Musculoskeletal
            'range_of_motion' => 'nullable|in:Full,Limited,None',
            'gait_stability' => 'nullable|in:Stable,Unsteady,Requires assistance',
            'assistive_devices' => 'nullable|string|max:255',

            // Skin
            'skin_color' => 'nullable|in:Pink,Pale,Cyanotic,Jaundiced',
            'skin_temperature' => 'nullable|in:Warm,Cool,Cold',
            'skin_lesions' => 'nullable|string|max:1000',
            'pressure_ulcers' => 'nullable|boolean',

            // Pain Assessment
            'pain_score' => 'nullable|integer|min:0|max:10',
            'pain_description' => 'nullable|string|max:1000',

            // File uploads
            'reports' => 'nullable|array',
            'reports.*' => 'file|max:10240', // 10MB max per file

            // Patient selection
            'patient_selection' => 'required|string',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required_if' => 'Patient name is required for new patients.',
            'age.required_if' => 'Patient age is required for new patients.',
            'gender.required_if' => 'Patient gender is required for new patients.',
            'age.min' => 'Age must be at least 0.',
            'age.max' => 'Age cannot exceed 150 years.',
            'weight.min' => 'Weight must be a positive number.',
            'height.min' => 'Height must be a positive number.',
            'temperature.min' => 'Temperature seems too low. Please check the value.',
            'temperature.max' => 'Temperature seems too high. Please check the value.',
            'heart_rate.min' => 'Heart rate seems too low. Please check the value.',
            'heart_rate.max' => 'Heart rate seems too high. Please check the value.',
            'respiratory_rate.min' => 'Respiratory rate seems too low. Please check the value.',
            'respiratory_rate.max' => 'Respiratory rate seems too high. Please check the value.',
            'oxygen_saturation.min' => 'Oxygen saturation cannot be below 50%.',
            'oxygen_saturation.max' => 'Oxygen saturation cannot exceed 100%.',
            'pain_scale.min' => 'Pain scale must be between 0 and 10.',
            'pain_scale.max' => 'Pain scale must be between 0 and 10.',
            'reports.*.max' => 'Each file must not exceed 10MB.',
        ];
    }
}
