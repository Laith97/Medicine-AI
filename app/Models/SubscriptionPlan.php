<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubscriptionPlan extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'price',
        'billing_cycle',
        'billing_period_months',
        'features',
        'is_active',
        'is_featured',
        'sort_order',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'billing_period_months' => 'integer',
        'features' => 'array',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Get the monthly invoice settings that use this plan
     */
    public function monthlyInvoiceSettings(): HasMany
    {
        return $this->hasMany(MonthlyInvoiceSetting::class);
    }

    /**
     * Get active plans only
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get plans ordered by sort order
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('price');
    }

    /**
     * Get monthly plans
     */
    public function scopeMonthly($query)
    {
        return $query->where('billing_cycle', 'monthly');
    }

    /**
     * Get yearly plans
     */
    public function scopeYearly($query)
    {
        return $query->where('billing_cycle', 'yearly');
    }

    /**
     * Get formatted price with billing cycle
     */
    public function getFormattedPriceAttribute(): string
    {
        $suffix = $this->billing_cycle === 'monthly' ? '/month' : '/year';
        return '$' . number_format($this->price, 2) . $suffix;
    }

    /**
     * Get monthly equivalent price for comparison
     */
    public function getMonthlyEquivalentPriceAttribute(): float
    {
        if ($this->billing_cycle === 'monthly') {
            return $this->price;
        }
        
        return $this->price / 12;
    }

    /**
     * Get savings compared to monthly plan
     */
    public function getSavingsPercentage($monthlyPrice): float
    {
        if ($this->billing_cycle === 'monthly' || $monthlyPrice <= 0) {
            return 0;
        }
        
        $yearlyMonthlyEquivalent = $this->price / 12;
        $savings = (($monthlyPrice - $yearlyMonthlyEquivalent) / $monthlyPrice) * 100;
        
        return max(0, $savings);
    }

    /**
     * Check if this is a yearly plan
     */
    public function isYearly(): bool
    {
        return $this->billing_cycle === 'yearly';
    }

    /**
     * Check if this is a monthly plan
     */
    public function isMonthly(): bool
    {
        return $this->billing_cycle === 'monthly';
    }

    /**
     * Get the plan's features as a formatted list
     */
    public function getFormattedFeatures(): array
    {
        return $this->features ?? [];
    }

    /**
     * Get billing period text
     */
    public function getBillingPeriodText(): string
    {
        return match($this->billing_period_months) {
            1 => 'Monthly',
            12 => 'Yearly',
            default => $this->billing_period_months . ' Months'
        };
    }
}