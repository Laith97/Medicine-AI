@extends('master')

@section('title', $assignment->hepProgram->title . ' - My HEP Program')

@section('content')
<div class="dashboard-container">
    <div class="container-fluid px-3 px-md-4">
        <!-- Header -->
        <div class="dashboard-header py-3 border-bottom mb-4">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h2 mb-1" id="program-title">
                        <i class="fas fa-dumbbell text-primary me-2" aria-hidden="true"></i>
                        {{ $assignment->hepProgram->title }}
                    </h1>
                    <p class="text-muted mb-0" id="program-subtitle">Week {{ $currentWeek }} of {{ $assignment->hepProgram->duration_weeks }} • {{ $weekCompletionRate }}% Complete</p>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-info" onclick="speakProgramInfo()" aria-label="Listen to program information">
                        <i class="fas fa-volume-up" aria-hidden="true"></i>
                        <span class="d-none d-sm-inline ms-1">Voice Guide</span>
                    </button>
                    <a href="{{ route('patient.hep.dashboard') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2" aria-hidden="true"></i>
                        <span class="d-none d-sm-inline">Back to Dashboard</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- Program Overview -->
        <div class="row mb-4">
            <div class="col-lg-8">
                <!-- Current Week Progress -->
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-calendar-week me-2" aria-hidden="true"></i>
                            Week {{ $currentWeek }} Progress
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row text-center mb-4">
                            <div class="col-md-4">
                                <div class="progress-circle-large" style="--progress: {{ $weekCompletionRate }}%">
                                    <div class="progress-value">{{ $weekCompletionRate }}%</div>
                                </div>
                                <small class="text-muted mt-2 d-block">Week Completion</small>
                            </div>
                            <div class="col-md-4">
                                <h3 class="text-primary">{{ $currentWeekProgress->count() }}</h3>
                                <small class="text-muted">Exercises Done</small>
                            </div>
                            <div class="col-md-4">
                                <h3 class="text-success">{{ $exercisesByWeek->get($currentWeek, collect())->count() - $currentWeekProgress->count() }}</h3>
                                <small class="text-muted">Remaining</small>
                            </div>
                        </div>

                        <!-- Week Progress Bar -->
                        <div class="mb-3">
                            <div class="d-flex justify-content-between small text-muted mb-1">
                                <span>Week {{ $currentWeek }} Progress</span>
                                <span>{{ $currentWeekProgress->count() }} / {{ $exercisesByWeek->get($currentWeek, collect())->count() }} exercises</span>
                            </div>
                            <div class="progress" style="height: 20px;">
                                <div class="progress-bar bg-success" style="width: {{ $weekCompletionRate }}%">
                                    {{ $weekCompletionRate }}%
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Current Week Exercises -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-tasks me-2 text-primary" aria-hidden="true"></i>
                            This Week's Exercises
                        </h5>
                        <span class="badge bg-primary">{{ $exercisesByWeek->get($currentWeek, collect())->count() }} exercises</span>
                    </div>
                    <div class="card-body">
                        @php $weekExercises = $exercisesByWeek->get($currentWeek, collect()) @endphp

                        @if($weekExercises->count() > 0)
                            <div class="exercise-grid">
                                @foreach($weekExercises as $exercise)
                                @php $progress = $currentWeekProgress->get($exercise->id) @endphp
                                <div class="exercise-item {{ $progress ? 'completed' : 'pending' }}">
                                    <div class="exercise-header">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div class="flex-grow-1">
                                                <h6 class="mb-1">{{ $exercise->exercise->name }}</h6>
                                                <div class="exercise-meta small text-muted">
                                                    @if($exercise->sets) <span><strong>Sets:</strong> {{ $exercise->sets }}</span> @endif
                                                    @if($exercise->reps) <span><strong>Reps:</strong> {{ $exercise->reps }}</span> @endif
                                                    @if($exercise->duration_seconds) <span><strong>Duration:</strong> {{ $exercise->duration_seconds }}s</span> @endif
                                                    @if($exercise->frequency) <span><strong>Frequency:</strong> {{ $exercise->frequency }}</span> @endif
                                                </div>
                                            </div>
                                            <div class="exercise-status">
                                                @if($progress)
                                                    <span class="badge bg-success">
                                                        <i class="fas fa-check me-1" aria-hidden="true"></i>Done
                                                    </span>
                                                @else
                                                    <span class="badge bg-warning">
                                                        <i class="fas fa-clock me-1" aria-hidden="true"></i>Pending
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>

                                    @if($exercise->notes)
                                    <div class="exercise-notes small text-muted mb-2">
                                        <strong>Notes:</strong> {{ $exercise->notes }}
                                    </div>
                                    @endif

                                    <div class="exercise-actions">
                                        <a href="{{ route('patient.hep.exercise', [$assignment, $exercise]) }}" class="btn btn-sm btn-primary me-2">
                                            <i class="fas fa-play me-1" aria-hidden="true"></i>Start Exercise
                                        </a>
                                        @if(!$progress)
                                        <button type="button" class="btn btn-sm btn-success" onclick="quickLogExercise({{ $exercise->id }}, '{{ $exercise->exercise->name }}')">
                                            <i class="fas fa-check me-1" aria-hidden="true"></i>Mark Complete
                                        </button>
                                        @else
                                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="viewProgress({{ $progress->id }})">
                                            <i class="fas fa-eye me-1" aria-hidden="true"></i>View Progress
                                        </button>
                                        @endif
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-5">
                                <i class="fas fa-calendar-times fa-3x text-muted mb-3" aria-hidden="true"></i>
                                <h5 class="text-muted">No Exercises This Week</h5>
                                <p class="text-muted">Check back later or contact your healthcare provider.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Program Info -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="fas fa-info-circle me-2 text-info" aria-hidden="true"></i>
                            Program Information
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="program-info-item">
                            <span class="label">Doctor:</span>
                            <span class="value">{{ $assignment->hepProgram->doctor->user->name }}</span>
                        </div>
                        <div class="program-info-item">
                            <span class="label">Assigned:</span>
                            <span class="value">{{ $assignment->assigned_at->format('M j, Y') }}</span>
                        </div>
                        <div class="program-info-item">
                            <span class="label">Duration:</span>
                            <span class="value">{{ $assignment->hepProgram->duration_weeks }} weeks</span>
                        </div>
                        <div class="program-info-item">
                            <span class="label">Total Exercises:</span>
                            <span class="value">{{ $assignment->hepProgram->hepExercises->count() }}</span>
                        </div>
                        <div class="program-info-item">
                            <span class="label">Overall Progress:</span>
                            <span class="value">{{ $assignment->getProgressPercentage() }}%</span>
                        </div>

                        @if($assignment->hepProgram->description)
                        <div class="mt-3">
                            <strong>Description:</strong>
                            <p class="small text-muted mt-1">{{ $assignment->hepProgram->description }}</p>
                        </div>
                        @endif

                        @if($assignment->hepProgram->goals)
                        <div class="mt-3">
                            <strong>Goals:</strong>
                            <p class="small text-muted mt-1">{{ $assignment->hepProgram->goals }}</p>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Progress Chart -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="fas fa-chart-line me-2 text-success" aria-hidden="true"></i>
                            Progress Overview
                        </h6>
                    </div>
                    <div class="card-body">
                        <canvas id="progressChart" width="100%" height="200"></canvas>
                        <div class="mt-3 small text-muted text-center">
                            Track your exercise completion over time
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="fas fa-bolt me-2 text-warning" aria-hidden="true"></i>
                            Quick Actions
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <button type="button" class="btn btn-primary" onclick="logBulkProgress()">
                                <i class="fas fa-plus-circle me-2" aria-hidden="true"></i>
                                Log Multiple Exercises
                            </button>
                            <button type="button" class="btn btn-outline-info" onclick="viewAllProgress()">
                                <i class="fas fa-chart-bar me-2" aria-hidden="true"></i>
                                View Detailed Progress
                            </button>
                            <button type="button" class="btn btn-outline-secondary" onclick="contactDoctor()">
                                <i class="fas fa-envelope me-2" aria-hidden="true"></i>
                                Contact Doctor
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Log Modal -->
<div class="modal fade" id="quickLogModal" tabindex="-1" aria-labelledby="quickLogModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="quickLogModalLabel">Log Exercise Progress</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="quickLogForm">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="log_exercise_name" class="form-label">Exercise</label>
                        <div id="log_exercise_name" class="form-control-plaintext fw-bold"></div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="log_completed_sets" class="form-label">Sets Completed</label>
                            <input type="number" class="form-control" id="log_completed_sets" name="completed_sets" min="0">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="log_completed_reps" class="form-label">Reps Completed</label>
                            <input type="number" class="form-control" id="log_completed_reps" name="completed_reps" min="0">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="log_duration_completed" class="form-label">Duration (seconds)</label>
                        <input type="number" class="form-control" id="log_duration_completed" name="duration_completed" min="0">
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="log_pain_level" class="form-label">Pain Level (0-10)</label>
                            <input type="range" class="form-range" id="log_pain_level" name="pain_level" min="0" max="10" value="0"
                                   oninput="document.getElementById('log_pain_value').textContent = this.value">
                            <div class="text-center small text-muted" id="log_pain_value">0</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="log_difficulty_rating" class="form-label">Difficulty (1-5)</label>
                            <input type="range" class="form-range" id="log_difficulty_rating" name="difficulty_rating" min="1" max="5" value="3"
                                   oninput="document.getElementById('log_difficulty_value').textContent = this.value">
                            <div class="text-center small text-muted" id="log_difficulty_value">3</div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="log_progress_notes" class="form-label">Notes (Optional)</label>
                        <textarea class="form-control" id="log_progress_notes" name="notes" rows="2" placeholder="Any additional notes..."></textarea>
                    </div>
                    <input type="hidden" name="hep_exercise_id" id="log_hep_exercise_id">
                    <input type="hidden" name="date" value="{{ date('Y-m-d') }}">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Log Progress</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('styles')
<style>
.progress-circle-large {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    background: conic-gradient(#007bff 0% var(--progress), #e9ecef var(--progress) 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
}

.progress-value {
    font-size: 1.5rem;
    font-weight: bold;
    color: #007bff;
}

.exercise-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(100%, 1fr));
    gap: 1rem;
}

.exercise-item {
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 1rem;
    transition: all 0.2s ease;
}

.exercise-item.completed {
    background: #f8fff8;
    border-color: #28a745;
}

.exercise-item.pending {
    background: #fffef8;
    border-color: #ffc107;
}

.exercise-item:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    transform: translateY(-1px);
}

.exercise-header {
    margin-bottom: 0.75rem;
}

.exercise-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    margin-top: 0.5rem;
}

.exercise-actions {
    margin-top: 0.75rem;
}

.program-info-item {
    display: flex;
    justify-content: space-between;
    padding: 0.5rem 0;
    border-bottom: 1px solid #f0f0f0;
}

.program-info-item:last-child {
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

/* High contrast mode support */
@media (prefers-contrast: high) {
    .exercise-item {
        border-width: 2px;
    }

    .card {
        border: 2px solid #000;
    }
}

/* Mobile optimizations */
@media (max-width: 768px) {
    .dashboard-header .d-flex {
        flex-direction: column;
        gap: 1rem;
        align-items: flex-start !important;
    }

    .exercise-grid {
        grid-template-columns: 1fr;
    }

    .exercise-meta {
        flex-direction: column;
        gap: 0.25rem;
    }

    .progress-circle-large {
        width: 80px;
        height: 80px;
    }

    .progress-value {
        font-size: 1.2rem;
    }
}

/* Print styles */
@media print {
    .dashboard-header,
    .btn,
    .modal,
    .card-header .badge {
        display: none !important;
    }

    .card {
        border: none !important;
        box-shadow: none !important;
    }

    .exercise-item {
        break-inside: avoid;
        page-break-inside: avoid;
    }
}
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Voice guidance functionality
function speakProgramInfo() {
    const title = document.getElementById('program-title').textContent;
    const subtitle = document.getElementById('program-subtitle').textContent;

    const utterance = new SpeechSynthesisUtterance(`${title}. ${subtitle}`);
    utterance.lang = '{{ app()->getLocale() }}';

    speechSynthesis.speak(utterance);
}

// Quick log exercise functionality
function quickLogExercise(exerciseId, exerciseName) {
    document.getElementById('log_exercise_name').textContent = exerciseName;
    document.getElementById('log_hep_exercise_id').value = exerciseId;
    document.getElementById('quickLogModalLabel').textContent = `Log Progress - ${exerciseName}`;

    // Reset form
    document.getElementById('quickLogForm').reset();
    document.getElementById('log_pain_value').textContent = '0';
    document.getElementById('log_difficulty_value').textContent = '3';

    new bootstrap.Modal(document.getElementById('quickLogModal')).show();
}

// Handle quick log form submission
document.getElementById('quickLogForm').addEventListener('submit', function(e) {
    e.preventDefault();

    const formData = new FormData(this);

    fetch(`{{ route('patient.hep.log-progress', $assignment) }}`, {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('quickLogModal')).hide();
            location.reload(); // Refresh to show updated progress
        } else {
            alert('Error: ' + (data.message || 'Failed to log progress'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while logging progress');
    });
});

// Progress chart
document.addEventListener('DOMContentLoaded', function() {
    fetch(`{{ route('patient.hep.progress-data', $assignment) }}`)
        .then(response => response.json())
        .then(data => {
            const ctx = document.getElementById('progressChart').getContext('2d');

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: data.progress_data.map(item => new Date(item.date).toLocaleDateString()),
                    datasets: [{
                        label: 'Sessions Completed',
                        data: data.progress_data.map(item => item.sessions),
                        borderColor: '#007bff',
                        backgroundColor: 'rgba(0, 123, 255, 0.1)',
                        tension: 0.4
                    }, {
                        label: 'Average Pain Level',
                        data: data.progress_data.map(item => item.avg_pain),
                        borderColor: '#dc3545',
                        backgroundColor: 'rgba(220, 53, 69, 0.1)',
                        tension: 0.4,
                        yAxisID: 'y1'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Sessions'
                            }
                        },
                        y1: {
                            beginAtZero: true,
                            position: 'right',
                            title: {
                                display: true,
                                text: 'Pain Level'
                            },
                            grid: {
                                drawOnChartArea: false,
                            },
                        }
                    },
                    plugins: {
                        legend: {
                            display: true,
                            position: 'bottom'
                        }
                    }
                }
            });
        })
        .catch(error => {
            console.error('Error loading progress data:', error);
        });
});

// Additional functions
function viewProgress(progressId) {
    // Could open a modal with detailed progress info
    alert('View detailed progress functionality would be implemented here');
}

function logBulkProgress() {
    alert('Bulk progress logging functionality would be implemented here');
}

function viewAllProgress() {
    // Redirect to detailed progress view
    window.location.href = `{{ route('patient.hep.progress-data', $assignment) }}`;
}

function contactDoctor() {
    const doctorEmail = '{{ $assignment->hepProgram->doctor->user->email }}';
    const subject = encodeURIComponent('Question about my HEP program: {{ $assignment->hepProgram->title }}');
    const body = encodeURIComponent('Hi Dr. {{ $assignment->hepProgram->doctor->user->name }},\n\nI have a question about my home exercise program.\n\nBest regards,\n{{ Auth::user()->name }}');

    window.location.href = `mailto:${doctorEmail}?subject=${subject}&body=${body}`;
}

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    if (e.altKey && e.key === 'v') {
        e.preventDefault();
        speakProgramInfo();
    }
});
</script>
@endpush
@endsection
