<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CheckEligibilityRequest extends FormRequest
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
            'patient_insurance_id' => 'required|exists:patient_insurances,id',
            'service_type' => 'required|string|max:100',
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
            'patient_insurance_id.required' => 'Patient insurance ID is required',
            'patient_insurance_id.exists' => 'Invalid patient insurance ID',
            'service_type.required' => 'Service type is required',
            'service_type.max' => 'Service type must not exceed 100 characters',
        ];
    }
}
