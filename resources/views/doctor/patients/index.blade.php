@extends('master')

@section('title', 'My Patients')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/doctor-design-system.css') }}">
<link rel="stylesheet" href="{{ asset('css/doctor-dashboard.css') }}">
<link rel="stylesheet" href="{{ asset('css/cases-overview.css') }}">

<style>

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
<style>
.app-main {
    background-color: #f8f9fa;
}

/* Professional Filter Panel */
.filter-card {
    padding: 0;
    overflow: hidden;
    border-radius: 16px;
}

.filter-header {
    background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
    padding: 1.1rem 1.5rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 0.75rem;
}

.filter-header-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    background: rgba(255, 255, 255, 0.15);
    border: 1px solid rgba(255, 255, 255, 0.25);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 1.1rem;
    flex-shrink: 0;
}

.filter-body {
    padding: 1.5rem;
}

.filter-label {
    font-size: 0.78rem;
    font-weight: 700;
    color: #495057;
    text-transform: uppercase;
    letter-spacing: 0.4px;
    margin-bottom: 0.4rem;
}

.filter-card .form-control,
.filter-card .form-select {
    border: 1px solid #e0e0e0;
    border-radius: 10px;
    padding: 0.6rem 0.9rem;
    font-size: 0.92rem;
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
}

.filter-card .form-control:focus,
.filter-card .form-select:focus {
    border-color: #DE6262;
    box-shadow: 0 0 0 3px rgba(222, 98, 98, 0.15);
}

.filter-input-group .input-group-text {
    background: #f8f9fa;
    border: 1px solid #e0e0e0;
    border-right: none;
    border-radius: 10px 0 0 10px;
    color: #6c757d;
}

.filter-input-group .form-control {
    border-left: none;
    border-radius: 0 10px 10px 0;
}

.filter-input-group .form-control:focus {
    box-shadow: 0 0 0 3px rgba(222, 98, 98, 0.15);
}

.filter-reset {
    width: 44px;
    height: 44px;
    padding: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border: 1px solid #e0e0e0;
    border-radius: 10px;
    color: #6c757d;
    background: #fff;
    transition: all 0.2s ease;
}

.filter-reset:hover {
    color: #fff;
    background: #DE6262;
    border-color: #DE6262;
}

.filter-body .btn-primary-custom {
    padding: 0.6rem 1.25rem;
    font-size: 0.92rem;
    height: 44px;
}
</style>
<div class="container-fluid" style="background-color: #f8f9fa;">
    <div class="container py-4">
        <div class="dashboard-header cases-header-compact">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2><i class="fas fa-users me-2"></i>My Patients</h2>
                    <p>Your assigned patient profiles and records</p>
                </div>
                <a href="{{ route('doctor.appointments.create') }}" class="btn">
                    <i class="fas fa-user-plus me-2"></i>New Appointment
                </a>
            </div>
        </div>
    <div class="row g-2 mb-3 cases-stats-compact">
            <div class="col-lg-4 col-4">
                <div class="stats-card stats-card--compact">
                    <div class="stats-icon stats-icon--sm" style="background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stats-text">
                        <p class="stats-number">{{ $patients->total() }}</p>
                        <p class="stats-label">Total Patients</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-4">
                <div class="stats-card stats-card--compact">
                    <div class="stats-icon stats-icon--sm" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%);">
                        <i class="fas fa-user-check"></i>
                    </div>
                    <div class="stats-text">
                        <p class="stats-number">{{ collect($patients->items())->filter(fn($p) => $p->is_active)->count() }}</p>
                        <p class="stats-label">Active Patients</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-4">
                <div class="stats-card stats-card--compact">
                    <div class="stats-icon stats-icon--sm" style="background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <div class="stats-text">
                        <p class="stats-number">{{ collect($patients->items())->filter(fn($p) => $p->appointments->isNotEmpty())->count() }}</p>
                        <p class="stats-label">With Appointments</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters + List - unified like cases-overview -->
        <div class="card border-0 shadow-sm cases-panel mb-4">
            <form method="GET" action="{{ route('doctor.patients.index') }}" class="m-0">
                <div class="cases-toolbar">
                    <div class="cases-toolbar__title">
                        <h6 class="mb-0 fw-semibold"><i class="fas fa-users me-2 text-primary"></i>Patients ({{ $patients->total() }})</h6>
                        @if(request()->hasAny(['search', 'gender', 'status', 'sort']))
                            <a href="{{ route('doctor.patients.index') }}" class="btn btn-outline-secondary btn-sm ms-2">
                                <i class="fas fa-times me-1"></i>Clear
                            </a>
                        @endif
                    </div>
                    <div class="cases-toolbar__controls">
                        <div class="input-group input-group-sm cases-search">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                            <input type="text" name="search" id="patientSearch" class="form-control" placeholder="Search by name, age, gender..." value="{{ request('search') }}">
                            <button class="btn btn-outline-secondary" type="button" id="clearSearch" title="Clear search">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <select name="gender" class="form-select form-select-sm cases-sort">
                            <option value="">All Genders</option>
                            <option value="male" {{ request('gender') == 'male' ? 'selected' : '' }}>Male</option>
                            <option value="female" {{ request('gender') == 'female' ? 'selected' : '' }}>Female</option>
                            <option value="other" {{ request('gender') == 'other' ? 'selected' : '' }}>Other</option>
                        </select>
                        <select name="status" class="form-select form-select-sm cases-sort">
                            <option value="">All Status</option>
                            <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                        <select name="sort" class="form-select form-select-sm cases-sort">
                            <option value="newest" {{ request('sort') == 'newest' ? 'selected' : '' }}>Newest</option>
                            <option value="oldest" {{ request('sort') == 'oldest' ? 'selected' : '' }}>Oldest</option>
                            <option value="name" {{ request('sort') == 'name' ? 'selected' : '' }}>Name A-Z</option>
                        </select>
                        <button type="submit" class="doctor-btn doctor-btn-primary doctor-btn-sm">
                            <i class="fas fa-filter me-1"></i>Filter
                        </button>
                        <a href="{{ route('doctor.patients.index') }}" class="doctor-btn doctor-btn-outline doctor-btn-sm" title="Reset">
                            <i class="fas fa-rotate-left"></i>
                        </a>
                    </div>
                </div>
            </form>
        @if($patients->count() > 0)
            <div class="doctor-table-container">
                <div class="table-responsive">
                    <table class="doctor-table mb-0">
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
                                            <span class="doctor-badge doctor-badge-secondary">No visits</span>
                                        @endif
                                    </td>

                                    <!-- Status -->
                                    <td>
                                        @if($patient->is_active)
                                            <span class="doctor-badge doctor-badge-success"><i class="fas fa-check-circle me-1"></i>Active</span>
                                        @else
                                            <span class="doctor-badge doctor-badge-secondary"><i class="fas fa-pause-circle me-1"></i>Inactive</span>
                                        @endif
                                    </td>

                                    <!-- Actions -->
                                    <td>
                                        <div class="d-flex gap-1">
                                            <a href="{{ route('doctor.patients.show', $patient->id) }}"
                                               class="doctor-btn doctor-btn-outline doctor-btn-sm" title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('doctor.patients.edit', $patient->id) }}"
                                               class="doctor-btn doctor-btn-outline doctor-btn-sm" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="{{ route('ai.ambient-listening.index', ['patient' => $patient->id]) }}"
                                               class="doctor-btn doctor-btn-success doctor-btn-sm" title="Start Consultation">
                                                <i class="fas fa-microphone"></i>
                                            </a>
                                            <button type="button"
                                                    class="doctor-btn doctor-btn-danger doctor-btn-sm"
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
            <div class="doctor-empty-state">
                <i class="fas fa-users"></i>
                <h5>No patients found</h5>
                <p>
                    @if(request('search') || request('gender') || request('status'))
                        No patients match your search criteria.
                    @else
                        You haven't added any patients yet. Create appointments to add patients.
                    @endif
                </p>
                <a href="{{ route('doctor.appointments.create') }}" class="doctor-btn doctor-btn-primary">
                    <i class="fas fa-calendar-plus"></i>Create Appointment
                </a>
            </div>
        @endif
    </div>
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
</div>

<script>
function deletePatient(id, name) {
    document.getElementById('deletePatientName').textContent = name;
    document.getElementById('deleteForm').action = '/doctor/patients/' + id;
    const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
    modal.show();
}
document.addEventListener('DOMContentLoaded', function(){
    const s=document.getElementById('patientSearch'), c=document.getElementById('clearSearch');
    if(c && s){ c.addEventListener('click', function(){ s.value=''; s.focus(); s.form.submit(); }); }
});
</script>
@endsection
