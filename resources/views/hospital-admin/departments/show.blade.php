@extends('layouts.app')

@section('page-title', 'Department Details')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-0">Department Details</h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('hospital-admin.dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('hospital-admin.departments.index') }}">Departments</a></li>
                            <li class="breadcrumb-item active">{{ $department->name }}</li>
                        </ol>
                    </nav>
                </div>
                <div>
                    <a href="{{ route('hospital-admin.departments.edit', $department) }}" class="btn btn-primary">
                        <i class="fas fa-edit me-2"></i>Edit Department
                    </a>
                    <a href="{{ route('hospital-admin.departments.index') }}" class="btn btn-secondary">
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
                <!-- Department Information -->
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Department Information</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Department Name</label>
                                        <p class="form-control-plaintext">{{ $department->name }}</p>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Head of Department</label>
                                        <p class="form-control-plaintext">{{ $department->head_of_department ?? 'Not assigned' }}</p>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Phone</label>
                                        <p class="form-control-plaintext">{{ $department->phone ?? 'Not provided' }}</p>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Email</label>
                                        <p class="form-control-plaintext">{{ $department->email ?? 'Not provided' }}</p>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Location</label>
                                        <p class="form-control-plaintext">{{ $department->location ?? 'Not specified' }}</p>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Capacity</label>
                                        <p class="form-control-plaintext">
                                            {{ $department->capacity ? $department->capacity . ' beds/rooms' : 'Not specified' }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Status</label>
                                        <p class="form-control-plaintext">
                                            @if($department->is_active)
                                                <span class="badge bg-success">Active</span>
                                            @else
                                                <span class="badge bg-secondary">Inactive</span>
                                            @endif
                                        </p>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Created Date</label>
                                        <p class="form-control-plaintext">{{ $department->created_at->format('M d, Y') }}</p>
                                    </div>
                                </div>
                            </div>

                            @if($department->description)
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Description</label>
                                    <p class="form-control-plaintext">{{ $department->description }}</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Statistics -->
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Department Statistics</h5>
                        </div>
                        <div class="card-body">
                            @php
                                $stats = $department->getStatistics();
                            @endphp
                            <div class="alert alert-info mb-3">
                                <small>
                                    <i class="fas fa-info-circle me-1"></i>
                                    Statistics reflect organizational structure only. Doctors are managed hospital-wide.
                                </small>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span>Department Doctors</span>
                                <span class="badge bg-secondary">
                                    {{ $stats['total_doctors'] }} (Organizational)
                                </span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span>Active Doctors</span>
                                <span class="badge bg-secondary">
                                    {{ $stats['active_doctors'] }} (Organizational)
                                </span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span>Total Appointments</span>
                                <span class="badge bg-secondary">{{ $stats['total_appointments'] }} (N/A)</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <span>This Month</span>
                                <span class="badge bg-secondary">{{ $stats['this_month_appointments'] }} (N/A)</span>
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
                                <a href="{{ route('hospital-admin.departments.edit', $department) }}" class="btn btn-primary">
                                    <i class="fas fa-edit me-2"></i>Edit Department
                                </a>
                                <form method="POST" action="{{ route('hospital-admin.departments.destroy', $department) }}" 
                                      onsubmit="return confirm('Are you sure you want to delete this department?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger w-100">
                                        <i class="fas fa-trash me-2"></i>Delete Department
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Doctor Management Info -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-info-circle me-2"></i>Doctor Management
                    </h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info d-flex align-items-center" role="alert">
                        <i class="fas fa-info-circle me-3"></i>
                        <div>
                            <h6 class="alert-heading mb-1">Organizational Structure Only</h6>
                            <p class="mb-2">This department serves as an organizational structure within your hospital. 
                            Doctors are managed globally at the hospital level for greater flexibility.</p>
                            <a href="{{ route('hospital-admin.doctors.index') }}" class="btn btn-primary btn-sm">
                                <i class="fas fa-user-md me-2"></i>Manage All Hospital Doctors
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.icon-circle {
    height: 2rem;
    width: 2rem;
    border-radius: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
}
</style>
@endsection