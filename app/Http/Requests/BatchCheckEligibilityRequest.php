<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BatchCheckEligibilityRequest extends FormRequest
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
            'checks' => 'required|array|min:1|max:50', // Limit batch size
            'checks.*.patient_insurance_id' => 'required|exists:patient_insurances,id',
            'checks.*.service_type' => 'required|string|max:100',
            'force_refresh' => 'sometimes|boolean',
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
            'checks.required' => 'Checks array is required',
            'checks.array' => 'Checks must be an array',
            'checks.min' => 'At least one check is required',
            'checks.max' => 'Maximum 50 checks allowed per batch',
            'checks.*.patient_insurance_id.required' => 'Patient insurance ID is required for each check',
            'checks.*.patient_insurance_id.exists' => 'Invalid patient insurance ID',
            'checks.*.service_type.required' => 'Service type is required for each check',
            'checks.*.service_type.max' => 'Service type must not exceed 100 characters',
        ];
    }
}
