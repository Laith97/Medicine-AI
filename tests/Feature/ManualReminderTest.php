<?php

use App\Models\User;
use App\Models\Admin;
use App\Models\MonthlyInvoiceSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use App\Notifications\GracePeriodReminder;

uses(RefreshDatabase::class);

test('manual reminders send emails directly without queuing', function () {
    Mail::fake();
    
    // Create an admin user
    $admin = Admin::factory()->create([
        'email' => 'admin@test.com',
        'password' => bcrypt('password'),
    ]);
    
    // Create a regular user with monthly invoice setting in grace period
    $user = User::factory()->create([
        'email' => 'user@test.com',
        'name' => 'Test User',
    ]);
    
    // Create monthly invoice setting that puts user in grace period
    $setting = MonthlyInvoiceSetting::create([
        'user_id' => $user->id,
        'billing_amount' => 50.00,
        'subscription_period_months' => 1,
        'subscription_starts_at' => now()->subMonths(2),
        'subscription_ends_at' => now()->subDays(2), // Expired 2 days ago
        'grace_period_days' => 7,
        'warning_period_days' => 3,
        'reminder_frequency_days' => 1,
        'is_active' => true,
        'is_restricted' => false,
    ]);
    
    // Login as admin
    $this->actingAs($admin, 'admin');
    
    // Send manual reminders
    $response = $this->post(route('admin.send-reminders'), [
        'reminder_type' => 'grace_period',
        'force_send' => true,
    ]);
    
    // Assert the request was successful
    $response->assertRedirect();
    $response->assertSessionHas('success');
    
    // Assert that mail was sent (not queued)
    Mail::assertSent(GracePeriodReminder::class, function ($mail) use ($user) {
        return $mail->hasTo($user->email);
    });
    
    // Assert that no jobs were queued
    Mail::assertNothingQueued();
});

test('admin can toggle doctor status', function () {
    // Create an admin user
    $admin = Admin::factory()->create([
        'email' => 'admin@test.com',
        'password' => bcrypt('password'),
    ]);
    
    // Create a user with doctor profile
    $user = User::factory()->create([
        'role' => 'doctor',
    ]);
    
    $user->doctor()->create([
        'specialty_id' => 1,
        'license_number' => 'LIC123456',
        'bio' => 'Test doctor',
        'consultation_fee' => 5000,
        'appointment_duration' => 30,
        'is_active' => true,
        'is_verified' => true,
    ]);
    
    // Login as admin
    $this->actingAs($admin, 'admin');
    
    // Initially doctor should be active
    expect($user->doctor->is_active)->toBeTrue();
    
    // Toggle doctor status (deactivate)
    $response = $this->post(route('admin.users.toggle-doctor-status', $user));
    
    $response->assertRedirect();
    $response->assertSessionHas('success');
    
    // Refresh the user and check status
    $user->refresh();
    expect($user->doctor->is_active)->toBeFalse();
    
    // Toggle again (activate)
    $response = $this->post(route('admin.users.toggle-doctor-status', $user));
    
    $response->assertRedirect();
    $response->assertSessionHas('success');
    
    // Refresh the user and check status
    $user->refresh();
    expect($user->doctor->is_active)->toBeTrue();
});