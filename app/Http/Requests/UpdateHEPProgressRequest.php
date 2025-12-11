<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateHEPProgressRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Patients can update their own progress, doctors can update for their patients
        $assignment = \App\Models\HepAssignment::find($this->route('assignment_id'));

        if (!$assignment) {
            return false;
        }

        if ($this->user()->id === $assignment->patient_id) {
            return true; // Patient updating their own progress
        }

        if ($this->user()->hasRole('doctor') && $assignment->hepProgram->doctor_id === $this->user()->id) {
            return true; // Doctor updating patient's progress
        }

        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'progress_data' => 'required|array',
            'progress_data.*.hep_exercise_id' => 'required|exists:hep_exercises,id',
            'progress_data.*.date' => 'required|date|before_or_equal:today',
            'progress_data.*.completed_sets' => 'nullable|integer|min:0|max:50',
            'progress_data.*.completed_reps' => 'nullable|integer|min:0|max:1000',
            'progress_data.*.duration_completed' => 'nullable|integer|min:0|max:3600', // max 1 hour
            'progress_data.*.pain_level' => 'nullable|integer|min:0|max:10',
            'progress_data.*.difficulty_rating' => 'nullable|integer|min:1|max:10',
            'progress_data.*.notes' => 'nullable|string|max:1000',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'progress_data.required' => 'Progress data is required.',
            'progress_data.array' => 'Progress data must be an array.',
            'progress_data.*.hep_exercise_id.required' => 'Exercise ID is required for each progress entry.',
            'progress_data.*.hep_exercise_id.exists' => 'Invalid exercise ID.',
            'progress_data.*.date.required' => 'Date is required for each progress entry.',
            'progress_data.*.date.date' => 'Invalid date format.',
            'progress_data.*.date.before_or_equal' => 'Progress date cannot be in the future.',
            'progress_data.*.completed_sets.integer' => 'Completed sets must be a number.',
            'progress_data.*.completed_sets.min' => 'Completed sets cannot be negative.',
            'progress_data.*.completed_sets.max' => 'Completed sets cannot exceed 50.',
            'progress_data.*.completed_reps.integer' => 'Completed reps must be a number.',
            'progress_data.*.completed_reps.min' => 'Completed reps cannot be negative.',
            'progress_data.*.completed_reps.max' => 'Completed reps cannot exceed 1000.',
            'progress_data.*.duration_completed.integer' => 'Duration must be a number.',
            'progress_data.*.duration_completed.min' => 'Duration cannot be negative.',
            'progress_data.*.duration_completed.max' => 'Duration cannot exceed 1 hour.',
            'progress_data.*.pain_level.integer' => 'Pain level must be a number.',
            'progress_data.*.pain_level.min' => 'Pain level must be between 0 and 10.',
            'progress_data.*.pain_level.max' => 'Pain level must be between 0 and 10.',
            'progress_data.*.difficulty_rating.integer' => 'Difficulty rating must be a number.',
            'progress_data.*.difficulty_rating.min' => 'Difficulty rating must be between 1 and 10.',
            'progress_data.*.difficulty_rating.max' => 'Difficulty rating must be between 1 and 10.',
            'progress_data.*.notes.string' => 'Notes must be text.',
            'progress_data.*.notes.max' => 'Notes cannot exceed 1000 characters.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $assignment = \App\Models\HepAssignment::find($this->route('assignment_id'));

            if (!$assignment) {
                $validator->errors()->add('assignment_id', 'HEP assignment not found.');
                return;
            }

            // Validate that all exercises belong to the assignment's program
            $exerciseIds = collect($this->progress_data)->pluck('hep_exercise_id')->unique();
            $programExerciseIds = $assignment->hepProgram->hepExercises()->pluck('id');

            $invalidExercises = $exerciseIds->diff($programExerciseIds);
            if ($invalidExercises->isNotEmpty()) {
                $validator->errors()->add('progress_data', 'Some exercises do not belong to this HEP program.');
            }

            // Validate that progress data makes sense (at least one completion metric provided)
            foreach ($this->progress_data as $index => $progress) {
                $hasCompletionData = !empty($progress['completed_sets']) ||
                                   !empty($progress['completed_reps']) ||
                                   !empty($progress['duration_completed']);

                if (!$hasCompletionData) {
                    $validator->errors()->add("progress_data.{$index}", 'At least one completion metric (sets, reps, or duration) must be provided.');
                }
            }
        });
    }
}
