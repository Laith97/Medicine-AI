<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PredictDenialRequest extends FormRequest
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
            'claim_id' => 'required|string|max:100',
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
            'claim_id.required' => 'Claim ID is required',
            'claim_id.string' => 'Claim ID must be a string',
            'claim_id.max' => 'Claim ID must not exceed 100 characters',
        ];
    }
}
