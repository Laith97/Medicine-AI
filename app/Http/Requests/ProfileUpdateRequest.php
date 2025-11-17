<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
            'phone' => ['nullable', 'string', 'max:20', 'regex:/^[\+]?[1-9][\d]{0,15}$/'],
            // WhatsApp notification preferences
            'whatsapp_enabled' => ['boolean'],
            'whatsapp_appointment_reminders' => ['boolean'],
            'whatsapp_urgent_alerts' => ['boolean'],
            'whatsapp_diagnosis_updates' => ['boolean'],
            'whatsapp_review_requests' => ['boolean'],
        ];
    }
}
