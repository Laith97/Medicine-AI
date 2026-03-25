<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class HealthMedicationLog extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'medication_schedule_id',
        'scheduled_date',
        'taken_at',
        'skipped',
        'skip_reason',
    ];

    protected $casts = [
        'scheduled_date' => 'date',
        'taken_at' => 'datetime',
        'skipped' => 'boolean',
    ];

    public function schedule()
    {
        return $this->belongsTo(HealthMedicationSchedule::class, 'medication_schedule_id');
    }

    public function save(array $options = [])
    {
        if ($this->skipped && !$this->skip_reason) {
            throw new \InvalidArgumentException('Skip reason is required when medication is skipped.');
        }
        if ($this->taken_at && $this->skipped) {
            throw new \InvalidArgumentException('Medication cannot be both taken and skipped.');
        }
        return parent::save($options);
    }

    public static function getAdherenceStreak(int $userId): int
    {
        $today = now()->toDateString();
        $scheduleIds = HealthMedicationSchedule::where('user_id', $userId)
            ->where('active', true)
            ->pluck('id');

        if ($scheduleIds->isEmpty()) {
            return 0;
        }

        // Build a lookup map: schedule_id => schedule with its date-range check
        $schedules = HealthMedicationSchedule::whereIn('id', $scheduleIds)->get();
        $scheduleMap = $schedules->keyBy('id');

        // Pre-fetch ALL logs for all schedules in a single query
        $allLogs = self::whereIn('medication_schedule_id', $scheduleIds)
            ->get()
            ->groupBy('medication_schedule_id');

        $streak = 0;
        $date = $today;

        while (true) {
            $allTaken = true;
            foreach ($scheduleIds as $scheduleId) {
                $schedule = $scheduleMap->get($scheduleId);
                if (!$schedule || !$schedule->isActiveOnDate($date)) {
                    continue;
                }

                $logsForSchedule = $allLogs->get($scheduleId, collect());
                $log = $logsForSchedule->firstWhere('scheduled_date', $date);

                if (!$log || (! $log->taken_at && ! $log->skipped)) {
                    $allTaken = false;
                    break 2; // break out of both loops
                }
            }

            if ($allTaken) {
                $streak++;
                $date = date('Y-m-d', strtotime($date . ' -1 day'));
            } else {
                break;
            }
        }

        return $streak;
    }
}
