@extends('master')

@section('title', 'Physical Therapy - HEP Programs')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/doctor-design-system.css') }}">
<link rel="stylesheet" href="{{ asset('css/doctor-dashboard.css') }}">
<link rel="stylesheet" href="{{ asset('css/cases-overview.css') }}">
<style>
/* Fix: table headers should scroll with content, not stick below toolbar (as cases-overview) */
#programsTable thead th {
    position: static !important;
    top: auto !important;
    background: linear-gradient(135deg, #f8f9fa 0%, #f1f5f9 100%) !important;
    color: #64748b !important;
}
</style>
@endpush

@section('content')
<div class="container-fluid" style="background-color: var(--bg-secondary, #f8f9fa);">
    <div class="container py-4">

        <!-- Page Header -->
        <div class="dashboard-header cases-header-compact">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <h2><i class="fas fa-dumbbell me-2"></i>Physical Therapy (Home Exercise Programs)</h2>
                    <p>Create and manage HEP programs for your patients</p>
                </div>
                <a href="{{ route('doctor.hep.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Create HEP Program
                </a>
            </div>
        </div>

        <!-- Stats Cards - compact horizontal -->
        <div class="row g-2 mb-3 cases-stats-compact">
            <div class="col-lg-3 col-md-6 col-6">
                <div class="stats-card stats-card--compact">
                    <div class="stats-icon stats-icon--sm" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                        <i class="fas fa-clipboard-list"></i>
                    </div>
                    <div class="stats-text">
                        <p class="stats-number">{{ $stats['total_programs'] }}</p>
                        <p class="stats-label">Total Programs</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 col-6">
                <div class="stats-card stats-card--compact">
                    <div class="stats-icon stats-icon--sm" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                        <i class="fas fa-play-circle"></i>
                    </div>
                    <div class="stats-text">
                        <p class="stats-number">{{ $stats['active_programs'] }}</p>
                        <p class="stats-label">Active Programs</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 col-6">
                <div class="stats-card stats-card--compact">
                    <div class="stats-icon stats-icon--sm" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                        <i class="fas fa-user-check"></i>
                    </div>
                    <div class="stats-text">
                        <p class="stats-number">{{ $stats['assigned_programs'] }}</p>
                        <p class="stats-label">Assigned to Patients</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 col-6">
                <div class="stats-card stats-card--compact">
                    <div class="stats-icon stats-icon--sm" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stats-text">
                        <p class="stats-number">{{ $stats['completed_programs'] }}</p>
                        <p class="stats-label">Completed Programs</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Programs Panel -->
        <div class="card border-0 shadow-sm cases-panel">
            <div class="cases-toolbar">
                <div class="cases-toolbar__title">
                    <h5 class="mb-0 fw-semibold"><i class="fas fa-dumbbell me-2 text-primary"></i>Your HEP Programs</h5>
                    <span class="cases-toolbar__meta">— {{ $programs->total() ?? $programs->count() }} programs</span>
                </div>
                <div class="cases-toolbar__controls">
                    <div class="input-group input-group-sm cases-search">
                        <input type="text" class="form-control" id="searchInput" placeholder="Search by name, diagnosis, status..." autocomplete="off">
                        </div>
                    <select id="statusFilter" class="form-select form-select-sm cases-sort" title="Filter by status">
                        <option value="">All Status</option>
                        <option value="draft">Draft</option>
                        <option value="active">Active</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
            </div>

            <div class="card-body p-0">
                <div class="doctor-table-container">
                    <div class="table-responsive">
                        <table class="doctor-table table-hover mb-0" id="programsTable" style="width:100%">
                            <thead style="background: linear-gradient(135deg, #f8f9fa 0%, #f1f5f9 100%);">
                                <tr>
                                    <th style="font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#64748b;padding:0.9rem 1rem;border-bottom:2px solid #e2e8f0;white-space:nowrap"><i class="fas fa-clipboard-list me-1 opacity-60"></i> Program</th>
                                    <th style="font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#64748b;padding:0.9rem 1rem;border-bottom:2px solid #e2e8f0;white-space:nowrap"><i class="fas fa-user me-1 opacity-60"></i> Patient</th>
                                    <th style="font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#64748b;padding:0.9rem 1rem;border-bottom:2px solid #e2e8f0;text-align:center;width:130px">Diagnosis</th>
                                    <th style="font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#64748b;padding:0.9rem 1rem;border-bottom:2px solid #e2e8f0;text-align:center;width:100px">Status</th>
                                    <th style="font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#64748b;padding:0.9rem 1rem;border-bottom:2px solid #e2e8f0;text-align:center;width:110px">Duration</th>
                                    <th style="font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#64748b;padding:0.9rem 1rem;border-bottom:2px solid #e2e8f0;white-space:nowrap"><i class="far fa-calendar me-1 opacity-60"></i> Created</th>
                                    <th style="font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#64748b;padding:0.9rem 1rem;border-bottom:2px solid #e2e8f0;text-align:right;width:140px">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($programs as $program)
                                    <tr data-program-title="{{ strtolower($program->title) }}" data-patient-name="{{ strtolower($program->patient->name ?? '') }}" data-status="{{ strtolower($program->status) }}" class="hep-row">
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <span class="d-flex align-items-center justify-content-center flex-shrink-0" style="width:36px;height:36px;border-radius:10px;background:linear-gradient(135deg,#f8fafc 0%,#f1f5f9 100%);border:1px solid #e2e8f0;color:#64748b;"><i class="fas fa-clipboard-list" style="font-size:0.78rem;"></i></span>
                                                <div class="flex-grow-1 min-w-0">
                                                    <h6 class="mb-0 text-truncate" style="font-size:0.875rem;max-width:220px;" title="{{ $program->title }}">{{ $program->title }}</h6>
                                                    <small class="text-muted">{{ Str::limit($program->description, 50) }}</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            @if($program->patient)
                                                <div>
                                                    <strong style="font-size:0.875rem;">{{ $program->patient->name }}</strong>
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
                                            <i class="fas fa-dumbbell fa-3x text-muted mb-3 d-block"></i>
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
                    @if($programs->count() > 0)
                    <div class="table-footer">
                        <span><i class="fas fa-info-circle me-1"></i> Showing {{ $programs->count() }} of {{ $programs->total() }} programs</span>
                        <span class="d-none d-sm-inline">Use search to filter</span>
                    </div>
                    @endif
                </div>
                @if($programs->hasPages())
                    <div class="d-flex justify-content-center p-3" style="background:#f8fafc;border-top:1px solid #f1f5f9">
                        {{ $programs->appends(request()->query())->links('pagination::bootstrap-5') }}
                    </div>
                @endif
            </div>
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
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const statusFilter = document.getElementById('statusFilter');
    const programsTable = document.getElementById('programsTable');

    function filterPrograms() {
        const searchTerm = (searchInput.value || '').toLowerCase().trim();
        const statusValue = (statusFilter.value || '').toLowerCase().trim();
        // Use same selector as cases-overview: all patient-row style, but for HEP use hep-row + fallback to all rows
        const rows = programsTable.querySelectorAll('tbody tr');
        let visibleCount = 0;
        rows.forEach(row => {
            // Skip empty state row (colspan 7)
            if (row.cells.length === 1 && row.querySelector('td[colspan]')) return;
            // Like cases-overview: check full row text + data attributes
            const title = (row.getAttribute('data-program-title') || '').toLowerCase();
            const patient = (row.getAttribute('data-patient-name') || '').toLowerCase();
            const status = (row.getAttribute('data-status') || '').toLowerCase();
            const rowText = row.textContent.toLowerCase();
            const matchesSearch = !searchTerm || rowText.includes(searchTerm) || title.includes(searchTerm) || patient.includes(searchTerm);
            const matchesStatus = !statusValue || status.includes(statusValue) || rowText.includes(statusValue);
            const show = matchesSearch && matchesStatus;
            row.style.display = show ? '' : 'none';
            if (show) visibleCount++;
        });
        // Handle empty state like cases-overview: toggle table vs empty message
        const hasHepRows = programsTable.querySelectorAll('tbody tr.hep-row').length > 0;
        let emptyState = programsTable.parentElement.querySelector('.search-empty-state');
        const isSearching = !!searchTerm || !!statusValue;
        if (visibleCount === 0 && hasHepRows && isSearching) {
            if (!emptyState) {
                emptyState = document.createElement('div');
                emptyState.className = 'search-empty-state text-center py-4 px-3';
                emptyState.innerHTML = `<i class="fas fa-search text-muted d-block" style="font-size:2rem;margin-bottom:0.75rem;"></i><h6 class="text-muted mb-1">No programs found</h6><p class="text-muted small mb-0">Try adjusting search or filter</p>`;
                programsTable.parentElement.appendChild(emptyState);
            }
            emptyState.style.display = 'block';
            programsTable.style.display = 'none';
        } else {
            if (emptyState) emptyState.style.display = 'none';
            programsTable.style.display = '';
            // Hide the "No HEP programs found" empty row when we have data but filtered to 0, show it only when truly 0 total
            const emptyRow = programsTable.querySelector('tbody tr:not(.hep-row)');
            if (emptyRow) emptyRow.style.display = (hasHepRows ? 'none' : '');
        }
        updateFooterCounts();
        // Also update table-footer counts like cases-overview
        const footer = document.querySelector('.doctor-table-container .table-footer');
        if (footer) {
            const firstSpan = footer.querySelector('span:first-child');
            if (firstSpan) {
                const total = programsTable.querySelectorAll('tbody tr.hep-row').length;
                if (visibleCount !== total && isSearching) firstSpan.textContent = `Showing ${visibleCount} of ${total} programs`;
                else if (total) firstSpan.innerHTML = `<i class="fas fa-info-circle me-1"></i> Showing ${total} programs`;
            }
        }
    }

    function updateFooterCounts() {
        const container = document.querySelector('.doctor-table-container');
        const footer = container ? container.querySelector('.table-footer span:first-child') : null;
        if (!footer || !programsTable) return;
        const visible = Array.from(programsTable.querySelectorAll('tbody tr')).filter(r => r.cells.length > 1 && r.style.display !== 'none').length;
        const total = Array.from(programsTable.querySelectorAll('tbody tr')).filter(r => r.cells.length > 1).length;
        if (visible !== total) footer.textContent = `Showing ${visible} of ${total} programs`;
        else if (total) footer.innerHTML = `<i class="fas fa-info-circle me-1"></i> Showing ${total} programs`;
    }

    if (searchInput) searchInput.addEventListener('input', filterPrograms);
    if (statusFilter) statusFilter.addEventListener('change', filterPrograms);


    // Assign program functionality
    const assignButtons = document.querySelectorAll('.assign-program-btn');
    const assignModalEl = document.getElementById('assignProgramModal');
    const assignModal = assignModalEl ? new bootstrap.Modal(assignModalEl) : null;
    const assignForm = document.getElementById('assignProgramForm');

    assignButtons.forEach(button => {
        button.addEventListener('click', function() {
            const programId = this.dataset.programId;
            const programTitle = this.dataset.programTitle;

            document.querySelector('#assignProgramModal .modal-title').textContent =
                `Assign "${programTitle}" to Patient`;

            assignForm.action = `/doctor/hep/${programId}/assign`;

            loadPatientsForAssignment();

            if (assignModal) assignModal.show();
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

    if (assignForm) {
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
                    if (assignModal) assignModal.hide();
                    location.reload();
                } else {
                    alert('Error: ' + (data.message || 'Failed to assign program'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while assigning the program');
            });
        });
    }
});
</script>
@endpush
