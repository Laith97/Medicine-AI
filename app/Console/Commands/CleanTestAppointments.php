<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Doctor;
use App\Models\Appointment;

class CleanTestAppointments extends Command
{
    protected $signature = 'test:clean-appointments {--dry-run : Show what would be deleted without actually deleting}';
    protected $description = 'Delete all appointments associated with newdoc (as doctor) and newpatient (as patient) users';

    public function handle()
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->info('DRY RUN MODE - No appointments will be deleted');
        }

        $this->info('Cleaning up test appointments...');

        // Find newdoc user and their doctor profile
        $newdocUser = User::where('email', 'like', '%newdoc%')->orWhere('name', 'like', '%newdoc%')->first();
        if (!$newdocUser) {
            $this->warn('newdoc user not found (searched by email/name containing "newdoc")');
            $doctorAppointmentsCount = 0;
        } else {
            $doctor = $newdocUser->doctor;
            if (!$doctor) {
                $this->warn('newdoc user does not have a doctor profile');
                $doctorAppointmentsCount = 0;
            } else {
                $doctorAppointments = Appointment::where('doctor_id', $doctor->id);
                $doctorAppointmentsCount = $doctorAppointments->count();

                $this->info("Found {$doctorAppointmentsCount} appointments for newdoc (doctor ID: {$doctor->id})");

                if (!$dryRun && $doctorAppointmentsCount > 0) {
                    $doctorAppointments->delete();
                    $this->info("Deleted {$doctorAppointmentsCount} appointments for newdoc");
                }
            }
        }

        // Find newpatient user
        $newpatientUser = User::where('email', 'like', '%newpatient%')->orWhere('name', 'like', '%newpatient%')->first();
        if (!$newpatientUser) {
            $this->warn('newpatient user not found (searched by email/name containing "newpatient")');
            $patientAppointmentsCount = 0;
        } else {
            $patientAppointments = Appointment::where('patient_id', $newpatientUser->id);
            $patientAppointmentsCount = $patientAppointments->count();

            $this->info("Found {$patientAppointmentsCount} appointments for newpatient (patient ID: {$newpatientUser->id})");

            if (!$dryRun && $patientAppointmentsCount > 0) {
                $patientAppointments->delete();
                $this->info("Deleted {$patientAppointmentsCount} appointments for newpatient");
            }
        }

        $totalDeleted = $doctorAppointmentsCount + $patientAppointmentsCount;

        if ($dryRun) {
            $this->info("DRY RUN COMPLETE - Would delete {$totalDeleted} appointments total");
        } else {
            $this->info("CLEANUP COMPLETE - Deleted {$totalDeleted} appointments total");
        }

        // Show remaining appointments count for verification
        $remainingAppointments = Appointment::count();
        $this->info("Remaining appointments in system: {$remainingAppointments}");

        return 0;
    }
}