<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CompensationPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'doctor_id',
        'name',
        'plan_type',
        'base_salary',
        'base_hourly_rate',
        'commission_percentage',
        'bonus_threshold',
        'bonus_percentage',
        'cpt_commission_rates',
        'is_active',
        'effective_date',
        'end_date',
    ];

    protected $casts = [
        'base_salary' => 'decimal:2',
        'base_hourly_rate' => 'decimal:2',
        'commission_percentage' => 'decimal:2',
        'bonus_threshold' => 'decimal:2',
        'bonus_percentage' => 'decimal:2',
        'cpt_commission_rates' => 'array',
        'is_active' => 'boolean',
        'effective_date' => 'date',
        'end_date' => 'date',
    ];

    /**
     * Get the doctor that owns the compensation plan.
     */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    /**
     * Get compensations for this plan.
     */
    public function compensations(): HasMany
    {
        return $this->hasMany(ProviderCompensation::class);
    }

    /**
     * Get bonuses for this plan.
     */
    public function bonuses(): HasMany
    {
        return $this->hasMany(ProviderBonus::class);
    }

    /**
     * Check if plan is currently active.
     */
    public function isActive(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $now = now()->toDateString();
        if ($this->effective_date && $this->effective_date > $now) {
            return false;
        }
        if ($this->end_date && $this->end_date < $now) {
            return false;
        }

        return true;
    }

    /**
     * Calculate commission for a given amount.
     */
    public function calculateCommission(float $amount, ?string $cptCode = null): float
    {
        // Check for CPT-specific rate first
        if ($cptCode && $this->cpt_commission_rates) {
            $category = $this->getCptCategory($cptCode);
            if (isset($this->cpt_commission_rates[$category])) {
                return $amount * ($this->cpt_commission_rates[$category] / 100);
            }
        }

        // Use default commission rate
        if ($this->commission_percentage) {
            return $amount * ($this->commission_percentage / 100);
        }

        return 0;
    }

    /**
     * Get CPT code category.
     */
    private function getCptCategory(string $cptCode): string
    {
        // Simple category based on CPT range
        $code = (int) substr($this->sanitizeCptCode($cptCode), 0, 3);

        if ($code >= 99201 && $code <= 99215) {
            return 'office_visit';
        } elseif ($code >= 99281 && $code <= 99285) {
            return 'emergency';
        } elseif ($code >= 99381 && $code <= 99385) {
            return 'preventive';
        } elseif ($code >= 99386 && $code <= 99387) {
            return 'preventive';
        } elseif ($code >= 90832 && $code <= 90847) {
            return 'therapy';
        } else {
            return 'other';
        }
    }

    /**
     * Sanitize CPT code.
     */
    private function sanitizeCptCode(string $cptCode): string
    {
        return preg_replace('/[^0-9]/', '', $cptCode);
    }

    /**
     * Scope for active plans.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('effective_date')
                    ->orWhere('effective_date', '<=', now()->toDateString());
            })
            ->where(function ($q) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>=', now()->toDateString());
            });
    }
}
