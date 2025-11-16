<?php

namespace App\Services;

use App\Models\Claim;
use App\Models\Payer;
use App\Models\PayerRule;
use App\Models\RuleApplication;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class PayerRulesEngine
{
    /**
     * Evaluate rules for a claim.
     *
     * @param Claim $claim
     * @return Collection
     */
    public function evaluateClaim(Claim $claim): Collection
    {
        $payer = $this->findPayerForClaim($claim);
        if (!$payer) {
            Log::info("No payer found for claim {$claim->id}");
            return collect();
        }

        $rules = $this->getRulesForPayer($payer);
        $results = collect();

        foreach ($rules as $rule) {
            $result = $this->evaluateRule($rule, $claim);
            if ($result) {
                $results->push($result);
                $this->recordRuleApplication($rule, $claim, $result);
            }
        }

        return $this->resolveConflicts($results);
    }

    /**
     * Evaluate a single rule against a claim.
     *
     * @param PayerRule $rule
     * @param Claim $claim
     * @return array|null
     */
    protected function evaluateRule(PayerRule $rule, Claim $claim): ?array
    {
        $conditions = $rule->conditions ?? [];
        $actions = $rule->actions ?? [];

        // Evaluate conditions
        if (!$this->evaluateConditions($conditions, $claim)) {
            return null;
        }

        // Execute actions
        $result = [
            'rule_id' => $rule->id,
            'rule_type' => $rule->ruleType->name,
            'priority' => $rule->priority,
            'actions' => $this->executeActions($actions, $claim),
            'applied_at' => now(),
        ];

        return $result;
    }

    /**
     * Evaluate conditions for a rule.
     *
     * @param array $conditions
     * @param Claim $claim
     * @return bool
     */
    protected function evaluateConditions(array $conditions, Claim $claim): bool
    {
        foreach ($conditions as $condition) {
            if (!$this->evaluateCondition($condition, $claim)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Evaluate a single condition.
     *
     * @param array $condition
     * @param Claim $claim
     * @return bool
     */
    protected function evaluateCondition(array $condition, Claim $claim): bool
    {
        $type = $condition['type'] ?? '';
        $operator = $condition['operator'] ?? 'equals';
        $value = $condition['value'] ?? null;

        switch ($type) {
            case 'diagnosis_code':
                return $this->evaluateDiagnosisCodeCondition($claim, $operator, $value);
            case 'procedure_code':
                return $this->evaluateProcedureCodeCondition($claim, $operator, $value);
            case 'amount':
                return $this->evaluateAmountCondition($claim, $operator, $value);
            case 'payer':
                return $this->evaluatePayerCondition($claim, $operator, $value);
            default:
                Log::warning("Unknown condition type: {$type}");
                return false;
        }
    }

    /**
     * Execute actions for a rule.
     *
     * @param array $actions
     * @param Claim $claim
     * @return array
     */
    protected function executeActions(array $actions, Claim $claim): array
    {
        $results = [];

        foreach ($actions as $action) {
            $result = $this->executeAction($action, $claim);
            if ($result) {
                $results[] = $result;
            }
        }

        return $results;
    }

    /**
     * Execute a single action.
     *
     * @param array $action
     * @param Claim $claim
     * @return array|null
     */
    protected function executeAction(array $action, Claim $claim): ?array
    {
        $type = $action['type'] ?? '';

        switch ($type) {
            case 'warning':
                return $this->executeWarningAction($action, $claim);
            case 'auto_correction':
                return $this->executeAutoCorrectionAction($action, $claim);
            case 'denial':
                return $this->executeDenialAction($action, $claim);
            default:
                Log::warning("Unknown action type: {$type}");
                return null;
        }
    }

    /**
     * Find payer for a claim.
     *
     * @param Claim $claim
     * @return Payer|null
     */
    protected function findPayerForClaim(Claim $claim): ?Payer
    {
        return Payer::findByPayerId($claim->payer);
    }

    /**
     * Get rules for a payer, ordered by priority.
     *
     * @param Payer $payer
     * @return Collection
     */
    protected function getRulesForPayer(Payer $payer): Collection
    {
        return $payer->rules()->with('ruleType')->byPriority()->get();
    }

    /**
     * Record rule application with comprehensive audit logging.
     *
     * @param PayerRule $rule
     * @param Claim $claim
     * @param array $result
     * @return void
     */
    protected function recordRuleApplication(PayerRule $rule, Claim $claim, array $result): void
    {
        $startTime = microtime(true);

        $applicationData = [
            'rule_id' => $rule->id,
            'claim_id' => $claim->id,
            'user_id' => auth()->id(),
            'session_id' => session()->getId(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'request_id' => request()->header('X-Request-ID') ?? uniqid('req_', true),
            'application_result' => $result,
            'rule_conditions' => $rule->conditions ?? [],
            'rule_actions' => $rule->actions ?? [],
            'rule_triggered' => !empty($result['actions'] ?? []),
            'applied_at' => now(),
        ];

        // Calculate execution time
        $endTime = microtime(true);
        $applicationData['execution_time_ms'] = round(($endTime - $startTime) * 1000, 2);

        // Determine data classification and HIPAA compliance
        $applicationData['data_classification'] = $this->determineDataClassification($claim, $rule);
        $applicationData['hipaa_compliance_flags'] = $this->assessHipaaCompliance($claim, $rule, $result);
        $applicationData['data_retention_until'] = $this->calculateRetentionDate($applicationData['data_classification']);

        // Determine outcome status
        $applicationData['outcome_status'] = $this->determineOutcomeStatus($result);
        $applicationData['outcome_reason'] = $this->determineOutcomeReason($result);

        // Add compliance event type
        $applicationData['compliance_event_type'] = $this->determineComplianceEventType($rule, $result);

        // Add audit metadata
        $applicationData['audit_metadata'] = $this->gatherAuditMetadata($rule, $claim, $result);

        RuleApplication::create($applicationData);

        // Log to audit service for additional compliance tracking
        if ($applicationData['rule_triggered']) {
            AuditLoggingService::logRuleApplication(
                $rule->id,
                $claim->id,
                auth()->id(),
                $result,
                'payer_rule_execution'
            );
        }
    }

    /**
     * Resolve conflicts between rule results.
     *
     * @param Collection $results
     * @return Collection
     */
    protected function resolveConflicts(Collection $results): Collection
    {
        // Group by action type and resolve conflicts based on priority
        $grouped = $results->groupBy(function ($result) {
            return $result['actions'][0]['type'] ?? 'unknown';
        });

        $resolved = collect();

        foreach ($grouped as $actionType => $group) {
            // Take the highest priority rule for each action type
            $highestPriority = $group->sortByDesc('priority')->first();
            $resolved->push($highestPriority);
        }

        return $resolved;
    }

    // Condition evaluation methods
    protected function evaluateDiagnosisCodeCondition(Claim $claim, string $operator, $value): bool
    {
        $codes = $claim->icd10_codes ?? [];
        return $this->evaluateArrayCondition($codes, $operator, $value);
    }

    protected function evaluateProcedureCodeCondition(Claim $claim, string $operator, $value): bool
    {
        $codes = $claim->cpt_codes ?? [];
        return $this->evaluateArrayCondition($codes, $operator, $value);
    }

    protected function evaluateAmountCondition(Claim $claim, string $operator, $value): bool
    {
        $amount = $claim->expected_amount ?? 0;
        return $this->evaluateNumericCondition($amount, $operator, $value);
    }

    protected function evaluatePayerCondition(Claim $claim, string $operator, $value): bool
    {
        $payer = $claim->payer ?? '';
        return $this->evaluateStringCondition($payer, $operator, $value);
    }

    // Action execution methods
    protected function executeWarningAction(array $action, Claim $claim): array
    {
        return [
            'type' => 'warning',
            'message' => $action['message'] ?? 'Rule violation detected',
            'severity' => $action['severity'] ?? 'medium',
        ];
    }

    protected function executeAutoCorrectionAction(array $action, Claim $claim): array
    {
        return [
            'type' => 'auto_correction',
            'field' => $action['field'] ?? '',
            'old_value' => $claim->{$action['field']} ?? null,
            'new_value' => $action['new_value'] ?? null,
            'message' => $action['message'] ?? 'Auto-correction applied',
        ];
    }

    protected function executeDenialAction(array $action, Claim $claim): array
    {
        return [
            'type' => 'denial',
            'reason' => $action['reason'] ?? 'Claim denied by rule',
            'code' => $action['code'] ?? '',
        ];
    }

    // Helper methods
    protected function evaluateArrayCondition(array $array, string $operator, $value): bool
    {
        switch ($operator) {
            case 'contains':
                return in_array($value, $array);
            case 'not_contains':
                return !in_array($value, $array);
            case 'empty':
                return empty($array);
            case 'not_empty':
                return !empty($array);
            default:
                return false;
        }
    }

    protected function evaluateNumericCondition(float $number, string $operator, $value): bool
    {
        $value = (float) $value;
        switch ($operator) {
            case 'equals':
                return $number == $value;
            case 'not_equals':
                return $number != $value;
            case 'greater_than':
                return $number > $value;
            case 'less_than':
                return $number < $value;
            case 'greater_equal':
                return $number >= $value;
            case 'less_equal':
                return $number <= $value;
            default:
                return false;
        }
    }

    protected function evaluateStringCondition(string $string, string $operator, $value): bool
    {
        switch ($operator) {
            case 'equals':
                return $string === $value;
            case 'not_equals':
                return $string !== $value;
            case 'contains':
                return str_contains($string, $value);
            case 'starts_with':
                return str_starts_with($string, $value);
            case 'ends_with':
                return str_ends_with($string, $value);
            default:
                return false;
        }
    }

    // Compliance and audit helper methods
    protected function determineDataClassification(Claim $claim, PayerRule $rule): string
    {
        // Check if claim contains PHI (Protected Health Information)
        $hasPhi = !empty($claim->patient_id) ||
                 !empty($claim->icd10_codes) ||
                 !empty($claim->cpt_codes) ||
                 str_contains(strtolower($claim->claim_status ?? ''), 'denied');

        // Check if rule involves sensitive actions
        $sensitiveActions = ['denial', 'auto_correction'];
        $hasSensitiveActions = collect($rule->actions ?? [])->contains(function ($action) use ($sensitiveActions) {
            return in_array($action['type'] ?? '', $sensitiveActions);
        });

        if ($hasPhi && $hasSensitiveActions) {
            return 'phi'; // Protected Health Information
        } elseif ($hasPhi) {
            return 'health_data';
        } else {
            return 'internal';
        }
    }

    protected function assessHipaaCompliance(Claim $claim, PayerRule $rule, array $result): ?array
    {
        $flags = [];

        // Check for unauthorized data access patterns
        if (empty($result['actions']) && !empty($rule->actions)) {
            $flags[] = 'rule_not_executed';
        }

        // Check for data retention violations
        $classification = $this->determineDataClassification($claim, $rule);
        if ($classification === 'phi' && empty($result['data_retention_policy'])) {
            $flags[] = 'missing_retention_policy';
        }

        // Check for audit logging completeness
        if (empty($result['audit_trail'])) {
            $flags[] = 'missing_audit_trail';
        }

        return empty($flags) ? null : $flags;
    }

    protected function calculateRetentionDate(string $classification): ?\Carbon\Carbon
    {
        return match ($classification) {
            'phi' => now()->addYears(7), // HIPAA requires 7 years for PHI
            'health_data' => now()->addYears(5),
            'internal' => now()->addYears(3),
            default => now()->addYears(1),
        };
    }

    protected function determineOutcomeStatus(array $result): ?string
    {
        if (empty($result['actions'])) {
            return 'no_action';
        }

        $actionTypes = collect($result['actions'])->pluck('type');

        if ($actionTypes->contains('denial')) {
            return 'denied';
        } elseif ($actionTypes->contains('warning')) {
            return 'warning';
        } elseif ($actionTypes->contains('auto_correction')) {
            return 'corrected';
        } else {
            return 'processed';
        }
    }

    protected function determineOutcomeReason(array $result): ?string
    {
        if (empty($result['actions'])) {
            return 'No rule conditions met';
        }

        $reasons = [];
        foreach ($result['actions'] as $action) {
            switch ($action['type']) {
                case 'denial':
                    $reasons[] = $action['reason'] ?? 'Claim denied by rule';
                    break;
                case 'warning':
                    $reasons[] = $action['message'] ?? 'Rule violation warning';
                    break;
                case 'auto_correction':
                    $reasons[] = $action['message'] ?? 'Auto-correction applied';
                    break;
            }
        }

        return implode('; ', $reasons);
    }

    protected function determineComplianceEventType(PayerRule $rule, array $result): ?string
    {
        if (empty($result['actions'])) {
            return 'rule_evaluation_no_action';
        }

        $actionTypes = collect($result['actions'])->pluck('type');

        if ($actionTypes->contains('denial')) {
            return 'claim_denial_rule_triggered';
        } elseif ($actionTypes->contains('auto_correction')) {
            return 'data_modification_rule_triggered';
        } elseif ($actionTypes->contains('warning')) {
            return 'compliance_warning_generated';
        } else {
            return 'rule_processing_completed';
        }
    }

    protected function gatherAuditMetadata(PayerRule $rule, Claim $claim, array $result): array
    {
        return [
            'rule_version' => $rule->version ?? '1.0',
            'rule_category' => $rule->ruleType->name ?? 'unknown',
            'claim_amount' => $claim->expected_amount,
            'claim_status' => $claim->claim_status,
            'payer_name' => $claim->payer,
            'processing_timestamp' => now()->toISOString(),
            'rule_priority' => $rule->priority,
            'conditions_matched' => count($result['actions'] ?? []),
            'total_conditions' => count($rule->conditions ?? []),
            'environment' => app()->environment(),
            'laravel_version' => app()->version(),
        ];
    }
}
