<?php

namespace Tests\Unit\Models;

use App\Models\User;
use App\Models\Doctor;
use App\Models\Appointment;
use App\Models\Review;
use App\Models\Subscription;
use App\Models\OpenAIUsage;
use App\Models\StripeInvoice;
use App\Models\MonthlyInvoiceSetting;
use App\Models\DoctorNote;
use App\Models\PatientAnalysis;
use App\Models\Setting;
use Tests\TestCase;

class UserTest extends TestCase
{

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'role' => 'patient',
            'monthly_cost_limit' => 100.00,
            'subscription_active' => true,
            'subscription_ends_at' => now()->addMonth(),
            'current_plan' => 'basic'
        ]);
    }

    public function test_user_can_be_created()
    {
        $this->assertInstanceOf(User::class, $this->user);
        $this->assertEquals('Test User', $this->user->name);
        $this->assertEquals('test@example.com', $this->user->email);
    }

    public function test_user_has_fillable_attributes()
    {
        $fillable = [
            'name', 'email', 'phone', 'password', 'role', 'phone',
            'date_of_birth', 'gender', 'address', 'city', 'state',
            'zip_code', 'emergency_contact_name', 'emergency_contact_phone',
            'email_verified_at', 'stripe_customer_id', 'current_plan',
            'monthly_cost_limit', 'subscription_ends_at', 'subscription_active'
        ];

        $this->assertEquals($fillable, $this->user->getFillable());
    }

    public function test_user_has_hidden_attributes()
    {
        $hidden = ['password', 'remember_token'];
        $this->assertEquals($hidden, $this->user->getHidden());
    }

    public function test_user_casts_attributes_correctly()
    {
        $casts = [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'date_of_birth' => 'date',
            'monthly_cost_limit' => 'decimal:2',
            'subscription_ends_at' => 'datetime',
            'subscription_active' => 'boolean',
        ];

        foreach ($casts as $attribute => $cast) {
            $this->assertEquals($cast, $this->user->getCasts()[$attribute]);
        }
    }

    public function test_user_is_doctor_method()
    {
        $this->assertFalse($this->user->isDoctor());

        $doctor = User::factory()->create(['role' => 'doctor']);
        $this->assertTrue($doctor->isDoctor());
    }

    public function test_user_is_patient_method()
    {
        $this->assertTrue($this->user->isPatient());

        $doctor = User::factory()->create(['role' => 'doctor']);
        $this->assertFalse($doctor->isPatient());
    }

    public function test_user_has_active_subscription()
    {
        $this->assertTrue($this->user->hasActiveSubscription());

        $expiredUser = User::factory()->create([
            'subscription_active' => true,
            'subscription_ends_at' => now()->subDay()
        ]);
        $this->assertFalse($expiredUser->hasActiveSubscription());

        $inactiveUser = User::factory()->create([
            'subscription_active' => false,
            'subscription_ends_at' => now()->addMonth()
        ]);
        $this->assertFalse($inactiveUser->hasActiveSubscription());
    }

    public function test_user_get_plan_config()
    {
        config(['stripe.plans.basic' => ['token_limit' => 1000, 'price' => 10]]);

        $config = $this->user->getPlanConfig();
        $this->assertEquals(['token_limit' => 1000, 'price' => 10], $config);
    }

    public function test_user_monthly_token_usage()
    {
        OpenAIUsage::factory()->create([
            'user_id' => $this->user->id,
            'total_tokens' => 500,
            'created_at' => now()
        ]);

        OpenAIUsage::factory()->create([
            'user_id' => $this->user->id,
            'total_tokens' => 300,
            'created_at' => now()->subMonth()
        ]);

        $this->assertEquals(500, $this->user->getMonthlyTokenUsage());
    }

    public function test_user_has_exceeded_token_limit()
    {
        config(['stripe.plans.basic' => ['token_limit' => 1000]]);

        OpenAIUsage::factory()->create([
            'user_id' => $this->user->id,
            'total_tokens' => 1500,
            'created_at' => now()
        ]);

        $this->assertTrue($this->user->hasExceededTokenLimit());
    }

    public function test_user_unlimited_plan_never_exceeds_token_limit()
    {
        config(['stripe.plans.basic' => ['token_limit' => -1]]);

        OpenAIUsage::factory()->create([
            'user_id' => $this->user->id,
            'total_tokens' => 999999,
            'created_at' => now()
        ]);

        $this->assertFalse($this->user->hasExceededTokenLimit());
    }

    public function test_user_get_remaining_tokens()
    {
        config(['stripe.plans.basic' => ['token_limit' => 1000]]);

        OpenAIUsage::factory()->create([
            'user_id' => $this->user->id,
            'total_tokens' => 300,
            'created_at' => now()
        ]);

        $this->assertEquals(700, $this->user->getRemainingTokens());
    }

    public function test_user_monthly_request_count()
    {
        OpenAIUsage::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'created_at' => now()
        ]);

        OpenAIUsage::factory()->create([
            'user_id' => $this->user->id,
            'created_at' => now()->subMonth()
        ]);

        $this->assertEquals(3, $this->user->getMonthlyRequestCount());
    }

    public function test_user_monthly_cost_estimate()
    {
        OpenAIUsage::factory()->create([
            'user_id' => $this->user->id,
            'cost_estimate' => 25.50,
            'created_at' => now()
        ]);

        OpenAIUsage::factory()->create([
            'user_id' => $this->user->id,
            'cost_estimate' => 15.25,
            'created_at' => now()
        ]);

        $this->assertEquals(40.75, $this->user->getMonthlyCostEstimate());
    }

    public function test_user_has_exceeded_cost_limit()
    {
        OpenAIUsage::factory()->create([
            'user_id' => $this->user->id,
            'cost_estimate' => 150.00,
            'created_at' => now()
        ]);

        $this->assertTrue($this->user->hasExceededCostLimit());
    }

    public function test_user_get_excess_cost()
    {
        OpenAIUsage::factory()->create([
            'user_id' => $this->user->id,
            'cost_estimate' => 150.00,
            'created_at' => now()
        ]);

        $this->assertEquals(50.00, $this->user->getExcessCost());
    }

    public function test_user_get_remaining_cost_allowance()
    {
        OpenAIUsage::factory()->create([
            'user_id' => $this->user->id,
            'cost_estimate' => 75.00,
            'created_at' => now()
        ]);

        $this->assertEquals(25.00, $this->user->getRemainingCostAllowance());
    }

    public function test_user_get_cost_usage_percentage()
    {
        OpenAIUsage::factory()->create([
            'user_id' => $this->user->id,
            'cost_estimate' => 75.00,
            'created_at' => now()
        ]);

        $this->assertEquals(75.0, $this->user->getCostUsagePercentage());
    }

    public function test_user_get_full_address_attribute()
    {
        $user = User::factory()->create([
            'address' => '123 Main St',
            'city' => 'Anytown',
            'state' => 'CA',
            'zip_code' => '12345'
        ]);

        $this->assertEquals('123 Main St, Anytown, CA, 12345', $user->full_address);
    }

    public function test_user_relationships()
    {
        // Test setting relationship
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasOne::class, $this->user->setting());

        // Test patientAnalyses relationship
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $this->user->patientAnalyses());

        // Test doctor relationship
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasOne::class, $this->user->doctor());

        // Test appointments relationship
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $this->user->appointments());

        // Test reviews relationship
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $this->user->reviews());

        // Test subscriptions relationship
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $this->user->subscriptions());

        // Test activeSubscription relationship
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasOne::class, $this->user->activeSubscription());

        // Test openaiUsages relationship
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $this->user->openaiUsages());

        // Test stripeInvoices relationship
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $this->user->stripeInvoices());

        // Test monthlyInvoiceSetting relationship
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasOne::class, $this->user->monthlyInvoiceSetting());

        // Test doctorNotes relationship
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $this->user->doctorNotes());

        // Test patientNotes relationship
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $this->user->patientNotes());
    }

    public function test_user_is_restricted()
    {
        $setting = MonthlyInvoiceSetting::factory()->create([
            'user_id' => $this->user->id,
            'is_restricted' => true
        ]);

        $this->assertTrue($this->user->isRestricted());
    }

    public function test_user_get_or_create_monthly_invoice_setting()
    {
        $this->assertNull($this->user->monthlyInvoiceSetting);

        $setting = $this->user->getOrCreateMonthlyInvoiceSetting();

        $this->assertInstanceOf(MonthlyInvoiceSetting::class, $setting);
        $this->assertEquals($this->user->id, $setting->user_id);
    }
}
