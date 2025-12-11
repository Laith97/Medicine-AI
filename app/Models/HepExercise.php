<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class HepExercise extends Model
{
    use HasFactory;

    protected $fillable = [
        'hep_program_id',
        'exercise_id',
        'sets',
        'reps',
        'duration_seconds',
        'rest_seconds',
        'frequency',
        'progression_notes',
        'week_number',
        'order',
    ];

    protected $casts = [
        'sets' => 'integer',
        'reps' => 'integer',
        'duration_seconds' => 'integer',
        'rest_seconds' => 'integer',
        'week_number' => 'integer',
        'order' => 'integer',
    ];

    /**
     * Get the HEP program this exercise belongs to
     */
    public function hepProgram()
    {
        return $this->belongsTo(HepProgram::class);
    }

    /**
     * Get the exercise details
     */
    public function exercise()
    {
        return $this->belongsTo(Exercise::class);
    }

    /**
     * Get the progress records for this HEP exercise
     */
    public function hepProgress()
    {
        return $this->hasMany(HepProgress::class);
    }

    /**
     * Scope for exercises in a specific week
     */
    public function scopeByWeek($query, $weekNumber)
    {
        return $query->where('week_number', $weekNumber);
    }

    /**
     * Scope for exercises by program
     */
    public function scopeByProgram($query, $programId)
    {
        return $query->where('hep_program_id', $programId);
    }

    /**
     * Get total duration for this exercise (sets * duration per set + rest time)
     */
    public function getTotalDurationSeconds()
    {
        $exerciseDuration = $this->duration_seconds ?? 0;
        $restDuration = $this->rest_seconds ?? 0;

        if ($this->sets && $this->sets > 0) {
            return ($this->sets * $exerciseDuration) + (($this->sets - 1) * $restDuration);
        }

        return $exerciseDuration;
    }

    /**
     * Get formatted duration
     */
    public function getFormattedDuration()
    {
        $totalSeconds = $this->getTotalDurationSeconds();

        if ($totalSeconds < 60) {
            return $totalSeconds . ' seconds';
        }

        $minutes = floor($totalSeconds / 60);
        $seconds = $totalSeconds % 60;

        if ($seconds === 0) {
            return $minutes . ' minute' . ($minutes > 1 ? 's' : '');
        }

        return $minutes . 'm ' . $seconds . 's';
    }

    /**
     * Get exercise description with sets/reps info
     */
    public function getExerciseDescription()
    {
        $description = $this->exercise->name;

        if ($this->sets && $this->reps) {
            $description .= " - {$this->sets} sets of {$this->reps} reps";
        } elseif ($this->sets && $this->duration_seconds) {
            $description .= " - {$this->sets} sets of {$this->duration_seconds} seconds";
        } elseif ($this->duration_seconds) {
            $description .= " - {$this->duration_seconds} seconds";
        }

        if ($this->frequency) {
            $description .= " ({$this->frequency})";
        }

        return $description;
    }

    /**
     * Check if exercise has progression notes
     */
    public function hasProgressionNotes()
    {
        return !empty($this->progression_notes);
    }
}
