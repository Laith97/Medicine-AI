<?php

namespace App\Traits;

use Illuminate\Support\Facades\Auth;

trait HandlesEffectiveDoctor
{
    /**
     * Get the effective doctor for the current user
     * For sub-users, returns the parent doctor's profile
     * For doctors, returns their own profile
     */
    protected function getEffectiveDoctor()
    {
        $user = Auth::user();
        
        if ($user->isSubUser()) {
            return $user->parentUser?->doctor;
        }
        
        return $user->doctor;
    }
    
    /**
     * Get the effective doctor user for the current user
     * For sub-users, returns the parent doctor user
     * For doctors, returns themselves
     */
    protected function getEffectiveDoctorUser()
    {
        $user = Auth::user();
        
        if ($user->isSubUser()) {
            return $user->parentUser;
        }
        
        return $user;
    }
    
    /**
     * Get the effective assigned patients for the current user
     * For sub-users, returns the parent doctor's assigned patients
     * For doctors, returns their own assigned patients
     */
    protected function getEffectiveAssignedPatients()
    {
        return $this->getEffectiveDoctorUser()->assignedPatients();
    }
}