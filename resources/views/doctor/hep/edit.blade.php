@extends('master')

@section('title', 'Edit ' . $program->title . ' - HEP Program')

@section('content')
<div class="dashboard-container">
    <div class="container">
        <!-- Header -->
        <div class="dashboard-header py-2 border-bottom">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2>Edit HEP Program</h2>
                    <p class="mb-0">{{ $program->title }}</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('doctor.hep.show', $program) }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Program
                    </a>
                </div>
            </div>
        </div>

        <form method="POST" action="{{ route('doctor.hep.update', $program) }}" class="mt-4">
            @csrf
            @method('PUT')

            <div class="row">
                <div class="col-lg-8">
                    <!-- Program Details -->
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-edit me-2"></i>Program Details</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="mb-3">
                                        <label for="title" class="form-label">Program Title *</label>
                                        <input type="text" class="form-control" id="title" name="title"
                                               value="{{ old('title', $program->title) }}" required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="status" class="form-label">Status</label>
                                        <select class="form-select" id="status" name="status">
                                            <option value="draft" {{ $program->status === 'draft' ? 'selected' : '' }}>Draft</option>
                                            <option value="active" {{ $program->status === 'active' ? 'selected' : '' }}>Active</option>
                                            <option value="completed" {{ $program->status === 'completed' ? 'selected' : '' }}>Completed</option>
                                            <option value="cancelled" {{ $program->status === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="duration_weeks" class="form-label">Duration (Weeks) *</label>
                                        <input type="number" class="form-control" id="duration_weeks" name="duration_weeks"
                                               value="{{ old('duration_weeks', $program->duration_weeks) }}" min="1" max="52" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Patient</label>
                                        <input type="text" class="form-control" value="{{ $program->patient ? $program->patient->name : 'No patient assigned' }}" readonly>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="3">{{ old('description', $program->description) }}</textarea>
                            </div>

                            <div class="mb-3">
                                <label for="goals" class="form-label">Goals & Objectives</label>
                                <textarea class="form-control" id="goals" name="goals" rows="3">{{ old('goals', $program->goals) }}</textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Exercises Section -->
                    <div class="card mt-4">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5><i class="fas fa-dumbbell me-2"></i>Exercises</h5>
                            <button type="button" class="btn btn-sm btn-outline-primary" id="add-exercise-btn">
                                <i class="fas fa-plus me-1"></i>Add Exercise
                            </button>
                        </div>
                        <div class="card-body">
                            <div id="exercises-container">
                                @foreach($program->hepExercises->sortBy(['week_number', 'order']) as $index => $hepExercise)
                                    <div class="exercise-item border rounded p-3 mb-3" data-exercise-id="{{ $hepExercise->id }}">
                                        <div class="exercise-header d-flex justify-content-between align-items-center mb-3">
                                            <h6 class="mb-0">{{ $hepExercise->exercise->name }}</h6>
                                            <button type="button" class="btn btn-sm btn-outline-danger remove-exercise-btn">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-2">
                                                <label class="form-label">Week</label>
                                                <input type="number" class="form-control" name="exercises[{{ $index }}][week_number]"
                                                       value="{{ $hepExercise->week_number }}" min="1" required>
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label">Order</label>
                                                <input type="number" class="form-control" name="exercises[{{ $index }}][order]"
                                                       value="{{ $hepExercise->order ?? $index }}" min="0">
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label">Sets</label>
                                                <input type="number" class="form-control" name="exercises[{{ $index }}][sets]"
                                                       value="{{ $hepExercise->sets }}" min="1">
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label">Reps</label>
                                                <input type="number" class="form-control" name="exercises[{{ $index }}][reps]"
                                                       value="{{ $hepExercise->reps }}" min="1">
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label">Duration (sec)</label>
                                                <input type="number" class="form-control" name="exercises[{{ $index }}][duration_seconds]"
                                                       value="{{ $hepExercise->duration_seconds }}" min="1">
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label">Frequency</label>
                                                <input type="text" class="form-control" name="exercises[{{ $index }}][frequency]"
                                                       value="{{ $hepExercise->frequency }}" placeholder="e.g., Daily, 3x/week">
                                            </div>
                                        </div>

                                        <div class="mt-3">
                                            <label class="form-label">Notes</label>
                                            <textarea class="form-control" name="exercises[{{ $index }}][notes]" rows="2">{{ $hepExercise->notes }}</textarea>
                                        </div>

                                        <input type="hidden" name="exercises[{{ $index }}][exercise_id]" value="{{ $hepExercise->exercise_id }}">
                                        <input type="hidden" name="exercises[{{ $index }}][existing_id]" value="{{ $hepExercise->id }}">
                                    </div>
                                @endforeach
                            </div>

                            @if($program->hepExercises->isEmpty())
                                <div class="text-center text-muted py-4">
                                    <i class="fas fa-dumbbell fa-3x mb-3"></i>
                                    <p>No exercises added yet. Click "Add Exercise" to get started.</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="col-lg-4">
                    <!-- Quick Actions -->
                    <div class="card">
                        <div class="card-header">
                            <h6>Actions</h6>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-save me-2"></i>Save Changes
                                </button>

                                <a href="{{ route('doctor.hep.show', $program) }}" class="btn btn-outline-secondary">
                                    <i class="fas fa-eye me-2"></i>Preview Program
                                </a>

                                @if($program->hepAssignments->isEmpty())
                                    <a href="{{ route('doctor.hep.show', $program) }}#assign" class="btn btn-outline-success">
                                        <i class="fas fa-user-plus me-2"></i>Assign to Patient
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Program Stats -->
                    <div class="card mt-4">
                        <div class="card-header">
                            <h6>Current Statistics</h6>
                        </div>
                        <div class="card-body">
                            <div class="stat-item">
                                <span class="stat-label">Total Exercises:</span>
                                <span class="stat-value">{{ $program->hepExercises->count() }}</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-label">Unique Exercises:</span>
                                <span class="stat-value">{{ $program->hepExercises->unique('exercise_id')->count() }}</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-label">Weeks Covered:</span>
                                <span class="stat-value">{{ $program->hepExercises->max('week_number') }}</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-label">Status:</span>
                                <span class="stat-value">
                                    <span class="badge bg-{{ $program->status === 'active' ? 'success' : ($program->status === 'draft' ? 'warning' : 'secondary') }}">
                                        {{ ucfirst($program->status) }}
                                    </span>
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Exercise Categories -->
                    <div class="card mt-4">
                        <div class="card-header">
                            <h6>Exercise Categories</h6>
                        </div>
                        <div class="card-body">
                            <div class="exercise-categories">
                                @foreach($exerciseCategories as $category)
                                    <span class="badge bg-secondary me-1 mb-1 category-filter" data-category="{{ $category }}" style="cursor: pointer;">
                                        {{ ucfirst($category) }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Exercise Selection Modal -->
<div class="modal fade" id="exerciseModal" tabindex="-1" size="xl">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Select Exercise</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <input type="text" class="form-control" id="exerciseSearch" placeholder="Search exercises...">
                </div>
                <div class="exercise-selection-grid">
                    <!-- Exercises will be loaded here -->
                    <div class="text-center">
                        <i class="fas fa-spinner fa-spin fa-2x mb-3"></i>
                        <p>Loading exercises...</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
.exercise-item {
    position: relative;
}

.exercise-header {
    border-bottom: 1px solid #f0f0f0;
    padding-bottom: 0.5rem;
}

.remove-exercise-btn {
    opacity: 0.7;
}

.remove-exercise-btn:hover {
    opacity: 1;
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
}

.stat-value {
    font-weight: bold;
    color: #007bff;
}

.exercise-categories {
    max-height: 200px;
    overflow-y: auto;
}

.category-filter:hover {
    background-color: #007bff !important;
}

.exercise-selection-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 1rem;
    max-height: 400px;
    overflow-y: auto;
}

.exercise-select-card {
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 1rem;
    cursor: pointer;
    transition: all 0.3s ease;
}

.exercise-select-card:hover {
    border-color: #007bff;
    box-shadow: 0 2px 8px rgba(0,123,255,0.15);
}

.exercise-select-card.selected {
    border-color: #007bff;
    background-color: #f8f9ff;
}
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    let exerciseIndex = {{ $program->hepExercises->count() }};

    // Add exercise functionality
    document.getElementById('add-exercise-btn').addEventListener('click', function() {
        const modal = new bootstrap.Modal(document.getElementById('exerciseModal'));
        loadExercises();
        modal.show();
    });

    // Remove exercise functionality
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-exercise-btn') || e.target.closest('.remove-exercise-btn')) {
            const exerciseItem = e.target.closest('.exercise-item');
            if (confirm('Are you sure you want to remove this exercise from the program?')) {
                exerciseItem.remove();
                updateExerciseIndices();
            }
        }
    });

    function loadExercises() {
        const container = document.querySelector('.exercise-selection-grid');
        container.innerHTML = '<div class="text-center"><i class="fas fa-spinner fa-spin fa-2x mb-3"></i><p>Loading exercises...</p></div>';

        // In a real implementation, you'd make an AJAX call to get exercises
        // For demo purposes, we'll simulate loading
        setTimeout(() => {
            container.innerHTML = `
                <div class="exercise-select-card" data-exercise-id="1" data-exercise-name="Squats">
                    <h6>Squats</h6>
                    <p class="text-muted small">Lower body strengthening exercise</p>
                    <span class="badge bg-primary">Strength</span>
                </div>
                <div class="exercise-select-card" data-exercise-id="2" data-exercise-name="Push-ups">
                    <h6>Push-ups</h6>
                    <p class="text-muted small">Upper body strengthening exercise</p>
                    <span class="badge bg-primary">Strength</span>
                </div>
                <div class="exercise-select-card" data-exercise-id="3" data-exercise-name="Planks">
                    <h6>Planks</h6>
                    <p class="text-muted small">Core stability exercise</p>
                    <span class="badge bg-success">Core</span>
                </div>
            `;

            // Add click handlers to exercise cards
            document.querySelectorAll('.exercise-select-card').forEach(card => {
                card.addEventListener('click', function() {
                    document.querySelectorAll('.exercise-select-card').forEach(c => c.classList.remove('selected'));
                    this.classList.add('selected');

                    setTimeout(() => {
                        addExerciseToProgram(this.dataset.exerciseId, this.dataset.exerciseName);
                        bootstrap.Modal.getInstance(document.getElementById('exerciseModal')).hide();
                    }, 300);
                });
            });
        }, 500);
    }

    function addExerciseToProgram(exerciseId, exerciseName) {
        const container = document.getElementById('exercises-container');
        const exerciseHtml = `
            <div class="exercise-item border rounded p-3 mb-3" data-exercise-id="new-${exerciseIndex}">
                <div class="exercise-header d-flex justify-content-between align-items-center mb-3">
                    <h6 class="mb-0">${exerciseName}</h6>
                    <button type="button" class="btn btn-sm btn-outline-danger remove-exercise-btn">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>

                <div class="row">
                    <div class="col-md-2">
                        <label class="form-label">Week</label>
                        <input type="number" class="form-control" name="exercises[${exerciseIndex}][week_number]" value="1" min="1" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Order</label>
                        <input type="number" class="form-control" name="exercises[${exerciseIndex}][order]" value="${exerciseIndex}" min="0">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Sets</label>
                        <input type="number" class="form-control" name="exercises[${exerciseIndex}][sets]" min="1">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Reps</label>
                        <input type="number" class="form-control" name="exercises[${exerciseIndex}][reps]" min="1">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Duration (sec)</label>
                        <input type="number" class="form-control" name="exercises[${exerciseIndex}][duration_seconds]" min="1">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Frequency</label>
                        <input type="text" class="form-control" name="exercises[${exerciseIndex}][frequency]" placeholder="e.g., Daily, 3x/week">
                    </div>
                </div>

                <div class="mt-3">
                    <label class="form-label">Notes</label>
                    <textarea class="form-control" name="exercises[${exerciseIndex}][notes]" rows="2"></textarea>
                </div>

                <input type="hidden" name="exercises[${exerciseIndex}][exercise_id]" value="${exerciseId}">
            </div>
        `;

        container.insertAdjacentHTML('beforeend', exerciseHtml);
        exerciseIndex++;
    }

    function updateExerciseIndices() {
        const exerciseItems = document.querySelectorAll('.exercise-item');
        exerciseItems.forEach((item, index) => {
            const inputs = item.querySelectorAll('input, textarea, select');
            inputs.forEach(input => {
                if (input.name) {
                    input.name = input.name.replace(/\[\d+\]/, `[${index}]`);
                }
            });
        });
    }

    // Exercise search
    document.getElementById('exerciseSearch')?.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        const exerciseCards = document.querySelectorAll('.exercise-select-card');

        exerciseCards.forEach(card => {
            const exerciseName = card.querySelector('h6').textContent.toLowerCase();
            const exerciseDesc = card.querySelector('p').textContent.toLowerCase();

            if (exerciseName.includes(searchTerm) || exerciseDesc.includes(searchTerm)) {
                card.style.display = 'block';
            } else {
                card.style.display = 'none';
            }
        });
    });
});
</script>
@endpush
@endsection
