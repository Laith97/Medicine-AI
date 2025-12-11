<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GetCodeSuggestionsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by middleware
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'description' => 'required|string|max:10000',
            'patient_info' => 'nullable|array',
            'patient_info.age' => 'nullable|integer|min:0|max:150',
            'patient_info.gender' => 'nullable|string|in:male,female,other',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'description.required' => 'Description is required',
            'description.string' => 'Description must be a string',
            'description.max' => 'Description must not exceed 10,000 characters',
            'patient_info.array' => 'Patient info must be an array',
            'patient_info.age.integer' => 'Patient age must be an integer',
            'patient_info.age.min' => 'Patient age must be at least 0',
            'patient_info.age.max' => 'Patient age must not exceed 150',
            'patient_info.gender.in' => 'Patient gender must be male, female, or other',
        ];
    }
}
