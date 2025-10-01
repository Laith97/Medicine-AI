@extends('layouts.app')

@section('content')
<div class="dashboard-header py-2 border-bottom">
    <h2 class="h1 mb-1" style="font-weight: 700;">Hospital Admin Dashboard</h2>
    <p>Hospital administration overview</p>
</div>
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-hospital mr-2"></i>Hospital Dashboard
        </h1>
        <div class="text-muted">
            <i class="fas fa-building mr-1"></i>{{ $hospital->name }}
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row">
        <!-- Total Doctors -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total Doctors
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ $statistics['total_doctors'] }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-user-md fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Active Doctors -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Active Doctors
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ $statistics['active_doctors'] }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Appointments -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Total Appointments
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ $statistics['total_appointments'] }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-calendar fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Average Rating -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Average Rating
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ number_format($statistics['average_rating'], 1) }}/5
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-star fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Recent Doctors -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Recent Doctors</h6>
                    <a href="{{ route('hospital-admin.doctors.index') }}" class="btn btn-sm btn-primary">
                        View All
                    </a>
                </div>
                <div class="card-body">
                    @if($recentDoctors->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Specialty</th>
                                        <th>Status</th>
                                        <th>Joined</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($recentDoctors as $doctor)
                                        <tr>
                                            <td>
                                                <a href="{{ route('hospital-admin.doctors.show', $doctor) }}">
                                                    {{ $doctor->name }}
                                                </a>
                                            </td>
                                            <td>
                                                {{ $doctor->doctor->specialty->name ?? 'N/A' }}
                                            </td>
                                            <td>
                                                @if($doctor->doctor && $doctor->doctor->is_active)
                                                    <span class="badge badge-success">Active</span>
                                                @else
                                                    <span class="badge badge-secondary">Inactive</span>
                                                @endif
                                            </td>
                                            <td>{{ $doctor->created_at->format('M d, Y') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-muted">No doctors added yet.</p>
                        <a href="{{ route('hospital-admin.doctors.create') }}" class="btn btn-primary">
                            Add First Doctor
                        </a>
                    @endif
                </div>
            </div>
        </div>

        <!-- Subscription Info -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Subscription Status</h6>
                    <a href="{{ route('hospital-admin.subscription.manage') }}" class="btn btn-sm btn-primary">
                        Manage
                    </a>
                </div>
                <div class="card-body">
                    @if($subscriptionInfo['status'] === 'not_configured')
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle mr-2"></i>
                            Subscription not configured. Please set up your billing plan.
                        </div>
                        <a href="{{ route('hospital-admin.subscription.manage') }}" class="btn btn-primary">
                            Configure Subscription
                        </a>
                    @else
                        <div class="row">
                            <div class="col-md-6">
                                <strong>Status:</strong>
                                <span class="badge badge-{{ $subscriptionInfo['status'] === 'active' ? 'success' : 'warning' }}">
                                    {{ ucfirst($subscriptionInfo['status']) }}
                                </span>
                            </div>
                            <div class="col-md-6">
                                <strong>Days Remaining:</strong>
                                {{ $subscriptionInfo['days_remaining'] }} days
                            </div>
                        </div>
                        <hr>
                        <div class="row">
                            <div class="col-md-6">
                                <strong>Monthly Price:</strong>
                                ${{ number_format($subscriptionInfo['monthly_price'], 2) }}
                            </div>
                            <div class="col-md-6">
                                <strong>Yearly Price:</strong>
                                ${{ number_format($subscriptionInfo['yearly_price'], 2) }}
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Monthly Statistics -->
    <div class="row">
        <div class="col-lg-12 mb-4">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">This Month's Activity</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 text-center">
                            <div class="h4 text-primary">{{ $monthlyStats['appointments'] }}</div>
                            <div class="text-muted">Appointments</div>
                        </div>
                        <div class="col-md-4 text-center">
                            <div class="h4 text-success">{{ $monthlyStats['reviews'] }}</div>
                            <div class="text-muted">Reviews</div>
                        </div>
                        <div class="col-md-4 text-center">
                            <div class="h4 text-info">${{ number_format($monthlyStats['revenue'], 2) }}</div>
                            <div class="text-muted">Revenue</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="row">
        <div class="col-lg-12">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Quick Actions</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <a href="{{ route('hospital-admin.doctors.create') }}" class="btn btn-primary btn-block">
                                <i class="fas fa-plus mr-2"></i>Add Doctor
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="{{ route('hospital-admin.doctors.statistics') }}" class="btn btn-info btn-block">
                                <i class="fas fa-chart-bar mr-2"></i>View Statistics
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="{{ route('hospital-admin.subscription.manage') }}" class="btn btn-success btn-block">
                                <i class="fas fa-credit-card mr-2"></i>Manage Billing
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="{{ route('hospital-admin.hospital.profile') }}" class="btn btn-secondary btn-block">
                                <i class="fas fa-building mr-2"></i>Hospital Settings
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection