@extends('layouts.app')

@section('page-title', 'Doctor Details')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-0">Doctor Details</h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('hospital-admin.dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('hospital-admin.doctors.index') }}">Doctors</a></li>
                            <li class="breadcrumb-item active">{{ $doctor->name }}</li>
                        </ol>
                    </nav>
                </div>
                <div>
                    <a href="{{ route('hospital-admin.doctors.edit', $doctor) }}" class="btn btn-primary">
                        <i class="fas fa-edit me-2"></i>Edit Doctor
                    </a>
                    <a href="{{ route('hospital-admin.doctors.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Back to List
                    </a>
                </div>
            </div>

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <div class="row">
                <!-- Doctor Information -->
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Doctor Information</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Full Name</label>
                                        <p class="form-control-plaintext">{{ $doctor->name }}</p>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Email</label>
                                        <p class="form-control-plaintext">{{ $doctor->email }}</p>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Phone</label>
                                        <p class="form-control-plaintext">{{ $doctor->phone ?? 'Not provided' }}</p>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Specialty</label>
                                        <p class="form-control-plaintext">
                                            {{ $doctor->doctor->specialty->name ?? 'Not specified' }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Status</label>
                                        <p class="form-control-plaintext">
                                            @if($doctor->doctor && $doctor->doctor->is_active)
                                                <span class="badge bg-success">Active</span>
                                            @else
                                                <span class="badge bg-secondary">Inactive</span>
                                            @endif
                                        </p>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Joined Date</label>
                                        <p class="form-control-plaintext">{{ $doctor->created_at->format('M d, Y') }}</p>
                                    </div>
                                </div>
                            </div>

                            @if($doctor->doctor)
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Consultation Fee</label>
                                            <p class="form-control-plaintext">
                                                ${{ number_format($doctor->doctor->consultation_fee ?? 0, 2) }}
                                            </p>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Experience</label>
                                            <p class="form-control-plaintext">
                                                {{ $doctor->doctor->years_of_experience ?? 'Not specified' }} years
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                @if($doctor->doctor->bio)
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Biography</label>
                                        <p class="form-control-plaintext">{{ $doctor->doctor->bio }}</p>
                                    </div>
                                @endif
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Statistics -->
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Doctor Statistics</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span>Total Appointments</span>
                                <span class="badge bg-primary">
                                    {{ $doctor->doctor ? $doctor->doctor->appointments()->count() : 0 }}
                                </span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span>Completed Appointments</span>
                                <span class="badge bg-success">
                                    {{ $doctor->doctor ? $doctor->doctor->appointments()->where('status', 'completed')->count() : 0 }}
                                </span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span>Total Reviews</span>
                                <span class="badge bg-info">
                                    {{ $doctor->doctor ? $doctor->doctor->reviews()->count() : 0 }}
                                </span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span>Average Rating</span>
                                <span class="badge bg-warning">
                                    @if($doctor->doctor && $doctor->doctor->reviews()->count() > 0)
                                        {{ number_format($doctor->doctor->reviews()->avg('rating'), 1) }}/5
                                    @else
                                        No ratings
                                    @endif
                                </span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <span>This Month Appointments</span>
                                <span class="badge bg-secondary">
                                    {{ $doctor->doctor ? $doctor->doctor->appointments()->whereBetween('appointment_date', [now()->startOfMonth(), now()->endOfMonth()])->count() : 0 }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="card mt-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Quick Actions</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <a href="{{ route('hospital-admin.doctors.edit', $doctor) }}" class="btn btn-primary">
                                    <i class="fas fa-edit me-2"></i>Edit Doctor
                                </a>
                                <form method="POST" action="{{ route('hospital-admin.doctors.toggle-status', $doctor) }}" style="display: inline;">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn btn-warning w-100">
                                        @if($doctor->doctor && $doctor->doctor->is_active)
                                            <i class="fas fa-pause me-2"></i>Deactivate
                                        @else
                                            <i class="fas fa-play me-2"></i>Activate
                                        @endif
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('hospital-admin.doctors.destroy', $doctor) }}" 
                                      onsubmit="return confirm('Are you sure you want to remove this doctor?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger w-100">
                                        <i class="fas fa-trash me-2"></i>Remove Doctor
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection