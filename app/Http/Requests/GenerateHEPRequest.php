<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GenerateHEPRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Only doctors can generate HEP programs
        return $this->user() && $this->user()->hasRole('doctor');
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'diagnosis_id' => 'required|exists:diagnoses,id',
            'patient_id' => 'required|exists:users,id',
            'additional_context' => 'nullable|array',
            'additional_context.patient_age' => 'nullable|integer|min:1|max:150',
            'additional_context.patient_gender' => 'nullable|in:male,female,other',
            'additional_context.functional_goals' => 'nullable|array',
            'additional_context.functional_goals.*' => 'string|max:255',
            'additional_context.preferred_exercise_types' => 'nullable|array',
            'additional_context.preferred_exercise_types.*' => 'in:strength,cardiovascular,flexibility,balance,functional',
            'additional_context.contraindications' => 'nullable|array',
            'additional_context.contraindications.*' => 'string|max:255',
            'additional_context.equipment_available' => 'nullable|array',
            'additional_context.equipment_available.*' => 'string|max:255',
            'use_background_processing' => 'nullable|boolean',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'diagnosis_id.required' => 'A diagnosis must be selected.',
            'diagnosis_id.exists' => 'The selected diagnosis does not exist.',
            'patient_id.required' => 'A patient must be selected.',
            'patient_id.exists' => 'The selected patient does not exist.',
            'additional_context.patient_age.integer' => 'Patient age must be a valid number.',
            'additional_context.patient_age.min' => 'Patient age must be at least 1.',
            'additional_context.patient_age.max' => 'Patient age must be no more than 150.',
            'additional_context.patient_gender.in' => 'Patient gender must be male, female, or other.',
            'additional_context.preferred_exercise_types.*.in' => 'Invalid exercise type selected.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'additional_context.patient_age' => 'patient age',
            'additional_context.patient_gender' => 'patient gender',
            'additional_context.functional_goals' => 'functional goals',
            'additional_context.preferred_exercise_types' => 'preferred exercise types',
            'additional_context.contraindications' => 'contraindications',
            'additional_context.equipment_available' => 'equipment available',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Check if the diagnosis belongs to the authenticated doctor
            $diagnosis = \App\Models\Diagnosis::find($this->diagnosis_id);
            if ($diagnosis && $diagnosis->doctor_id !== $this->user()->id) {
                $validator->errors()->add('diagnosis_id', 'You can only generate HEP programs for your own diagnoses.');
            }

            // Check if the patient belongs to the authenticated doctor
            $patient = \App\Models\User::find($this->patient_id);
            if ($patient && $patient->assigned_doctor_id !== $this->user()->id) {
                $validator->errors()->add('patient_id', 'You can only generate HEP programs for your assigned patients.');
            }
        });
    }
}
