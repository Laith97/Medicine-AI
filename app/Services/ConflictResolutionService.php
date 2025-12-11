<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

class ConflictResolutionService
{
    protected DataSynchronizationService $syncService;
    protected DeviceNotificationService $notificationService;

    /**
     * Conflict resolution strategies
     */
    const STRATEGY_LAST_WRITE_WINS = 'last_write_wins';
    const STRATEGY_MERGE = 'merge';
    const STRATEGY_USER_CHOICE = 'user_choice';
    const STRATEGY_TIMESTAMP_BASED = 'timestamp_based';
    const STRATEGY_ROLE_BASED = 'role_based';

    public function __construct(
        DataSynchronizationService $syncService,
        DeviceNotificationService $notificationService
    ) {
        $this->syncService = $syncService;
        $this->notificationService = $notificationService;
    }

    /**
     * Detect and resolve conflicts automatically
     */
    public function detectAndResolveConflicts(User $user, string $deviceId, array $appointmentData, int $expectedVersion): array
    {
        $currentVersion = $this->syncService->getLatestVersionForUser($user->id);

        // Check for version conflict
        if ($expectedVersion >= $currentVersion) {
            // No conflict
            return [
                'conflict' => false,
                'resolution' => null,
                'data' => $appointmentData
            ];
        }

        // Get conflicting changes
        $conflictingChanges = $this->syncService->getAppointmentChangesSinceVersion($user->id, $expectedVersion);

        if (empty($conflictingChanges)) {
            // No actual conflicts
            return [
                'conflict' => false,
                'resolution' => null,
                'data' => $appointmentData
            ];
        }

        // Analyze conflict type and severity
        $conflictAnalysis = $this->analyzeConflict($appointmentData, $conflictingChanges);

        // Choose resolution strategy based on conflict type and user preferences
        $strategy = $this->chooseResolutionStrategy($user, $conflictAnalysis);

        Log::info('Conflict detected and analyzing resolution', [
            'user_id' => $user->id,
            'device_id' => $deviceId,
            'appointment_id' => $appointmentData['id'],
            'conflict_type' => $conflictAnalysis['type'],
            'severity' => $conflictAnalysis['severity'],
            'chosen_strategy' => $strategy
        ]);

        // Apply resolution strategy
        $resolution = $this->applyResolutionStrategy($strategy, $appointmentData, $conflictingChanges, $user, $deviceId);

        return [
            'conflict' => true,
            'conflict_analysis' => $conflictAnalysis,
            'resolution' => $resolution,
            'strategy' => $strategy,
            'requires_user_input' => $strategy === self::STRATEGY_USER_CHOICE
        ];
    }

    /**
     * Analyze the type and severity of a conflict
     */
    protected function analyzeConflict(array $incomingData, array $conflictingChanges): array
    {
        $appointmentId = $incomingData['id'];
        $incomingFields = array_keys($incomingData);
        $conflictingFields = [];

        // Collect all fields that have conflicting changes
        foreach ($conflictingChanges as $change) {
            if (($change['appointment_id'] ?? null) === $appointmentId) {
                $changeData = $change['appointment_data'] ?? [];
                $conflictingFields = array_merge($conflictingFields, array_keys($changeData));
            }
        }

        $conflictingFields = array_unique($conflictingFields);
        $overlapFields = array_intersect($incomingFields, $conflictingFields);

        // Determine conflict type
        $conflictType = $this->determineConflictType($overlapFields, $incomingData, $conflictingChanges);

        // Calculate severity
        $severity = $this->calculateConflictSeverity($overlapFields, $conflictType);

        return [
            'type' => $conflictType,
            'severity' => $severity,
            'overlapping_fields' => $overlapFields,
            'incoming_fields' => $incomingFields,
            'conflicting_fields' => $conflictingFields,
            'change_count' => count($conflictingChanges)
        ];
    }

    /**
     * Determine the type of conflict
     */
    protected function determineConflictType(array $overlapFields, array $incomingData, array $conflictingChanges): string
    {
        if (empty($overlapFields)) {
            return 'none';
        }

        // Check for status conflicts (most critical)
        if (in_array('status', $overlapFields)) {
            return 'status_conflict';
        }

        // Check for time conflicts
        if (in_array('appointment_date', $overlapFields) || in_array('duration', $overlapFields)) {
            return 'schedule_conflict';
        }

        // Check for data conflicts
        $dataFields = ['reason', 'notes', 'patient_name', 'doctor_id'];
        if (!empty(array_intersect($overlapFields, $dataFields))) {
            return 'data_conflict';
        }

        // Check for concurrent modifications
        if (count($conflictingChanges) > 1) {
            return 'concurrent_modification';
        }

        return 'field_conflict';
    }

    /**
     * Calculate conflict severity (1-10 scale)
     */
    protected function calculateConflictSeverity(array $overlapFields, string $conflictType): int
    {
        $baseSeverity = match($conflictType) {
            'status_conflict' => 9,
            'schedule_conflict' => 8,
            'concurrent_modification' => 7,
            'data_conflict' => 5,
            'field_conflict' => 3,
            default => 1
        };

        // Increase severity based on number of overlapping fields
        $fieldMultiplier = min(count($overlapFields) * 0.5, 2.0);

        return min((int)($baseSeverity * $fieldMultiplier), 10);
    }

    /**
     * Choose resolution strategy based on conflict analysis and user preferences
     */
    protected function chooseResolutionStrategy(User $user, array $conflictAnalysis): string
    {
        $severity = $conflictAnalysis['severity'];
        $conflictType = $conflictAnalysis['type'];

        // High severity conflicts require user input
        if ($severity >= 8) {
            return self::STRATEGY_USER_CHOICE;
        }

        // Role-based resolution for medium severity conflicts
        if ($severity >= 6 && in_array($user->role, ['admin', 'hospital_admin'])) {
            return self::STRATEGY_ROLE_BASED;
        }

        // Automatic resolution for low severity conflicts
        if ($severity <= 4) {
            return self::STRATEGY_LAST_WRITE_WINS;
        }

        // Default to timestamp-based for medium conflicts
        return self::STRATEGY_TIMESTAMP_BASED;
    }

    /**
     * Apply the chosen resolution strategy
     */
    protected function applyResolutionStrategy(string $strategy, array $incomingData, array $conflictingChanges, User $user, string $deviceId): array
    {
        return match($strategy) {
            self::STRATEGY_LAST_WRITE_WINS => $this->resolveLastWriteWins($incomingData, $conflictingChanges),
            self::STRATEGY_MERGE => $this->resolveMerge($incomingData, $conflictingChanges),
            self::STRATEGY_TIMESTAMP_BASED => $this->resolveTimestampBased($incomingData, $conflictingChanges),
            self::STRATEGY_ROLE_BASED => $this->resolveRoleBased($incomingData, $conflictingChanges, $user),
            self::STRATEGY_USER_CHOICE => $this->prepareUserChoiceResolution($incomingData, $conflictingChanges, $user, $deviceId),
            default => $this->resolveLastWriteWins($incomingData, $conflictingChanges)
        };
    }

    /**
     * Last Write Wins strategy - use the most recent change
     */
    protected function resolveLastWriteWins(array $incomingData, array $conflictingChanges): array
    {
        $latestChange = collect($conflictingChanges)->sortByDesc('timestamp')->first();

        if (!$latestChange) {
            return [
                'strategy' => self::STRATEGY_LAST_WRITE_WINS,
                'resolved_data' => $incomingData,
                'explanation' => 'No conflicting changes found, using incoming data'
            ];
        }

        // Compare timestamps
        $incomingTime = now(); // Assume incoming data is current
        $latestTime = $latestChange['timestamp'] ?? now()->subDay();

        if ($incomingTime->greaterThan($latestTime)) {
            return [
                'strategy' => self::STRATEGY_LAST_WRITE_WINS,
                'resolved_data' => $incomingData,
                'explanation' => 'Incoming data is more recent',
                'winning_change' => 'incoming'
            ];
        } else {
            return [
                'strategy' => self::STRATEGY_LAST_WRITE_WINS,
                'resolved_data' => $latestChange['appointment_data'] ?? $incomingData,
                'explanation' => 'Existing change is more recent',
                'winning_change' => 'existing'
            ];
        }
    }

    /**
     * Merge strategy - combine non-conflicting fields
     */
    protected function resolveMerge(array $incomingData, array $conflictingChanges): array
    {
        $resolvedData = $incomingData;

        foreach ($conflictingChanges as $change) {
            $changeData = $change['appointment_data'] ?? [];

            foreach ($changeData as $field => $value) {
                // Only merge if field doesn't exist in incoming data or is empty
                if (!isset($resolvedData[$field]) || empty($resolvedData[$field])) {
                    $resolvedData[$field] = $value;
                }
            }
        }

        return [
            'strategy' => self::STRATEGY_MERGE,
            'resolved_data' => $resolvedData,
            'explanation' => 'Merged non-conflicting fields from all changes'
        ];
    }

    /**
     * Timestamp-based resolution
     */
    protected function resolveTimestampBased(array $incomingData, array $conflictingChanges): array
    {
        $allChanges = $conflictingChanges;
        $allChanges[] = [
            'appointment_data' => $incomingData,
            'timestamp' => now(),
            'source' => 'incoming'
        ];

        $sortedChanges = collect($allChanges)->sortByDesc('timestamp');
        $latestChange = $sortedChanges->first();

        return [
            'strategy' => self::STRATEGY_TIMESTAMP_BASED,
            'resolved_data' => $latestChange['appointment_data'],
            'explanation' => 'Selected change with latest timestamp',
            'winning_timestamp' => $latestChange['timestamp'],
            'source' => $latestChange['source'] ?? 'existing'
        ];
    }

    /**
     * Role-based resolution - prefer changes from higher role users
     */
    protected function resolveRoleBased(array $incomingData, array $conflictingChanges, User $user): array
    {
        $roleHierarchy = [
            'patient' => 1,
            'doctor' => 2,
            'admin' => 3,
            'hospital_admin' => 4
        ];

        $userRoleLevel = $roleHierarchy[$user->role] ?? 1;

        // Check if any conflicting change comes from higher role
        $higherRoleChanges = collect($conflictingChanges)->filter(function ($change) use ($userRoleLevel, $roleHierarchy) {
            $changeUser = User::find($change['user_id'] ?? null);
            if (!$changeUser) return false;

            $changeRoleLevel = $roleHierarchy[$changeUser->role] ?? 1;
            return $changeRoleLevel > $userRoleLevel;
        });

        if ($higherRoleChanges->isNotEmpty()) {
            $highestRoleChange = $higherRoleChanges->sortByDesc(function ($change) use ($roleHierarchy) {
                $changeUser = User::find($change['user_id']);
                return $roleHierarchy[$changeUser->role] ?? 1;
            })->first();

            return [
                'strategy' => self::STRATEGY_ROLE_BASED,
                'resolved_data' => $highestRoleChange['appointment_data'],
                'explanation' => 'Selected change from higher role user',
                'winning_role' => User::find($highestRoleChange['user_id'])->role
            ];
        }

        // No higher role changes, use incoming data
        return [
            'strategy' => self::STRATEGY_ROLE_BASED,
            'resolved_data' => $incomingData,
            'explanation' => 'No higher role conflicts, using incoming data'
        ];
    }

    /**
     * Prepare user choice resolution - notify user of conflict
     */
    protected function prepareUserChoiceResolution(array $incomingData, array $conflictingChanges, User $user, string $deviceId): array
    {
        // Send conflict notification to user
        $conflictData = [
            'appointment_id' => $incomingData['id'],
            'incoming_data' => $incomingData,
            'conflicting_changes' => $conflictingChanges,
            'options' => [
                'accept_incoming' => 'Use the incoming changes',
                'accept_existing' => 'Keep the existing changes',
                'merge' => 'Merge the changes where possible',
                'manual' => 'Review manually'
            ]
        ];

        $this->notificationService->sendSyncConflictAlert($user, $deviceId, $conflictData);

        return [
            'strategy' => self::STRATEGY_USER_CHOICE,
            'resolved_data' => null, // Wait for user input
            'explanation' => 'User input required to resolve conflict',
            'conflict_data' => $conflictData,
            'notification_sent' => true
        ];
    }

    /**
     * Resolve conflict with user choice
     */
    public function resolveWithUserChoice(User $user, string $deviceId, int $appointmentId, string $choice, array $customData = []): array
    {
        $appointment = Appointment::findOrFail($appointmentId);

        $resolvedData = match($choice) {
            'accept_incoming' => $customData['incoming_data'] ?? [],
            'accept_existing' => $appointment->toArray(),
            'merge' => $this->mergeUserChoiceData($customData),
            'manual' => $customData['manual_data'] ?? [],
            default => $appointment->toArray()
        };

        return $this->syncService->resolveConflict($user, $deviceId, $appointmentId, $resolvedData, 'user_choice_' . $choice);
    }

    /**
     * Merge data based on user choice
     */
    protected function mergeUserChoiceData(array $customData): array
    {
        $incoming = $customData['incoming_data'] ?? [];
        $existing = $customData['existing_data'] ?? [];
        $manual = $customData['manual_data'] ?? [];

        // Start with existing data
        $merged = $existing;

        // Apply incoming data where specified
        if (isset($customData['merge_preferences'])) {
            foreach ($customData['merge_preferences'] as $field => $preference) {
                if ($preference === 'incoming' && isset($incoming[$field])) {
                    $merged[$field] = $incoming[$field];
                }
            }
        }

        // Apply manual overrides
        foreach ($manual as $field => $value) {
            $merged[$field] = $value;
        }

        return $merged;
    }

    /**
     * Get conflict resolution statistics
     */
    public function getConflictResolutionStats(int $userId): array
    {
        // This would track resolution statistics in a database/cache
        // For now, return mock data
        return [
            'total_conflicts' => 0,
            'auto_resolved' => 0,
            'user_resolved' => 0,
            'resolution_methods' => [
                self::STRATEGY_LAST_WRITE_WINS => 0,
                self::STRATEGY_MERGE => 0,
                self::STRATEGY_TIMESTAMP_BASED => 0,
                self::STRATEGY_ROLE_BASED => 0,
                self::STRATEGY_USER_CHOICE => 0
            ]
        ];
    }

    /**
     * Batch resolve conflicts for multiple appointments
     */
    public function batchResolveConflicts(User $user, string $deviceId, array $conflicts): array
    {
        $results = [];

        foreach ($conflicts as $conflict) {
            try {
                $result = $this->detectAndResolveConflicts(
                    $user,
                    $deviceId,
                    $conflict['appointment_data'],
                    $conflict['expected_version']
                );

                $results[] = [
                    'appointment_id' => $conflict['appointment_data']['id'],
                    'success' => true,
                    'result' => $result
                ];
            } catch (\Exception $e) {
                $results[] = [
                    'appointment_id' => $conflict['appointment_data']['id'],
                    'success' => false,
                    'error' => $e->getMessage()
                ];
            }
        }

        return $results;
    }
}
