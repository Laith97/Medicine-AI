<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Services\WaitlistService;
use App\Models\Waitlist;
use App\Models\WaitlistEntry;
use Carbon\Carbon;

class WaitlistMonitoringJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $doctorId;

    /**
     * Create a new job instance.
     */
    public function __construct(int $doctorId = null)
    {
        $this->doctorId = $doctorId;
    }

    /**
     * Execute the job.
     */
    public function handle(WaitlistService $waitlistService): void
    {
        Log::info('Starting waitlist monitoring job', ['doctor_id' => $this->doctorId]);

        // Get doctors to monitor (all or specific)
        $doctorIds = $this->doctorId ? [$this->doctorId] : $this->getActiveDoctorsWithWaitlists();

        foreach ($doctorIds as $doctorId) {
            $this->processDoctorWaitlists($waitlistService, $doctorId);
        }

        Log::info('Completed waitlist monitoring job');
    }

    /**
     * Process waitlists for a specific doctor
     */
    private function processDoctorWaitlists(WaitlistService $waitlistService, int $doctorId): void
    {
        // Find available slots for this doctor
        $availableSlots = $waitlistService->findAvailableSlots($doctorId, 7); // Check next 7 days

        if (empty($availableSlots)) {
            Log::info('No available slots found for doctor', ['doctor_id' => $doctorId]);
            return;
        }

        // Get active waitlists for this doctor, ordered by priority and creation date
        $waitlists = Waitlist::where('doctor_id', $doctorId)
            ->active()
            ->orderByRaw("
                CASE priority_level
                    WHEN 'urgent' THEN 1
                    WHEN 'high' THEN 2
                    WHEN 'medium' THEN 3
                    WHEN 'low' THEN 4
                END
            ")
            ->orderBy('created_at')
            ->get();

        foreach ($waitlists as $waitlist) {
            $matchedSlot = $this->findMatchingSlotForWaitlist($availableSlots, $waitlist);

            if ($matchedSlot) {
                $this->createWaitlistEntry($waitlist, $matchedSlot);
                break; // Only offer to first patient
            }
        }
    }

    /**
     * Find a matching slot for a waitlist based on preferences
     */
    private function findMatchingSlotForWaitlist(array $availableSlots, Waitlist $waitlist): ?array
    {
        foreach ($availableSlots as $slot) {
            if ($this->slotMatchesWaitlistPreferences($slot, $waitlist)) {
                return $slot;
            }
        }

        return null;
    }

    /**
     * Check if slot matches waitlist preferences
     */
    private function slotMatchesWaitlistPreferences(array $slot, Waitlist $waitlist): bool
    {
        // Check preferred time slots
        if (!empty($waitlist->preferred_time_slots)) {
            $slotTime = date('H:i', strtotime($slot['time']));
            if (!$this->timeMatchesPreferences($slotTime, $waitlist->preferred_time_slots)) {
                return false;
            }
        }

        // Check preferred days
        if (!empty($waitlist->preferred_days)) {
            $slotDay = Carbon::parse($slot['date'])->format('l');
            if (!in_array(strtolower($slotDay), array_map('strtolower', $waitlist->preferred_days))) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if time matches preferences
     */
    private function timeMatchesPreferences(string $time, array $preferredTimes): bool
    {
        $hour = (int) date('H', strtotime($time));

        foreach ($preferredTimes as $preferredTime) {
            switch ($preferredTime) {
                case 'morning':
                    if ($hour >= 6 && $hour < 12) return true;
                    break;
                case 'afternoon':
                    if ($hour >= 12 && $hour < 17) return true;
                    break;
                case 'evening':
                    if ($hour >= 17 && $hour < 22) return true;
                    break;
            }
        }

        return false;
    }

    /**
     * Create a waitlist entry for the matched slot
     */
    private function createWaitlistEntry(Waitlist $waitlist, array $slot): void
    {
        // Check if entry already exists for this slot
        $existingEntry = WaitlistEntry::where('waitlist_id', $waitlist->id)
            ->where('slot_date', $slot['date'])
            ->where('slot_time', $slot['time'])
            ->first();

        if ($existingEntry) {
            Log::info('Waitlist entry already exists for slot', [
                'waitlist_id' => $waitlist->id,
                'slot_date' => $slot['date'],
                'slot_time' => $slot['time']
            ]);
            return;
        }

        $entry = WaitlistEntry::create([
            'waitlist_id' => $waitlist->id,
            'slot_date' => $slot['date'],
            'slot_time' => $slot['time'],
            'status' => 'pending',
        ]);

        // Offer the slot to the patient
        app(WaitlistService::class)->offerSlotToPatient($entry);

        Log::info('Created waitlist entry and offered slot', [
            'waitlist_id' => $waitlist->id,
            'entry_id' => $entry->id,
            'slot_date' => $slot['date'],
            'slot_time' => $slot['time']
        ]);
    }

    /**
     * Get active doctors who have waitlists
     */
    private function getActiveDoctorsWithWaitlists(): array
    {
        return Waitlist::where('status', 'active')
            ->distinct()
            ->pluck('doctor_id')
            ->toArray();
    }

    /**
     * Handle job failure
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Waitlist monitoring job failed', [
            'doctor_id' => $this->doctorId,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);
    }
}
