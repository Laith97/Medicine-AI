<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use Illuminate\Http\Request;

class AppointmentController extends Controller
{
    public function index(Request $request)
    {
        $query = Appointment::with(['patient', 'doctor.user'])
            ->orderBy('created_at', 'desc');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('reason', 'like', "%{$search}%")
                  ->orWhereHas('patient', fn($pq) => $pq->where('name', 'like', "%{$search}%"))
                  ->orWhereHas('doctor', fn($dq) => $dq->where('name', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $appointments = $query->paginate(20)->withQueryString();
        $stats = [
            'total' => Appointment::count(),
            'completed' => Appointment::where('status','completed')->count(),
            'pending' => Appointment::where('status','pending')->count(),
            'today' => Appointment::whereDate('appointment_date', today())->count(),
        ];

        return view('admin.appointments.index', compact('appointments','stats'));
    }

    public function show(Appointment $appointment)
    {
        $appointment->load(['patient', 'doctor.user', 'doctor.specialty']);
        return view('admin.appointments.show', compact('appointment'));
    }
}
