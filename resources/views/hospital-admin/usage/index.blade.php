@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">Usage Reports</h1>
                <a href="{{ route('hospital-admin.usage.export') }}" class="btn btn-success">
                    <i class="fas fa-download me-2"></i>Export Report
                </a>
            </div>

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <!-- Usage Statistics -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h3 class="text-primary">{{ $usageStats['total_doctors'] }}</h3>
                            <p class="mb-0">Total Doctors</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h3 class="text-success">{{ $usageStats['active_doctors'] }}</h3>
                            <p class="mb-0">Active Doctors</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h3 class="text-info">{{ $usageStats['total_diagnoses'] }}</h3>
                            <p class="mb-0">Total Diagnoses</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h3 class="text-warning">{{ $usageStats['total_appointments'] }}</h3>
                            <p class="mb-0">Total Appointments</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Doctors List -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">{{ $hospital->name }} - Doctor Usage</h5>
                </div>
                <div class="card-body">
                    @if($doctors->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Doctor Name</th>
                                        <th>Email</th>
                                        <th>Status</th>
                                        <th>Joined Date</th>
                                        <th>Diagnoses</th>
                                        <th>Appointments</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($doctors as $doctor)
                                        <tr>
                                            <td>{{ $doctor->name }}</td>
                                            <td>{{ $doctor->email }}</td>
                                            <td>
                                                @if($doctor->is_active)
                                                    <span class="badge bg-success">Active</span>
                                                @else
                                                    <span class="badge bg-secondary">Inactive</span>
                                                @endif
                                            </td>
                                            <td>{{ $doctor->created_at->format('M d, Y') }}</td>
                                            <td>
                                                <span class="badge bg-info">0</span>
                                                <!-- Replace with actual count -->
                                            </td>
                                            <td>
                                                <span class="badge bg-warning">0</span>
                                                <!-- Replace with actual count -->
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-user-md fa-3x text-muted mb-3"></i>
                            <h5>No Doctors Found</h5>
                            <p class="text-muted">No doctors are associated with this hospital yet.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection