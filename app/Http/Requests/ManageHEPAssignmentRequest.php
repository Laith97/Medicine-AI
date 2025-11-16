<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ManageHEPAssignmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Only doctors can manage HEP assignments
        return $this->user() && $this->user()->hasRole('doctor');
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $rules = [
            'hep_program_id' => 'required|exists:hep_programs,id',
            'patient_id' => 'required|exists:users,id',
            'due_date' => 'nullable|date|after:today',
            'patient_notes' => 'nullable|string|max:2000',
            'clinician_feedback' => 'nullable|string|max:2000',
        ];

        // For creating assignments, assigned_at is auto-set
        // For updating, we might allow changing due_date and notes
        if ($this->isMethod('patch') || $this->isMethod('put')) {
            $rules['completion_status'] = 'nullable|in:pending,in_progress,completed,overdue';
            $rules['due_date'] = 'nullable|date|after:today';
        }

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'hep_program_id.required' => 'HEP program is required.',
            'hep_program_id.exists' => 'Selected HEP program does not exist.',
            'patient_id.required' => 'Patient is required.',
            'patient_id.exists' => 'Selected patient does not exist.',
            'due_date.date' => 'Due date must be a valid date.',
            'due_date.after' => 'Due date must be in the future.',
            'completion_status.in' => 'Invalid completion status.',
            'patient_notes.string' => 'Patient notes must be text.',
            'patient_notes.max' => 'Patient notes cannot exceed 2000 characters.',
            'clinician_feedback.string' => 'Clinician feedback must be text.',
            'clinician_feedback.max' => 'Clinician feedback cannot exceed 2000 characters.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $program = \App\Models\HepProgram::find($this->hep_program_id);
            $patient = \App\Models\User::find($this->patient_id);

            if ($program && $program->doctor_id !== $this->user()->id) {
                $validator->errors()->add('hep_program_id', 'You can only assign your own HEP programs.');
            }

            if ($patient && $patient->assigned_doctor_id !== $this->user()->id) {
                $validator->errors()->add('patient_id', 'You can only assign programs to your own patients.');
            }

            // Check if patient already has an active assignment for this program
            if ($program && $patient && $this->isMethod('post')) {
                $existingAssignment = \App\Models\HepAssignment::where('hep_program_id', $program->id)
                    ->where('patient_id', $patient->id)
                    ->where('completion_status', '!=', 'completed')
                    ->first();

                if ($existingAssignment) {
                    $validator->errors()->add('hep_program_id', 'This patient already has an active assignment for this program.');
                }
            }

            // Validate due date is not too far in the future
            if ($this->due_date && $program) {
                $maxDueDate = now()->addWeeks($program->duration_weeks + 4); // Allow some buffer
                if (\Carbon\Carbon::parse($this->due_date)->greaterThan($maxDueDate)) {
                    $validator->errors()->add('due_date', 'Due date cannot be more than ' . ($program->duration_weeks + 4) . ' weeks from now.');
                }
            }
        });
    }
}
