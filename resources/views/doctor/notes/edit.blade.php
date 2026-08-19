@extends('master')

@section('title', 'Edit Note')

@section('content')
<style>
.app-main {
    background-color: #f8f9fa;
}
</style>
<div class="container-fluid" style="background-color: #f8f9fa;">
    <div class="container">
    <div class="row">
        <div class="col-12">
            <div class="dashboard-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h2><i class="fas fa-edit me-2"></i>Edit Note</h2>
                        <p class="text-muted mb-0">{{ $note->getDisplayTitle() }}</p>
                    </div>
                    <div class="header-actions">
                        <a href="{{ route('doctor.notes.show', $note) }}" class="btn btn-outline-info">
                            <i class="fas fa-eye me-2"></i>View
                        </a>
                        <a href="{{ route('doctor.notes.index') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Back to Notes
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>
</div>

<!-- Edit Form -->
<div class="dashboard-container">
    <div class="table-card note-form-card">
        <form id="editNoteForm" class="note-form-body">
            @csrf
            @method('PUT')

            <!-- Note Type Display (Read-only) -->
            <div class="form-section mb-4">
                <div class="form-section-header">
                    <div class="form-section-icon">
                        <i class="fas fa-pen-nib"></i>
                    </div>
                    <div>
                        <h6 class="mb-0">Note Type</h6>
                        <small class="text-muted">Note type cannot be changed after creation</small>
                    </div>
                </div>
                <div class="note-type-banner">
                    <i class="{{ $note->getTypeIcon() }} me-2"></i>
                    <strong>{{ ucfirst($note->note_type) }} Note</strong>
                </div>
            </div>

            @if($note->isVoiceNote() && $note->audio_file_path)
                <!-- Audio Player (Read-only) -->
                <div class="form-section mb-4">
                    <div class="form-section-header">
                        <div class="form-section-icon">
                            <i class="fas fa-volume-up"></i>
                        </div>
                        <div>
                            <h6 class="mb-0">Original Recording</h6>
                            <small class="text-muted">The audio that was transcribed for this note</small>
                        </div>
                    </div>
                    <audio controls class="w-100 note-audio">
                        <source src="{{ Storage::url($note->audio_file_path) }}" type="audio/webm">
                        Your browser does not support the audio element.
                    </audio>
                </div>
            @endif

            <!-- Basic Information -->
            <div class="form-section mb-4">
                <div class="form-section-header">
                    <div class="form-section-icon">
                        <i class="fas fa-clipboard-list"></i>
                    </div>
                    <div>
                        <h6 class="mb-0">Basic Information</h6>
                        <small class="text-muted">Optional details for this note</small>
                    </div>
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="title" class="form-label note-label">Title (Optional)</label>
                        <input type="text" class="form-control note-input" id="title" name="title"
                               value="{{ old('title', $note->title) }}" placeholder="Enter note title">
                    </div>
                    <div class="col-md-6">
                        <label for="appointment_date" class="form-label note-label">Appointment Date (Optional)</label>
                        <input type="date" class="form-control note-input" id="appointment_date" name="appointment_date"
                               value="{{ old('appointment_date', $note->appointment_date?->format('Y-m-d')) }}">
                    </div>
                </div>
            </div>

            <!-- Patient & Appointment -->
            <div class="form-section mb-4">
                <div class="form-section-header">
                    <div class="form-section-icon">
                        <i class="fas fa-user"></i>
                    </div>
                    <div>
                        <h6 class="mb-0">Patient & Appointment</h6>
                        <small class="text-muted">Link this note to a patient record</small>
                    </div>
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="patient_id" class="form-label note-label">Patient (Optional)</label>
                        <select class="form-select note-select" id="patient_id" name="patient_id">
                            <option value="">General Note (No specific patient)</option>
                            @foreach($patients as $patient)
                                <option value="{{ $patient->id }}"
                                        {{ old('patient_id', $note->patient_id) == $patient->id ? 'selected' : '' }}>
                                    {{ $patient->name }} - {{ $patient->email }}
                                </option>
                            @endforeach
                        </select>
                        <div class="form-text">Leave empty for general notes</div>
                    </div>
                    <div class="col-md-6">
                        <label for="appointment_id" class="form-label note-label">Related Appointment (Optional)</label>
                        <select class="form-select note-select" id="appointment_id" name="appointment_id">
                            <option value="">No specific appointment</option>
                            @foreach($appointments as $appointment)
                                <option value="{{ $appointment->id }}"
                                        {{ old('appointment_id', $note->appointment_id) == $appointment->id ? 'selected' : '' }}>
                                    {{ $appointment->patient->name ?? 'Unknown Patient' }} - {{ $appointment->appointment_date->format('M j, Y g:i A') }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <!-- Note Content -->
            <div class="form-section mb-4">
                <div class="form-section-header">
                    <div class="form-section-icon">
                        <i class="fas fa-file-alt"></i>
                    </div>
                    <div>
                        <h6 class="mb-0">
                            @if($note->isVoiceNote())
                                Note Content (Editable Transcription)
                            @else
                                Note Content
                            @endif
                        </h6>
                        <small class="text-muted">Write the medical note details</small>
                    </div>
                </div>
                <textarea class="form-control note-input" id="note_text" name="note_text" rows="10"
                          placeholder="Enter your note content here...">{{ old('note_text', $note->note_text) }}</textarea>

                @if($note->isVoiceNote())
                    <div class="form-text mt-2">
                        <i class="fas fa-info-circle me-1"></i>
                        This content was originally transcribed from voice. You can edit it as needed.
                    </div>
                @endif
            </div>

            @if($note->isVoiceNote() && $note->transcript && $note->transcript !== $note->note_text)
                <!-- Original Transcription (Read-only) -->
                <div class="form-section mb-4">
                    <div class="form-section-header">
                        <div class="form-section-icon">
                            <i class="fas fa-language"></i>
                        </div>
                        <div>
                            <h6 class="mb-0">Original Transcription</h6>
                            <small class="text-muted">The original AI transcription for reference</small>
                        </div>
                    </div>
                    <div class="original-transcription">
                        {!! nl2br(e($note->transcript)) !!}
                    </div>
                </div>
            @endif

            <!-- Submit Buttons -->
            <div class="form-actions d-flex justify-content-end gap-2 pt-3 border-top">
                <a href="{{ route('doctor.notes.show', $note) }}" class="btn btn-outline-secondary note-cancel">Cancel</a>
                <button type="submit" class="btn btn-primary-custom" id="updateBtn">
                    <i class="fas fa-save me-2"></i>Update Note
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('styles')
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
    font-size: 2.5rem;
    margin-bottom: 0.5rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.dashboard-header p {
    color: rgba(255, 255, 255, 0.9);
    font-size: 1.1rem;
    font-weight: 500;
    margin-bottom: 0;
}

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

/* Note form panel */
.note-form-card {
    padding: 0;
    overflow: hidden;
}

.note-form-body {
    padding: 2rem;
}

.form-section-header {
    display: flex;
    align-items: center;
    gap: 0.85rem;
    margin-bottom: 1.25rem;
    padding-bottom: 0.85rem;
    border-bottom: 1px solid #f1f3f4;
}

.form-section-icon {
    width: 42px;
    height: 42px;
    border-radius: 10px;
    background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    flex-shrink: 0;
}

.form-section-header h6 {
    font-weight: 700;
    color: #2c3e50;
    font-size: 0.95rem;
}

.form-section-header small {
    font-size: 0.8rem;
}

.note-label {
    font-size: 0.8rem;
    font-weight: 600;
    color: #495057;
    margin-bottom: 0.4rem;
}

.note-input,
.note-select {
    border: 1px solid #e0e0e0;
    border-radius: 10px;
    padding: 0.6rem 0.9rem;
    font-size: 0.92rem;
    color: #333;
    background-color: #fff;
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
}

.note-input:focus,
.note-select:focus {
    border-color: #DE6262;
    box-shadow: 0 0 0 3px rgba(222, 98, 98, 0.15);
}

.note-input::placeholder {
    color: #adb5bd;
}

.note-type-banner {
    background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
    color: #fff;
    padding: 1rem 1.25rem;
    border-radius: 12px;
    display: inline-flex;
    align-items: center;
    font-size: 1rem;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
}

.original-transcription {
    background-color: #f8f9fa;
    padding: 1.5rem;
    border-radius: 0.5rem;
    border-left: 4px solid #DE6262;
    line-height: 1.6;
}

.note-audio {
    border-radius: 0.5rem;
}

.form-actions .btn {
    border-radius: 50rem;
    padding: 0.6rem 1.8rem;
    font-weight: 600;
    font-size: 0.92rem;
}

.note-cancel {
    border-color: #dee2e6;
    color: #6c757d;
}

.note-cancel:hover {
    background: #f1f3f4;
    border-color: #ced4da;
    color: #495057;
}
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle form submission
    document.getElementById('editNoteForm').addEventListener('submit', async function(e) {
        e.preventDefault();

        const formData = new FormData(this);

        // Validate required fields
        const noteText = formData.get('note_text');
        if (!noteText.trim()) {
            alert('Please enter note content');
            return;
        }

        // Convert FormData to JSON
        const jsonData = {};
        for (let [key, value] of formData.entries()) {
            if (key !== '_token' && key !== '_method') {
                jsonData[key] = value;
            }
        }

        // Submit form
        const updateBtn = document.getElementById('updateBtn');
        const originalText = updateBtn.innerHTML;
        updateBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Updating...';
        updateBtn.disabled = true;

        try {
            const response = await fetch('{{ route("doctor.notes.update", $note) }}', {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify(jsonData)
            });

            const data = await response.json();

            if (data.success) {
                window.location.href = '{{ route("doctor.notes.show", $note) }}';
            } else {
                if (data.errors) {
                    let errorMessage = 'Validation errors:\n';
                    for (let field in data.errors) {
                        errorMessage += `${field}: ${data.errors[field].join(', ')}\n`;
                    }
                    alert(errorMessage);
                } else {
                    alert('Error updating note: ' + (data.message || 'Unknown error'));
                }
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Error updating note');
        } finally {
            updateBtn.innerHTML = originalText;
            updateBtn.disabled = false;
        }
    });
});
</script>
@endpush
