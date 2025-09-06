<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('doctor.{doctorId}', function ($user, $doctorId) {
    // Authorize the authenticated user if they are the doctor or a sub-user of that doctor
    if ((string) $user->id === (string) $doctorId) {
        return true;
    }
    // Allow sub-users of this doctor
    if (method_exists($user, 'isSubUser') && $user->isSubUser()) {
        $parent = $user->parentUser;
        return $parent && (string) $parent->id === (string) $doctorId;
    }
    return false;
});
