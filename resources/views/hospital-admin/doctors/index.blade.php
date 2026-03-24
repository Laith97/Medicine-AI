@extends('layouts.app')

@section('content')
<div class="admin-page">
<div class="admin-container">

    <!-- Page Header -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-user-md me-2"></i>Manage Doctors
        </h1>
        <a href="{{ route('hospital-admin.doctors.create') }}" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i>Add Doctor
        </a>
    </div>

    <!-- Search and Filter -->
    <div class="card shadow mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('hospital-admin.doctors.index') }}">
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="search">Search</label>
                            <input type="text" class="form-control" id="search" name="search" 
                                   value="{{ request('search') }}" placeholder="Search by name or email">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="specialty">Medical Specialty</label>
                            <select class="form-control" id="specialty" name="specialty">
                                <option value="">All Specialties</option>
                                <option value="Cardiology" {{ request('specialty') == 'Cardiology' ? 'selected' : '' }}>Cardiology</option>
                                <option value="Pediatrics" {{ request('specialty') == 'Pediatrics' ? 'selected' : '' }}>Pediatrics</option>
                                <option value="Internal Medicine" {{ request('specialty') == 'Internal Medicine' ? 'selected' : '' }}>Internal Medicine</option>
                                <option value="General Practitioner" {{ request('specialty') == 'General Practitioner' ? 'selected' : '' }}>General Practitioner</option>
                                <option value="Dermatology" {{ request('specialty') == 'Dermatology' ? 'selected' : '' }}>Dermatology</option>
                                <option value="Emergency Medicine" {{ request('specialty') == 'Emergency Medicine' ? 'selected' : '' }}>Emergency Medicine</option>
                                <option value="Neurology" {{ request('specialty') == 'Neurology' ? 'selected' : '' }}>Neurology</option>
                                <option value="Orthopedic Surgery" {{ request('specialty') == 'Orthopedic Surgery' ? 'selected' : '' }}>Orthopedic Surgery</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label for="status">Status</label>
                            <select class="form-control" id="status" name="status">
                                <option value="" {{ request('status') === '' ? 'selected' : '' }}>Active Only</option>
                                <option value="all" {{ request('status') === 'all' ? 'selected' : '' }}>All Statuses</option>
                                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search me-1"></i>Filter
                                </button>
                                <a href="{{ route('hospital-admin.doctors.index') }}" class="btn btn-secondary">
                                    <i class="fas fa-times me-1"></i>Clear
                                </a>
                                <a href="{{ route('hospital-admin.departments.index') }}" class="btn btn-outline-info">
                                    <i class="fas fa-building me-1"></i>Manage Departments
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-left-primary shadow h-100">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col me-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total Doctors
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $doctors->total() }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-user-md fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-left-success shadow h-100">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col me-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Active Doctors
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ $doctors->filter(function($doctor) { return $doctor->doctor && $doctor->doctor->is_active; })->count() }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-left-info shadow h-100">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col me-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Total Appointments
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ $doctors->sum(function($doctor) { return $doctor->doctor ? $doctor->doctor->appointments()->count() : 0; }) }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-calendar-check fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-left-warning shadow h-100">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col me-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                This Month
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ $doctors->sum(function($doctor) { 
                                    return $doctor->doctor ? $doctor->doctor->appointments()
                                        ->where('appointment_date', '>=', now()->startOfMonth())
                                        ->count() : 0; 
                                }) }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-calendar-alt fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Doctors Table -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h6 class="m-0 fw-bold text-primary">
                Doctors ({{ $doctors->total() }} total)
            </h6>
        </div>
        <div class="admin-card-body">
            @if($doctors->count() > 0)
                <div class="admin-table-container">
                    <table class="admin-table doctors-table">
                        <thead>
                            <tr>
                                <th style="width: 30%;">Doctor</th>
                                <th style="width: 25%;">Contact</th>
                                <th style="width: 20%;">Specialty</th>
                                <th style="width: 15%;">Status</th>
                                <th style="width: 10%;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($doctors as $doctor)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="me-3">
                                                @if($doctor->doctor && $doctor->doctor->is_active)
                                                    <div class="user-avatar bg-success">
                                                        <i class="fas fa-user-md"></i>
                                                    </div>
                                                @else
                                                    <div class="user-avatar bg-secondary">
                                                        <i class="fas fa-user-slash"></i>
                                                    </div>
                                                @endif
                                            </div>
                                            <div class="user-info">
                                                <h6 class="mb-1">{{ $doctor->name }}</h6>
                                                <small class="text-muted">
                                                    ID: #{{ str_pad($doctor->id, 4, '0', STR_PAD_LEFT) }}
                                                </small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="contact-info">
                                            <div class="mb-1">
                                                <i class="fas fa-envelope text-primary me-2"></i>
                                                {{ $doctor->email }}
                                            </div>
                                            @if($doctor->phone)
                                                <div>
                                                    <i class="fas fa-phone text-success me-2"></i>
                                                    {{ $doctor->phone }}
                                                </div>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        @if($doctor->doctor && $doctor->doctor->specialty)
                                            <span class="admin-badge primary">
                                                {{ $doctor->doctor->specialty->name }}
                                            </span>
                                        @else
                                            <span class="text-muted">Not set</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if($doctor->doctor && $doctor->doctor->is_active)
                                            <span class="admin-badge success">
                                                <i class="fas fa-check"></i>Active
                                            </span>
                                        @else
                                            <span class="admin-badge secondary">
                                                <i class="fas fa-pause"></i>Inactive
                                            </span>
                                        @endif
                                        
                                        <div class="mt-1">
                                            <small class="text-muted">
                                                Joined {{ $doctor->created_at->format('M Y') }}
                                            </small>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="admin-actions">
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" 
                                                        data-bs-toggle="dropdown" aria-expanded="false" 
                                                        id="dropdownMenuButton{{ $doctor->id }}">
                                                    <i class="fas fa-ellipsis-v"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li><a class="dropdown-item" href="{{ route('hospital-admin.doctors.show', $doctor) }}">
                                                        <i class="fas fa-eye me-2"></i>View Details
                                                    </a></li>
                                                    <li><a class="dropdown-item text-primary fw-bold" href="#" 
                                                       onclick="loginAsDoctor({{ $doctor->id }}, '{{ $doctor->name }}')"
                                                       style="background-color: rgba(0,123,255,0.1);">
                                                        <i class="fas fa-sign-in-alt me-2"></i>Login as Doctor
                                                    </a></li>
                                                    <form id="login-as-form-{{ $doctor->id }}" method="POST" action="{{ route('hospital-admin.doctors.login-as', $doctor) }}" style="display: none;">
                                                        @csrf
                                                    </form>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li><a class="dropdown-item" href="{{ route('hospital-admin.doctors.edit', $doctor) }}">
                                                        <i class="fas fa-edit me-2"></i>Edit
                                                    </a></li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <form method="POST" action="{{ route('hospital-admin.doctors.toggle-status', $doctor) }}" 
                                                              style="display: inline;" class="w-100">
                                                            @csrf
                                                            @method('PATCH')
                                                            <button type="submit" class="dropdown-item w-100 text-start">
                                                                @if($doctor->doctor && $doctor->doctor->is_active)
                                                                    <i class="fas fa-pause me-2"></i>Deactivate
                                                                @else
                                                                    <i class="fas fa-play me-2"></i>Activate
                                                                @endif
                                                            </button>
                                                        </form>
                                                    </li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <form method="POST" action="{{ route('hospital-admin.doctors.destroy', $doctor) }}" 
                                                              style="display: inline;" class="w-100"
                                                              onsubmit="return confirm('Are you sure you want to deactivate this doctor? This will hide them from the active doctors list.')">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="dropdown-item text-danger w-100 text-start">
                                                                <i class="fas fa-user-slash me-2"></i>Deactivate
                                                            </button>
                                                        </form>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="admin-pagination">
                    {{ $doctors->appends(request()->query())->links() }}
                </div>
            @else
                <div class="text-center py-5">
                    @if(request()->hasAny(['search', 'status']))
                        <!-- Search/Filter Results Empty State -->
                        <div class="row justify-content-center">
                            <div class="col-md-6">
                                <i class="fas fa-search fa-3x text-warning mb-4"></i>
                                <h4 class="text-warning">No Doctors Found</h4>
                                <p class="text-muted mb-4">No doctors match your search criteria. Try adjusting your filters or search terms.</p>
                                <a href="{{ route('hospital-admin.doctors.index') }}" class="btn btn-outline-primary">
                                    <i class="fas fa-times me-2"></i>Clear Filters
                                </a>
                            </div>
                        </div>
                    @else
                        <!-- No Doctors Added Yet State -->
                        <div class="row justify-content-center mb-4">
                            <div class="col-md-8">
                                <div class="card border-0 shadow-sm">
                                    <div class="card-body text-center p-5">
                                        <i class="fas fa-user-md fa-4x text-primary mb-4"></i>
                                        <h3 class="text-primary mb-3">Build Your Medical Team</h3>
                                        <p class="text-muted mb-4 lead">
                                            Get started by adding doctors to your hospital. Create doctor accounts 
                                            and give them access to manage appointments, patients, and AI medical tools.
                                        </p>
                                        <a href="{{ route('hospital-admin.doctors.create') }}" class="btn btn-primary btn-lg">
                                            <i class="fas fa-plus me-2"></i>Add Your First Doctor
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Feature Preview -->
                        <div class="row">
                            <div class="col-md-4 mb-4">
                                <div class="card h-100 border-0 shadow-sm">
                                    <div class="card-body text-center p-4">
                                        <i class="fas fa-user-plus fa-3x text-info mb-3"></i>
                                        <h6 class="font-weight-bold">Simple Doctor Setup</h6>
                                        <p class="small text-muted mb-0">Add doctors with just name, email, phone, specialty, and password</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 mb-4">
                                <div class="card h-100 border-0 shadow-sm">
                                    <div class="card-body text-center p-4">
                                        <i class="fas fa-calendar-check fa-3x text-success mb-3"></i>
                                        <h6 class="font-weight-bold">Appointment Management</h6>
                                        <p class="small text-muted mb-0">Doctors can schedule, manage and track patient appointments</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 mb-4">
                                <div class="card h-100 border-0 shadow-sm">
                                    <div class="card-body text-center p-4">
                                        <i class="fas fa-robot fa-3x text-warning mb-3"></i>
                                        <h6 class="font-weight-bold">AI Medical Assistant</h6>
                                        <p class="small text-muted mb-0">Access to AI-powered medical analysis and diagnosis tools</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            @endif
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

/* Fix dropdown visibility issues - CRITICAL FIXES */
.admin-card-body {
    overflow: visible !important;
    position: relative;
    z-index: 1;
}

.admin-table-container {
    overflow: visible !important;
    position: static !important;
}

.table-responsive {
    overflow: visible !important;
}

.admin-table {
    overflow: visible !important;
}

.admin-table tbody {
    overflow: visible !important;
}

.admin-table tbody tr {
    position: static !important;
    overflow: visible !important;
}

.admin-table tbody td {
    position: static !important;
    overflow: visible !important;
}

.admin-table tbody td:last-child {
    overflow: visible !important;
    position: relative;
}

/* Dropdown positioning */
.dropdown {
    position: static !important;
}

.dropdown-menu {
    position: fixed !important;
    z-index: 999999 !important;
    min-width: 200px !important;
    max-height: 300px !important;
    overflow-y: auto !important;
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
    border: 1px solid rgba(0,0,0,.125) !important;
    background: white !important;
}

/* Fix for last column dropdowns */
.table td:last-child .dropdown-menu {
    right: 10px !important;
    left: auto !important;
}

/* Ensure dropdown shows on click */
.dropdown-menu.show {
    display: block !important;
}

/* Minimal dropdown fixes */
.dropdown-item {
    white-space: nowrap !important;
    padding: 0.5rem 1rem !important;
    border: none !important;
    background: transparent !important;
    width: 100% !important;
    text-align: left !important;
    display: block !important;
}

.dropdown-item:hover, .dropdown-item:focus {
    background-color: #f8f9fa !important;
    color: #212529 !important;
}

/* Make sure pagination doesn't interfere */
.admin-pagination {
    position: relative;
    z-index: 1;
    margin-top: 1rem;
    clear: both;
}

/* Additional dropdown fixes */
.admin-actions {
    position: relative;
    z-index: 100;
}

.dropdown-toggle::after {
    display: none !important;
}

/* Ensure card doesn't clip content */
.admin-card {
    overflow: visible !important;
}

/* Force visibility */
body .dropdown-menu {
    position: fixed !important;
    z-index: 999999 !important;
}
</style>

<script>
function loginAsDoctor(doctorId, doctorName) {
    // console.log('loginAsDoctor called with:', doctorId, doctorName);
    
    if (confirm('Are you sure you want to login as Dr. ' + doctorName + '? You will be redirected to their dashboard.')) {
        // console.log('User confirmed, submitting form...');
        const form = document.getElementById('login-as-form-' + doctorId);
        if (form) {
            // console.log('Form found, submitting...');
            form.submit();
        } else {
            // console.error('Form not found:', 'login-as-form-' + doctorId);
            alert('Error: Form not found. Please refresh the page and try again.');
        }
    } else {
        // console.log('User cancelled');
    }
}


</script>

@endsection

<style>
/* Summary Cards */
.border-left-primary {
    border-left: 0.25rem solid #4e73df !important;
}

.border-left-success {
    border-left: 0.25rem solid #1cc88a !important;
}

.border-left-info {
    border-left: 0.25rem solid #36b9cc !important;
}

.border-left-warning {
    border-left: 0.25rem solid #f6c23e !important;
}

/* Doctor Avatar */
.avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
}

/* Table Styling */
.table {
    margin-bottom: 0;
}

.table th {
    border-top: none;
    font-weight: 600;
    background-color: #f8f9fc !important;
    color: #5a5c69;
    padding: 12px 15px;
    font-size: 13px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.table td {
    padding: 15px;
    vertical-align: middle;
    border-top: 1px solid #e3e6f0;
}

.table-striped tbody tr:nth-of-type(odd) {
    background-color: rgba(0,0,0,.02);
}

/* Badge Styling */
.badge {
    font-size: 11px;
    font-weight: 600;
    padding: 6px 12px;
    border-radius: 4px;
}

.badge-lg {
    font-size: 12px;
    padding: 8px 16px;
    border-radius: 6px;
}

.badge-success {
    background-color: #1cc88a;
    color: white;
}

.badge-secondary {
    background-color: #858796;
    color: white;
}

.badge-primary {
    background-color: #4e73df;
    color: white;
}

/* Contact Info */
.contact-info {
    font-size: 13px;
}

.contact-info i {
    width: 16px;
    text-align: center;
}

/* Card Styling */
.card-header {
    background-color: #f8f9fc;
    border-bottom: 1px solid #e3e6f0;
    font-weight: 600;
}

/* Button Styling */
.btn-outline-primary {
    color: #4e73df;
    border-color: #4e73df;
}

.btn-outline-primary:hover {
    color: white;
    background-color: #4e73df;
    border-color: #4e73df;
}

/* Dropdown Menu */
.dropdown-menu {
    border: 1px solid #e3e6f0;
    box-shadow: 0 .15rem 1.75rem 0 rgba(58,59,69,.15);
    border-radius: .35rem;
}

.dropdown-item {
    font-size: 13px;
    padding: 8px 16px;
}

.dropdown-item:hover {
    background-color: #eaecf4;
    color: #3a3b45;
}
</style>