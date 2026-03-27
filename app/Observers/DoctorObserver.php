<?php

namespace App\Observers;

use App\Models\Doctor;
use App\Services\SmsService;
use Illuminate\Support\Facades\Auth;

class DoctorObserver
{
    /**
     * Handle the Doctor "updating" event.
     */
    public function updating(Doctor $doctor): void
    {
        if ($doctor->isDirty('sms_provider')) {
            $smsService = app(SmsService::class);
            $user = Auth::user();

            $smsService->logConfigurationChange('doctor_sms_provider_changed', [
                'user_id' => $user ? $user->id : null,
                'user_role' => $user ? $user->role : 'system',
                'model_type' => 'doctor',
                'model_id' => $doctor->id,
                'old_provider' => $doctor->getOriginal('sms_provider'),
                'new_provider' => $doctor->sms_provider,
            ]);
        }
    }
}