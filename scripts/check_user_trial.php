<?php
// php /home/laith/Documents/Medicine/scripts/check_user_trial.php fofo@medical.com

use Illuminate\Contracts\Http\Kernel;

$basePath = dirname(__DIR__);
require $basePath . '/vendor/autoload.php';
$app = require_once $basePath . '/bootstrap/app.php';

$app->make(Kernel::class);

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$email = $argv[1] ?? null;
if (!$email) {
    fwrite(STDERR, "Usage: php scripts/check_user_trial.php user@example.com\n");
    exit(1);
}

/** @var \App\Models\User|null $user */
$user = \App\Models\User::where('email', $email)->first();

if (!$user) {
    echo json_encode(['found' => false, 'email' => $email], JSON_PRETTY_PRINT) . "\n";
    exit(0);
}

$setting = $user->monthlyInvoiceSetting;

$result = [
    'found' => true,
    'user' => [
        'id' => $user->id,
        'email' => $user->email,
        'role' => $user->role,
    ],
    'trial' => [
        'trial_used' => (bool) $user->trial_used,
        'trial_ends_at' => optional($user->trial_ends_at)?->toDateTimeString(),
        'is_in_trial' => $user->isInTrialPeriod(),
        'days_remaining' => method_exists($user, 'getTrialDaysRemaining') ? $user->getTrialDaysRemaining() : null,
    ],
    'subscription_setting' => $setting ? [
        'exists' => true,
        'is_active' => (bool) $setting->is_active,
        'is_restricted' => (bool) $setting->is_restricted,
        'subscription_starts_at' => optional($setting->subscription_starts_at)?->toDateTimeString(),
        'subscription_ends_at' => optional($setting->subscription_ends_at)?->toDateTimeString(),
        'grace_period_days' => $setting->grace_period_days,
        'warning_period_days' => $setting->warning_period_days,
        'days_remaining' => $setting->getDaysRemaining(),
        'grace_end' => optional($setting->getGracePeriodEndDate())?->toDateTimeString(),
        'status' => $setting->getSubscriptionStatus(),
        'next_billing_date' => optional($setting->next_billing_date)?->toDateTimeString(),
    ] : ['exists' => false],
];

echo json_encode($result, JSON_PRETTY_PRINT) . "\n";