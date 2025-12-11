<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

class HepAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'hep_program_id',
        'patient_id',
        'assigned_by',
        'assigned_at',
        'due_date',
        'completion_status',
        'patient_notes',
        'clinician_feedback',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'due_date' => 'date',
    ];

    /**
     * Get the HEP program for this assignment
     */
    public function hepProgram()
    {
        return $this->belongsTo(HepProgram::class);
    }

    /**
     * Get the patient for this assignment
     */
    public function patient()
    {
        return $this->belongsTo(User::class, 'patient_id');
    }

    /**
     * Get the clinician who assigned this program
     */
    public function assignedBy()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    /**
     * Get the progress records for this assignment
     */
    public function hepProgress()
    {
        return $this->hasMany(HepProgress::class);
    }

    /**
     * Scope for pending assignments
     */
    public function scopePending($query)
    {
        return $query->where('completion_status', 'pending');
    }

    /**
     * Scope for in-progress assignments
     */
    public function scopeInProgress($query)
    {
        return $query->where('completion_status', 'in_progress');
    }

    /**
     * Scope for completed assignments
     */
    public function scopeCompleted($query)
    {
        return $query->where('completion_status', 'completed');
    }

    /**
     * Scope for overdue assignments
     */
    public function scopeOverdue($query)
    {
        return $query->where('completion_status', '!=', 'completed')
                    ->where('due_date', '<', now()->toDateString());
    }

    /**
     * Scope for assignments by patient
     */
    public function scopeByPatient($query, $patientId)
    {
        return $query->where('patient_id', $patientId);
    }

    /**
     * Scope for assignments by clinician
     */
    public function scopeByClinician($query, $clinicianId)
    {
        return $query->where('assigned_by', $clinicianId);
    }

    /**
     * Scope for assignments due today
     */
    public function scopeDueToday($query)
    {
        return $query->where('due_date', now()->toDateString());
    }

    /**
     * Scope for assignments due this week
     */
    public function scopeDueThisWeek($query)
    {
        return $query->whereBetween('due_date', [
            now()->startOfWeek()->toDateString(),
            now()->endOfWeek()->toDateString()
        ]);
    }

    /**
     * Check if assignment is pending
     */
    public function isPending()
    {
        return $this->completion_status === 'pending';
    }

    /**
     * Check if assignment is in progress
     */
    public function isInProgress()
    {
        return $this->completion_status === 'in_progress';
    }

    /**
     * Check if assignment is completed
     */
    public function isCompleted()
    {
        return $this->completion_status === 'completed';
    }

    /**
     * Check if assignment is overdue
     */
    public function isOverdue()
    {
        return !$this->isCompleted() && $this->due_date < now()->toDateString();
    }

    /**
     * Mark assignment as in progress
     */
    public function markInProgress()
    {
        $this->update(['completion_status' => 'in_progress']);
    }

    /**
     * Mark assignment as completed
     */
    public function markCompleted()
    {
        $this->update(['completion_status' => 'completed']);
    }

    /**
     * Mark assignment as overdue
     */
    public function markOverdue()
    {
        $this->update(['completion_status' => 'overdue']);
    }

    /**
     * Get days until due date
     */
    public function getDaysUntilDue()
    {
        return now()->diffInDays($this->due_date, false);
    }

    /**
     * Get completion status options
     */
    public static function getCompletionStatusOptions()
    {
        return ['pending', 'in_progress', 'completed', 'overdue'];
    }

    /**
     * Get assignment progress percentage
     */
    public function getProgressPercentage()
    {
        $totalExercises = $this->hepProgram->hepExercises()->count();
        if ($totalExercises === 0) {
            return 0;
        }

        $completedExercises = $this->hepProgress()
            ->whereHas('hepExercise', function ($query) {
                $query->where('week_number', '<=', $this->getCurrentWeek());
            })
            ->distinct('hep_exercise_id')
            ->count('hep_exercise_id');

        return round(($completedExercises / $totalExercises) * 100);
    }

    /**
     * Get current week of the program
     */
    private function getCurrentWeek()
    {
        $daysSinceAssignment = now()->diffInDays($this->assigned_at);
        return min(ceil($daysSinceAssignment / 7), $this->hepProgram->duration_weeks);
    }
}
