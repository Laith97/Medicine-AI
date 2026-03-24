@extends('master')

@section('title', $exercise->exercise->name . ' - Exercise Instructions')

@section('content')
<div class="dashboard-container">
    <div class="container-fluid px-3 px-md-4">
        <!-- Header -->
        <div class="dashboard-header py-3 border-bottom mb-4">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h2 mb-1" id="exercise-title">
                        <i class="fas fa-dumbbell text-primary me-2" aria-hidden="true"></i>
                        {{ $exercise->exercise->name }}
                    </h1>
                    <p class="text-muted mb-0" id="exercise-subtitle">Exercise instructions and progress tracking</p>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-info" onclick="speakExerciseInfo()" aria-label="Listen to exercise information">
                        <i class="fas fa-volume-up" aria-hidden="true"></i>
                        <span class="d-none d-sm-inline ms-1">Voice Guide</span>
                    </button>
                    <a href="{{ route('patient.hep.show', $assignment) }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2" aria-hidden="true"></i>
                        <span class="d-none d-sm-inline">Back to Program</span>
                    </a>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Main Content -->
            <div class="col-lg-8">
                <!-- Exercise Media -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-play-circle me-2 text-primary" aria-hidden="true"></i>
                            Exercise Demonstration
                        </h5>
                    </div>
                    <div class="card-body">
                        @if($exercise->exercise->video_url)
                            <div class="exercise-video-container mb-3">
                                <video controls class="w-100 rounded" style="max-height: 400px;" poster="{{ $exercise->exercise->image_url ?? '/images/exercise-placeholder.jpg' }}">
                                    <source src="{{ $exercise->exercise->video_url }}" type="video/mp4">
                                    <source src="{{ $exercise->exercise->video_url }}" type="video/webm">
                                    Your browser does not support the video tag.
                                    <track kind="captions" src="{{ $exercise->exercise->video_url }}.vtt" srclang="en" label="English">
                                </video>
                            </div>
                        @elseif($exercise->exercise->image_url)
                            <div class="exercise-image-container text-center mb-3">
                                <img src="{{ $exercise->exercise->image_url }}" alt="{{ $exercise->exercise->name }} demonstration"
                                     class="img-fluid rounded" style="max-height: 400px;">
                            </div>
                        @else
                            <div class="exercise-placeholder text-center mb-3">
                                <i class="fas fa-dumbbell fa-5x text-muted mb-3" aria-hidden="true"></i>
                                <p class="text-muted">No multimedia content available for this exercise.</p>
                            </div>
                        @endif

                        <!-- Voice Instructions -->
                        <div class="d-flex gap-2 mb-3">
                            <button type="button" class="btn btn-outline-primary" onclick="speakInstructions()">
                                <i class="fas fa-volume-up me-2" aria-hidden="true"></i>
                                Play Instructions
                            </button>
                            <button type="button" class="btn btn-outline-secondary" onclick="speakStepByStep()">
                                <i class="fas fa-list-ol me-2" aria-hidden="true"></i>
                                Step-by-Step Guide
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Exercise Details -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-info-circle me-2 text-info" aria-hidden="true"></i>
                            Exercise Details
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6>Prescription</h6>
                                <div class="exercise-details">
                                    @if($exercise->sets)
                                    <div class="detail-item">
                                        <strong>Sets:</strong> <span class="text-primary">{{ $exercise->sets }}</span>
                                    </div>
                                    @endif
                                    @if($exercise->reps)
                                    <div class="detail-item">
                                        <strong>Reps:</strong> <span class="text-primary">{{ $exercise->reps }}</span>
                                    </div>
                                    @endif
                                    @if($exercise->duration_seconds)
                                    <div class="detail-item">
                                        <strong>Duration:</strong> <span class="text-primary">{{ $exercise->duration_seconds }} seconds</span>
                                    </div>
                                    @endif
                                    @if($exercise->frequency)
                                    <div class="detail-item">
                                        <strong>Frequency:</strong> <span class="text-primary">{{ $exercise->frequency }}</span>
                                    </div>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h6>Progress Tracking</h6>
                                <div class="progress-tracking">
                                    <div class="detail-item">
                                        <strong>Week:</strong> <span class="text-success">{{ $exercise->week_number }}</span>
                                    </div>
                                    <div class="detail-item">
                                        <strong>Order:</strong> <span class="text-muted">{{ $exercise->order ?? 'N/A' }}</span>
                                    </div>
                                    <div class="detail-item">
                                        <strong>Completed:</strong>
                                        <span class="badge bg-{{ $progress->count() > 0 ? 'success' : 'warning' }}">
                                            {{ $progress->count() }} times
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        @if($exercise->notes)
                        <div class="mt-3">
                            <h6>Special Instructions</h6>
                            <div class="alert alert-info">
                                <i class="fas fa-sticky-note me-2" aria-hidden="true"></i>
                                {{ $exercise->notes }}
                            </div>
                        </div>
                        @endif

                        @if($exercise->exercise->description)
                        <div class="mt-3">
                            <h6>About This Exercise</h6>
                            <p class="text-muted">{{ $exercise->exercise->description }}</p>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Progress Logging -->
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-clipboard-check me-2" aria-hidden="true"></i>
                            Log Your Progress
                        </h5>
                    </div>
                    <div class="card-body">
                        <form id="progressForm">
                            @csrf
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="completed_sets" class="form-label">Sets Completed</label>
                                    <input type="number" class="form-control" id="completed_sets" name="completed_sets" min="0"
                                           value="{{ $exercise->sets }}" aria-describedby="sets-help">
                                    <div id="sets-help" class="form-text">Prescribed: {{ $exercise->sets ?? 'N/A' }}</div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="completed_reps" class="form-label">Reps Completed</label>
                                    <input type="number" class="form-control" id="completed_reps" name="completed_reps" min="0"
                                           value="{{ $exercise->reps }}" aria-describedby="reps-help">
                                    <div id="reps-help" class="form-text">Prescribed: {{ $exercise->reps ?? 'N/A' }}</div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="duration_completed" class="form-label">Duration Completed (seconds)</label>
                                <input type="number" class="form-control" id="duration_completed" name="duration_completed" min="0"
                                       value="{{ $exercise->duration_seconds }}" aria-describedby="duration-help">
                                <div id="duration-help" class="form-text">Prescribed: {{ $exercise->duration_seconds ?? 'N/A' }} seconds</div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="pain_level" class="form-label">
                                        Pain Level (0-10)
                                        <button type="button" class="btn btn-sm btn-outline-info ms-2" onclick="explainPainScale()" aria-label="Explain pain scale">
                                            <i class="fas fa-question-circle" aria-hidden="true"></i>
                                        </button>
                                    </label>
                                    <input type="range" class="form-range" id="pain_level" name="pain_level" min="0" max="10" value="0"
                                           oninput="updatePainDisplay(this.value)" aria-describedby="pain-display">
                                    <div class="text-center">
                                        <span id="pain-display" class="badge bg-secondary fs-6" aria-live="polite">0</span>
                                        <div class="small text-muted mt-1">No pain → Worst pain imaginable</div>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="difficulty_rating" class="form-label">
                                        Difficulty Level (1-5)
                                        <button type="button" class="btn btn-sm btn-outline-info ms-2" onclick="explainDifficultyScale()" aria-label="Explain difficulty scale">
                                            <i class="fas fa-question-circle" aria-hidden="true"></i>
                                    </label>
                                    <input type="range" class="form-range" id="difficulty_rating" name="difficulty_rating" min="1" max="5" value="3"
                                           oninput="updateDifficultyDisplay(this.value)" aria-describedby="difficulty-display">
                                    <div class="text-center">
                                        <span id="difficulty-display" class="badge bg-secondary fs-6" aria-live="polite">3</span>
                                        <div class="small text-muted mt-1">Very Easy → Very Difficult</div>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="progress_notes" class="form-label">Additional Notes (Optional)</label>
                                <textarea class="form-control" id="progress_notes" name="notes" rows="3"
                                          placeholder="How did the exercise feel? Any difficulties or observations?" aria-describedby="notes-help"></textarea>
                                <div id="notes-help" class="form-text">Help your doctor understand your experience</div>
                            </div>

                            <input type="hidden" name="hep_exercise_id" value="{{ $exercise->id }}">
                            <input type="hidden" name="date" value="{{ date('Y-m-d') }}">

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-success btn-lg">
                                    <i class="fas fa-save me-2" aria-hidden="true"></i>
                                    Log Progress
                                </button>
                                <button type="button" class="btn btn-outline-primary" onclick="startTimer()">
                                    <i class="fas fa-stopwatch me-2" aria-hidden="true"></i>
                                    Start Timer
                                </button>
                                <button type="button" class="btn btn-outline-secondary" onclick="resetForm()">
                                    <i class="fas fa-undo me-2" aria-hidden="true"></i>
                                    Reset
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Progress History -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="fas fa-history me-2 text-primary" aria-hidden="true"></i>
                            Your Progress History
                        </h6>
                    </div>
                    <div class="card-body">
                        @if($progress->count() > 0)
                            <div class="progress-timeline">
                                @foreach($progress->sortByDesc('date') as $session)
                                <div class="progress-item mb-3 pb-3 border-bottom">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <small class="text-muted fw-bold">{{ $session->date->format('M j, Y') }}</small>
                                        <small class="text-muted">{{ $session->date->format('h:i A') }}</small>
                                    </div>
                                    <div class="progress-details small">
                                        @if($session->completed_sets) <span><strong>Sets:</strong> {{ $session->completed_sets }}</span> @endif
                                        @if($session->completed_reps) <span><strong>Reps:</strong> {{ $session->completed_reps }}</span> @endif
                                        @if($session->duration_completed) <span><strong>Duration:</strong> {{ $session->duration_completed }}s</span> @endif
                                    </div>
                                    @if($session->pain_level)
                                    <div class="mt-1">
                                        <small>
                                            <strong>Pain:</strong>
                                            <span class="badge bg-{{ $session->pain_level > 7 ? 'danger' : ($session->pain_level > 4 ? 'warning' : 'success') }} badge-sm">
                                                {{ $session->pain_level }}/10
                                            </span>
                                        </small>
                                    </div>
                                    @endif
                                    @if($session->difficulty_rating)
                                    <div class="mt-1">
                                        <small><strong>Difficulty:</strong> {{ $session->difficulty_rating }}/5</small>
                                    </div>
                                    @endif
                                    @if($session->notes)
                                    <div class="mt-1">
                                        <small class="text-muted fst-italic">"{{ Str::limit($session->notes, 50) }}"</small>
                                    </div>
                                    @endif
                                </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-4">
                                <i class="fas fa-chart-line fa-3x text-muted mb-3" aria-hidden="true"></i>
                                <p class="text-muted small mb-0">No progress logged yet. Complete this exercise to start tracking your improvement!</p>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Quick Stats -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="fas fa-chart-bar me-2 text-success" aria-hidden="true"></i>
                            Quick Stats
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="stat-item">
                            <span class="stat-label">Times Completed:</span>
                            <span class="stat-value">{{ $progress->count() }}</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label">Average Pain:</span>
                            <span class="stat-value">{{ $progress->avg('pain_level') ? round($progress->avg('pain_level'), 1) : 'N/A' }}/10</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label">Average Difficulty:</span>
                            <span class="stat-value">{{ $progress->avg('difficulty_rating') ? round($progress->avg('difficulty_rating'), 1) : 'N/A' }}/5</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label">Last Completed:</span>
                            <span class="stat-value">{{ $progress->first() ? $progress->first()->date->format('M j') : 'Never' }}</span>
                        </div>
                    </div>
                </div>

                <!-- Program Context -->
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="fas fa-clipboard-list me-2 text-info" aria-hidden="true"></i>
                            Program Context
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="program-context">
                            <div class="context-item">
                                <span class="label">Program:</span>
                                <span class="value">{{ $assignment->hepProgram->title }}</span>
                            </div>
                            <div class="context-item">
                                <span class="label">Week:</span>
                                <span class="value">{{ $exercise->week_number }} of {{ $assignment->hepProgram->duration_weeks }}</span>
                            </div>
                            <div class="context-item">
                                <span class="label">Doctor:</span>
                                <span class="value">{{ $assignment->hepProgram->doctor->user->name }}</span>
                            </div>
                            <div class="context-item">
                                <span class="label">Overall Progress:</span>
                                <span class="value">{{ $assignment->getProgressPercentage() }}%</span>
                            </div>
                        </div>
                        <div class="mt-3">
                            <a href="{{ route('patient.hep.show', $assignment) }}" class="btn btn-outline-primary btn-sm w-100">
                                <i class="fas fa-arrow-left me-2" aria-hidden="true"></i>
                                Back to Program
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Timer Modal -->
<div class="modal fade" id="timerModal" tabindex="-1" aria-labelledby="timerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="timerModalLabel">Exercise Timer</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <div id="timer-display" class="display-4 mb-3" aria-live="polite">00:00</div>
                <div class="btn-group" role="group">
                    <button type="button" class="btn btn-success" id="start-timer-btn" onclick="startTimerAction()">
                        <i class="fas fa-play me-1" aria-hidden="true"></i>Start
                    </button>
                    <button type="button" class="btn btn-warning" id="pause-timer-btn" onclick="pauseTimerAction()" disabled>
                        <i class="fas fa-pause me-1" aria-hidden="true"></i>Pause
                    </button>
                    <button type="button" class="btn btn-danger" id="reset-timer-btn" onclick="resetTimerAction()">
                        <i class="fas fa-redo me-1" aria-hidden="true"></i>Reset
                    </button>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" onclick="useTimerValue()">Use This Time</button>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
.exercise-video-container video,
.exercise-image-container img {
    width: 100%;
    height: auto;
    border-radius: 8px;
}

.exercise-placeholder {
    padding: 3rem;
    border: 2px dashed #dee2e6;
    border-radius: 8px;
    background: #f8f9fa;
}

.exercise-details, .progress-tracking {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.detail-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.25rem 0;
}

.progress-timeline {
    max-height: 400px;
    overflow-y: auto;
}

.progress-item:last-child {
    border-bottom: none !important;
    padding-bottom: 0 !important;
}

.stat-item {
    display: flex;
    justify-content: space-between;
    padding: 0.5rem 0;
    border-bottom: 1px solid #f0f0f0;
}

.stat-item:last-child {
    border-bottom: none;
}

.stat-label {
    font-weight: 500;
    color: #6c757d;
}

.stat-value {
    font-weight: bold;
    color: #007bff;
}

.program-context, .program-info-item {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.context-item, .program-info-item {
    display: flex;
    justify-content: space-between;
    padding: 0.25rem 0;
    border-bottom: 1px solid #f0f0f0;
}

.context-item:last-child, .program-info-item:last-child {
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

#timer-display {
    font-family: monospace;
    font-weight: bold;
    color: #007bff;
}

/* High contrast mode support */
@media (prefers-contrast: high) {
    .card {
        border: 2px solid #000;
    }

    .form-range {
        border: 2px solid #000;
    }

    .badge {
        border: 1px solid #000;
    }
}

/* Mobile optimizations */
@media (max-width: 768px) {
    .dashboard-header .d-flex {
        flex-direction: column;
        gap: 1rem;
        align-items: flex-start !important;
    }

    .exercise-details, .progress-tracking {
        gap: 0.25rem;
    }

    .detail-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.25rem;
    }

    .stat-item, .context-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.25rem;
    }

    .value {
        text-align: left;
    }
}

/* Reduced motion support */
@media (prefers-reduced-motion: reduce) {
    .form-range {
        transition: none;
    }

    * {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.01ms !important;
    }
}
</style>
@endpush

@push('scripts')
<script>
// Voice guidance functionality
function speakExerciseInfo() {
    const title = document.getElementById('exercise-title').textContent;
    const subtitle = document.getElementById('exercise-subtitle').textContent;

    const utterance = new SpeechSynthesisUtterance(`${title}. ${subtitle}`);
    utterance.lang = '{{ app()->getLocale() }}';

    speechSynthesis.speak(utterance);
}

function speakInstructions() {
    const exerciseName = '{{ $exercise->exercise->name }}';
    const instructions = '{{ $exercise->exercise->description }}';

    const utterance = new SpeechSynthesisUtterance(`Exercise: ${exerciseName}. ${instructions}`);
    utterance.lang = '{{ app()->getLocale() }}';

    speechSynthesis.speak(utterance);
}

function speakStepByStep() {
    const exerciseName = '{{ $exercise->exercise->name }}';
    const sets = '{{ $exercise->sets }}';
    const reps = '{{ $exercise->reps }}';
    const duration = '{{ $exercise->duration_seconds }}';

    let instructions = `Step by step guide for ${exerciseName}. `;

    if (sets) instructions += `Perform ${sets} sets. `;
    if (reps) instructions += `Complete ${reps} repetitions per set. `;
    if (duration) instructions += `Each set should take ${duration} seconds. `;

    if ('{{ $exercise->notes }}') {
        instructions += `Special instructions: {{ $exercise->notes }}`;
    }

    const utterance = new SpeechSynthesisUtterance(instructions);
    utterance.lang = '{{ app()->getLocale() }}';

    speechSynthesis.speak(utterance);
}

// Pain and difficulty scale explanations
function explainPainScale() {
    const explanation = "The pain scale ranges from 0 to 10. 0 means no pain. 10 means the worst pain imaginable. Choose the number that best describes your current pain level.";
    const utterance = new SpeechSynthesisUtterance(explanation);
    utterance.lang = '{{ app()->getLocale() }}';
    speechSynthesis.speak(utterance);

    alert("Pain Scale:\n0 = No pain\n1-3 = Mild pain\n4-6 = Moderate pain\n7-9 = Severe pain\n10 = Worst pain imaginable");
}

function explainDifficultyScale() {
    const explanation = "The difficulty scale ranges from 1 to 5. 1 is very easy. 5 is very difficult. Choose the number that best describes how challenging this exercise felt.";
    const utterance = new SpeechSynthesisUtterance(explanation);
    utterance.lang = '{{ app()->getLocale() }}';
    speechSynthesis.speak(utterance);

    alert("Difficulty Scale:\n1 = Very Easy\n2 = Easy\n3 = Moderate\n4 = Difficult\n5 = Very Difficult");
}

// Display updates
function updatePainDisplay(value) {
    const display = document.getElementById('pain-display');
    display.textContent = value;

    // Update color based on pain level
    display.className = 'badge fs-6';
    if (value == 0) display.classList.add('bg-success');
    else if (value <= 3) display.classList.add('bg-info');
    else if (value <= 6) display.classList.add('bg-warning');
    else display.classList.add('bg-danger');
}

function updateDifficultyDisplay(value) {
    const display = document.getElementById('difficulty-display');
    display.textContent = value;

    // Update color based on difficulty
    display.className = 'badge fs-6';
    if (value <= 2) display.classList.add('bg-success');
    else if (value == 3) display.classList.add('bg-warning');
    else display.classList.add('bg-danger');
}

// Form handling
document.getElementById('progressForm').addEventListener('submit', function(e) {
    e.preventDefault();

    const formData = new FormData(this);
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;

    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2" aria-hidden="true"></i>Saving...';
    submitBtn.disabled = true;

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
            // Success message
            const successAlert = document.createElement('div');
            successAlert.className = 'alert alert-success alert-dismissible fade show mt-3';
            successAlert.innerHTML = `
                <i class="fas fa-check-circle me-2" aria-hidden="true"></i>
                Progress logged successfully!
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            `;

            this.insertBefore(successAlert, this.firstChild);

            // Reset form after short delay
            setTimeout(() => {
                this.reset();
                updatePainDisplay(0);
                updateDifficultyDisplay(3);
                successAlert.remove();
            }, 3000);

            // Refresh progress history
            setTimeout(() => location.reload(), 1000);
        } else {
            alert('Error: ' + (data.message || 'Failed to log progress'));
        }
    })
    .catch(error => {
        // console.error('Error:', error);
        alert('An error occurred while logging progress');
    })
    .finally(() => {
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    });
});

// Timer functionality
let timerInterval = null;
let timerSeconds = 0;
let timerRunning = false;

function startTimer() {
    new bootstrap.Modal(document.getElementById('timerModal')).show();
}

function startTimerAction() {
    if (!timerRunning) {
        timerRunning = true;
        document.getElementById('start-timer-btn').disabled = true;
        document.getElementById('pause-timer-btn').disabled = false;

        timerInterval = setInterval(() => {
            timerSeconds++;
            updateTimerDisplay();
        }, 1000);
    }
}

function pauseTimerAction() {
    timerRunning = false;
    document.getElementById('start-timer-btn').disabled = false;
    document.getElementById('pause-timer-btn').disabled = true;
    clearInterval(timerInterval);
}

function resetTimerAction() {
    timerRunning = false;
    timerSeconds = 0;
    updateTimerDisplay();
    document.getElementById('start-timer-btn').disabled = false;
    document.getElementById('pause-timer-btn').disabled = true;
    clearInterval(timerInterval);
}

function updateTimerDisplay() {
    const minutes = Math.floor(timerSeconds / 60);
    const seconds = timerSeconds % 60;
    const display = document.getElementById('timer-display');
    display.textContent = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
}

function useTimerValue() {
    document.getElementById('duration_completed').value = timerSeconds;
    bootstrap.Modal.getInstance(document.getElementById('timerModal')).hide();
    resetTimerAction();
}

function resetForm() {
    document.getElementById('progressForm').reset();
    updatePainDisplay(0);
    updateDifficultyDisplay(3);
}

// Initialize displays
document.addEventListener('DOMContentLoaded', function() {
    updatePainDisplay(0);
    updateDifficultyDisplay(3);

    // Keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        if (e.altKey && e.key === 'v') {
            e.preventDefault();
            speakExerciseInfo();
        }
        if (e.altKey && e.key === 'i') {
            e.preventDefault();
            speakInstructions();
        }
        if (e.altKey && e.key === 's') {
            e.preventDefault();
            speakStepByStep();
        }
    });
});
</script>
@endpush
@endsection
