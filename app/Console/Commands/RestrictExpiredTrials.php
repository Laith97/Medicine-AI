<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\MonthlyInvoiceSetting;
use Illuminate\Console\Command;

class RestrictExpiredTrials extends Command
{
    protected $signature = 'trials:restrict-expired';
    protected $description = 'Restrict accounts whose trial expired and have not started a subscription';

    public function handle(): int
    {
        $this->info('Restricting users with expired trials and no subscription...');

        $users = User::where('trial_used', true)
            ->whereNotNull('trial_ends_at')
            ->where('trial_ends_at', '<=', now())
            ->with('monthlyInvoiceSetting')
            ->get();

        $restricted = 0;

        foreach ($users as $user) {
            $setting = $user->monthlyInvoiceSetting ?? $user->getOrCreateMonthlyInvoiceSetting();

            // Only restrict if the user has not started a subscription yet
            $status = $setting->getSubscriptionStatus();
            if (in_array($status, ['setup_pending', 'ready_to_subscribe'])) {
                $setting->update([
                    'is_active' => true, // ensure setting is active for status checks
                ]);

                // Restrict access to default pages
                $setting->restrictAccess(MonthlyInvoiceSetting::getDefaultRestrictedPages());
                $restricted++;

                $this->line("- Restricted user #{$user->id} ({$user->email}) - status was '{$status}'");
            }
        }

        $this->info("Done. Restricted {$restricted} user(s).");
        return 0;
    }
}