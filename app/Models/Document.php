<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Document extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'document_type',
        'status',
        'workflow_state',
        'metadata',
        'compliance_data',
        'current_version',
        'content',
        'created_by',
        'updated_by',
        'submitted_at',
        'approved_at',
        'rejected_at',
        'rejection_reason',
    ];

    protected $casts = [
        'metadata' => 'array',
        'compliance_data' => 'array',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'current_version' => 'integer',
    ];

    /**
     * Get the user who created the document.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated the document.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the workflow tasks for this document.
     */
    public function workflowTasks(): HasMany
    {
        return $this->hasMany(WorkflowTask::class, 'taskable_id')->where('taskable_type', self::class);
    }

    /**
     * Get the documentable model (polymorphic relationship to actual document content).
     */
    public function documentable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the versions for this document.
     */
    public function versions(): HasMany
    {
        return $this->hasMany(DocumentVersion::class)->orderBy('version_number', 'desc');
    }

    /**
     * Get the current version of this document.
     */
    public function currentVersion(): HasMany
    {
        return $this->hasMany(DocumentVersion::class)->where('version_number', $this->current_version);
    }

    /**
     * Get the template this document was created from.
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(DocumentTemplate::class, 'template_id');
    }

    /**
     * Scope for documents by type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('document_type', $type);
    }

    /**
     * Scope for documents by status
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope for documents by workflow state
     */
    public function scopeByWorkflowState($query, string $state)
    {
        return $query->where('workflow_state', $state);
    }

    /**
     * Scope for draft documents
     */
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    /**
     * Scope for submitted documents
     */
    public function scopeSubmitted($query)
    {
        return $query->where('status', 'submitted');
    }

    /**
     * Scope for documents under review
     */
    public function scopeUnderReview($query)
    {
        return $query->where('status', 'under_review');
    }

    /**
     * Scope for approved documents
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope for rejected documents
     */
    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    /**
     * Check if document can transition to a new state
     */
    public function canTransitionTo(string $newState): bool
    {
        $currentState = $this->workflow_state;

        $allowedTransitions = [
            'created' => ['draft', 'submitted'],
            'draft' => ['submitted', 'archived'],
            'submitted' => ['under_review', 'rejected'],
            'under_review' => ['approved', 'rejected', 'escalated'],
            'approved' => ['archived'],
            'rejected' => ['draft', 'archived'],
            'escalated' => ['under_review', 'approved', 'rejected'],
            'archived' => [], // Terminal state
        ];

        return in_array($newState, $allowedTransitions[$currentState] ?? []);
    }

    /**
     * Transition document to a new workflow state
     */
    public function transitionTo(string $newState, array $metadata = []): bool
    {
        if (!$this->canTransitionTo($newState)) {
            return false;
        }

        $this->workflow_state = $newState;

        // Update status based on workflow state
        $statusMapping = [
            'created' => 'draft',
            'draft' => 'draft',
            'submitted' => 'submitted',
            'under_review' => 'under_review',
            'approved' => 'approved',
            'rejected' => 'rejected',
            'escalated' => 'under_review',
            'archived' => 'archived',
        ];

        if (isset($statusMapping[$newState])) {
            $this->status = $statusMapping[$newState];

            // Set timestamps based on status
            switch ($newState) {
                case 'submitted':
                    $this->submitted_at = now();
                    break;
                case 'approved':
                    $this->approved_at = now();
                    break;
                case 'rejected':
                    $this->rejected_at = now();
                    break;
            }
        }

        // Merge metadata
        $existingMetadata = $this->metadata ?? [];
        $this->metadata = array_merge($existingMetadata, $metadata);

        $this->save();

        return true;
    }

    /**
     * Get available transitions from current state
     */
    public function getAvailableTransitions(): array
    {
        $currentState = $this->workflow_state;

        $allowedTransitions = [
            'created' => ['draft', 'submitted'],
            'draft' => ['submitted', 'archived'],
            'submitted' => ['under_review', 'rejected'],
            'under_review' => ['approved', 'rejected', 'escalated'],
            'approved' => ['archived'],
            'rejected' => ['draft', 'archived'],
            'escalated' => ['under_review', 'approved', 'rejected'],
            'archived' => [], // Terminal state
        ];

        return $allowedTransitions[$currentState] ?? [];
    }
}
