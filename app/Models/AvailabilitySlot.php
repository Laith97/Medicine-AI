<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AvailabilitySlot extends Model
{
    use HasFactory;

    protected $fillable = [
        'doctor_id',
        'day_of_week',
        'start_time',
        'end_time',
        'slot_duration',
        'max_bookings_per_slot',
        'is_active',
        'effective_from',
        'effective_until',
    ];

    protected $casts = [
        'slot_duration' => 'integer',
        'max_bookings_per_slot' => 'integer',
        'is_active' => 'boolean',
        'effective_from' => 'date',
        'effective_until' => 'date',
    ];

    /**
     * Get the doctor that owns the availability slot
     */
    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    /**
     * Scope for active slots
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for specific day
     */
    public function scopeForDay($query, $day)
    {
        return $query->where('day_of_week', strtolower($day));
    }

    /**
     * Scope for effective date range
     */
    public function scopeEffectiveOn($query, $date)
    {
        return $query->where(function ($q) use ($date) {
            $q->whereNull('effective_from')
              ->orWhere('effective_from', '<=', $date);
        })->where(function ($q) use ($date) {
            $q->whereNull('effective_until')
              ->orWhere('effective_until', '>=', $date);
        });
    }

    /**
     * Get formatted time range
     */
    public function getTimeRangeAttribute()
    {
        return date('g:i A', strtotime($this->start_time)) . ' - ' . date('g:i A', strtotime($this->end_time));
    }

    /**
     * Get day name
     */
    public function getDayNameAttribute()
    {
        return ucfirst($this->day_of_week);
    }
}
