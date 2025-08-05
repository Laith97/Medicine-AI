<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

abstract class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    /**
     * Get the effective doctor for the current user (handles sub-users)
     */
    protected function getEffectiveDoctor()
    {
        $doctor = auth()->user()->getEffectiveDoctor();
        
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
        return auth()->user()->getEffectiveDoctorUser();
    }
}
