<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class HealthMedicationSchedule extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'medication_name',
        'dosage',
        'frequency',
        'time_of_day',
        'start_date',
        'end_date',
        'active',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'active' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function logs()
    {
        return $this->hasMany(HealthMedicationLog::class, 'medication_schedule_id');
    }

    public function getTodayLog()
    {
        return $this->logs()
            ->where('scheduled_date', now()->toDateString())
            ->first();
    }

    public function isActiveOnDate($date): bool
    {
        if (!$this->active) {
            return false;
        }
        $date = \Carbon\Carbon::parse($date);
        if ($this->start_date && $this->start_date->isAfter($date)) {
            return false;
        }
        if ($this->end_date && $this->end_date->isBefore($date)) {
            return false;
        }
        return true;
    }

    public static function getActiveForUser(int $userId): \Illuminate\Database\Eloquent\Collection
    {
        $today = now()->toDateString();
        return self::where('user_id', $userId)
            ->where('active', true)
            ->where('start_date', '<=', $today)
            ->where(function ($q) use ($today) {
                $q->whereNull('end_date')->orWhere('end_date', '>=', $today);
            })
            ->get();
    }
}
