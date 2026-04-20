@extends('layouts.admin')

@section('title', 'Create Data Migration')

@push('styles')
<style>
    .wizard-container {
        background: white;
        border-radius: 16px;
        padding: 2rem;
        box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    }
    .wizard-step {
        padding: 2rem;
        border: 2px solid #e9ecef;
        border-radius: 12px;
        margin-bottom: 1.5rem;
        transition: all 0.3s;
    }
    .wizard-step.active {
        border-color: #DE6262;
        background: #fff5f5;
    }
    .step-number {
        width: 40px;
        height: 40px;
        background: linear-gradient(135deg, #DE6262, #E87A7A);
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        margin-bottom: 1rem;
    }
    .step-title {
        font-size: 1.25rem;
        font-weight: 600;
        color: #2c3e50;
        margin-bottom: 0.5rem;
    }
    .step-description {
        color: #6c757d;
        font-size: 0.9rem;
    }
    .entity-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 1rem;
    }
    .entity-card {
        border: 2px solid #e9ecef;
        border-radius: 12px;
        padding: 1.25rem;
        cursor: pointer;
        transition: all 0.2s;
        text-align: center;
    }
    .entity-card:hover {
        border-color: #DE6262;
        transform: translateY(-2px);
    }
    .entity-card.selected {
        border-color: #DE6262;
        background: linear-gradient(135deg, rgba(222,98,98,0.1), rgba(232,122,122,0.1));
    }
    .entity-card i {
        font-size: 2rem;
        color: #DE6262;
        margin-bottom: 0.5rem;
    }
    .entity-card h5 {
        font-weight: 600;
        color: #2c3e50;
        margin-bottom: 0.25rem;
    }
    .entity-card p {
        font-size: 0.8rem;
        color: #6c757d;
        margin: 0;
    }
    .file-upload-zone {
        border: 2px dashed #dee2e6;
        border-radius: 12px;
        padding: 3rem;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s;
        background: #fafafa;
    }
    .file-upload-zone:hover {
        border-color: #DE6262;
        background: #fff5f5;
    }
    .file-upload-zone.dragover {
        border-color: #DE6262;
        background: #fff5f5;
    }
    .file-upload-zone i {
        font-size: 3rem;
        color: #DE6262;
        margin-bottom: 1rem;
    }
    .template-preview {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 1.5rem;
        margin-top: 1rem;
    }
    .template-preview h6 {
        font-weight: 600;
        color: #2c3e50;
        margin-bottom: 1rem;
    }
    .template-preview code {
        display: block;
        white-space: pre;
        font-size: 0.8rem;
        background: white;
        padding: 1rem;
        border-radius: 4px;
        border: 1px solid #dee2e6;
    }
    .instructions-box {
        background: linear-gradient(135deg, #e7f1ff 0%, #f0f7ff 100%);
        border-left: 4px solid #2196f3;
        border-radius: 8px;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
    }
    .instructions-box h6 {
        font-weight: 600;
        color: #1565c0;
        margin-bottom: 0.75rem;
    }
    .instructions-box ul {
        margin: 0;
        padding-left: 1.25rem;
    }
    .instructions-box li {
        margin-bottom: 0.5rem;
        color: #1565c0;
        font-size: 0.9rem;
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="page-title">
                <i class="fas fa-magic me-2"></i>Create New Migration
            </h2>
            <p class="text-muted">Import data from external systems into MedCura</p>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.data-migration.store') }}" enctype="multipart/form-data">
        @csrf

        {{-- Step 1: Basic Info --}}
        <div class="wizard-step active" id="step1">
            <div class="d-flex align-items-start mb-4">
                <div class="step-number">1</div>
                <div class="ms-3">
                    <div class="step-title">Basic Information</div>
                    <div class="step-description">Give your migration a name and select the data type</div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Migration Name *</label>
                    <input type="text" name="name" class="form-control" placeholder="e.g., Patient Import from Old System" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Description (optional)</label>
                    <input type="text" name="description" class="form-control" placeholder="Brief description of this migration">
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label">Data Type to Import *</label>
                <div class="entity-grid">
                    <div class="entity-card" onclick="selectEntity('department', this)">
                        <i class="fas fa-building"></i>
                        <h5>Department</h5>
                        <p>Office/facility structure</p>
                    </div>
                    <div class="entity-card" onclick="selectEntity('specialty', this)">
                        <i class="fas fa-stethoscope"></i>
                        <h5>Specialty</h5>
                        <p>Medical specialties</p>
                    </div>
                    <div class="entity-card" onclick="selectEntity('doctor', this)">
                        <i class="fas fa-user-md"></i>
                        <h5>Doctor</h5>
                        <p>Physicians & specialists</p>
                    </div>
                    <div class="entity-card" onclick="selectEntity('patient', this)">
                        <i class="fas fa-user"></i>
                        <h5>Patient</h5>
                        <p>Patient records</p>
                    </div>
                    <div class="entity-card" onclick="selectEntity('appointment', this)">
                        <i class="fas fa-calendar-check"></i>
                        <h5>Appointment</h5>
                        <p>Scheduled appointments</p>
                    </div>
                    <div class="entity-card" onclick="selectEntity('diagnosis', this)">
                        <i class="fas fa-notes-medical"></i>
                        <h5>Diagnosis</h5>
                        <p>ICD-10 diagnoses</p>
                    </div>
                    <div class="entity-card" onclick="selectEntity('prescription', this)">
                        <i class="fas fa-pills"></i>
                        <h5>Prescription</h5>
                        <p>Medication prescriptions</p>
                    </div>
                    <div class="entity-card" onclick="selectEntity('treatment', this)">
                        <i class="fas fa-heartbeat"></i>
                        <h5>Treatment</h5>
                        <p>Treatment plans</p>
                    </div>
                    <div class="entity-card" onclick="selectEntity('allergy', this)">
                        <i class="fas fa-exclamation-triangle"></i>
                        <h5>Allergy</h5>
                        <p>Patient allergies</p>
                    </div>
                    <div class="entity-card" onclick="selectEntity('insurance', this)">
                        <i class="fas fa-shield-alt"></i>
                        <h5>Insurance</h5>
                        <p>Insurance policies</p>
                    </div>
                </div>
                <input type="hidden" name="entity_type" id="selected_entity_type" required>
            </div>
        </div>

        {{-- Step 2: Upload File --}}
        <div class="wizard-step" id="step2">
            <div class="d-flex align-items-start mb-4">
                <div class="step-number">2</div>
                <div class="ms-3">
                    <div class="step-title">Upload Data File</div>
                    <div class="step-description">Select CSV or Excel file containing your data</div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Source Type *</label>
                    <select name="source_type" class="form-select" required>
                        <option value="csv">CSV File (.csv)</option>
                        <option value="excel">Excel File (.xlsx, .xls)</option>
                        <option value="api">API Connection (Coming Soon)</option>
                        <option value="sql_database">SQL Database (Coming Soon)</option>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Upload File *</label>
                    <input type="file" name="file" class="form-control" accept=".csv,.xlsx,.xls" required>
                </div>
            </div>

            <div class="file-upload-zone" id="file-upload-zone">
                <i class="fas fa-cloud-upload-alt"></i>
                <h5>Drag & Drop or Click to Upload</h5>
                <p class="text-muted mb-0">Supports CSV and Excel files up to 50MB</p>
            </div>

            <div class="form-check mt-3">
                <input class="form-check-input" type="checkbox" name="incremental_sync" id="incremental_sync">
                <label class="form-check-label" for="incremental_sync">
                    <strong>Enable Incremental Sync</strong> - Only import new/changed records (based on timestamps)
                </label>
            </div>
        </div>

        {{-- Step 3: Use Template --}}
        <div class="wizard-step" id="step3">
            <div class="d-flex align-items-start mb-4">
                <div class="step-number">3</div>
                <div class="ms-3">
                    <div class="step-title">Use Import Template (Recommended)</div>
                    <div class="step-description">Select a saved template or continue without one</div>
                </div>
            </div>

            @if($templates->count() > 0)
                <div class="mb-4">
                    <label class="form-label">Saved Templates</label>
                    <div class="row">
                        @foreach($templates as $template)
                            <div class="col-md-4 mb-2">
                                <div class="card">
                                    <div class="card-body py-2">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="template_id" value="{{ $template->id }}" id="template_{{ $template->id }}">
                                            <label class="form-check-label" for="template_{{ $template->id }}">
                                                <strong>{{ $template->name }}</strong>
                                                <br><small class="text-muted">{{ ucfirst($template->entity_type) }}</small>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="instructions-box">
                <h6>
                    <i class="fas fa-lightbulb me-2"></i>What is a Template?
                </h6>
                <ul>
                    <li>Templates save your field mapping configuration so you don't have to remap fields every time</li>
                    <li>If you don't have a template yet, you can create one after this import completes</li>
                    <li>For your first import, you can skip this step and map fields manually</li>
                </ul>
            </div>

            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                <strong>After creating this migration:</strong> You'll be taken to a field mapping page where you can match your file's columns to MedCura's fields. The system will try to auto-detect the mapping.
            </div>
        </div>

        <div class="d-flex justify-content-between mt-4">
            <button type="submit" class="btn btn-primary btn-lg">
                <i class="fas fa-rocket me-2"></i>Create Migration & Continue
            </button>
        </div>
    </form>
</div>

<script>
let selectedEntity = null;

function selectEntity(type, element) {
    // Remove selected from all
    document.querySelectorAll('.entity-card').forEach(card => {
        card.classList.remove('selected');
    });

    // Add selected to clicked
    element.classList.add('selected');
    selectedEntity = type;

    // Update hidden input
    document.getElementById('selected_entity_type').value = type;
}

// File upload handling
const fileZone = document.getElementById('file-upload-zone');
const fileInput = document.querySelector('input[name="file"]');

fileZone.addEventListener('click', () => fileInput.click());

fileZone.addEventListener('dragover', (e) => {
    e.preventDefault();
    fileZone.classList.add('dragover');
});

fileZone.addEventListener('dragleave', () => {
    fileZone.classList.remove('dragover');
});

fileZone.addEventListener('drop', (e) => {
    e.preventDefault();
    fileZone.classList.remove('dragover');
    if (e.dataTransfer.files.length) {
        fileInput.files = e.dataTransfer.files;
    }
});
</script>
@endsection