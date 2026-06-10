<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Doctor;
use App\Models\Hospital;
use App\Models\SystemSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SmsSettingsApiTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function test_doctor_get_sms_settings_endpoint()
    {
        // Create doctor user
        $doctorUser = User::factory()->create(['role' => 'doctor']);
        $doctor = Doctor::factory()->create([
            'user_id' => $doctorUser->id,
            'sms_provider' => 'twilio'
        ]);

        // Authenticate as doctor
        $this->actingAs($doctorUser);

        // Test GET endpoint
        $response = $this->get("/api/doctor/sms-settings");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'sms_provider',
                    'available_providers'
                ]
            ])
            ->assertJson([
                'data' => [
                    'sms_provider' => 'twilio'
                ]
            ]);
    }

    /** @test */
    public function test_doctor_update_sms_settings_endpoint()
    {
        // Create doctor user
        $doctorUser = User::factory()->create(['role' => 'doctor']);
        $doctor = Doctor::factory()->create([
            'user_id' => $doctorUser->id,
            'sms_provider' => 'twilio'
        ]);

        // Authenticate as doctor
        $this->actingAs($doctorUser);

        // Test PUT endpoint
        $response = $this->put("/api/doctor/sms-settings", [
            'sms_provider' => 'plivo'
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'SMS settings updated successfully',
                'data' => [
                    'sms_provider' => 'plivo'
                ]
            ]);

        // Verify database update
        $this->assertDatabaseHas('doctors', [
            'id' => $doctor->id,
            'sms_provider' => 'plivo'
        ]);
    }

    /** @test */
    public function test_hospital_admin_get_sms_settings_endpoint()
    {
        // Create hospital admin user
        $hospitalAdmin = User::factory()->create(['role' => 'hospital_admin']);
        $hospital = Hospital::factory()->create([
            'admin_id' => $hospitalAdmin->id,
            'sms_provider' => 'messagebird'
        ]);

        // Authenticate as hospital admin
        $this->actingAs($hospitalAdmin);

        // Test GET endpoint
        $response = $this->get("/api/hospitals/{$hospital->id}/sms-settings");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'sms_provider',
                    'available_providers'
                ]
            ])
            ->assertJson([
                'data' => [
                    'sms_provider' => 'messagebird'
                ]
            ]);
    }

    /** @test */
    public function test_hospital_admin_update_sms_settings_endpoint()
    {
        // Create hospital admin user
        $hospitalAdmin = User::factory()->create(['role' => 'hospital_admin']);
        $hospital = Hospital::factory()->create([
            'admin_id' => $hospitalAdmin->id,
            'sms_provider' => 'messagebird'
        ]);

        // Authenticate as hospital admin
        $this->actingAs($hospitalAdmin);

        // Test PUT endpoint
        $response = $this->put("/api/hospitals/{$hospital->id}/sms-settings", [
            'sms_provider' => 'unifonic'
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'SMS settings updated successfully',
                'data' => [
                    'sms_provider' => 'unifonic'
                ]
            ]);

        // Verify database update
        $this->assertDatabaseHas('hospitals', [
            'id' => $hospital->id,
            'sms_provider' => 'unifonic'
        ]);
    }

    /** @test */
    public function test_validation_of_provider_values()
    {
        // Create doctor user
        $doctorUser = User::factory()->create(['role' => 'doctor']);
        $doctor = Doctor::factory()->create(['user_id' => $doctorUser->id]);

        // Authenticate as doctor
        $this->actingAs($doctorUser);

        // Test invalid provider value
        $response = $this->put("/api/doctor/sms-settings", [
            'sms_provider' => 'invalid_provider'
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['sms_provider']);
    }

    /** @test */
    public function test_unauthorized_access_to_doctor_settings()
    {
        // Create two doctors
        $doctorUser1 = User::factory()->create(['role' => 'doctor']);
        $doctor1 = Doctor::factory()->create(['user_id' => $doctorUser1->id]);
        
        $doctorUser2 = User::factory()->create(['role' => 'doctor']);
        $doctor2 = Doctor::factory()->create(['user_id' => $doctorUser2->id]);

        // Authenticate as doctor 1 and try to access doctor 2's settings
        $this->actingAs($doctorUser1);

        $response = $this->get("/api/doctor/sms-settings");

        $response->assertStatus(403);
    }

    /** @test */
    public function test_unauthorized_access_to_hospital_settings()
    {
        // Create hospital admin and regular user
        $hospitalAdmin = User::factory()->create(['role' => 'hospital_admin']);
        $hospital = Hospital::factory()->create(['admin_id' => $hospitalAdmin->id]);
        
        $regularUser = User::factory()->create(['role' => 'patient']);

        // Authenticate as regular user and try to access hospital settings
        $this->actingAs($regularUser);

        $response = $this->get("/api/hospitals/{$hospital->id}/sms-settings");

        $response->assertStatus(403);
    }

    /** @test */
    public function test_system_admin_can_access_all_settings()
    {
        // Create system admin
        $systemAdmin = User::factory()->create(['role' => 'admin']);
        
        // Create doctor and hospital
        $doctorUser = User::factory()->create(['role' => 'doctor']);
        $doctor = Doctor::factory()->create(['user_id' => $doctorUser->id]);
        $hospital = Hospital::factory()->create();

        // Authenticate as system admin
        $this->actingAs($systemAdmin);

        // Test access to doctor settings
        $response = $this->get("/api/doctor/sms-settings");
        $response->assertStatus(200);

        // Test access to hospital settings
        $response = $this->get("/api/hospitals/{$hospital->id}/sms-settings");
        $response->assertStatus(200);
    }

    /** @test */
    public function test_response_format_and_data()
    {
        // Create doctor with SMS provider
        $doctorUser = User::factory()->create(['role' => 'doctor']);
        $doctor = Doctor::factory()->create([
            'user_id' => $doctorUser->id,
            'sms_provider' => 'twilio'
        ]);

        // Authenticate as doctor
        $this->actingAs($doctorUser);

        // Test response format
        $response = $this->get("/api/doctor/sms-settings");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'current_setting',
                    'effective_provider',
                    'available_providers',
                    'is_inherited',
                    'hospital_id',
                    'hospital_name'
                ]
            ]);
    }
}