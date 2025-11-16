<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class HepProgress extends Model
{
    use HasFactory;

    protected $fillable = [
        'hep_assignment_id',
        'hep_exercise_id',
        'date',
        'completed_sets',
        'completed_reps',
        'duration_completed',
        'pain_level',
        'difficulty_rating',
        'notes',
    ];

    protected $casts = [
        'date' => 'date',
        'completed_sets' => 'integer',
        'completed_reps' => 'integer',
        'duration_completed' => 'integer',
        'pain_level' => 'integer',
        'difficulty_rating' => 'integer',
    ];

    /**
     * Get the HEP assignment this progress belongs to
     */
    public function hepAssignment()
    {
        return $this->belongsTo(HepAssignment::class);
    }

    /**
     * Get the HEP exercise this progress is for
     */
    public function hepExercise()
    {
        return $this->belongsTo(HepExercise::class);
    }

    /**
     * Scope for progress by date
     */
    public function scopeByDate($query, $date)
    {
        return $query->where('date', $date);
    }

    /**
     * Scope for progress by date range
     */
    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    /**
     * Scope for progress by assignment
     */
    public function scopeByAssignment($query, $assignmentId)
    {
        return $query->where('hep_assignment_id', $assignmentId);
    }

    /**
     * Scope for progress by exercise
     */
    public function scopeByExercise($query, $exerciseId)
    {
        return $query->where('hep_exercise_id', $exerciseId);
    }

    /**
     * Scope for progress with high pain levels
     */
    public function scopeHighPain($query, $threshold = 7)
    {
        return $query->where('pain_level', '>=', $threshold);
    }

    /**
     * Scope for progress with high difficulty ratings
     */
    public function scopeHighDifficulty($query, $threshold = 8)
    {
        return $query->where('difficulty_rating', '>=', $threshold);
    }

    /**
     * Check if exercise was completed fully
     */
    public function isFullyCompleted()
    {
        $exercise = $this->hepExercise;

        if ($exercise->sets && $exercise->reps) {
            return $this->completed_sets >= $exercise->sets &&
                   $this->completed_reps >= ($exercise->sets * $exercise->reps);
        }

        if ($exercise->duration_seconds) {
            return $this->duration_completed >= $exercise->duration_seconds;
        }

        return $this->completed_sets > 0 || $this->completed_reps > 0 || $this->duration_completed > 0;
    }

    /**
     * Get completion percentage for this session
     */
    public function getCompletionPercentage()
    {
        $exercise = $this->hepExercise;

        if ($exercise->sets && $exercise->reps) {
            $expectedReps = $exercise->sets * $exercise->reps;
            $actualReps = $this->completed_sets * $this->completed_reps;
            return min(100, round(($actualReps / $expectedReps) * 100));
        }

        if ($exercise->duration_seconds) {
            return min(100, round(($this->duration_completed / $exercise->duration_seconds) * 100));
        }

        return $this->isFullyCompleted() ? 100 : 0;
    }

    /**
     * Get pain level description
     */
    public function getPainLevelDescription()
    {
        $levels = [
            0 => 'No pain',
            1 => 'Very mild',
            2 => 'Mild',
            3 => 'Moderate',
            4 => 'Somewhat severe',
            5 => 'Severe',
            6 => 'Very severe',
            7 => 'Very severe',
            8 => 'Very severe',
            9 => 'Extremely severe',
            10 => 'Worst possible pain'
        ];

        return $levels[$this->pain_level] ?? 'Unknown';
    }

    /**
     * Get difficulty rating description
     */
    public function getDifficultyDescription()
    {
        $levels = [
            1 => 'Very easy',
            2 => 'Easy',
            3 => 'Somewhat easy',
            4 => 'Moderate',
            5 => 'Somewhat difficult',
            6 => 'Difficult',
            7 => 'Very difficult',
            8 => 'Extremely difficult',
            9 => 'Almost impossible',
            10 => 'Impossible'
        ];

        return $levels[$this->difficulty_rating] ?? 'Unknown';
    }

    /**
     * Check if patient reported pain
     */
    public function hasPain()
    {
        return $this->pain_level > 0;
    }

    /**
     * Check if patient found exercise difficult
     */
    public function isDifficult()
    {
        return $this->difficulty_rating >= 7;
    }

    /**
     * Get formatted duration completed
     */
    public function getFormattedDurationCompleted()
    {
        if (!$this->duration_completed) {
            return '0 seconds';
        }

        if ($this->duration_completed < 60) {
            return $this->duration_completed . ' seconds';
        }

        $minutes = floor($this->duration_completed / 60);
        $seconds = $this->duration_completed % 60;

        if ($seconds === 0) {
            return $minutes . ' minute' . ($minutes > 1 ? 's' : '');
        }

        return $minutes . 'm ' . $seconds . 's';
    }
}
