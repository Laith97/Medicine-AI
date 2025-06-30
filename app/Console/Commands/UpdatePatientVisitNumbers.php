<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class UpdatePatientVisitNumbers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'patients:update-visit-numbers';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update patient_key and visit_number fields for all patient records';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Updating patient visit numbers...');
        
        // Import the PatientAnalysis model
        $patientModel = new \App\Models\PatientAnalysis();
        
        // Get all users
        $users = \App\Models\User::all();
        
        $this->info('Processing ' . $users->count() . ' users...');
        
        $bar = $this->output->createProgressBar($users->count());
        $bar->start();
        
        foreach ($users as $user) {
            // Get all records for this user
            $records = \App\Models\PatientAnalysis::where('user_id', $user->id)->get();
            
            // Group records by name, age, gender
            $patientGroups = [];
            
            foreach ($records as $record) {
                $key = $record->name . '-' . $record->age . '-' . $record->gender;
                
                if (!isset($patientGroups[$key])) {
                    $patientGroups[$key] = [];
                }
                
                $patientGroups[$key][] = $record;
            }
            
            // Process each patient group
            foreach ($patientGroups as $key => $patientRecords) {
                // Generate a patient_key
                $patientKey = \App\Models\PatientAnalysis::generatePatientKey(
                    $patientRecords[0]->name,
                    $patientRecords[0]->age,
                    $patientRecords[0]->gender,
                    $user->id
                );
                
                // Sort records by created_at
                usort($patientRecords, function($a, $b) {
                    return $a->created_at <=> $b->created_at;
                });
                
                // Update each record with patient_key and visit_number
                foreach ($patientRecords as $index => $record) {
                    $record->patient_key = $patientKey;
                    $record->visit_number = $index + 1;
                    $record->save();
                }
            }
            
            $bar->advance();
        }
        
        $bar->finish();
        $this->newLine();
        $this->info('Patient visit numbers updated successfully!');
    }
}
