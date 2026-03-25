<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Models\HealthJournal;
use App\Models\HealthMedicationSchedule;
use App\Models\HealthMedicationLog;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class HealthTrackingController extends Controller
{
    private const COMMON_SYMPTOMS = [
        'Headache',
        'Fatigue',
        'Nausea',
        'Dizziness',
        'Pain',
        'Fever',
        'Cough',
        'Shortness of breath',
        'Insomnia',
        'Anxiety',
        'Back pain',
        'Joint pain',
        'Muscle aches',
        'Sore throat',
        'Congestion',
    ];

    public function dashboard(): View
    {
        $patient = Auth::user();
        $today = now()->toDateString();

        $todayJournal = HealthJournal::getForDate($patient->id, $today);
        $recentJournals = HealthJournal::where('user_id', $patient->id)
            ->orderBy('entry_date', 'desc')
            ->limit(7)
            ->get();

        $todayMedications = HealthMedicationSchedule::getActiveForUser($patient->id)
            ->map(fn($s) => [
                'schedule' => $s,
                'log' => $s->getTodayLog(),
            ]);

        $adherenceStreak = HealthMedicationLog::getAdherenceStreak($patient->id);

        return view('patient.health.dashboard', compact(
            'todayJournal',
            'recentJournals',
            'todayMedications',
            'adherenceStreak',
        ));
    }

    public function journalEntry(): View
    {
        $patient = Auth::user();
        $today = now()->toDateString();
        $existingEntry = HealthJournal::getForDate($patient->id, $today);

        return view('patient.health.journal.entry', [
            'existingEntry' => $existingEntry,
            'today' => $today,
            'commonSymptoms' => self::COMMON_SYMPTOMS,
        ]);
    }

    public function storeJournal(Request $request): RedirectResponse
    {
        $patient = Auth::user();

        $validated = $request->validate([
            'entry_date' => 'required|date|after_or_equal:today',
            'symptoms' => 'array|max:20',
            'symptoms.*' => 'string|max:100',
            'severity' => 'array',
            'severity.*' => 'integer|min:1|max:5',
            'notes' => 'nullable|string|max:1000',
        ]);

        try {
            HealthJournal::upsertEntry(
                $patient->id,
                $validated['entry_date'],
                $validated['symptoms'] ?? [],
                $validated['severity'] ?? [],
                $validated['notes'] ?? null
            );
        } catch (\Exception $e) {
            Log::error('Failed to save health journal', [
                'user_id' => $patient->id,
                'entry_date' => $validated['entry_date'],
                'error' => $e->getMessage(),
            ]);
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Failed to save journal entry. Please try again.');
        }

        return redirect()
            ->route('patient.health.dashboard')
            ->with('success', 'Journal entry saved successfully.');
    }

    public function medications(): View
    {
        $patient = Auth::user();

        $schedules = HealthMedicationSchedule::where('user_id', $patient->id)
            ->orderBy('active', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        $todayLogs = $schedules->mapWithKeys(fn($s) => [$s->id => $s->getTodayLog()]);

        return view('patient.health.medications.index', compact('schedules', 'todayLogs'));
    }

    public function addMedication(Request $request): RedirectResponse
    {
        $patient = Auth::user();

        $validated = $request->validate([
            'medication_name' => 'required|string|max:255',
            'dosage' => 'required|string|max:255',
            'frequency' => 'required|string|max:100',
            'time_of_day' => 'nullable|string|max:50',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        try {
            HealthMedicationSchedule::create([
                'user_id' => $patient->id,
                'medication_name' => $validated['medication_name'],
                'dosage' => $validated['dosage'],
                'frequency' => $validated['frequency'],
                'time_of_day' => $validated['time_of_day'] ?? null,
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'] ?? null,
                'active' => true,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to add medication', [
                'user_id' => $patient->id,
                'medication' => $validated['medication_name'],
                'error' => $e->getMessage(),
            ]);
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Failed to add medication. Please try again.');
        }

        return redirect()
            ->route('patient.health.medications')
            ->with('success', 'Medication added successfully.');
    }

    public function takeMedication(Request $request, int $log): JsonResponse
    {
        try {
            $logEntry = HealthMedicationLog::findOrFail($log);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Medication log not found.'], 404);
        }

        $schedule = $logEntry->schedule;
        if (!$schedule) {
            return response()->json(['message' => 'Medication schedule not found.'], 404);
        }
        if ($schedule->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        try {
            $logEntry->update([
                'taken_at' => now(),
                'skipped' => false,
                'skip_reason' => null,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to mark medication as taken', [
                'log_id' => $logEntry->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);
            return response()->json(['message' => 'Failed to update medication status. Please try again.'], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Medication marked as taken.',
            'taken_at' => $logEntry->fresh()->taken_at?->toDateTimeString(),
        ]);
    }

    public function skipMedication(Request $request, int $log): JsonResponse
    {
        $validated = $request->validate([
            'skip_reason' => 'nullable|string|max:500',
        ]);

        try {
            $logEntry = HealthMedicationLog::findOrFail($log);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Medication log not found.'], 404);
        }

        $schedule = $logEntry->schedule;
        if (!$schedule) {
            return response()->json(['message' => 'Medication schedule not found.'], 404);
        }
        if ($schedule->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        try {
            $logEntry->update([
                'taken_at' => null,
                'skipped' => true,
                'skip_reason' => $validated['skip_reason'] ?? null,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to skip medication', [
                'log_id' => $logEntry->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);
            return response()->json(['message' => 'Failed to update medication status. Please try again.'], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Medication marked as skipped.',
        ]);
    }

    public function history(): View
    {
        $patient = Auth::user();

        $journals = HealthJournal::where('user_id', $patient->id)
            ->orderBy('entry_date', 'desc')
            ->paginate(15);

        return view('patient.health.history.index', compact('journals'));
    }
}
