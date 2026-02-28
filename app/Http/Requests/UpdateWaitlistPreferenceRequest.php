<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWaitlistPreferenceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'preferred_times' => 'sometimes|nullable|array',
            'preferred_times.*' => 'in:morning,afternoon,evening',
            'preferred_days' => 'sometimes|nullable|array',
            'preferred_days.*' => 'in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'service_priorities' => 'sometimes|nullable|array',
            'service_priorities.*' => 'in:high,medium,low',
            'notification_settings' => 'sometimes|nullable|array',
            'notification_settings.email' => 'boolean',
            'notification_settings.sms' => 'boolean',
            'notification_settings.push' => 'boolean',
            'auto_accept_threshold' => 'sometimes|nullable|integer|min:1|max:30',
            'max_travel_distance' => 'sometimes|nullable|numeric|min:0|max:100',
            'preferred_location_lat' => 'sometimes|nullable|numeric|between:-90,90',
            'preferred_location_lng' => 'sometimes|nullable|numeric|between:-180,180',
            'emergency_contact' => 'sometimes|nullable|string|max:255',
            'special_requirements' => 'sometimes|nullable|string|max:1000',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'preferred_times.*.in' => 'Invalid time preference. Must be morning, afternoon, or evening.',
            'preferred_days.*.in' => 'Invalid day preference. Must be a valid day of the week.',
            'service_priorities.*.in' => 'Service priority must be high, medium, or low.',
            'auto_accept_threshold.min' => 'Auto-accept threshold must be at least 1 day.',
            'auto_accept_threshold.max' => 'Auto-accept threshold cannot exceed 30 days.',
            'max_travel_distance.max' => 'Maximum travel distance cannot exceed 100 km.',
            'preferred_location_lat.between' => 'Latitude must be between -90 and 90 degrees.',
            'preferred_location_lng.between' => 'Longitude must be between -180 and 180 degrees.',
        ];
    }
}
