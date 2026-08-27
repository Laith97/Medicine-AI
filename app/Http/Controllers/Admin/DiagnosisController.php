<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Diagnosis;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class DiagnosisController extends Controller
{
    public function index(Request $request)
    {
        $query = Diagnosis::with(['patient', 'doctor', 'appointment'])
            ->orderBy('created_at', 'desc');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('diagnosis_text', 'like', "%{$search}%")
                  ->orWhereHas('patient', fn($pq) => $pq->where('name', 'like', "%{$search}%"))
                  ->orWhereHas('doctor', fn($dq) => $dq->where('name', 'like', "%{$search}%"));
            });
        }

        $hasTypeColumn = Schema::hasColumn('diagnoses', 'type');
        if ($request->filled('type') && $hasTypeColumn) {
            $query->where('type', $request->type);
        }

        $diagnoses = $query->paginate(20)->withQueryString();
        if ($hasTypeColumn) {
            $stats = [
                'total' => Diagnosis::count(),
                'voice' => Diagnosis::where('type','voice_assistant')->count(),
                'text' => Diagnosis::where('type','text')->count(),
                'today' => Diagnosis::whereDate('created_at', today())->count(),
            ];
        } else {
            // type column was removed (manual-only diagnoses) — derive stats from available columns
            $total = Diagnosis::count();
            $voice = Diagnosis::whereNotNull('voice_files')->where('voice_files','!=','[]')->count();
            // fallback: voice_transcripts not null
            if ($voice === 0) {
                $voice = Diagnosis::whereNotNull('voice_transcripts')->where('voice_transcripts','!=','[]')->count();
            }
            $stats = [
                'total' => $total,
                'voice' => $voice,
                'text' => max(0, $total - $voice),
                'today' => Diagnosis::whereDate('created_at', today())->count(),
            ];
        }

        return view('admin.diagnoses.index', compact('diagnoses','stats'));
    }

    public function show(Diagnosis $diagnosis)
    {
        $diagnosis->load(['patient', 'doctor', 'appointment', 'followUps']);
        return view('admin.diagnoses.show', compact('diagnosis'));
    }
}
