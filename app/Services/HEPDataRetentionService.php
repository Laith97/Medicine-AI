<?php

namespace App\Services;

use App\Models\HepAssignment;
use App\Models\HepProgress;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class HEPDataRetentionService
{
    /**
     * HIPAA data retention periods (in years)
     */
    const RETENTION_PERIODS = [
        'active_patient' => 7,      // 7 years for active patients
        'inactive_patient' => 7,    // 7 years after last activity
        'completed_program' => 7,   // 7 years after program completion
        'audit_logs' => 7,          // 7 years for audit logs
        'safety_events' => 10,      // 10 years for safety-related events
    ];

    /**
     * Process data retention for HEP records
     */
    public function processDataRetention(): array
    {
        $results = [
            'processed_assignments' => 0,
            'processed_progress' => 0,
            'processed_audit_logs' => 0,
            'archived_records' => 0,
            'deleted_records' => 0,
            'errors' => []
        ];

        try {
            // Process completed HEP assignments
            $results['processed_assignments'] = $this->processCompletedAssignments();

            // Process old progress records
            $results['processed_progress'] = $this->processOldProgressRecords();

            // Process audit logs
            $results['processed_audit_logs'] = $this->processAuditLogs();

            // Archive old records before deletion
            $results['archived_records'] = $this->archiveOldRecords();

            Log::info('HEP data retention processing completed', $results);

        } catch (\Exception $e) {
            $results['errors'][] = $e->getMessage();
            Log::error('HEP data retention processing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }

        return $results;
    }

    /**
     * Process completed HEP assignments for retention
     */
    private function processCompletedAssignments(): int
    {
        $cutoffDate = Carbon::now()->subYears(self::RETENTION_PERIODS['completed_program']);

        $oldAssignments = HepAssignment::where('completion_status', 'completed')
            ->where('updated_at', '<', $cutoffDate)
            ->get();

        foreach ($oldAssignments as $assignment) {
            try {
                // Archive the assignment data
                $this->archiveAssignment($assignment);

                // Mark for deletion (don't delete immediately for safety)
                $assignment->update([
                    'retention_status' => 'archived',
                    'retention_date' => now(),
                ]);

                // Log the retention action
                AuditLog::log('data_retention', $assignment->patient_id, null, null, [
                    'action_type' => 'hep_data_retention',
                    'record_type' => 'hep_assignment',
                    'record_id' => $assignment->id,
                    'retention_reason' => 'completed_program_retention_period',
                    'cutoff_date' => $cutoffDate->toISOString(),
                    'archived_at' => now(),
                ]);

            } catch (\Exception $e) {
                Log::error('Failed to process assignment retention', [
                    'assignment_id' => $assignment->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $oldAssignments->count();
    }

    /**
     * Process old progress records
     */
    private function processOldProgressRecords(): int
    {
        $cutoffDate = Carbon::now()->subYears(self::RETENTION_PERIODS['inactive_patient']);

        // Find progress records from assignments that haven't been updated recently
        $oldProgress = HepProgress::whereHas('hepAssignment', function($query) use ($cutoffDate) {
            $query->where('updated_at', '<', $cutoffDate);
        })
        ->where('created_at', '<', $cutoffDate)
        ->get();

        foreach ($oldProgress as $progress) {
            try {
                // Archive the progress data
                $this->archiveProgress($progress);

                // Mark for deletion
                $progress->update([
                    'retention_status' => 'archived',
                    'retention_date' => now(),
                ]);

                // Log the retention action
                AuditLog::log('data_retention', $progress->hepAssignment->patient_id, null, null, [
                    'action_type' => 'hep_data_retention',
                    'record_type' => 'hep_progress',
                    'record_id' => $progress->id,
                    'assignment_id' => $progress->hep_assignment_id,
                    'retention_reason' => 'inactive_patient_retention_period',
                    'cutoff_date' => $cutoffDate->toISOString(),
                    'archived_at' => now(),
                ]);

            } catch (\Exception $e) {
                Log::error('Failed to process progress retention', [
                    'progress_id' => $progress->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $oldProgress->count();
    }

    /**
     * Process old audit logs
     */
    private function processAuditLogs(): int
    {
        $cutoffDate = Carbon::now()->subYears(self::RETENTION_PERIODS['audit_logs']);

        $oldAuditLogs = AuditLog::where('action_type', 'hep_safety')
            ->where('created_at', '<', $cutoffDate)
            ->get();

        foreach ($oldAuditLogs as $auditLog) {
            try {
                // For safety events, keep longer
                if (isset($auditLog->metadata['event_type']) &&
                    in_array($auditLog->metadata['event_type'], ['emergency_contact', 'program_paused'])) {
                    $safetyCutoff = Carbon::now()->subYears(self::RETENTION_PERIODS['safety_events']);
                    if ($auditLog->created_at >= $safetyCutoff) {
                        continue; // Keep safety events longer
                    }
                }

                // Archive the audit log
                $this->archiveAuditLog($auditLog);

                // Mark for deletion
                $auditLog->update([
                    'retention_status' => 'archived',
                    'retention_date' => now(),
                ]);

            } catch (\Exception $e) {
                Log::error('Failed to process audit log retention', [
                    'audit_log_id' => $auditLog->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $oldAuditLogs->count();
    }

    /**
     * Archive old records to secure storage
     */
    private function archiveOldRecords(): int
    {
        $archivedCount = 0;

        // Archive assignments marked for retention
        $assignmentsToArchive = HepAssignment::where('retention_status', 'archived')
            ->where('retention_date', '<', now()->subDays(30)) // Wait 30 days before final deletion
            ->get();

        foreach ($assignmentsToArchive as $assignment) {
            DB::transaction(function() use ($assignment) {
                // Move to archive table (if exists) or mark as permanently archived
                $assignment->update(['retention_status' => 'permanently_archived']);
            });
            $archivedCount++;
        }

        // Archive progress records
        $progressToArchive = HepProgress::where('retention_status', 'archived')
            ->where('retention_date', '<', now()->subDays(30))
            ->get();

        foreach ($progressToArchive as $progress) {
            $progress->update(['retention_status' => 'permanently_archived']);
            $archivedCount++;
        }

        return $archivedCount;
    }

    /**
     * Archive assignment data
     */
    private function archiveAssignment(HepAssignment $assignment): void
    {
        $archiveData = [
            'assignment_id' => $assignment->id,
            'patient_id' => $assignment->patient_id,
            'program_id' => $assignment->hep_program_id,
            'assigned_by' => $assignment->assigned_by,
            'assigned_at' => $assignment->assigned_at,
            'completion_status' => $assignment->completion_status,
            'archived_at' => now(),
            'retention_reason' => 'hipaa_compliance',
            'data' => $assignment->toJson(),
        ];

        // Store in secure archive (this could be a separate table or encrypted file)
        // For now, we'll log it
        Log::info('HEP Assignment archived for retention', $archiveData);
    }

    /**
     * Archive progress data
     */
    private function archiveProgress(HepProgress $progress): void
    {
        $archiveData = [
            'progress_id' => $progress->id,
            'assignment_id' => $progress->hep_assignment_id,
            'exercise_id' => $progress->hep_exercise_id,
            'patient_id' => $progress->hepAssignment->patient_id,
            'date' => $progress->date,
            'pain_level' => $progress->pain_level,
            'archived_at' => now(),
            'retention_reason' => 'hipaa_compliance',
            'data' => $progress->toJson(),
        ];

        // Store in secure archive
        Log::info('HEP Progress archived for retention', $archiveData);
    }

    /**
     * Archive audit log data
     */
    private function archiveAuditLog(AuditLog $auditLog): void
    {
        $archiveData = [
            'audit_log_id' => $auditLog->id,
            'action_type' => $auditLog->action_type,
            'user_id' => $auditLog->user_id,
            'patient_id' => $auditLog->metadata['patient_id'] ?? null,
            'archived_at' => now(),
            'retention_reason' => 'hipaa_compliance',
            'data' => $auditLog->toJson(),
        ];

        // Store in secure archive
        Log::info('HEP Audit Log archived for retention', $archiveData);
    }

    /**
     * Check if a record should be retained based on HIPAA rules
     */
    public function shouldRetainRecord($record, string $recordType): bool
    {
        $now = Carbon::now();

        switch ($recordType) {
            case 'hep_assignment':
                if ($record->completion_status === 'completed') {
                    $cutoff = $now->subYears(self::RETENTION_PERIODS['completed_program']);
                    return $record->updated_at >= $cutoff;
                }
                // Active assignments are retained
                return true;

            case 'hep_progress':
                $cutoff = $now->subYears(self::RETENTION_PERIODS['inactive_patient']);
                return $record->created_at >= $cutoff;

            case 'audit_log':
                if ($record->action_type === 'hep_safety') {
                    $cutoff = $now->subYears(self::RETENTION_PERIODS['safety_events']);
                } else {
                    $cutoff = $now->subYears(self::RETENTION_PERIODS['audit_logs']);
                }
                return $record->created_at >= $cutoff;

            default:
                return true;
        }
    }

    /**
     * Get retention statistics
     */
    public function getRetentionStatistics(): array
    {
        return [
            'retention_periods' => self::RETENTION_PERIODS,
            'assignments_pending_deletion' => HepAssignment::where('retention_status', 'archived')->count(),
            'progress_pending_deletion' => HepProgress::where('retention_status', 'archived')->count(),
            'audit_logs_pending_deletion' => AuditLog::where('retention_status', 'archived')->count(),
            'last_retention_run' => $this->getLastRetentionRun(),
        ];
    }

    /**
     * Get the last retention processing run
     */
    private function getLastRetentionRun(): ?Carbon
    {
        $lastLog = AuditLog::where('action_type', 'hep_data_retention')
            ->where('action', 'retention_processing_completed')
            ->latest()
            ->first();

        return $lastLog ? $lastLog->created_at : null;
    }
}
