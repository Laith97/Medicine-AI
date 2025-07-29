<?php

namespace Tests\Unit\Models;

use App\Models\User;
use PHPUnit\Framework\TestCase;

class UserUnitTest extends TestCase
{
    public function test_user_can_be_instantiated()
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

    public function test_user_has_correct_fillable_attributes()
    {
        $expectedFillable = [
            'name', 'email', 'phone', 'password', 'role', 'phone',
            'date_of_birth', 'gender', 'address', 'city', 'state', 'zip_code',
            'emergency_contact_name', 'emergency_contact_phone', 'email_verified_at',
            'stripe_customer_id', 'current_plan', 'monthly_cost_limit',
            'subscription_ends_at', 'subscription_active'
        ];

        $user = new User();
        $this->assertEquals($expectedFillable, $user->getFillable());
    }

    public function test_user_has_correct_hidden_attributes()
    {
        $expectedHidden = [
            'password',
            'remember_token',
        ];

        $user = new User();
        $this->assertEquals($expectedHidden, $user->getHidden());
    }

    public function test_user_is_doctor_method_returns_true_for_doctor()
    {
        $user = new User(['role' => 'doctor']);
        $this->assertTrue($user->isDoctor());
    }

    public function test_user_is_doctor_method_returns_false_for_non_doctor()
    {
        $user = new User(['role' => 'patient']);
        $this->assertFalse($user->isDoctor());
    }

    public function test_user_is_patient_method_returns_true_for_patient()
    {
        $user = new User(['role' => 'patient']);
        $this->assertTrue($user->isPatient());
    }

    public function test_user_is_patient_method_returns_false_for_non_patient()
    {
        $user = new User(['role' => 'doctor']);
        $this->assertFalse($user->isPatient());
    }

    public function test_user_subscription_active_attribute()
    {
        $activeUser = new User(['subscription_active' => true]);
        $inactiveUser = new User(['subscription_active' => false]);

        $this->assertTrue($activeUser->subscription_active);
        $this->assertFalse($inactiveUser->subscription_active);
    }

    public function test_user_has_subscription_ends_at_in_fillable()
    {
        $user = new User();
        $this->assertContains('subscription_ends_at', $user->getFillable());
    }

    public function test_user_get_full_address_attribute_with_all_fields()
    {
        $user = new User([
            'address' => '123 Main St',
            'city' => 'Anytown',
            'state' => 'CA',
            'zip_code' => '12345'
        ]);

        $expected = '123 Main St, Anytown, CA, 12345';
        $this->assertEquals($expected, $user->getFullAddressAttribute());
    }

    public function test_user_get_full_address_attribute_with_partial_fields()
    {
        $user = new User([
            'address' => '123 Main St',
            'city' => 'Anytown',
            'state' => null,
            'zip_code' => null,
            'country' => null
        ]);

        $expected = '123 Main St, Anytown';
        $this->assertEquals($expected, $user->getFullAddressAttribute());
    }

    public function test_user_get_full_address_attribute_with_empty_fields()
    {
        $user = new User([
            'address' => null,
            'city' => null,
            'state' => null,
            'zip_code' => null,
            'country' => null
        ]);

        $this->assertEquals('', $user->getFullAddressAttribute());
    }

    public function test_user_casts_attributes_correctly()
    {
        $user = new User();
        $casts = $user->getCasts();

        $this->assertArrayHasKey('email_verified_at', $casts);
        $this->assertArrayHasKey('password', $casts);
        $this->assertArrayHasKey('subscription_active', $casts);
        $this->assertArrayHasKey('subscription_ends_at', $casts);
        $this->assertArrayHasKey('date_of_birth', $casts);
        $this->assertArrayHasKey('monthly_cost_limit', $casts);
    }

    public function test_user_role_constants()
    {
        // Test that role checking works with different roles
        $doctor = new User(['role' => 'doctor']);
        $patient = new User(['role' => 'patient']);
        $admin = new User(['role' => 'admin']);

        $this->assertTrue($doctor->isDoctor());
        $this->assertFalse($doctor->isPatient());

        $this->assertTrue($patient->isPatient());
        $this->assertFalse($patient->isDoctor());

        $this->assertFalse($admin->isDoctor());
        $this->assertFalse($admin->isPatient());
    }

    public function test_user_subscription_status_edge_cases()
    {
        // Test null subscription_active
        $user1 = new User(['subscription_active' => null]);
        $this->assertNull($user1->subscription_active);

        // Test false subscription_active
        $user2 = new User(['subscription_active' => false]);
        $this->assertFalse($user2->subscription_active);

        // Test true subscription_active
        $user3 = new User(['subscription_active' => true]);
        $this->assertTrue($user3->subscription_active);
    }

    public function test_user_address_formatting_edge_cases()
    {
        // Test with only address
        $user1 = new User(['address' => '123 Main St']);
        $this->assertEquals('123 Main St', $user1->getFullAddressAttribute());

        // Test with address and city
        $user2 = new User([
            'address' => '123 Main St',
            'city' => 'Anytown'
        ]);
        $this->assertEquals('123 Main St, Anytown', $user2->getFullAddressAttribute());

        // Test with empty strings (should be treated as null)
        $user3 = new User([
            'address' => '',
            'city' => '',
            'state' => '',
            'zip_code' => '',
            'country' => ''
        ]);
        $this->assertEquals('', $user3->getFullAddressAttribute());
    }
}
