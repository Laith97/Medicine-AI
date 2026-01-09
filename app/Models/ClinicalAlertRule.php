<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClinicalAlertRule extends Model
{
    use HasFactory;

    protected $table = 'clinical_alert_rules';

    protected $fillable = [
        'name',
        'algorithm_type',
        'severity',
        'threshold_min',
        'threshold_max',
        'notification_channels',
        'is_active',
    ];

    protected $casts = [
        'threshold_min' => 'decimal:2',
        'threshold_max' => 'decimal:2',
        'notification_channels' => 'array',
        'is_active' => 'boolean',
    ];

    public function alerts(): HasMany
    {
        return $this->hasMany(ClinicalAlert::class, 'rule_id');
    }
}
