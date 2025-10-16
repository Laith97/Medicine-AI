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
     * Get the authenticated user
     */
    protected function user(): ?\App\Models\User
    {
        return Auth::user();
    }

    /**
     * Get the effective doctor for the current user (handles sub-users)
     */
    protected function getEffectiveDoctor()
    {
        $user = $this->user();
        if (!$user) {
            abort(401, 'Unauthorized.');
        }

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
        $user = $this->user();
        if (!$user) {
            abort(401, 'Unauthorized.');
        }

        return $user->getEffectiveDoctorUser();
    }
}
