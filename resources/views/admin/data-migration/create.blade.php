@extends('layouts.admin')

@section('title', 'Create Data Migration')

@push('styles')
<style>
    .wizard-step { padding:1.5rem;border:1px solid #eef2f7;border-radius:14px;margin-bottom:1.25rem;transition:all 0.2s;background:#fff;box-shadow:0 2px 12px rgba(15,23,42,0.06); }
    .wizard-step.active { border-color:#0f172a;background:#f8fafc;box-shadow:0 4px 16px rgba(15,23,42,0.08); }
    .step-number { width:40px;height:40px;background:linear-gradient(135deg,#0f172a,#334155);color:#fff;border-radius:12px;display:flex;align-items:center;justify-content:center;font-weight:800;flex-shrink:0; }
    .wizard-step.active .step-number { background:linear-gradient(135deg,#0f172a,#1e293b); }
    .step-title { font-size:1.05rem;font-weight:800;color:#0f172a;margin-bottom:0.2rem; }
    .step-description { color:#64748b;font-size:0.84rem; }
    .entity-grid { display:grid;grid-template-columns:repeat(3,1fr);gap:0.9rem; }
    @media(max-width:768px){ .entity-grid{grid-template-columns:repeat(2,1fr);} }
    .entity-card { border:1px solid #eef2f7;border-radius:12px;padding:1.1rem;cursor:pointer;transition:all 0.2s;text-align:center;background:#fff; }
    .entity-card:hover { border-color:#0f172a;transform:translateY(-2px);box-shadow:0 4px 12px rgba(15,23,42,0.08); }
    .entity-card.selected { border-color:#0f172a;background:#f8fafc;box-shadow:0 4px 16px rgba(15,23,42,0.12); }
    .entity-card i { font-size:1.6rem;color:#0f172a;margin-bottom:0.5rem; }
    .entity-card h5 { font-weight:800;color:#0f172a;font-size:0.90rem;margin-bottom:0.2rem; }
    .entity-card p { font-size:0.74rem;color:#64748b;margin:0; }
    .file-upload-zone { border:1px dashed #cbd5e1;border-radius:14px;padding:2.2rem;text-align:center;cursor:pointer;transition:all 0.2s;background:#f8fafc; }
    .file-upload-zone:hover { border-color:#0f172a;background:#eff6ff; }
    .file-upload-zone.dragover { border-color:#0f172a;background:#eff6ff; }
    .file-upload-zone i { font-size:2.2rem;color:#0f172a;margin-bottom:0.8rem; }
    .instructions-box { background:#f8fafc;border:1px solid #eef2f7;border-radius:12px;padding:1.25rem;margin-bottom:1rem; }
    .instructions-box h6 { font-weight:800;color:#0f172a;margin-bottom:0.6rem;font-size:0.90rem; }
    .instructions-box li { margin-bottom:0.3rem;color:#475569;font-size:0.84rem; }
</style>
@endpush

@section('content')
<div class="container-fluid px-0">
    {{-- Header compatible --}}
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4" style="background:linear-gradient(135deg,#1e293b 0%,#334155 100%);border-radius:16px;padding:1.4rem 1.6rem;box-shadow:0 8px 24px rgba(15,23,42,0.12)">
        <div class="d-flex align-items-center gap-3">
            <div style="width:44px;height:44px;border-radius:12px;background:rgba(255,255,255,0.14);display:flex;align-items:center;justify-content:center"><i class="fas fa-plus" style="color:#fff;font-size:1.1rem"></i></div>
            <div>
                <h1 style="font-size:1.35rem;font-weight:800;color:#fff;letter-spacing:-0.02em;margin:0">Create New Migration</h1>
                <p style="font-size:0.78rem;color:rgba(255,255,255,0.75);margin:2px 0 0">Import data from external systems into MedCura</p>
            </div>
        </div>
        <a href="{{ route('admin.data-migration.index') }}" class="btn btn-sm" style="background:rgba(255,255,255,0.14);border:1px solid rgba(255,255,255,0.18);color:#fff;border-radius:10px;font-weight:700"><i class="fas fa-arrow-left me-1"></i> Back to List</a>
    </div>

    <form method="POST" action="{{ route('admin.data-migration.store') }}" enctype="multipart/form-data">
        @csrf

        {{-- Step 1 --}}
        <div class="wizard-step active" id="step1">
            <div class="d-flex align-items-start gap-3 mb-4">
                <div class="step-number">1</div>
                <div>
                    <div class="step-title">Basic Information</div>
                    <div class="step-description">Give your migration a name and select the data type</div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label" style="font-weight:700;color:#0f172a;font-size:0.84rem">Migration Name *</label>
                    <input type="text" name="name" class="form-control" style="border-radius:10px" placeholder="e.g., Patient Import from Old System" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label" style="font-weight:700;color:#0f172a;font-size:0.84rem">Description (optional)</label>
                    <input type="text" name="description" class="form-control" style="border-radius:10px" placeholder="Brief description">
                </div>
            </div>
            <div class="mb-2">
                <label class="form-label" style="font-weight:700;color:#0f172a;font-size:0.84rem">Data Type to Import *</label>
                <div class="entity-grid">
                    <div class="entity-card" onclick="selectEntity('department', this)"><i class="fas fa-building"></i><h5>Department</h5><p>Office/facility structure</p></div>
                    <div class="entity-card" onclick="selectEntity('specialty', this)"><i class="fas fa-stethoscope"></i><h5>Specialty</h5><p>Medical specialties</p></div>
                    <div class="entity-card" onclick="selectEntity('doctor', this)"><i class="fas fa-user-md"></i><h5>Doctor</h5><p>Physicians & specialists</p></div>
                    <div class="entity-card" onclick="selectEntity('patient', this)"><i class="fas fa-user"></i><h5>Patient</h5><p>Patient records</p></div>
                    <div class="entity-card" onclick="selectEntity('appointment', this)"><i class="fas fa-calendar-check"></i><h5>Appointment</h5><p>Scheduled appointments</p></div>
                    <div class="entity-card" onclick="selectEntity('diagnosis', this)"><i class="fas fa-notes-medical"></i><h5>Diagnosis</h5><p>ICD-10 diagnoses</p></div>
                    <div class="entity-card" onclick="selectEntity('prescription', this)"><i class="fas fa-pills"></i><h5>Prescription</h5><p>Medication prescriptions</p></div>
                    <div class="entity-card" onclick="selectEntity('treatment', this)"><i class="fas fa-heartbeat"></i><h5>Treatment</h5><p>Treatment plans</p></div>
                    <div class="entity-card" onclick="selectEntity('allergy', this)"><i class="fas fa-exclamation-triangle"></i><h5>Allergy</h5><p>Patient allergies</p></div>
                    <div class="entity-card" onclick="selectEntity('insurance', this)"><i class="fas fa-shield-alt"></i><h5>Insurance</h5><p>Insurance policies</p></div>
                </div>
                <input type="hidden" name="entity_type" id="selected_entity_type" required>
            </div>
        </div>

        {{-- Step 2 --}}
        <div class="wizard-step" id="step2">
            <div class="d-flex align-items-start gap-3 mb-4">
                <div class="step-number">2</div>
                <div>
                    <div class="step-title">Upload Data File</div>
                    <div class="step-description">Select CSV or Excel file containing your data</div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label" style="font-weight:700;color:#0f172a;font-size:0.84rem">Source Type *</label>
                    <select name="source_type" class="form-select" style="border-radius:10px" required>
                        <option value="csv">CSV File (.csv)</option>
                        <option value="excel">Excel File (.xlsx, .xls)</option>
                        <option value="api" disabled>API Connection (Coming Soon)</option>
                        <option value="sql_database" disabled>SQL Database (Coming Soon)</option>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label" style="font-weight:700;color:#0f172a;font-size:0.84rem">Upload File *</label>
                    <input type="file" name="file" class="form-control" style="border-radius:10px" accept=".csv,.xlsx,.xls" required>
                </div>
            </div>
            <div class="file-upload-zone" id="file-upload-zone">
                <i class="fas fa-cloud-upload-alt"></i>
                <h5 style="font-weight:800;color:#0f172a">Drag & Drop or Click to Upload</h5>
                <p class="text-muted small mb-0">Supports CSV and Excel files up to 50MB</p>
            </div>
            <div class="form-check mt-3">
                <input class="form-check-input" type="checkbox" name="incremental_sync" id="incremental_sync">
                <label class="form-check-label" for="incremental_sync" style="font-size:0.84rem;color:#0f172a"><strong>Enable Incremental Sync</strong> <span class="text-muted">- Only import new/changed records</span></label>
            </div>
        </div>

        {{-- Step 3 --}}
        <div class="wizard-step" id="step3">
            <div class="d-flex align-items-start gap-3 mb-4">
                <div class="step-number">3</div>
                <div>
                    <div class="step-title">Use Import Template (Recommended)</div>
                    <div class="step-description">Select a saved template or continue without one</div>
                </div>
            </div>
            @if($templates->count() > 0)
                <div class="mb-4">
                    <label class="form-label" style="font-weight:700;color:#0f172a;font-size:0.84rem">Saved Templates</label>
                    <div class="row g-2">
                        @foreach($templates as $template)
                            <div class="col-md-4">
                                <label class="card border-0 shadow-sm p-3 d-flex align-items-center gap-2" style="border-radius:12px;cursor:pointer">
                                    <input class="form-check-input" type="radio" name="template_id" value="{{ $template->id }}" id="template_{{ $template->id }}">
                                    <div><div style="font-weight:700;color:#0f172a;font-size:0.84rem">{{ $template->name }}</div><small class="text-muted">{{ ucfirst($template->entity_type) }}</small></div>
                                </label>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
            <div class="instructions-box">
                <h6><i class="fas fa-lightbulb me-2" style="color:#f59e0b"></i>What is a Template?</h6>
                <ul class="mb-0">
                    <li>Templates save your field mapping so you don't remap every time</li>
                    <li>If you don't have one yet, you can create one after this import</li>
                    <li>For your first import, skip this step and map manually</li>
                </ul>
            </div>
            <div class="alert d-flex align-items-center gap-2 mb-0" style="background:#eff6ff;border:1px solid #dbeafe;border-radius:12px;color:#1e40af;font-size:0.84rem"><i class="fas fa-info-circle"></i> <strong>After creating:</strong> You'll be taken to field mapping where you can match columns. Auto-detect tries to map.</div>
        </div>

        <div class="d-flex justify-content-end mt-3">
            <button type="submit" class="btn" style="background:#0f172a;color:#fff;border-radius:12px;font-weight:800;padding:0.7rem 1.6rem"><i class="fas fa-rocket me-2"></i>Create Migration & Continue</button>
        </div>
    </form>
</div>

<script>
let selectedEntity = null;
function selectEntity(type, element) {
    document.querySelectorAll('.entity-card').forEach(card => card.classList.remove('selected'));
    element.classList.add('selected');
    selectedEntity = type;
    document.getElementById('selected_entity_type').value = type;
    document.querySelectorAll('.wizard-step').forEach(s=>s.classList.remove('active'));
    document.getElementById('step1').classList.add('active');
    // Highlight step2 when entity selected
    if(type) document.getElementById('step2').classList.add('active');
}
const fileZone = document.getElementById('file-upload-zone');
const fileInput = document.querySelector('input[name="file"]');
if(fileZone && fileInput){
    fileZone.addEventListener('click', () => fileInput.click());
    fileZone.addEventListener('dragover', (e) => { e.preventDefault(); fileZone.classList.add('dragover'); });
    fileZone.addEventListener('dragleave', () => fileZone.classList.remove('dragover'));
    fileZone.addEventListener('drop', (e) => { e.preventDefault(); fileZone.classList.remove('dragover'); if (e.dataTransfer.files.length) fileInput.files = e.dataTransfer.files; });
}
</script>
@endsection
