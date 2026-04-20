<?php

namespace App\Observers;

use App\Models\Hospital;
use App\Services\SmsService;
use Illuminate\Support\Facades\Auth;

class HospitalObserver
{
    /**
     * Handle the Hospital "updating" event.
     */
    public function updating(Hospital $hospital): void
    {
        if ($hospital->isDirty('sms_provider')) {
            $smsService = app(SmsService::class);
            $user = Auth::user();

            $smsService->logConfigurationChange('hospital_sms_provider_changed', [
                'user_id' => $user ? $user->id : null,
                'user_role' => $user ? $user->role : 'system',
                'model_type' => 'hospital',
                'model_id' => $hospital->id,
                'old_provider' => $hospital->getOriginal('sms_provider'),
                'new_provider' => $hospital->sms_provider,
            ]);
        }
    }
}