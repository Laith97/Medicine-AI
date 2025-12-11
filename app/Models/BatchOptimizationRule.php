<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BatchOptimizationRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'hospital_id',
        'rule_name',
        'description',
        'clearinghouse_provider',
        'grouping_criteria',
        'max_batch_size',
        'min_batch_size',
        'max_total_amount',
        'priority_rules',
        'auto_create_batches',
        'submission_cutoff_time',
        'exclusion_rules',
        'is_active',
    ];

    protected $casts = [
        'grouping_criteria' => 'array',
        'max_total_amount' => 'decimal:2',
        'priority_rules' => 'array',
        'auto_create_batches' => 'boolean',
        'submission_cutoff_time' => 'datetime:H:i',
        'exclusion_rules' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Get the hospital that owns the rule.
     */
    public function hospital(): BelongsTo
    {
        return $this->belongsTo(User::class, 'hospital_id');
    }

    /**
     * Scope for active rules
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for rules by clearinghouse provider
     */
    public function scopeByClearinghouse($query, $provider)
    {
        return $query->where('clearinghouse_provider', $provider);
    }

    /**
     * Check if claim should be excluded based on rules
     */
    public function shouldExcludeClaim(Claim $claim): bool
    {
        if (!$this->exclusion_rules) {
            return false;
        }

        foreach ($this->exclusion_rules as $rule) {
            if ($this->matchesExclusionRule($claim, $rule)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if claim matches exclusion rule
     */
    private function matchesExclusionRule(Claim $claim, array $rule): bool
    {
        $field = $rule['field'] ?? null;
        $operator = $rule['operator'] ?? null;
        $value = $rule['value'] ?? null;

        if (!$field || !$operator || $value === null) {
            return false;
        }

        $claimValue = $this->getClaimFieldValue($claim, $field);

        switch ($operator) {
            case 'equals':
                return $claimValue == $value;
            case 'not_equals':
                return $claimValue != $value;
            case 'greater_than':
                return $claimValue > $value;
            case 'less_than':
                return $claimValue < $value;
            case 'contains':
                return str_contains((string)$claimValue, (string)$value);
            case 'in':
                return in_array($claimValue, (array)$value);
            default:
                return false;
        }
    }

    /**
     * Get claim field value
     */
    private function getClaimFieldValue(Claim $claim, string $field)
    {
        return match ($field) {
            'total_amount' => $claim->total_amount,
            'payer' => $claim->payer,
            'claim_status' => $claim->claim_status,
            'provider_name' => $claim->provider_name,
            'patient_name' => $claim->patient_name,
            default => $claim->$field ?? null,
        };
    }

    /**
     * Get grouping key for claim
     */
    public function getGroupingKey(Claim $claim): string
    {
        if (!$this->grouping_criteria) {
            return 'default';
        }

        $keyParts = [];
        foreach ($this->grouping_criteria as $criterion) {
            $field = $criterion['field'] ?? null;
            if ($field) {
                $value = $this->getClaimFieldValue($claim, $field);
                $keyParts[] = $field . ':' . $value;
            }
        }

        return implode('|', $keyParts) ?: 'default';
    }

    /**
     * Check if batch size is valid
     */
    public function isValidBatchSize(int $size): bool
    {
        return $size >= $this->min_batch_size && $size <= $this->max_batch_size;
    }

    /**
     * Check if total amount is valid
     */
    public function isValidTotalAmount(float $amount): bool
    {
        return !$this->max_total_amount || $amount <= $this->max_total_amount;
    }
}
