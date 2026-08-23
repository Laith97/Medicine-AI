@extends('master')

@section('title', 'My Notes')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/doctor-design-system.css') }}">
<link rel="stylesheet" href="{{ asset('css/doctor-dashboard.css') }}">
<link rel="stylesheet" href="{{ asset('css/cases-overview.css') }}">
<style>
.app-main { background-color: var(--bg-secondary, #f8f9fa); }
/* Notes preview clamp */
.notes-preview { font-size: 0.82rem; color: #64748b; line-height: 1.45; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
/* Toolbar date inputs match cases-sort */
.cases-date { width: 148px; border: 1px solid var(--gray-200, #dee2e6); border-radius: 8px; background: #fff; box-shadow: 0 1px 2px rgba(0,0,0,0.04); font-size: 0.84rem; padding: 0.45rem 0.75rem; height: 38px; }
.cases-date:focus { border-color: var(--secondary-color, #3498db); box-shadow: 0 0 0 3px rgba(52,152,219,0.12); outline: none; }
/* Ensure cases-search search input id matches legacy */
.cases-search #filter-search { border: none !important; box-shadow: none !important; font-size: 0.875rem; padding: 0.45rem 0.5rem; }
.cases-search #filter-search:focus { box-shadow: none !important; }
@media (max-width: 768px){
  .cases-date { width: 100% !important; }
  .cases-sort { width: 100% !important; }
}
</style>
@endpush

@section('content')
<div class="container-fluid" style="background-color: var(--bg-secondary, #f8f9fa);">
    <div class="container py-4">
        <div class="dashboard-header cases-header-compact">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <h2><i class="fas fa-sticky-note me-2"></i>Doctor Notes</h2>
                    <p>View and manage your clinical notes — text & voice</p>
                </div>
                <a href="{{ route('doctor.notes.create') }}" class="btn">
                    <i class="fas fa-plus me-2"></i>New Note
                </a>
            </div>
        </div>

        @php
            $totalNotes = $notes->total();
            $textCount = collect($notes->items())->where('note_type', 'text')->count();
            $voiceCount = collect($notes->items())->where('note_type', 'voice')->count();
            // When filtered, counts reflect current page; total is accurate across pagination.
            // Fallback to total for empty page still shows total correctly.
        @endphp
        <div class="row g-2 mb-3 cases-stats-compact">
            <div class="col-lg-4 col-4">
                <div class="stats-card stats-card--compact">
                    <div class="stats-icon stats-icon--sm" style="background: linear-gradient(135deg, #2c5aa0 0%, #1e3a8a 100%);">
                        <i class="fas fa-sticky-note"></i>
                    </div>
                    <div class="stats-text">
                        <p class="stats-number">{{ $totalNotes }}</p>
                        <p class="stats-label">Total Notes</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-4">
                <div class="stats-card stats-card--compact">
                    <div class="stats-icon stats-icon--sm" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%);">
                        <i class="fas fa-file-alt"></i>
                    </div>
                    <div class="stats-text">
                        <p class="stats-number">{{ $voiceCount + $textCount > 0 ? $textCount : $totalNotes }}</p>
                        <p class="stats-label">Text</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-4">
                <div class="stats-card stats-card--compact">
                    <div class="stats-icon stats-icon--sm" style="background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);">
                        <i class="fas fa-microphone"></i>
                    </div>
                    <div class="stats-text">
                        <p class="stats-number">{{ $voiceCount }}</p>
                        <p class="stats-label">Voice</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Unified Panel: Toolbar + Table -->
        <div class="card border-0 shadow-sm cases-panel mb-4">
            <form method="GET" action="{{ route('doctor.notes.index') }}" class="m-0" id="notesFilterForm">
                <div class="cases-toolbar">
                    <div class="cases-toolbar__title">
                        <h6 class="mb-0 fw-semibold"><i class="fas fa-sticky-note me-2 text-primary"></i>Notes ({{ $notes->total() }})</h6>
                        <span class="d-none">Search & Filters</span>
                        @if(request()->hasAny(['search', 'patient_id', 'note_type', 'date_from', 'date_to']))
                            <a href="{{ route('doctor.notes.index') }}" class="btn btn-outline-secondary btn-sm ms-2">
                                <i class="fas fa-times me-1"></i>Clear
                            </a>
                        @endif
                        <span class="cases-toolbar__meta d-none d-md-inline">— Text & voice records</span>
                    </div>
                    <div class="cases-toolbar__controls">
                        <div class="input-group input-group-sm cases-search">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                            <input type="text" name="search" id="filter-search" class="form-control" placeholder="Search title or content..." value="{{ request('search') }}">
                            <button class="btn btn-outline-secondary" type="button" id="clearSearch" title="Clear search">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <select name="patient_id" id="filter-patient" class="form-select form-select-sm cases-sort">
                            <option value="">All Patients</option>
                            @foreach($patients as $patient)
                                <option value="{{ $patient->id }}" {{ request('patient_id') == $patient->id ? 'selected' : '' }}>
                                    {{ $patient->name }}
                                </option>
                            @endforeach
                        </select>
                        <select name="note_type" id="filter-type" class="form-select form-select-sm cases-sort">
                            <option value="">All Types</option>
                            <option value="text" {{ request('note_type') == 'text' ? 'selected' : '' }}>Text</option>
                            <option value="voice" {{ request('note_type') == 'voice' ? 'selected' : '' }}>Voice</option>
                        </select>
                        <input type="date" name="date_from" id="filter-from" class="form-control form-control-sm cases-date" value="{{ request('date_from') }}" title="From date">
                        <input type="date" name="date_to" id="filter-to" class="form-control form-control-sm cases-date" value="{{ request('date_to') }}" title="To date">
                        <button type="submit" class="doctor-btn doctor-btn-primary doctor-btn-sm">
                            <i class="fas fa-filter me-1"></i>Filter
                        </button>
                        <a href="{{ route('doctor.notes.index') }}" class="doctor-btn doctor-btn-outline doctor-btn-sm" title="Reset filters">
                            <i class="fas fa-rotate-left"></i>
                        </a>
                    </div>
                </div>
            </form>

            @if($notes->count() > 0)
                <div class="doctor-table-container">
                    <div class="table-responsive">
                        <table class="table doctor-table custom-table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Type</th>
                                    <th>Title / Preview</th>
                                    <th>Patient</th>
                                    <th>Date</th>
                                    <th class="text-end" style="width: 140px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($notes as $note)
                                    <tr>
                                        <td>
                                            @if($note->note_type === 'voice')
                                                <span class="doctor-badge doctor-badge-info"><i class="fas fa-microphone me-1"></i>Voice</span>
                                            @else
                                                <span class="doctor-badge doctor-badge-secondary"><i class="fas fa-file-alt me-1"></i>Text</span>
                                            @endif
                                        </td>
                                        <td style="min-width: 240px; max-width: 420px;">
                                            <div class="fw-semibold" style="font-size: 0.88rem; color: #1e293b; line-height: 1.3;">{{ $note->getDisplayTitle() }}</div>
                                            <div class="notes-preview mt-1">{{ $note->getPreview(90) }}</div>
                                        </td>
                                        <td>
                                            @if($note->patient)
                                                <div class="d-flex align-items-center gap-2">
                                                    <div class="d-flex align-items-center justify-content-center rounded-circle flex-shrink-0" style="width:32px; height:32px; background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%); color:#fff; font-weight:700; font-size:0.75rem;">
                                                        {{ strtoupper(substr($note->patient->name, 0, 2)) }}
                                                    </div>
                                                    <div style="min-width:0;">
                                                        <div class="fw-medium text-truncate" style="font-size:0.875rem; max-width: 160px;">{{ $note->patient->name }}</div>
                                                        <small class="text-muted text-truncate d-block" style="max-width:160px;">{{ $note->patient->email }}</small>
                                                    </div>
                                                </div>
                                            @else
                                                <span class="doctor-badge doctor-badge-secondary">General Note</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="fw-medium" style="font-size:0.875rem;">{{ $note->created_at->format('M j, Y') }}</div>
                                            <small class="text-muted">{{ $note->created_at->format('g:i A') }}</small>
                                        </td>
                                        <td>
                                            <div class="d-flex justify-content-end gap-1">
                                                <a href="{{ route('doctor.notes.show', $note) }}" class="doctor-btn doctor-btn-outline doctor-btn-sm" title="View">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="{{ route('doctor.notes.edit', $note) }}" class="doctor-btn doctor-btn-outline doctor-btn-sm" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <button type="button" class="doctor-btn doctor-btn-danger doctor-btn-sm" onclick="deleteNote({{ $note->id }})" title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if($notes->hasPages())
                        <div class="d-flex justify-content-center py-3" style="background: var(--gray-50, #f8f9fa); border-top: 1px solid var(--border-light, rgba(0,0,0,0.05));">
                            {{ $notes->appends(request()->query())->links() }}
                        </div>
                    @else
                        <div class="table-footer">
                            <span><i class="fas fa-info-circle me-1"></i> Showing {{ $notes->count() }} of {{ $notes->total() }} notes</span>
                            <span class="d-none d-sm-inline">Use filters to refine</span>
                        </div>
                    @endif
                </div>
            @else
                <div class="doctor-empty-state">
                    <i class="fas fa-sticky-note"></i>
                    <h5>No Notes Found</h5>
                    <p>
                        @if(request()->hasAny(['search', 'patient_id', 'note_type', 'date_from', 'date_to']))
                            No notes match your filters. Try adjusting your search.
                        @else
                            You haven't created any notes yet. Start with your first clinical note.
                        @endif
                    </p>
                    <a href="{{ route('doctor.notes.create') }}" class="doctor-btn doctor-btn-primary">
                        <i class="fas fa-plus me-2"></i>Create Your First Note
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
                <h5 class="modal-title" id="deleteModalLabel">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
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

document.addEventListener('DOMContentLoaded', function(){
    const s=document.getElementById('filter-search'), c=document.getElementById('clearSearch'), f=document.getElementById('notesFilterForm');
    if(c && s){
        c.addEventListener('click', function(){
            s.value='';
            s.focus();
            if(f) f.submit();
        });
    }
});
</script>
@endpush
