<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\AdminController;
use Illuminate\Http\Request;
use App\Models\Hospital;

class TestFormSubmission extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:form-submission';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test form submission scenarios';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🧪 Testing Form Submission Scenarios');
        $this->newLine();

        // Test 1: Hospital Admin without hospital_id (should fail)
        $this->info('Test 1: Hospital Admin without hospital selection');
        $this->testScenario([
            'name' => 'Test Admin',
            'email' => 'test.admin.new@example.com',
            'phone' => '+1555000100',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'hospital_admin',
            // Missing hospital_id - should trigger validation error
        ]);

        // Test 2: Hospital Admin with existing hospital (should pass)
        $hospital = Hospital::first();
        if ($hospital) {
            $this->info('Test 2: Hospital Admin with existing hospital');
            $this->testScenario([
                'name' => 'Test Admin 2',
                'email' => 'test.admin2.new@example.com',
                'phone' => '+1555000101',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role' => 'hospital_admin',
                'hospital_id' => (string)$hospital->id,
            ]);
        }

        // Test 3: Hospital Admin with new hospital but missing hospital details (should fail)
        $this->info('Test 3: Hospital Admin with new hospital but missing details');
        $this->testScenario([
            'name' => 'Test Admin 3',
            'email' => 'test.admin3.new@example.com',
            'phone' => '+1555000102',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'hospital_admin',
            'hospital_id' => 'new',
            // Missing hospital_name and hospital_email - should trigger validation error
        ]);

        // Test 4: Hospital Admin with new hospital and complete details (should pass)
        $this->info('Test 4: Hospital Admin with new hospital and complete details');
        $this->testScenario([
            'name' => 'Test Admin 4',
            'email' => 'test.admin4.new@example.com',
            'phone' => '+1555000103',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'hospital_admin',
            'hospital_id' => 'new',
            'hospital_name' => 'Test New Hospital',
            'hospital_email' => 'info@testnew.com',
            'hospital_phone' => '+1555000104',
        ]);
    }

    private function testScenario($data)
    {
        try {
            $this->info('Submitting data:');
            foreach ($data as $key => $value) {
                $this->info("  {$key}: {$value}");
            }

            // Create request
            $request = Request::create('/admin/users', 'POST', $data);
            $request->headers->set('Content-Type', 'application/x-www-form-urlencoded');

            // Test validation
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
                $this->info('✅ Validation passed - would create user successfully');
            }

        } catch (\Exception $e) {
            $this->error('❌ Exception: ' . $e->getMessage());
        }

        $this->newLine();
    }
}