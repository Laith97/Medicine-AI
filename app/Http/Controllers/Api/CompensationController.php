<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CompensationPlan;
use App\Models\ProviderCompensation;
use App\Models\ProviderBonus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CompensationController extends Controller
{
    /**
     * List compensation plans for the authenticated doctor.
     */
    public function indexPlans(): JsonResponse
    {
        $plans = CompensationPlan::where('doctor_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'plans' => $plans,
        ]);
    }

    /**
     * Create a new compensation plan.
     */
    public function storePlan(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'plan_type' => 'required|in:salary,hourly,commission,hybrid',
            'base_salary' => 'nullable|numeric|min:0',
            'base_hourly_rate' => 'nullable|numeric|min:0',
            'commission_percentage' => 'nullable|numeric|min:0|max:100',
            'bonus_threshold' => 'nullable|numeric|min:0',
            'bonus_percentage' => 'nullable|numeric|min:0|max:100',
            'cpt_commission_rates' => 'nullable|array',
            'effective_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:effective_date',
        ]);

        $validated['doctor_id'] = Auth::id();
        $validated['is_active'] = true;

        $plan = CompensationPlan::create($validated);

        return response()->json([
            'plan' => $plan,
            'message' => 'Compensation plan created successfully',
        ], 201);
    }

    /**
     * Update a compensation plan.
     */
    public function updatePlan(Request $request, int $id): JsonResponse
    {
        $plan = CompensationPlan::where('doctor_id', Auth::id())
            ->findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'plan_type' => 'sometimes|in:salary,hourly,commission,hybrid',
            'base_salary' => 'nullable|numeric|min:0',
            'base_hourly_rate' => 'nullable|numeric|min:0',
            'commission_percentage' => 'nullable|numeric|min:0|max:100',
            'bonus_threshold' => 'nullable|numeric|min:0',
            'bonus_percentage' => 'nullable|numeric|min:0|max:100',
            'cpt_commission_rates' => 'nullable|array',
            'is_active' => 'sometimes|boolean',
            'effective_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:effective_date',
        ]);

        $plan->update($validated);

        return response()->json([
            'plan' => $plan->fresh(),
            'message' => 'Compensation plan updated successfully',
        ]);
    }

    /**
     * Delete a compensation plan.
     */
    public function destroyPlan(int $id): JsonResponse
    {
        $plan = CompensationPlan::where('doctor_id', Auth::id())
            ->findOrFail($id);

        $plan->delete();

        return response()->json([
            'message' => 'Compensation plan deleted successfully',
        ]);
    }

    /**
     * List compensations for the authenticated doctor.
     */
    public function indexCompensations(Request $request): JsonResponse
    {
        $query = ProviderCompensation::where('doctor_id', Auth::id())
            ->with(['compensationPlan', 'appointment', 'claim']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('pay_period_start') && $request->has('pay_period_end')) {
            $query->forPayPeriod($request->pay_period_start, $request->pay_period_end);
        }

        $compensations = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json($compensations);
    }

    /**
     * Record a new compensation.
     */
    public function storeCompensation(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'compensation_plan_id' => 'nullable|exists:compensation_plans,id',
            'appointment_id' => 'nullable|exists:appointments,id',
            'claim_id' => 'nullable|exists:claims,id',
            'compensation_type' => 'required|in:salary,hourly,commission,bonus,adjustment',
            'amount' => 'required|numeric',
            'hours_worked' => 'nullable|numeric|min:0',
            'base_amount' => 'nullable|numeric|min:0',
            'commission_rate' => 'nullable|numeric|min:0|max:1',
            'description' => 'nullable|string',
            'pay_period_start' => 'required|date',
            'pay_period_end' => 'required|date|after_or_equal:pay_period_start',
        ]);

        $validated['doctor_id'] = Auth::id();
        $validated['status'] = 'pending';

        $compensation = ProviderCompensation::create($validated);

        return response()->json([
            'compensation' => $compensation->load(['compensationPlan', 'appointment', 'claim']),
            'message' => 'Compensation recorded successfully',
        ], 201);
    }

    /**
     * Approve a compensation.
     */
    public function approveCompensation(int $id): JsonResponse
    {
        $compensation = ProviderCompensation::where('doctor_id', Auth::id())
            ->findOrFail($id);

        $compensation->approve();

        return response()->json([
            'compensation' => $compensation->fresh(),
            'message' => 'Compensation approved successfully',
        ]);
    }

    /**
     * Mark compensation as paid.
     */
    public function markPaidCompensation(Request $request, int $id): JsonResponse
    {
        $compensation = ProviderCompensation::where('doctor_id', Auth::id())
            ->findOrFail($id);

        $validated = $request->validate([
            'payroll_reference' => 'nullable|string|max:255',
        ]);

        $compensation->markAsPaid($validated['payroll_reference'] ?? null);

        return response()->json([
            'compensation' => $compensation->fresh(),
            'message' => 'Compensation marked as paid',
        ]);
    }

    /**
     * List bonuses for the authenticated doctor.
     */
    public function indexBonuses(Request $request): JsonResponse
    {
        $query = ProviderBonus::where('doctor_id', Auth::id());

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('bonus_type')) {
            $query->ofType($request->bonus_type);
        }

        $bonuses = $query->orderBy('earned_date', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json($bonuses);
    }

    /**
     * Create a new bonus.
     */
    public function storeBonus(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'compensation_plan_id' => 'nullable|exists:compensation_plans,id',
            'bonus_type' => 'required|string',
            'amount' => 'required|numeric|min:0',
            'reason' => 'nullable|string',
            'earned_date' => 'required|date',
        ]);

        $validated['doctor_id'] = Auth::id();
        $validated['status'] = 'pending';

        $bonus = ProviderBonus::create($validated);

        return response()->json([
            'bonus' => $bonus->load('compensationPlan'),
            'message' => 'Bonus created successfully',
        ], 201);
    }

    /**
     * Approve a bonus.
     */
    public function approveBonus(int $id): JsonResponse
    {
        $bonus = ProviderBonus::where('doctor_id', Auth::id())
            ->findOrFail($id);

        $bonus->approve();

        return response()->json([
            'bonus' => $bonus->fresh(),
            'message' => 'Bonus approved successfully',
        ]);
    }

    /**
     * Get compensation summary for a pay period.
     */
    public function getSummary(Request $request): JsonResponse
    {
        $request->validate([
            'pay_period_start' => 'required|date',
            'pay_period_end' => 'required|date|after_or_equal:pay_period_start',
        ]);

        $compensations = ProviderCompensation::where('doctor_id', Auth::id())
            ->forPayPeriod($request->pay_period_start, $request->pay_period_end)
            ->get();

        $bonuses = ProviderBonus::where('doctor_id', Auth::id())
            ->whereBetween('earned_date', [$request->pay_period_start, $request->pay_period_end])
            ->where('status', '!=', 'cancelled')
            ->get();

        $totalCompensation = $compensations->sum('amount');
        $totalBonus = $bonuses->where('status', 'paid')->sum('amount');
        $pendingBonus = $bonuses->where('status', 'pending')->sum('amount');

        return response()->json([
            'pay_period' => [
                'start' => $request->pay_period_start,
                'end' => $request->pay_period_end,
            ],
            'compensations' => [
                'total' => $totalCompensation,
                'count' => $compensations->count(),
                'by_type' => $compensations->groupBy('compensation_type')
                    ->map(fn($g) => $g->sum('amount')),
            ],
            'bonuses' => [
                'total_paid' => $totalBonus,
                'total_pending' => $pendingBonus,
                'count' => $bonuses->count(),
            ],
            'grand_total' => $totalCompensation + $totalBonus,
        ]);
    }
}
