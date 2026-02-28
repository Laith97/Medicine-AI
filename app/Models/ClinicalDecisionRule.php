<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ClinicalDecisionRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'trigger_conditions',
        'action_type',
        'action_payload',
        'priority',
        'is_active',
        'evidence_reference'
    ];

    protected $casts = [
        'trigger_conditions' => 'array',
        'action_payload' => 'array',
        'priority' => 'integer',
        'is_active' => 'boolean'
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByPriority($query, $minPriority = 5)
    {
        return $query->where('priority', '>=', $minPriority);
    }
}
