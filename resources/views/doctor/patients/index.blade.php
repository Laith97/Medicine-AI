@extends('master')

@section('title', 'My Patients')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/custom-openai.css') }}">
<link rel="stylesheet" href="{{ asset('css/doctor-dashboard.css') }}">

<style>
/* Professional Dashboard Header Styling */
.dashboard-header {
    background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
    border-radius: 15px;
    padding: 2rem;
    margin-bottom: 2rem;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
    border: 1px solid rgba(222, 98, 98, 0.2);
    position: relative;
    overflow: hidden;
}

.dashboard-header::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(135deg, #DE6262 0%, #2c3e50 100%);
}

.dashboard-header h2 {
    color: #ffffff;
    font-weight: 700;
    font-size: 2rem;
    margin-bottom: 0.5rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.dashboard-header h2::before {
    content: '👥';
    font-size: 2rem;
}

.dashboard-header p {
    color: rgba(255, 255, 255, 0.9);
    font-size: 1.1rem;
    font-weight: 500;
    margin-bottom: 0;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .dashboard-header {
        padding: 1.5rem;
        margin-bottom: 1.5rem;
    }
    .dashboard-header h2 {
        font-size: 1.75rem;
    }
    .dashboard-header p {
        font-size: 1rem;
    }
}

/* Patient avatar */
.patient-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 1rem;
}

.patient-avatar-male {
    background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
    color: white;
}

.patient-avatar-female {
    background: linear-gradient(135deg, #e83e8c 0%, #c21e56 100%);
    color: white;
}

.patient-avatar-default {
    background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
    color: white;
}

/* Status badges */
.status-active { background: linear-gradient(135deg, #28a745 0%, #20c997 100%); }
.status-inactive { background: linear-gradient(135deg, #6c757d 0%, #495057 100%); }
.status-new { background: linear-gradient(135deg, #17a2b8 0%, #138496 100%); }

/* Action buttons */
.action-btn {
    width: 32px;
    height: 32px;
    padding: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 6px;
    transition: all 0.2s ease;
}

.action-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
}

/* Table enhancements */
.custom-table tbody tr {
    transition: all 0.2s ease;
}

.custom-table tbody tr:hover {
    background-color: rgba(59, 146, 246, 0.05);
}

/* Search box enhancement */
.search-box {
    border-radius: 10px;
    border: 1px solid #e0e0e0;
    transition: all 0.3s ease;
}

.search-box:focus {
    border-color: #3b92f6;
    box-shadow: 0 0 0 3px rgba(59, 146, 246, 0.1);
}
</style>
@endpush

@section('content')
<div class="dashboard-container">
    <div class="container">
        <!-- Dashboard Header -->
        <div class="dashboard-header">
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <div>
                    <h2>My Patients</h2>
                    <p>Your assigned patient profiles and records</p>
                </div>
                <a href="{{ route('doctor.patients.create') }}" class="btn btn-light btn-lg mt-3 mt-md-0">
                    <i class="fas fa-user-plus me-2"></i>Add New Patient
                </a>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="row mb-4">
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="stats-card">
                    <div class="stats-icon" style="background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);">
                        <i class="fas fa-users"></i>
                    </div>
                    <p class="stats-number">{{ $patients->total() }}</p>
                    <p class="stats-label">Total Patients</p>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="stats-card">
                    <div class="stats-icon" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%);">
                        <i class="fas fa-user-check"></i>
                    </div>
                    <p class="stats-number">{{ collect($patients->items())->filter(fn($p) => ($p->is_active ?? true))->count() }}</p>
                    <p class="stats-label">Active Patients</p>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="stats-card">
                    <div class="stats-icon" style="background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <p class="stats-number">{{ collect($patients->items())->filter(fn($p) => $p->appointments->isNotEmpty())->count() }}</p>
                    <p class="stats-label">With Appointments</p>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="stats-card clickable-card" onclick="window.location.href='{{ route('doctor.patients.create') }}'">
                    <div class="stats-icon" style="background: linear-gradient(135deg, #6f42c1 0%, #e83e8c 100%);">
                        <i class="fas fa-plus"></i>
                    </div>
                    <p class="stats-number">+</p>
                    <p class="stats-label">Add Patient</p>
                </div>
            </div>
        </div>

        <!-- Search & Filters -->
        <div class="table-card mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="mb-0"><i class="fas fa-search me-2"></i>Search & Filters</h6>
            </div>
            <form method="GET" action="{{ route('doctor.patients.index') }}" class="row g-3">
                <div class="col-md-4">
                    <input type="text" name="search" class="form-control search-box"
                           placeholder="Search by name, email, or phone..."
                           value="{{ request('search') }}">
                </div>
                <div class="col-md-2">
                    <select name="gender" class="form-select search-box">
                        <option value="">All Genders</option>
                        <option value="male" {{ request('gender') == 'male' ? 'selected' : '' }}>Male</option>
                        <option value="female" {{ request('gender') == 'female' ? 'selected' : '' }}>Female</option>
                        <option value="other" {{ request('gender') == 'other' ? 'selected' : '' }}>Other</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="status" class="form-select search-box">
                        <option value="">All Status</option>
                        <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="sort" class="form-select search-box">
                        <option value="newest" {{ request('sort') == 'newest' ? 'selected' : '' }}>Newest First</option>
                        <option value="oldest" {{ request('sort') == 'oldest' ? 'selected' : '' }}>Oldest First</option>
                        <option value="name" {{ request('sort') == 'name' ? 'selected' : '' }}>Name A-Z</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary-custom flex-grow-1">
                        <i class="fas fa-filter me-1"></i>Filter
                    </button>
                    <a href="{{ route('doctor.patients.index') }}" class="btn btn-secondary">
                        <i class="fas fa-times"></i>
                    </a>
                </div>
            </form>
        </div>

        <!-- Patients List -->
        @if($patients->count() > 0)
            <div class="table-card">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="mb-0"><i class="fas fa-users me-2"></i>Patients ({{ $patients->total() }})</h6>
                </div>
                <div class="table-responsive">
                    <table class="table custom-table mb-0">
                        <thead>
                            <tr>
                                <th>Patient</th>
                                <th>Age / Gender</th>
                                <th>Contact</th>
                                <th>Last Visit</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($patients as $patient)
                                <tr>
                                    <!-- Patient Info -->
                                    <td>
                                        <div class="d-flex align-items-center">
                                            @php
                                                $avatarClass = 'patient-avatar-default';
                                                $initials = '??';
                                                if ($patient->gender == 'male') {
                                                    $avatarClass = 'patient-avatar-male';
                                                } elseif ($patient->gender == 'female') {
                                                    $avatarClass = 'patient-avatar-female';
                                                }
                                                $initials = collect(explode(' ', $patient->name))->map(function($word) {
                                                    return substr($word, 0, 1);
                                                })->take(2)->join('');
                                                if (strlen($initials) < 2) {
                                                    $initials = substr($patient->name, 0, 2);
                                                }
                                                $initials = strtoupper($initials);
                                            @endphp
                                            <div class="patient-avatar {{ $avatarClass }} me-3">
                                                {{ $initials }}
                                            </div>
                                            <div>
                                                <div class="fw-medium">{{ $patient->name }}</div>
                                                <small class="text-muted">ID: {{ $patient->id }}</small>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Age / Gender -->
                                    <td>
                                        @if($patient->age)
                                            <span class="fw-medium">{{ $patient->age }} years</span>
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                        <br>
                                        <small class="text-muted">{{ ucfirst($patient->gender ?? 'Not specified') }}</small>
                                    </td>

                                    <!-- Contact -->
                                    <td>
                                        <div class="d-flex flex-column">
                                            <span>{{ $patient->email }}</span>
                                            @if($patient->phone)
                                                <small class="text-muted">{{ $patient->phone }}</small>
                                            @else
                                                <small class="text-muted">No phone</small>
                                            @endif
                                        </div>
                                    </td>

                                    <!-- Last Visit -->
                                    <td>
                                        @if($patient->appointments->first())
                                            <div class="fw-medium">
                                                {{ $patient->appointments->first()->appointment_date->format('M j, Y') }}
                                            </div>
                                            <small class="text-muted">
                                                {{ $patient->appointments->first()->appointment_date->diffForHumans() }}
                                            </small>
                                        @else
                                            <span class="badge bg-secondary">No visits</span>
                                        @endif
                                    </td>

                                    <!-- Status -->
                                    <td>
                                        @if($patient->is_active ?? true)
                                            <span class="badge status-active">Active</span>
                                        @else
                                            <span class="badge status-inactive">Inactive</span>
                                        @endif
                                    </td>

                                    <!-- Actions -->
                                    <td>
                                        <div class="d-flex gap-1">
                                            <a href="{{ route('doctor.patients.show', $patient->id) }}"
                                               class="btn btn-sm btn-outline-primary action-btn" title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('doctor.patients.edit', $patient->id) }}"
                                               class="btn btn-sm btn-outline-warning action-btn" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="{{ route('ai.voice-assistant.index', ['patient' => $patient->id]) }}"
                                               class="btn btn-sm btn-outline-success action-btn" title="Start Consultation">
                                                <i class="fas fa-microphone"></i>
                                            </a>
                                            <button type="button"
                                                    class="btn btn-sm btn-outline-danger action-btn"
                                                    title="Delete"
                                                    onclick="deletePatient({{ $patient->id }}, '{{ addslashes($patient->name) }}')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                @if($patients->hasPages())
                    <div class="d-flex justify-content-center mt-4">
                        {{ $patients->links() }}
                    </div>
                @endif
            </div>
        @else
            <div class="table-card text-center py-5">
                <div class="empty-icon mb-3">
                    <i class="fas fa-users"></i>
                </div>
                <h5>No patients found</h5>
                <p class="text-muted">
                    @if(request('search') || request('gender') || request('status'))
                        No patients match your search criteria.
                    @else
                        You haven't added any patients yet.
                    @endif
                </p>
                <a href="{{ route('doctor.patients.create') }}" class="btn btn-primary-custom">
                    <i class="fas fa-user-plus me-2"></i>Add Your First Patient
                </a>
            </div>
        @endif
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">Delete Patient</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete <strong id="deletePatientName"></strong>?</p>
                <p class="text-danger mb-0"><i class="fas fa-exclamation-triangle me-2"></i>This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form id="deleteForm" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash me-2"></i>Delete Patient
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function deletePatient(id, name) {
    document.getElementById('deletePatientName').textContent = name;
    document.getElementById('deleteForm').action = '/doctor/patients/' + id;
    const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
    modal.show();
}
</script>
@endsection
