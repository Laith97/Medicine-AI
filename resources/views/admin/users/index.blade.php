@extends('layouts.admin')

@section('title', 'Manage Users')

@push('styles')
<style>
    /* Page-specific overrides if needed */
    .btn-primary-custom {
        background-color: #007bff;
        border-color: #007bff;
        color: white;
    }
    
    .btn-primary-custom:hover {
        background-color: #0056b3;
        border-color: #0056b3;
        color: white;
    }
</style>
@endpush

@section('content')
<div class="admin-page">
    <div class="admin-container">
        <!-- Header -->
        <div class="admin-header">
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <div>
                    <h1 class="text-white">Manage Users</h1>
                    <p class="mb-0">View and manage all system users</p>
                </div>
                <div class="d-flex gap-2 mt-2 mt-md-0">
                    <a href="{{ route('admin.dashboard') }}" class="btn btn-light btn-sm">
                        <i class="bi bi-arrow-left me-1"></i>Back to Dashboard
                    </a>
                    <a href="{{ route('admin.users.create') }}" class="btn btn-success btn-sm">
                        <i class="bi bi-person-plus me-1"></i>Create New User
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

        <!-- Users Table -->
        <div class="admin-card">
            <div class="admin-card-header">
                <h5 class="mb-0">System Users</h5>
                <small class="text-muted">{{ $users->total() }} total users</small>
            </div>
            <div class="admin-card-body">
                <div class="table-responsive">
                    <table class="admin-table users-table" style="min-width: 1200px;">
                        <thead>
                        <tr>
                            <th>User</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Hospital</th>
                            <th>Specialty</th>
                            <th>Status</th>
                            <th>Verification</th>
                            <th>Pricing (M/Y)</th>
                            <th>Cost Limit</th>
                            <th>Joined</th>
                            <th>Cases</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $user)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="user-avatar me-2">
                                            {{ substr($user->name, 0, 1) }}
                                        </div>
                                        <div class="user-info">
                                            <h6>{{ $user->name }}</h6>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="text-muted text-truncate-custom" title="{{ $user->email }}">{{ $user->email }}</span>
                                </td>
                                <td>
                                    @php
                                        $roleColors = [
                                            'admin' => 'danger',
                                            'hospital_admin' => 'warning',
                                            'doctor' => 'success',
                                            'patient' => 'info'
                                        ];
                                        $roleColor = $roleColors[$user->role] ?? 'secondary';
                                    @endphp
                                    <span class="admin-badge {{ $roleColor }}">
                                        @if($user->role === 'admin')
                                            <i class="bi bi-shield-check"></i>Admin
                                        @elseif($user->role === 'hospital_admin')
                                            <i class="bi bi-building"></i>Hospital Admin
                                        @elseif($user->role === 'doctor')
                                            <i class="bi bi-person-badge"></i>Doctor
                                        @elseif($user->role === 'patient')
                                            <i class="bi bi-person"></i>Patient
                                        @else
                                            {{ ucfirst($user->role) }}
                                        @endif
                                    </span>
                                </td>
                                <td>
                                    @if($user->hospital)
                                        <span class="admin-badge info text-truncate-custom" title="{{ $user->hospital->name }}">
                                            <i class="bi bi-building"></i>{{ Str::limit($user->hospital->name, 15) }}
                                        </span>
                                    @else
                                        <span class="admin-badge secondary">Independent</span>
                                    @endif
                                </td>
                                <td>
                                    @if($user->setting && $user->setting->specialty)
                                        <span class="admin-badge primary text-truncate-custom" title="{{ $user->setting->specialty }}">{{ Str::limit($user->setting->specialty, 10) }}</span>
                                    @else
                                        <span class="admin-badge secondary">N/A</span>
                                    @endif
                                </td>
                                <td>
                                    @if($user->role === 'doctor')
                                        @if($user->doctor)
                                            @if($user->doctor->is_active)
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
                                    @elseif($user->role === 'hospital_admin')
                                        @if($user->hospital && $user->hospital->is_active)
                                            <span class="admin-badge success">
                                                <i class="bi bi-check-circle"></i>Active
                                            </span>
                                        @else
                                            <span class="admin-badge warning">
                                                <i class="bi bi-exclamation-triangle"></i>Hospital Inactive
                                            </span>
                                        @endif
                                    @else
                                        <span class="admin-badge info">
                                            <i class="bi bi-person-check"></i>User
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    @if($user->role === 'doctor')
                                        @if($user->doctor)
                                            @if($user->doctor->is_verified)
                                                <span class="admin-badge success">
                                                    <i class="bi bi-check-circle"></i>Verified
                                                </span>
                                            @else
                                                <span class="admin-badge warning">
                                                    <i class="bi bi-clock"></i>Pending
                                                </span>
                                            @endif
                                        @else
                                            <span class="admin-badge secondary">No Profile</span>
                                        @endif
                                    @else
                                        <span class="admin-badge secondary">N/A</span>
                                    @endif
                                </td>
                                <td>
                                    @if($user->monthlyInvoiceSetting)
                                        @php
                                            $setting = $user->monthlyInvoiceSetting;
                                            $monthlyPrice = $setting->monthly_price ?? 0;
                                            $yearlyPrice = $setting->yearly_price ?? 0;
                                        @endphp
                                        <div class="d-flex flex-column">
                                            <span class="admin-badge {{ $monthlyPrice > 0 ? 'success' : 'secondary' }} mb-1">
                                                <i class="bi bi-calendar-month"></i>${{ number_format($monthlyPrice, 0) }}/mo
                                            </span>
                                            <span class="admin-badge {{ $yearlyPrice > 0 ? 'info' : 'secondary' }}">
                                                <i class="bi bi-calendar-year"></i>${{ number_format($yearlyPrice, 0) }}/yr
                                            </span>
                                        </div>
                                        @if($setting->is_restricted)
                                            <small class="admin-badge danger mt-1">Restricted</small>
                                        @endif
                                    @else
                                        <span class="admin-badge secondary">Not set</span>
                                    @endif
                                </td>
                                <td>
                                    @if($user->monthly_cost_limit > 0)
                                        <span class="admin-badge info">
                                            <i class="bi bi-currency-dollar"></i>${{ number_format($user->monthly_cost_limit, 2) }}
                                        </span>
                                        @php
                                            $usagePercentage = $user->getCostUsagePercentage();
                                            $monthlyCost = $user->getMonthlyCostEstimate();
                                        @endphp
                                        @if($monthlyCost > 0)
                                            <br><small class="admin-badge {{ $usagePercentage >= 90 ? 'danger' : ($usagePercentage >= 70 ? 'warning' : 'success') }} mt-1">
                                                {{ number_format($usagePercentage, 1) }}% used
                                            </small>
                                        @endif
                                    @else
                                        <span class="admin-badge secondary">No limit</span>
                                    @endif
                                </td>
                                <td>
                                    <small class="text-muted">{{ $user->created_at->format('M d, Y') }}</small>
                                </td>
                                <td>
                                    <span class="admin-badge info">{{ $user->patientAnalyses->count() }}</span>
                                </td>
                                <td>
                                    <div class="admin-actions">
                                        <a href="{{ route('admin.users.show', $user) }}" class="admin-btn primary" title="View">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="{{ route('admin.users.edit', $user) }}" class="admin-btn warning" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>

                                        @if($user->role === 'hospital_admin')
                                            <a href="{{ route('admin.hospital-admins.manage', $user) }}" class="admin-btn info" title="Manage Hospital">
                                                <i class="bi bi-building"></i>
                                            </a>
                                            @if($user->hospital)
                                                <a href="{{ route('admin.hospital-admins.doctors', $user) }}" class="admin-btn success" title="Manage Doctors">
                                                    <i class="bi bi-people"></i>
                                                </a>
                                            @endif
                                            <!-- Login as Hospital Admin -->
                                            <button type="button" class="admin-btn primary" title="Login as Hospital Admin" 
                                                    onclick="loginAsUser({{ $user->id }}, '{{ $user->name }}', 'hospital_admin')">
                                                <i class="bi bi-box-arrow-in-right"></i>
                                            </button>
                                        @elseif($user->role === 'doctor')
                                            <!-- Doctor Verification Toggle -->
                                            @if($user->doctor)
                                                @if($user->doctor->is_verified)
                                                    <form action="{{ route('admin.doctors.unverify', $user->doctor) }}" method="POST" class="d-inline"
                                                          onsubmit="return confirm('Are you sure you want to unverify this doctor? They will no longer be visible to patients.')">
                                                        @csrf
                                                        <button type="submit" class="admin-btn warning" title="Unverify Doctor">
                                                            <i class="bi bi-x-circle"></i>
                                                        </button>
                                                    </form>
                                                @else
                                                    <form action="{{ route('admin.doctors.verify', $user->doctor) }}" method="POST" class="d-inline"
                                                          onsubmit="return confirm('Are you sure you want to verify this doctor? They will become visible to patients.')">
                                                        @csrf
                                                        <button type="submit" class="admin-btn success" title="Verify Doctor">
                                                            <i class="bi bi-check-circle"></i>
                                                        </button>
                                                    </form>
                                                @endif

                                                <!-- Doctor Status Toggle -->
                                                <form action="{{ route('admin.users.toggle-doctor-status', $user) }}" method="POST" class="d-inline"
                                                      onsubmit="return confirm('Are you sure you want to {{ $user->doctor->is_active ? 'deactivate' : 'activate' }} this doctor account?')">
                                                    @csrf
                                                    <button type="submit" class="admin-btn {{ $user->doctor->is_active ? 'secondary' : 'success' }}"
                                                            title="{{ $user->doctor->is_active ? 'Deactivate' : 'Activate' }} Doctor">
                                                        <i class="bi {{ $user->doctor->is_active ? 'bi-pause-circle' : 'bi-play-circle' }}"></i>
                                                    </button>
                                                </form>
                                            @endif
                                            <!-- Login as Doctor -->
                                            <button type="button" class="admin-btn primary" title="Login as Doctor"
                                                    onclick="loginAsUser({{ $user->id }}, '{{ $user->name }}', 'doctor')">
                                                <i class="bi bi-box-arrow-in-right"></i>
                                            </button>
                                        @endif

                                        @if($user->id !== auth()->id())
                                            <form action="{{ route('admin.users.destroy', $user) }}" method="POST" class="d-inline"
                                                  onsubmit="return confirm('Are you sure you want to delete this user?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="admin-btn danger" title="Delete">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        @else
                                            <span class="admin-badge secondary" style="font-size: 0.65rem;">You</span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="12">
                                    <div class="admin-empty-state">
                                        <i class="bi bi-people"></i>
                                        <p>No users found.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                </div>
            </div>
            
            <!-- Pagination -->
            @if($users->hasPages())
                <div class="admin-pagination">
                    {{ $users->links() }}
                    <div class="pagination-info">
                        Showing {{ $users->firstItem() }} to {{ $users->lastItem() }} of {{ $users->total() }} users
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Hidden forms for login-as functionality -->
@foreach($users as $user)
    @if(in_array($user->role, ['hospital_admin', 'doctor']))
        <form id="login-as-form-{{ $user->id }}" method="POST" action="{{ route('admin.login-as', $user) }}" style="display: none;">
            @csrf
        </form>
    @endif
@endforeach

<script>
function loginAsUser(userId, userName, userRole) {
    console.log('loginAsUser called with:', userId, userName, userRole);
    
    const roleText = userRole === 'hospital_admin' ? 'Hospital Admin' : 'Doctor';
    
    if (confirm(`Are you sure you want to login as ${roleText} ${userName}? You will be redirected to their dashboard.`)) {
        console.log('User confirmed, submitting form...');
        const form = document.getElementById('login-as-form-' + userId);
        if (form) {
            console.log('Form found, submitting...');
            form.submit();
        } else {
            console.error('Form not found:', 'login-as-form-' + userId);
            alert('Error: Form not found. Please refresh the page and try again.');
        }
    } else {
        console.log('User cancelled');
    }
}
</script>
@endsection
