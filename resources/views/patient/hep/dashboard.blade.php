@extends('master')

@section('title', 'My Home Exercise Programs')

@section('content')
<div class="dashboard-container">
    <div class="container-fluid px-3 px-md-4">
        <!-- Header -->
        <div class="dashboard-header py-3 border-bottom mb-4">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h2 mb-1" id="page-title">
                        <i class="fas fa-dumbbell text-primary me-2" aria-hidden="true"></i>
                        My Home Exercise Programs
                    </h1>
                    <p class="text-muted mb-0" id="page-subtitle">Track your progress and stay on top of your exercises</p>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-info" onclick="speakPageTitle()" aria-label="Listen to page title">
                        <i class="fas fa-volume-up" aria-hidden="true"></i>
                        <span class="d-none d-sm-inline ms-1">Voice Guide</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Overall Progress Stats -->
        <div class="row mb-4">
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="stats-card h-100">
                    <div class="stats-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                        <i class="fas fa-clipboard-list" aria-hidden="true"></i>
                    </div>
                    <div class="stats-number" aria-label="{{ $activeAssignments->count() + $completedAssignments->count() }} total programs">{{ $activeAssignments->count() + $completedAssignments->count() }}</div>
                    <div class="stats-label">Total Programs</div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6 mb-3">
                <div class="stats-card h-100">
                    <div class="stats-icon" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                        <i class="fas fa-play-circle" aria-hidden="true"></i>
                    </div>
                    <div class="stats-number" aria-label="{{ $activeAssignments->count() }} active programs">{{ $activeAssignments->count() }}</div>
                    <div class="stats-label">Active Programs</div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6 mb-3">
                <div class="stats-card h-100">
                    <div class="stats-icon" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
                        <i class="fas fa-check-circle" aria-hidden="true"></i>
                    </div>
                    <div class="stats-number" aria-label="{{ $completedAssignments->count() }} completed programs">{{ $completedAssignments->count() }}</div>
                    <div class="stats-label">Completed Programs</div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6 mb-3">
                <div class="stats-card h-100">
                    <div class="stats-icon" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                        <i class="fas fa-percentage" aria-hidden="true"></i>
                    </div>
                    <div class="stats-number" aria-label="{{ $overallCompletionRate }}% overall completion">{{ $overallCompletionRate }}%</div>
                    <div class="stats-label">Overall Completion</div>
                </div>
            </div>
        </div>

        <!-- Today's Progress -->
        @if($todayProgress->count() > 0)
        <div class="card mb-4">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0">
                    <i class="fas fa-calendar-check me-2" aria-hidden="true"></i>
                    Today's Progress
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    @foreach($todayProgress as $progress)
                    <div class="col-md-6 col-lg-4 mb-3">
                        <div class="border rounded p-3 h-100">
                            <h6 class="text-primary mb-2">{{ $progress->hepExercise->exercise->name }}</h6>
                            <div class="small text-muted mb-2">
                                <strong>Completed:</strong>
                                @if($progress->completed_sets) {{ $progress->completed_sets }} sets × @endif
                                @if($progress->completed_reps) {{ $progress->completed_reps }} reps @endif
                                @if($progress->duration_completed) ({{ $progress->duration_completed }}s) @endif
                            </div>
                            @if($progress->pain_level)
                            <div class="small mb-1">
                                <strong>Pain Level:</strong>
                                <span class="badge bg-{{ $progress->pain_level > 7 ? 'danger' : ($progress->pain_level > 4 ? 'warning' : 'success') }}">
                                    {{ $progress->pain_level }}/10
                                </span>
                            </div>
                            @endif
                            @if($progress->difficulty_rating)
                            <div class="small">
                                <strong>Difficulty:</strong> {{ $progress->difficulty_rating }}/5
                            </div>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endif

        <div class="row">
            <!-- Active Programs -->
            <div class="col-lg-8 mb-4">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-running me-2 text-primary" aria-hidden="true"></i>
                            Active Programs
                        </h5>
                        <span class="badge bg-primary">{{ $activeAssignments->count() }}</span>
                    </div>
                    <div class="card-body">
                        @if($activeAssignments->count() > 0)
                            @foreach($activeAssignments as $assignment)
                            <div class="program-card mb-3 p-3 border rounded">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1">
                                            <a href="{{ route('patient.hep.show', $assignment) }}" class="text-decoration-none">
                                                {{ $assignment->hepProgram->title }}
                                            </a>
                                        </h6>
                                        <p class="small text-muted mb-2">{{ $assignment->hepProgram->description }}</p>
                                        <div class="d-flex gap-3 small text-muted">
                                            <span><i class="fas fa-user-md me-1" aria-hidden="true"></i>{{ $assignment->hepProgram->doctor->user->name }}</span>
                                            <span><i class="fas fa-calendar me-1" aria-hidden="true"></i>Week {{ min(now()->diffInWeeks($assignment->assigned_at) + 1, $assignment->hepProgram->duration_weeks) }} of {{ $assignment->hepProgram->duration_weeks }}</span>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <div class="progress-circle mb-2" style="--progress: {{ $assignment->getProgressPercentage() }}%">
                                            <div class="progress-value">{{ $assignment->getProgressPercentage() }}%</div>
                                        </div>
                                        <small class="text-muted">Complete</small>
                                    </div>
                                </div>
                                <div class="d-flex gap-2">
                                    <a href="{{ route('patient.hep.show', $assignment) }}" class="btn btn-sm btn-primary">
                                        <i class="fas fa-eye me-1" aria-hidden="true"></i>View Program
                                    </a>
                                    <button type="button" class="btn btn-sm btn-outline-success" onclick="quickLogModal({{ $assignment->id }}, '{{ $assignment->hepProgram->title }}')">
                                        <i class="fas fa-plus me-1" aria-hidden="true"></i>Log Progress
                                    </button>
                                </div>
                            </div>
                            @endforeach
                        @else
                            <div class="text-center py-5">
                                <i class="fas fa-dumbbell fa-3x text-muted mb-3" aria-hidden="true"></i>
                                <h5 class="text-muted">No Active Programs</h5>
                                <p class="text-muted">You don't have any active exercise programs at the moment.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Upcoming Exercises -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="fas fa-clock me-2 text-warning" aria-hidden="true"></i>
                            Today's Exercises
                        </h6>
                    </div>
                    <div class="card-body">
                        @if(count($upcomingExercises) > 0)
                            @foreach($upcomingExercises as $item)
                            <div class="upcoming-exercise mb-3 p-2 border rounded small">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <strong>{{ $item['exercise']->exercise->name }}</strong>
                                        <br>
                                        <span class="text-muted">{{ $item['assignment']->hepProgram->title }}</span>
                                    </div>
                                    <a href="{{ route('patient.hep.exercise', [$item['assignment'], $item['exercise']]) }}" class="btn btn-sm btn-outline-primary ms-2">
                                        <i class="fas fa-play" aria-hidden="true"></i>
                                    </a>
                                </div>
                            </div>
                            @endforeach
                        @else
                            <div class="text-center py-3">
                                <i class="fas fa-check-circle fa-2x text-success mb-2" aria-hidden="true"></i>
                                <p class="small text-muted mb-0">All caught up for today!</p>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Recent Completed Programs -->
                @if($completedAssignments->count() > 0)
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="fas fa-trophy me-2 text-success" aria-hidden="true"></i>
                            Recently Completed
                        </h6>
                    </div>
                    <div class="card-body">
                        @foreach($completedAssignments as $assignment)
                        <div class="completed-program mb-2 p-2 bg-light rounded small">
                            <strong>{{ $assignment->hepProgram->title }}</strong>
                            <br>
                            <span class="text-muted">Completed {{ $assignment->updated_at->format('M j, Y') }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Quick Log Progress Modal -->
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
                        <label for="exercise_select" class="form-label">Select Exercise</label>
                        <select class="form-select" id="exercise_select" name="hep_exercise_id" required>
                            <option value="">Choose an exercise...</option>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="completed_sets" class="form-label">Sets Completed</label>
                            <input type="number" class="form-control" id="completed_sets" name="completed_sets" min="0">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="completed_reps" class="form-label">Reps Completed</label>
                            <input type="number" class="form-control" id="completed_reps" name="completed_reps" min="0">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="duration_completed" class="form-label">Duration (seconds)</label>
                        <input type="number" class="form-control" id="duration_completed" name="duration_completed" min="0">
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="pain_level" class="form-label">Pain Level (0-10)</label>
                            <input type="range" class="form-range" id="pain_level" name="pain_level" min="0" max="10" value="0"
                                   oninput="document.getElementById('pain_value').textContent = this.value">
                            <div class="text-center small text-muted" id="pain_value">0</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="difficulty_rating" class="form-label">Difficulty (1-5)</label>
                            <input type="range" class="form-range" id="difficulty_rating" name="difficulty_rating" min="1" max="5" value="3"
                                   oninput="document.getElementById('difficulty_value').textContent = this.value">
                            <div class="text-center small text-muted" id="difficulty_value">3</div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="progress_notes" class="form-label">Notes (Optional)</label>
                        <textarea class="form-control" id="progress_notes" name="notes" rows="2" placeholder="Any additional notes..."></textarea>
                    </div>
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
.stats-card {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    border: none;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.stats-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
}

.stats-icon {
    width: 50px;
    height: 50px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.2rem;
    margin-bottom: 1rem;
}

.stats-number {
    font-size: 2rem;
    font-weight: bold;
    color: #2d3748;
    margin: 0;
}

.stats-label {
    color: #718096;
    font-size: 0.9rem;
    margin: 0;
}

.progress-circle {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: conic-gradient(#007bff 0% var(--progress), #e9ecef var(--progress) 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
}

.progress-value {
    font-size: 0.9rem;
    font-weight: bold;
    color: #007bff;
}

.program-card {
    transition: all 0.2s ease;
}

.program-card:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    border-color: #007bff !important;
}

.upcoming-exercise {
    background: #f8f9fa;
}

.completed-program {
    border-left: 4px solid #28a745;
}

/* High contrast mode support */
@media (prefers-contrast: high) {
    .stats-card {
        border: 2px solid #000;
    }

    .program-card {
        border: 2px solid #666;
    }
}

/* Mobile optimizations */
@media (max-width: 768px) {
    .dashboard-header .d-flex {
        flex-direction: column;
        gap: 1rem;
        align-items: flex-start !important;
    }

    .stats-card {
        padding: 1rem;
    }

    .stats-number {
        font-size: 1.5rem;
    }

    .program-card .d-flex {
        flex-direction: column;
        gap: 1rem;
    }

    .progress-circle {
        width: 50px;
        height: 50px;
    }
}
</style>
@endpush

@push('scripts')
<script>
// Voice guidance functionality
function speakPageTitle() {
    const title = document.getElementById('page-title').textContent;
    const subtitle = document.getElementById('page-subtitle').textContent;

    const utterance = new SpeechSynthesisUtterance(`${title}. ${subtitle}`);
    utterance.lang = '{{ app()->getLocale() }}'; // Use current app locale

    // Try to find a female voice if available
    const voices = speechSynthesis.getVoices();
    const femaleVoice = voices.find(voice => voice.name.toLowerCase().includes('female') || voice.name.toLowerCase().includes('woman'));

    if (femaleVoice) {
        utterance.voice = femaleVoice;
    }

    speechSynthesis.speak(utterance);
}

// Quick log modal functionality
let currentAssignmentId = null;

function quickLogModal(assignmentId, programTitle) {
    currentAssignmentId = assignmentId;
    document.getElementById('quickLogModalLabel').textContent = `Log Progress - ${programTitle}`;

    // Load exercises for this assignment
    fetch(`/api/hep/assignments/${assignmentId}`)
        .then(response => response.json())
        .then(data => {
            const exerciseSelect = document.getElementById('exercise_select');
            exerciseSelect.innerHTML = '<option value="">Choose an exercise...</option>';

            data.program.hep_exercises.forEach(exercise => {
                const option = document.createElement('option');
                option.value = exercise.id;
                option.textContent = exercise.exercise.name;
                exerciseSelect.appendChild(option);
            });

            new bootstrap.Modal(document.getElementById('quickLogModal')).show();
        })
        .catch(error => {
            // console.error('Error loading exercises:', error);
            showHepError('Error loading exercises. Please try again.');
        });
}

// Handle quick log form submission
document.getElementById('quickLogForm').addEventListener('submit', function(e) {
    e.preventDefault();

    if (!currentAssignmentId) return;

    const formData = new FormData(this);

    fetch(`/patient/hep/assignment/${currentAssignmentId}/progress`, {
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
        // console.error('Error:', error);
        showHepError('An error occurred while logging progress');
    });
});

// Initialize voice synthesis voices
if ('speechSynthesis' in window) {
    speechSynthesis.onvoiceschanged = function() {
        // Voices loaded
    };
}

// Error display function
function showHepError(message) {
    const errorDiv = document.createElement('div');
    errorDiv.className = 'alert alert-danger alert-dismissible fade show position-fixed';
    errorDiv.style.cssText = 'top: 20px; right: 20px; z-index: 1050; min-width: 300px;';
    errorDiv.innerHTML = `
        <i class="fas fa-exclamation-circle me-2"></i>
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;
    document.body.appendChild(errorDiv);

    // Auto-dismiss after 5 seconds
    setTimeout(() => {
        if (errorDiv.parentNode) {
            errorDiv.remove();
        }
    }, 5000);
}

// Accessibility: Keyboard navigation enhancements
document.addEventListener('DOMContentLoaded', function() {
    // Add keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        // Alt + V for voice guidance
        if (e.altKey && e.key === 'v') {
            e.preventDefault();
            speakPageTitle();
        }
    });

    // Focus management for modals
    const modal = document.getElementById('quickLogModal');
    modal.addEventListener('shown.bs.modal', function() {
        document.getElementById('exercise_select').focus();
    });
});
</script>
@endpush
@endsection
