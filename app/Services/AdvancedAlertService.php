<?php

namespace App\Services;

use App\Models\Alert;
use App\Models\AlertRule;
use App\Models\User;
use App\Services\AlertPriorityService;
use App\Services\AlertNotificationService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class AdvancedAlertService
{
    protected AlertPriorityService $priorityService;
    protected AlertNotificationService $notificationService;

    public function __construct(
        AlertPriorityService $priorityService,
        AlertNotificationService $notificationService
    ) {
        $this->priorityService = $priorityService;
        $this->notificationService = $notificationService;
    }

    /**
     * Process compliance event and create alerts if needed
     */
    public function processComplianceEvent(
        string $eventType,
        string $modelType,
        array $eventData,
        $model = null
    ): array {
        $alertsCreated = [];
        $errors = [];

        try {
            // Get active alert rules for this event
            $rules = AlertRule::active()
                ->byEventType($eventType)
                ->byModelType($modelType)
                ->orderedByPriority()
                ->get();

            foreach ($rules as $rule) {
                try {
                    // Check if rule matches conditions
                    if (!$rule->matchesConditions($eventData)) {
                        continue;
                    }

                    // Check cooldown period
                    if ($rule->isInCooldown()) {
                        Log::info('Alert rule in cooldown period', [
                            'rule_id' => $rule->id,
                            'event_type' => $eventType,
                        ]);
                        continue;
                    }

                    // Create alert
                    $alert = $this->createAlert($rule, $eventType, $modelType, $eventData, $model);

                    if ($alert) {
                        $alertsCreated[] = $alert;

                        // Send initial notifications
                        $this->sendInitialNotifications($alert);
                    }

                } catch (\Exception $e) {
                    Log::error('Failed to process alert rule', [
                        'rule_id' => $rule->id,
                        'event_type' => $eventType,
                        'error' => $e->getMessage(),
                    ]);
                    $errors[] = [
                        'rule_id' => $rule->id,
                        'error' => $e->getMessage(),
                    ];
                }
            }

        } catch (\Exception $e) {
            Log::error('Failed to process compliance event for alerts', [
                'event_type' => $eventType,
                'model_type' => $modelType,
                'error' => $e->getMessage(),
            ]);
            $errors[] = [
                'event_type' => $eventType,
                'error' => $e->getMessage(),
            ];
        }

        return [
            'alerts_created' => $alertsCreated,
            'errors' => $errors,
            'total_rules_evaluated' => $rules->count() ?? 0,
        ];
    }

    /**
     * Create a new alert
     */
    protected function createAlert(
        AlertRule $rule,
        string $eventType,
        string $modelType,
        array $eventData,
        $model = null
    ): ?Alert {
        try {
            // Determine severity
            $severity = $rule->getSeverityLevel($eventData);

            // Create alert data
            $alertData = [
                'alert_rule_id' => $rule->id,
                'title' => $this->generateAlertTitle($rule, $eventData),
                'message' => $this->generateAlertMessage($rule, $eventData),
                'severity' => $severity,
                'event_type' => $eventType,
                'model_type' => $modelType,
                'model_id' => $model ? $model->id : null,
                'event_data' => $eventData,
                'context_data' => $this->extractContextData($model),
                'escalation_level' => 0,
            ];

            // Set initial escalation timer if configured
            $escalationRules = $rule->getEscalationRules($severity);
            if (!empty($escalationRules) && isset($escalationRules[0]['delay_minutes'])) {
                $alertData['next_escalation_at'] = now()->addMinutes($escalationRules[0]['delay_minutes']);
            }

            $alert = Alert::create($alertData);

            // Calculate and set priority score
            $priorityScore = $this->priorityService->calculatePriorityScore($alert);
            $alert->update(['priority_score' => $priorityScore]);

            Log::info('Alert created', [
                'alert_id' => $alert->id,
                'rule_id' => $rule->id,
                'severity' => $severity,
                'priority_score' => $priorityScore,
            ]);

            return $alert;

        } catch (\Exception $e) {
            Log::error('Failed to create alert', [
                'rule_id' => $rule->id,
                'event_type' => $eventType,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Generate alert title
     */
    protected function generateAlertTitle(AlertRule $rule, array $eventData): string
    {
        // Use template if available, otherwise generate from rule name
        $template = $rule->metadata['title_template'] ?? null;

        if ($template) {
            return $this->renderTemplate($template, $eventData);
        }

        return $rule->name;
    }

    /**
     * Generate alert message
     */
    protected function generateAlertMessage(AlertRule $rule, array $eventData): string
    {
        // Use template if available, otherwise use rule description
        $template = $rule->metadata['message_template'] ?? null;

        if ($template) {
            return $this->renderTemplate($template, $eventData);
        }

        return $rule->description ?: 'Alert triggered for ' . $rule->name;
    }

    /**
     * Render template with event data
     */
    protected function renderTemplate(string $template, array $data): string
    {
        // Simple template rendering - replace {{field}} with values
        foreach ($data as $key => $value) {
            $template = str_replace("{{$key}}", (string)$value, $template);
        }

        return $template;
    }

    /**
     * Extract context data from model
     */
    protected function extractContextData($model): ?array
    {
        if (!$model) {
            return null;
        }

        $context = [
            'model_class' => get_class($model),
            'model_id' => $model->id,
            'created_at' => $model->created_at?->toISOString(),
            'updated_at' => $model->updated_at?->toISOString(),
        ];

        // Add model-specific context
        if (method_exists($model, 'toAlertContext')) {
            $context = array_merge($context, $model->toAlertContext());
        }

        return $context;
    }

    /**
     * Send initial notifications for alert
     */
    protected function sendInitialNotifications(Alert $alert): void
    {
        try {
            $this->notificationService->sendAlertNotifications($alert, 'initial');
        } catch (\Exception $e) {
            Log::error('Failed to send initial alert notifications', [
                'alert_id' => $alert->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Process alert escalation
     */
    public function processEscalation(): void
    {
        $alertsToEscalate = Alert::requiringEscalation()->get();

        foreach ($alertsToEscalate as $alert) {
            try {
                $this->escalateAlert($alert);
            } catch (\Exception $e) {
                Log::error('Failed to escalate alert', [
                    'alert_id' => $alert->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Escalate a specific alert
     */
    protected function escalateAlert(Alert $alert): void
    {
        DB::transaction(function () use ($alert) {
            $alert->escalate();

            // Send escalation notifications
            $this->notificationService->sendAlertNotifications($alert, 'escalation');

            Log::info('Alert escalated', [
                'alert_id' => $alert->id,
                'new_level' => $alert->escalation_level,
            ]);
        });
    }

    /**
     * Acknowledge alert
     */
    public function acknowledgeAlert(string $alertId, User $user, ?string $notes = null): bool
    {
        try {
            $alert = Alert::findOrFail($alertId);

            if (!$alert->acknowledge($user, $notes)) {
                return false;
            }

            // Send acknowledgement notifications
            $this->notificationService->sendAlertNotifications($alert, 'acknowledged');

            Log::info('Alert acknowledged', [
                'alert_id' => $alert->id,
                'user_id' => $user->id,
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to acknowledge alert', [
                'alert_id' => $alertId,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Resolve alert
     */
    public function resolveAlert(string $alertId, User $user, ?string $notes = null): bool
    {
        try {
            $alert = Alert::findOrFail($alertId);

            if (!$alert->resolve($user, $notes)) {
                return false;
            }

            // Send resolution notifications
            $this->notificationService->sendAlertNotifications($alert, 'resolved');

            Log::info('Alert resolved', [
                'alert_id' => $alert->id,
                'user_id' => $user->id,
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to resolve alert', [
                'alert_id' => $alertId,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Get alerts for dashboard
     */
    public function getAlertsForDashboard(?string $status = null, ?string $severity = null, int $limit = 50): Collection
    {
        $query = Alert::with(['alertRule', 'acknowledgedBy', 'resolvedBy'])
            ->orderedByPriority();

        if ($status) {
            $query->where('status', $status);
        }

        if ($severity) {
            $query->where('severity', $severity);
        }

        return $query->limit($limit)->get();
    }

    /**
     * Get alert statistics
     */
    public function getAlertStatistics(): array
    {
        $stats = Alert::selectRaw('
            COUNT(*) as total,
            COUNT(CASE WHEN status = "active" THEN 1 END) as active_count,
            COUNT(CASE WHEN status = "acknowledged" THEN 1 END) as acknowledged_count,
            COUNT(CASE WHEN status = "resolved" THEN 1 END) as resolved_count,
            COUNT(CASE WHEN status = "escalated" THEN 1 END) as escalated_count,
            COUNT(CASE WHEN severity = "critical" THEN 1 END) as critical_count,
            COUNT(CASE WHEN severity = "high" THEN 1 END) as high_count,
            COUNT(CASE WHEN severity = "medium" THEN 1 END) as medium_count,
            COUNT(CASE WHEN severity = "low" THEN 1 END) as low_count,
            COUNT(CASE WHEN severity = "info" THEN 1 END) as info_count,
            AVG(priority_score) as avg_priority_score,
            COUNT(CASE WHEN next_escalation_at < NOW() AND status = "active" THEN 1 END) as overdue_count
        ')->first();

        return [
            'total' => $stats->total ?? 0,
            'by_status' => [
                'active' => $stats->active_count ?? 0,
                'acknowledged' => $stats->acknowledged_count ?? 0,
                'resolved' => $stats->resolved_count ?? 0,
                'escalated' => $stats->escalated_count ?? 0,
            ],
            'by_severity' => [
                'critical' => $stats->critical_count ?? 0,
                'high' => $stats->high_count ?? 0,
                'medium' => $stats->medium_count ?? 0,
                'low' => $stats->low_count ?? 0,
                'info' => $stats->info_count ?? 0,
            ],
            'average_priority_score' => round($stats->avg_priority_score ?? 0, 2),
            'overdue_for_escalation' => $stats->overdue_count ?? 0,
        ];
    }

    /**
     * Bulk acknowledge alerts
     */
    public function bulkAcknowledgeAlerts(array $alertIds, User $user, ?string $notes = null): array
    {
        $results = ['successful' => [], 'failed' => []];

        foreach ($alertIds as $alertId) {
            if ($this->acknowledgeAlert($alertId, $user, $notes)) {
                $results['successful'][] = $alertId;
            } else {
                $results['failed'][] = $alertId;
            }
        }

        return $results;
    }

    /**
     * Bulk resolve alerts
     */
    public function bulkResolveAlerts(array $alertIds, User $user, ?string $notes = null): array
    {
        $results = ['successful' => [], 'failed' => []];

        foreach ($alertIds as $alertId) {
            if ($this->resolveAlert($alertId, $user, $notes)) {
                $results['successful'][] = $alertId;
            } else {
                $results['failed'][] = $alertId;
            }
        }

        return $results;
    }

    /**
     * Clean up old resolved alerts
     */
    public function cleanupOldAlerts(int $daysOld = 90): int
    {
        return Alert::where('status', 'resolved')
            ->where('resolved_at', '<', now()->subDays($daysOld))
            ->delete();
    }
}
