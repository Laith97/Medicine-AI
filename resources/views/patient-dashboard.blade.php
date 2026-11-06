@extends('layouts.app')

@section('content')
<div class="container py-4">
    <h1 class="mb-4">Welcome, {{ auth()->user()->name }}</h1>
    
    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h3>{{ $stats['total_appointments'] }}</h3>
                    <p class="text-muted">Total Appointments</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center border-warning">
                <div class="card-body">
                    <h3>{{ $stats['pending_appointments'] ?? $appointments->where('status', 'pending')->count() }}</h3>
                    <p class="text-muted">Pending Confirmation</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h3>{{ $stats['upcoming_appointments'] }}</h3>
                    <p class="text-muted">Upcoming</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h3>{{ $stats['completed_appointments'] }}</h3>
                    <p class="text-muted">Completed</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h3>{{ $stats['total_diagnoses'] }}</h3>
                    <p class="text-muted">Diagnoses</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h3>{{ $stats['total_prescriptions'] ?? 0 }}</h3>
                    <p class="text-muted">Prescriptions</p>
                    <a href="{{ route('appointments.index') }}" class="btn btn-sm btn-link">View in appointments</a>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h3>{{ $stats['active_hep'] ?? 0 }}</h3>
                    <p class="text-muted">Active Exercise Programs</p>
                    <a href="{{ route('patient.hep.dashboard') }}" class="btn btn-sm btn-link">Open Exercises</a>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h3>{{ $stats['active_waitlist'] ?? 0 }}</h3>
                    <p class="text-muted">Active Waitlists</p>
                    <a href="{{ route('patient.waitlist.dashboard') }}" class="btn btn-sm btn-link">View Waitlist</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="card mb-4">
        <div class="card-header"><h5>Quick Actions</h5></div>
        <div class="card-body d-flex flex-wrap gap-2">
            <a href="{{ route('doctors.index') }}" class="btn btn-primary">Book Appointment</a>
            <a href="{{ route('appointments.index') }}" class="btn btn-outline-primary">My Appointments</a>
            <a href="{{ route('diagnosis.patient.index') }}" class="btn btn-outline-primary">My Diagnoses</a>
            <a href="{{ route('patient.hep.dashboard') }}" class="btn btn-outline-success">Exercises</a>
            <a href="{{ route('patient.waitlist.dashboard') }}" class="btn btn-outline-info">Waitlist</a>
            <a href="{{ route('notifications.index') }}" class="btn btn-outline-secondary">Notifications</a>
        </div>
    </div>
    
    <!-- Upcoming Appointments -->
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5>Upcoming Appointments</h5>
            <a href="{{ route('appointments.index') }}" class="btn btn-sm btn-link">View all</a>
        </div>
        <div class="card-body">
            @if(($pendingAppointments ?? $appointments->where('status', 'pending'))->count() > 0)
                <div class="alert alert-warning py-2">You have {{ ($pendingAppointments ?? $appointments->where('status', 'pending'))->count() }} appointment(s) awaiting doctor confirmation.</div>
            @endif
            @if($appointments->where('status', 'confirmed')->where('appointment_date', '>', now())->count() > 0)
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Doctor</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($appointments->where('status', 'confirmed')->where('appointment_date', '>', now())->take(5) as $appointment)
                            <tr>
                                <td>{{ $appointment->appointment_date->format('M d, Y h:i A') }}</td>
                                <td>{{ $appointment->doctor->user->name ?? 'N/A' }}</td>
                                <td>{{ ucfirst($appointment->appointment_type) }}</td>
                                <td><span class="badge bg-success">{{ ucfirst($appointment->status) }}</span></td>
                                <td class="text-nowrap">
                                    <a href="{{ route('appointments.show', $appointment) }}" class="btn btn-sm btn-primary">View</a>
                                    @if($appointment->appointment_type === 'video_call')
                                        <a href="{{ route('video.room', $appointment) }}" class="btn btn-sm btn-success" target="_blank">Join</a>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-muted">No upcoming appointments</p>
                <a href="{{ route('doctors.index') }}" class="btn btn-primary">Book Appointment</a>
            @endif
        </div>
    </div>
    
    <!-- Recent Diagnoses -->
    <div class="card mb-4">
        <div class="card-header">
            <h5>Recent Diagnoses</h5>
        </div>
        <div class="card-body">
            @if($diagnoses->count() > 0)
                <div class="list-group">
                    @foreach($diagnoses->take(5) as $diagnosis)
                    <a href="{{ route('diagnosis.patient.view', $diagnosis) }}" class="list-group-item list-group-item-action">
                        <div class="d-flex w-100 justify-content-between">
                            <h6 class="mb-1">Diagnosis by Dr. {{ $diagnosis->doctor->user->name ?? 'N/A' }}</h6>
                            <small>{{ $diagnosis->created_at->diffForHumans() }}</small>
                        </div>
                        <p class="mb-1">{{ Str::limit($diagnosis->diagnosis_text, 100) }}</p>
                    </a>
                    @endforeach
                </div>
                <a href="{{ route('diagnosis.patient.index') }}" class="btn btn-link mt-2">View All Diagnoses</a>
            @else
                <p class="text-muted">No diagnoses yet</p>
            @endif
        </div>
    </div>
    
    <!-- Recent Reviews -->
    @if($reviews->count() > 0)
    <div class="card">
        <div class="card-header">
            <h5>Your Reviews</h5>
        </div>
        <div class="card-body">
            <div class="list-group">
                @foreach($reviews->take(3) as $review)
                <div class="list-group-item">
                    <div class="d-flex w-100 justify-content-between">
                        <h6 class="mb-1">Review for Dr. {{ $review->doctor->user->name ?? 'N/A' }}</h6>
                        <small>{{ $review->created_at->diffForHumans() }}</small>
                    </div>
                    <div class="mb-1">
                        @for($i = 1; $i <= 5; $i++)
                            <i class="fas fa-star {{ $i <= $review->rating ? 'text-warning' : 'text-muted' }}"></i>
                        @endfor
                    </div>
                    <p class="mb-1">{{ Str::limit($review->comment, 100) }}</p>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif
</div>
@endsection
