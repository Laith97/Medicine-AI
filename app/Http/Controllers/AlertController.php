<?php

namespace App\Http\Controllers;

use App\Models\Alert;
use App\Models\AlertRule;
use App\Services\AdvancedAlertService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class AlertController extends Controller
{
    protected AdvancedAlertService $alertService;

    public function __construct(AdvancedAlertService $alertService)
    {
        $this->alertService = $alertService;
    }

    /**
     * Display a listing of alerts
     */
    public function index(Request $request): View
    {
        $query = Alert::with(['alertRule', 'acknowledgedBy', 'resolvedBy'])
            ->orderedByPriority();

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('severity')) {
            $query->where('severity', $request->severity);
        }

        if ($request->filled('event_type')) {
            $query->where('event_type', $request->event_type);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('message', 'like', "%{$search}%")
                  ->orWhere('alert_id', 'like', "%{$search}%");
            });
        }

        $alerts = $query->paginate(25);

        // Get statistics for dashboard
        $stats = $this->alertService->getAlertStatistics();

        return view('alerts.index', compact('alerts', 'stats'));
    }

    /**
     * Show the form for creating a new alert rule
     */
    public function create(): View
    {
        return view('alerts.create');
    }

    /**
     * Store a newly created alert rule
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'event_type' => 'required|string',
            'model_type' => 'nullable|string',
            'conditions' => 'required|array',
            'severity_config' => 'nullable|array',
            'escalation_rules' => 'nullable|array',
            'notification_channels' => 'nullable|array',
            'priority' => 'required|integer|min:1|max:10',
            'cooldown_minutes' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        AlertRule::create($validated);

        return redirect()->route('alerts.index')
            ->with('success', 'Alert rule created successfully.');
    }

    /**
     * Display the specified alert
     */
    public function show(Alert $alert): View
    {
        $alert->load(['alertRule', 'acknowledgedBy', 'resolvedBy', 'model']);

        // Get priority recommendations
        $priorityService = app(\App\Services\AlertPriorityService::class);
        $recommendations = $priorityService->getPriorityRecommendations($alert);

        return view('alerts.show', compact('alert', 'recommendations'));
    }

    /**
     * Show the form for editing an alert rule
     */
    public function edit(AlertRule $alert): View
    {
        return view('alerts.edit', compact('alert'));
    }

    /**
     * Update the specified alert rule
     */
    public function update(Request $request, AlertRule $alert): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'event_type' => 'required|string',
            'model_type' => 'nullable|string',
            'conditions' => 'required|array',
            'severity_config' => 'nullable|array',
            'escalation_rules' => 'nullable|array',
            'notification_channels' => 'nullable|array',
            'priority' => 'required|integer|min:1|max:10',
            'cooldown_minutes' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        $alert->update($validated);

        return redirect()->route('alerts.index')
            ->with('success', 'Alert rule updated successfully.');
    }

    /**
     * Remove the specified alert rule
     */
    public function destroy(AlertRule $alert): RedirectResponse
    {
        $alert->delete();

        return redirect()->route('alerts.index')
            ->with('success', 'Alert rule deleted successfully.');
    }

    /**
     * Acknowledge an alert
     */
    public function acknowledge(Request $request, Alert $alert): JsonResponse
    {
        $validated = $request->validate([
            'notes' => 'nullable|string|max:1000',
        ]);

        $success = $this->alertService->acknowledgeAlert(
            $alert->id,
            $request->user(),
            $validated['notes'] ?? null
        );

        if ($success) {
            return response()->json([
                'success' => true,
                'message' => 'Alert acknowledged successfully.',
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to acknowledge alert.',
        ], 400);
    }

    /**
     * Resolve an alert
     */
    public function resolve(Request $request, Alert $alert): JsonResponse
    {
        $validated = $request->validate([
            'notes' => 'nullable|string|max:1000',
        ]);

        $success = $this->alertService->resolveAlert(
            $alert->id,
            $request->user(),
            $validated['notes'] ?? null
        );

        if ($success) {
            return response()->json([
                'success' => true,
                'message' => 'Alert resolved successfully.',
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to resolve alert.',
        ], 400);
    }

    /**
     * Bulk acknowledge alerts
     */
    public function bulkAcknowledge(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'alert_ids' => 'required|array',
            'alert_ids.*' => 'required|string',
            'notes' => 'nullable|string|max:1000',
        ]);

        $results = $this->alertService->bulkAcknowledgeAlerts(
            $validated['alert_ids'],
            $request->user(),
            $validated['notes'] ?? null
        );

        return response()->json([
            'success' => true,
            'results' => $results,
            'message' => "Acknowledged {$results['successful']} of " . count($validated['alert_ids']) . " alerts.",
        ]);
    }

    /**
     * Bulk resolve alerts
     */
    public function bulkResolve(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'alert_ids' => 'required|array',
            'alert_ids.*' => 'required|string',
            'notes' => 'nullable|string|max:1000',
        ]);

        $results = $this->alertService->bulkResolveAlerts(
            $validated['alert_ids'],
            $request->user(),
            $validated['notes'] ?? null
        );

        return response()->json([
            'success' => true,
            'results' => $results,
            'message' => "Resolved {$results['successful']} of " . count($validated['alert_ids']) . " alerts.",
        ]);
    }

    /**
     * Get alerts statistics
     */
    public function statistics(): JsonResponse
    {
        $stats = $this->alertService->getAlertStatistics();

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Get alerts for API
     */
    public function apiIndex(Request $request): JsonResponse
    {
        $query = Alert::with(['alertRule', 'acknowledgedBy', 'resolvedBy'])
            ->orderedByPriority();

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('severity')) {
            $query->where('severity', $request->severity);
        }

        if ($request->filled('event_type')) {
            $query->where('event_type', $request->event_type);
        }

        $alerts = $query->paginate($request->get('per_page', 25));

        return response()->json([
            'success' => true,
            'data' => $alerts,
        ]);
    }

    /**
     * Get alert rules for API
     */
    public function rules(Request $request): JsonResponse
    {
        $query = AlertRule::query();

        if ($request->filled('event_type')) {
            $query->where('event_type', $request->event_type);
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $rules = $query->paginate($request->get('per_page', 25));

        return response()->json([
            'success' => true,
            'data' => $rules,
        ]);
    }
}
