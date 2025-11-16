<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RuleApplication extends Model
{
    use HasFactory;

    protected $fillable = [
        'rule_id',
        'claim_id',
        'user_id',
        'session_id',
        'ip_address',
        'user_agent',
        'request_id',
        'application_result',
        'rule_conditions',
        'rule_actions',
        'rule_triggered',
        'execution_time_ms',
        'data_classification',
        'hipaa_compliance_flags',
        'data_retention_until',
        'outcome_status',
        'outcome_reason',
        'user_acknowledged',
        'user_acknowledged_at',
        'audit_metadata',
        'compliance_event_type',
        'applied_at',
    ];

    protected $casts = [
        'application_result' => 'array',
        'rule_conditions' => 'array',
        'rule_actions' => 'array',
        'hipaa_compliance_flags' => 'array',
        'audit_metadata' => 'array',
        'applied_at' => 'datetime',
        'data_retention_until' => 'datetime',
        'user_acknowledged_at' => 'datetime',
        'execution_time_ms' => 'decimal:2',
        'rule_triggered' => 'boolean',
        'user_acknowledged' => 'boolean',
    ];

    /**
     * Get the rule that was applied.
     */
    public function rule(): BelongsTo
    {
        return $this->belongsTo(PayerRule::class, 'rule_id');
    }

    /**
     * Get the claim the rule was applied to.
     */
    public function claim(): BelongsTo
    {
        return $this->belongsTo(Claim::class);
    }

    /**
     * Get the user who triggered the rule application.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope for applications by rule.
     */
    public function scopeForRule($query, $ruleId)
    {
        return $query->where('rule_id', $ruleId);
    }

    /**
     * Scope for applications by claim.
     */
    public function scopeForClaim($query, $claimId)
    {
        return $query->where('claim_id', $claimId);
    }

    /**
     * Scope for applications by user.
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope for triggered rules only.
     */
    public function scopeTriggered($query)
    {
        return $query->where('rule_triggered', true);
    }

    /**
     * Scope for rules by outcome status.
     */
    public function scopeByOutcomeStatus($query, $status)
    {
        return $query->where('outcome_status', $status);
    }

    /**
     * Scope for HIPAA compliance flagged records.
     */
    public function scopeHipaaFlagged($query)
    {
        return $query->whereNotNull('hipaa_compliance_flags');
    }

    /**
     * Scope for records requiring data retention.
     */
    public function scopeRequiringRetention($query)
    {
        return $query->whereNotNull('data_retention_until');
    }

    /**
     * Scope for records past retention date.
     */
    public function scopePastRetentionDate($query)
    {
        return $query->where('data_retention_until', '<', now());
    }

    /**
     * Scope for recent applications.
     */
    public function scopeRecent($query, $days = 30)
    {
        return $query->where('applied_at', '>=', now()->subDays($days));
    }

    /**
     * Check if this application is HIPAA compliant.
     */
    public function isHipaaCompliant(): bool
    {
        return empty($this->hipaa_compliance_flags) || !in_array('non_compliant', $this->hipaa_compliance_flags ?? []);
    }

    /**
     * Check if data retention period has expired.
     */
    public function isRetentionExpired(): bool
    {
        return $this->data_retention_until && $this->data_retention_until->isPast();
    }

    /**
     * Mark as user acknowledged.
     */
    public function markAsAcknowledged(): void
    {
        $this->update([
            'user_acknowledged' => true,
            'user_acknowledged_at' => now(),
        ]);
    }

    /**
     * Get formatted execution time.
     */
    public function getFormattedExecutionTimeAttribute(): string
    {
        if (!$this->execution_time_ms) {
            return 'N/A';
        }

        if ($this->execution_time_ms < 1) {
            return round($this->execution_time_ms * 1000, 2) . ' μs';
        }

        return round($this->execution_time_ms, 2) . ' ms';
    }

    /**
     * Get compliance status badge class.
     */
    public function getComplianceStatusBadgeClass(): string
    {
        if (!$this->isHipaaCompliant()) {
            return 'badge bg-danger';
        }

        if ($this->data_classification === 'phi') {
            return 'badge bg-warning';
        }

        return 'badge bg-success';
    }
}
