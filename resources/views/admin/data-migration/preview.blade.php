@extends('layouts.admin')

@section('title', 'Preview Data - ' . $dataMigration->name)

@push('styles')
<style>
    .preview-container {
        background: white;
        border-radius: 16px;
        padding: 2rem;
        box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    }
    .mapping-row {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin-bottom: 0.75rem;
        padding: 0.75rem;
        background: #f8f9fa;
        border-radius: 8px;
    }
    .mapping-row select {
        flex: 1;
    }
    .preview-table {
        font-size: 0.8rem;
    }
    .preview-table th {
        background: #2c3e50;
        color: white;
        font-weight: 600;
        padding: 0.75rem 0.5rem;
        text-transform: uppercase;
        font-size: 0.7rem;
    }
    .preview-table td {
        padding: 0.5rem;
        vertical-align: middle;
        max-width: 200px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .preview-table tr:nth-child(even) {
        background: #f8f9fa;
    }
    .instructions-box {
        background: linear-gradient(135deg, #fff3cd 0%, #fff8e1 100%);
        border-left: 4px solid #ffc107;
        border-radius: 8px;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
    }
    .instructions-box h6 {
        color: #856404;
        font-weight: 600;
        margin-bottom: 0.75rem;
    }
    .instructions-box ul {
        margin: 0;
        padding-left: 1.25rem;
        color: #856404;
    }
    .instructions-box li {
        margin-bottom: 0.5rem;
    }
    .success-icon {
        color: #28a745;
        font-size: 1.5rem;
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    {{-- Header --}}
    <div class="row mb-4">
        <div class="col-md-8">
            <h2 class="page-title">
                <i class="fas fa-eye me-2"></i>Preview & Field Mapping
            </h2>
            <p class="text-muted mb-0">Review your data and map source fields to MedCura fields</p>
        </div>
        <div class="col-md-4 text-end">
            <a href="{{ route('admin.data-migration.show', $dataMigration) }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>Back to Details
            </a>
        </div>
    </div>

    {{-- Instructions --}}
    <div class="instructions-box">
        <h6>
            <i class="fas fa-info-circle me-2"></i>How to Map Fields
        </h6>
        <ul>
            <li><strong>Source Fields (Left)</strong> - These are the column names from your uploaded file</li>
            <li><strong>MedCura Fields (Right)</strong> - Select which MedCura field each source column should map to</li>
            <li><strong>Source ID</strong> - Select which column contains the unique ID from your source system (for linking related data)</li>
            <li>Only required fields need to be mapped. Optional fields can be left as "Not Mapped"</li>
            <li>Your mapping will be saved as a template if you want to reuse it for future imports</li>
        </ul>
    </div>

    <form method="POST" action="{{ route('admin.data-migration.start', $dataMigration) }}">
        @csrf

        <div class="preview-container mb-4">
            <h5 class="mb-4">
                <i class="fas fa-exchange-alt me-2"></i>Field Mapping Configuration
            </h5>

            {{-- Source ID mapping --}}
            <div class="mapping-row">
                <div class="col-md-3">
                    <label class="form-label text-muted small">SOURCE ID COLUMN *</label>
                    <select name="field_mapping[source_id]" class="form-select form-select-sm" required>
                        <option value="">-- Select Source ID Column --</option>
                        @foreach($headers as $header)
                            <option value="{{ $header }}" {{ ($fieldMapping['source_id'] ?? '') === $header ? 'selected' : '' }}>
                                {{ $header }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <small class="text-muted">
                        <i class="fas fa-info-circle me-1"></i>
                        Select the column that contains the unique ID from your source system.
                        This is critical for linking related data (e.g., linking appointments to patients).
                    </small>
                </div>
            </div>

            <hr class="my-4">

            <div class="row">
                @foreach($headers as $index => $header)
                    <div class="col-md-6 mb-3">
                        <div class="mapping-row">
                            <div class="flex-grow-1">
                                <label class="form-label text-muted small">SOURCE: <strong>{{ $header }}</strong></label>
                                <select name="field_mapping[{{ $header }}]" class="form-select form-select-sm">
                                    <option value="">Not Mapped (Skip this column)</option>
                                    @foreach($availableFields as $field => $label)
                                        <option value="{{ $field }}" {{ ($fieldMapping[$header] ?? '') === $field ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="text-center" style="width: 30px;">
                                <i class="fas fa-arrow-right text-muted"></i>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Data Preview --}}
        <div class="preview-container mb-4">
            <h5 class="mb-4">
                <i class="fas fa-table me-2"></i>Data Preview (First 10 rows)
            </h5>
            <div class="table-responsive">
                <table class="table preview-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            @foreach($headers as $header)
                                <th>{{ $header }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($rows as $rowIndex => $row)
                            <tr>
                                <td>{{ $rowIndex + 1 }}</td>
                                @foreach($row as $cell)
                                    <td title="{{ $cell }}">{{ $cell }}</td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="d-flex justify-content-between">
            <a href="{{ route('admin.data-migration.show', $dataMigration) }}" class="btn btn-outline-secondary btn-lg">
                <i class="fas fa-times me-2"></i>Cancel
            </a>
            <button type="submit" class="btn btn-primary btn-lg">
                <i class="fas fa-play me-2"></i>Start Import
            </button>
        </div>
    </form>
</div>
@endsection