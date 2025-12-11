<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClearinghouseResponse extends Model
{
    use HasFactory;

    protected $fillable = [
        'clearinghouse_account_id',
        'clearinghouse_submission_id',
        'response_type',
        'transaction_set_id',
        'batch_id',
        'status',
        'response_content',
        'parsed_data',
        'claim_count',
        'total_paid_amount',
        'total_adjustment_amount',
        'received_at',
        'processed_at',
        'processing_errors',
        'metadata',
    ];

    protected $casts = [
        'parsed_data' => 'json',
        'claim_count' => 'integer',
        'total_paid_amount' => 'decimal:2',
        'total_adjustment_amount' => 'decimal:2',
        'received_at' => 'datetime',
        'processed_at' => 'datetime',
        'metadata' => 'json',
    ];

    /**
     * Get the clearinghouse account that owns this response.
     */
    public function clearinghouseAccount(): BelongsTo
    {
        return $this->belongsTo(ClearinghouseAccount::class);
    }

    /**
     * Get the submission this response belongs to.
     */
    public function submission(): BelongsTo
    {
        return $this->belongsTo(ClearinghouseSubmission::class, 'clearinghouse_submission_id');
    }

    /**
     * Scope for received responses
     */
    public function scopeReceived($query)
    {
        return $query->where('status', 'received');
    }

    /**
     * Scope for processed responses
     */
    public function scopeProcessed($query)
    {
        return $query->where('status', 'processed');
    }

    /**
     * Scope for error responses
     */
    public function scopeErrors($query)
    {
        return $query->where('status', 'error');
    }

    /**
     * Scope for responses by type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('response_type', $type);
    }

    /**
     * Scope for 277CA responses
     */
    public function scopeClaimAcknowledgements($query)
    {
        return $query->where('response_type', '277CA');
    }

    /**
     * Scope for 835 responses
     */
    public function scopeRemittanceAdvices($query)
    {
        return $query->where('response_type', '835');
    }

    /**
     * Mark as processed
     */
    public function markAsProcessed(): void
    {
        $this->update([
            'status' => 'processed',
            'processed_at' => now(),
        ]);
    }

    /**
     * Mark as error
     */
    public function markAsError(string $errorMessage): void
    {
        $this->update([
            'status' => 'error',
            'processing_errors' => $errorMessage,
        ]);
    }

    /**
     * Check if response is processed
     */
    public function isProcessed(): bool
    {
        return $this->status === 'processed';
    }

    /**
     * Check if response has errors
     */
    public function hasErrors(): bool
    {
        return $this->status === 'error' || !empty($this->processing_errors);
    }
}
