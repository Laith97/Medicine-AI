<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\AdminController;
use Illuminate\Http\Request;
use App\Models\User;

class TestManualReminderCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:manual-reminder {user_id} {type=grace_period} {--email=laythfares99@gmail.com}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test manual reminder system';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $userId = $this->argument('user_id');
        $type = $this->argument('type');
        $email = $this->option('email');
        
        $this->info("🧪 Testing Manual Reminder System");
        $this->info("User ID: $userId");
        $this->info("Type: $type");
        $this->info("Test Email: $email");
        $this->newLine();

        // Check if user exists
        $user = User::find($userId);
        if (!$user) {
            $this->error("❌ User with ID $userId not found");
            return 1;
        }

        $this->info("✅ User found: {$user->name} ({$user->email})");

        // Test the reminder system directly
        try {
            $adminController = new AdminController();
            
            // Create mock request
            $request = new Request();
            $request->merge([
                'reminder_type' => $type,
                'user_ids' => [$userId],
                'force_send' => '1'
            ]);

            $this->info("📧 Sending manual reminder...");
            
            // Call the method directly
            switch ($type) {
                case 'grace_period':
                    $result = $this->callMethod($adminController, 'sendGracePeriodReminders', [[$userId], true]);
                    break;
                case 'warning_period':
                    $result = $this->callMethod($adminController, 'sendWarningPeriodReminders', [[$userId], true]);
                    break;
                case 'overdue':
                    $result = $this->callMethod($adminController, 'sendOverdueReminders', [[$userId], true]);
                    break;
                default:
                    $this->error("❌ Invalid reminder type: $type");
                    return 1;
            }

            $this->newLine();
            $this->info("📊 Results:");
            $this->table(['Metric', 'Value'], [
                ['Grace Reminders Sent', $result['grace_reminders_sent'] ?? 0],
                ['Warning Reminders Sent', $result['warning_reminders_sent'] ?? 0],
                ['Overdue Reminders Sent', $result['overdue_reminders_sent'] ?? 0],
                ['Errors', count($result['errors'] ?? [])],
            ]);

            if (!empty($result['errors'])) {
                $this->newLine();
                $this->warn("⚠️  Errors encountered:");
                foreach ($result['errors'] as $error) {
                    $this->line("  • $error");
                }
            }

            $totalSent = ($result['grace_reminders_sent'] ?? 0) + 
                        ($result['warning_reminders_sent'] ?? 0) + 
                        ($result['overdue_reminders_sent'] ?? 0);

            if ($totalSent > 0) {
                $this->newLine();
                $this->info("✅ Manual reminder test completed successfully!");
                $this->info("💡 Check your email inbox: $email");
                return 0;
            } else {
                $this->newLine();
                $this->warn("⚠️  No reminders were sent. This might be expected if:");
                $this->line("  • User doesn't meet the criteria for this reminder type");
                $this->line("  • Force send is disabled and user doesn't need reminder");
                $this->line("  • There are configuration issues");
                return 1;
            }

        } catch (\Exception $e) {
            $this->error("❌ Test failed: " . $e->getMessage());
            $this->line("Stack trace: " . $e->getTraceAsString());
            return 1;
        }
    }

    /**
     * Call a private method on an object
     */
    private function callMethod($object, $methodName, $parameters = [])
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);
        return $method->invokeArgs($object, $parameters);
    }
}