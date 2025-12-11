<?php

namespace App\Policies;

use App\Models\User;
use App\Models\HepProgram;
use Illuminate\Auth\Access\HandlesAuthorization;

class HepProgramPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the hep program.
     */
    public function view(User $user, HepProgram $program): bool
    {
        // Doctors can view their own HEP programs
        if ($user->isDoctor()) {
            return $program->doctor->user_id === $user->id;
        }

        // Sub-users can view HEP programs of their parent doctor
        if ($user->isSubUser() && $user->parentUser) {
            return $program->doctor->user_id === $user->parentUser->id;
        }

        return false;
    }

    /**
     * Determine whether the user can create hep programs.
     */
    public function create(User $user): bool
    {
        return $user->isDoctor() || ($user->isSubUser() && $user->parentUser && $user->parentUser->isDoctor());
    }

    /**
     * Determine whether the user can update the hep program.
     */
    public function update(User $user, HepProgram $program): bool
    {
        // Doctors can update their own HEP programs
        if ($user->isDoctor()) {
            return $program->doctor->user_id === $user->id;
        }

        // Sub-users can update HEP programs of their parent doctor
        if ($user->isSubUser() && $user->parentUser) {
            return $program->doctor->user_id === $user->parentUser->id;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the hep program.
     */
    public function delete(User $user, HepProgram $program): bool
    {
        // Doctors can delete their own HEP programs
        if ($user->isDoctor()) {
            return $program->doctor->user_id === $user->id;
        }

        // Sub-users can delete HEP programs of their parent doctor
        if ($user->isSubUser() && $user->parentUser) {
            return $program->doctor->user_id === $user->parentUser->id;
        }

        return false;
    }
}
