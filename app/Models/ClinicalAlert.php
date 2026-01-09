<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClinicalAlert extends Model
{
    use HasFactory;

    protected $table = 'clinical_alerts';

    protected $fillable = [
        'patient_id',
        'rule_id',
        'decision_rule_id',
        'severity',
        'status',
        'message',
        'trigger_data',
        'triggered_at',
        'acknowledged_at',
        'acknowledged_by',
        'resolved_at',
        'resolved_by',
        'resolution_notes',
    ];

    protected $casts = [
        'trigger_data' => 'array',
        'triggered_at' => 'datetime',
        'acknowledged_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'patient_id');
    }

    public function rule(): BelongsTo
    {
        return $this->belongsTo(ClinicalAlertRule::class, 'rule_id');
    }

    public function decisionRule(): BelongsTo
    {
        return $this->belongsTo(ClinicalDecisionRule::class, 'decision_rule_id');
    }

    public function acknowledgedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acknowledged_by');
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }
}
