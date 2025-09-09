<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BillingUnderpaymentAlert extends Model
{
    use HasFactory;

    protected $fillable = [
        'claim_id',
        'expected_amount',
        'paid_amount',
        'variance',
        'threshold_percentage',
        'flagged_at',
        'status',
        'notes',
    ];

    protected $casts = [
        'expected_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'variance' => 'decimal:2',
        'threshold_percentage' => 'decimal:2',
        'flagged_at' => 'datetime',
    ];

    /**
     * Get the claim that this alert belongs to.
     */
    public function claim(): BelongsTo
    {
        return $this->belongsTo(Claim::class);
    }

    /**
     * Scope to get active alerts.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope to get alerts by status.
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }
}
