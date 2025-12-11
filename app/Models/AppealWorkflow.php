<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppealWorkflow extends Model
{
    use HasFactory;

    protected $fillable = [
        'claim_id',
        'denial_category',
        'current_step',
        'workflow_steps',
        'completed_steps',
        'deadline',
        'auto_appeal_eligible',
        'appeal_probability',
        'appeal_reason',
        'required_documents',
        'status',
    ];

    protected $casts = [
        'workflow_steps' => 'array',
        'completed_steps' => 'array',
        'deadline' => 'date',
        'auto_appeal_eligible' => 'boolean',
        'appeal_probability' => 'decimal:4',
        'required_documents' => 'array',
    ];

    /**
     * Get the claim that owns the appeal workflow.
     */
    public function claim(): BelongsTo
    {
        return $this->belongsTo(Claim::class);
    }

    /**
     * Scope for pending workflows
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for in progress workflows
     */
    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    /**
     * Scope for workflows by denial category
     */
    public function scopeByDenialCategory($query, $category)
    {
        return $query->where('denial_category', $category);
    }

    /**
     * Scope for auto-appeal eligible workflows
     */
    public function scopeAutoAppealEligible($query)
    {
        return $query->where('auto_appeal_eligible', true);
    }

    /**
     * Scope for overdue workflows
     */
    public function scopeOverdue($query)
    {
        return $query->where('deadline', '<', now())
                    ->whereIn('status', ['pending', 'in_progress']);
    }

    /**
     * Check if workflow is overdue
     */
    public function isOverdue(): bool
    {
        return $this->deadline && $this->deadline->isPast() &&
               in_array($this->status, ['pending', 'in_progress']);
    }

    /**
     * Get next step in workflow
     */
    public function getNextStep(): ?string
    {
        $steps = $this->workflow_steps ?? [];
        $completed = $this->completed_steps ?? [];

        foreach ($steps as $step) {
            if (!in_array($step, $completed)) {
                return $step;
            }
        }

        return null;
    }

    /**
     * Complete current step
     */
    public function completeStep(string $step): void
    {
        $completed = $this->completed_steps ?? [];
        if (!in_array($step, $completed)) {
            $completed[] = $step;
            $this->completed_steps = $completed;
            $this->current_step = $this->getNextStep() ?? 'completed';
            $this->save();
        }
    }

    /**
     * Check if workflow is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed' || $this->getNextStep() === null;
    }
}
