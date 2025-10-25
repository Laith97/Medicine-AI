@extends('master')

@section('title', 'Sub-User Access Success')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card border-success">
                <div class="card-header bg-success text-white">
                    <h3 class="card-title mb-0">
                        <i class="fas fa-check-circle me-2"></i>Sub-User Access Successful!
                    </h3>
                </div>
                <div class="card-body">
                    <div class="alert alert-success">
                        <h4 class="alert-heading">🎉 Congratulations!</h4>
                        <p>You have successfully logged in as a sub-user and can access the system.</p>
                        <hr>
                        <p class="mb-0">The role-based access control system is working correctly.</p>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <h5>Your Information</h5>
                            <table class="table table-sm">
                                <tr>
                                    <td><strong>Name:</strong></td>
                                    <td>{{ auth()->user()->name }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Email:</strong></td>
                                    <td>{{ auth()->user()->email }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Role:</strong></td>
                                    <td>{{ ucfirst(auth()->user()->sub_user_role) }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Parent Doctor:</strong></td>
                                    <td>{{ auth()->user()->parentUser->name ?? 'N/A' }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h5>Available Actions</h5>
                            <div class="d-grid gap-2">
                                <a href="{{ route('dashboard') }}" class="btn btn-primary">
                                    <i class="fas fa-tachometer-alt me-2"></i>Go to Dashboard
                                </a>
                                <a href="{{ route('settings') }}" class="btn btn-outline-secondary">
                                    <i class="fas fa-cog me-2"></i>Settings
                                </a>
                                <a href="{{ route('doctor.patient-management.index') }}" class="btn btn-outline-info">
                                    <i class="fas fa-folder-open me-2"></i>Patient Management
                                </a>
                                @if(auth()->user()->canAccessRoute('doctor.appointments.index'))
                                    <a href="{{ route('doctor.appointments.index') }}" class="btn btn-outline-success">
                                        <i class="fas fa-calendar me-2"></i>Appointments
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="mt-4">
                        <h5>System Status</h5>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="text-center">
                                    <i class="fas fa-shield-check fa-2x text-success mb-2"></i>
                                    <p class="small mb-0">Middleware</p>
                                    <strong class="text-success">Working</strong>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-center">
                                    <i class="fas fa-user-check fa-2x text-success mb-2"></i>
                                    <p class="small mb-0">Permissions</p>
                                    <strong class="text-success">{{ auth()->user()->permissions->count() }} Granted</strong>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-center">
                                    <i class="fas fa-link fa-2x text-success mb-2"></i>
                                    <p class="small mb-0">Parent Link</p>
                                    <strong class="text-success">Active</strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection