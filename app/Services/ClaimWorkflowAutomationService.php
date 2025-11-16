<?php

namespace App\Services;

use App\Models\Claim;
use App\Models\ProviderContract;
use App\Models\ClearinghouseAccount;
use App\Models\WorkflowTask;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class ClaimWorkflowAutomationService
{
    protected ClaimSubmissionService $claimSubmissionService;

    public function __construct(ClaimSubmissionService $claimSubmissionService)
    {
        $this->claimSubmissionService = $claimSubmissionService;
    }

    /**
     * Route claims to appropriate clearinghouse based on provider contracts
     */
    public function routeClaims(Collection $claims, int $hospitalId): array
    {
        $routedClaims = [
            'routed' => [],
            'unrouted' => [],
            'errors' => []
        ];

        foreach ($claims as $claim) {
            try {
                $routingResult = $this->routeSingleClaim($claim, $hospitalId);
                if ($routingResult) {
                    $routedClaims['routed'][] = $routingResult;
                } else {
                    $routedClaims['unrouted'][] = $claim;
                }
            } catch (\Exception $e) {
                Log::error('Failed to route claim', [
                    'claim_id' => $claim->id,
                    'error' => $e->getMessage()
                ]);
                $routedClaims['errors'][] = [
                    'claim' => $claim,
                    'error' => $e->getMessage()
                ];
            }
        }

        return $routedClaims;
    }

    /**
     * Route a single claim based on provider contracts
     */
    protected function routeSingleClaim(Claim $claim, int $hospitalId): ?array
    {
        // Find active contracts for this hospital and claim's payer
        $contracts = ProviderContract::active()
            ->where('hospital_id', $hospitalId)
            ->whereHas('insuranceProvider', function ($query) use ($claim) {
                $query->where('name', $claim->payer);
            })
            ->get();

        if ($contracts->isEmpty()) {
            // No specific contract found, try to find default routing
            return $this->getDefaultRouting($claim, $hospitalId);
        }

        // Evaluate contracts based on routing rules
        foreach ($contracts as $contract) {
            if ($this->matchesContractRules($claim, $contract)) {
                return [
                    'claim' => $claim,
                    'contract' => $contract,
                    'clearinghouse_provider' => $contract->clearinghouse_provider,
                    'auto_submit' => $contract->auto_submit
                ];
            }
        }

        // No matching contract found
        return null;
    }

    /**
     * Check if claim matches contract routing rules
     */
    protected function matchesContractRules(Claim $claim, ProviderContract $contract): bool
    {
        $rules = $contract->getRoutingRules();

        if (empty($rules)) {
            return true; // No rules means it matches
        }

        foreach ($rules as $rule) {
            if (!$this->evaluateRoutingRule($claim, $rule)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Evaluate a single routing rule
     */
    protected function evaluateRoutingRule(Claim $claim, array $rule): bool
    {
        $field = $rule['field'] ?? null;
        $operator = $rule['operator'] ?? null;
        $value = $rule['value'] ?? null;

        if (!$field || !$operator) {
            return true;
        }

        $claimValue = $this->getClaimFieldValue($claim, $field);

        switch ($operator) {
            case 'equals':
                return $claimValue == $value;
            case 'not_equals':
                return $claimValue != $value;
            case 'greater_than':
                return $claimValue > $value;
            case 'less_than':
                return $claimValue < $value;
            case 'contains':
                return str_contains((string)$claimValue, (string)$value);
            case 'in':
                return in_array($claimValue, (array)$value);
            case 'between':
                return $claimValue >= $value[0] && $claimValue <= $value[1];
            default:
                return true;
        }
    }

    /**
     * Get default routing when no contract matches
     */
    protected function getDefaultRouting(Claim $claim, int $hospitalId): ?array
    {
        // Find any active clearinghouse account for this hospital
        $account = ClearinghouseAccount::where('hospital_id', $hospitalId)
            ->where('is_active', true)
            ->first();

        if ($account) {
            return [
                'claim' => $claim,
                'contract' => null,
                'clearinghouse_provider' => $account->provider,
                'auto_submit' => false // Manual review needed
            ];
        }

        return null;
    }

    /**
     * Get claim field value for routing
     */
    protected function getClaimFieldValue(Claim $claim, string $field)
    {
        return match ($field) {
            'total_amount' => $claim->total_amount,
            'expected_amount' => $claim->expected_amount,
            'payer' => $claim->payer,
            'provider_name' => $claim->provider_name,
            'patient_gender' => $claim->patient_gender,
            'service_date' => $claim->service_date?->format('Y-m-d'),
            'icd10_codes' => $claim->icd10_codes,
            'cpt_codes' => $claim->cpt_codes,
            default => $claim->$field ?? null,
        };
    }

    /**
     * Create workflow tasks for routed claims
     */
    public function createRoutingTasks(array $routedClaims): void
    {
        foreach ($routedClaims as $routing) {
            $claim = $routing['claim'];
            $autoSubmit = $routing['auto_submit'] ?? false;

            if ($autoSubmit) {
                // Create task for auto-submission
                WorkflowTask::create([
                    'task_type' => 'auto_claim_submission',
                    'taskable_type' => Claim::class,
                    'taskable_id' => $claim->id,
                    'title' => "Auto-submit claim {$claim->claim_id}",
                    'description' => "Automatically submit claim to {$routing['clearinghouse_provider']}",
                    'task_data' => [
                        'clearinghouse_provider' => $routing['clearinghouse_provider'],
                        'contract_id' => $routing['contract']->id ?? null,
                    ],
                    'priority' => 'medium',
                    'due_date' => now()->addHours(2), // Submit within 2 hours
                ]);
            } else {
                // Create task for manual review
                WorkflowTask::create([
                    'task_type' => 'manual_claim_review',
                    'taskable_type' => Claim::class,
                    'taskable_id' => $claim->id,
                    'title' => "Review claim routing for {$claim->claim_id}",
                    'description' => "Review and manually submit claim to {$routing['clearinghouse_provider']}",
                    'task_data' => [
                        'clearinghouse_provider' => $routing['clearinghouse_provider'],
                        'contract_id' => $routing['contract']->id ?? null,
                        'reason' => 'No auto-submit contract found',
                    ],
                    'priority' => 'high',
                    'due_date' => now()->addHours(24), // Review within 24 hours
                ]);
            }
        }
    }

    /**
     * Process auto-submission tasks
     */
    public function processAutoSubmissionTasks(): void
    {
        $tasks = WorkflowTask::byType('auto_claim_submission')
            ->pending()
            ->where('due_date', '<=', now())
            ->get();

        foreach ($tasks as $task) {
            try {
                $claim = $task->taskable;
                $clearinghouseProvider = $task->task_data['clearinghouse_provider'];

                // Find appropriate clearinghouse account
                $account = ClearinghouseAccount::where('hospital_id', $claim->hospital_id)
                    ->where('provider', $clearinghouseProvider)
                    ->where('is_active', true)
                    ->first();

                if ($account) {
                    // Submit the claim
                    $submission = $this->claimSubmissionService->submitClaims(
                        collect([$claim]),
                        $account,
                        '837P' // Default to professional claims
                    );

                    $task->markCompleted();

                    Log::info('Auto-submitted claim', [
                        'claim_id' => $claim->id,
                        'submission_id' => $submission->id,
                        'clearinghouse' => $clearinghouseProvider
                    ]);
                } else {
                    // Mark as failed - no account found
                    $task->status = 'cancelled';
                    $task->task_data = array_merge($task->task_data ?? [], [
                        'error' => 'No active clearinghouse account found'
                    ]);
                    $task->save();

                    Log::warning('Failed to auto-submit claim - no account', [
                        'claim_id' => $claim->id,
                        'clearinghouse' => $clearinghouseProvider
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('Failed to process auto-submission task', [
                    'task_id' => $task->id,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }
}
