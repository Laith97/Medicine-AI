<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ComplianceRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'rule_type',
        'event_type',
        'model_type',
        'conditions',
        'actions',
        'is_active',
        'priority',
        'metadata',
    ];

    protected $casts = [
        'conditions' => 'array',
        'actions' => 'array',
        'metadata' => 'array',
        'is_active' => 'boolean',
        'priority' => 'integer',
    ];

    /**
     * Scope for active rules
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for rules by type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('rule_type', $type);
    }

    /**
     * Scope for rules by event type
     */
    public function scopeByEventType($query, string $eventType)
    {
        return $query->where('event_type', $eventType);
    }

    /**
     * Scope for rules by model type
     */
    public function scopeByModelType($query, string $modelType)
    {
        return $query->where('model_type', $modelType);
    }

    /**
     * Scope for rules ordered by priority
     */
    public function scopeOrderedByPriority($query)
    {
        return $query->orderBy('priority', 'desc');
    }

    /**
     * Check if the rule matches the given conditions
     */
    public function matchesConditions(array $data): bool
    {
        if (empty($this->conditions)) {
            return true;
        }

        foreach ($this->conditions as $condition) {
            if (!$this->evaluateCondition($condition, $data)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Evaluate a single condition
     */
    protected function evaluateCondition(array $condition, array $data): bool
    {
        $field = $condition['field'] ?? null;
        $operator = $condition['operator'] ?? null;
        $value = $condition['value'] ?? null;

        if (!$field || !$operator) {
            return false;
        }

        $fieldValue = data_get($data, $field);

        switch ($operator) {
            case 'equals':
                return $fieldValue == $value;
            case 'not_equals':
                return $fieldValue != $value;
            case 'contains':
                return str_contains((string)$fieldValue, (string)$value);
            case 'not_contains':
                return !str_contains((string)$fieldValue, (string)$value);
            case 'greater_than':
                return $fieldValue > $value;
            case 'less_than':
                return $fieldValue < $value;
            case 'greater_than_or_equal':
                return $fieldValue >= $value;
            case 'less_than_or_equal':
                return $fieldValue <= $value;
            case 'in':
                return in_array($fieldValue, (array)$value);
            case 'not_in':
                return !in_array($fieldValue, (array)$value);
            case 'is_null':
                return is_null($fieldValue);
            case 'is_not_null':
                return !is_null($fieldValue);
            case 'regex':
                return preg_match($value, (string)$fieldValue);
            default:
                return false;
        }
    }

    /**
     * Get the actions for this rule
     */
    public function getActions(): array
    {
        return $this->actions ?? [];
    }
}
