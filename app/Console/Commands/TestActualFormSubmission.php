<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\AdminController;
use Illuminate\Http\Request;
use App\Models\Hospital;
use App\Models\User;

class TestActualFormSubmission extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:actual-form-submission';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test actual form submission with real data';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🧪 Testing Actual Form Submission');
        $this->newLine();

        // Test 1: Hospital Admin with existing hospital
        $hospital = Hospital::first();
        if ($hospital) {
            $this->info('Test 1: Hospital Admin with existing hospital');
            $this->testActualSubmission([
                'name' => 'Test Hospital Admin Final',
                'email' => 'test.final.admin@hospital.com',
                'phone' => '+1555999001',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role' => 'hospital_admin',
                'hospital_id' => (string)$hospital->id,
            ]);
        }

        // Test 2: Hospital Admin with new hospital
        $this->info('Test 2: Hospital Admin with new hospital');
        $this->testActualSubmission([
            'name' => 'Test Hospital Admin New',
            'email' => 'test.new.admin@newhospital.com',
            'phone' => '+1555999002',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'hospital_admin',
            'hospital_id' => 'new',
            'hospital_name' => 'Final Test Hospital',
            'hospital_email' => 'info@finaltesthospital.com',
            'hospital_phone' => '+1555999003',
        ]);

        // Test 3: Missing hospital_id (should fail)
        $this->info('Test 3: Hospital Admin without hospital selection (should fail)');
        $this->testActualSubmission([
            'name' => 'Test Hospital Admin No Hospital',
            'email' => 'test.nohospital.admin@hospital.com',
            'phone' => '+1555999004',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'hospital_admin',
            // Missing hospital_id
        ]);
    }

    private function testActualSubmission($data)
    {
        try {
            $this->info('Submitting data:');
            foreach ($data as $key => $value) {
                $this->info("  {$key}: {$value}");
            }

            // Create a proper request
            $request = new Request();
            $request->merge($data);
            $request->setMethod('POST');

            // Create controller instance
            $controller = new AdminController();

            // Try to create the user
            try {
                $response = $controller->store($request);
                
                if ($response instanceof \Illuminate\Http\RedirectResponse) {
                    $this->info('✅ SUCCESS: User created successfully');
                    
                    // Check if user was actually created
                    $user = User::where('email', $data['email'])->first();
                    if ($user) {
                        $this->info("   - User ID: {$user->id}");
                        $this->info("   - Name: {$user->name}");
                        $this->info("   - Email: {$user->email}");
                        $this->info("   - Role: {$user->role}");
                        if ($user->hospital) {
                            $this->info("   - Hospital: {$user->hospital->name}");
                        }
                    }
                } else {
                    $this->error('❌ Unexpected response type');
                }
                
            } catch (\Illuminate\Validation\ValidationException $e) {
                $this->error('❌ Validation failed:');
                foreach ($e->errors() as $field => $errors) {
                    foreach ($errors as $error) {
                        $this->error("  - {$field}: {$error}");
                    }
                }
            }

        } catch (\Exception $e) {
            $this->error('❌ Exception: ' . $e->getMessage());
            $this->error('   Stack trace: ' . $e->getTraceAsString());
        }

        $this->newLine();
    }
}