@extends('master')

@section('title', $program->title . ' - Physical Therapy HEP Program')

@section('content')
<div class="dashboard-container">
    <div class="container">
        <!-- Header -->
        <div class="dashboard-header py-2 border-bottom">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2>{{ $program->title }}</h2>
                    <p class="mb-0">Physical Therapy (Home Exercise Program) Details</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('doctor.hep.edit', $program) }}" class="btn btn-outline-primary">
                        <i class="fas fa-edit me-2"></i>Edit Program
                    </a>
                    <a href="{{ route('doctor.hep.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Programs
                    </a>
                </div>
            </div>
        </div>

        <!-- Program Overview -->
        <div class="row mt-4">
            <div class="col-lg-8">
                <!-- Program Info Card -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5><i class="fas fa-info-circle me-2"></i>Program Information</h5>
                        <span class="badge bg-{{ $program->status === 'active' ? 'success' : ($program->status === 'draft' ? 'warning' : ($program->status === 'completed' ? 'primary' : 'secondary')) }}">
                            {{ ucfirst($program->status) }}
                        </span>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6>Patient Information</h6>
                                @if($program->patient)
                                    <p class="mb-1"><strong>Name:</strong> {{ $program->patient->name }}</p>
                                    <p class="mb-1"><strong>Email:</strong> {{ $program->patient->email }}</p>
                                    @if($program->patient->phone)
                                        <p class="mb-1"><strong>Phone:</strong> {{ $program->patient->phone }}</p>
                                    @endif
                                @else
                                    <p class="text-muted">No patient assigned</p>
                                @endif
                            </div>
                            <div class="col-md-6">
                                <h6>Program Details</h6>
                                <p class="mb-1"><strong>Duration:</strong> {{ $program->duration_weeks }} weeks</p>
                                <p class="mb-1"><strong>Total Exercises:</strong> {{ $program->hepExercises->count() }}</p>
                                <p class="mb-1"><strong>Created:</strong> {{ $program->created_at->format('M j, Y') }}</p>
                                @if($program->diagnosis)
                                    <p class="mb-1"><strong>Diagnosis:</strong> {{ $program->diagnosis->diagnosis_name }}</p>
                                @endif
                            </div>
                        </div>

                        @if($program->description)
                            <div class="mt-3">
                                <h6>Description</h6>
                                <p>{{ $program->description }}</p>
                            </div>
                        @endif

                        @if($program->goals && count($program->goals) > 0)
                            <div class="mt-3">
                                <h6>Goals & Objectives</h6>
                                <ul class="mb-0">
                                    @foreach($program->goals as $goal)
                                        <li>{{ $goal }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Assignment & Progress -->
                @if($assignment)
                    <div class="card mt-4">
                        <div class="card-header">
                            <h5><i class="fas fa-chart-line me-2"></i>Patient Progress</h5>
                        </div>
                        <div class="card-body">
                            <div class="row text-center">
                                <div class="col-md-3">
                                    <div class="progress-circle" data-progress="{{ $progressStats['completion_percentage'] }}">
                                        <div class="progress-value">{{ $progressStats['completion_percentage'] }}%</div>
                                    </div>
                                    <small class="text-muted">Overall Progress</small>
                                </div>
                                <div class="col-md-3">
                                    <h3 class="text-primary">{{ $progressStats['completed_exercises'] }}</h3>
                                    <small class="text-muted">Exercises Completed</small>
                                </div>
                                <div class="col-md-3">
                                    <h3 class="text-info">{{ $progressStats['current_week'] }}</h3>
                                    <small class="text-muted">Current Week</small>
                                </div>
                                <div class="col-md-3">
                                    <h3 class="text-success">{{ $assignment->hepProgress->count() }}</h3>
                                    <small class="text-muted">Total Sessions</small>
                                </div>
                            </div>

                            <div class="mt-4">
                                <a href="{{ route('doctor.hep.progress', $program) }}" class="btn btn-outline-primary">
                                    <i class="fas fa-eye me-2"></i>View Detailed Progress
                                </a>
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Quick Actions -->
                <div class="card">
                    <div class="card-header">
                        <h6>Quick Actions</h6>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            @if($program->hepAssignments->isEmpty())
                                <button type="button" class="btn btn-success assign-program-btn">
                                    <i class="fas fa-user-plus me-2"></i>Assign to Patient
                                </button>
                            @endif

                            <a href="{{ route('doctor.hep.edit', $program) }}" class="btn btn-primary">
                                <i class="fas fa-edit me-2"></i>Edit Program
                            </a>

                            <button type="button" class="btn btn-outline-info duplicate-program-btn">
                                <i class="fas fa-copy me-2"></i>Duplicate Program
                            </button>

                            <button type="button" class="btn btn-outline-danger delete-program-btn">
                                <i class="fas fa-trash me-2"></i>Delete Program
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Program Stats -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h6>Program Statistics</h6>
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
                        @if($assignment)
                            <div class="stat-item">
                                <span class="stat-label">Patient Compliance:</span>
                                <span class="stat-value">{{ $progressStats['completion_percentage'] }}%</span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Exercise Program -->
        <div class="card mt-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5><i class="fas fa-dumbbell me-2"></i>Exercise Program</h5>
                <div class="btn-group" role="group">
                    <button type="button" class="btn btn-sm btn-outline-secondary active" data-view="week">Week View</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-view="exercise">Exercise View</button>
                </div>
            </div>
            <div class="card-body">
                <!-- Week View -->
                <div id="week-view">
                    @foreach($exercisesByWeek as $week => $exercises)
                        <div class="week-section mb-4">
                            <h6 class="week-header">
                                <i class="fas fa-calendar-week me-2"></i>Week {{ $week }}
                                <span class="badge bg-primary ms-2">{{ $exercises->count() }} exercises</span>
                            </h6>
                            <div class="exercises-grid">
                                @foreach($exercises as $hepExercise)
                                    <div class="exercise-card">
                                        <div class="exercise-header">
                                            <h6>{{ $hepExercise->exercise->name }}</h6>
                                            @if($hepExercise->exercise->image_url)
                                                <img src="{{ $hepExercise->exercise->image_url }}" alt="{{ $hepExercise->exercise->name }}"
                                                     class="exercise-image">
                                            @else
                                                <div class="exercise-placeholder">
                                                    <i class="fas fa-dumbbell"></i>
                                                </div>
                                            @endif
                                        </div>
                                        <div class="exercise-details">
                                            <div class="detail-item">
                                                <strong>Sets:</strong> {{ $hepExercise->sets ?? 'N/A' }}
                                            </div>
                                            <div class="detail-item">
                                                <strong>Reps:</strong> {{ $hepExercise->reps ?? 'N/A' }}
                                            </div>
                                            @if($hepExercise->duration_seconds)
                                                <div class="detail-item">
                                                    <strong>Duration:</strong> {{ $hepExercise->duration_seconds }}s
                                                </div>
                                            @endif
                                            @if($hepExercise->frequency)
                                                <div class="detail-item">
                                                    <strong>Frequency:</strong> {{ $hepExercise->frequency }}
                                                </div>
                                            @endif
                                        </div>
                                        @if($hepExercise->notes)
                                            <div class="exercise-notes">
                                                <strong>Notes:</strong> {{ $hepExercise->notes }}
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Exercise View (Hidden by default) -->
                <div id="exercise-view" style="display: none;">
                    <div class="exercises-list">
                        @foreach($program->hepExercises->groupBy('exercise.name') as $exerciseName => $hepExercises)
                            <div class="exercise-section mb-4">
                                <h6 class="exercise-header">
                                    <i class="fas fa-dumbbell me-2"></i>{{ $exerciseName }}
                                    <span class="badge bg-secondary ms-2">{{ $hepExercises->count() }} weeks</span>
                                </h6>
                                <div class="progression-table">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Week</th>
                                                <th>Sets</th>
                                                <th>Reps</th>
                                                <th>Duration</th>
                                                <th>Frequency</th>
                                                <th>Notes</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($hepExercises->sortBy('week_number') as $hepExercise)
                                                <tr>
                                                    <td>{{ $hepExercise->week_number }}</td>
                                                    <td>{{ $hepExercise->sets ?? '-' }}</td>
                                                    <td>{{ $hepExercise->reps ?? '-' }}</td>
                                                    <td>{{ $hepExercise->duration_seconds ? $hepExercise->duration_seconds . 's' : '-' }}</td>
                                                    <td>{{ $hepExercise->frequency ?? '-' }}</td>
                                                    <td>{{ $hepExercise->notes ?? '-' }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Assign Program Modal -->
<div class="modal fade" id="assignProgramModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Assign HEP Program</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="assignProgramForm" method="POST" action="{{ route('doctor.hep.assign', $program) }}">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="assign_patient_id" class="form-label">Select Patient</label>
                        <select class="form-select" id="assign_patient_id" name="patient_id" required>
                            <option value="">Choose a patient...</option>
                            @foreach($patients as $patient)
                                <option value="{{ $patient->id }}">{{ $patient->name }} ({{ $patient->email }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="assign_notes" class="form-label">Assignment Notes (Optional)</label>
                        <textarea class="form-control" id="assign_notes" name="notes" rows="3" placeholder="Any special instructions for the patient..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Assign Program</button>
                </div>
            </form>
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

.week-section {
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 1.5rem;
    background: #f8f9fa;
}

.week-header {
    color: #495057;
    margin-bottom: 1rem;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid #dee2e6;
}

.exercises-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 1rem;
}

.exercise-card {
    background: white;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 1rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.exercise-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.exercise-image {
    width: 60px;
    height: 60px;
    object-fit: cover;
    border-radius: 6px;
}

.exercise-placeholder {
    width: 60px;
    height: 60px;
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #6c757d;
}

.exercise-details {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.5rem;
    margin-bottom: 0.5rem;
}

.detail-item {
    font-size: 0.9rem;
}

.exercise-notes {
    font-size: 0.9rem;
    color: #6c757d;
    padding-top: 0.5rem;
    border-top: 1px solid #f0f0f0;
}

.exercise-section {
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 1.5rem;
    background: white;
}

.exercise-header {
    color: #495057;
    margin-bottom: 1rem;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid #dee2e6;
}

.progression-table .table th {
    background: #f8f9fa;
    font-weight: 600;
    font-size: 0.85rem;
}

@media (max-width: 768px) {
    .exercises-grid {
        grid-template-columns: 1fr;
    }

    .exercise-details {
        grid-template-columns: 1fr;
    }
}
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Progress circle animation
    const progressCircles = document.querySelectorAll('.progress-circle');
    progressCircles.forEach(circle => {
        const progress = circle.dataset.progress;
        circle.style.setProperty('--progress', progress + '%');
    });

    // View toggle
    const viewButtons = document.querySelectorAll('[data-view]');
    viewButtons.forEach(button => {
        button.addEventListener('click', function() {
            const view = this.dataset.view;

            // Update button states
            viewButtons.forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');

            // Show/hide views
            document.getElementById('week-view').style.display = view === 'week' ? 'block' : 'none';
            document.getElementById('exercise-view').style.display = view === 'exercise' ? 'block' : 'none';
        });
    });

    // Assign program functionality
    const assignBtn = document.querySelector('.assign-program-btn');
    if (assignBtn) {
        assignBtn.addEventListener('click', function() {
            const modal = new bootstrap.Modal(document.getElementById('assignProgramModal'));
            loadPatientsForAssignment();
            modal.show();
        });
    }

    function loadPatientsForAssignment() {
        // Patients are now loaded from the server, no need for AJAX
        const modal = new bootstrap.Modal(document.getElementById('assignProgramModal'));
        // Modal is already shown by the caller
    }

    // Handle form submission
    const assignForm = document.getElementById('assignProgramForm');
    if (assignForm) {
        assignForm.addEventListener('submit', function(e) {
            // Allow the form to submit normally without preventing default
            // This will ensure proper form submission and redirect handling

            const formData = new FormData(this);

            // Convert form data to URL encoded format to ensure proper processing
            const params = new URLSearchParams();
            for (const [key, value] of formData.entries()) {
                params.append(key, value);
            }

            fetch(this.action, {
                method: 'POST',
                body: params,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'Content-Type': 'application/x-www-form-urlencoded'
                }
            })
            .then(response => {
                // Check if response is a redirect (302)
                if (response.redirected) {
                    // For redirects, we'll just reload the page to show the updated status
                    window.location.reload();
                    return;
                }

                // For non-redirect responses, check content type
                const contentType = response.headers.get('content-type');
                if (contentType && contentType.includes('application/json')) {
                    // Process as JSON
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                } else {
                    // If not JSON, just reload the page
                    window.location.reload();
                    return;
                }
            })
            .then(data => {
                if (data.success) {
                    // Hide the modal
                    const modal = bootstrap.Modal.getInstance(document.getElementById('assignProgramModal'));
                    modal.hide();
                    
                    // Show success message
                    const alert = document.createElement('div');
                    alert.className = 'alert alert-success alert-dismissible fade show';
                    alert.innerHTML = `
                        ${data.message}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    `;
                    
                    // Insert alert at the top of the page
                    const container = document.querySelector('.container-fluid');
                    container.insertBefore(alert, container.firstChild);
                    
                    // Refresh the page after a short delay to show updated status
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    // Show error message
                    const alert = document.createElement('div');
                    alert.className = 'alert alert-danger alert-dismissible fade show';
                    alert.innerHTML = `
                        Error: ${data.message || 'Failed to assign program'}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    `;
                    
                    // Insert alert at the top of the page
                    const container = document.querySelector('.container-fluid');
                    container.insertBefore(alert, container.firstChild);
                }
            })
            .catch(error => {
                // console.error('Error:', error);
                
                // Show error message
                const alert = document.createElement('div');
                alert.className = 'alert alert-danger alert-dismissible fade show';
                alert.innerHTML = `
                    An error occurred while assigning the program
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                `;
                
                // Insert alert at the top of the page
                const container = document.querySelector('.container-fluid');
                container.insertBefore(alert, container.firstChild);
            });
        });
    }

    // Delete program functionality
    const deleteBtn = document.querySelector('.delete-program-btn');
    if (deleteBtn) {
        deleteBtn.addEventListener('click', function() {
            if (confirm('Are you sure you want to delete this HEP program? This action cannot be undone.')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '{{ route("doctor.hep.destroy", $program) }}';

                const csrfToken = document.createElement('input');
                csrfToken.type = 'hidden';
                csrfToken.name = '_token';
                csrfToken.value = document.querySelector('meta[name="csrf-token"]').content;
                form.appendChild(csrfToken);

                const methodField = document.createElement('input');
                methodField.type = 'hidden';
                methodField.name = '_method';
                methodField.value = 'DELETE';
                form.appendChild(methodField);

                document.body.appendChild(form);
                form.submit();
            }
        });
    }
});
</script>
@endpush
@endsection
