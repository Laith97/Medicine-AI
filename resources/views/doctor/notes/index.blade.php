@extends('master')

@section('title', 'My Notes')

@section('content')
<div class="dashboard-container">
    <div class="container">
        <!-- Header -->
        <div class="dashboard-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2><i class="fas fa-sticky-note me-2"></i>My Notes</h2>
                    <p>Manage your clinical notes and voice recordings</p>
                </div>
                <a href="{{ route('doctor.notes.create') }}" class="btn btn-primary-custom">
                    <i class="fas fa-plus me-2"></i>Add Note
                </a>
            </div>
        </div>

        <!-- Filters -->
        <div class="table-card mb-4">
            <form method="GET" action="{{ route('doctor.notes.index') }}" class="row g-3">
                <div class="col-md-3">
                    <label for="patient_id" class="form-label">Patient</label>
                    <select name="patient_id" id="patient_id" class="form-select">
                        <option value="">All Patients</option>
                        @foreach($patients as $patient)
                            <option value="{{ $patient->id }}" {{ request('patient_id') == $patient->id ? 'selected' : '' }}>
                                {{ $patient->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="note_type" class="form-label">Type</label>
                    <select name="note_type" id="note_type" class="form-select">
                        <option value="">All Types</option>
                        <option value="text" {{ request('note_type') == 'text' ? 'selected' : '' }}>Text</option>
                        <option value="voice" {{ request('note_type') == 'voice' ? 'selected' : '' }}>Voice</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="date_from" class="form-label">From Date</label>
                    <input type="date" name="date_from" id="date_from" class="form-control" value="{{ request('date_from') }}">
                </div>
                <div class="col-md-2">
                    <label for="date_to" class="form-label">To Date</label>
                    <input type="date" name="date_to" id="date_to" class="form-control" value="{{ request('date_to') }}">
                </div>
                <div class="col-md-2">
                    <label for="search" class="form-label">Search</label>
                    <input type="text" name="search" id="search" class="form-control" placeholder="Search notes..." value="{{ request('search') }}">
                </div>
                <div class="col-md-1">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-outline-primary">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Notes List -->
        <div class="table-card">
            @if($notes->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover">
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
                                            <a href="{{ route('doctor.notes.show', $note) }}" class="btn btn-sm btn-outline-primary" title="View">
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
