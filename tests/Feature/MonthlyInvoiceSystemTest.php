<?php

use App\Models\User;
use App\Models\MonthlyInvoiceSetting;
use App\Models\StripeInvoice;
use App\Jobs\CreateMonthlyInvoices;
use App\Jobs\ProcessOverdueInvoices;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    // Create a test user
    $this->user = User::factory()->create([
        'email' => 'doctor@example.com',
        'phone' => '+1234567890'
    ]);
    
    // Create monthly invoice setting
    $this->setting = MonthlyInvoiceSetting::create([
        'user_id' => $this->user->id,
        'monthly_amount' => 100.00,
        'is_active' => true,
        'grace_period_days' => 7,
        'reminder_frequency_days' => 3,
        'restricted_pages' => ['ask-ai', 'dashboard'],
        'is_restricted' => false,
    ]);
});

test('user can view monthly invoice settings', function () {
    expect($this->user->hasMonthlyInvoicing())->toBeTrue();
    expect($this->user->getMonthlyAmount())->toBe(100.00);
    expect($this->user->getGracePeriodDays())->toBe(7);
});

test('monthly invoice generation job is dispatched', function () {
    Queue::fake();
    
    CreateMonthlyInvoices::dispatch('2024-01');
    
    Queue::assertPushed(CreateMonthlyInvoices::class);
});

test('user restriction works', function () {
    // Restrict the user
    $this->setting->update(['is_restricted' => true]);
    
    expect($this->user->fresh()->isRestricted())->toBeTrue();
    
    // Test that restricted pages redirect
    $response = $this->actingAs($this->user)->get('/ask-ai');
    $response->assertRedirect(route('access.restricted'));
});

test('access restriction page shows unpaid invoices', function () {
    // Create an unpaid monthly invoice
    $invoice = StripeInvoice::create([
        'user_id' => $this->user->id,
        'stripe_invoice_id' => 'in_test123',
        'invoice_type' => 'monthly',
        'invoice_month' => 1,
        'invoice_year' => 2024,
        'amount_due' => 10000, // $100.00 in cents
        'amount_paid' => 0,
        'status' => 'open',
        'due_date' => now()->subDays(5),
        'grace_period_ends_at' => now()->subDays(2),
        'currency' => 'usd',
        'description' => 'Monthly service fee for January 2024',
    ]);
    
    // Restrict the user
    $this->setting->update(['is_restricted' => true]);
    
    $response = $this->actingAs($this->user)->get('/access/restricted');
    $response->assertStatus(200);
    $response->assertSee('Monthly service fee for January 2024');
    $response->assertSee('$100.00');
});

test('admin can access monthly invoice management', function () {
    // Create admin user
    $admin = User::factory()->create();
    $admin->admin()->create(['name' => 'Test Admin']);
    
    $response = $this->actingAs($admin)->get('/admin/monthly-invoices');
    $response->assertStatus(200);
    $response->assertSee('Monthly Invoice Management');
});
