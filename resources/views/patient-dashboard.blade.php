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
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h3>{{ $stats['total_diagnoses'] }}</h3>
                    <p class="text-muted">Diagnoses</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Upcoming Appointments -->
    <div class="card mb-4">
        <div class="card-header">
            <h5>Upcoming Appointments</h5>
        </div>
        <div class="card-body">
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
                                <td>
                                    <a href="{{ route('appointments.show', $appointment) }}" class="btn btn-sm btn-primary">View</a>
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
