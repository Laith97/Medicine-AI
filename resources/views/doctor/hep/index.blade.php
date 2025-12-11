@extends('master')

@section('title', 'HEP Programs')

@section('content')
<div class="dashboard-container">
    <div class="container">
        <!-- Header -->
        <div class="dashboard-header py-2 border-bottom">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2>Home Exercise Programs</h2>
                    <p class="mb-0">Create and manage HEP programs for your patients</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('doctor.hep.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Create HEP Program
                    </a>
                </div>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="row mt-4">
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="stats-card">
                    <div class="stats-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                        <i class="fas fa-clipboard-list"></i>
                    </div>
                    <p class="stats-number">{{ $stats['total_programs'] }}</p>
                    <p class="stats-label">Total Programs</p>
                </div>
            </div>

            <div class="col-lg-3 col-md-6 mb-4">
                <div class="stats-card">
                    <div class="stats-icon" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                        <i class="fas fa-play-circle"></i>
                    </div>
                    <p class="stats-number">{{ $stats['active_programs'] }}</p>
                    <p class="stats-label">Active Programs</p>
                </div>
            </div>

            <div class="col-lg-3 col-md-6 mb-4">
                <div class="stats-card">
                    <div class="stats-icon" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                        <i class="fas fa-user-check"></i>
                    </div>
                    <p class="stats-number">{{ $stats['assigned_programs'] }}</p>
                    <p class="stats-label">Assigned to Patients</p>
                </div>
            </div>

            <div class="col-lg-3 col-md-6 mb-4">
                <div class="stats-card">
                    <div class="stats-icon" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <p class="stats-number">{{ $stats['completed_programs'] }}</p>
                    <p class="stats-label">Completed Programs</p>
                </div>
            </div>
        </div>

        <!-- Programs Table -->
        <div class="table-card">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h6><i class="fas fa-dumbbell me-2"></i>Your HEP Programs</h6>
                <div class="d-flex gap-2">
                    <input type="text" id="searchInput" class="form-control form-control-sm" placeholder="Search programs..." style="width: 200px;">
                    <select id="statusFilter" class="form-select form-select-sm" style="width: 150px;">
                        <option value="">All Status</option>
                        <option value="draft">Draft</option>
                        <option value="active">Active</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover" id="programsTable">
                    <thead class="table-dark">
                        <tr>
                            <th>Program</th>
                            <th>Patient</th>
                            <th>Diagnosis</th>
                            <th>Status</th>
                            <th>Duration</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($programs as $program)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="flex-grow-1">
                                            <h6 class="mb-0">{{ $program->title }}</h6>
                                            <small class="text-muted">{{ Str::limit($program->description, 50) }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    @if($program->patient)
                                        <div>
                                            <strong>{{ $program->patient->name }}</strong>
                                            @if($program->hepAssignments->count() > 0)
                                                <br><small class="text-success"><i class="fas fa-check-circle me-1"></i>Assigned</small>
                                            @else
                                                <br><small class="text-muted"><i class="fas fa-clock me-1"></i>Not Assigned</small>
                                            @endif
                                        </div>
                                    @else
                                        <span class="text-muted">No patient</span>
                                    @endif
                                </td>
                                <td>
                                    @if($program->diagnosis)
                                        <span class="badge bg-info">{{ $program->diagnosis->diagnosis_name }}</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-{{ $program->status === 'active' ? 'success' : ($program->status === 'draft' ? 'warning' : ($program->status === 'completed' ? 'primary' : 'secondary')) }}">
                                        {{ ucfirst($program->status) }}
                                    </span>
                                </td>
                                <td>
                                    <small>{{ $program->duration_weeks }} weeks</small>
                                    <br>
                                    <small class="text-muted">{{ $program->hepExercises->count() }} exercises</small>
                                </td>
                                <td>
                                    <small>{{ $program->created_at->format('M j, Y') }}</small>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('doctor.hep.show', $program) }}" class="btn btn-sm btn-outline-primary" title="View">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ route('doctor.hep.edit', $program) }}" class="btn btn-sm btn-outline-secondary" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        @if($program->hepAssignments->isEmpty())
                                            <button type="button" class="btn btn-sm btn-outline-success assign-program-btn"
                                                    data-program-id="{{ $program->id }}"
                                                    data-program-title="{{ $program->title }}"
                                                    title="Assign to Patient">
                                                <i class="fas fa-user-plus"></i>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-5">
                                    <i class="fas fa-dumbbell fa-3x text-muted mb-3"></i>
                                    <h5 class="text-muted">No HEP programs found</h5>
                                    <p class="text-muted mb-3">Get started by creating your first HEP program for a patient.</p>
                                    <a href="{{ route('doctor.hep.create') }}" class="btn btn-primary">
                                        <i class="fas fa-plus me-2"></i>Create Your First HEP Program
                                    </a>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if($programs->hasPages())
                <div class="d-flex justify-content-center mt-4">
                    {{ $programs->appends(request()->query())->links() }}
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Assign Program Modal -->
<div class="modal fade" id="assignProgramModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Assign HEP Program</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="assignProgramForm">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="assign_patient_id" class="form-label">Select Patient</label>
                        <select class="form-select" id="assign_patient_id" name="patient_id" required>
                            <option value="">Choose a patient...</option>
                            <!-- Patients will be loaded via AJAX -->
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="assign_notes" class="form-label">Assignment Notes (Optional)</label>
                        <textarea class="form-control" id="assign_notes" name="notes" rows="3" placeholder="Any special instructions for the patient..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Assign Program</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('styles')
<style>
.stats-card {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    border: none;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.stats-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
}

.stats-icon {
    width: 50px;
    height: 50px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.2rem;
    margin-bottom: 1rem;
}

.stats-number {
    font-size: 2rem;
    font-weight: bold;
    color: #2d3748;
    margin: 0;
}

.stats-label {
    color: #718096;
    font-size: 0.9rem;
    margin: 0;
}

.table-card {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    margin-top: 2rem;
}

.badge {
    font-size: 0.75rem;
}
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const statusFilter = document.getElementById('statusFilter');
    const programsTable = document.getElementById('programsTable');

    // Search functionality
    searchInput.addEventListener('input', filterPrograms);
    statusFilter.addEventListener('change', filterPrograms);

    function filterPrograms() {
        const searchTerm = searchInput.value.toLowerCase();
        const statusValue = statusFilter.value.toLowerCase();
        const rows = programsTable.querySelectorAll('tbody tr');

        rows.forEach(row => {
            if (row.cells.length === 1) return; // Skip empty state row

            const programTitle = row.cells[0].textContent.toLowerCase();
            const patientName = row.cells[1].textContent.toLowerCase();
            const status = row.cells[3].textContent.toLowerCase();

            const matchesSearch = programTitle.includes(searchTerm) || patientName.includes(searchTerm);
            const matchesStatus = !statusValue || status.includes(statusValue);

            row.style.display = matchesSearch && matchesStatus ? '' : 'none';
        });
    }

    // Assign program functionality
    const assignButtons = document.querySelectorAll('.assign-program-btn');
    const assignModal = new bootstrap.Modal(document.getElementById('assignProgramModal'));
    const assignForm = document.getElementById('assignProgramForm');

    assignButtons.forEach(button => {
        button.addEventListener('click', function() {
            const programId = this.dataset.programId;
            const programTitle = this.dataset.programTitle;

            // Update modal title
            document.querySelector('#assignProgramModal .modal-title').textContent =
                `Assign "${programTitle}" to Patient`;

            // Update form action
            assignForm.action = `/doctor/hep/${programId}/assign`;

            // Load patients (you might want to cache this)
            loadPatientsForAssignment();

            assignModal.show();
        });
    });

    function loadPatientsForAssignment() {
        const patientSelect = document.getElementById('assign_patient_id');
        patientSelect.innerHTML = '<option value="">Loading patients...</option>';

        fetch('{{ route("doctor.hep.patients-list") }}')
            .then(response => response.json())
            .then(data => {
                let options = '<option value="">Choose a patient...</option>';
                if (data.patients && data.patients.length > 0) {
                    data.patients.forEach(patient => {
                        options += `<option value="${patient.id}">${patient.name} (${patient.email})</option>`;
                    });
                } else {
                    options += '<option value="" disabled>No patients found</option>';
                }
                patientSelect.innerHTML = options;
            })
            .catch(error => {
                console.error('Error loading patients:', error);
                patientSelect.innerHTML = '<option value="">Error loading patients</option>';
            });
    }

    // Handle form submission
    assignForm.addEventListener('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(this);
        const csrfTokenMeta = document.querySelector('meta[name="csrf-token"]');
        const csrfToken = csrfTokenMeta ? csrfTokenMeta.content : '';

        if (!csrfToken) {
            alert('Security token missing. Please refresh the page.');
            return;
        }

        fetch(this.action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                assignModal.hide();
                location.reload(); // Refresh to show updated status
            } else {
                alert('Error: ' + (data.message || 'Failed to assign program'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while assigning the program');
        });
    });
});
</script>
@endpush
@endsection
