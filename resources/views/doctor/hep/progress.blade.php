@extends('master')

@section('title', 'Progress - Physical Therapy (' . $program->title . ')')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/doctor-design-system.css') }}">
<link rel="stylesheet" href="{{ asset('css/doctor-dashboard.css') }}">
<link rel="stylesheet" href="{{ asset('css/cases-overview.css') }}">
<style>
.progress-circle {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: conic-gradient(var(--secondary-color, #3498db) 0% var(--progress), #e9ecef var(--progress) 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
}
.progress-value {
    font-size: 1.2rem;
    font-weight: 700;
    color: var(--secondary-color, #3498db);
    background: white;
    width: 64px;
    height: 64px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}
.week-progress {
    border: 1px solid var(--gray-200, #e9ecef);
    border-radius: 10px;
    padding: 1rem;
    background: var(--bg-secondary, #f8f9fa);
}
.sessions-list { max-height: 300px; overflow-y: auto; }
.session-item {
    background: white;
    transition: all 0.2s ease;
    border: 1px solid var(--gray-200, #e9ecef) !important;
}
.session-item:hover { box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
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
.detail-item {
    display: flex;
    justify-content: space-between;
    padding: 0.5rem 0;
    border-bottom: 1px solid var(--gray-100, #f1f5f9);
    font-size: 0.875rem;
}
.detail-item:last-child { border-bottom: none; }
.label { font-weight: 500; color: var(--gray-500, #6c757d); }
.value { text-align: right; font-weight: 500; }
.compliance-item {
    padding: 0.5rem 0;
    border-bottom: 1px solid var(--gray-100, #f1f5f9);
}
.compliance-item:last-child { border-bottom: none; }
</style>
@endpush

@section('content')
<div class="container-fluid" style="background-color: var(--bg-secondary, #f8f9fa);">
    <div class="container py-4">
        <div class="dashboard-header cases-header-compact">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <h2><i class="fas fa-chart-line me-2"></i>Patient Progress</h2>
                    <p>Physical Therapy ({{ $program->title }}) - {{ $assignment->patient->name }}</p>
                </div>
                <a href="{{ route('doctor.hep.show', $program) }}" class="btn btn-outline-secondary bg-white">
                    <i class="fas fa-arrow-left me-2"></i>Back to Program
                </a>
            </div>
        </div>

        <!-- Progress Overview -->
        <div class="row g-3">
            <div class="col-lg-8">
                <!-- Overall Progress -->
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-bottom">
                        <h5 class="mb-0 fw-semibold"><i class="fas fa-chart-line me-2 text-primary"></i>Overall Progress</h5>
                    </div>
                    <div class="card-body">
                        <div class="row text-center g-3">
                            <div class="col-md-3">
                                <div class="progress-circle" style="--progress: {{ $assignment->getCompliancePercentage() }}%">
                                    <div class="progress-value">{{ $assignment->getCompliancePercentage() }}%</div>
                                </div>
                                <small class="text-muted mt-2 d-block">Compliance Rate</small>
                            </div>
                            <div class="col-md-3">
                                <h3 class="text-primary mb-1">{{ $assignment->hepProgress->count() }}</h3>
                                <small class="text-muted">Total Sessions</small>
                            </div>
                            <div class="col-md-3">
                                <h3 class="text-success mb-1">{{ $assignment->getCurrentWeek() }}</h3>
                                <small class="text-muted">Current Week</small>
                            </div>
                            <div class="col-md-3">
                                <h3 class="text-info mb-1">{{ \Carbon\Carbon::parse($assignment->assigned_at)->diffInDays(now()) }}</h3>
                                <small class="text-muted">Days Active</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Weekly Progress -->
                <div class="card border-0 shadow-sm mt-3">
                    <div class="card-header bg-white border-bottom">
                        <h5 class="mb-0 fw-semibold"><i class="fas fa-calendar-week me-2 text-primary"></i>Weekly Progress</h5>
                    </div>
                    <div class="card-body">
                        @if($progressByWeek->isNotEmpty())
                            @foreach($progressByWeek as $week => $progress)
                                <div class="week-progress mb-3">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h6 class="mb-0 fw-semibold">Week {{ $week }}</h6>
                                        <span class="badge bg-{{ $progress->count() > 0 ? 'success' : 'warning' }}">
                                            {{ $progress->count() }} sessions
                                        </span>
                                    </div>

                                    <div class="progress mb-3" style="height: 20px; background: #e9ecef; border-radius: 10px;">
                                        <div class="progress-bar bg-success" style="width: {{ $assignment->getWeekCompletionPercentage($week) }}%">
                                            {{ $assignment->getWeekCompletionPercentage($week) }}%
                                        </div>
                                    </div>

                                    @if($progress->isNotEmpty())
                                        <div class="sessions-list">
                                            @foreach($progress->sortBy('date') as $session)
                                                <div class="session-item d-flex justify-content-between align-items-center p-2 rounded mb-2">
                                                    <div>
                                                        <strong style="font-size:0.875rem;">{{ $session->hepExercise->exercise->name }}</strong>
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
                                            <i class="fas fa-calendar-times fa-2x mb-2 d-block"></i>
                                            <p class="mb-0">No sessions recorded for this week</p>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        @else
                            <div class="text-center text-muted py-5">
                                <i class="fas fa-chart-line fa-3x mb-3 d-block"></i>
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
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-bottom">
                        <h6 class="mb-0 fw-semibold">Patient Information</h6>
                    </div>
                    <div class="card-body">
                        <div class="text-center mb-3">
                            <div class="avatar-circle mb-2">
                                <i class="fas fa-user fa-2x"></i>
                            </div>
                            <h6 class="fw-semibold">{{ $assignment->patient->name }}</h6>
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
                <div class="card border-0 shadow-sm mt-3">
                    <div class="card-header bg-white border-bottom">
                        <h6 class="mb-0 fw-semibold">Actions</h6>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <button type="button" class="btn btn-primary" onclick="printProgress()">
                                <i class="fas fa-print me-2"></i>Print Progress Report
                            </button>

                            <button type="button" class="btn btn-outline-secondary" onclick="exportProgress()">
                                <i class="fas fa-download me-2"></i>Export Data
                            </button>

                            <a href="mailto:{{ $assignment->patient->email }}?subject=HEP Progress Update&body=Hi {{ $assignment->patient->name }},%0A%0AHere's an update on your home exercise program progress..." class="btn btn-outline-success">
                                <i class="fas fa-envelope me-2"></i>Email Patient
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Exercise Compliance -->
                <div class="card border-0 shadow-sm mt-3">
                    <div class="card-header bg-white border-bottom">
                        <h6 class="mb-0 fw-semibold">Exercise Compliance</h6>
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
                                        <small class="fw-medium" style="font-size:0.85rem;">{{ $compliance->hepExercise->exercise->name }}</small>
                                        <small class="text-muted">{{ $compliance->sessions_count }} sessions</small>
                                    </div>
                                    <div class="progress" style="height: 6px; background:#e9ecef;">
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
@endsection

@push('scripts')
<script>
function printProgress() {
    window.print();
}

function exportProgress() {
    alert('Export functionality would be implemented here to generate a downloadable progress report.');
}

document.addEventListener('DOMContentLoaded', function() {
    const progressCircles = document.querySelectorAll('.progress-circle');
    progressCircles.forEach(circle => {
        const progress = circle.style.getPropertyValue('--progress');
        circle.style.background = `conic-gradient(var(--secondary-color, #3498db) 0% ${progress}, #e9ecef ${progress} 100%)`;
    });
});
</script>
@endpush
