@extends('layouts.admin')

@section('title', 'Migration Details - ' . $dataMigration->name)

@push('styles')
<style>
    .detail-card {
        background: white;
        border-radius: 16px;
        padding: 1.5rem;
        box-shadow: 0 2px 12px rgba(0,0,0,0.08);
        margin-bottom: 1.5rem;
    }
    .progress-container {
        background: #f8f9fa;
        border-radius: 12px;
        padding: 2rem;
        text-align: center;
    }
    .progress-circle {
        width: 150px;
        height: 150px;
        border-radius: 50%;
        background: conic-gradient(#DE6262 {{ $dataMigration->getProgressPercentage() }}%, #e9ecef 0);
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 1.5rem;
    }
    .progress-circle-inner {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        background: white;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-direction: column;
    }
    .progress-circle-inner .percentage {
        font-size: 2rem;
        font-weight: 700;
        color: #DE6262;
    }
    .progress-circle-inner .label {
        font-size: 0.75rem;
        color: #6c757d;
    }
    .stats-row {
        display: flex;
        justify-content: space-around;
        margin-top: 1.5rem;
    }
    .stat-item {
        text-align: center;
    }
    .stat-item .number {
        font-size: 1.5rem;
        font-weight: 700;
        color: #2c3e50;
    }
    .stat-item .label {
        font-size: 0.75rem;
        color: #6c757d;
    }
    .stat-item.success .number { color: #28a745; }
    .stat-item.failed .number { color: #dc3545; }
    .stat-item.pending .number { color: #6c757d; }
    .error-log {
        background: #fff5f5;
        border: 1px solid #f5c6cb;
        border-radius: 8px;
        padding: 1rem;
        max-height: 300px;
        overflow-y: auto;
        font-family: monospace;
        font-size: 0.8rem;
        white-space: pre-wrap;
    }
    .field-mapping-table {
        font-size: 0.85rem;
    }
    .field-mapping-table th {
        background: #f8f9fa;
        font-weight: 600;
        color: #495057;
    }
    .sample-data-table {
        font-size: 0.8rem;
    }
    .sample-data-table th {
        background: #f8f9fa;
        font-weight: 600;
        color: #495057;
    }
    .badge-custom {
        padding: 0.5rem 1rem;
        border-radius: 20px;
        font-size: 0.875rem;
        font-weight: 600;
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    {{-- Header --}}
    <div class="row mb-4">
        <div class="col-md-8">
            <h2 class="page-title">
                <i class="fas fa-exchange-alt me-2"></i>{{ $dataMigration->name }}
            </h2>
            <p class="text-muted mb-0">{{ $dataMigration->description ?? 'No description' }}</p>
        </div>
        <div class="col-md-4 text-end">
            <a href="{{ route('admin.data-migration.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>Back to List
            </a>
        </div>
    </div>

    {{-- Status Banner --}}
    <div class="detail-card">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <span class="badge-custom {{ $dataMigration->getStatusBadgeClass() }}">
                    <i class="fas @if($dataMigration->status === 'completed') fa-check @elseif($dataMigration->status === 'failed') fa-times @elseif($dataMigration->status === 'in_progress') fa-spinner fa-spin @endif me-2"></i>
                    {{ ucfirst(str_replace('_', ' ', $dataMigration->status)) }}
                </span>
                <span class="ms-3 text-muted">
                    <i class="fas fa-database me-2"></i>{{ ucfirst($dataMigration->entity_type) }}
                    <i class="fas fa-file-alt ms-3 me-2"></i>{{ $dataMigration->getSourceTypeLabel() }}
                </span>
            </div>
            <div class="btn-group">
                @if($dataMigration->status === 'failed' || $dataMigration->status === 'completed')
                    <a href="{{ route('admin.data-migration.export-errors', $dataMigration) }}" class="btn btn-outline-danger">
                        <i class="fas fa-download me-2"></i>Export Errors
                    </a>
                @endif
                @if($dataMigration->status !== 'completed' && $dataMigration->status !== 'cancelled')
                    <form method="POST" action="{{ route('admin.data-migration.cancel', $dataMigration) }}" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-outline-warning" onclick="return confirm('Cancel this migration?')">
                            <i class="fas fa-stop me-2"></i>Cancel
                        </button>
                    </form>
                @endif
                <form method="POST" action="{{ route('admin.data-migration.destroy', $dataMigration) }}" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger" onclick="return confirm('Delete this migration?')">
                        <i class="fas fa-trash me-2"></i>Delete
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="row">
        {{-- Progress Section --}}
        <div class="col-md-5">
            <div class="detail-card">
                <h5 class="mb-4">
                    <i class="fas fa-chart-pie me-2"></i>Progress
                </h5>

                @if($dataMigration->total_records > 0)
                    <div class="progress-container">
                        <div class="progress-circle">
                            <div class="progress-circle-inner">
                                <span class="percentage">{{ $dataMigration->getProgressPercentage() }}%</span>
                                <span class="label">Complete</span>
                            </div>
                        </div>

                        <div class="stats-row">
                            <div class="stat-item">
                                <div class="number">{{ $dataMigration->total_records }}</div>
                                <div class="label">Total Records</div>
                            </div>
                            <div class="stat-item success">
                                <div class="number">{{ $dataMigration->success_records }}</div>
                                <div class="label">Imported</div>
                            </div>
                            <div class="stat-item failed">
                                <div class="number">{{ $dataMigration->failed_records }}</div>
                                <div class="label">Failed</div>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="text-center py-4">
                        <i class="fas fa-hourglass-half fa-3x text-muted mb-3"></i>
                        <p class="text-muted">Waiting to start processing...</p>
                    </div>
                @endif
            </div>

            {{-- Field Mapping --}}
            @if($dataMigration->field_mapping)
                <div class="detail-card">
                    <h5 class="mb-3">
                        <i class="fas fa-exchange-alt me-2"></i>Field Mapping
                    </h5>
                    <table class="table table-sm field-mapping-table">
                        <thead>
                            <tr>
                                <th>Source Field</th>
                                <th>→</th>
                                <th>MedCura Field</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($dataMigration->field_mapping as $source => $target)
                                @if($source !== 'source_id')
                                    <tr>
                                        <td><code>{{ $source }}</code></td>
                                        <td>→</td>
                                        <td><code>{{ $target }}</code></td>
                                    </tr>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        {{-- Details Section --}}
        <div class="col-md-7">
            {{-- Migration Info --}}
            <div class="detail-card">
                <h5 class="mb-3">
                    <i class="fas fa-info-circle me-2"></i>Migration Details
                </h5>
                <table class="table table-borderless">
                    <tr>
                        <td class="text-muted" style="width: 40%;">Created By</td>
                        <td><strong>{{ $dataMigration->user->name ?? 'Unknown' }}</strong></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Created At</td>
                        <td><strong>{{ $dataMigration->created_at->format('M d, Y H:i') }}</strong></td>
                    </tr>
                    @if($dataMigration->last_sync_at)
                        <tr>
                            <td class="text-muted">Last Sync</td>
                            <td><strong>{{ $dataMigration->last_sync_at->format('M d, Y H:i') }}</strong></td>
                        </tr>
                    @endif
                    @if($dataMigration->template_name)
                        <tr>
                            <td class="text-muted">Using Template</td>
                            <td><span class="badge bg-info">{{ $dataMigration->template_name }}</span></td>
                        </tr>
                    @endif
                    @if($dataMigration->source_path)
                        <tr>
                            <td class="text-muted">Source File</td>
                            <td><code>{{ basename($dataMigration->source_path) }}</code></td>
                        </tr>
                    @endif
                    <tr>
                        <td class="text-muted">Incremental Sync</td>
                        <td>
                            @if($dataMigration->incremental_sync)
                                <span class="badge bg-success">Enabled</span>
                            @else
                                <span class="badge bg-secondary">Disabled</span>
                            @endif
                        </td>
                    </tr>
                </table>
            </div>

            {{-- Error Log --}}
            @if($dataMigration->error_log)
                <div class="detail-card">
                    <h5 class="mb-3">
                        <i class="fas fa-exclamation-triangle me-2"></i>Error Log
                    </h5>
                    <div class="error-log">{{ $dataMigration->error_log }}</div>
                </div>
            @endif

            {{-- Failed Records --}}
            @if($failedRecords->count() > 0)
                <div class="detail-card">
                    <h5 class="mb-3">
                        <i class="fas fa-times-circle me-2"></i>Failed Records ({{ $failedRecords->count() }})
                    </h5>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Source ID</th>
                                    <th>Error</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($failedRecords->take(20) as $record)
                                    <tr>
                                        <td><code>{{ $record->source_id ?? 'N/A' }}</code></td>
                                        <td>
                                            <small>{{ $record->error_message ?? 'Validation failed' }}</small>
                                            @if($record->validation_errors)
                                                <br><small class="text-danger">
                                                    {{ implode(', ', array_column($record->validation_errors, 'message')) }}
                                                </small>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        @if($failedRecords->count() > 20)
                            <p class="text-muted text-center">Showing first 20 of {{ $failedRecords->count() }} failed records</p>
                        @endif
                    </div>
                </div>
            @endif

            {{-- Import Records --}}
            @if($records->count() > 0)
                <div class="detail-card">
                    <h5 class="mb-3">
                        <i class="fas fa-list me-2"></i>Imported Records
                    </h5>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Source ID</th>
                                    <th>MedCura ID</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($records as $record)
                                    <tr>
                                        <td><code>{{ $record->source_id ?? 'N/A' }}</code></td>
                                        <td><code>{{ $record->medcura_id ?? '—' }}</code></td>
                                        <td>
                                            <span class="badge {{ $record->getStatusBadgeClass() }}">
                                                {{ ucfirst($record->status) }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3">
                        {{ $records->links() }}
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection