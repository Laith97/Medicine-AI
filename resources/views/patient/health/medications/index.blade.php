@extends('master')

@section('title', 'My Medications')

@section('content')
<div class="dashboard-container">
    <div class="container-fluid px-3 px-md-4">
        <!-- Header -->
        <div class="dashboard-header py-3 border-bottom mb-4">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h2 mb-1" id="page-title">
                        <i class="fas fa-pills text-success me-2" aria-hidden="true"></i>
                        My Medications
                    </h1>
                    <p class="text-muted mb-0" id="page-subtitle">Track your medication schedule and adherence</p>
                </div>
                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addMedicationModal">
                    <i class="fas fa-plus me-1" aria-hidden="true"></i>Add Medication
                </button>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2" aria-hidden="true"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <!-- Medications List -->
        @if($schedules->count() > 0)
            <div class="row">
                @foreach($schedules as $schedule)
                    @php
                        $log = $todayLogs[$schedule->id] ?? null;
                        $isTaken = $log && $log->taken_at;
                        $isSkipped = $log && $log->skipped;
                    @endphp
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card h-100 {{ !$schedule->active ? 'opacity-75' : '' }}">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0 text-truncate" title="{{ $schedule->medication_name }}">
                                    {{ $schedule->medication_name }}
                                </h5>
                                @if(!$schedule->active)
                                    <span class="badge bg-secondary">Inactive</span>
                                @endif
                            </div>
                            <div class="card-body">
                                <p class="mb-1"><strong>Dosage:</strong> {{ $schedule->dosage }}</p>
                                <p class="mb-1"><strong>Frequency:</strong> {{ $schedule->frequency }}</p>
                                @if($schedule->time_of_day)
                                    <p class="mb-1"><strong>Time:</strong> {{ $schedule->time_of_day }}</p>
                                @endif
                                <p class="mb-1"><strong>Start Date:</strong> {{ $schedule->start_date->format('M j, Y') }}</p>
                                @if($schedule->end_date)
                                    <p class="mb-0"><strong>End Date:</strong> {{ $schedule->end_date->format('M j, Y') }}</p>
                                @endif

                                <hr>

                                <!-- Today's Status -->
                                <div class="text-center">
                                    @if($isTaken)
                                        <div class="text-success">
                                            <i class="fas fa-check-circle fa-2x mb-2" aria-hidden="true"></i>
                                            <p class="mb-0"><strong>Taken</strong> at {{ $log->taken_at->format('g:i A') }}</p>
                                        </div>
                                    @elseif($isSkipped)
                                        <div class="text-warning">
                                            <i class="fas fa-minus-circle fa-2x mb-2" aria-hidden="true"></i>
                                            <p class="mb-0"><strong>Skipped</strong></p>
                                            @if($log->skip_reason)
                                                <small class="text-muted">{{ $log->skip_reason }}</small>
                                            @endif
                                        </div>
                                    @else
                                        <p class="text-muted mb-2">Not yet logged for today</p>
                                        <div class="d-flex gap-2 justify-content-center">
                                            <button type="button"
                                                    class="btn btn-success btn-sm take-med-btn"
                                                    data-log-id="{{ $log ? $log->id : '' }}"
                                                    data-schedule-id="{{ $schedule->id }}"
                                                    {{ !$log ? 'disabled' : '' }}>
                                                <i class="fas fa-check me-1" aria-hidden="true"></i>Take
                                            </button>
                                            <button type="button"
                                                    class="btn btn-outline-warning btn-sm skip-med-btn"
                                                    data-log-id="{{ $log ? $log->id : '' }}"
                                                    data-schedule-id="{{ $schedule->id }}">
                                                <i class="fas fa-minus me-1" aria-hidden="true"></i>Skip
                                            </button>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="card">
                <div class="card-body text-center py-5">
                    <i class="fas fa-pills fa-4x text-muted mb-3" aria-hidden="true"></i>
                    <h4 class="text-muted">No Medications Added</h4>
                    <p class="text-muted">Add your medications to start tracking your adherence.</p>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addMedicationModal">
                        <i class="fas fa-plus me-1" aria-hidden="true"></i>Add Your First Medication
                    </button>
                </div>
            </div>
        @endif
    </div>
</div>

<!-- Add Medication Modal -->
<div class="modal fade" id="addMedicationModal" tabindex="-1" aria-labelledby="addMedicationModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addMedicationModalLabel">
                    <i class="fas fa-pills me-2 text-success" aria-hidden="true"></i>
                    Add Medication
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="{{ route('patient.health.medications.add') }}">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="medication_name" class="form-label">Medication Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="medication_name" name="medication_name" required maxlength="255" placeholder="e.g., Aspirin">
                        @error('medication_name')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="dosage" class="form-label">Dosage <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="dosage" name="dosage" required maxlength="255" placeholder="e.g., 500mg">
                        @error('dosage')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="frequency" class="form-label">Frequency <span class="text-danger">*</span></label>
                        <select class="form-select" id="frequency" name="frequency" required>
                            <option value="">Select frequency...</option>
                            <option value="Once daily">Once daily</option>
                            <option value="Twice daily">Twice daily</option>
                            <option value="Three times daily">Three times daily</option>
                            <option value="Four times daily">Four times daily</option>
                            <option value="Every other day">Every other day</option>
                            <option value="Weekly">Weekly</option>
                            <option value="As needed">As needed</option>
                        </select>
                        @error('frequency')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="time_of_day" class="form-label">Time of Day</label>
                        <select class="form-select" id="time_of_day" name="time_of_day">
                            <option value="">Select time...</option>
                            <option value="Morning">Morning</option>
                            <option value="Afternoon">Afternoon</option>
                            <option value="Evening">Evening</option>
                            <option value="Night">Night</option>
                            <option value="With meals">With meals</option>
                        </select>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="start_date" class="form-label">Start Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="start_date" name="start_date" required value="{{ date('Y-m-d') }}">
                            @error('start_date')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="end_date" class="form-label">End Date</label>
                            <input type="date" class="form-control" id="end_date" name="end_date">
                            <small class="text-muted">Leave blank for ongoing</small>
                            @error('end_date')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save me-1" aria-hidden="true"></i>Add Medication
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    // Take medication
    document.querySelectorAll('.take-med-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const logId = this.dataset.logId;
            if (!logId) return;

            fetch(`/patient/health/medications/${logId}/take`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Content-Type': 'application/json',
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                }
            })
            .catch(error => console.error('Error:', error));
        });
    });

    // Skip medication
    document.querySelectorAll('.skip-med-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const logId = this.dataset.logId;
            if (!logId) return;

            fetch(`/patient/health/medications/${logId}/skip`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Content-Type': 'application/json',
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                }
            })
            .catch(error => console.error('Error:', error));
        });
    });
});
</script>
@endpush
@endsection
