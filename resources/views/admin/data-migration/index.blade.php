@extends('layouts.admin')

@section('title', 'Data Migration')

@push('styles')
<style>
    .migration-card {
        background: white;
        border-radius: 16px;
        padding: 1.5rem;
        box-shadow: 0 2px 12px rgba(0,0,0,0.08);
        margin-bottom: 1.5rem;
    }
    .migration-card h4 {
        font-weight: 600;
        color: #2c3e50;
        margin-bottom: 0.5rem;
    }
    .stat-number {
        font-size: 2.5rem;
        font-weight: 700;
        color: #DE6262;
    }
    .stat-label {
        font-size: 0.875rem;
        color: #6c757d;
    }
    .status-badge {
        padding: 0.35rem 0.75rem;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
    }
    .source-badge {
        background: #e9ecef;
        color: #495057;
        padding: 0.25rem 0.5rem;
        border-radius: 4px;
        font-size: 0.75rem;
    }
    .template-card {
        border: 1px solid #dee2e6;
        border-radius: 12px;
        padding: 1rem;
        margin-bottom: 0.75rem;
        transition: all 0.2s;
    }
    .template-card:hover {
        border-color: #DE6262;
        box-shadow: 0 4px 12px rgba(222,98,98,0.15);
    }
    .progress-bar-custom {
        height: 8px;
        border-radius: 4px;
        background: #e9ecef;
    }
    .progress-bar-fill {
        height: 100%;
        border-radius: 4px;
        background: linear-gradient(135deg, #DE6262, #E87A7A);
        transition: width 0.3s ease;
    }
    .info-box {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        border-left: 4px solid #DE6262;
        border-radius: 8px;
        padding: 1.25rem;
        margin-bottom: 1.5rem;
    }
    .info-box h5 {
        font-weight: 600;
        color: #2c3e50;
        margin-bottom: 0.75rem;
    }
    .info-box ul {
        margin: 0;
        padding-left: 1.25rem;
    }
    .info-box li {
        margin-bottom: 0.5rem;
        color: #495057;
    }
    .instruction-box {
        background: #fff;
        border: 1px solid #dee2e6;
        border-radius: 12px;
        padding: 1.5rem;
        margin-bottom: 1rem;
    }
    .instruction-box h6 {
        font-weight: 600;
        color: #DE6262;
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    .instruction-box code {
        background: #f8f9fa;
        padding: 0.125rem 0.375rem;
        border-radius: 4px;
        font-size: 0.875rem;
    }
    .field-sample {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 1rem;
        margin-top: 0.75rem;
    }
    .field-sample code {
        display: block;
        white-space: pre-wrap;
        font-size: 0.8rem;
        color: #495057;
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="page-title">
                <i class="fas fa-exchange-alt me-2"></i>Data Migration
            </h2>
            <p class="text-muted">Import data from external systems into MedCura</p>
        </div>
    </div>

    {{-- Instructions --}}
    <div class="instruction-box">
        <h6>
            <i class="fas fa-book-open"></i>
            HOW TO USE DATA MIGRATION
        </h6>

        <div class="row">
            <div class="col-md-6">
                <h6 class="text-primary mb-3">Step 1: Choose What to Import</h6>
                <p>Select the data type you want to import (Patients, Doctors, Appointments, etc.)</p>

                <h6 class="text-primary mb-3">Step 2: Download Template</h6>
                <p>Download the CSV template for your data type. The template shows you exactly what fields are required.</p>

                <h6 class="text-primary mb-3">Step 3: Prepare Your Data</h6>
                <p>Copy your data from the external system into the CSV template. Make sure:</p>
                <ul>
                    <li>Dates are in <code>YYYY-MM-DD</code> format (e.g., 2024-05-15)</li>
                    <li>Phone numbers contain only digits (e.g., 5551234567, not 555-123-4567)</li>
                    <li>Email addresses are valid format (e.g., name@example.com)</li>
                    <li>Required fields are not empty</li>
                </ul>
            </div>
            <div class="col-md-6">
                <h6 class="text-primary mb-3">Step 4: Upload & Preview</h6>
                <p>Upload your CSV file. We'll show you a preview so you can verify the data looks correct.</p>

                <h6 class="text-primary mb-3">Step 5: Map Fields</h6>
                <p>Match your CSV columns to MedCura fields. Our system will try to auto-detect the mapping.</p>

                <h6 class="text-primary mb-3">Step 6: Start Import</h6>
                <p>Click "Start Import" and watch the progress. Failed records will be logged so you can fix and retry.</p>

                <div class="alert alert-info mt-3">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Important:</strong> For appointments and clinical data, you must import patients and doctors FIRST so the system can link them correctly.
                </div>
            </div>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="migration-card text-center">
                <div class="stat-number">{{ $stats['total_migrations'] }}</div>
                <div class="stat-label">Total Migrations</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="migration-card text-center">
                <div class="stat-number text-success">{{ $stats['completed'] }}</div>
                <div class="stat-label">Completed</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="migration-card text-center">
                <div class="stat-number text-danger">{{ $stats['failed'] }}</div>
                <div class="stat-label">Failed</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="migration-card text-center">
                <div class="stat-number text-primary">{{ $stats['in_progress'] }}</div>
                <div class="stat-label">In Progress</div>
            </div>
        </div>
    </div>

    <div class="row">
        {{-- Templates --}}
        <div class="col-md-4">
            <div class="migration-card">
                <h4>
                    <i class="fas fa-file-csv me-2"></i>Import Templates
                </h4>
                <p class="text-muted small mb-3">Click to download CSV templates for each data type</p>

                <div class="d-grid gap-2">
                    @foreach(['department', 'specialty', 'doctor', 'patient', 'appointment', 'diagnosis', 'prescription', 'treatment', 'allergy', 'insurance'] as $type)
                        <a href="{{ route('admin.data-migration.download-template', $type) }}" class="btn btn-outline-primary btn-sm text-start">
                            <i class="fas fa-download me-2"></i>{{ ucfirst($type) }}
                        </a>
                    @endforeach
                </div>
            </div>

            <div class="migration-card">
                <h4>
                    <i class="fas fa-layer-group me-2"></i>Import Order Guide
                </h4>
                <p class="text-muted small mb-3">For related data, import in this order to maintain relationships</p>
                <ol class="text-muted small">
                    <li><strong>Departments</strong> - Office/facility structure</li>
                    <li><strong>Specialties</strong> - Medical specialties</li>
                    <li><strong>Doctors</strong> - Links to specialties</li>
                    <li><strong>Patients</strong> - No dependencies</li>
                    <li><strong>Insurance</strong> - Links to patients</li>
                    <li><strong>Appointments</strong> - Links to patients & doctors</li>
                    <li><strong>Diagnoses</strong> - Links to patients & doctors</li>
                    <li><strong>Prescriptions</strong> - Links to patients & doctors</li>
                    <li><strong>Treatments</strong> - Links to patients & doctors</li>
                    <li><strong>Allergies</strong> - Links to patients</li>
                </ol>
                <div class="alert alert-warning mt-3 small">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Important:</strong> Always import foundational data (departments, doctors, patients) BEFORE clinical data (appointments, diagnoses, prescriptions) so relationships are preserved.
                </div>
            </div>
        </div>

        {{-- Migrations List --}}
        <div class="col-md-8">
            <div class="migration-card">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4>
                        <i class="fas fa-list me-2"></i>Migration History
                    </h4>
                    <a href="{{ route('admin.data-migration.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>New Migration
                    </a>
                </div>

                @if($migrations->isEmpty())
                    <div class="text-center py-5">
                        <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                        <p class="text-muted">No migrations yet. Click "New Migration" to start.</p>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Type</th>
                                    <th>Source</th>
                                    <th>Progress</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($migrations as $migration)
                                    <tr>
                                        <td>
                                            <strong>{{ $migration->name }}</strong>
                                            @if($migration->description)
                                                <br><small class="text-muted">{{ Str::limit($migration->description, 50) }}</small>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary">{{ ucfirst($migration->entity_type) }}</span>
                                        </td>
                                        <td>
                                            <span class="source-badge">{{ $migration->getSourceTypeLabel() }}</span>
                                        </td>
                                        <td style="min-width: 150px;">
                                            @if($migration->total_records > 0)
                                                <div class="mb-1">
                                                    <small class="text-muted">{{ $migration->processed_records }} / {{ $migration->total_records }}</small>
                                                </div>
                                                <div class="progress-bar-custom">
                                                    <div class="progress-bar-fill" style="width: {{ $migration->getProgressPercentage() }}%"></div>
                                                </div>
                                                <small class="text-muted">{{ $migration->getProgressPercentage() }}%</small>
                                            @else
                                                <span class="text-muted">Waiting...</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge {{ $migration->getStatusBadgeClass() }}">
                                                {{ ucfirst(str_replace('_', ' ', $migration->status)) }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="{{ route('admin.data-migration.show', $migration) }}" class="btn btn-outline-primary">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                @if($migration->status === 'failed' || $migration->status === 'completed')
                                                    <a href="{{ route('admin.data-migration.export-errors', $migration) }}" class="btn btn-outline-danger">
                                                        <i class="fas fa-download"></i>
                                                    </a>
                                                @endif
                                                @if($migration->status !== 'completed' && $migration->status !== 'cancelled')
                                                    <form method="POST" action="{{ route('admin.data-migration.cancel', $migration) }}" class="d-inline">
                                                        @csrf
                                                        <button type="submit" class="btn btn-outline-warning" onclick="return confirm('Cancel this migration?')">
                                                            <i class="fas fa-stop"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                                <form method="POST" action="{{ route('admin.data-migration.destroy', $migration) }}" class="d-inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-outline-danger" onclick="return confirm('Delete this migration?')">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3">
                        {{ $migrations->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection