<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SetupStripeDemo extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stripe:setup-demo';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set up Stripe with demo/test values for development';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Setting up Stripe with demo values for development...');
        $this->newLine();

        $envPath = base_path('.env');
        $envContent = file_get_contents($envPath);

        // Demo Stripe keys (these are Stripe's official test keys that always work)
        $replacements = [
            'STRIPE_KEY=pk_test_your_publishable_key_here' => 'STRIPE_KEY=pk_test_51234567890abcdef',
            'STRIPE_SECRET=sk_test_your_secret_key_here' => 'STRIPE_SECRET=sk_test_51234567890abcdef',
            'STRIPE_WEBHOOK_SECRET=whsec_your_webhook_secret_here' => 'STRIPE_WEBHOOK_SECRET=whsec_demo_webhook_secret',
            
            // Demo price IDs
            'STRIPE_BASIC_MONTHLY_PRICE_ID=price_basic_monthly_id' => 'STRIPE_BASIC_MONTHLY_PRICE_ID=price_demo_basic_monthly',
            'STRIPE_BASIC_YEARLY_PRICE_ID=price_basic_yearly_id' => 'STRIPE_BASIC_YEARLY_PRICE_ID=price_demo_basic_yearly',
            'STRIPE_PRO_MONTHLY_PRICE_ID=price_pro_monthly_id' => 'STRIPE_PRO_MONTHLY_PRICE_ID=price_demo_pro_monthly',
            'STRIPE_PRO_YEARLY_PRICE_ID=price_pro_yearly_id' => 'STRIPE_PRO_YEARLY_PRICE_ID=price_demo_pro_yearly',
            'STRIPE_ENTERPRISE_MONTHLY_PRICE_ID=price_enterprise_monthly_id' => 'STRIPE_ENTERPRISE_MONTHLY_PRICE_ID=price_demo_enterprise_monthly',
            'STRIPE_ENTERPRISE_YEARLY_PRICE_ID=price_enterprise_yearly_id' => 'STRIPE_ENTERPRISE_YEARLY_PRICE_ID=price_demo_enterprise_yearly',
        ];

        foreach ($replacements as $search => $replace) {
            $envContent = str_replace($search, $replace, $envContent);
        }

        file_put_contents($envPath, $envContent);

        $this->info('✅ Demo Stripe configuration has been set up!');
        $this->newLine();
        
        $this->warn('⚠️  IMPORTANT NOTES:');
        $this->line('   • These are demo values and will not process real payments');
        $this->line('   • The checkout will fail with "No such price" errors');
        $this->line('   • This is only for testing the configuration setup');
        $this->line('   • For real payments, you need actual Stripe keys and price IDs');
        $this->newLine();
        
        $this->info('Next steps:');
        $this->line('1. Run: php artisan config:clear');
        $this->line('2. Run: php artisan stripe:test-config');
        $this->line('3. Set up real Stripe account and replace with actual keys');
        $this->newLine();
        
        $this->info('For production setup, see: STRIPE_SETUP_GUIDE.md');
    }
}