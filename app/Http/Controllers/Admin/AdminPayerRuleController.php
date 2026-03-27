<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payer;
use App\Models\PayerRule;
use App\Models\RuleType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AdminPayerRuleController extends Controller
{
    public function __construct()
    {
        $this->middleware('admin');
    }

    /**
     * Display a listing of rules for a payer
     */
    public function index(Request $request, Payer $payer)
    {
        $query = $payer->rules()->with(['ruleType']);

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('ruleType', function ($rt) use ($search) {
                    $rt->where('name', 'like', "%{$search}%");
                });
            });
        }

        // Filter by rule type
        if ($request->filled('rule_type')) {
            $query->where('rule_type_id', $request->rule_type);
        }

        // Filter by priority
        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        $rules = $query->orderBy('priority', 'desc')->paginate(20);
        $ruleTypes = RuleType::orderBy('name')->get();

        return view('admin.payers.rules.index', compact('payer', 'rules', 'ruleTypes'));
    }

    /**
     * Show the form for creating a new rule
     */
    public function create(Payer $payer)
    {
        $ruleTypes = RuleType::orderBy('name')->get();

        return view('admin.payers.rules.create', compact('payer', 'ruleTypes'));
    }

    /**
     * Store a newly created rule
     */
    public function store(Request $request, Payer $payer)
    {
        $validated = $request->validate([
            'rule_type_id' => 'required|exists:rule_types,id',
            'conditions' => 'required|array',
            'actions' => 'required|array',
            'priority' => 'required|integer|min:1|max:100',
        ]);

        try {
            $validated['payer_id'] = $payer->id;

            $rule = PayerRule::create($validated);

            return redirect()->route('admin.payers.rules.show', [$payer, $rule])
                ->with('success', 'Rule created successfully.');

        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to create rule: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified rule
     */
    public function show(Payer $payer, PayerRule $rule)
    {
        // Ensure rule belongs to payer
        if ($rule->payer_id !== $payer->id) {
            abort(404);
        }

        $rule->load(['ruleType', 'applications' => function($query) {
            $query->latest()->limit(20);
        }]);

        // Get statistics
        $totalApplications = $rule->applications()->count();
        $successfulApplications = $rule->applications()->where('result', 'success')->count();
        $failedApplications = $rule->applications()->where('result', 'failure')->count();

        return view('admin.payers.rules.show', compact(
            'payer',
            'rule',
            'totalApplications',
            'successfulApplications',
            'failedApplications'
        ));
    }

    /**
     * Show the form for editing the specified rule
     */
    public function edit(Payer $payer, PayerRule $rule)
    {
        // Ensure rule belongs to payer
        if ($rule->payer_id !== $payer->id) {
            abort(404);
        }

        $ruleTypes = RuleType::orderBy('name')->get();

        return view('admin.payers.rules.edit', compact('payer', 'rule', 'ruleTypes'));
    }

    /**
     * Update the specified rule
     */
    public function update(Request $request, Payer $payer, PayerRule $rule)
    {
        // Ensure rule belongs to payer
        if ($rule->payer_id !== $payer->id) {
            abort(404);
        }

        $validated = $request->validate([
            'rule_type_id' => 'required|exists:rule_types,id',
            'conditions' => 'required|array',
            'actions' => 'required|array',
            'priority' => 'required|integer|min:1|max:100',
        ]);

        try {
            $rule->update($validated);

            return redirect()->route('admin.payers.rules.show', [$payer, $rule])
                ->with('success', 'Rule updated successfully.');

        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to update rule: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified rule
     */
    public function destroy(Payer $payer, PayerRule $rule)
    {
        // Ensure rule belongs to payer
        if ($rule->payer_id !== $payer->id) {
            abort(404);
        }

        try {
            // Check if rule has applications
            if ($rule->applications()->count() > 0) {
                return redirect()->back()
                    ->with('error', 'Cannot delete rule that has been applied.');
            }

            $rule->delete();

            return redirect()->route('admin.payers.rules.index', $payer)
                ->with('success', 'Rule deleted successfully.');

        } catch (\Exception $e) {
            return redirect()->route('admin.payers.rules.index', $payer)
                ->with('error', 'Failed to delete rule: ' . $e->getMessage());
        }
    }

    /**
     * Test a rule with sample claim data
     */
    public function test(Request $request, Payer $payer, PayerRule $rule)
    {
        // Ensure rule belongs to payer
        if ($rule->payer_id !== $payer->id) {
            abort(404);
        }

        $validator = Validator::make($request->all(), [
            'claim_data' => 'required|array',
            'claim_data.patient_id' => 'nullable|string',
            'claim_data.provider_id' => 'nullable|string',
            'claim_data.service_code' => 'nullable|string',
            'claim_data.diagnosis_codes' => 'nullable|array',
            'claim_data.procedure_codes' => 'nullable|array',
            'claim_data.amount' => 'nullable|numeric',
            'claim_data.date_of_service' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $claimData = $request->input('claim_data');
            $conditions = $rule->conditions ?? [];
            $actions = $rule->actions ?? [];

            // Evaluate conditions
            $conditionsMet = $this->evaluateConditions($conditions, $claimData);

            // Determine result based on conditions and actions
            $result = $this->determineResult($conditionsMet, $actions, $claimData);

            return response()->json([
                'success' => true,
                'result' => $result
            ]);

        } catch (\Exception $e) {
            \Log::error('Rule test failed: ' . $e->getMessage(), [
                'payer_id' => $payer->id,
                'rule_id' => $rule->id,
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'error' => 'An error occurred while testing the rule'
            ], 500);
        }
    }

    /**
     * Evaluate rule conditions against claim data
     */
    private function evaluateConditions(array $conditions, array $claimData): bool
    {
        if (empty($conditions)) {
            return true; // No conditions means rule always applies
        }

        // Handle single condition
        if (isset($conditions['field'])) {
            return $this->evaluateSingleCondition($conditions, $claimData);
        }

        // Handle multiple conditions with AND/OR logic
        $logic = $conditions['logic'] ?? 'and';
        $conditionList = $conditions['conditions'] ?? [];

        if (empty($conditionList)) {
            return true;
        }

        foreach ($conditionList as $condition) {
            $conditionResult = $this->evaluateConditions($condition, $claimData);

            if ($logic === 'and' && !$conditionResult) {
                return false;
            }
            if ($logic === 'or' && $conditionResult) {
                return true;
            }
        }

        return $logic === 'and';
    }

    /**
     * Evaluate a single condition
     */
    private function evaluateSingleCondition(array $condition, array $claimData): bool
    {
        $field = $condition['field'] ?? null;
        $operator = $condition['operator'] ?? 'equals';
        $expectedValue = $condition['value'] ?? null;

        if (!$field) {
            return false;
        }

        // Get the actual value from claim data (supports nested fields like 'diagnosis_codes.0')
        $actualValue = $this->getNestedValue($claimData, $field);

        // Handle null values
        if ($actualValue === null) {
            return $operator === 'is_null';
        }

        // Evaluate based on operator
        switch ($operator) {
            case 'equals':
            case '==':
                return $actualValue == $expectedValue;

            case 'not_equals':
            case '!=':
                return $actualValue != $expectedValue;

            case 'greater_than':
            case '>':
                return is_numeric($actualValue) && is_numeric($expectedValue) && $actualValue > $expectedValue;

            case 'greater_than_or_equal':
            case '>=':
                return is_numeric($actualValue) && is_numeric($expectedValue) && $actualValue >= $expectedValue;

            case 'less_than':
            case '<':
                return is_numeric($actualValue) && is_numeric($expectedValue) && $actualValue < $expectedValue;

            case 'less_than_or_equal':
            case '<=':
                return is_numeric($actualValue) && is_numeric($expectedValue) && $actualValue <= $expectedValue;

            case 'contains':
                return is_string($actualValue) && is_string($expectedValue)
                    && str_contains(strtolower($actualValue), strtolower($expectedValue));

            case 'in':
                $values = is_array($expectedValue) ? $expectedValue : [$expectedValue];
                return in_array($actualValue, $values);

            case 'not_in':
                $values = is_array($expectedValue) ? $expectedValue : [$expectedValue];
                return !in_array($actualValue, $values);

            case 'starts_with':
                return is_string($actualValue) && is_string($expectedValue)
                    && str_starts_with(strtolower($actualValue), strtolower($expectedValue));

            case 'ends_with':
                return is_string($actualValue) && is_string($expectedValue)
                    && str_ends_with(strtolower($actualValue), strtolower($expectedValue));

            case 'is_null':
                return $actualValue === null;

            case 'is_not_null':
                return $actualValue !== null;

            default:
                return false;
        }
    }

    /**
     * Get nested value from array using dot notation
     */
    private function getNestedValue(array $data, string $field)
    {
        $keys = explode('.', $field);
        $value = $data;

        foreach ($keys as $key) {
            if (is_array($value) && array_key_exists($key, $value)) {
                $value = $value[$key];
            } else {
                return null;
            }
        }

        return $value;
    }

    /**
     * Determine the result based on conditions and actions
     */
    private function determineResult(bool $conditionsMet, array $actions, array $claimData): array
    {
        $resultAction = $actions['action'] ?? 'approve';
        $modifications = $actions['modifications'] ?? [];

        if (!$conditionsMet) {
            return [
                'rule_applied' => false,
                'conditions_met' => false,
                'actions_taken' => [],
                'result' => 'pending_review',
                'notes' => 'Rule conditions not met - requires manual review'
            ];
        }

        // Apply result based on action
        switch ($resultAction) {
            case 'approve':
            case 'auto_approve':
                return [
                    'rule_applied' => true,
                    'conditions_met' => true,
                    'actions_taken' => $actions,
                    'result' => 'approved',
                    'notes' => 'Rule conditions met - automatically approved'
                ];

            case 'deny':
            case 'auto_deny':
                return [
                    'rule_applied' => true,
                    'conditions_met' => true,
                    'actions_taken' => $actions,
                    'result' => 'denied',
                    'reason' => $actions['denial_reason'] ?? 'Denied by payer rule',
                    'notes' => 'Rule conditions met - automatically denied'
                ];

            case 'modify':
                // Apply modifications to claim data
                $modifiedClaimData = $this->applyModifications($claimData, $modifications);
                return [
                    'rule_applied' => true,
                    'conditions_met' => true,
                    'actions_taken' => $actions,
                    'result' => 'modified',
                    'original_data' => $claimData,
                    'modified_data' => $modifiedClaimData,
                    'modifications' => $modifications,
                    'notes' => 'Rule conditions met - claim modified per rule'
                ];

            case 'pend':
            case 'pending_review':
            default:
                return [
                    'rule_applied' => true,
                    'conditions_met' => true,
                    'actions_taken' => $actions,
                    'result' => 'pending_review',
                    'notes' => 'Rule conditions met - flagged for pending review'
                ];
        }
    }

    /**
     * Apply modifications to claim data
     */
    private function applyModifications(array $claimData, array $modifications): array
    {
        $modified = $claimData;

        foreach ($modifications as $modification) {
            $field = $modification['field'] ?? null;
            $action = $modification['action'] ?? 'set';
            $value = $modification['value'] ?? null;

            if (!$field) {
                continue;
            }

            switch ($action) {
                case 'set':
                    $this->setNestedValue($modified, $field, $value);
                    break;

                case 'remove':
                    $this->removeNestedValue($modified, $field);
                    break;

                case 'multiply':
                    $currentValue = $this->getNestedValue($modified, $field);
                    if (is_numeric($currentValue)) {
                        $this->setNestedValue($modified, $field, $currentValue * $value);
                    }
                    break;

                case 'add':
                    $currentValue = $this->getNestedValue($modified, $field);
                    if (is_numeric($currentValue)) {
                        $this->setNestedValue($modified, $field, $currentValue + $value);
                    }
                    break;
            }
        }

        return $modified;
    }

    /**
     * Set nested value in array using dot notation
     */
    private function setNestedValue(array &$data, string $field, $value): void
    {
        $keys = explode('.', $field);
        $current = &$data;

        foreach ($keys as $i => $key) {
            if (!isset($current[$key]) && $i < count($keys) - 1) {
                $current[$key] = [];
            }
            if ($i === count($keys) - 1) {
                $current[$key] = $value;
            } else {
                $current = &$current[$key];
            }
        }
    }

    /**
     * Remove nested value from array using dot notation
     */
    private function removeNestedValue(array &$data, string $field): void
    {
        $keys = explode('.', $field);
        $current = &$data;

        foreach ($keys as $i => $key) {
            if (!isset($current[$key])) {
                return;
            }
            if ($i === count($keys) - 1) {
                unset($current[$key]);
            } else {
                $current = &$current[$key];
            }
        }
    }

    /**
     * Export rules for a payer
     */
    public function export(Payer $payer)
    {
        $rules = $payer->rules()->with('ruleType')->orderBy('priority', 'desc')->get();

        $filename = 'payer-rules-' . $payer->payer_id . '-' . now()->format('Y-m-d-H-i-s') . '.json';

        $headers = [
            'Content-Type' => 'application/json',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $exportData = [
            'payer' => [
                'id' => $payer->id,
                'name' => $payer->name,
                'payer_id' => $payer->payer_id,
            ],
            'rules' => $rules->map(function ($rule) {
                return [
                    'id' => $rule->id,
                    'rule_type' => $rule->ruleType->name,
                    'conditions' => $rule->conditions,
                    'actions' => $rule->actions,
                    'priority' => $rule->priority,
                    'created_at' => $rule->created_at->format('Y-m-d H:i:s'),
                    'updated_at' => $rule->updated_at->format('Y-m-d H:i:s'),
                ];
            }),
            'exported_at' => now()->toISOString(),
        ];

        return response()->json($exportData, 200, $headers);
    }

    /**
     * Show import form
     */
    public function importForm(Payer $payer)
    {
        return view('admin.payers.rules.import', compact('payer'));
    }

    /**
     * Import rules from JSON
     */
    public function import(Request $request, Payer $payer)
    {
        $request->validate([
            'rules_file' => 'required|file|mimes:json|max:5120', // 5MB max
        ]);

        try {
            $file = $request->file('rules_file');
            $content = json_decode($file->get(), true);

            if (!$content || !isset($content['rules'])) {
                return redirect()->back()
                    ->with('error', 'Invalid JSON format. Missing rules array.');
            }

            $imported = 0;
            $errors = [];

            foreach ($content['rules'] as $index => $ruleData) {
                try {
                    // Validate rule data
                    $validator = Validator::make($ruleData, [
                        'rule_type' => 'required|string',
                        'conditions' => 'required|array',
                        'actions' => 'required|array',
                        'priority' => 'required|integer|min:1|max:100',
                    ]);

                    if ($validator->fails()) {
                        $errors[] = "Rule " . ($index + 1) . ": " . implode(', ', $validator->errors()->all());
                        continue;
                    }

                    // Find rule type
                    $ruleType = RuleType::where('name', $ruleData['rule_type'])->first();
                    if (!$ruleType) {
                        $errors[] = "Rule " . ($index + 1) . ": Unknown rule type '{$ruleData['rule_type']}'";
                        continue;
                    }

                    PayerRule::create([
                        'payer_id' => $payer->id,
                        'rule_type_id' => $ruleType->id,
                        'conditions' => $ruleData['conditions'],
                        'actions' => $ruleData['actions'],
                        'priority' => $ruleData['priority'],
                    ]);

                    $imported++;

                } catch (\Exception $e) {
                    $errors[] = "Rule " . ($index + 1) . ": " . $e->getMessage();
                }
            }

            $message = "Import completed. {$imported} rules imported successfully.";
            if (!empty($errors)) {
                $message .= " Errors: " . implode('; ', array_slice($errors, 0, 5));
                if (count($errors) > 5) {
                    $message .= " (and " . (count($errors) - 5) . " more errors)";
                }
            }

            return redirect()->route('admin.payers.rules.index', $payer)
                ->with('success', $message);

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Import failed: ' . $e->getMessage());
        }
    }
}
