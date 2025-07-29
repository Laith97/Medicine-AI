<?php

namespace Tests\Unit\Models;

use App\Models\User;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserBasicTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_be_created()
    {
        $user = new User([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'role' => 'patient'
        ]);

        $this->assertEquals('Test User', $user->name);
        $this->assertEquals('test@example.com', $user->email);
        $this->assertEquals('patient', $user->role);
    }

    public function test_user_has_fillable_attributes()
    {
        $fillable = [
            'name', 'email', 'email_verified_at', 'password', 'role',
            'phone', 'address', 'city', 'state', 'zip_code', 'country',
            'date_of_birth', 'gender', 'emergency_contact_name',
            'emergency_contact_phone', 'medical_history', 'current_medications',
            'allergies', 'insurance_provider', 'insurance_policy_number',
            'preferred_language', 'subscription_active', 'current_plan',
            'subscription_ends_at', 'stripe_customer_id', 'monthly_cost_limit'
        ];

        $user = new User();
        $this->assertEquals($fillable, $user->getFillable());
    }

    public function test_user_has_hidden_attributes()
    {
        $hidden = [
            'password',
            'remember_token',
            'stripe_customer_id',
        ];

        $user = new User();
        $this->assertEquals($hidden, $user->getHidden());
    }

    public function test_user_is_doctor_method()
    {
        $doctor = new User(['role' => 'doctor']);
        $patient = new User(['role' => 'patient']);

        $this->assertTrue($doctor->isDoctor());
        $this->assertFalse($patient->isDoctor());
    }

    public function test_user_is_patient_method()
    {
        $doctor = new User(['role' => 'doctor']);
        $patient = new User(['role' => 'patient']);

        $this->assertFalse($doctor->isPatient());
        $this->assertTrue($patient->isPatient());
    }

    public function test_user_has_active_subscription()
    {
        $activeUser = new User([
            'subscription_active' => true,
            'subscription_ends_at' => now()->addMonth()
        ]);

        $inactiveUser = new User([
            'subscription_active' => false,
            'subscription_ends_at' => now()->subMonth()
        ]);

        $this->assertTrue($activeUser->hasActiveSubscription());
        $this->assertFalse($inactiveUser->hasActiveSubscription());
    }

    public function test_user_get_full_address_attribute()
    {
        $user = new User([
            'address' => '123 Main St',
            'city' => 'Anytown',
            'state' => 'CA',
            'zip_code' => '12345',
            'country' => 'USA'
        ]);

        $expected = '123 Main St, Anytown, CA 12345, USA';
        $this->assertEquals($expected, $user->full_address);
    }

    public function test_user_get_full_address_attribute_with_missing_fields()
    {
        $user = new User([
            'address' => '123 Main St',
            'city' => 'Anytown'
        ]);

        $expected = '123 Main St, Anytown';
        $this->assertEquals($expected, $user->full_address);
    }

    public function test_user_get_full_address_attribute_empty()
    {
        $user = new User();
        $this->assertEquals('', $user->full_address);
    }
}
