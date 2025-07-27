<?php

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Contracts\Console\Kernel;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\MonthlyInvoiceSetting;
use Carbon\Carbon;

echo "=== DEBUGGING SUBSCRIPTION DATE LOGIC ===\n\n";

// Create test user
$testUser = User::create([
    'name' => 'Debug User',
    'email' => 'debug@example.com',
    'password' => bcrypt('password'),
    'specialty' => 'General Medicine',
]);

// Create subscription setting
$setting = $testUser->monthlyInvoiceSetting()->create([
    'billing_amount' => 100.00,
    'subscription_period_months' => 1,
    'is_active' => true,
    'grace_period_days' => 7,
    'warning_period_days' => 3,
    'reminder_frequency_days' => 2,
    'restricted_pages' => ['ask-ai', 'dashboard', 'cases'],
    'is_restricted' => false,
]);

echo "Current time: " . now()->format('Y-m-d H:i:s') . "\n\n";

// Test expired subscription (3 days ago)
$expiredDate = now()->subDays(3);
echo "Setting subscription_ends_at to: " . $expiredDate->format('Y-m-d H:i:s') . " (3 days ago)\n";

$setting->update([
    'subscription_starts_at' => $expiredDate->copy()->subMonth(),
    'subscription_ends_at' => $expiredDate,
    'is_restricted' => false
]);

$setting->refresh();

echo "\nAfter update:\n";
echo "subscription_starts_at: " . ($setting->subscription_starts_at ? $setting->subscription_starts_at->format('Y-m-d H:i:s') : 'NULL') . "\n";
echo "subscription_ends_at: " . ($setting->subscription_ends_at ? $setting->subscription_ends_at->format('Y-m-d H:i:s') : 'NULL') . "\n";

echo "\nChecking expiration:\n";
echo "isSubscriptionExpired(): " . ($setting->isSubscriptionExpired() ? 'TRUE' : 'FALSE') . "\n";
echo "subscription_ends_at->isPast(): " . ($setting->subscription_ends_at->isPast() ? 'TRUE' : 'FALSE') . "\n";

echo "\nGrace period calculation:\n";
$gracePeriodEnd = $setting->getGracePeriodEndDate();
echo "Grace period ends: " . ($gracePeriodEnd ? $gracePeriodEnd->format('Y-m-d H:i:s') : 'NULL') . "\n";
echo "isInGracePeriod(): " . ($setting->isInGracePeriod() ? 'TRUE' : 'FALSE') . "\n";
echo "now()->isBefore(gracePeriodEnd): " . (now()->isBefore($gracePeriodEnd) ? 'TRUE' : 'FALSE') . "\n";

echo "\nWarning period calculation:\n";
$warningPeriodEnd = $setting->getWarningPeriodEndDate();
echo "Warning period ends: " . ($warningPeriodEnd ? $warningPeriodEnd->format('Y-m-d H:i:s') : 'NULL') . "\n";
echo "isInWarningPeriod(): " . ($setting->isInWarningPeriod() ? 'TRUE' : 'FALSE') . "\n";

echo "\nFinal status:\n";
echo "getSubscriptionStatus(): " . $setting->getSubscriptionStatus() . "\n";

// Clean up
$testUser->monthlyInvoiceSetting()->delete();
$testUser->delete();

echo "\n✅ Debug complete\n";

?>