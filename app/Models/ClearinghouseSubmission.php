<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClearinghouseSubmission extends Model
{
    use HasFactory;

    protected $fillable = [
        'clearinghouse_account_id',
        'batch_id',
        'submission_type',
        'status',
        'edi_content',
        'file_name',
        'claim_count',
        'total_amount',
        'submitted_at',
        'response_received_at',
        'error_message',
        'metadata',
    ];

    protected $casts = [
        'claim_count' => 'integer',
        'total_amount' => 'decimal:2',
        'submitted_at' => 'datetime',
        'response_received_at' => 'datetime',
        'metadata' => 'json',
    ];

    /**
     * Get the clearinghouse account that owns this submission.
     */
    public function clearinghouseAccount(): BelongsTo
    {
        return $this->belongsTo(ClearinghouseAccount::class);
    }

    /**
     * Get the responses for this submission.
     */
    public function responses(): HasMany
    {
        return $this->hasMany(ClearinghouseResponse::class);
    }

    /**
     * Get the claims associated with this submission.
     */
    public function claims()
    {
        return $this->hasMany(Claim::class, 'clearinghouse_submission_id');
    }

    /**
     * Scope for pending submissions
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for submitted submissions
     */
    public function scopeSubmitted($query)
    {
        return $query->where('status', 'submitted');
    }

    /**
     * Scope for accepted submissions
     */
    public function scopeAccepted($query)
    {
        return $query->where('status', 'accepted');
    }

    /**
     * Scope for rejected submissions
     */
    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    /**
     * Scope for submissions by type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('submission_type', $type);
    }

    /**
     * Mark as submitted
     */
    public function markAsSubmitted(): void
    {
        $this->update([
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);
    }

    /**
     * Mark as accepted
     */
    public function markAsAccepted(): void
    {
        $this->update(['status' => 'accepted']);
    }

    /**
     * Mark as rejected
     */
    public function markAsRejected(string $errorMessage = null): void
    {
        $this->update([
            'status' => 'rejected',
            'error_message' => $errorMessage,
        ]);
    }

    /**
     * Mark response as received
     */
    public function markResponseReceived(): void
    {
        $this->update(['response_received_at' => now()]);
    }

    /**
     * Check if submission is complete
     */
    public function isComplete(): bool
    {
        return in_array($this->status, ['accepted', 'rejected', 'partial_accept']);
    }
}
