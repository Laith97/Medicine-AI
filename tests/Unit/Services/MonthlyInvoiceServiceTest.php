<?php

namespace Tests\Unit\Services;

use App\Services\MonthlyInvoiceService;
use App\Models\User;
use App\Models\MonthlyInvoiceSetting;
use App\Models\StripeInvoice;
use App\Models\OpenAIUsage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Mockery;

class MonthlyInvoiceServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $monthlyInvoiceService;
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->monthlyInvoiceService = new MonthlyInvoiceService();

        $this->user = User::factory()->create([
            'monthly_cost_limit' => 50.00,
            'current_plan' => 'basic'
        ]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_monthly_invoice_service_can_be_instantiated()
    {
        $this->assertInstanceOf(MonthlyInvoiceService::class, $this->monthlyInvoiceService);
    }

    public function test_calculate_monthly_excess_cost()
    {
        // Create usage that exceeds the limit
        OpenAIUsage::factory()->create([
            'user_id' => $this->user->id,
            'cost_estimate' => 75.00,
            'created_at' => now()
        ]);

        $excessCost = $this->monthlyInvoiceService->calculateMonthlyExcessCost($this->user);

        $this->assertEquals(25.00, $excessCost);
    }

    public function test_calculate_monthly_excess_cost_no_excess()
    {
        // Create usage within the limit
        OpenAIUsage::factory()->create([
            'user_id' => $this->user->id,
            'cost_estimate' => 30.00,
            'created_at' => now()
        ]);

        $excessCost = $this->monthlyInvoiceService->calculateMonthlyExcessCost($this->user);

        $this->assertEquals(0.00, $excessCost);
    }

    public function test_calculate_monthly_excess_cost_no_limit()
    {
        $this->user->monthly_cost_limit = 0; // No limit
        $this->user->save();

        OpenAIUsage::factory()->create([
            'user_id' => $this->user->id,
            'cost_estimate' => 100.00,
            'created_at' => now()
        ]);

        $excessCost = $this->monthlyInvoiceService->calculateMonthlyExcessCost($this->user);

        $this->assertEquals(0.00, $excessCost);
    }

    public function test_should_create_monthly_invoice()
    {
        // User with excess cost should get invoice
        OpenAIUsage::factory()->create([
            'user_id' => $this->user->id,
            'cost_estimate' => 75.00,
            'created_at' => now()
        ]);

        $shouldCreate = $this->monthlyInvoiceService->shouldCreateMonthlyInvoice($this->user);

        $this->assertTrue($shouldCreate);
    }

    public function test_should_not_create_monthly_invoice_no_excess()
    {
        // User without excess cost should not get invoice
        OpenAIUsage::factory()->create([
            'user_id' => $this->user->id,
            'cost_estimate' => 30.00,
            'created_at' => now()
        ]);

        $shouldCreate = $this->monthlyInvoiceService->shouldCreateMonthlyInvoice($this->user);

        $this->assertFalse($shouldCreate);
    }

    public function test_should_not_create_monthly_invoice_already_exists()
    {
        // Create excess usage
        OpenAIUsage::factory()->create([
            'user_id' => $this->user->id,
            'cost_estimate' => 75.00,
            'created_at' => now()
        ]);

        // Create existing invoice for this month
        StripeInvoice::factory()->create([
            'user_id' => $this->user->id,
            'invoice_type' => 'monthly',
            'invoice_month' => now()->month,
            'invoice_year' => now()->year
        ]);

        $shouldCreate = $this->monthlyInvoiceService->shouldCreateMonthlyInvoice($this->user);

        $this->assertFalse($shouldCreate);
    }

    public function test_create_monthly_invoice()
    {
        // Create excess usage
        OpenAIUsage::factory()->create([
            'user_id' => $this->user->id,
            'cost_estimate' => 75.00,
            'created_at' => now()
        ]);

        $invoice = $this->monthlyInvoiceService->createMonthlyInvoice($this->user);

        $this->assertInstanceOf(StripeInvoice::class, $invoice);
        $this->assertEquals($this->user->id, $invoice->user_id);
        $this->assertEquals('monthly', $invoice->invoice_type);
        $this->assertEquals(2500, $invoice->amount_due); // $25.00 in cents
        $this->assertEquals(now()->month, $invoice->invoice_month);
        $this->assertEquals(now()->year, $invoice->invoice_year);
    }

    public function test_get_monthly_invoice_setting()
    {
        $setting = MonthlyInvoiceSetting::factory()->create([
            'user_id' => $this->user->id,
            'billing_amount' => 25.00,
            'grace_period_days' => 7
        ]);

        $retrievedSetting = $this->monthlyInvoiceService->getMonthlyInvoiceSetting($this->user);

        $this->assertEquals($setting->id, $retrievedSetting->id);
        $this->assertEquals(25.00, $retrievedSetting->billing_amount);
    }

    public function test_create_monthly_invoice_setting()
    {
        $settingData = [
            'billing_amount' => 30.00,
            'grace_period_days' => 10,
            'reminder_frequency_days' => 3
        ];

        $setting = $this->monthlyInvoiceService->createMonthlyInvoiceSetting($this->user, $settingData);

        $this->assertInstanceOf(MonthlyInvoiceSetting::class, $setting);
        $this->assertEquals($this->user->id, $setting->user_id);
        $this->assertEquals(30.00, $setting->billing_amount);
        $this->assertEquals(10, $setting->grace_period_days);
        $this->assertEquals(3, $setting->reminder_frequency_days);
    }

    public function test_update_monthly_invoice_setting()
    {
        $setting = MonthlyInvoiceSetting::factory()->create([
            'user_id' => $this->user->id,
            'billing_amount' => 25.00
        ]);

        $updateData = [
            'billing_amount' => 35.00,
            'grace_period_days' => 14
        ];

        $updatedSetting = $this->monthlyInvoiceService->updateMonthlyInvoiceSetting($setting, $updateData);

        $this->assertEquals(35.00, $updatedSetting->billing_amount);
        $this->assertEquals(14, $updatedSetting->grace_period_days);
    }

    public function test_process_overdue_invoices()
    {
        // Create overdue invoice
        $overdueInvoice = StripeInvoice::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'open',
            'due_date' => now()->subDays(5),
            'invoice_type' => 'monthly'
        ]);

        $setting = MonthlyInvoiceSetting::factory()->create([
            'user_id' => $this->user->id,
            'grace_period_days' => 3
        ]);

        $processedCount = $this->monthlyInvoiceService->processOverdueInvoices();

        $this->assertGreaterThan(0, $processedCount);

        // Check that user is restricted
        $setting->refresh();
        $this->assertTrue($setting->is_restricted);
    }

    public function test_send_invoice_reminders()
    {
        // Create invoice due soon
        $invoice = StripeInvoice::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'open',
            'due_date' => now()->addDays(2),
            'invoice_type' => 'monthly'
        ]);

        $setting = MonthlyInvoiceSetting::factory()->create([
            'user_id' => $this->user->id,
            'reminder_frequency_days' => 3,
            'last_reminder_sent_at' => now()->subDays(4)
        ]);

        $reminderCount = $this->monthlyInvoiceService->sendInvoiceReminders();

        $this->assertGreaterThan(0, $reminderCount);

        // Check that reminder timestamp was updated
        $setting->refresh();
        $this->assertTrue($setting->last_reminder_sent_at->isToday());
    }

    public function test_calculate_grace_period_end()
    {
        $invoice = StripeInvoice::factory()->create([
            'due_date' => now()->subDays(2)
        ]);

        $setting = MonthlyInvoiceSetting::factory()->create([
            'grace_period_days' => 7
        ]);

        $gracePeriodEnd = $this->monthlyInvoiceService->calculateGracePeriodEnd($invoice, $setting);

        $expectedEnd = $invoice->due_date->addDays(7);
        $this->assertEquals($expectedEnd->toDateString(), $gracePeriodEnd->toDateString());
    }

    public function test_is_in_grace_period()
    {
        $invoice = StripeInvoice::factory()->create([
            'due_date' => now()->subDays(2)
        ]);

        $setting = MonthlyInvoiceSetting::factory()->create([
            'grace_period_days' => 7
        ]);

        $isInGracePeriod = $this->monthlyInvoiceService->isInGracePeriod($invoice, $setting);

        $this->assertTrue($isInGracePeriod);
    }

    public function test_is_not_in_grace_period()
    {
        $invoice = StripeInvoice::factory()->create([
            'due_date' => now()->subDays(10)
        ]);

        $setting = MonthlyInvoiceSetting::factory()->create([
            'grace_period_days' => 7
        ]);

        $isInGracePeriod = $this->monthlyInvoiceService->isInGracePeriod($invoice, $setting);

        $this->assertFalse($isInGracePeriod);
    }

    public function test_restrict_user_access()
    {
        $setting = MonthlyInvoiceSetting::factory()->create([
            'user_id' => $this->user->id,
            'is_restricted' => false
        ]);

        $this->monthlyInvoiceService->restrictUserAccess($this->user, 'Overdue payment');

        $setting->refresh();
        $this->assertTrue($setting->is_restricted);
        $this->assertEquals('Overdue payment', $setting->restriction_reason);
        $this->assertNotNull($setting->restricted_at);
    }

    public function test_restore_user_access()
    {
        $setting = MonthlyInvoiceSetting::factory()->create([
            'user_id' => $this->user->id,
            'is_restricted' => true,
            'restriction_reason' => 'Overdue payment',
            'restricted_at' => now()->subDays(5)
        ]);

        $this->monthlyInvoiceService->restoreUserAccess($this->user);

        $setting->refresh();
        $this->assertFalse($setting->is_restricted);
        $this->assertNull($setting->restriction_reason);
        $this->assertNull($setting->restricted_at);
    }

    public function test_get_monthly_statistics()
    {
        // Create some invoices and usage
        StripeInvoice::factory()->count(3)->create([
            'invoice_type' => 'monthly',
            'status' => 'paid',
            'amount_due' => 2500,
            'invoice_month' => now()->month,
            'invoice_year' => now()->year
        ]);

        StripeInvoice::factory()->count(2)->create([
            'invoice_type' => 'monthly',
            'status' => 'open',
            'amount_due' => 1500,
            'invoice_month' => now()->month,
            'invoice_year' => now()->year
        ]);

        $stats = $this->monthlyInvoiceService->getMonthlyStatistics();

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total_invoices', $stats);
        $this->assertArrayHasKey('paid_invoices', $stats);
        $this->assertArrayHasKey('unpaid_invoices', $stats);
        $this->assertArrayHasKey('total_revenue', $stats);
        $this->assertArrayHasKey('outstanding_amount', $stats);

        $this->assertEquals(5, $stats['total_invoices']);
        $this->assertEquals(3, $stats['paid_invoices']);
        $this->assertEquals(2, $stats['unpaid_invoices']);
    }

    public function test_get_user_invoice_history()
    {
        // Create invoice history for user
        StripeInvoice::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'invoice_type' => 'monthly'
        ]);

        // Create invoices for other users (should not be included)
        StripeInvoice::factory()->count(2)->create([
            'invoice_type' => 'monthly'
        ]);

        $history = $this->monthlyInvoiceService->getUserInvoiceHistory($this->user);

        $this->assertCount(3, $history);
        foreach ($history as $invoice) {
            $this->assertEquals($this->user->id, $invoice->user_id);
        }
    }

    public function test_calculate_next_billing_date()
    {
        $setting = MonthlyInvoiceSetting::factory()->create([
            'billing_cycle_day' => 15
        ]);

        $nextBillingDate = $this->monthlyInvoiceService->calculateNextBillingDate($setting);

        if (now()->day <= 15) {
            $expectedDate = now()->day(15);
        } else {
            $expectedDate = now()->addMonth()->day(15);
        }

        $this->assertEquals($expectedDate->toDateString(), $nextBillingDate->toDateString());
    }

    public function test_generate_invoice_pdf()
    {
        $invoice = StripeInvoice::factory()->create([
            'user_id' => $this->user->id,
            'amount_due' => 2500,
            'invoice_type' => 'monthly'
        ]);

        $pdf = $this->monthlyInvoiceService->generateInvoicePDF($invoice);

        $this->assertNotNull($pdf);
        $this->assertStringContains('PDF', get_class($pdf));
    }

    public function test_send_invoice_notification()
    {
        $invoice = StripeInvoice::factory()->create([
            'user_id' => $this->user->id,
            'invoice_type' => 'monthly'
        ]);

        $result = $this->monthlyInvoiceService->sendInvoiceNotification($invoice);

        $this->assertTrue($result);
    }

    public function test_process_monthly_billing_cycle()
    {
        // Create users with excess usage
        $users = User::factory()->count(3)->create([
            'monthly_cost_limit' => 50.00
        ]);

        foreach ($users as $user) {
            OpenAIUsage::factory()->create([
                'user_id' => $user->id,
                'cost_estimate' => 75.00,
                'created_at' => now()
            ]);
        }

        $processedCount = $this->monthlyInvoiceService->processMonthliBillingCycle();

        $this->assertEquals(3, $processedCount);

        // Check that invoices were created
        $this->assertEquals(3, StripeInvoice::where('invoice_type', 'monthly')->count());
    }
}
