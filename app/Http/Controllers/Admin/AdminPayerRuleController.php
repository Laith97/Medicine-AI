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
            // TODO: Implement actual rule testing logic
            //       Evaluate $rule->conditions against $request->claim_data
            //       Return 'approved', 'denied', or 'modified' based on rule evaluation
            //       The mock below always approves - real implementation should check conditions
            $result = [
                'rule_applied' => true,
                'conditions_met' => true,
                'actions_taken' => $rule->actions,
                'result' => 'approved', // Currently hardcoded - real implementation needed
                'notes' => 'Rule test completed successfully'
            ];

            return response()->json([
                'success' => true,
                'result' => $result
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
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
