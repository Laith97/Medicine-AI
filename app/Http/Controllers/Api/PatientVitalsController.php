<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessPatientVitalJob;
use App\Models\PatientVital;
use Illuminate\Http\Request;

class PatientVitalsController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'patient_id' => 'required|exists:users,id',
            'vital_type' => 'required|string',
            'value' => 'required|string',
            'timestamp' => 'required|date',
        ]);

        $vital = PatientVital::create($validated);

        ProcessPatientVitalJob::dispatch($vital);

        return response()->json(['message' => 'Vital signs are being processed.'], 202);
    }
}
