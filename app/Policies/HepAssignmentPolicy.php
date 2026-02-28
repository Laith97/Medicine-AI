<?php

namespace App\Policies;

use App\Models\User;
use App\Models\HepAssignment;
use Illuminate\Auth\Access\HandlesAuthorization;

class HepAssignmentPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the HEP assignment.
     */
    public function view(User $user, HepAssignment $assignment): bool
    {
        // Patients can view their own assignments
        if ($user->isPatient()) {
            return $assignment->patient_id === $user->id;
        }

        // Doctors can view assignments they created
        if ($user->isDoctor()) {
            return $assignment->assigned_by === $user->id ||
                   $assignment->hepProgram->doctor->user_id === $user->id;
        }

        // Sub-users can view assignments from their parent doctor
        if ($user->isSubUser() && $user->parentUser) {
            return $assignment->assigned_by === $user->parentUser->id ||
                   $assignment->hepProgram->doctor->user_id === $user->parentUser->id;
        }

        return false;
    }

    /**
     * Determine whether the user can update the HEP assignment.
     */
    public function update(User $user, HepAssignment $assignment): bool
    {
        // Patients can update their own progress (not assignment details)
        if ($user->isPatient()) {
            return $assignment->patient_id === $user->id;
        }

        // Doctors can update assignments they created
        if ($user->isDoctor()) {
            return $assignment->assigned_by === $user->id ||
                   $assignment->hepProgram->doctor->user_id === $user->id;
        }

        // Sub-users can update assignments from their parent doctor
        if ($user->isSubUser() && $user->parentUser) {
            return $assignment->assigned_by === $user->parentUser->id ||
                   $assignment->hepProgram->doctor->user_id === $user->parentUser->id;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the HEP assignment.
     */
    public function delete(User $user, HepAssignment $assignment): bool
    {
        // Only doctors who created the assignment can delete it
        if ($user->isDoctor()) {
            return $assignment->assigned_by === $user->id;
        }

        // Sub-users can delete assignments from their parent doctor
        if ($user->isSubUser() && $user->parentUser) {
            return $assignment->assigned_by === $user->parentUser->id;
        }

        return false;
    }

    /**
     * Determine whether the user can view progress data for the assignment.
     */
    public function viewProgress(User $user, HepAssignment $assignment): bool
    {
        // Same logic as view
        return $this->view($user, $assignment);
    }

    /**
     * Determine whether the user can export data for the assignment.
     */
    public function export(User $user, HepAssignment $assignment): bool
    {
        // Patients can export their own data
        if ($user->isPatient()) {
            return $assignment->patient_id === $user->id;
        }

        // Doctors can export data for their assignments
        if ($user->isDoctor()) {
            return $assignment->assigned_by === $user->id ||
                   $assignment->hepProgram->doctor->user_id === $user->id;
        }

        return false;
    }
}
