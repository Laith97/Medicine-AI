<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProviderContract extends Model
{
    use HasFactory;

    protected $fillable = [
        'insurance_provider_id',
        'hospital_id',
        'contract_number',
        'clearinghouse_provider',
        'routing_rules',
        'fee_schedule',
        'contract_rate',
        'auto_submit',
        'batch_size_limit',
        'supported_claim_types',
        'effective_date',
        'expiration_date',
        'is_active',
    ];

    protected $casts = [
        'routing_rules' => 'array',
        'fee_schedule' => 'array',
        'contract_rate' => 'decimal:4',
        'auto_submit' => 'boolean',
        'supported_claim_types' => 'array',
        'effective_date' => 'date',
        'expiration_date' => 'date',
        'is_active' => 'boolean',
    ];

    /**
     * Get the insurance provider that owns the contract.
     */
    public function insuranceProvider(): BelongsTo
    {
        return $this->belongsTo(InsuranceProvider::class);
    }

    /**
     * Get the hospital that owns the contract.
     */
    public function hospital(): BelongsTo
    {
        return $this->belongsTo(User::class, 'hospital_id');
    }

    /**
     * Scope for active contracts
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
                    ->where('effective_date', '<=', now())
                    ->where(function ($q) {
                        $q->whereNull('expiration_date')
                          ->orWhere('expiration_date', '>=', now());
                    });
    }

    /**
     * Scope for contracts by clearinghouse provider
     */
    public function scopeByClearinghouse($query, $provider)
    {
        return $query->where('clearinghouse_provider', $provider);
    }

    /**
     * Check if contract supports a claim type
     */
    public function supportsClaimType(string $claimType): bool
    {
        if (!$this->supported_claim_types) {
            return true; // If no specific types defined, support all
        }

        return in_array($claimType, $this->supported_claim_types);
    }

    /**
     * Get routing rules for a claim
     */
    public function getRoutingRules(): array
    {
        return $this->routing_rules ?? [];
    }
}
