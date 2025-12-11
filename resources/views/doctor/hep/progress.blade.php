@extends('master')

@section('title', 'Progress - ' . $program->title)

@section('content')
<div class="dashboard-container">
    <div class="container">
        <!-- Header -->
        <div class="dashboard-header py-2 border-bottom">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2>Patient Progress</h2>
                    <p class="mb-0">{{ $program->title }} - {{ $assignment->patient->name }}</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('doctor.hep.show', $program) }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Program
                    </a>
                </div>
            </div>
        </div>

        <!-- Progress Overview -->
        <div class="row mt-4">
            <div class="col-lg-8">
                <!-- Overall Progress -->
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-chart-line me-2"></i>Overall Progress</h5>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-md-3">
                                <div class="progress-circle" style="--progress: {{ $assignment->getCompliancePercentage() }}%">
                                    <div class="progress-value">{{ $assignment->getCompliancePercentage() }}%</div>
                                </div>
                                <small class="text-muted mt-2 d-block">Compliance Rate</small>
                            </div>
                            <div class="col-md-3">
                                <h3 class="text-primary">{{ $assignment->hepProgress->count() }}</h3>
                                <small class="text-muted">Total Sessions</small>
                            </div>
                            <div class="col-md-3">
                                <h3 class="text-success">{{ $assignment->getCurrentWeek() }}</h3>
                                <small class="text-muted">Current Week</small>
                            </div>
                            <div class="col-md-3">
                                <h3 class="text-info">{{ \Carbon\Carbon::parse($assignment->assigned_at)->diffInDays(now()) }}</h3>
                                <small class="text-muted">Days Active</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Weekly Progress -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h5><i class="fas fa-calendar-week me-2"></i>Weekly Progress</h5>
                    </div>
                    <div class="card-body">
                        @if($progressByWeek->isNotEmpty())
                            @foreach($progressByWeek as $week => $progress)
                                <div class="week-progress mb-4">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h6 class="mb-0">Week {{ $week }}</h6>
                                        <span class="badge bg-{{ $progress->count() > 0 ? 'success' : 'warning' }}">
                                            {{ $progress->count() }} sessions
                                        </span>
                                    </div>

                                    <div class="progress mb-3" style="height: 20px;">
                                        <div class="progress-bar bg-success" style="width: {{ $assignment->getWeekCompletionPercentage($week) }}%">
                                            {{ $assignment->getWeekCompletionPercentage($week) }}%
                                        </div>
                                    </div>

                                    @if($progress->isNotEmpty())
                                        <div class="sessions-list">
                                            @foreach($progress->sortBy('date') as $session)
                                                <div class="session-item d-flex justify-content-between align-items-center p-2 border rounded mb-2">
                                                    <div>
                                                        <strong>{{ $session->hepExercise->exercise->name }}</strong>
                                                        <br>
                                                        <small class="text-muted">
                                                            {{ $session->date->format('M j, Y') }} -
                                                            {{ $session->completed_sets ?? 0 }} sets × {{ $session->completed_reps ?? 0 }} reps
                                                            @if($session->duration_seconds)
                                                                ({{ $session->duration_seconds }}s)
                                                            @endif
                                                        </small>
                                                    </div>
                                                    <div class="text-end">
                                                        @if($session->pain_level)
                                                            <small class="text-warning">
                                                                <i class="fas fa-exclamation-triangle me-1"></i>Pain: {{ $session->pain_level }}/10
                                                            </small>
                                                            <br>
                                                        @endif
                                                        @if($session->notes)
                                                            <small class="text-muted">
                                                                <i class="fas fa-sticky-note me-1"></i>Notes
                                                            </small>
                                                        @endif
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <div class="text-center text-muted py-3">
                                            <i class="fas fa-calendar-times fa-2x mb-2"></i>
                                            <p class="mb-0">No sessions recorded for this week</p>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        @else
                            <div class="text-center text-muted py-5">
                                <i class="fas fa-chart-line fa-3x mb-3"></i>
                                <h5>No Progress Data</h5>
                                <p>The patient hasn't started tracking their exercises yet.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Patient Info -->
                <div class="card">
                    <div class="card-header">
                        <h6>Patient Information</h6>
                    </div>
                    <div class="card-body">
                        <div class="text-center mb-3">
                            <div class="avatar-circle mx-auto mb-2">
                                <i class="fas fa-user fa-2x"></i>
                            </div>
                            <h6>{{ $assignment->patient->name }}</h6>
                        </div>

                        <div class="patient-details">
                            <div class="detail-item">
                                <span class="label">Assigned:</span>
                                <span class="value">{{ $assignment->assigned_at->format('M j, Y') }}</span>
                            </div>
                            <div class="detail-item">
                                <span class="label">Program:</span>
                                <span class="value">{{ $program->title }}</span>
                            </div>
                            <div class="detail-item">
                                <span class="label">Duration:</span>
                                <span class="value">{{ $program->duration_weeks }} weeks</span>
                            </div>
                            @if($assignment->notes)
                                <div class="detail-item">
                                    <span class="label">Notes:</span>
                                    <span class="value">{{ $assignment->notes }}</span>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h6>Actions</h6>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <button type="button" class="btn btn-primary" onclick="printProgress()">
                                <i class="fas fa-print me-2"></i>Print Progress Report
                            </button>

                            <button type="button" class="btn btn-outline-info" onclick="exportProgress()">
                                <i class="fas fa-download me-2"></i>Export Data
                            </button>

                            <a href="mailto:{{ $assignment->patient->email }}?subject=HEP Progress Update&body=Hi {{ $assignment->patient->name }},\n\nHere's an update on your home exercise program progress..." class="btn btn-outline-success">
                                <i class="fas fa-envelope me-2"></i>Email Patient
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Exercise Compliance -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h6>Exercise Compliance</h6>
                    </div>
                    <div class="card-body">
                        @php
                            $exerciseCompliance = $assignment->hepProgress()
                                ->selectRaw('hep_exercise_id, COUNT(*) as sessions_count')
                                ->groupBy('hep_exercise_id')
                                ->with('hepExercise.exercise')
                                ->get();
                        @endphp

                        @if($exerciseCompliance->isNotEmpty())
                            @foreach($exerciseCompliance as $compliance)
                                <div class="compliance-item mb-3">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <small class="fw-medium">{{ $compliance->hepExercise->exercise->name }}</small>
                                        <small class="text-muted">{{ $compliance->sessions_count }} sessions</small>
                                    </div>
                                    <div class="progress" style="height: 6px;">
                                        <div class="progress-bar bg-success" style="width: {{ min(100, ($compliance->sessions_count / max(1, $assignment->getCurrentWeek())) * 100) }}%"></div>
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <div class="text-center text-muted py-3">
                                <small>No exercise data available</small>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
.progress-circle {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: conic-gradient(#007bff 0% var(--progress), #e9ecef var(--progress) 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
}

.progress-value {
    font-size: 1.2rem;
    font-weight: bold;
    color: #007bff;
}

.week-progress {
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 1rem;
    background: #f8f9fa;
}

.sessions-list {
    max-height: 300px;
    overflow-y: auto;
}

.session-item {
    background: white;
    transition: all 0.2s ease;
}

.session-item:hover {
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.avatar-circle {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
}

.patient-details {
    text-align: left;
}

.detail-item {
    display: flex;
    justify-content: space-between;
    padding: 0.5rem 0;
    border-bottom: 1px solid #f0f0f0;
}

.detail-item:last-child {
    border-bottom: none;
}

.label {
    font-weight: 500;
    color: #6c757d;
}

.value {
    text-align: right;
    font-weight: 500;
}

.compliance-item {
    padding: 0.5rem 0;
    border-bottom: 1px solid #f0f0f0;
}

.compliance-item:last-child {
    border-bottom: none;
}

@media print {
    .dashboard-header,
    .card-header,
    .btn,
    .badge {
        display: none !important;
    }

    .card {
        border: none !important;
        box-shadow: none !important;
    }

    .card-body {
        padding: 0 !important;
    }
}
</style>
@endpush

@push('scripts')
<script>
function printProgress() {
    window.print();
}

function exportProgress() {
    // In a real implementation, this would generate and download a CSV/PDF
    alert('Export functionality would be implemented here to generate a downloadable progress report.');
}

// Progress circle animation
document.addEventListener('DOMContentLoaded', function() {
    const progressCircles = document.querySelectorAll('.progress-circle');
    progressCircles.forEach(circle => {
        const progress = circle.style.getPropertyValue('--progress');
        circle.style.background = `conic-gradient(#007bff 0% ${progress}, #e9ecef ${progress} 100%)`;
    });
});
</script>
@endpush
@endsection
