<?php

namespace App\Services;

use App\Models\Claim;
use App\Models\Payer;
use App\Models\PayerRule;
use App\Services\PayerRulesEngine;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;

class RuleTestingService
{
    protected PayerRulesEngine $rulesEngine;

    public function __construct(PayerRulesEngine $rulesEngine)
    {
        $this->rulesEngine = $rulesEngine;
    }

    /**
     * Test a rule against sample claim data.
     *
     * @param PayerRule $rule
     * @param array $claimData
     * @return array
     */
    public function testRule(PayerRule $rule, array $claimData): array
    {
        // Validate claim data structure
        $validationResult = $this->validateClaimData($claimData);
        if (!$validationResult['valid']) {
            return [
                'success' => false,
                'error' => 'Invalid claim data: ' . implode(', ', $validationResult['errors']),
            ];
        }

        // Create a mock claim object
        $mockClaim = $this->createMockClaim($claimData);

        // Create a temporary rules collection with just this rule
        $tempRules = collect([$rule]);

        // Temporarily modify the rules engine to only use this rule
        $originalRules = $rule->payer->rules ?? collect();
        $rule->payer->rules = $tempRules;

        // Test using the public evaluateClaim method
        $results = $this->rulesEngine->evaluateClaim($mockClaim);

        // Restore original rules
        $rule->payer->rules = $originalRules;

        $result = $results->first();

        return [
            'success' => true,
            'rule_triggered' => $result !== null,
            'result' => $result,
            'claim_data' => $claimData,
        ];
    }

    /**
     * Test multiple rules against sample claim data.
     *
     * @param Collection $rules
     * @param array $claimData
     * @return array
     */
    public function testRules(Collection $rules, array $claimData): array
    {
        $results = [];

        foreach ($rules as $rule) {
            $results[] = $this->testRule($rule, $claimData);
        }

        return [
            'success' => true,
            'total_rules' => $rules->count(),
            'triggered_rules' => collect($results)->where('rule_triggered', true)->count(),
            'results' => $results,
            'claim_data' => $claimData,
        ];
    }

    /**
     * Validate rule conditions and actions structure.
     *
     * @param PayerRule $rule
     * @return array
     */
    public function validateRule(PayerRule $rule): array
    {
        $errors = [];

        // Validate conditions
        if (!empty($rule->conditions)) {
            foreach ($rule->conditions as $condition) {
                $conditionErrors = $this->validateCondition($condition);
                $errors = array_merge($errors, $conditionErrors);
            }
        }

        // Validate actions
        if (!empty($rule->actions)) {
            foreach ($rule->actions as $action) {
                $actionErrors = $this->validateAction($action);
                $errors = array_merge($errors, $actionErrors);
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Generate sample claim data for testing.
     *
     * @param array $overrides
     * @return array
     */
    public function generateSampleClaimData(array $overrides = []): array
    {
        $defaultData = [
            'claim_id' => 'TEST' . rand(10000, 99999),
            'patient_id' => 1,
            'diagnosis_text' => 'Sample diagnosis',
            'procedure_text' => 'Sample procedure',
            'icd10_codes' => ['Z00.00', 'M79.3'],
            'cpt_codes' => ['99201', '85025'],
            'payer' => 'TEST_PAYER',
            'claim_status' => 'pending',
            'expected_amount' => 150.00,
            'paid_amount' => 0.00,
            'service_date' => now()->subDays(7)->toDateString(),
            'submission_date' => now()->toDateString(),
        ];

        return array_merge($defaultData, $overrides);
    }

    /**
     * Test rule performance with multiple claims.
     *
     * @param PayerRule $rule
     * @param array $claimDataSets
     * @return array
     */
    public function testRulePerformance(PayerRule $rule, array $claimDataSets): array
    {
        $startTime = microtime(true);
        $results = [];

        foreach ($claimDataSets as $claimData) {
            $results[] = $this->testRule($rule, $claimData);
        }

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        return [
            'success' => true,
            'total_tests' => count($claimDataSets),
            'execution_time' => $executionTime,
            'average_time_per_test' => $executionTime / count($claimDataSets),
            'triggered_count' => collect($results)->where('rule_triggered', true)->count(),
            'results' => $results,
        ];
    }

    /**
     * Validate condition structure.
     *
     * @param array $condition
     * @return array
     */
    protected function validateCondition(array $condition): array
    {
        $errors = [];

        if (!isset($condition['type'])) {
            $errors[] = 'Condition missing required field: type';
        }

        if (!isset($condition['operator'])) {
            $errors[] = 'Condition missing required field: operator';
        }

        $validTypes = ['diagnosis_code', 'procedure_code', 'amount', 'payer'];
        if (isset($condition['type']) && !in_array($condition['type'], $validTypes)) {
            $errors[] = "Invalid condition type: {$condition['type']}";
        }

        return $errors;
    }

    /**
     * Validate action structure.
     *
     * @param array $action
     * @return array
     */
    protected function validateAction(array $action): array
    {
        $errors = [];

        if (!isset($action['type'])) {
            $errors[] = 'Action missing required field: type';
        }

        $validTypes = ['warning', 'auto_correction', 'denial'];
        if (isset($action['type']) && !in_array($action['type'], $validTypes)) {
            $errors[] = "Invalid action type: {$action['type']}";
        }

        return $errors;
    }

    /**
     * Validate claim data structure.
     *
     * @param array $claimData
     * @return array
     */
    protected function validateClaimData(array $claimData): array
    {
        $validator = Validator::make($claimData, [
            'claim_id' => 'required|string',
            'patient_id' => 'required|integer',
            'icd10_codes' => 'nullable|array',
            'cpt_codes' => 'nullable|array',
            'payer' => 'nullable|string',
            'expected_amount' => 'nullable|numeric',
            'paid_amount' => 'nullable|numeric',
            'service_date' => 'nullable|date',
            'submission_date' => 'nullable|date',
        ]);

        return [
            'valid' => !$validator->fails(),
            'errors' => $validator->errors()->all(),
        ];
    }

    /**
     * Create a mock claim object from data.
     *
     * @param array $claimData
     * @return Claim
     */
    protected function createMockClaim(array $claimData): Claim
    {
        $claim = new Claim();

        foreach ($claimData as $key => $value) {
            $claim->$key = $value;
        }

        return $claim;
    }

    /**
     * Test rule conflicts with other rules.
     *
     * @param Collection $rules
     * @param array $claimData
     * @return array
     */
    public function testRuleConflicts(Collection $rules, array $claimData): array
    {
        $results = $this->testRules($rules, $claimData);
        $triggeredRules = collect($results['results'])->where('rule_triggered', true);

        $conflicts = [];

        // Check for conflicting actions
        $actionsByType = $triggeredRules->pluck('result.actions', 'result.rule_id')
            ->flatten(1)
            ->groupBy('type');

        foreach ($actionsByType as $actionType => $actions) {
            if ($actions->count() > 1) {
                $conflicts[] = [
                    'type' => 'multiple_' . $actionType . '_actions',
                    'description' => "Multiple rules triggered {$actionType} actions",
                    'rules' => $actions->pluck('rule_id')->unique()->values(),
                ];
            }
        }

        return [
            'success' => true,
            'has_conflicts' => !empty($conflicts),
            'conflicts' => $conflicts,
            'triggered_rules' => $triggeredRules->count(),
            'results' => $results,
        ];
    }
}
