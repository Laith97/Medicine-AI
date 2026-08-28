@extends('master')

@section('title', 'Create Physical Therapy - HEP Program')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/doctor-design-system.css') }}">
<link rel="stylesheet" href="{{ asset('css/doctor-dashboard.css') }}">
<link rel="stylesheet" href="{{ asset('css/cases-overview.css') }}">
<style>
.progress-steps {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    flex-wrap: wrap;
}
.step {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 0.85rem 1.1rem;
    border-radius: 10px;
    background: #fff;
    border: 1px solid var(--gray-200, #e9ecef);
    box-shadow: var(--shadow-sm, 0 2px 4px rgba(0,0,0,0.06));
    transition: all 0.3s ease;
    min-width: 130px;
}
.step.active {
    background: var(--primary-color, #2c3e50);
    color: white;
    border-color: var(--primary-color, #2c3e50);
    box-shadow: var(--shadow-md, 0 4px 15px rgba(0,0,0,0.08));
}
.step-number {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: var(--gray-200, #e9ecef);
    color: var(--gray-600, #495057);
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    margin-bottom: 0.35rem;
    font-size: 0.9rem;
}
.step.active .step-number {
    background: white;
    color: var(--primary-color, #2c3e50);
}
.step-label {
    font-size: 0.82rem;
    font-weight: 600;
    text-align: center;
}
.step-arrow {
    color: var(--gray-400, #adb5bd);
    font-size: 0.8rem;
}
.step-content {
    display: none;
}
.step-content.active {
    display: block;
}
.method-card {
    border: 1px solid var(--gray-200, #e9ecef);
    border-radius: 12px;
    padding: 1.5rem;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s ease;
    background: white;
    height: 100%;
    box-shadow: var(--shadow-sm, 0 2px 4px rgba(0,0,0,0.06));
}
.method-card:hover {
    border-color: var(--secondary-color, #3498db);
    box-shadow: 0 4px 12px rgba(52,152,219,0.12);
    transform: translateY(-2px);
}
.method-card.selected {
    border-color: var(--secondary-color, #3498db);
    background: #f8f9ff;
    box-shadow: 0 4px 12px rgba(52,152,219,0.15);
}
.method-icon {
    width: 56px;
    height: 56px;
    border-radius: 12px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.4rem;
    margin: 0 auto 1rem;
    box-shadow: 0 4px 12px rgba(102,126,234,0.3);
}
.exercise-item {
    border: 1px solid var(--gray-200, #e9ecef);
    border-radius: 10px;
    padding: 1rem;
    margin-bottom: 1rem;
    background: white;
}
.exercise-select-card {
    cursor: pointer;
    transition: all 0.3s ease;
}
.exercise-select-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 16px rgba(0,0,0,0.08);
}
.exercise-select-card.selected {
    border: 2px solid var(--secondary-color, #3498db);
    box-shadow: 0 0 0 0.2rem rgba(52,152,219,0.15);
}
.exercise-select-card .card {
    height: 100%;
}
</style>
@endpush

@section('content')
<div class="container-fluid" style="background-color: var(--bg-secondary, #f8f9fa);">
    <div class="container py-4">
        <div class="dashboard-header cases-header-compact">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <h2><i class="fas fa-plus me-2"></i>Create Home Exercise Program</h2>
                    <p>Design a personalized exercise program for your patient</p>
                </div>
                <a href="{{ route('doctor.hep.index') }}" class="btn btn-outline-secondary bg-white">
                    <i class="fas fa-arrow-left me-2"></i>Back to Programs
                </a>
            </div>
        </div>

        <!-- Progress Steps -->
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body py-3">
                <div class="progress-steps">
                    <div class="step active" id="step1">
                        <div class="step-number">1</div>
                        <div class="step-label">Select Diagnosis</div>
                    </div>
                    <div class="step-arrow"><i class="fas fa-chevron-right"></i></div>
                    <div class="step" id="step2">
                        <div class="step-number">2</div>
                        <div class="step-label">Choose Method</div>
                    </div>
                    <div class="step-arrow"><i class="fas fa-chevron-right"></i></div>
                    <div class="step" id="step3">
                        <div class="step-number">3</div>
                        <div class="step-label">Design Program</div>
                    </div>
                    <div class="step-arrow"><i class="fas fa-chevron-right"></i></div>
                    <div class="step" id="step4">
                        <div class="step-number">4</div>
                        <div class="step-label">Review & Save</div>
                    </div>
                </div>
            </div>
        </div>

        <form id="hepForm" method="POST" action="{{ route('doctor.hep.store') }}" enctype="multipart/form-data">
            @csrf
            <input type="hidden" id="generated_program_id" name="generated_program_id">

            <!-- Step 1: Diagnosis Selection -->
            <div class="step-content active" id="step1-content">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-bottom">
                        <h5 class="mb-0 fw-semibold"><i class="fas fa-stethoscope me-2 text-primary"></i>Step 1: Select Diagnosis & Patient</h5>
                    </div>
                    <div class="card-body">
                        @if($selectedDiagnosis)
                            <div class="alert alert-info border-0 shadow-sm">
                                <h6 class="fw-semibold"><i class="fas fa-check-circle me-1"></i>Pre-selected Diagnosis</h6>
                                <p class="mb-1"><strong>Patient:</strong> {{ $selectedDiagnosis->patient->name }}</p>
                                <p class="mb-1"><strong>Diagnosis:</strong> {{ $selectedDiagnosis->diagnosis_name }}</p>
                                <p class="mb-0"><strong>Date:</strong> {{ $selectedDiagnosis->created_at->format('M j, Y') }}</p>
                            </div>
                            <input type="hidden" name="diagnosis_id" value="{{ $selectedDiagnosis->id }}">
                            <input type="hidden" id="diagnosis_id" value="{{ $selectedDiagnosis->id }}">
                        @else
                            <div class="row">
                                <div class="col-md-6">
                                    <label for="diagnosis_id" class="form-label fw-medium">Select Diagnosis</label>
                                    <select class="form-select" id="diagnosis_id" name="diagnosis_id" required>
                                        <option value="">Choose a diagnosis...</option>
                                        @foreach($diagnoses as $diagnosis)
                                            <option value="{{ $diagnosis->id }}" data-patient="{{ $diagnosis->patient->name }}">
                                                {{ $diagnosis->patient->name }} - {{ $diagnosis->diagnosis_name }}
                                                ({{ $diagnosis->created_at->format('M j, Y') }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-medium">Patient Information</label>
                                    <div id="patient-info" class="p-3 border rounded" style="background: var(--bg-secondary, #f8f9fa);">
                                        <p class="text-muted mb-0">Select a diagnosis to view patient details</p>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                    <div class="card-footer bg-white border-top d-flex justify-content-end">
                        <button type="button" class="btn btn-primary next-step" data-next="2">Next: Choose Method <i class="fas fa-arrow-right ms-2"></i></button>
                    </div>
                </div>
            </div>

            <!-- Step 2: Method Selection -->
            <div class="step-content" id="step2-content">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-bottom">
                        <h5 class="mb-0 fw-semibold"><i class="fas fa-magic me-2 text-primary"></i>Step 2: Choose Creation Method</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="method-card" data-method="ai">
                                    <div class="method-icon">
                                        <i class="fas fa-robot"></i>
                                    </div>
                                    <h6 class="fw-semibold">AI-Generated Program</h6>
                                    <p class="text-muted small">Let our AI analyze the diagnosis and clinical notes to create a personalized HEP program automatically.</p>
                                    <ul class="list-unstyled small text-start d-inline-block">
                                        <li><i class="fas fa-check text-success me-1"></i>Evidence-based exercises</li>
                                        <li><i class="fas fa-check text-success me-1"></i>Progression planning</li>
                                        <li><i class="fas fa-check text-success me-1"></i>Patient-specific modifications</li>
                                    </ul>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="method-card" data-method="manual">
                                    <div class="method-icon" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                                        <i class="fas fa-hand-pointer"></i>
                                    </div>
                                    <h6 class="fw-semibold">Manual Creation</h6>
                                    <p class="text-muted small">Build your HEP program manually by selecting exercises and customizing parameters.</p>
                                    <ul class="list-unstyled small text-start d-inline-block">
                                        <li><i class="fas fa-check text-success me-1"></i>Full control over exercises</li>
                                        <li><i class="fas fa-check text-success me-1"></i>Custom progression</li>
                                        <li><i class="fas fa-check text-success me-1"></i>Advanced customization</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <input type="hidden" id="creation_method" name="creation_method" value="">
                    </div>
                    <div class="card-footer bg-white border-top d-flex justify-content-between">
                        <button type="button" class="btn btn-outline-secondary prev-step" data-prev="1"><i class="fas fa-arrow-left me-1"></i> Back</button>
                        <button type="button" class="btn btn-primary next-step" data-next="3" id="method-next-btn" disabled>Next: Design Program <i class="fas fa-arrow-right ms-1"></i></button>
                    </div>
                </div>
            </div>

            <!-- Step 3: Program Design -->
            <div class="step-content" id="step3-content">
                <!-- AI Generation Form -->
                <div class="card border-0 shadow-sm ai-generation-card" style="display: none;">
                    <div class="card-header bg-white border-bottom">
                        <h5 class="mb-0 fw-semibold"><i class="fas fa-robot me-2 text-primary"></i>AI Program Generation</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="additional_context" class="form-label">Additional Context (Optional)</label>
                            <textarea class="form-control" id="additional_context" name="additional_context" rows="4"
                                      placeholder="Any additional clinical notes, patient preferences, or specific requirements..."></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="program_duration" class="form-label">Program Duration</label>
                            <select class="form-select" id="program_duration" name="program_duration">
                                <option value="4">4 weeks</option>
                                <option value="6" selected>6 weeks</option>
                                <option value="8">8 weeks</option>
                                <option value="12">12 weeks</option>
                            </select>
                        </div>
                        <div class="text-center">
                            <button type="button" class="btn btn-success btn-lg" id="generate-ai-btn">
                                <i class="fas fa-magic me-2"></i>Generate AI Program
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Manual Creation Form -->
                <div class="card border-0 shadow-sm manual-creation-card" style="display: none;">
                    <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 fw-semibold"><i class="fas fa-edit me-2 text-primary"></i>Manual Program Design</h5>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="add-exercise-btn">
                            <i class="fas fa-plus me-1"></i>Add Exercise
                        </button>
                    </div>
                    <div class="card-body">
                        <!-- Program Details -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label for="program_title" class="form-label">Program Title</label>
                                <input type="text" class="form-control" id="program_title" name="title">
                            </div>
                            <div class="col-md-3">
                                <label for="program_duration_manual" class="form-label">Duration (Weeks)</label>
                                <input type="number" class="form-control" id="program_duration_manual" name="duration_weeks" value="6" min="1" max="52" required>
                            </div>
                            <div class="col-md-3">
                                <label for="program_status" class="form-label">Status</label>
                                <select class="form-select" id="program_status" name="status">
                                    <option value="draft">Draft</option>
                                    <option value="active">Active</option>
                                </select>
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label for="program_description" class="form-label">Description</label>
                                <textarea class="form-control" id="program_description" name="description" rows="3"></textarea>
                            </div>
                            <div class="col-md-6">
                                <label for="program_goals" class="form-label">Goals & Objectives</label>
                                <textarea class="form-control" id="program_goals" name="goals" rows="3"></textarea>
                            </div>
                        </div>

                        <!-- Exercise Builder -->
                        <div id="exercise-builder">
                            <h6 class="fw-semibold mb-3"><i class="fas fa-dumbbell me-2 text-primary"></i>Exercises</h6>
                            <div id="exercises-container">
                                <!-- Exercises will be added here dynamically -->
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-white border-top d-flex justify-content-between">
                        <button type="button" class="btn btn-outline-secondary prev-step" data-prev="2"><i class="fas fa-arrow-left me-1"></i> Back</button>
                        <button type="button" class="btn btn-primary next-step" data-next="4">Review & Save <i class="fas fa-arrow-right ms-1"></i></button>
                    </div>
                </div>
            </div>

            <!-- Step 4: Review & Save -->
            <div class="step-content" id="step4-content">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-bottom">
                        <h5 class="mb-0 fw-semibold"><i class="fas fa-check-circle me-2 text-success"></i>Step 4: Review & Save Program</h5>
                    </div>
                    <div class="card-body">
                        <div id="program-preview">
                            <div class="text-center text-muted py-4">
                                <i class="fas fa-spinner fa-spin fa-2x mb-3 d-block"></i>
                                <p>Loading program preview...</p>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-white border-top d-flex justify-content-between">
                        <button type="button" class="btn btn-outline-secondary prev-step" data-prev="3"><i class="fas fa-arrow-left me-1"></i> Back</button>
                        <button type="submit" class="btn btn-success btn-lg" id="save-hep-btn">
                            <i class="fas fa-save me-2"></i>Save HEP Program
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Exercise Selection Modal -->
<div class="modal fade" id="exerciseModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <h5 class="modal-title fw-semibold">Select Exercise</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="text-center py-4">
                    <i class="fas fa-spinner fa-spin fa-2x mb-3 d-block text-muted"></i>
                    <p>Loading exercises...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="add-selected-exercise-btn" disabled>Add Exercise</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    let currentStep = 1;
    let selectedMethod = null;
    let exercises = [];

    // Step navigation
    document.querySelectorAll('.next-step').forEach(btn => {
        btn.addEventListener('click', function() {
            const nextStep = parseInt(this.dataset.next);
            if (validateStep(currentStep)) {
                goToStep(nextStep);
            }
        });
    });

    document.querySelectorAll('.prev-step').forEach(btn => {
        btn.addEventListener('click', function() {
            const prevStep = parseInt(this.dataset.prev);
            goToStep(prevStep);
        });
    });

    // Method selection
    document.querySelectorAll('.method-card').forEach(card => {
        card.addEventListener('click', function() {
            document.querySelectorAll('.method-card').forEach(c => c.classList.remove('selected'));
            this.classList.add('selected');
            selectedMethod = this.dataset.method;
            document.getElementById('creation_method').value = selectedMethod;
            document.getElementById('method-next-btn').disabled = false;

            document.querySelector('.ai-generation-card').style.display = selectedMethod === 'ai' ? 'block' : 'none';
            document.querySelector('.manual-creation-card').style.display = selectedMethod === 'manual' ? 'block' : 'none';

            const titleInput = document.getElementById('program_title');
            if (selectedMethod === 'manual') {
                titleInput.setAttribute('required', 'required');
            } else {
                titleInput.removeAttribute('required');
            }
        });
    });

    // Diagnosis selection
    const diagnosisSelect = document.getElementById('diagnosis_id');
    if (diagnosisSelect) {
        diagnosisSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const patientName = selectedOption.dataset.patient;

            const patientInfo = document.getElementById('patient-info');
            if (patientName) {
                patientInfo.innerHTML = `
                    <strong>Patient:</strong> ${patientName}<br>
                    <strong>Diagnosis:</strong> ${selectedOption.text.split(' - ')[1]}
                `;
            } else {
                patientInfo.innerHTML = '<p class="text-muted mb-0">Select a diagnosis to view patient details</p>';
            }
        });
    }

    // AI Generation
    const generateAiBtn = document.getElementById('generate-ai-btn');
    if (generateAiBtn) {
        generateAiBtn.addEventListener('click', function() {
            const diagnosisIdEl = document.getElementById('diagnosis_id');
            const diagnosisId = diagnosisIdEl ? diagnosisIdEl.value : '';
            const additionalContext = document.getElementById('additional_context').value;
            const duration = document.getElementById('program_duration').value;

            if (!diagnosisId) {
                alert('Please select a diagnosis first');
                return;
            }

            this.disabled = true;
            this.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Generating...';

            fetch('{{ route("doctor.hep.generate-ai") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    diagnosis_id: diagnosisId,
                    additional_context: additionalContext,
                    duration_weeks: duration
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    populateFormWithAIData(data.program);
                    goToStep(4);
                } else {
                    alert('Error: ' + (data.message || 'Failed to generate program'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while generating the program');
            })
            .finally(() => {
                this.disabled = false;
                this.innerHTML = '<i class="fas fa-magic me-2"></i>Generate AI Program';
            });
        });
    }

    function goToStep(step) {
        document.querySelectorAll('.step-content').forEach(content => {
            content.classList.remove('active');
        });
        document.querySelectorAll('.step').forEach(stepEl => {
            stepEl.classList.remove('active');
        });

        document.getElementById(`step${step}-content`).classList.add('active');
        document.getElementById(`step${step}`).classList.add('active');

        currentStep = step;
    }

    function validateStep(step) {
        switch(step) {
            case 1:
                const diagEl = document.getElementById('diagnosis_id');
                return diagEl && diagEl.value !== '';
            case 2:
                return selectedMethod !== null;
            case 3:
                if (selectedMethod === 'ai') return true;
                else if (selectedMethod === 'manual') {
                    const titleValid = document.getElementById('program_title').value.trim() !== '';
                    const exercisesValid = exercises.length > 0;
                    return titleValid && exercisesValid;
                } else return false;
            default:
                return true;
        }
    }

    function populateFormWithAIData(program) {
        document.getElementById('generated_program_id').value = program.id;
        document.getElementById('program_title').value = program.title || '';
        document.getElementById('program_description').value = program.description || '';
        document.getElementById('program_goals').value = program.goals ? program.goals.join('\n') : '';
        document.getElementById('program_duration_manual').value = program.duration_weeks || 6;

        document.getElementById('exercises-container').innerHTML = '';
        exercises = [];

        if (program.hep_exercises && program.hep_exercises.length > 0) {
            program.hep_exercises.forEach((exercise, index) => {
                exercises.push(exercise);
                const exerciseHtml = `
                    <div class="exercise-item mb-3" data-exercise-id="${exercise.exercise_id}">
                        <div class="exercise-header d-flex justify-content-between align-items-center">
                            <h6 class="mb-0">${exercise.exercise ? exercise.exercise.name : 'Unknown Exercise'}</h6>
                            <span class="badge bg-info">AI Generated</span>
                        </div>
                        <div class="row g-2 mt-2">
                            <div class="col-md-2">
                                <label class="form-label small">Week</label>
                                <input type="number" name="exercises[${index}][week_number]" class="form-control form-control-sm" min="1" value="${exercise.week_number || 1}" required>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small">Sets</label>
                                <input type="number" name="exercises[${index}][sets]" class="form-control form-control-sm" min="1" value="${exercise.sets || 3}">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small">Reps</label>
                                <input type="number" name="exercises[${index}][reps]" class="form-control form-control-sm" min="1" value="${exercise.reps || 10}">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small">Duration (sec)</label>
                                <input type="number" name="exercises[${index}][duration_seconds]" class="form-control form-control-sm" min="1" value="${exercise.duration_seconds || 30}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small">Frequency</label>
                                <input type="text" name="exercises[${index}][frequency]" class="form-control form-control-sm" value="${exercise.frequency || 'Daily'}">
                            </div>
                        </div>
                        <div class="mt-2">
                            <label class="form-label small">Notes</label>
                            <textarea name="exercises[${index}][notes]" class="form-control form-control-sm" rows="2" placeholder="Exercise instructions or notes...">${exercise.notes || ''}</textarea>
                        </div>
                        <input type="hidden" name="exercises[${index}][exercise_id]" value="${exercise.exercise_id}">
                        <input type="hidden" name="exercises[${index}][order]" value="${index}">
                    </div>
                `;
                document.getElementById('exercises-container').insertAdjacentHTML('beforeend', exerciseHtml);
            });
        }

        const preview = document.getElementById('program-preview');
        preview.innerHTML = `
            <h6 class="fw-semibold">AI-Generated Program Preview</h6>
            <div class="alert alert-success border-0 shadow-sm">
                <strong>Program Generated Successfully!</strong><br>
                Title: ${program.title}<br>
                Duration: ${program.duration_weeks} weeks<br>
                Exercises: ${program.hep_exercises ? program.hep_exercises.length : 0}
            </div>
            <div class="alert alert-info border-0">
                <i class="fas fa-info-circle me-2"></i>
                The form has been populated with the AI-generated program. You can review and modify the details before saving.
            </div>
        `;
    }
    
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('next-step') && e.target.dataset.next === '4') {
            updateProgramPreview();
        }
    });
    
    function updateProgramPreview() {
        const preview = document.getElementById('program-preview');
        const title = document.getElementById('program_title').value;
        const duration = document.getElementById('program_duration_manual').value;
        const description = document.getElementById('program_description').value;
        const goals = document.getElementById('program_goals').value;
        const status = document.getElementById('program_status').value;
        
        let exercisesHtml = '';
        const exerciseItems = document.querySelectorAll('.exercise-item');
        
        if (exerciseItems.length === 0) {
            exercisesHtml = '<p class="text-muted">No exercises added to this program.</p>';
        } else {
            exercisesHtml = '<div class="table-responsive"><table class="table table-sm doctor-table"><thead><tr><th>Exercise</th><th>Week</th><th>Sets</th><th>Reps</th><th>Duration</th><th>Frequency</th></tr></thead><tbody>';
            
            exerciseItems.forEach(item => {
                const exerciseName = item.querySelector('h6').textContent;
                const week = item.querySelector('input[name*="week_number"]').value;
                const sets = item.querySelector('input[name*="sets"]').value;
                const reps = item.querySelector('input[name*="reps"]').value;
                const dur = item.querySelector('input[name*="duration_seconds"]').value;
                const frequency = item.querySelector('input[name*="frequency"]').value;
                
                exercisesHtml += `<tr><td>${exerciseName}</td><td>${week}</td><td>${sets}</td><td>${reps}</td><td>${dur}s</td><td>${frequency}</td></tr>`;
            });
            
            exercisesHtml += '</tbody></table></div>';
        }
        
        preview.innerHTML = `
            <div class="row mb-4">
                <div class="col-md-6">
                    <h6 class="fw-semibold">Program Details</h6>
                    <p><strong>Title:</strong> ${title || 'Untitled Program'}</p>
                    <p><strong>Duration:</strong> ${duration} weeks</p>
                    <p><strong>Status:</strong> ${status}</p>
                </div>
                <div class="col-md-6">
                    <h6 class="fw-semibold">Description</h6>
                    <p>${description || 'No description provided'}</p>
                </div>
            </div>
            
            ${goals ? `<div class="mb-4"><h6 class="fw-semibold">Goals & Objectives</h6><p>${goals.replace(/\n/g, '<br>')}</p></div>` : ''}
            
            <div class="mb-4">
                <h6 class="fw-semibold">Exercises (${exerciseItems.length})</h6>
                ${exercisesHtml}
            </div>
            
            <div class="alert alert-info border-0">
                <i class="fas fa-info-circle me-2"></i>
                Please review the program details above. Click "Save HEP Program" to create this program.
            </div>
        `;
    }

    // Add exercise functionality
    const addExerciseBtn = document.getElementById('add-exercise-btn');
    if (addExerciseBtn) {
        addExerciseBtn.addEventListener('click', function() {
            const exerciseModal = new bootstrap.Modal(document.getElementById('exerciseModal'));
            window.selectedExercise = null;
            const addBtn = document.getElementById('add-selected-exercise-btn');
            if (addBtn) addBtn.disabled = true;
            loadExercises();
            exerciseModal.show();
        });
    }
    
    const exerciseModalEl = document.getElementById('exerciseModal');
    if (exerciseModalEl) {
        exerciseModalEl.addEventListener('hidden.bs.modal', function () {
            window.selectedExercise = null;
            const addBtn = document.getElementById('add-selected-exercise-btn');
            if (addBtn) addBtn.disabled = true;
        });
    }
    
    const addSelectedBtn = document.getElementById('add-selected-exercise-btn');
    if (addSelectedBtn) {
        addSelectedBtn.addEventListener('click', function() {
            if (window.selectedExercise) {
                addExerciseToProgram(window.selectedExercise.id, window.selectedExercise.name);
                bootstrap.Modal.getInstance(document.getElementById('exerciseModal')).hide();
            }
        });
    }
    
    function addExerciseToProgram(exerciseId, exerciseName) {
        const exercisesContainer = document.getElementById('exercises-container');
        const exerciseIndex = exercisesContainer.children.length;
        
        const exerciseHtml = `
            <div class="exercise-item mb-3" data-exercise-id="${exerciseId}">
                <div class="exercise-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">${exerciseName}</h6>
                    <button type="button" class="btn btn-sm btn-outline-danger remove-exercise-btn">
                        <i class="fas fa-trash"></i> Remove
                    </button>
                </div>
                <div class="row g-2 mt-2">
                    <div class="col-md-2">
                        <label class="form-label small">Week</label>
                        <input type="number" name="exercises[${exerciseIndex}][week_number]" class="form-control form-control-sm" min="1" value="1" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">Sets</label>
                        <input type="number" name="exercises[${exerciseIndex}][sets]" class="form-control form-control-sm" min="1" value="3">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">Reps</label>
                        <input type="number" name="exercises[${exerciseIndex}][reps]" class="form-control form-control-sm" min="1" value="10">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">Duration (sec)</label>
                        <input type="number" name="exercises[${exerciseIndex}][duration_seconds]" class="form-control form-control-sm" min="1" value="30">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small">Frequency</label>
                        <input type="text" name="exercises[${exerciseIndex}][frequency]" class="form-control form-control-sm" value="Daily">
                    </div>
                </div>
                <div class="mt-2">
                    <label class="form-label small">Notes</label>
                    <textarea name="exercises[${exerciseIndex}][notes]" class="form-control form-control-sm" rows="2" placeholder="Exercise instructions or notes..."></textarea>
                </div>
                <input type="hidden" name="exercises[${exerciseIndex}][exercise_id]" value="${exerciseId}">
                <input type="hidden" name="exercises[${exerciseIndex}][order]" value="${exerciseIndex}">
            </div>
        `;
        
        exercisesContainer.insertAdjacentHTML('beforeend', exerciseHtml);
        
        exercises.push({
            exercise_id: exerciseId,
            name: exerciseName,
            week_number: 1,
            sets: 3,
            reps: 10,
            duration_seconds: 30,
            frequency: 'Daily',
            notes: ''
        });
    }
    
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-exercise-btn') || e.target.closest('.remove-exercise-btn')) {
            const exerciseItem = e.target.closest('.exercise-item');
            if (confirm('Are you sure you want to remove this exercise from the program?')) {
                const exerciseId = exerciseItem.dataset.exerciseId;
                exerciseItem.remove();
                exercises = exercises.filter(ex => ex.exercise_id != exerciseId);
                updateExerciseIndices();
            }
        }
    });
    
    function updateExerciseIndices() {
        const exerciseItems = document.querySelectorAll('.exercise-item');
        exerciseItems.forEach((item, index) => {
            const inputs = item.querySelectorAll('input, textarea');
            inputs.forEach(input => {
                const name = input.getAttribute('name');
                if (name && name.includes('exercises[')) {
                    const newName = name.replace(/exercises\[\d+\]/, `exercises[${index}]`);
                    input.setAttribute('name', newName);
                }
            });
        });
    }

    function loadExercises() {
        const modalBody = document.querySelector('#exerciseModal .modal-body');
        modalBody.innerHTML = '<div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x mb-3 d-block text-muted"></i><p>Loading exercises...</p></div>';

        fetch('/api/hep/exercises')
            .then(response => response.json())
            .then(data => {
                let html = '<div class="row">';
                
                html += `
                    <div class="col-12 mb-3">
                        <div class="row g-2">
                            <div class="col-md-4">
                                <select class="form-select form-select-sm" id="category-filter">
                                    <option value="">All Categories</option>
                                    @foreach($exerciseCategories as $category)
                                        <option value="{{ $category }}">{{ $category }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-8">
                                <input type="text" class="form-control form-control-sm" id="exercise-search" placeholder="Search exercises...">
                            </div>
                        </div>
                    </div>
                `;
                
                data.data.forEach(exercise => {
                    html += `
                        <div class="col-md-6 col-lg-4 mb-3 exercise-select-card" data-exercise-id="${exercise.id}" data-exercise-name="${exercise.name}">
                            <div class="card h-100 border shadow-sm">
                                <div class="card-body">
                                    <h6 class="card-title" style="font-size:0.875rem;">${exercise.name}</h6>
                                    <p class="card-text text-muted small">${exercise.description || 'No description available'}</p>
                                    <span class="badge bg-primary">${exercise.category || 'General'}</span>
                                </div>
                            </div>
                        </div>
                    `;
                });
                
                html += '</div>';
                modalBody.innerHTML = html;
                
                document.querySelectorAll('.exercise-select-card').forEach(card => {
                    card.addEventListener('click', function() {
                        document.querySelectorAll('.exercise-select-card').forEach(c => c.classList.remove('selected'));
                        this.classList.add('selected');
                        window.selectedExercise = {
                            id: this.dataset.exerciseId,
                            name: this.dataset.exerciseName
                        };
                        document.getElementById('add-selected-exercise-btn').disabled = false;
                    });
                });
                
                document.getElementById('category-filter')?.addEventListener('change', filterExercises);
                document.getElementById('exercise-search')?.addEventListener('input', filterExercises);
            })
            .catch(error => {
                console.error('Error loading exercises:', error);
                modalBody.innerHTML = '<div class="alert alert-danger border-0">Failed to load exercises. Please try again.</div>';
            });
    }
    
    function filterExercises() {
        const category = document.getElementById('category-filter')?.value || '';
        const searchTerm = document.getElementById('exercise-search')?.value.toLowerCase() || '';
        
        document.querySelectorAll('.exercise-select-card').forEach(card => {
            const cardText = card.textContent.toLowerCase();
            const matchesCategory = !category || card.textContent.includes(category);
            const matchesSearch = !searchTerm || cardText.includes(searchTerm);
            
            card.style.display = matchesCategory && matchesSearch ? '' : 'none';
        });
    }

    // Form submission handling
    const hepForm = document.getElementById('hepForm');
    if (hepForm) {
        hepForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            if (!document.getElementById('creation_method').value) {
                document.getElementById('creation_method').value = selectedMethod;
            }
            
            const formData = new FormData(this);
            
            const submitBtn = document.getElementById('save-hep-btn');
            const originalText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Saving...';
            
            fetch(this.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                },
            })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(data => {
                        throw new Error(data.message || 'Failed to save HEP program');
                    });
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    window.location.href = data.redirect_url || '/doctor/hep';
                } else {
                    throw new Error(data.message || 'Failed to save HEP program');
                }
            })
            .catch(error => {
                console.error('Error saving HEP program:', error);
                alert(error.message || 'Failed to save HEP program. Please try again.');
            })
            .finally(() => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            });
            
            if(selectedMethod === 'manual'){
                const title = document.getElementById('program_title').value.trim();
                if (exercises.length === 0) {
                    e.preventDefault();
                    alert('Please add at least one exercise to the program.');
                    return false;
                }
                if (title === '') {
                    e.preventDefault();
                    alert('Please provide a title for the program.');
                    goToStep(3);
                    return false;
                }
            }
        });
    }
});
</script>
@endpush
