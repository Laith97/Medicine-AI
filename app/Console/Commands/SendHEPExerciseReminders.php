<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\HepAssignment;
use App\Models\HepProgress;
use App\Models\HepExercise;
use App\Notifications\HEPExerciseReminder;
use Carbon\Carbon;

class SendHEPExerciseReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hep:send-reminders {--type=daily : Type of reminder (daily, weekly, missed)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send HEP exercise reminders to patients';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $type = $this->option('type');

        switch ($type) {
            case 'daily':
                $this->sendDailyReminders();
                break;
            case 'weekly':
                $this->sendWeeklyReminders();
                break;
            case 'missed':
                $this->sendMissedExerciseReminders();
                break;
            default:
                $this->error('Invalid reminder type. Use: daily, weekly, or missed');
                return 1;
        }

        return 0;
    }

    /**
     * Send daily exercise reminders
     */
    protected function sendDailyReminders()
    {
        $this->info('Sending daily HEP exercise reminders...');

        $assignments = HepAssignment::where('completion_status', '!=', 'completed')
            ->where('assigned_at', '<=', now())
            ->with(['hepProgram.hepExercises.exercise', 'patient'])
            ->get();

        $remindersSent = 0;

        foreach ($assignments as $assignment) {
            // Check if patient has notification preferences enabled
            if (!$this->shouldSendNotification($assignment->patient, 'daily_reminders')) {
                continue;
            }

            $currentWeek = min(
                now()->diffInWeeks($assignment->assigned_at) + 1,
                $assignment->hepProgram->duration_weeks
            );

            // Get exercises for current week
            $weekExercises = $assignment->hepProgram->hepExercises
                ->where('week_number', $currentWeek);

            // Check if any exercises haven't been completed today
            $incompleteExercises = [];
            $today = Carbon::today();

            foreach ($weekExercises as $exercise) {
                $completedToday = HepProgress::where('hep_assignment_id', $assignment->id)
                    ->where('hep_exercise_id', $exercise->id)
                    ->where('date', $today)
                    ->exists();

                if (!$completedToday) {
                    $incompleteExercises[] = $exercise;
                }
            }

            // Send reminder if there are incomplete exercises
            if (!empty($incompleteExercises)) {
                try {
                    $assignment->patient->notify(
                        new HEPExerciseReminder($assignment, $incompleteExercises, 'daily')
                    );
                    $remindersSent++;
                } catch (\Exception $e) {
                    $this->error("Failed to send reminder to patient {$assignment->patient->id}: {$e->getMessage()}");
                }
            }
        }

        $this->info("Sent {$remindersSent} daily reminders");
    }

    /**
     * Send weekly progress reminders
     */
    protected function sendWeeklyReminders()
    {
        $this->info('Sending weekly HEP progress reminders...');

        // Send on Sundays (end of week)
        if (now()->dayOfWeek !== Carbon::SUNDAY) {
            $this->info('Weekly reminders only sent on Sundays');
            return;
        }

        $assignments = HepAssignment::where('completion_status', '!=', 'completed')
            ->where('assigned_at', '<=', now())
            ->with(['hepProgram', 'patient'])
            ->get();

        $remindersSent = 0;

        foreach ($assignments as $assignment) {
            // Check if patient has notification preferences enabled
            if (!$this->shouldSendNotification($assignment->patient, 'weekly_reports')) {
                continue;
            }

            try {
                $assignment->patient->notify(
                    new HEPExerciseReminder($assignment, [], 'weekly')
                );
                $remindersSent++;
            } catch (\Exception $e) {
                $this->error("Failed to send weekly reminder to patient {$assignment->patient->id}: {$e->getMessage()}");
            }
        }

        $this->info("Sent {$remindersSent} weekly reminders");
    }

    /**
     * Send reminders for missed exercises (2+ days without completion)
     */
    protected function sendMissedExerciseReminders()
    {
        $this->info('Sending missed exercise reminders...');

        $assignments = HepAssignment::where('completion_status', '!=', 'completed')
            ->where('assigned_at', '<=', now()->subDays(2))
            ->with(['hepProgram.hepExercises.exercise', 'patient'])
            ->get();

        $remindersSent = 0;

        foreach ($assignments as $assignment) {
            // Check if patient has notification preferences enabled
            if (!$this->shouldSendNotification($assignment->patient, 'missed_exercise_alerts')) {
                continue;
            }

            $currentWeek = min(
                now()->diffInWeeks($assignment->assigned_at) + 1,
                $assignment->hepProgram->duration_weeks
            );

            // Get exercises for current week
            $weekExercises = $assignment->hepProgram->hepExercises
                ->where('week_number', $currentWeek);

            // Check for exercises not completed in the last 2 days
            $missedExercises = [];
            $twoDaysAgo = Carbon::today()->subDays(2);

            foreach ($weekExercises as $exercise) {
                $lastCompletion = HepProgress::where('hep_assignment_id', $assignment->id)
                    ->where('hep_exercise_id', $exercise->id)
                    ->where('date', '>=', $twoDaysAgo)
                    ->exists();

                if (!$lastCompletion) {
                    $missedExercises[] = $exercise;
                }
            }

            // Send reminder if there are missed exercises
            if (!empty($missedExercises)) {
                try {
                    $assignment->patient->notify(
                        new HEPExerciseReminder($assignment, $missedExercises, 'missed')
                    );
                    $remindersSent++;
                } catch (\Exception $e) {
                    $this->error("Failed to send missed exercise reminder to patient {$assignment->patient->id}: {$e->getMessage()}");
                }
            }
        }

        $this->info("Sent {$remindersSent} missed exercise reminders");
    }

    /**
     * Check if notification should be sent based on patient preferences
     */
    protected function shouldSendNotification($patient, string $preferenceType): bool
    {
        // Check if patient has disabled this type of notification
        $preference = $patient->notificationPreferences()
            ->where('type', $preferenceType)
            ->first();

        // Default to true if no preference set
        return $preference ? $preference->enabled : true;
    }
}
