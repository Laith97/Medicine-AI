@extends('master')

@section('title', 'View Note')

@push('styles')
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
    font-size: 2.5rem;
    margin-bottom: 0.5rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.dashboard-header h2::before {
    content: '👁️';
    font-size: 2rem;
}

.dashboard-header p {
    color: rgba(255, 255, 255, 0.9);
    font-size: 1.1rem;
    font-weight: 500;
    margin-bottom: 0;
}

/* Button styles within header */
.dashboard-header .btn {
    background: rgba(255, 255, 255, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.3);
    color: white;
    transition: all 0.3s ease;
}

.dashboard-header .btn:hover {
    background: rgba(255, 255, 255, 0.2);
    border-color: rgba(255, 255, 255, 0.5);
    color: white;
    transform: translateY(-1px);
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .dashboard-header {
        padding: 1.5rem;
        margin-bottom: 1.5rem;
    }

    .dashboard-header h2 {
        font-size: 2rem;
    }

    .dashboard-header p {
        font-size: 1rem;
    }
}
</style>
@endpush

@section('content')
<div class="dashboard-container">
    <div class="container">
        <!-- Header -->
        <div class="dashboard-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2>View Note</h2>
                    <p>{{ $note->getDisplayTitle() }}</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('doctor.notes.edit', $note) }}" class="btn btn-outline-primary">
                        <i class="fas fa-edit me-2"></i>Edit
                    </a>
                    <a href="{{ route('doctor.notes.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Notes
                    </a>
                </div>
            </div>
        </div>

        <!-- Note Details -->
        <div class="row">
            <div class="col-lg-8">
                <!-- Main Content -->
                <div class="table-card mb-4">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <h5 class="mb-1">{{ $note->title ?: 'Untitled Note' }}</h5>
                            <div class="d-flex align-items-center gap-3 text-muted small">
                                <span>
                                    <i class="fas fa-calendar me-1"></i>
                                    {{ $note->created_at->format('M j, Y g:i A') }}
                                </span>
                                <span class="badge {{ $note->getTypeBadgeClass() }}">
                                    <i class="{{ $note->getTypeIcon() }} me-1"></i>
                                    {{ ucfirst($note->note_type) }} Note
                                </span>
                            </div>
                        </div>
                    </div>

                    @if($note->isVoiceNote() && $note->audio_file_path)
                        <!-- Audio Player -->
                        <div class="mb-4">
                            <h6><i class="fas fa-volume-up me-2"></i>Audio Recording</h6>
                            <audio controls class="w-100">
                                <source src="{{ Storage::url($note->audio_file_path) }}" type="audio/webm">
                                Your browser does not support the audio element.
                            </audio>
                        </div>
                    @endif

                    @if($note->isVoiceNote() && $note->transcript)
                        <!-- Transcription -->
                        <div class="mb-4">
                            <h6><i class="fas fa-language me-2"></i>Transcription</h6>
                            <div class="bg-light p-3 rounded">
                                {!! nl2br(e($note->transcript)) !!}
                            </div>
                        </div>
                    @endif

                    <!-- Note Content -->
                    <div>
                        <h6><i class="fas fa-file-text me-2"></i>Note Content</h6>
                        <div class="note-content">
                            {!! nl2br(e($note->note_text)) !!}
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <!-- Note Information -->
                <div class="table-card mb-4">
                    <h6 class="mb-3"><i class="fas fa-info-circle me-2"></i>Note Information</h6>

                    <div class="mb-3">
                        <strong>Type:</strong>
                        <span class="badge {{ $note->getTypeBadgeClass() }} ms-2">
                            <i class="{{ $note->getTypeIcon() }} me-1"></i>
                            {{ ucfirst($note->note_type) }}
                        </span>
                    </div>

                    <div class="mb-3">
                        <strong>Patient:</strong>
                        @if($note->patient)
                            <div class="mt-1">
                                <div>{{ $note->patient->name }}</div>
                                <small class="text-muted">{{ $note->patient->email }}</small>
                            </div>
                        @else
                            <span class="text-muted ms-2">General Note</span>
                        @endif
                    </div>

                    @if($note->appointment)
                        <div class="mb-3">
                            <strong>Related Appointment:</strong>
                            <div class="mt-1">
                                <div>{{ $note->appointment->patient->name ?? 'Unknown Patient' }}</div>
                                <small class="text-muted">{{ $note->appointment->appointment_date->format('M j, Y g:i A') }}</small>
                            </div>
                        </div>
                    @endif

                    @if($note->appointment_date)
                        <div class="mb-3">
                            <strong>Appointment Date:</strong>
                            <div class="mt-1">{{ $note->appointment_date->format('M j, Y') }}</div>
                        </div>
                    @endif

                    <div class="mb-3">
                        <strong>Created:</strong>
                        <div class="mt-1">{{ $note->created_at->format('M j, Y g:i A') }}</div>
                        <small class="text-muted">{{ $note->created_at->diffForHumans() }}</small>
                    </div>

                    @if($note->updated_at != $note->created_at)
                        <div class="mb-3">
                            <strong>Last Updated:</strong>
                            <div class="mt-1">{{ $note->updated_at->format('M j, Y g:i A') }}</div>
                            <small class="text-muted">{{ $note->updated_at->diffForHumans() }}</small>
                        </div>
                    @endif
                </div>

                <!-- Actions -->
                <div class="table-card">
                    <h6 class="mb-3"><i class="fas fa-cogs me-2"></i>Actions</h6>
                    <div class="d-grid gap-2">
                        <a href="{{ route('doctor.notes.edit', $note) }}" class="btn btn-primary-custom">
                            <i class="fas fa-edit me-2"></i>Edit Note
                        </a>
                        <button type="button" class="btn btn-outline-danger" onclick="deleteNote()">
                            <i class="fas fa-trash me-2"></i>Delete Note
                        </button>
                    </div>
                </div>
            </div>
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
.note-content {
    background-color: #f8f9fa;
    padding: 1.5rem;
    border-radius: 0.5rem;
    border-left: 4px solid #007bff;
    line-height: 1.6;
    font-size: 1rem;
}

.badge {
    font-size: 0.75rem;
}

audio {
    border-radius: 0.5rem;
}

.bg-light {
    border-left: 4px solid #28a745;
}
</style>
@endpush

@push('scripts')
<script>
function deleteNote() {
    const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
    modal.show();
}

document.getElementById('confirmDelete').addEventListener('click', function() {
    fetch(`{{ route('doctor.notes.destroy', $note) }}`, {
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
            window.location.href = '{{ route("doctor.notes.index") }}';
        } else {
            alert('Error deleting note: ' + (data.message || 'Unknown error'));
        }
    })
    .catch(error => {
        // console.error('Error:', error);
        alert('Error deleting note');
    });
});
</script>
@endpush
