<?php

namespace App\Http\Controllers\HospitalAdmin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Diagnosis;
use App\Models\Appointment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

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

        // Get hospital doctor IDs
        $hospitalDoctorIds = User::where('hospital_id', $hospital->id)
            ->where('role', 'doctor')
            ->pluck('id');

        $doctors = User::where('hospital_id', $hospital->id)
            ->where('role', 'doctor')
            ->get();

        // Get usage statistics - real queries
        $totalDiagnoses = Diagnosis::whereIn('doctor_id', $hospitalDoctorIds)->count();
        $totalAppointments = Appointment::whereIn('doctor_id', $hospitalDoctorIds)->count();

        $usageStats = [
            'total_doctors' => $doctors->count(),
            'active_doctors' => $doctors->where('is_active', true)->count(),
            'total_diagnoses' => $totalDiagnoses,
            'total_appointments' => $totalAppointments,
        ];

        // Get monthly usage data - real queries for last 12 months
        $monthlyUsage = collect(range(11, 0))->map(function ($monthsAgo) {
            $month = Carbon::now()->subMonths($monthsAgo);
            $startOfMonth = $month->copy()->startOfMonth();
            $endOfMonth = $month->copy()->endOfMonth();

            // Get hospital doctor IDs for this month (in case doctors were added/removed)
            $hospitalDoctorIds = User::where('hospital_id', auth()->user()->hospital_id)
                ->where('role', 'doctor')
                ->pluck('id');

            $diagnosesCount = Diagnosis::whereIn('doctor_id', $hospitalDoctorIds)
                ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
                ->count();

            $appointmentsCount = Appointment::whereIn('doctor_id', $hospitalDoctorIds)
                ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
                ->count();

            return [
                'month' => $month->format('M'),
                'diagnoses' => $diagnosesCount,
                'appointments' => $appointmentsCount,
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
                $diagnosesCount = Diagnosis::where('doctor_id', $doctor->id)->count();
                $appointmentsCount = Appointment::where('doctor_id', $doctor->id)->count();

                fputcsv($file, [
                    $hospital->name,
                    $doctor->name,
                    $doctor->email,
                    $doctor->is_active ? 'Active' : 'Inactive',
                    $doctor->created_at->format('Y-m-d'),
                    $diagnosesCount,
                    $appointmentsCount,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}