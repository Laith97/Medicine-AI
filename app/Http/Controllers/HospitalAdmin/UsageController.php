<?php

namespace App\Http\Controllers\HospitalAdmin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class UsageController extends Controller
{
    /**
     * Display usage reports for the hospital.
     */
    public function index()
    {
        $user = Auth::user();
        $hospital = $user->hospital;
        
        if (!$hospital) {
            return redirect()->route('hospital-admin.dashboard')
                ->with('error', 'No hospital associated with your account.');
        }

        // Get hospital doctors
        $doctors = User::where('hospital_id', $hospital->id)
            ->where('role', 'doctor')
            ->get();

        // Get usage statistics
        // TODO: Query Diagnosis model for count filtered by hospital's doctors
        // TODO: Query Appointment model for count filtered by hospital's doctors/patients
        $usageStats = [
            'total_doctors' => $doctors->count(),
            'active_doctors' => $doctors->where('is_active', true)->count(),
            'total_diagnoses' => 0, // TODO: Count diagnoses from doctors in this hospital
            'total_appointments' => 0, // TODO: Count appointments for this hospital
        ];

        // Get monthly usage data (placeholder - you'd implement based on your actual data)
        // TODO: Query actual monthly aggregates from diagnoses/appointments tables
        //       Group by MONTH(created_at) and hospital_id
        $monthlyUsage = collect(range(1, 12))->map(function ($month) {
            return [
                'month' => date('M', mktime(0, 0, 0, $month, 1)),
                'diagnoses' => rand(10, 100), // TODO: Replace with actual count
                'appointments' => rand(20, 200), // TODO: Replace with actual count
            ];
        });

        return view('hospital-admin.usage.index', compact('hospital', 'doctors', 'usageStats', 'monthlyUsage'));
    }

    /**
     * Export usage data.
     */
    public function export(Request $request)
    {
        $user = Auth::user();
        $hospital = $user->hospital;
        
        if (!$hospital) {
            return redirect()->route('hospital-admin.dashboard')
                ->with('error', 'No hospital associated with your account.');
        }

        // Get export data
        $doctors = User::where('hospital_id', $hospital->id)
            ->where('role', 'doctor')
            ->get();

        $filename = 'hospital_usage_report_' . date('Y-m-d') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($doctors, $hospital) {
            $file = fopen('php://output', 'w');
            
            // CSV headers
            fputcsv($file, [
                'Hospital',
                'Doctor Name',
                'Email',
                'Status',
                'Created Date',
                'Total Diagnoses',
                'Total Appointments'
            ]);

            // CSV data
            foreach ($doctors as $doctor) {
                fputcsv($file, [
                    $hospital->name,
                    $doctor->name,
                    $doctor->email,
                    $doctor->is_active ? 'Active' : 'Inactive',
                    $doctor->created_at->format('Y-m-d'),
                    0, // Replace with actual diagnosis count
                    0, // Replace with actual appointment count
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}