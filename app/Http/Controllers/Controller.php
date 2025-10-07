<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Auth;

abstract class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    /**
     * Get the effective doctor for the current user (handles sub-users)
     */
    protected function getEffectiveDoctor()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user(); // @phan-ignore-current-line

        $doctor = $user->getEffectiveDoctor();

        if (!$doctor) {
            abort(403, 'Doctor profile not found.');
        }

        return $doctor;
    }

    /**
     * Get the effective doctor user for the current user (handles sub-users)
     */
    protected function getEffectiveDoctorUser()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user(); // @phan-ignore-current-line

        return $user->getEffectiveDoctorUser();
    }
}
