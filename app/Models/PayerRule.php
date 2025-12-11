<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayerRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'payer_id',
        'rule_type_id',
        'conditions',
        'actions',
        'priority',
    ];

    protected $casts = [
        'conditions' => 'array',
        'actions' => 'array',
        'priority' => 'integer',
    ];

    /**
     * Get the payer that owns the rule.
     */
    public function payer(): BelongsTo
    {
        return $this->belongsTo(Payer::class);
    }

    /**
     * Get the rule type.
     */
    public function ruleType(): BelongsTo
    {
        return $this->belongsTo(RuleType::class);
    }

    /**
     * Get the rule applications.
     */
    public function applications(): HasMany
    {
        return $this->hasMany(RuleApplication::class, 'rule_id');
    }

    /**
     * Scope for rules by priority (higher priority first).
     */
    public function scopeByPriority($query)
    {
        return $query->orderBy('priority', 'desc');
    }

    /**
     * Scope for rules by payer.
     */
    public function scopeForPayer($query, $payerId)
    {
        return $query->where('payer_id', $payerId);
    }

    /**
     * Scope for rules by type.
     */
    public function scopeOfType($query, $ruleTypeId)
    {
        return $query->where('rule_type_id', $ruleTypeId);
    }
}
