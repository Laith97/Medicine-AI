@extends('layouts.admin')

@section('title', 'Manage Users')

{{-- Styles are inline to work with AJAX page loading --}}
<style>
    /* Tab Navigation Styles */
    .user-tabs {
        border-bottom: 2px solid #e9ecef;
        margin-bottom: 1.5rem;
    }

    .user-tabs .nav-link {
        border: none;
        color: #6c757d;
        font-weight: 500;
        padding: 1rem 1.5rem;
        position: relative;
        transition: all 0.3s ease;
    }

    .user-tabs .nav-link:hover {
        color: #495057;
        background: transparent;
    }

    .user-tabs .nav-link.active {
        color: #0d6efd;
        background: transparent;
        border: none;
    }

    .user-tabs .nav-link.active::after {
        content: '';
        position: absolute;
        bottom: -2px;
        left: 0;
        right: 0;
        height: 3px;
        background: linear-gradient(135deg, #0d6efd, #0dcaf0);
        border-radius: 3px 3px 0 0;
    }

    .user-tabs .nav-link .tab-badge {
        background: #e9ecef;
        color: #6c757d;
        padding: 0.25rem 0.6rem;
        border-radius: 20px;
        font-size: 0.75rem;
        margin-left: 0.5rem;
    }

    .user-tabs .nav-link.active .tab-badge {
        background: linear-gradient(135deg, #0d6efd, #0dcaf0);
        color: white;
    }

    /* Stats Cards */
    .user-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
        margin-bottom: 1.5rem;
    }

    .user-stat-card {
        background: white;
        border-radius: 12px;
        padding: 1.25rem;
        display: flex;
        align-items: center;
        gap: 1rem;
        box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        border: 1px solid #e9ecef;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .user-stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    }

    .user-stat-icon {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
    }

    .user-stat-icon.doctors {
        background: linear-gradient(135deg, #d4edda, #c3e6cb);
        color: #155724;
    }

    .user-stat-icon.patients {
        background: linear-gradient(135deg, #cce5ff, #b3d7ff);
        color: #004085;
    }

    .user-stat-info h4 {
        font-size: 1.5rem;
        font-weight: 600;
        margin: 0;
        color: #212529;
    }

    .user-stat-info p {
        font-size: 0.875rem;
        color: #6c757d;
        margin: 0;
    }

    /* Table Styles */
    .user-table-card {
        background: white;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        border: 1px solid #e9ecef;
        overflow: hidden;
    }

    .user-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
    }

    .user-table thead th {
        background: #f8f9fa;
        border-bottom: 2px solid #e9ecef;
        font-weight: 600;
        color: #495057;
        padding: 1rem;
        text-transform: uppercase;
        font-size: 0.75rem;
        letter-spacing: 0.5px;
    }

    .user-table tbody td {
        padding: 1rem;
        border-bottom: 1px solid #e9ecef;
        vertical-align: middle;
    }

    .user-table tbody tr {
        transition: background-color 0.2s ease;
    }

    .user-table tbody tr:hover {
        background-color: #f8f9fa;
    }

    /* User Avatar */
    .user-avatar {
        width: 42px;
        height: 42px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        font-size: 1rem;
        flex-shrink: 0;
    }

    .user-avatar.doctor {
        background: linear-gradient(135deg, #20c997, #12b886);
        color: white;
    }

    .user-avatar.patient {
        background: linear-gradient(135deg, #6610f2, #6f42c1);
        color: white;
    }

    /* Badges */
    .status-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.35rem 0.75rem;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 500;
    }

    .status-badge.active {
        background: #d4edda;
        color: #155724;
    }

    .status-badge.inactive {
        background: #f8d7da;
        color: #721c24;
    }

    .status-badge.pending {
        background: #fff3cd;
        color: #856404;
    }

    .status-badge.verified {
        background: #cce5ff;
        color: #004085;
    }

    /* Action Buttons */
    .action-buttons {
        display: flex;
        gap: 0.5rem;
    }

    .action-btn {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        border: none;
        cursor: pointer;
        transition: all 0.2s ease;
        text-decoration: none;
        font-size: 0.875rem;
    }

    .action-btn.view {
        background: #e7f1ff;
        color: #0d6efd;
    }

    .action-btn.edit {
        background: #fff3cd;
        color: #856404;
    }

    .action-btn.delete {
        background: #f8d7da;
        color: #721c24;
    }

    .action-btn.login {
        background: #d4edda;
        color: #155724;
    }

    .action-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    /* Pagination */
    .user-pagination {
        padding: 1rem 1.5rem;
        border-top: 1px solid #e9ecef;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 1rem;
    }

    .pagination-info {
        font-size: 0.875rem;
        color: #6c757d;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .user-tabs .nav-link {
            padding: 0.75rem 1rem;
            font-size: 0.875rem;
        }

        .user-table thead th,
        .user-table tbody td {
            padding: 0.75rem 0.5rem;
            font-size: 0.875rem;
        }
    }

    /* Empty State */
    .empty-state {
        text-align: center;
        padding: 3rem 1rem;
    }

    .empty-state i {
        font-size: 3rem;
        color: #adb5bd;
        margin-bottom: 1rem;
    }

    .empty-state h5 {
        color: #495057;
        margin-bottom: 0.5rem;
    }

    .empty-state p {
        color: #6c757d;
    }
</style>

@section('content')
<div class="admin-page">
    <div class="admin-container">
        <!-- Header -->
        <div class="admin-header">
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <div>
                    <h1 class="text-white">Manage Users</h1>
                    <p class="mb-0">Manage doctors and patients in your system</p>
                </div>
                <div class="d-flex gap-2 mt-2 mt-md-0">
                    <a href="{{ route('admin.dashboard') }}" class="btn btn-light btn-sm">
                        <i class="bi bi-arrow-left me-1"></i>Back
                    </a>
                    <a href="{{ route('admin.users.create') }}" class="btn btn-success btn-sm">
                        <i class="bi bi-person-plus me-1"></i>Create User
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

        <!-- Stats Cards -->
        <div class="user-stats">
            <div class="user-stat-card">
                <div class="user-stat-icon doctors">
                    <i class="bi bi-person-badge"></i>
                </div>
                <div class="user-stat-info">
                    <h4>{{ $stats['total_doctors'] }}</h4>
                    <p>Total Doctors</p>
                </div>
            </div>
            <div class="user-stat-card">
                <div class="user-stat-icon patients">
                    <i class="bi bi-people"></i>
                </div>
                <div class="user-stat-info">
                    <h4>{{ $stats['total_patients'] }}</h4>
                    <p>Total Patients</p>
                </div>
            </div>
        </div>

        <!-- Tab Content -->
        <div class="user-table-card">
            <!-- Tab Navigation -->
            <ul class="nav user-tabs" id="userTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="doctors-tab" data-bs-toggle="tab" data-bs-target="#doctors" type="button" role="tab">
                        <i class="bi bi-person-badge me-2"></i>Doctors
                        <span class="tab-badge">{{ $stats['total_doctors'] }}</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="patients-tab" data-bs-toggle="tab" data-bs-target="#patients" type="button" role="tab">
                        <i class="bi bi-people me-2"></i>Patients
                        <span class="tab-badge">{{ $stats['total_patients'] }}</span>
                    </button>
                </li>
            </ul>

            <!-- Tab Content -->
            <div class="tab-content" id="userTabsContent">
                <!-- Doctors Tab -->
                <div class="tab-pane fade show active" id="doctors" role="tabpanel">
                    <div class="table-responsive">
                        <table class="user-table">
                            <thead>
                                <tr>
                                    <th style="width: 50px;">#</th>
                                    <th>Doctor</th>
                                    <th>Hospital</th>
                                    <th>Specialty</th>
                                    <th>Status</th>
                                    <th>Joined</th>
                                    <th style="width: 120px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($doctors as $index => $doctor)
                                    <tr>
                                        <td>
                                            <span class="text-muted">{{ $doctors->firstItem() + $index }}</span>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="user-avatar doctor me-3">
                                                    {{ substr($doctor->name, 0, 1) }}
                                                </div>
                                                <div>
                                                    <h6 class="mb-0">{{ $doctor->name }}</h6>
                                                    <small class="text-muted">{{ $doctor->email }}</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            @if($doctor->hospital)
                                                <span class="text-truncate d-block" style="max-width: 150px;" title="{{ $doctor->hospital->name }}">
                                                    {{ Str::limit($doctor->hospital->name, 20) }}
                                                </span>
                                            @else
                                                <span class="text-muted">Independent</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($doctor->doctor && $doctor->doctor->specialty)
                                                <span class="badge bg-light text-dark border">
                                                    {{ $doctor->doctor->specialty->name }}
                                                </span>
                                            @elseif($doctor->setting && $doctor->setting->specialty)
                                                <span class="badge bg-light text-dark border">
                                                    {{ $doctor->setting->specialty }}
                                                </span>
                                            @else
                                                <span class="text-muted">Not set</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="d-flex flex-column gap-1">
                                                @if($doctor->doctor)
                                                    @if($doctor->doctor->is_active)
                                                        <span class="status-badge active">
                                                            <i class="bi bi-check-circle"></i>Active
                                                        </span>
                                                    @else
                                                        <span class="status-badge inactive">
                                                            <i class="bi bi-x-circle"></i>Inactive
                                                        </span>
                                                    @endif
                                                @else
                                                    <span class="status-badge inactive">No Profile</span>
                                                @endif
                                            </div>
                                        </td>
                                        <td>
                                            <small class="text-muted">{{ $doctor->created_at->format('M d, Y') }}</small>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="{{ route('admin.users.show', $doctor) }}" class="action-btn view" title="View">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <a href="{{ route('admin.users.edit', $doctor) }}" class="action-btn edit" title="Edit">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <button type="button" class="action-btn login" title="Login As"
                                                        onclick="loginAsUser({{ $doctor->id }}, '{{ $doctor->name }}', 'doctor')">
                                                    <i class="bi bi-box-arrow-in-right"></i>
                                                </button>
                                                <form action="{{ route('admin.users.destroy', $doctor) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete {{ addslashes($doctor->name) }}? This action cannot be undone.');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="action-btn delete" title="Delete">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7">
                                            <div class="empty-state">
                                                <i class="bi bi-person-badge"></i>
                                                <h5>No Doctors Found</h5>
                                                <p>No doctors have been added yet.</p>
                                                <a href="{{ route('admin.users.create') }}?role=doctor" class="btn btn-primary btn-sm">
                                                    <i class="bi bi-plus-lg me-1"></i>Add First Doctor
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    @if($doctors->hasPages())
                        <div class="user-pagination">
                            <div class="pagination-info">
                                Showing {{ $doctors->firstItem() }} to {{ $doctors->lastItem() }} of {{ $doctors->total() }} doctors
                            </div>
                            <nav>
                                {{ $doctors->appends(['patients_page' => $patients->currentPage()])->links() }}
                            </nav>
                        </div>
                    @endif
                </div>

                <!-- Patients Tab -->
                <div class="tab-pane fade" id="patients" role="tabpanel">
                    <div class="table-responsive">
                        <table class="user-table">
                            <thead>
                                <tr>
                                    <th style="width: 50px;">#</th>
                                    <th>Patient</th>
                                    <th>Primary Doctor</th>
                                    <th>Appointments</th>
                                    <th>Joined</th>
                                    <th style="width: 100px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($patients as $index => $patient)
                                    <tr>
                                        <td>
                                            <span class="text-muted">{{ $patients->firstItem() + $index }}</span>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="user-avatar patient me-3">
                                                    {{ substr($patient->name, 0, 1) }}
                                                </div>
                                                <div>
                                                    <h6 class="mb-0">{{ $patient->name }}</h6>
                                                    <small class="text-muted">{{ $patient->email }}</small>
                                                    @if($patient->age || $patient->gender)
                                                        <small class="text-muted d-block">
                                                            {{ $patient->age ?? '' }} {{ $patient->gender ? ucfirst($patient->gender) : '' }}
                                                        </small>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            @if($patient->primaryDoctor)
                                                <div class="d-flex align-items-center">
                                                    <div class="user-avatar doctor me-2" style="width: 28px; height: 28px; font-size: 0.75rem;">
                                                        {{ substr($patient->primaryDoctor->name, 0, 1) }}
                                                    </div>
                                                    <span>{{ $patient->primaryDoctor->name }}</span>
                                                </div>
                                            @else
                                                <span class="text-muted">Not assigned</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($patient->appointments_count > 0)
                                                <span class="badge bg-primary">
                                                    <i class="bi bi-calendar-check me-1"></i>{{ $patient->appointments_count }}
                                                </span>
                                            @else
                                                <span class="text-muted">No visits</span>
                                            @endif
                                        </td>
                                        <td>
                                            <small class="text-muted">{{ $patient->created_at->format('M d, Y') }}</small>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="{{ route('admin.users.show', $patient) }}" class="action-btn view" title="View">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <a href="{{ route('admin.users.edit', $patient) }}" class="action-btn edit" title="Edit">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <form action="{{ route('admin.users.destroy', $patient) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete {{ addslashes($patient->name) }}? This action cannot be undone.');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="action-btn delete" title="Delete">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6">
                                            <div class="empty-state">
                                                <i class="bi bi-people"></i>
                                                <h5>No Patients Found</h5>
                                                <p>No patients have been added yet.</p>
                                                <a href="{{ route('admin.users.create') }}?role=patient" class="btn btn-primary btn-sm">
                                                    <i class="bi bi-plus-lg me-1"></i>Add First Patient
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    @if($patients->hasPages())
                        <div class="user-pagination">
                            <div class="pagination-info">
                                Showing {{ $patients->firstItem() }} to {{ $patients->lastItem() }} of {{ $patients->total() }} patients
                            </div>
                            <nav>
                                {{ $patients->appends(['doctors_page' => $doctors->currentPage()])->links() }}
                            </nav>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Hidden forms for login-as functionality -->
@foreach($doctors as $doctor)
    <form id="login-as-form-{{ $doctor->id }}" method="POST" action="{{ route('admin.login-as', $doctor) }}" style="display: none;">
        @csrf
    </form>
@endforeach

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Tab persistence
    const activeTab = localStorage.getItem('adminUsersActiveTab');
    if (activeTab) {
        const tabEl = document.querySelector(activeTab);
        if (tabEl) {
            new bootstrap.Tab(tabEl).show();
        }
    }

    // Save active tab on click
    document.querySelectorAll('.user-tabs .nav-link').forEach(link => {
        link.addEventListener('shown.bs.tab', function(e) {
            localStorage.setItem('adminUsersActiveTab', '#' + e.target.getAttribute('data-bs-target'));
        });
    });
});

function loginAsUser(userId, userName, userRole) {
    const roleText = userRole === 'hospital_admin' ? 'Hospital Admin' : 'Doctor';

    if (confirm(`Are you sure you want to login as ${roleText} ${userName}? You will be redirected to their dashboard.`)) {
        const form = document.getElementById('login-as-form-' + userId);
        if (form) {
            form.submit();
        } else {
            alert('Error: Form not found. Please refresh the page and try again.');
        }
    }
}
</script>
@endsection
