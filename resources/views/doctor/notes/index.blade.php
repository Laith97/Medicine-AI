@extends('master')

@section('title', 'My Notes')

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
    <div class="container">
        <div class="dashboard-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2><i class="fas fa-sticky-note me-2"></i>Doctor Notes</h2>
                    <p class="text-muted mb-0">View and manage doctor notes</p>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="container-fluid" style="background-color: #f8f9fa;">
    <div class="container">

        <!-- Filters -->
        <div class="table-card filter-card mb-4">
            <div class="filter-header">
                <div class="d-flex align-items-center gap-3">
                    <div class="filter-header-icon">
                        <i class="fas fa-sliders-h"></i>
                    </div>
                    <div>
                        <h6 class="mb-0 text-white fw-semibold">Search & Filters</h6>
                        <small class="text-white-50">Narrow down your notes</small>
                    </div>
                </div>
                @if(request()->hasAny(['search', 'patient_id', 'note_type', 'date_from', 'date_to']))
                    <a href="{{ route('doctor.notes.index') }}" class="btn btn-light btn-sm fw-semibold">
                        <i class="fas fa-times me-1"></i>Clear All
                    </a>
                @endif
            </div>
            <div class="filter-body">
                <form method="GET" action="{{ route('doctor.notes.index') }}" class="row g-3 align-items-end">
                    <div class="col-md-3 col-sm-6">
                        <label class="filter-label" for="filter-search">Search</label>
                        <div class="input-group filter-input-group">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                            <input type="text" id="filter-search" name="search" class="form-control"
                                   placeholder="Search title or content..." value="{{ request('search') }}">
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <label class="filter-label" for="filter-patient">Patient</label>
                        <select id="filter-patient" name="patient_id" class="form-select">
                            <option value="">All Patients</option>
                            @foreach($patients as $patient)
                                <option value="{{ $patient->id }}" {{ request('patient_id') == $patient->id ? 'selected' : '' }}>
                                    {{ $patient->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 col-sm-6">
                        <label class="filter-label" for="filter-type">Type</label>
                        <select id="filter-type" name="note_type" class="form-select">
                            <option value="">All Types</option>
                            <option value="text" {{ request('note_type') == 'text' ? 'selected' : '' }}>Text</option>
                            <option value="voice" {{ request('note_type') == 'voice' ? 'selected' : '' }}>Voice</option>
                        </select>
                    </div>
                    <div class="col-md-2 col-sm-6">
                        <label class="filter-label" for="filter-from">From Date</label>
                        <input type="date" name="date_from" id="filter-from" class="form-control" value="{{ request('date_from') }}">
                    </div>
                    <div class="col-md-2 col-sm-6">
                        <label class="filter-label" for="filter-to">To Date</label>
                        <input type="date" name="date_to" id="filter-to" class="form-control" value="{{ request('date_to') }}">
                    </div>
                    <div class="col-12 d-flex justify-content-end gap-2">
                        <button type="submit" class="btn btn-primary-custom">
                            <i class="fas fa-filter me-1"></i>Filter
                        </button>
                        <a href="{{ route('doctor.notes.index') }}" class="filter-reset" title="Reset filters">
                            <i class="fas fa-rotate-left"></i>
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Notes List -->
        <div class="table-card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="mb-0"><i class="fas fa-sticky-note me-2"></i>Notes ({{ $notes->total() }})</h6>
            </div>
            @if($notes->count() > 0)
                <div class="table-responsive">
                    <table class="table custom-table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Type</th>
                                <th>Title/Preview</th>
                                <th>Patient</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($notes as $note)
                                <tr>
                                    <td>
                                        <span class="badge {{ $note->getTypeBadgeClass() }}">
                                            <i class="{{ $note->getTypeIcon() }} me-1"></i>
                                            {{ ucfirst($note->note_type) }}
                                        </span>
                                    </td>
                                    <td>
                                        <div>
                                            <strong>{{ $note->getDisplayTitle() }}</strong>
                                            <div class="text-muted small mt-1">
                                                {{ $note->getPreview(80) }}
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        @if($note->patient)
                                            <div>
                                                <strong>{{ $note->patient->name }}</strong>
                                                <div class="text-muted small">{{ $note->patient->email }}</div>
                                            </div>
                                        @else
                                            <span class="text-muted">General Note</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div>{{ $note->created_at->format('M j, Y') }}</div>
                                        <div class="text-muted small">{{ $note->created_at->format('g:i A') }}</div>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('doctor.notes.show', $note) }}" class="btn btn-sm btn-outline-secondary" title="View">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('doctor.notes.edit', $note) }}" class="btn btn-sm btn-outline-secondary" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteNote({{ $note->id }})" title="Delete">
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
                <div class="d-flex justify-content-center mt-4">
                    {{ $notes->appends(request()->query())->links() }}
                </div>
            @else
                <div class="empty-state text-center py-5">
                    <i class="fas fa-sticky-note fa-3x text-muted mb-3"></i>
                    <h5>No Notes Found</h5>
                    <p class="text-muted">You haven't created any notes yet.</p>
                    <a href="{{ route('doctor.notes.create') }}" class="btn btn-primary-custom">
                        <i class="fas fa-plus me-2"></i>Create Your First Note
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to delete this note? This action cannot be undone.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDelete">Delete</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/doctor-dashboard.css') }}">
<style>
.empty-state {
    padding: 3rem 1rem;
}

.badge {
    font-size: 0.75rem;
}

.table td {
    vertical-align: middle;
}

.btn-group .btn {
    border-radius: 0.25rem;
    margin-right: 0.25rem;
}

.btn-group .btn:last-child {
    margin-right: 0;
}
</style>
@endpush

@push('scripts')
<script>
let noteToDelete = null;

function deleteNote(noteId) {
    noteToDelete = noteId;
    const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
    modal.show();
}

document.getElementById('confirmDelete').addEventListener('click', function() {
    if (noteToDelete) {
        fetch(`{{ route('doctor.notes.index') }}/${noteToDelete}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error deleting note: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error deleting note');
        });

        const modal = bootstrap.Modal.getInstance(document.getElementById('deleteModal'));
        modal.hide();
    }
});
</script>
@endpush
