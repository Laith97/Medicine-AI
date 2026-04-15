@extends('master')

@section('title', 'Create Physical Therapy - HEP Program')

@section('content')
<style>
.app-main {
    background-color: #f8f9fa;
}
.dashboard-header {
    background: linear-gradient(135deg, #2c5aa0 0%, #1e3a8a 100%);
    border-radius: 12px;
    padding: 2.5rem;
    margin-bottom: 2rem;
}
</style>
<div class="container-fluid" style="background-color: #f8f9fa;">
    <div class="container">
        <div class="dashboard-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2><i class="fas fa-plus me-2"></i>Create Physical Therapy (Home Exercise Program)</h2>
                    <p class="text-muted mb-0">Design a personalized exercise program for your patient</p>
                </div>
                <div class="header-actions">
                    <a href="{{ route('doctor.hep.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Programs
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Progress Steps -->
        <div class="row mt-4">
            <div class="col-12">
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
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-stethoscope me-2"></i>Step 1: Select Diagnosis & Patient</h5>
                    </div>
                    <div class="card-body">
                        @if($selectedDiagnosis)
                            <div class="alert alert-info">
                                <h6>Pre-selected Diagnosis:</h6>
                                <p class="mb-1"><strong>Patient:</strong> {{ $selectedDiagnosis->patient->name }}</p>
                                <p class="mb-1"><strong>Diagnosis:</strong> {{ $selectedDiagnosis->diagnosis_name }}</p>
                                <p class="mb-0"><strong>Date:</strong> {{ $selectedDiagnosis->created_at->format('M j, Y') }}</p>
                            </div>
                            <input type="hidden" name="diagnosis_id" value="{{ $selectedDiagnosis->id }}">
                        @else
                            <div class="row">
                                <div class="col-md-6">
                                    <label for="diagnosis_id" class="form-label">Select Diagnosis</label>
                                    <select class="form-select" id="diagnosis_id" name="diagnosis_id" required>
                                        <option value="">Choose a diagnosis...</option>
                                        @foreach($diagnoses as $diagnosis)
                                            <option value="{{ $diagnosis->id }}" data-patient="{{ $diagnosis->patient->name }}">
                                                {{ $diagnosis->patient->name }} - {{ $diagnosis->diagnosis_name }}
                                                <small class="text-muted">({{ $diagnosis->created_at->format('M j, Y') }})</small>
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Patient Information</label>
                                    <div id="patient-info" class="p-3 border rounded bg-light">
                                        <p class="text-muted mb-0">Select a diagnosis to view patient details</p>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                    <div class="card-footer">
                        <button type="button" class="btn btn-primary next-step" data-next="2">Next: Choose Method</button>
                    </div>
                </div>
            </div>

            <!-- Step 2: Method Selection -->
            <div class="step-content" id="step2-content">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-magic me-2"></i>Step 2: Choose Creation Method</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="method-card" data-method="ai">
                                    <div class="method-icon">
                                        <i class="fas fa-robot"></i>
                                    </div>
                                    <h6>AI-Generated Program</h6>
                                    <p>Let our AI analyze the diagnosis and clinical notes to create a personalized HEP program automatically.</p>
                                    <ul class="list-unstyled small">
                                        <li><i class="fas fa-check text-success me-1"></i>Evidence-based exercises</li>
                                        <li><i class="fas fa-check text-success me-1"></i>Progression planning</li>
                                        <li><i class="fas fa-check text-success me-1"></i>Patient-specific modifications</li>
                                    </ul>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="method-card" data-method="manual">
                                    <div class="method-icon">
                                        <i class="fas fa-hand-pointer"></i>
                                    </div>
                                    <h6>Manual Creation</h6>
                                    <p>Build your HEP program manually by selecting exercises and customizing parameters.</p>
                                    <ul class="list-unstyled small">
                                        <li><i class="fas fa-check text-success me-1"></i>Full control over exercises</li>
                                        <li><i class="fas fa-check text-success me-1"></i>Custom progression</li>
                                        <li><i class="fas fa-check text-success me-1"></i>Advanced customization</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <input type="hidden" id="creation_method" name="creation_method" value="">
                    </div>
                    <div class="card-footer">
                        <button type="button" class="btn btn-outline-secondary prev-step" data-prev="1">Back</button>
                        <button type="button" class="btn btn-primary next-step" data-next="3" id="method-next-btn" disabled>Next: Design Program</button>
                    </div>
                </div>
            </div>

            <!-- Step 3: Program Design -->
            <div class="step-content" id="step3-content">
                <!-- AI Generation Form -->
                <div class="card ai-generation-card" style="display: none;">
                    <div class="card-header">
                        <h5><i class="fas fa-magic me-2"></i>AI Program Generation</h5>
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
                <div class="card manual-creation-card" style="display: none;">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5><i class="fas fa-edit me-2"></i>Manual Program Design</h5>
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
                            <h6>Exercises</h6>
                            <div id="exercises-container">
                                <!-- Exercises will be added here dynamically -->
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="button" class="btn btn-outline-secondary prev-step" data-prev="2">Back</button>
                        <button type="button" class="btn btn-primary next-step" data-next="4">Review & Save</button>
                    </div>
                </div>
            </div>

            <!-- Step 4: Review & Save -->
            <div class="step-content" id="step4-content">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-check-circle me-2"></i>Step 4: Review & Save Program</h5>
                    </div>
                    <div class="card-body">
                        <div id="program-preview">
                            <div class="text-center text-muted">
                                <i class="fas fa-spinner fa-spin fa-2x mb-3"></i>
                                <p>Loading program preview...</p>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="button" class="btn btn-outline-secondary prev-step" data-prev="3">Back</button>
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
<div class="modal fade" id="exerciseModal" tabindex="-1" size="xl">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Select Exercise</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <!-- Exercise selection interface will be loaded here -->
                <div class="text-center">
                    <i class="fas fa-spinner fa-spin fa-2x mb-3"></i>
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

@push('styles')
<style>
.progress-steps {
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 2rem;
    flex-wrap: wrap;
}

.step {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 1rem;
    border-radius: 8px;
    background: #f8f9fa;
    border: 2px solid #e9ecef;
    transition: all 0.3s ease;
}

.step.active {
    background: #007bff;
    color: white;
    border-color: #007bff;
}

.step-number {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #6c757d;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    margin-bottom: 0.5rem;
}

.step.active .step-number {
    background: white;
    color: #007bff;
}

.step-label {
    font-size: 0.9rem;
    font-weight: 500;
    text-align: center;
}

.step-arrow {
    margin: 0 1rem;
    color: #6c757d;
}

.step-content {
    display: none;
}

.step-content.active {
    display: block;
}

.method-card {
    border: 2px solid #e9ecef;
    border-radius: 12px;
    padding: 2rem;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s ease;
    background: white;
    height: 100%;
}

.method-card:hover {
    border-color: #007bff;
    box-shadow: 0 4px 12px rgba(0,123,255,0.15);
}

.method-card.selected {
    border-color: #007bff;
    background: #f8f9ff;
    box-shadow: 0 4px 12px rgba(0,123,255,0.15);
}

.method-icon {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    margin: 0 auto 1rem;
}

.exercise-item {
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 1rem;
    margin-bottom: 1rem;
    background: white;
}

.exercise-header {
    display: flex;
    justify-content: between;
    align-items: center;
    margin-bottom: 1rem;
}

.exercise-details {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
}

.exercise-select-card {
    cursor: pointer;
    transition: all 0.3s ease;
}

.exercise-select-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(0,0,0,0.1);
}

.exercise-select-card.selected {
    border: 2px solid #007bff;
    box-shadow: 0 0 0 0.2rem rgba(0,123,255,0.25);
}

.exercise-select-card .card {
    height: 100%;
}
</style>
@endpush

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

            // Show/hide relevant forms
            document.querySelector('.ai-generation-card').style.display = selectedMethod === 'ai' ? 'block' : 'none';
            document.querySelector('.manual-creation-card').style.display = selectedMethod === 'manual' ? 'block' : 'none';

            // Set required attribute on program_title based on method
            const titleInput = document.getElementById('program_title');
            if (selectedMethod === 'manual') {
                titleInput.setAttribute('required', 'required');
            } else {
                titleInput.removeAttribute('required');
            }
        });
    });

    // Diagnosis selection
    document.getElementById('diagnosis_id').addEventListener('change', function() {
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

    // AI Generation
    document.getElementById('generate-ai-btn').addEventListener('click', function() {
        const diagnosisId = document.getElementById('diagnosis_id').value;
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
                // Populate the form with AI-generated data
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

    function goToStep(step) {
        // Hide all steps
        document.querySelectorAll('.step-content').forEach(content => {
            content.classList.remove('active');
        });
        document.querySelectorAll('.step').forEach(stepEl => {
            stepEl.classList.remove('active');
        });

        // Show target step
        document.getElementById(`step${step}-content`).classList.add('active');
        document.getElementById(`step${step}`).classList.add('active');

        currentStep = step;
    }

    function validateStep(step) {
        switch(step) {
            case 1:
                const diagnosisValid = document.getElementById('diagnosis_id').value !== '';
                console.log('Step 1 validation - diagnosis_id:', diagnosisValid, 'value:', document.getElementById('diagnosis_id').value);
                return diagnosisValid;
            case 2:
                const methodValid = selectedMethod !== null;
                console.log('Step 2 validation - selectedMethod:', methodValid, selectedMethod);
                return methodValid;
            case 3:
                if (selectedMethod === 'ai') {
                    console.log('Step 3 validation - AI mode: valid');
                    return true;
                } else if (selectedMethod === 'manual') {
                    const titleValid = document.getElementById('program_title').value.trim() !== '';
                    const exercisesValid = exercises.length > 0;
                    const step3Valid = titleValid && exercisesValid;
                    console.log('Step 3 validation - Manual mode: titleValid:', titleValid, 'exercisesValid:', exercisesValid, 'valid:', step3Valid);
                    return step3Valid;
                } else {
                    console.log('Step 3 validation - No method selected: invalid');
                    return false;
                }
            default:
                return true;
        }
    }

    function populateFormWithAIData(program) {
        // Set the generated program ID
        document.getElementById('generated_program_id').value = program.id;

        // Populate form fields with AI-generated data
        document.getElementById('program_title').value = program.title || '';
        document.getElementById('program_description').value = program.description || '';
        document.getElementById('program_goals').value = program.goals ? program.goals.join('\n') : '';
        document.getElementById('program_duration_manual').value = program.duration_weeks || 6;

        // Clear existing exercises
        document.getElementById('exercises-container').innerHTML = '';

        // Populate exercises
        if (program.hep_exercises && program.hep_exercises.length > 0) {
            program.hep_exercises.forEach((exercise, index) => {
                const exerciseHtml = `
                    <div class="exercise-item mb-3" data-exercise-id="${exercise.exercise_id}">
                        <div class="exercise-header d-flex justify-content-between align-items-center">
                            <h6>${exercise.exercise ? exercise.exercise.name : 'Unknown Exercise'}</h6>
                            <span class="badge bg-info">AI Generated</span>
                        </div>
                        <div class="exercise-details">
                            <div class="mb-3">
                                <label class="form-label">Week</label>
                                <input type="number" name="exercises[${index}][week_number]" class="form-control" min="1" value="${exercise.week_number || 1}" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Sets</label>
                                <input type="number" name="exercises[${index}][sets]" class="form-control" min="1" value="${exercise.sets || 3}">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Reps</label>
                                <input type="number" name="exercises[${index}][reps]" class="form-control" min="1" value="${exercise.reps || 10}">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Duration (seconds)</label>
                                <input type="number" name="exercises[${index}][duration_seconds]" class="form-control" min="1" value="${exercise.duration_seconds || 30}">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Frequency</label>
                                <input type="text" name="exercises[${index}][frequency]" class="form-control" value="${exercise.frequency || 'Daily'}">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Notes</label>
                            <textarea name="exercises[${index}][notes]" class="form-control" rows="2" placeholder="Exercise instructions or notes...">${exercise.notes || ''}</textarea>
                        </div>
                        <input type="hidden" name="exercises[${index}][exercise_id]" value="${exercise.exercise_id}">
                        <input type="hidden" name="exercises[${index}][order]" value="${index}">
                    </div>
                `;
                document.getElementById('exercises-container').insertAdjacentHTML('beforeend', exerciseHtml);
            });
        }

        // Update program preview
        const preview = document.getElementById('program-preview');
        preview.innerHTML = `
            <h6>AI-Generated Program Preview</h6>
            <div class="alert alert-success">
                <strong>Program Generated Successfully!</strong><br>
                Title: ${program.title}<br>
                Duration: ${program.duration_weeks} weeks<br>
                Exercises: ${program.hep_exercises ? program.hep_exercises.length : 0}
            </div>
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                The form has been populated with the AI-generated program. You can review and modify the details before saving.
            </div>
        `;
    }
    
    // Update program preview when moving to step 4
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
            exercisesHtml = '<div class="table-responsive"><table class="table table-striped"><thead><tr><th>Exercise</th><th>Week</th><th>Sets</th><th>Reps</th><th>Duration</th><th>Frequency</th></tr></thead><tbody>';
            
            exerciseItems.forEach(item => {
                const exerciseName = item.querySelector('h6').textContent;
                const week = item.querySelector('input[name*="week_number"]').value;
                const sets = item.querySelector('input[name*="sets"]').value;
                const reps = item.querySelector('input[name*="reps"]').value;
                const duration = item.querySelector('input[name*="duration_seconds"]').value;
                const frequency = item.querySelector('input[name*="frequency"]').value;
                
                exercisesHtml += `<tr><td>${exerciseName}</td><td>${week}</td><td>${sets}</td><td>${reps}</td><td>${duration}s</td><td>${frequency}</td></tr>`;
            });
            
            exercisesHtml += '</tbody></table></div>';
        }
        
        preview.innerHTML = `
            <div class="row mb-4">
                <div class="col-md-6">
                    <h6>Program Details</h6>
                    <p><strong>Title:</strong> ${title || 'Untitled Program'}</p>
                    <p><strong>Duration:</strong> ${duration} weeks</p>
                    <p><strong>Status:</strong> ${status}</p>
                </div>
                <div class="col-md-6">
                    <h6>Description</h6>
                    <p>${description || 'No description provided'}</p>
                </div>
            </div>
            
            ${goals ? `<div class="mb-4"><h6>Goals & Objectives</h6><p>${goals.replace(/\n/g, '<br>')}</p></div>` : ''}
            
            <div class="mb-4">
                <h6>Exercises (${exerciseItems.length})</h6>
                ${exercisesHtml}
            </div>
            
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                Please review the program details above. Click "Save HEP Program" to create this program.
            </div>
        `;
    }

    // Add exercise functionality
    document.getElementById('add-exercise-btn').addEventListener('click', function() {
        const exerciseModal = new bootstrap.Modal(document.getElementById('exerciseModal'));
        
        // Reset modal state
        window.selectedExercise = null;
        document.getElementById('add-selected-exercise-btn').disabled = true;
        
        loadExercises();
        exerciseModal.show();
    });
    
    // Reset modal when hidden
    document.getElementById('exerciseModal').addEventListener('hidden.bs.modal', function () {
        window.selectedExercise = null;
        document.getElementById('add-selected-exercise-btn').disabled = true;
    });
    
    // Add selected exercise to program
    document.getElementById('add-selected-exercise-btn').addEventListener('click', function() {
        if (window.selectedExercise) {
            addExerciseToProgram(window.selectedExercise.id, window.selectedExercise.name);
            bootstrap.Modal.getInstance(document.getElementById('exerciseModal')).hide();
        }
    });
    
    function addExerciseToProgram(exerciseId, exerciseName) {
        const exercisesContainer = document.getElementById('exercises-container');
        const exerciseIndex = exercisesContainer.children.length;
        
        const exerciseHtml = `
            <div class="exercise-item mb-3" data-exercise-id="${exerciseId}">
                <div class="exercise-header d-flex justify-content-between align-items-center">
                    <h6>${exerciseName}</h6>
                    <button type="button" class="btn btn-sm btn-outline-danger remove-exercise-btn">
                        <i class="fas fa-trash"></i> Remove
                    </button>
                </div>
                <div class="exercise-details">
                    <div class="mb-3">
                        <label class="form-label">Week</label>
                        <input type="number" name="exercises[${exerciseIndex}][week_number]" class="form-control" min="1" value="1" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Sets</label>
                        <input type="number" name="exercises[${exerciseIndex}][sets]" class="form-control" min="1" value="3">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Reps</label>
                        <input type="number" name="exercises[${exerciseIndex}][reps]" class="form-control" min="1" value="10">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Duration (seconds)</label>
                        <input type="number" name="exercises[${exerciseIndex}][duration_seconds]" class="form-control" min="1" value="30">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Frequency</label>
                        <input type="text" name="exercises[${exerciseIndex}][frequency]" class="form-control" value="Daily">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Notes</label>
                    <textarea name="exercises[${exerciseIndex}][notes]" class="form-control" rows="2" placeholder="Exercise instructions or notes..."></textarea>
                </div>
                <input type="hidden" name="exercises[${exerciseIndex}][exercise_id]" value="${exerciseId}">
                <input type="hidden" name="exercises[${exerciseIndex}][order]" value="${exerciseIndex}">
            </div>
        `;
        
        exercisesContainer.insertAdjacentHTML('beforeend', exerciseHtml);
        
        // Update exercises array with actual form values
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
        
        // Log exercises array for debugging
        console.log('Exercises array after adding:', exercises);
    }
    
    // Remove exercise functionality
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-exercise-btn') || e.target.closest('.remove-exercise-btn')) {
            const exerciseItem = e.target.closest('.exercise-item');
            if (confirm('Are you sure you want to remove this exercise from the program?')) {
                const exerciseId = exerciseItem.dataset.exerciseId;
                exerciseItem.remove();
                
                // Update exercises array
                exercises = exercises.filter(ex => ex.exercise_id != exerciseId);
                
                // Log exercises array for debugging
                console.log('Exercises array after removal:', exercises);
                
                // Update exercise indices
                updateExerciseIndices();
            }
        }
    });
    
    function updateExerciseIndices() {
        const exerciseItems = document.querySelectorAll('.exercise-item');
        exerciseItems.forEach((item, index) => {
            // Update all input names to use the new index
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
        modalBody.innerHTML = '<div class="text-center"><i class="fas fa-spinner fa-spin fa-2x mb-3"></i><p>Loading exercises...</p></div>';

        // Fetch exercises from API
        fetch('/api/hep/exercises')
            .then(response => response.json())
            .then(data => {
                let html = '<div class="row">';
                
                // Add filter controls
                html += `
                    <div class="col-12 mb-3">
                        <div class="row">
                            <div class="col-md-4">
                                <select class="form-select" id="category-filter">
                                    <option value="">All Categories</option>
                                    @foreach($exerciseCategories as $category)
                                        <option value="{{ $category }}">{{ $category }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-8">
                                <input type="text" class="form-control" id="exercise-search" placeholder="Search exercises...">
                            </div>
                        </div>
                    </div>
                `;
                
                // Add exercise cards
                data.data.forEach(exercise => {
                    html += `
                        <div class="col-md-6 col-lg-4 mb-3 exercise-select-card" data-exercise-id="${exercise.id}" data-exercise-name="${exercise.name}">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h6 class="card-title">${exercise.name}</h6>
                                    <p class="card-text text-muted small">${exercise.description || 'No description available'}</p>
                                    <span class="badge bg-primary">${exercise.category || 'General'}</span>
                                </div>
                            </div>
                        </div>
                    `;
                });
                
                html += '</div>';
                modalBody.innerHTML = html;
                
                // Add click handlers to exercise cards
                document.querySelectorAll('.exercise-select-card').forEach(card => {
                    card.addEventListener('click', function() {
                        // Remove previous selections
                        document.querySelectorAll('.exercise-select-card').forEach(c => c.classList.remove('selected'));
                        
                        // Mark this card as selected
                        this.classList.add('selected');
                        
                        // Store selected exercise data
                        window.selectedExercise = {
                            id: this.dataset.exerciseId,
                            name: this.dataset.exerciseName
                        };
                        
                        // Enable the Add Exercise button
                        document.getElementById('add-selected-exercise-btn').disabled = false;
                    });
                });
                
                // Add filter functionality
                document.getElementById('category-filter')?.addEventListener('change', filterExercises);
                document.getElementById('exercise-search')?.addEventListener('input', filterExercises);
            })
            .catch(error => {
                console.error('Error loading exercises:', error);
                modalBody.innerHTML = '<div class="alert alert-danger">Failed to load exercises. Please try again.</div>';
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
    document.getElementById('hepForm').addEventListener('submit', function(e) {
        e.preventDefault(); // Prevent default form submission
        
        // Make sure we have the creation_method field set
        if (!document.getElementById('creation_method').value) {
            document.getElementById('creation_method').value = selectedMethod;
        }
        
        // Debug form data before submission
        const formData = new FormData(this);
        console.log('Form submission data:');
        for (let [key, value] of formData.entries()) {
            console.log(key, value);
        }
        
        // Submit form via AJAX
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
        
        // Log form data for debugging
        if(selectedMethod === 'manual'){
            const title = document.getElementById('program_title').value.trim();
            console.log('Manual title value:', title, 'is empty:', title === '');
            console.log('Exercises count:', exercises.length);
            
            // Check if we have exercises
            if (exercises.length === 0) {
                e.preventDefault();
                alert('Please add at least one exercise to the program.');
                return false;
            }
            
            // Check if title is provided
            if (title === '') {
                e.preventDefault();
                alert('Please provide a title for the program.');
                goToStep(3);
                return false;
            }
        }
        
        console.log('Form submission - selectedMethod:', selectedMethod, 'currentStep:', currentStep);
    });
    
    // Form submission logging for debugging
    document.getElementById('save-hep-btn').addEventListener('click', function() {
        console.log('Save button clicked - selectedMethod:', selectedMethod, 'currentStep:', currentStep);
        if(selectedMethod === 'manual'){
            const title = document.getElementById('program_title').value.trim();
            console.log('Manual title value:', title, 'is empty:', title === '');
            const step3Display = document.getElementById('step3-content').style.display;
            console.log('Step3-content display:', step3Display);
        }
    });
});
</script>
@endpush
@endsection
