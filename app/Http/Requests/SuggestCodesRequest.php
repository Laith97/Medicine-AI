<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SuggestCodesRequest extends FormRequest
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
            'encounter_id' => 'required|integer',
            'clinical_text' => 'required|string|max:10000',
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
            'encounter_id.required' => 'Encounter ID is required',
            'encounter_id.integer' => 'Encounter ID must be an integer',
            'clinical_text.required' => 'Clinical text is required',
            'clinical_text.max' => 'Clinical text must not exceed 10,000 characters',
        ];
    }
}
