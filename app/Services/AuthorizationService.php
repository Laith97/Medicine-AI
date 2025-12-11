<?php

namespace App\Services;

use App\Models\User;
use App\Models\Appointment;
use Illuminate\Support\Facades\Auth;

class AuthorizationService
{
    /**
     * Check if user can view an appointment
     */
    public function canViewAppointment(User $user, Appointment $appointment): bool
    {
        // Patients can view their own appointments
        if ($user->isPatient() && $appointment->patient_id === $user->id) {
            return true;
        }

        // Doctors can view appointments with their patients
        if ($user->isDoctor() && $appointment->doctor_id === $user->doctor->id) {
            return true;
        }

        // Hospital admins can view appointments for patients in their hospital
        if ($user->isHospitalAdmin() && $appointment->patient && $appointment->patient->hospital_id === $user->hospital_id) {
            return true;
        }

        // System admins can view all appointments
        if ($user->isAdmin()) {
            return true;
        }

        return false;
    }

    /**
     * Check if user can modify an appointment
     */
    public function canModifyAppointment(User $user, Appointment $appointment): bool
    {
        // Patients can only modify their own appointments
        if ($user->isPatient() && $appointment->patient_id === $user->id) {
            return true;
        }

        // Doctors can modify appointments with their patients
        if ($user->isDoctor() && $appointment->doctor_id === $user->doctor->id) {
            return true;
        }

        // Hospital admins can modify appointments for patients in their hospital
        if ($user->isHospitalAdmin() && $appointment->patient && $appointment->patient->hospital_id === $user->hospital_id) {
            return true;
        }

        // System admins can modify all appointments
        if ($user->isAdmin()) {
            return true;
        }

        return false;
    }

    /**
     * Check if user can cancel an appointment
     */
    public function canCancelAppointment(User $user, Appointment $appointment): bool
    {
        // Must be able to modify the appointment and appointment must be cancellable
        return $this->canModifyAppointment($user, $appointment) && $appointment->canBeCancelled();
    }

    /**
     * Check if user can reschedule an appointment
     */
    public function canRescheduleAppointment(User $user, Appointment $appointment): bool
    {
        // Must be able to modify the appointment and appointment must be reschedulable
        return $this->canModifyAppointment($user, $appointment) && $appointment->canBeRescheduled();
    }

    /**
     * Check if user can access eligibility information
     */
    public function canAccessEligibility(User $user, $patientId): bool
    {
        // Patients can access their own eligibility
        if ($user->isPatient() && $user->id === $patientId) {
            return true;
        }

        // System admins can access all eligibility
        if ($user->isAdmin()) {
            return true;
        }

        // For doctors and hospital admins, batch fetch patient data to prevent N+1 queries
        if ($user->isDoctor() || $user->isHospitalAdmin()) {
            $patient = User::find($patientId);
            if (!$patient) {
                return false;
            }

            if ($user->isDoctor()) {
                return $patient->primary_doctor_id === $user->id;
            }

            if ($user->isHospitalAdmin()) {
                return $patient->hospital_id === $user->hospital_id;
            }
        }

        return false;
    }

    /**
     * Batch check access for multiple patients to prevent N+1 queries
     */
    public function canAccessEligibilityForPatients(User $user, array $patientIds): array
    {
        $results = [];

        if ($user->isAdmin()) {
            // System admins can access all patients
            foreach ($patientIds as $patientId) {
                $results[$patientId] = true;
            }
            return $results;
        }

        if ($user->isPatient()) {
            // Patients can only access their own data
            foreach ($patientIds as $patientId) {
                $results[$patientId] = ($user->id === $patientId);
            }
            return $results;
        }

        // For doctors and hospital admins, batch fetch patient data
        if ($user->isDoctor() || $user->isHospitalAdmin()) {
            $patients = User::whereIn('id', $patientIds)->get();

            foreach ($patientIds as $patientId) {
                $patient = $patients->firstWhere('id', $patientId);

                if (!$patient) {
                    $results[$patientId] = false;
                    continue;
                }

                if ($user->isDoctor()) {
                    $results[$patientId] = ($patient->primary_doctor_id === $user->id);
                } elseif ($user->isHospitalAdmin()) {
                    $results[$patientId] = ($patient->hospital_id === $user->hospital_id);
                } else {
                    $results[$patientId] = false;
                }
            }
        }

        return $results;
    }

    /**
     * Check if user can access claims
     */
    public function canAccessClaims(User $user, $patientId): bool
    {
        // Patients can access their own claims
        if ($user->isPatient() && $user->id === $patientId) {
            return true;
        }

        // Doctors can access claims for their patients
        if ($user->isDoctor()) {
            $patient = User::find($patientId);
            return $patient && $patient->primary_doctor_id === $user->id;
        }

        // Hospital admins can access claims for patients in their hospital
        if ($user->isHospitalAdmin()) {
            $patient = User::find($patientId);
            return $patient && $patient->hospital_id === $user->hospital_id;
        }

        // System admins can access all claims
        if ($user->isAdmin()) {
            return true;
        }

        return false;
    }

    /**
     * Get authenticated user with role validation
     */
    public function getAuthenticatedUser(): ?User
    {
        $user = Auth::user();
        if (!$user) {
            return null;
        }

        // Validate user has a valid role
        $validRoles = ['patient', 'doctor', 'hospital_admin', 'admin'];
        if (!in_array($user->role, $validRoles)) {
            return null;
        }

        return $user;
    }

    /**
     * Check if user is authenticated and has required role
     */
    public function isAuthenticatedWithRole(string $requiredRole): bool
    {
        $user = $this->getAuthenticatedUser();
        if (!$user) {
            return false;
        }

        return match($requiredRole) {
            'patient' => $user->isPatient(),
            'doctor' => $user->isDoctor(),
            'hospital_admin' => $user->isHospitalAdmin(),
            'admin' => $user->isAdmin(),
            default => false,
        };
    }
}
