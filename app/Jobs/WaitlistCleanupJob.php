<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Waitlist;
use App\Models\WaitlistEntry;
use Carbon\Carbon;

class WaitlistCleanupJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $cleanupType;
    protected $maxAge;

    /**
     * Create a new job instance.
     *
     * @param string $cleanupType Type of cleanup: 'expired_entries', 'old_waitlists', 'orphaned_data'
     * @param int $maxAge Maximum age in days for cleanup
     */
    public function __construct(string $cleanupType = 'expired_entries', int $maxAge = 30)
    {
        $this->cleanupType = $cleanupType;
        $this->maxAge = $maxAge;
        $this->queue = 'waitlist-maintenance';
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Starting waitlist cleanup job', [
            'cleanup_type' => $this->cleanupType,
            'max_age' => $this->maxAge
        ]);

        $results = match($this->cleanupType) {
            'expired_entries' => $this->cleanupExpiredEntries(),
            'old_waitlists' => $this->cleanupOldWaitlists(),
            'orphaned_data' => $this->cleanupOrphanedData(),
            'all' => $this->cleanupAll(),
            default => ['error' => 'Unknown cleanup type']
        };

        Log::info('Completed waitlist cleanup job', $results);
    }

    /**
     * Clean up expired waitlist entries
     */
    private function cleanupExpiredEntries(): array
    {
        $expiredEntries = WaitlistEntry::where('status', 'expired')
            ->where('updated_at', '<', now()->subDays($this->maxAge))
            ->get();

        $deletedCount = 0;
        $errors = [];

        foreach ($expiredEntries as $entry) {
            try {
                // Log cleanup action
                Log::info('Cleaning up expired waitlist entry', [
                    'entry_id' => $entry->id,
                    'waitlist_id' => $entry->waitlist_id,
                    'expired_at' => $entry->updated_at
                ]);

                $entry->delete();
                $deletedCount++;
            } catch (\Exception $e) {
                $errors[] = "Failed to delete entry {$entry->id}: {$e->getMessage()}";
                Log::error('Failed to cleanup expired waitlist entry', [
                    'entry_id' => $entry->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return [
            'cleanup_type' => 'expired_entries',
            'deleted_count' => $deletedCount,
            'errors' => $errors,
            'total_found' => $expiredEntries->count()
        ];
    }

    /**
     * Clean up old inactive waitlists
     */
    private function cleanupOldWaitlists(): array
    {
        $oldWaitlists = Waitlist::whereIn('status', ['cancelled', 'fulfilled'])
            ->where('updated_at', '<', now()->subDays($this->maxAge))
            ->get();

        $deletedCount = 0;
        $errors = [];

        foreach ($oldWaitlists as $waitlist) {
            try {
                // Check if all entries are also old before deleting
                $hasRecentEntries = $waitlist->entries()
                    ->where('updated_at', '>=', now()->subDays($this->maxAge))
                    ->exists();

                if (!$hasRecentEntries) {
                    Log::info('Cleaning up old waitlist', [
                        'waitlist_id' => $waitlist->id,
                        'status' => $waitlist->status,
                        'last_updated' => $waitlist->updated_at
                    ]);

                    // Delete associated entries first (cascade should handle this, but being explicit)
                    $waitlist->entries()->delete();
                    $waitlist->delete();
                    $deletedCount++;
                }
            } catch (\Exception $e) {
                $errors[] = "Failed to delete waitlist {$waitlist->id}: {$e->getMessage()}";
                Log::error('Failed to cleanup old waitlist', [
                    'waitlist_id' => $waitlist->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return [
            'cleanup_type' => 'old_waitlists',
            'deleted_count' => $deletedCount,
            'errors' => $errors,
            'total_found' => $oldWaitlists->count()
        ];
    }

    /**
     * Clean up orphaned data (entries without waitlists, etc.)
     */
    private function cleanupOrphanedData(): array
    {
        $orphanedEntries = DB::table('waitlist_entries')
            ->leftJoin('waitlists', 'waitlist_entries.waitlist_id', '=', 'waitlists.id')
            ->whereNull('waitlists.id')
            ->pluck('waitlist_entries.id');

        $deletedCount = 0;
        $errors = [];

        foreach ($orphanedEntries as $entryId) {
            try {
                DB::table('waitlist_entries')->where('id', $entryId)->delete();
                $deletedCount++;
                Log::info('Cleaned up orphaned waitlist entry', ['entry_id' => $entryId]);
            } catch (\Exception $e) {
                $errors[] = "Failed to delete orphaned entry {$entryId}: {$e->getMessage()}";
                Log::error('Failed to cleanup orphaned waitlist entry', [
                    'entry_id' => $entryId,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return [
            'cleanup_type' => 'orphaned_data',
            'deleted_count' => $deletedCount,
            'errors' => $errors,
            'total_found' => $orphanedEntries->count()
        ];
    }

    /**
     * Run all cleanup operations
     */
    private function cleanupAll(): array
    {
        $results = [];

        $results['expired_entries'] = $this->cleanupExpiredEntries();
        $results['old_waitlists'] = $this->cleanupOldWaitlists();
        $results['orphaned_data'] = $this->cleanupOrphanedData();

        $totalDeleted = array_sum(array_column($results, 'deleted_count'));
        $allErrors = array_merge(...array_column($results, 'errors'));

        return [
            'cleanup_type' => 'all',
            'total_deleted' => $totalDeleted,
            'errors' => $allErrors,
            'details' => $results
        ];
    }

    /**
     * Handle job failure
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Waitlist cleanup job failed', [
            'cleanup_type' => $this->cleanupType,
            'max_age' => $this->maxAge,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);
    }

    /**
     * Get the queue name for this job
     */
    public function queue(): string
    {
        return 'waitlist-maintenance';
    }
}
