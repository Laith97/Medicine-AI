@extends('layouts.admin')

@section('title', 'Manage Hospital Doctors')

@section('content')
<div class="admin-page">
    <div class="admin-container">
        <!-- Header -->
        <div class="admin-header">
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <div>
                    <h1 class="text-white">Manage Hospital Doctors</h1>
                    <p class="mb-0">{{ $user->hospital->name }} - {{ $user->name }}</p>
                </div>
                <div class="d-flex gap-2 mt-2 mt-md-0">
                    <a href="{{ route('admin.hospital-admins.manage', $user) }}" class="btn btn-light btn-sm">
                        <i class="bi bi-arrow-left me-1"></i>Back to Hospital
                    </a>
                    <a href="{{ route('admin.users.index') }}" class="btn btn-secondary btn-sm">
                        <i class="bi bi-people me-1"></i>All Users
                    </a>
                </div>
            </div>
        </div>

        <!-- Alerts -->
        @if(session('success'))
            <div class="admin-alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="admin-alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle"></i>{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <!-- Hospital Info Card -->
        <div class="admin-card mb-4">
            <div class="admin-card-body">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h5 class="mb-1">{{ $user->hospital->name }}</h5>
                        <p class="text-muted mb-0">
                            <i class="bi bi-envelope me-1"></i>{{ $user->hospital->email }}
                            @if($user->hospital->phone)
                                <span class="ms-3"><i class="bi bi-telephone me-1"></i>{{ $user->hospital->phone }}</span>
                            @endif
                        </p>
                    </div>
                    <div class="col-md-4 text-md-end">
                        <span class="admin-badge {{ $user->hospital->is_active ? 'success' : 'danger' }}">
                            {{ $user->hospital->is_active ? 'Active' : 'Inactive' }}
                        </span>
                        <div class="mt-1">
                            <small class="text-muted">{{ $doctors->total() }} doctors total</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Doctors Table -->
        <div class="admin-card">
            <div class="admin-card-header">
                <h5 class="mb-0">Hospital Doctors</h5>
            </div>
            <div class="admin-card-body">
                @if($doctors->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Doctor</th>
                                    <th>Specialty</th>
                                    <th>Status</th>
                                    <th>Contact</th>
                                    <th>Joined</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($doctors as $doctor)
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="me-3">
                                                    @if($doctor->doctor && $doctor->doctor->is_active)
                                                        <div class="icon-circle bg-success">
                                                            <i class="bi bi-check text-white"></i>
                                                        </div>
                                                    @else
                                                        <div class="icon-circle bg-secondary">
                                                            <i class="bi bi-pause text-white"></i>
                                                        </div>
                                                    @endif
                                                </div>
                                                <div>
                                                    <div class="fw-bold">{{ $doctor->name }}</div>
                                                    <div class="text-muted small">{{ $doctor->email }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            @if($doctor->doctor && $doctor->doctor->specialty)
                                                <span class="admin-badge info">{{ $doctor->doctor->specialty->name }}</span>
                                            @else
                                                <span class="admin-badge secondary">Not specified</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($doctor->doctor)
                                                @if($doctor->doctor->is_active)
                                                    <span class="admin-badge success">
                                                        <i class="bi bi-check-circle"></i>Active
                                                    </span>
                                                @else
                                                    <span class="admin-badge danger">
                                                        <i class="bi bi-x-circle"></i>Inactive
                                                    </span>
                                                @endif
                                            @else
                                                <span class="admin-badge secondary">No Profile</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="small">
                                                @if($doctor->phone)
                                                    <div><i class="bi bi-telephone me-1"></i>{{ $doctor->phone }}</div>
                                                @endif
                                                <div class="text-muted">{{ $doctor->email }}</div>
                                            </div>
                                        </td>
                                        <td>
                                            <small class="text-muted">{{ $doctor->created_at->format('M d, Y') }}</small>
                                        </td>
                                        <td>
                                            <div class="admin-actions">
                                                <a href="{{ route('admin.users.show', $doctor) }}" class="admin-btn primary" title="View">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <a href="{{ route('admin.users.edit', $doctor) }}" class="admin-btn warning" title="Edit">
                                                    <i class="bi bi-pencil"></i>
                                                </a>

                                                @if($doctor->doctor)
                                                    <form action="{{ route('admin.hospital-admins.doctors.toggle-status', [$user, $doctor]) }}" method="POST" class="d-inline"
                                                          onsubmit="return confirm('Are you sure you want to {{ $doctor->doctor->is_active ? 'deactivate' : 'activate' }} this doctor?')">
                                                        @csrf
                                                        <button type="submit" class="admin-btn {{ $doctor->doctor->is_active ? 'secondary' : 'success' }}" 
                                                                title="{{ $doctor->doctor->is_active ? 'Deactivate' : 'Activate' }} Doctor">
                                                            <i class="bi {{ $doctor->doctor->is_active ? 'bi-pause-circle' : 'bi-play-circle' }}"></i>
                                                        </button>
                                                    </form>
                                                @endif

                                                <!-- Login as Doctor -->
                                                <button type="button" class="admin-btn primary" title="Login as Doctor" 
                                                        onclick="loginAsUser({{ $doctor->id }}, '{{ $doctor->name }}', 'doctor')">
                                                    <i class="bi bi-box-arrow-in-right"></i>
                                                </button>

                                                <form action="{{ route('admin.users.destroy', $doctor) }}" method="POST" class="d-inline"
                                                      onsubmit="return confirm('Are you sure you want to delete this doctor? This action cannot be undone.')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="admin-btn danger" title="Delete">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    @if($doctors->hasPages())
                        <div class="d-flex justify-content-center mt-4">
                            {{ $doctors->links() }}
                        </div>
                    @endif
                @else
                    <div class="text-center py-5">
                        <i class="bi bi-people fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No Doctors Found</h5>
                        <p class="text-muted">This hospital doesn't have any doctors yet.</p>
                        <a href="{{ route('admin.users.create') }}" class="btn btn-primary">
                            <i class="bi bi-plus-circle me-1"></i>Add First Doctor
                        </a>
                    </div>
                @endif
            </div>
        </div>

        <!-- Statistics Card -->
        @if($doctors->count() > 0)
            <div class="admin-card mt-4">
                <div class="admin-card-header">
                    <h5 class="mb-0">Doctor Statistics</h5>
                </div>
                <div class="admin-card-body">
                    <div class="row text-center">
                        <div class="col-md-3">
                            <div class="mb-3">
                                <h4 class="text-primary">{{ $doctors->total() }}</h4>
                                <small class="text-muted">Total Doctors</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <h4 class="text-success">
                                    {{ $doctors->filter(function($doctor) { return $doctor->doctor && $doctor->doctor->is_active; })->count() }}
                                </h4>
                                <small class="text-muted">Active Doctors</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <h4 class="text-warning">
                                    {{ $doctors->filter(function($doctor) { return $doctor->doctor && $doctor->doctor->specialty; })->count() }}
                                </h4>
                                <small class="text-muted">With Specialty</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <h4 class="text-info">
                                    {{ $doctors->filter(function($doctor) { return $doctor->created_at->isCurrentMonth(); })->count() }}
                                </h4>
                                <small class="text-muted">Joined This Month</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>

<!-- Hidden forms for login-as functionality -->
@foreach($doctors as $doctor)
    <form id="login-as-form-{{ $doctor->id }}" method="POST" action="{{ route('admin.login-as', $doctor) }}" style="display: none;">
        @csrf
    </form>
@endforeach

<script>
function loginAsUser(userId, userName, userRole) {
    // console.log('loginAsUser called with:', userId, userName, userRole);
    
    const roleText = userRole === 'hospital_admin' ? 'Hospital Admin' : 'Doctor';
    
    if (confirm(`Are you sure you want to login as ${roleText} ${userName}? You will be redirected to their dashboard.`)) {
        // console.log('User confirmed, submitting form...');
        const form = document.getElementById('login-as-form-' + userId);
        if (form) {
            // console.log('Form found, submitting...');
            form.submit();
        } else {
            // console.error('Form not found:', 'login-as-form-' + userId);
            alert('Error: Form not found. Please refresh the page and try again.');
        }
    } else {
        // console.log('User cancelled');
    }
}
</script>

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