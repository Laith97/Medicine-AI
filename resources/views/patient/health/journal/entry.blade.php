@extends('master')

@section('title', $existingEntry ? 'Update Journal Entry' : 'Daily Health Journal')

@section('content')
<div class="dashboard-container">
    <div class="container-fluid px-3 px-md-4">
        <!-- Header -->
        <div class="dashboard-header py-3 border-bottom mb-4">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h2 mb-1" id="page-title">
                        <i class="fas fa-book-medical text-primary me-2" aria-hidden="true"></i>
                        {{ $existingEntry ? 'Update Journal Entry' : 'Daily Health Journal' }}
                    </h1>
                    <p class="text-muted mb-0" id="page-subtitle">
                        {{ \Carbon\Carbon::parse($today)->format('l, F j, Y') }}
                    </p>
                </div>
                <a href="{{ route('patient.health.dashboard') }}" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1" aria-hidden="true"></i>Back
                </a>
            </div>
        </div>

        <form method="POST" action="{{ route('patient.health.journal.store') }}">
            @csrf
            <input type="hidden" name="entry_date" value="{{ $today }}">

            <div class="row">
                <!-- Symptom Checklist -->
                <div class="col-lg-8 mb-4">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-list-ul me-2 text-primary" aria-hidden="true"></i>
                                How are you feeling today?
                            </h5>
                        </div>
                        <div class="card-body">
                            <p class="text-muted small mb-3">Select any symptoms you're experiencing and rate their severity.</p>

                            <div class="symptom-checklist" id="symptomChecklist">
                                @php
                                    $selectedSymptoms = $existingEntry->symptoms ?? [];
                                    $severities = $existingEntry->severity ?? [];
                                @endphp

                                @foreach($commonSymptoms as $index => $symptom)
                                    <div class="symptom-row mb-3 p-3 border rounded {{ in_array($symptom, $selectedSymptoms) ? 'border-primary bg-light' : '' }}"
                                         data-symptom="{{ $symptom }}" id="symptom-row-{{ $index }}">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div class="form-check">
                                                <input class="form-check-input symptom-checkbox"
                                                       type="checkbox"
                                                       name="symptoms[]"
                                                       value="{{ $symptom }}"
                                                       id="symptom-{{ $index }}"
                                                       {{ in_array($symptom, $selectedSymptoms) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="symptom-{{ $index }}">
                                                    {{ $symptom }}
                                                </label>
                                            </div>
                                            <div class="severity-wrapper {{ in_array($symptom, $selectedSymptoms) ? '' : 'd-none' }}" id="severity-{{ $index }}">
                                                <label class="form-label small text-muted mb-1 me-2">Severity:</label>
                                                @for($i = 1; $i <= 5; $i++)
                                                    <label class="severity-star {{ ($severities[$symptom] ?? 0) >= $i ? 'active' : '' }}" title="{{ $i }}/5">
                                                        <input type="radio" name="severity[{{ $symptom }}]" value="{{ $i }}" class="d-none"
                                                            {{ ($severities[$symptom] ?? 0) == $i ? 'checked' : '' }}>
                                                        <i class="fas fa-star" aria-hidden="true"></i>
                                                    </label>
                                                @endfor
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <!-- Notes -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-sticky-note me-2 text-warning" aria-hidden="true"></i>
                                Additional Notes
                            </h5>
                        </div>
                        <div class="card-body">
                            <textarea name="notes"
                                      class="form-control"
                                      rows="4"
                                      maxlength="1000"
                                      placeholder="Any other observations, how you're feeling overall, or notes for your doctor..."
                            >{{ $existingEntry->notes ?? old('notes') }}</textarea>
                            <div class="d-flex justify-content-between align-items-center mt-2">
                                <small class="text-muted">Max 1000 characters</small>
                                @error('notes')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Submit -->
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-success btn-lg">
                            <i class="fas fa-save me-2" aria-hidden="true"></i>
                            {{ $existingEntry ? 'Update Entry' : 'Save Entry' }}
                        </button>
                        <a href="{{ route('patient.health.dashboard') }}" class="btn btn-outline-secondary btn-lg">
                            Cancel
                        </a>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-info-circle me-2 text-info" aria-hidden="true"></i>
                                Tips
                            </h5>
                        </div>
                        <div class="card-body">
                            <ul class="small text-muted mb-0">
                                <li class="mb-2">Log your symptoms daily, even if you feel well — this builds a helpful health pattern.</li>
                                <li class="mb-2">Rate symptom severity from 1 (mild) to 5 (severe) to give your doctor more insight.</li>
                                <li>Notes about sleep, stress, diet, or activity can also be valuable for your care team.</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@push('styles')
<style>
.symptom-row {
    transition: all 0.2s ease;
    cursor: pointer;
}

.symptom-row:hover {
    background-color: #f8f9fa;
}

.symptom-row.border-primary {
    background-color: #f0f7ff !important;
}

.severity-star {
    cursor: pointer;
    color: #dee2e6;
    font-size: 1.1rem;
    transition: color 0.15s ease;
}

.severity-star.active,
.severity-star:hover {
    color: #f39c12;
}
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle symptom row selection
    document.querySelectorAll('.symptom-row').forEach(function(row) {
        row.addEventListener('click', function(e) {
            if (e.target.type === 'checkbox') return;

            const checkbox = row.querySelector('.symptom-checkbox');
            checkbox.checked = !checkbox.checked;
            row.dispatchEvent(new Event('change'));
        });
    });

    // Show/hide severity on checkbox change
    document.querySelectorAll('.symptom-checkbox').forEach(function(checkbox) {
        checkbox.addEventListener('change', function() {
            const row = document.getElementById('symptom-row-' + checkbox.id.split('-')[1]);
            const severityWrapper = row.querySelector('.severity-wrapper');

            if (checkbox.checked) {
                row.classList.add('border-primary', 'bg-light');
                severityWrapper.classList.remove('d-none');
            } else {
                row.classList.remove('border-primary', 'bg-light');
                severityWrapper.classList.add('d-none');
            }
        });
    });

    // Star rating
    document.querySelectorAll('.severity-star').forEach(function(star) {
        star.addEventListener('click', function() {
            const wrapper = star.closest('.severity-wrapper');
            wrapper.querySelectorAll('.severity-star').forEach(function(s) {
                s.classList.remove('active');
            });

            let current = star;
            while (current) {
                if (current.classList.contains('severity-star')) {
                    current.classList.add('active');
                }
                current = current.previousElementSibling;
            }

            // Set radio button
            const radio = wrapper.querySelector('input[type="radio"]');
            radio.checked = true;
        });

        star.addEventListener('mouseenter', function() {
            const wrapper = star.closest('.severity-wrapper');
            const stars = wrapper.querySelectorAll('.severity-star');
            const index = Array.from(stars).indexOf(star);

            stars.forEach(function(s, i) {
                if (i <= index) {
                    s.classList.add('active');
                } else {
                    s.classList.remove('active');
                }
            });
        });
    });

    document.querySelector('.severity-wrapper').closest('.symptom-row').addEventListener('mouseleave', function() {
        document.querySelectorAll('.severity-star').forEach(function(star) {
            const radio = star.closest('.severity-wrapper').querySelector('input[type="radio"]:checked');
            if (radio) {
                star.classList.add('active');
            }
        });
    });
});
</script>
@endpush
@endsection
