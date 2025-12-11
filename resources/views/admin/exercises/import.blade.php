@extends('layouts.admin')

@section('title', 'Import Exercises')

@push('styles')
<style>
    .import-section {
        background: white;
        border-radius: 12px;
        padding: 2rem;
        margin-bottom: 2rem;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }

    .template-download {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 12px;
        padding: 2rem;
        text-align: center;
        margin-bottom: 2rem;
    }

    .instructions-list {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 1.5rem;
        margin-bottom: 2rem;
    }

    .instructions-list ol {
        margin-bottom: 0;
    }

    .instructions-list li {
        margin-bottom: 0.5rem;
    }

    .file-upload-area {
        border: 2px dashed #dee2e6;
        border-radius: 8px;
        padding: 2rem;
        text-align: center;
        transition: all 0.3s ease;
        background: #f8f9fa;
    }

    .file-upload-area:hover {
        border-color: #007bff;
        background: #e3f2fd;
    }

    .file-upload-area.dragover {
        border-color: #007bff;
        background: #e3f2fd;
    }

    .upload-icon {
        font-size: 3rem;
        color: #6c757d;
        margin-bottom: 1rem;
    }

    .file-info {
        margin-top: 1rem;
        padding: 1rem;
        background: white;
        border-radius: 6px;
        border: 1px solid #dee2e6;
    }
</style>
@endpush

@section('content')
<div class="admin-page">
    <div class="admin-container">
        <!-- Header -->
        <div class="admin-header">
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <div>
                    <h1 class="text-white">Import Exercises</h1>
                    <p class="mb-0">Bulk import exercises from CSV file</p>
                </div>
                <a href="{{ route('admin.exercises.index') }}" class="btn btn-outline-light">
                    <i class="fas fa-arrow-left me-2"></i>Back to Library
                </a>
            </div>
        </div>

        <!-- Template Download -->
        <div class="template-download">
            <i class="fas fa-download fa-3x mb-3"></i>
            <h4>Download Import Template</h4>
            <p class="mb-3">Get started with our CSV template that includes all required fields and sample data.</p>
            <a href="{{ route('admin.exercises.template.download') }}" class="btn btn-light btn-lg">
                <i class="fas fa-file-csv me-2"></i>Download Template
            </a>
        </div>

        <!-- Instructions -->
        <div class="instructions-list">
            <h5><i class="fas fa-info-circle me-2"></i>Import Instructions</h5>
            <ol>
                <li><strong>Download the template</strong> above to see the required format and sample data.</li>
                <li><strong>Fill in your exercise data</strong> following the column structure in the template.</li>
                <li><strong>Required fields:</strong> Name, Category, Difficulty Level, Description, Instructions</li>
                <li><strong>Optional fields:</strong> Equipment Required, Target Muscle Groups, Contraindications, Duration, Video URL, Image URL</li>
                <li><strong>Array fields</strong> (Equipment, Muscles, Contraindications) should be semicolon-separated values.</li>
                <li><strong>Save as CSV</strong> and upload below. Maximum file size: 5MB</li>
                <li><strong>Review results</strong> after import - any errors will be displayed.</li>
            </ol>
        </div>

        <!-- Import Form -->
        <div class="import-section">
            <h4><i class="fas fa-upload me-2"></i>Upload CSV File</h4>

            <form method="POST" action="{{ route('admin.exercises.import.store') }}" enctype="multipart/form-data" id="import-form">
                @csrf

                <div class="row">
                    <div class="col-md-8">
                        <div class="mb-3">
                            <label for="csv_file" class="form-label">CSV File <span class="text-danger">*</span></label>
                            <div class="file-upload-area" id="file-upload-area">
                                <div class="upload-icon">
                                    <i class="fas fa-cloud-upload-alt"></i>
                                </div>
                                <h5>Drag & Drop CSV File Here</h5>
                                <p class="text-muted">Or click to browse files</p>
                                <input type="file" id="csv_file" name="csv_file"
                                       accept=".csv,.txt" style="display: none;" required>
                                <button type="button" class="btn btn-primary" onclick="document.getElementById('csv_file').click()">
                                    <i class="fas fa-folder-open me-2"></i>Browse Files
                                </button>
                            </div>
                            @error('csv_file')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div id="file-info" class="file-info" style="display: none;">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-file-csv fa-2x text-success me-3"></i>
                                <div>
                                    <h6 id="file-name"></h6>
                                    <small id="file-size" class="text-muted"></small>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-danger ms-auto" onclick="clearFile()">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">Import Options</h6>
                            </div>
                            <div class="card-body">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="skip_duplicates" name="skip_duplicates" checked>
                                    <label class="form-check-label" for="skip_duplicates">
                                        Skip duplicate exercises
                                    </label>
                                    <small class="form-text text-muted">
                                        Skip exercises that already exist (based on name)
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('admin.exercises.index') }}" class="btn btn-secondary">
                        <i class="fas fa-times me-2"></i>Cancel
                    </a>
                    <button type="submit" class="btn btn-primary" id="import-btn" disabled>
                        <i class="fas fa-upload me-2"></i>Import Exercises
                    </button>
                </div>
            </form>
        </div>

        <!-- Recent Imports -->
        @if(session('success'))
            <div class="import-section">
                <h4><i class="fas fa-history me-2"></i>Import Results</h4>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle me-2"></i>
                    {{ session('success') }}
                </div>
            </div>
        @endif

        @if(session('error'))
            <div class="import-section">
                <h4><i class="fas fa-exclamation-triangle me-2"></i>Import Errors</h4>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    {{ session('error') }}
                </div>
            </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
    // File upload handling
    const fileInput = document.getElementById('csv_file');
    const fileUploadArea = document.getElementById('file-upload-area');
    const fileInfo = document.getElementById('file-info');
    const fileName = document.getElementById('file-name');
    const fileSize = document.getElementById('file-size');
    const importBtn = document.getElementById('import-btn');

    // Drag and drop functionality
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        fileUploadArea.addEventListener(eventName, preventDefaults, false);
    });

    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }

    ['dragenter', 'dragover'].forEach(eventName => {
        fileUploadArea.addEventListener(eventName, highlight, false);
    });

    ['dragleave', 'drop'].forEach(eventName => {
        fileUploadArea.addEventListener(eventName, unhighlight, false);
    });

    function highlight(e) {
        fileUploadArea.classList.add('dragover');
    }

    function unhighlight(e) {
        fileUploadArea.classList.remove('dragover');
    }

    fileUploadArea.addEventListener('drop', handleDrop, false);

    function handleDrop(e) {
        const dt = e.dataTransfer;
        const files = dt.files;

        if (files.length > 0) {
            fileInput.files = files;
            handleFileSelect();
        }
    }

    // File selection handling
    fileInput.addEventListener('change', handleFileSelect);

    function handleFileSelect() {
        const file = fileInput.files[0];

        if (file) {
            // Validate file type
            const allowedTypes = ['text/csv', 'application/vnd.ms-excel', 'text/plain'];
            if (!allowedTypes.includes(file.type) && !file.name.toLowerCase().endsWith('.csv')) {
                alert('Please select a valid CSV file.');
                clearFile();
                return;
            }

            // Validate file size (5MB)
            if (file.size > 5 * 1024 * 1024) {
                alert('File size must be less than 5MB.');
                clearFile();
                return;
            }

            // Display file info
            fileName.textContent = file.name;
            fileSize.textContent = formatFileSize(file.size);
            fileInfo.style.display = 'block';
            importBtn.disabled = false;
        } else {
            clearFile();
        }
    }

    function clearFile() {
        fileInput.value = '';
        fileInfo.style.display = 'none';
        importBtn.disabled = true;
    }

    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    // Form submission
    document.getElementById('import-form').addEventListener('submit', function(e) {
        importBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Importing...';
        importBtn.disabled = true;
    });
</script>
@endpush
