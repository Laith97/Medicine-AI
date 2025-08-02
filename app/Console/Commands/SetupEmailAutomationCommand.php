<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SetupEmailAutomationCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'setup:email-automation {--show-only}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Setup automated email system with cron jobs';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $showOnly = $this->option('show-only');
        
        $this->info("🚀 Email Automation Setup");
        $this->newLine();

        $projectPath = base_path();
        
        // Cron job commands
        $cronJobs = [
            [
                'schedule' => '0 */6 * * *',
                'command' => "cd $projectPath && php artisan subscriptions:process-lifecycle >> /dev/null 2>&1",
                'description' => 'Process subscription lifecycle (every 6 hours)'
            ],
            [
                'schedule' => '0 2 * * *',
                'command' => "cd $projectPath && php artisan email:monitor >> /dev/null 2>&1",
                'description' => 'Monitor email system health (daily at 2 AM)'
            ]
        ];

        if ($showOnly) {
            $this->info("📋 Cron Jobs to Add:");
            $this->newLine();
            
            foreach ($cronJobs as $job) {
                $this->line("# {$job['description']}");
                $this->line("{$job['schedule']} {$job['command']}");
                $this->newLine();
            }
            
            $this->info("📝 To add these cron jobs manually:");
            $this->line("1. Run: crontab -e");
            $this->line("2. Add the above lines to your crontab");
            $this->line("3. Save and exit");
            
        } else {
            $this->info("⚙️  Setting up cron jobs...");
            
            // Get current crontab
            $currentCrontab = shell_exec('crontab -l 2>/dev/null') ?: '';
            $newCrontab = $currentCrontab;
            
            // Add header if crontab is empty
            if (empty(trim($currentCrontab))) {
                $newCrontab .= "# MedCura AI - Email Automation Cron Jobs\n";
            }
            
            $added = 0;
            foreach ($cronJobs as $job) {
                // Check if job already exists
                if (strpos($currentCrontab, $job['command']) === false) {
                    $newCrontab .= "\n# {$job['description']}\n";
                    $newCrontab .= "{$job['schedule']} {$job['command']}\n";
                    $added++;
                } else {
                    $this->warn("⚠️  Cron job already exists: {$job['description']}");
                }
            }
            
            if ($added > 0) {
                // Write new crontab
                $tempFile = tempnam(sys_get_temp_dir(), 'crontab');
                file_put_contents($tempFile, $newCrontab);
                
                $result = shell_exec("crontab $tempFile 2>&1");
                unlink($tempFile);
                
                if ($result === null || empty(trim($result))) {
                    $this->info("✅ Successfully added $added cron job(s)!");
                } else {
                    $this->error("❌ Failed to update crontab: $result");
                    return 1;
                }
            } else {
                $this->info("ℹ️  All cron jobs already exist.");
            }
        }
        
        $this->newLine();
        $this->info("🧪 Testing Commands:");
        $this->line("# Test the automation system");
        $this->line("php artisan test:email-automation --period=grace");
        $this->line("php artisan test:email-automation --period=warning");
        $this->line("php artisan test:email-automation --period=restrict");
        $this->newLine();
        $this->line("# Test with existing user");
        $this->line("php artisan test:email-automation --user-id=65 --period=grace --reset");
        $this->newLine();
        $this->line("# Manual processing");
        $this->line("php artisan subscriptions:process-lifecycle");
        $this->newLine();
        $this->line("# Monitor system");
        $this->line("php artisan email:monitor");
        
        $this->newLine();
        $this->info("📊 Current Cron Jobs:");
        $currentJobs = shell_exec('crontab -l 2>/dev/null') ?: 'No cron jobs found';
        $this->line($currentJobs);
        
        return 0;
    }
}