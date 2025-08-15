<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Http\Request;
use App\Http\Controllers\AdminController;
use App\Models\Hospital;

class TestHospitalAdminForm extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:hospital-admin-form';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test hospital admin form submission';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🧪 Testing Hospital Admin Form Submission');
        $this->newLine();

        // Test 1: Missing hospital_id
        $this->info('Test 1: Hospital Admin without hospital_id');
        $this->testFormSubmission([
            'name' => 'Test Admin',
            'email' => 'test@hospital.com',
            'phone' => '+1234567890',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'hospital_admin',
            // Missing hospital_id
        ]);

        // Test 2: With existing hospital
        $hospital = Hospital::first();
        if ($hospital) {
            $this->info('Test 2: Hospital Admin with existing hospital');
            $this->testFormSubmission([
                'name' => 'Test Admin 2',
                'email' => 'test2@hospital.com',
                'phone' => '+1234567891',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role' => 'hospital_admin',
                'hospital_id' => $hospital->id,
            ]);
        }

        // Test 3: With new hospital
        $this->info('Test 3: Hospital Admin with new hospital');
        $this->testFormSubmission([
            'name' => 'Test Admin 3',
            'email' => 'test3@hospital.com',
            'phone' => '+1234567892',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'hospital_admin',
            'hospital_id' => 'new',
            'hospital_name' => 'Test Hospital',
            'hospital_email' => 'info@testhospital.com',
            'hospital_phone' => '+1234567893',
        ]);
    }

    private function testFormSubmission($data)
    {
        try {
            // Create a mock request
            $request = new Request();
            $request->merge($data);

            $this->info('Submitting data:');
            foreach ($data as $key => $value) {
                $this->info("  {$key}: {$value}");
            }

            // Test validation rules
            $validationRules = [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users'],
                'phone' => ['required', 'string', 'regex:/^\+?[1-9]\d{6,14}$/', 'unique:users'],
                'password' => ['required', 'confirmed'],
                'role' => ['required', 'string', 'in:doctor,hospital_admin,patient'],
            ];

            if ($request->role === 'hospital_admin') {
                if ($request->hospital_id === 'new') {
                    $validationRules['hospital_id'] = ['required'];
                    $validationRules['hospital_name'] = ['required', 'string', 'max:255'];
                    $validationRules['hospital_email'] = ['required', 'email', 'max:255'];
                } else {
                    $validationRules['hospital_id'] = ['required', 'exists:hospitals,id'];
                }
            }

            $messages = [
                'hospital_id.required' => 'Please select a hospital for the hospital admin.',
                'hospital_id.exists' => 'The selected hospital does not exist.',
                'hospital_name.required' => 'Hospital name is required when creating a new hospital.',
                'hospital_email.required' => 'Hospital email is required when creating a new hospital.',
            ];

            $validator = \Validator::make($request->all(), $validationRules, $messages);

            if ($validator->fails()) {
                $this->error('❌ Validation failed:');
                foreach ($validator->errors()->all() as $error) {
                    $this->error("  - {$error}");
                }
            } else {
                $this->info('✅ Validation passed');
            }

        } catch (\Exception $e) {
            $this->error('❌ Exception: ' . $e->getMessage());
        }

        $this->newLine();
    }
}