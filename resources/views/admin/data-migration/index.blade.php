@extends('layouts.admin')

@section('title', 'Data Migration')

@section('content')
<div class="container-fluid px-0">
    {{-- Header compatible --}}
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4" style="background:linear-gradient(135deg,#1e293b 0%,#334155 100%);border-radius:16px;padding:1.4rem 1.6rem;box-shadow:0 8px 24px rgba(15,23,42,0.12)">
        <div class="d-flex align-items-center gap-3">
            <div style="width:44px;height:44px;border-radius:12px;background:rgba(255,255,255,0.14);display:flex;align-items:center;justify-content:center"><i class="fas fa-database" style="color:#fff;font-size:1.1rem"></i></div>
            <div>
                <h1 style="font-size:1.35rem;font-weight:800;color:#fff;letter-spacing:-0.02em;margin:0">Data Migration</h1>
                <p style="font-size:0.78rem;color:rgba(255,255,255,0.75);margin:2px 0 0">Import data from external systems into MedCura</p>
            </div>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.data-migration.create') }}" class="btn btn-sm" style="background:#fff;color:#0f172a;border-radius:10px;font-weight:800;border:1px solid #e2e8f0"><i class="fas fa-plus me-1"></i> New Migration</a>
        </div>
    </div>

    {{-- Instructions - card like dashboard Quick Actions --}}
    <div class="card border-0 shadow-sm mb-4" style="border-radius:14px;overflow:hidden">
        <div class="card-header bg-white d-flex align-items-center gap-2" style="padding:1rem 1.25rem;border-bottom:1px solid #eef2f7">
            <span style="width:32px;height:32px;border-radius:10px;background:#eff6ff;border:1px solid #dbeafe;color:#2563eb;display:flex;align-items:center;justify-content:center"><i class="fas fa-book-open"></i></span>
            <h5 class="mb-0" style="font-weight:800;color:#0f172a;font-size:0.95rem">How to Use Data Migration</h5>
        </div>
        <div class="card-body p-4">
            <div class="row g-4">
                <div class="col-md-6">
                    <div class="d-flex gap-3 mb-3"><span style="width:28px;height:28px;border-radius:8px;background:#2563eb;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:0.78rem">1</span><div><h6 style="font-weight:700;color:#0f172a;font-size:0.84rem;margin:0">Choose What to Import</h6><p class="text-muted small mb-0">Patients, Doctors, Appointments, etc.</p></div></div>
                    <div class="d-flex gap-3 mb-3"><span style="width:28px;height:28px;border-radius:8px;background:#2563eb;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:0.78rem">2</span><div><h6 style="font-weight:700;color:#0f172a;font-size:0.84rem;margin:0">Download Template</h6><p class="text-muted small mb-0">CSV shows required fields.</p></div></div>
                    <div class="d-flex gap-3"><span style="width:28px;height:28px;border-radius:8px;background:#2563eb;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:0.78rem">3</span><div><h6 style="font-weight:700;color:#0f172a;font-size:0.84rem;margin:0">Prepare Your Data</h6><p class="text-muted small mb-0">Dates <code style="background:#f8fafc;padding:0.1rem 0.3rem;border-radius:4px">YYYY-MM-DD</code>, phones digits, valid emails.</p></div></div>
                </div>
                <div class="col-md-6">
                    <div class="d-flex gap-3 mb-3"><span style="width:28px;height:28px;border-radius:8px;background:#10b981;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:0.78rem">4</span><div><h6 style="font-weight:700;color:#0f172a;font-size:0.84rem;margin:0">Upload & Preview</h6><p class="text-muted small mb-0">Verify data looks correct.</p></div></div>
                    <div class="d-flex gap-3 mb-3"><span style="width:28px;height:28px;border-radius:8px;background:#10b981;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:0.78rem">5</span><div><h6 style="font-weight:700;color:#0f172a;font-size:0.84rem;margin:0">Map Fields</h6><p class="text-muted small mb-0">Auto-detected mapping.</p></div></div>
                    <div class="d-flex gap-3"><span style="width:28px;height:28px;border-radius:8px;background:#10b981;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:0.78rem">6</span><div><h6 style="font-weight:700;color:#0f172a;font-size:0.84rem;margin:0">Start Import</h6><p class="text-muted small mb-0">Watch progress, failed logged.</p></div></div>
                </div>
            </div>
            <div class="alert d-flex align-items-center gap-2 mt-3 mb-0" style="background:#eff6ff;border:1px solid #dbeafe;border-radius:12px;color:#1e40af;font-size:0.84rem"><i class="fas fa-info-circle"></i> <strong>Important:</strong> Import patients and doctors FIRST before appointments/clinical data.</div>
        </div>
    </div>

    {{-- Stats Cards compatible --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-xl-3"><div class="card border-0 shadow-sm" style="border-radius:14px"><div class="card-body p-3 d-flex align-items-center gap-3"><div style="width:42px;height:42px;border-radius:12px;background:#f8fafc;border:1px solid #e2e8f0;color:#0f172a;display:flex;align-items:center;justify-content:center"><i class="fas fa-exchange-alt"></i></div><div><div style="font-weight:800;color:#0f172a">{{ $stats['total_migrations'] }}</div><small class="text-muted">Total Migrations</small></div></div></div></div>
        <div class="col-6 col-xl-3"><div class="card border-0 shadow-sm" style="border-radius:14px"><div class="card-body p-3 d-flex align-items-center gap-3"><div style="width:42px;height:42px;border-radius:12px;background:#ecfdf5;border:1px solid #a7f3d0;color:#059669;display:flex;align-items:center;justify-content:center"><i class="fas fa-check"></i></div><div><div style="font-weight:800;color:#0f172a">{{ $stats['completed'] }}</div><small class="text-muted">Completed</small></div></div></div></div>
        <div class="col-6 col-xl-3"><div class="card border-0 shadow-sm" style="border-radius:14px"><div class="card-body p-3 d-flex align-items-center gap-3"><div style="width:42px;height:42px;border-radius:12px;background:#fef2f2;border:1px solid #fecaca;color:#dc2626;display:flex;align-items:center;justify-content:center"><i class="fas fa-times"></i></div><div><div style="font-weight:800;color:#0f172a">{{ $stats['failed'] }}</div><small class="text-muted">Failed</small></div></div></div></div>
        <div class="col-6 col-xl-3"><div class="card border-0 shadow-sm" style="border-radius:14px"><div class="card-body p-3 d-flex align-items-center gap-3"><div style="width:42px;height:42px;border-radius:12px;background:#eff6ff;border:1px solid #dbeafe;color:#2563eb;display:flex;align-items:center;justify-content:center"><i class="fas fa-spinner"></i></div><div><div style="font-weight:800;color:#0f172a">{{ $stats['in_progress'] }}</div><small class="text-muted">In Progress</small></div></div></div></div>
    </div>

    <div class="row g-4">
        {{-- Templates --}}
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm" style="border-radius:14px;overflow:hidden">
                <div class="card-header bg-white" style="padding:1rem 1.25rem;border-bottom:1px solid #eef2f7"><h5 class="mb-0 d-flex align-items-center gap-2" style="font-weight:800;color:#0f172a;font-size:0.95rem"><i class="fas fa-file-csv" style="color:#64748b"></i> Import Templates <span class="badge bg-light border text-muted" style="border-radius:20px">10</span></h5></div>
                <div class="card-body p-3">
                    <p class="text-muted small">Click to download CSV templates</p>
                    <div class="d-flex flex-column gap-2">
                        @foreach(['department'=>'building','specialty'=>'stethoscope','doctor'=>'user-md','patient'=>'user','appointment'=>'calendar-check','diagnosis'=>'notes-medical','prescription'=>'pills','treatment'=>'heartbeat','allergy'=>'exclamation-triangle','insurance'=>'shield-alt'] as $type => $icon)
                            <a href="{{ route('admin.data-migration.download-template', $type) }}" class="d-flex align-items-center gap-3 p-2 text-decoration-none" style="background:#f8fafc;border:1px solid #eef2f7;border-radius:12px;color:#0f172a;font-weight:600;font-size:0.82rem"><span style="width:32px;height:32px;border-radius:10px;background:#fff;border:1px solid #e2e8f0;display:flex;align-items:center;justify-content:center;color:#475569"><i class="fas fa-{{ $icon }}"></i></span> {{ ucfirst($type) }} <i class="fas fa-download ms-auto" style="color:#94a3b8;font-size:0.75rem"></i></a>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm mt-4" style="border-radius:14px;overflow:hidden">
                <div class="card-header bg-white" style="padding:1rem 1.25rem;border-bottom:1px solid #eef2f7"><h5 class="mb-0 d-flex align-items-center gap-2" style="font-weight:800;color:#0f172a;font-size:0.95rem"><i class="fas fa-layer-group" style="color:#64748b"></i> Import Order</h5></div>
                <div class="card-body p-3">
                    <ol class="small mb-0" style="color:#475569;padding-left:1.2rem">
                        <li class="mb-1"><strong>Departments</strong> <span class="text-muted">- Office structure</span></li>
                        <li class="mb-1"><strong>Specialties</strong> <span class="text-muted">- Medical specialties</span></li>
                        <li class="mb-1"><strong>Doctors</strong> <span class="text-muted">- Links to specialties</span></li>
                        <li class="mb-1"><strong>Patients</strong> <span class="text-muted">- No dependencies</span></li>
                        <li class="mb-1"><strong>Insurance</strong> <span class="text-muted">- Links to patients</span></li>
                        <li class="mb-1"><strong>Appointments</strong> <span class="text-muted">- Patients & doctors</span></li>
                        <li class="mb-1"><strong>Diagnoses</strong> <span class="text-muted">- Patients & doctors</span></li>
                        <li class="mb-1"><strong>Prescriptions</strong> <span class="text-muted">- Patients & doctors</span></li>
                        <li class="mb-1"><strong>Treatments</strong> <span class="text-muted">- Patients & doctors</span></li>
                        <li><strong>Allergies</strong> <span class="text-muted">- Links to patients</span></li>
                    </ol>
                    <div class="alert mt-3 mb-0 d-flex gap-2" style="background:#fffbeb;border:1px solid #fde68a;border-radius:12px;color:#92400e;font-size:0.78rem"><i class="fas fa-exclamation-triangle mt-1"></i> <div><strong>Important:</strong> Import foundational data BEFORE clinical data.</div></div>
                </div>
            </div>
        </div>

        {{-- Migrations List --}}
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm" style="border-radius:14px;overflow:hidden">
                <div class="card-header bg-white d-flex justify-content-between align-items-center" style="padding:1rem 1.25rem;border-bottom:1px solid #eef2f7">
                    <h5 class="mb-0 d-flex align-items-center gap-2" style="font-weight:800;color:#0f172a;font-size:0.95rem"><i class="fas fa-list" style="color:#64748b"></i> Migration History <span class="badge bg-light border text-muted" style="border-radius:20px">{{ $migrations->total() }}</span></h5>
                    <a href="{{ route('admin.data-migration.create') }}" class="btn btn-sm" style="background:#0f172a;color:#fff;border-radius:10px;font-weight:700"><i class="fas fa-plus me-1"></i> New Migration</a>
                </div>
                <div class="card-body p-0">
                    @if($migrations->isEmpty())
                        <div class="text-center py-5"><div style="width:56px;height:56px;border-radius:16px;background:#f8fafc;border:1px dashed #e2e8f0;display:flex;align-items:center;justify-content:center;margin:0 auto 12px;color:#94a3b8"><i class="fas fa-inbox"></i></div><p class="text-muted small mb-0">No migrations yet. Click "New Migration" to start.</p></div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover mb-0" style="font-size:0.84rem">
                                <thead style="background:#f8fafc"><tr style="font-size:0.75rem;letter-spacing:0.04em;color:#64748b;text-transform:uppercase"><th style="padding:0.9rem 1.25rem;border:none">Name</th><th style="padding:0.9rem 1.25rem;border:none">Type</th><th style="padding:0.9rem 1.25rem;border:none">Progress</th><th style="padding:0.9rem 1.25rem;border:none">Status</th><th style="padding:0.9rem 1.25rem;border:none">Actions</th></tr></thead>
                                <tbody>
                                    @foreach($migrations as $migration)
                                        <tr style="border-bottom:1px solid #f8fafc">
                                            <td style="padding:0.9rem 1.25rem"><div style="font-weight:700;color:#0f172a;font-size:0.84rem">{{ $migration->name }}</div>@if($migration->description)<div style="font-size:0.76rem;color:#64748b">{{ Str::limit($migration->description, 40) }}</div>@endif</td>
                                            <td style="padding:0.9rem 1.25rem"><span class="badge bg-light border text-muted" style="border-radius:20px;font-weight:600">{{ ucfirst($migration->entity_type) }}</span><br><small style="color:#94a3b8;font-size:0.72rem">{{ $migration->getSourceTypeLabel() }}</small></td>
                                            <td style="padding:0.9rem 1.25rem;min-width:140px">@if($migration->total_records > 0)<div class="d-flex justify-content-between mb-1"><small style="font-weight:700;color:#0f172a">{{ $migration->processed_records }}/{{ $migration->total_records }}</small><small style="color:#64748b">{{ $migration->getProgressPercentage() }}%</small></div><div class="progress" style="height:6px;background:#f1f5f9;border-radius:20px"><div class="progress-bar" style="width:{{ $migration->getProgressPercentage() }}%;background:linear-gradient(90deg,#0f172a,#334155);border-radius:20px"></div></div>@else<span class="badge bg-light border text-muted" style="border-radius:20px">Waiting</span>@endif</td>
                                            <td style="padding:0.9rem 1.25rem"><span class="badge {{ $migration->getStatusBadgeClass() }}" style="border-radius:20px">{{ ucfirst(str_replace('_',' ',$migration->status)) }}</span></td>
                                            <td style="padding:0.9rem 1.25rem"><div class="btn-group btn-group-sm"><a href="{{ route('admin.data-migration.show', $migration) }}" class="btn btn-light border" style="border-radius:8px"><i class="fas fa-eye" style="font-size:0.75rem"></i></a>@if($migration->status==='failed'||$migration->status==='completed')<a href="{{ route('admin.data-migration.export-errors', $migration) }}" class="btn btn-light border" style="border-radius:8px"><i class="fas fa-download" style="font-size:0.75rem"></i></a>@endif @if($migration->status!=='completed' && $migration->status!=='cancelled')<form method="POST" action="{{ route('admin.data-migration.cancel', $migration) }}" class="d-inline">@csrf<button type="submit" class="btn btn-light border" style="border-radius:8px" onclick="return confirm('Cancel?')"><i class="fas fa-stop" style="font-size:0.75rem"></i></button></form>@endif<form method="POST" action="{{ route('admin.data-migration.destroy', $migration) }}" class="d-inline">@csrf @method('DELETE')<button type="submit" class="btn btn-light border" style="border-radius:8px" onclick="return confirm('Delete?')"><i class="fas fa-trash" style="font-size:0.75rem"></i></button></form></div></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="p-3 d-flex justify-content-center border-top" style="background:#f8fafc">{{ $migrations->links() }}</div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
