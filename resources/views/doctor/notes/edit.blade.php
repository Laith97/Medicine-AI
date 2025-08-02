@extends('master')

@section('title', 'Edit Note')

@section('content')
<div class="dashboard-container">
    <div class="container">
        <!-- Header -->
        <div class="dashboard-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2><i class="fas fa-edit me-2"></i>Edit Note</h2>
                    <p>{{ $note->getDisplayTitle() }}</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('doctor.notes.show', $note) }}" class="btn btn-outline-info">
                        <i class="fas fa-eye me-2"></i>View
                    </a>
                    <a href="{{ route('doctor.notes.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Notes
                    </a>
                </div>
            </div>
        </div>

        <!-- Edit Form -->
        <div class="table-card">
            <form id="editNoteForm">
                @csrf
                @method('PUT')

                <!-- Note Type Display (Read-only) -->
                <div class="row mb-4">
                    <div class="col-12">
                        <label class="form-label fw-bold">Note Type</label>
                        <div class="alert alert-info">
                            <i class="{{ $note->getTypeIcon() }} me-2"></i>
                            <strong>{{ ucfirst($note->note_type) }} Note</strong>
                            <small class="d-block mt-1">Note type cannot be changed after creation</small>
                        </div>
                    </div>
                </div>

                @if($note->isVoiceNote() && $note->audio_file_path)
                    <!-- Audio Player (Read-only) -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <label class="form-label fw-bold">Original Recording</label>
                            <audio controls class="w-100">
                                <source src="{{ Storage::url($note->audio_file_path) }}" type="audio/webm">
                                Your browser does not support the audio element.
                            </audio>
                        </div>
                    </div>
                @endif

                <!-- Basic Information -->
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="title" class="form-label">Title (Optional)</label>
                        <input type="text" class="form-control" id="title" name="title"
                               value="{{ old('title', $note->title) }}" placeholder="Enter note title">
                    </div>
                    <div class="col-md-6">
                        <label for="appointment_date" class="form-label">Appointment Date (Optional)</label>
                        <input type="date" class="form-control" id="appointment_date" name="appointment_date"
                               value="{{ old('appointment_date', $note->appointment_date?->format('Y-m-d')) }}">
                    </div>
                </div>

                <!-- Patient Selection -->
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="patient_id" class="form-label">Patient (Optional)</label>
                        <select class="form-select" id="patient_id" name="patient_id">
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
                        <label for="appointment_id" class="form-label">Related Appointment (Optional)</label>
                        <select class="form-select" id="appointment_id" name="appointment_id">
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

                <!-- Note Content -->
                <div class="mb-4">
                    <label for="note_text" class="form-label fw-bold">
                        @if($note->isVoiceNote())
                            Note Content (Editable Transcription)
                        @else
                            Note Content
                        @endif
                    </label>
                    <textarea class="form-control" id="note_text" name="note_text" rows="10"
                              placeholder="Enter your note content here...">{{ old('note_text', $note->note_text) }}</textarea>

                    @if($note->isVoiceNote())
                        <div class="form-text">
                            <i class="fas fa-info-circle me-1"></i>
                            This content was originally transcribed from voice. You can edit it as needed.
                        </div>
                    @endif
                </div>

                @if($note->isVoiceNote() && $note->transcript && $note->transcript !== $note->note_text)
                    <!-- Original Transcription (Read-only) -->
                    <div class="mb-4">
                        <label class="form-label fw-bold">Original Transcription</label>
                        <div class="bg-light p-3 rounded">
                            {!! nl2br(e($note->transcript)) !!}
                        </div>
                        <div class="form-text">This is the original AI transcription for reference</div>
                    </div>
                @endif

                <!-- Submit Buttons -->
                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('doctor.notes.show', $note) }}" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary-custom" id="updateBtn">
                        <i class="fas fa-save me-2"></i>Update Note
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
audio {
    border-radius: 0.5rem;
}

.bg-light {
    border-left: 4px solid #28a745;
    line-height: 1.6;
}

.alert-info {
    border-left: 4px solid #0dcaf0;
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
