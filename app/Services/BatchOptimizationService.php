<?php

namespace App\Services;

use App\Models\Claim;
use App\Models\BatchOptimizationRule;
use App\Models\ClearinghouseAccount;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class BatchOptimizationService
{
    /**
     * Create optimized batches based on rules
     */
    public function createOptimizedBatches(Collection $claims, int $hospitalId, string $clearinghouseProvider): array
    {
        $rule = BatchOptimizationRule::active()
            ->where('hospital_id', $hospitalId)
            ->where('clearinghouse_provider', $clearinghouseProvider)
            ->first();

        if (!$rule) {
            // No optimization rule found, create single batch
            return $this->createSingleBatch($claims, $clearinghouseProvider);
        }

        return $this->applyOptimizationRule($claims, $rule);
    }

    /**
     * Apply optimization rule to create batches
     */
    protected function applyOptimizationRule(Collection $claims, BatchOptimizationRule $rule): array
    {
        $batches = [];
        $groupedClaims = $this->groupClaimsByRule($claims, $rule);

        foreach ($groupedClaims as $groupKey => $groupClaims) {
            $optimizedBatches = $this->optimizeGroupBatches($groupClaims, $rule);
            $batches = array_merge($batches, $optimizedBatches);
        }

        return $batches;
    }

    /**
     * Group claims based on optimization rule criteria
     */
    protected function groupClaimsByRule(Collection $claims, BatchOptimizationRule $rule): array
    {
        $groups = [];

        foreach ($claims as $claim) {
            if ($rule->shouldExcludeClaim($claim)) {
                continue; // Skip excluded claims
            }

            $groupKey = $rule->getGroupingKey($claim);

            if (!isset($groups[$groupKey])) {
                $groups[$groupKey] = collect();
            }

            $groups[$groupKey]->push($claim);
        }

        return $groups;
    }

    /**
     * Optimize batches within a group
     */
    protected function optimizeGroupBatches(Collection $claims, BatchOptimizationRule $rule): array
    {
        $batches = [];
        $claims = $this->sortClaimsByPriority($claims, $rule);

        $currentBatch = collect();
        $currentTotal = 0;

        foreach ($claims as $claim) {
            // Check if adding this claim would exceed limits
            $newSize = $currentBatch->count() + 1;
            $newTotal = $currentTotal + $claim->total_amount;

            if (!$rule->isValidBatchSize($newSize) ||
                !$rule->isValidTotalAmount($newTotal) ||
                $this->wouldCauseInefficiency($currentBatch, $claim, $rule)) {

                // Start new batch if current is not empty
                if ($currentBatch->isNotEmpty()) {
                    $batches[] = $currentBatch;
                    $currentBatch = collect();
                    $currentTotal = 0;
                }
            }

            $currentBatch->push($claim);
            $currentTotal += $claim->total_amount;

            // Check if batch is at optimal size
            if ($this->isOptimalBatchSize($currentBatch, $rule)) {
                $batches[] = $currentBatch;
                $currentBatch = collect();
                $currentTotal = 0;
            }
        }

        // Add remaining claims as final batch
        if ($currentBatch->isNotEmpty()) {
            $batches[] = $currentBatch;
        }

        return $batches;
    }

    /**
     * Sort claims by priority rules
     */
    protected function sortClaimsByPriority(Collection $claims, BatchOptimizationRule $rule): Collection
    {
        if (!$rule->priority_rules) {
            return $claims->sortBy('total_amount'); // Default sort by amount
        }

        return $claims->sort(function ($a, $b) use ($rule) {
            return $this->compareClaimPriority($a, $b, $rule->priority_rules);
        });
    }

    /**
     * Compare priority between two claims
     */
    protected function compareClaimPriority(Claim $a, Claim $b, array $priorityRules): int
    {
        foreach ($priorityRules as $rule) {
            $field = $rule['field'] ?? null;
            $direction = $rule['direction'] ?? 'asc'; // asc or desc

            if (!$field) continue;

            $valueA = $this->getClaimPriorityValue($a, $field);
            $valueB = $this->getClaimPriorityValue($b, $field);

            if ($valueA != $valueB) {
                $comparison = $direction === 'desc' ? -1 : 1;
                return ($valueA < $valueB) ? -$comparison : $comparison;
            }
        }

        return 0; // Equal priority
    }

    /**
     * Get claim value for priority comparison
     */
    protected function getClaimPriorityValue(Claim $claim, string $field)
    {
        return match ($field) {
            'total_amount' => $claim->total_amount,
            'expected_amount' => $claim->expected_amount,
            'service_date' => $claim->service_date?->timestamp ?? 0,
            'submission_date' => $claim->submission_date?->timestamp ?? 0,
            'denial_risk_probability' => $claim->denial_risk_probability ?? 0,
            default => $claim->$field ?? 0,
        };
    }

    /**
     * Check if adding claim would cause inefficiency
     */
    protected function wouldCauseInefficiency(Collection $currentBatch, Claim $newClaim, BatchOptimizationRule $rule): bool
    {
        if ($currentBatch->isEmpty()) {
            return false;
        }

        // Check for payer mixing (if rule specifies)
        if ($this->shouldSeparateByPayer($rule)) {
            $batchPayer = $currentBatch->first()->payer;
            if ($newClaim->payer !== $batchPayer) {
                return true;
            }
        }

        // Check for provider mixing
        if ($this->shouldSeparateByProvider($rule)) {
            $batchProvider = $currentBatch->first()->provider_name;
            if ($newClaim->provider_name !== $batchProvider) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if rule requires separating by payer
     */
    protected function shouldSeparateByPayer(BatchOptimizationRule $rule): bool
    {
        $criteria = $rule->grouping_criteria ?? [];
        foreach ($criteria as $criterion) {
            if (($criterion['field'] ?? null) === 'payer') {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if rule requires separating by provider
     */
    protected function shouldSeparateByProvider(BatchOptimizationRule $rule): bool
    {
        $criteria = $rule->grouping_criteria ?? [];
        foreach ($criteria as $criterion) {
            if (($criterion['field'] ?? null) === 'provider_name') {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if batch is at optimal size
     */
    protected function isOptimalBatchSize(Collection $batch, BatchOptimizationRule $rule): bool
    {
        $size = $batch->count();
        $total = $batch->sum('total_amount');

        // Consider batch optimal if at max size or close to max total
        return $size >= $rule->max_batch_size ||
               ($rule->max_total_amount && $total >= $rule->max_total_amount * 0.9);
    }

    /**
     * Create single batch when no optimization rule exists
     */
    protected function createSingleBatch(Collection $claims, string $clearinghouseProvider): array
    {
        // Group by clearinghouse account if multiple accounts exist
        $accounts = ClearinghouseAccount::where('provider', $clearinghouseProvider)
            ->where('is_active', true)
            ->get();

        if ($accounts->count() <= 1) {
            return [$claims]; // Single batch
        }

        // Distribute across accounts (simple round-robin)
        $batches = [];
        $accountIndex = 0;

        foreach ($claims as $claim) {
            $accountId = $accounts[$accountIndex]->id;

            if (!isset($batches[$accountId])) {
                $batches[$accountId] = collect();
            }

            $batches[$accountId]->push($claim);
            $accountIndex = ($accountIndex + 1) % $accounts->count();
        }

        return array_values($batches);
    }

    /**
     * Validate batch before submission
     */
    public function validateBatch(Collection $claims, BatchOptimizationRule $rule = null): array
    {
        $errors = [];
        $warnings = [];

        $totalAmount = $claims->sum('total_amount');
        $claimCount = $claims->count();

        // Check size limits
        if ($rule) {
            if (!$rule->isValidBatchSize($claimCount)) {
                $errors[] = "Batch size {$claimCount} is outside valid range ({$rule->min_batch_size}-{$rule->max_batch_size})";
            }

            if (!$rule->isValidTotalAmount($totalAmount)) {
                $errors[] = "Batch total \${$totalAmount} exceeds maximum \${$rule->max_total_amount}";
            }
        }

        // Check for duplicate claims
        $claimIds = $claims->pluck('id');
        if ($claimIds->duplicates()->isNotEmpty()) {
            $errors[] = "Batch contains duplicate claims";
        }

        // Check submission cutoff time
        if ($rule && $rule->submission_cutoff_time) {
            $cutoffTime = now()->setTimeFromTimeString($rule->submission_cutoff_time);
            if (now()->greaterThan($cutoffTime)) {
                $warnings[] = "Batch submitted after cutoff time {$rule->submission_cutoff_time}";
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
            'summary' => [
                'claim_count' => $claimCount,
                'total_amount' => $totalAmount,
                'payers' => $claims->pluck('payer')->unique()->values(),
                'providers' => $claims->pluck('provider_name')->unique()->values(),
            ]
        ];
    }
}
