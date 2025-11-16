@extends('master')

@section('title', 'Create HEP Program')

@section('content')
<div class="dashboard-container">
    <div class="container">
        <!-- Header -->
        <div class="dashboard-header py-2 border-bottom">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2>Create Home Exercise Program</h2>
                    <p class="mb-0">Design a personalized exercise program for your patient</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('doctor.hep.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Programs
                    </a>
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

        <form id="hepForm" method="POST" action="{{ route('doctor.hep.store') }}">
            @csrf

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
                                <input type="text" class="form-control" id="program_title" name="title" required>
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
                        <button type="submit" class="btn btn-success btn-lg">
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
                return document.getElementById('diagnosis_id').value !== '';
            case 2:
                return selectedMethod !== null;
            case 3:
                return selectedMethod === 'ai' || exercises.length > 0;
            default:
                return true;
        }
    }

    function populateFormWithAIData(program) {
        // This would populate the form with AI-generated program data
        // For now, we'll just show a preview
        const preview = document.getElementById('program-preview');
        preview.innerHTML = `
            <h6>AI-Generated Program Preview</h6>
            <div class="alert alert-success">
                <strong>Program Generated Successfully!</strong><br>
                Title: ${program.title}<br>
                Duration: ${program.duration_weeks} weeks<br>
                Exercises: ${program.hep_exercises_count || 'Multiple'}
            </div>
        `;
    }

    // Add exercise functionality (simplified)
    document.getElementById('add-exercise-btn').addEventListener('click', function() {
        const exerciseModal = new bootstrap.Modal(document.getElementById('exerciseModal'));
        exerciseModal.show();
    });
});
</script>
@endpush
@endsection
