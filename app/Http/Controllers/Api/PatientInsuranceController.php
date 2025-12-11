<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PatientInsurance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class PatientInsuranceController extends Controller
{
    /**
     * Display a listing of the patient's insurance records.
     */
    public function index(Request $request)
    {
        try {
            $query = PatientInsurance::with('insuranceProvider');

            // Filter by patient_id if provided
            if ($request->has('patient_id')) {
                $query->where('patient_id', $request->patient_id);
            }

            // In a real application, you'd also filter by the authenticated user's accessible patients
            // For now, return filtered insurance records
            $insurances = $query->get();

            return response()->json([
                'success' => true,
                'data' => $insurances
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load insurance records'
            ], 500);
        }
    }

    /**
     * Store a newly created insurance record.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'insurance_provider_id' => 'required|exists:insurance_providers,id',
            'policy_number' => 'required|string|max:255',
            'group_number' => 'nullable|string|max:255',
            'member_id' => 'required|string|max:255',
            'effective_date' => 'required|date',
            'expiration_date' => 'required|date|after:effective_date',
            'insurance_card' => 'nullable|file|mimes:jpeg,jpg,png,pdf|max:5120', // 5MB max
            'notes' => 'nullable|string|max:1000'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $data = $request->only([
                'insurance_provider_id',
                'policy_number',
                'group_number',
                'member_id',
                'effective_date',
                'expiration_date',
                'notes'
            ]);

            // Handle file upload
            if ($request->hasFile('insurance_card')) {
                $file = $request->file('insurance_card');
                $filename = time() . '_' . $file->getClientOriginalName();
                $path = $file->storeAs('insurance_cards', $filename, 'public');
                $data['card_path'] = $path;
            }

            // For now, we'll use a default patient_id
            // In a real application, this would be determined by the context
            $data['patient_id'] = 1; // Default patient ID

            $insurance = PatientInsurance::create($data);

            return response()->json([
                'success' => true,
                'message' => 'Insurance record created successfully',
                'insurance' => $insurance->load('insuranceProvider')
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create insurance record'
            ], 500);
        }
    }

    /**
     * Display the specified insurance record.
     */
    public function show(PatientInsurance $insurance)
    {
        try {
            return response()->json([
                'success' => true,
                'insurance' => $insurance->load('insuranceProvider')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load insurance record'
            ], 500);
        }
    }

    /**
     * Update the specified insurance record.
     */
    public function update(Request $request, PatientInsurance $insurance)
    {
        $validator = Validator::make($request->all(), [
            'insurance_provider_id' => 'required|exists:insurance_providers,id',
            'policy_number' => 'required|string|max:255',
            'group_number' => 'nullable|string|max:255',
            'member_id' => 'required|string|max:255',
            'effective_date' => 'required|date',
            'expiration_date' => 'required|date|after:effective_date',
            'insurance_card' => 'nullable|file|mimes:jpeg,jpg,png,pdf|max:5120', // 5MB max
            'notes' => 'nullable|string|max:1000'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $data = $request->only([
                'insurance_provider_id',
                'policy_number',
                'group_number',
                'member_id',
                'effective_date',
                'expiration_date',
                'notes'
            ]);

            // Handle file upload
            if ($request->hasFile('insurance_card')) {
                // Delete old file if exists
                if ($insurance->card_path && Storage::disk('public')->exists($insurance->card_path)) {
                    Storage::disk('public')->delete($insurance->card_path);
                }

                $file = $request->file('insurance_card');
                $filename = time() . '_' . $file->getClientOriginalName();
                $path = $file->storeAs('insurance_cards', $filename, 'public');
                $data['card_path'] = $path;
            }

            $insurance->update($data);

            return response()->json([
                'success' => true,
                'message' => 'Insurance record updated successfully',
                'insurance' => $insurance->load('insuranceProvider')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update insurance record'
            ], 500);
        }
    }

    /**
     * Remove the specified insurance record.
     */
    public function destroy(PatientInsurance $insurance)
    {
        try {
            // Delete associated file if exists
            if ($insurance->card_path && Storage::disk('public')->exists($insurance->card_path)) {
                Storage::disk('public')->delete($insurance->card_path);
            }

            $insurance->delete();

            return response()->json([
                'success' => true,
                'message' => 'Insurance record deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete insurance record'
            ], 500);
        }
    }
}
