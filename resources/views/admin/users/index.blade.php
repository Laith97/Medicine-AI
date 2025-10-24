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

    /* Improved table styling */
    .admin-table.users-table {
        font-size: 0.875rem;
        border-collapse: separate;
        border-spacing: 0;
    }

    .admin-table.users-table thead th {
        background: linear-gradient(135deg, #f8f9fa, #e9ecef);
        border-bottom: 2px solid #dee2e6;
        font-weight: 600;
        color: #495057;
        padding: 1rem 0.75rem;
        text-align: left;
        position: sticky;
        top: 0;
        z-index: 10;
    }

    .admin-table.users-table tbody tr {
        border-bottom: 1px solid #dee2e6;
        transition: background-color 0.2s ease;
    }

    .admin-table.users-table tbody tr:hover {
        background-color: #f8f9fa;
    }

    .admin-table.users-table tbody td {
        padding: 1rem 0.75rem;
        vertical-align: top;
    }

    .admin-table.users-table .user-avatar {
        width: 45px;
        height: 45px;
        border-radius: 50%;
        background: linear-gradient(135deg, #DE6262, #007bff);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        font-size: 1.2rem;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        flex-shrink: 0;
    }

    .admin-table.users-table .admin-actions {
        min-width: 180px;
    }

    .admin-table.users-table .admin-actions .admin-btn {
        padding: 0.375rem 0.5rem;
        font-size: 0.75rem;
        margin: 0.125rem;
        border-radius: 0.375rem;
        transition: all 0.2s ease;
    }

    .admin-table.users-table .admin-actions .admin-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 2px 4px rgba(0,0,0,0.15);
    }

    .admin-table.users-table .admin-actions .btn-group-vertical {
        margin: 0.125rem;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        border-radius: 0.375rem;
        overflow: hidden;
    }

    .admin-table.users-table .admin-actions .btn-group-vertical .admin-btn {
        margin: 0;
        border-radius: 0 !important;
        border: none;
    }

    .admin-table.users-table .admin-actions .btn-group-vertical .admin-btn:first-child {
        border-top-left-radius: 0.375rem !important;
        border-top-right-radius: 0.375rem !important;
    }

    .admin-table.users-table .admin-actions .btn-group-vertical .admin-btn:last-child {
        border-bottom-left-radius: 0.375rem !important;
        border-bottom-right-radius: 0.375rem !important;
    }

    /* Badge improvements */
    .admin-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        padding: 0.25rem 0.5rem;
        border-radius: 0.375rem;
        font-size: 0.75rem;
        font-weight: 500;
        text-transform: none;
        letter-spacing: 0.025em;
        white-space: nowrap;
    }

    .admin-badge.success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
    .admin-badge.danger { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    .admin-badge.warning { background-color: #fff3cd; color: #856404; border: 1px solid #ffeaa7; }
    .admin-badge.info { background-color: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
    .admin-badge.primary { background-color: #cce5ff; color: #004085; border: 1px solid #b3d7ff; }
    .admin-badge.secondary { background-color: #e2e3e5; color: #383d41; border: 1px solid #d6d8db; }

    /* Responsive improvements */
    @media (max-width: 1200px) {
        .admin-table.users-table {
            font-size: 0.8rem;
        }

        .admin-table.users-table .admin-actions {
            min-width: 160px;
        }

        .admin-table.users-table .admin-actions .admin-btn {
            padding: 0.3rem 0.4rem;
            font-size: 0.7rem;
        }

        .admin-table.users-table .user-avatar {
            width: 40px;
            height: 40px;
            font-size: 1.1rem;
        }
    }

    @media (max-width: 992px) {
        .admin-table.users-table thead th {
            padding: 0.75rem 0.5rem;
            font-size: 0.8rem;
        }

        .admin-table.users-table tbody td {
            padding: 0.75rem 0.5rem;
        }

        .admin-table.users-table .user-avatar {
            width: 38px;
            height: 38px;
            font-size: 1rem;
        }
    }

    @media (max-width: 768px) {
        .admin-table.users-table {
            font-size: 0.75rem;
        }

        .admin-table.users-table .user-avatar {
            width: 35px;
            height: 35px;
            font-size: 0.9rem;
        }

        .admin-table.users-table .admin-actions {
            flex-direction: column !important;
            align-items: flex-start !important;
            min-width: auto;
        }

        .admin-table.users-table .admin-actions .admin-btn {
            width: 100%;
            margin: 0.125rem 0;
            justify-content: center;
        }

        .admin-table.users-table .admin-actions .btn-group-vertical {
            width: 100%;
        }

        .admin-table.users-table .admin-actions .btn-group-vertical .admin-btn {
            width: 100%;
            text-align: center;
        }
    }

    @media (max-width: 576px) {
        .admin-table.users-table thead th {
            font-size: 0.7rem;
            padding: 0.5rem 0.25rem;
        }

        .admin-table.users-table tbody td {
            padding: 0.5rem 0.25rem;
        }

        .admin-table.users-table .user-avatar {
            width: 30px;
            height: 30px;
            font-size: 0.8rem;
        }

        .admin-badge {
            font-size: 0.65rem;
            padding: 0.2rem 0.4rem;
        }
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
                    <table class="admin-table users-table" style="min-width: 1000px;">
                        <thead>
                        <tr>
                            <th>User & Role</th>
                            <th>Status</th>
                            <th>Billing & Usage</th>
                            <th>Hospital/Specialty</th>
                            <th>Joined</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $user)
                            <tr>
                                <!-- User & Role Column -->
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="user-avatar me-3">
                                            {{ substr($user->name, 0, 1) }}
                                        </div>
                                        <div class="user-info">
                                            <h6 class="mb-1">{{ $user->name }}</h6>
                                            <small class="text-muted text-truncate-custom" title="{{ $user->email }}">{{ $user->email }}</small>
                                            @php
                                                $roleColors = [
                                                    'admin' => 'danger',
                                                    'hospital_admin' => 'warning',
                                                    'doctor' => 'success',
                                                    'patient' => 'info'
                                                ];
                                                $roleColor = $roleColors[$user->role] ?? 'secondary';
                                            @endphp
                                            <br><span class="admin-badge {{ $roleColor }} mt-1">
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
                                        </div>
                                    </div>
                                </td>

                                <!-- Status Column -->
                                <td>
                                    <div class="d-flex flex-column gap-1">
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
                                                @if($user->doctor->is_verified)
                                                    <span class="admin-badge success">
                                                        <i class="bi bi-shield-check"></i>Verified
                                                    </span>
                                                @else
                                                    <span class="admin-badge warning">
                                                        <i class="bi bi-clock"></i>Pending
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
                                    </div>
                                </td>

                                <!-- Billing & Usage Column -->
                                <td>
                                    @if($user->monthlyInvoiceSetting)
                                        @php
                                            $setting = $user->monthlyInvoiceSetting;
                                            $monthlyPrice = $setting->monthly_price ?? 0;
                                            $yearlyPrice = $setting->yearly_price ?? 0;
                                            $billingAmount = $setting->billing_amount ?? 0;
                                            $usagePercentage = $user->getCostUsagePercentage();
                                        @endphp
                                        <div class="d-flex flex-column gap-1">
                                            <!-- Current Billing Plan -->
                                            @if($billingAmount > 0)
                                                <span class="admin-badge primary">
                                                    <i class="bi bi-credit-card"></i>${{ number_format($billingAmount, 0) }}/mo
                                                </span>
                                            @else
                                                <span class="admin-badge secondary">No billing</span>
                                            @endif

                                            <!-- Usage Status -->
                                            @if($user->monthly_cost_limit > 0)
                                                @php
                                                    $usageColor = $usagePercentage >= 90 ? 'danger' : ($usagePercentage >= 70 ? 'warning' : 'success');
                                                @endphp
                                                <span class="admin-badge {{ $usageColor }}">
                                                    <i class="bi bi-graph-up"></i>{{ number_format($usagePercentage, 1) }}% used
                                                </span>
                                            @endif

                                            <!-- Subscription Status -->
                                            @if($setting->is_restricted)
                                                <span class="admin-badge danger">
                                                    <i class="bi bi-lock"></i>Restricted
                                                </span>
                                            @endif
                                        </div>
                                    @else
                                        <span class="admin-badge secondary">Not configured</span>
                                    @endif
                                </td>

                                <!-- Hospital/Specialty Column -->
                                <td>
                                    <div class="d-flex flex-column gap-1">
                                        @if($user->hospital)
                                            <span class="admin-badge info text-truncate-custom" title="{{ $user->hospital->name }}">
                                                <i class="bi bi-building"></i>{{ Str::limit($user->hospital->name, 15) }}
                                            </span>
                                        @else
                                            <span class="admin-badge secondary">Independent</span>
                                        @endif

                                        @if($user->setting && $user->setting->specialty)
                                            <span class="admin-badge primary text-truncate-custom" title="{{ $user->setting->specialty }}">
                                                <i class="bi bi-stethoscope"></i>{{ Str::limit($user->setting->specialty, 12) }}
                                            </span>
                                        @endif
                                    </div>
                                </td>

                                <!-- Joined Column -->
                                <td>
                                    <div class="d-flex flex-column">
                                        <small class="text-muted">{{ $user->created_at->format('M d, Y') }}</small>
                                        @if($user->patientAnalyses->count() > 0)
                                            <small class="admin-badge info mt-1">{{ $user->patientAnalyses->count() }} cases</small>
                                        @endif
                                    </div>
                                </td>
                                <!-- Actions Column -->
                                <td>
                                    <div class="admin-actions d-flex flex-wrap gap-1">
                                        <!-- Common Actions -->
                                        <a href="{{ route('admin.users.show', $user) }}" class="admin-btn primary btn-sm" title="View Details">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="{{ route('admin.users.edit', $user) }}" class="admin-btn warning btn-sm" title="Edit User">
                                            <i class="bi bi-pencil"></i>
                                        </a>

                                        <!-- Role-specific Actions -->
                                        @if($user->role === 'hospital_admin')
                                            <div class="btn-group-vertical btn-group-sm">
                                                <a href="{{ route('admin.hospital-admins.manage', $user) }}" class="admin-btn info" title="Manage Hospital">
                                                    <i class="bi bi-building"></i>
                                                </a>
                                                @if($user->hospital)
                                                    <a href="{{ route('admin.hospital-admins.doctors', $user) }}" class="admin-btn success" title="Manage Doctors">
                                                        <i class="bi bi-people"></i>
                                                    </a>
                                                @endif
                                                <button type="button" class="admin-btn primary" title="Login as Admin"
                                                        onclick="loginAsUser({{ $user->id }}, '{{ $user->name }}', 'hospital_admin')">
                                                    <i class="bi bi-box-arrow-in-right"></i>
                                                </button>
                                            </div>
                                        @elseif($user->role === 'doctor')
                                            <div class="btn-group-vertical btn-group-sm">
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
                                                <button type="button" class="admin-btn primary" title="Login as Doctor"
                                                        onclick="loginAsUser({{ $user->id }}, '{{ $user->name }}', 'doctor')">
                                                    <i class="bi bi-box-arrow-in-right"></i>
                                                </button>
                                            </div>
                                        @endif

                                        <!-- Delete Action (if not current user) -->
                                        @if($user->id !== auth()->id())
                                            <form action="{{ route('admin.users.destroy', $user) }}" method="POST" class="d-inline"
                                                  onsubmit="return confirm('Are you sure you want to delete this user?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="admin-btn danger btn-sm" title="Delete User">
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
                                <td colspan="6">
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
