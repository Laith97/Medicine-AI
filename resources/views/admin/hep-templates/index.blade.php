@extends('layouts.admin')

@section('title', 'HEP Program Templates')

@section('content')
<div class="container-fluid px-4 py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4" style="background:linear-gradient(135deg,#1e293b 0%,#334155 100%);border-radius:16px;padding:1.4rem 1.6rem;box-shadow:0 8px 24px rgba(15,23,42,0.12)">
        <div class="d-flex align-items-center gap-3">
            <div style="width:44px;height:44px;border-radius:12px;background:rgba(255,255,255,0.14);display:flex;align-items:center;justify-content:center"><i class="fas fa-clipboard-list" style="color:#fff;font-size:1.1rem"></i></div>
            <div>
                <h1 style="font-size:1.35rem;font-weight:800;color:#fff;letter-spacing:-0.02em;margin:0">HEP Program Templates</h1>
                <p style="font-size:0.78rem;color:rgba(255,255,255,0.75);margin:2px 0 0">Pre-built rehab programs · {{ $templates->total() }} total · {{ $templates->getCollection()->where('is_active', true)->count() }} active</p>
            </div>
        </div>
        <a href="{{ route('admin.hep-templates.create') }}" class="btn btn-sm text-white" style="background:rgba(255,255,255,0.14);border:1px solid rgba(255,255,255,0.18);color:#fff;border-radius:10px;font-weight:700"><i class="fas fa-plus me-1"></i> Create Template</a>
    </div>

    <!-- Statistics Cards -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-xl-3"><div class="card border-0 shadow-sm" style="border-radius:14px"><div class="card-body p-3 text-center"><div style="width:42px;height:42px;border-radius:12px;background:#eff6ff;border:1px solid #dbeafe;color:#2563eb;display:flex;align-items:center;justify-content:center;margin:0 auto 8px"><i class="fas fa-layer-group"></i></div><div style="font-size:1.5rem;font-weight:800;color:#0f172a">{{ $templates->total() }}</div><div style="font-size:.68rem;color:#64748b;font-weight:700;letter-spacing:.05em;text-transform:uppercase">Total Templates</div></div></div></div>
        <div class="col-6 col-xl-3"><div class="card border-0 shadow-sm" style="border-radius:14px"><div class="card-body p-3 text-center"><div style="width:42px;height:42px;border-radius:12px;background:#ecfdf5;border:1px solid #a7f3d0;color:#059669;display:flex;align-items:center;justify-content:center;margin:0 auto 8px"><i class="fas fa-check-circle"></i></div><div style="font-size:1.5rem;font-weight:800;color:#0f172a">{{ $templates->getCollection()->where('is_active', true)->count() }}</div><div style="font-size:.68rem;color:#64748b;font-weight:700;letter-spacing:.05em;text-transform:uppercase">Active Templates</div></div></div></div>
        <div class="col-6 col-xl-3"><div class="card border-0 shadow-sm" style="border-radius:14px"><div class="card-body p-3 text-center"><div style="width:42px;height:42px;border-radius:12px;background:#f5f3ff;border:1px solid #ddd6fe;color:#7c3aed;display:flex;align-items:center;justify-content:center;margin:0 auto 8px"><i class="fas fa-tags"></i></div><div style="font-size:1.5rem;font-weight:800;color:#0f172a">{{ count($categories) }}</div><div style="font-size:.68rem;color:#64748b;font-weight:700;letter-spacing:.05em;text-transform:uppercase">Categories</div></div></div></div>
        <div class="col-6 col-xl-3"><div class="card border-0 shadow-sm" style="border-radius:14px"><div class="card-body p-3 text-center"><div style="width:42px;height:42px;border-radius:12px;background:#fffbeb;border:1px solid #fde68a;color:#d97706;display:flex;align-items:center;justify-content:center;margin:0 auto 8px"><i class="fas fa-chart-line"></i></div><div style="font-size:1.5rem;font-weight:800;color:#0f172a">{{ $templates->getCollection()->sum(fn($t) => $t->getUsageCount()) }}</div><div style="font-size:.68rem;color:#64748b;font-weight:700;letter-spacing:.05em;text-transform:uppercase">Programs Created</div></div></div></div>
    </div>

    <!-- Filters -->
    <div class="card border-0 shadow-sm mb-4" style="border-radius:14px;overflow:hidden">
        <div class="card-body p-3" style="background:#fff">
            <form method="GET" action="{{ route('admin.hep-templates.index') }}">
                <div class="row g-2 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label fw-bold" style="font-size:.72rem;letter-spacing:.04em;text-transform:uppercase;color:#64748b">Search</label>
                        <div class="position-relative">
                            <i class="fas fa-search" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:.78rem"></i>
                            <input type="text" class="form-control" name="search" value="{{ request('search') }}" placeholder="Search templates..." style="border-radius:10px;padding-left:34px;border:1px solid #e2e8f0;height:38px;font-size:.88rem">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-bold" style="font-size:.72rem;letter-spacing:.04em;text-transform:uppercase;color:#64748b">Category</label>
                        <select class="form-select" name="category" style="border-radius:10px;border:1px solid #e2e8f0;height:38px;font-size:.88rem">
                            <option value="">All Categories</option>
                            @foreach($categories as $category)
                                <option value="{{ $category }}" {{ request('category') === $category ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $category)) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-bold" style="font-size:.72rem;letter-spacing:.04em;text-transform:uppercase;color:#64748b">Diagnosis Type</label>
                        <select class="form-select" name="diagnosis_type" style="border-radius:10px;border:1px solid #e2e8f0;height:38px;font-size:.88rem">
                            <option value="">All Types</option>
                            @foreach($diagnosisTypes as $type)
                                <option value="{{ $type }}" {{ request('diagnosis_type') === $type ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $type)) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-bold" style="font-size:.72rem;letter-spacing:.04em;text-transform:uppercase;color:#64748b">Status</label>
                        <select class="form-select" name="status" style="border-radius:10px;border:1px solid #e2e8f0;height:38px;font-size:.88rem">
                            <option value="">All Status</option>
                            <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex gap-2">
                        <button type="submit" class="btn flex-grow-1 text-white" style="border-radius:10px;background:#0f172a;border:none;font-weight:700;height:38px"><i class="fas fa-filter me-1"></i> Filter</button>
                        <a href="{{ route('admin.hep-templates.index') }}" class="btn btn-light border" style="border-radius:10px;font-weight:600;height:38px">Reset</a>
                    </div>
                </div>
            </form>
        </div>
        <div class="table-responsive" style="border-top:1px solid #f1f5f9">
            <table class="table mb-0" style="font-size:.84rem">
                <thead>
                    <tr style="background:#f8fafc">
                        <th style="padding:12px 16px;border:none;border-bottom:1px solid #e2e8f0;font-size:.68rem;font-weight:800;color:#475569;text-transform:uppercase;letter-spacing:.05em">Template</th>
                        <th style="padding:12px 16px;border:none;border-bottom:1px solid #e2e8f0;font-size:.68rem;font-weight:800;color:#475569;text-transform:uppercase;letter-spacing:.05em">Category</th>
                        <th style="padding:12px 16px;border:none;border-bottom:1px solid #e2e8f0;font-size:.68rem;font-weight:800;color:#475569;text-transform:uppercase;letter-spacing:.05em">Diagnosis</th>
                        <th style="padding:12px 16px;border:none;border-bottom:1px solid #e2e8f0;font-size:.68rem;font-weight:800;color:#475569;text-transform:uppercase;letter-spacing:.05em">Duration</th>
                        <th style="padding:12px 16px;border:none;border-bottom:1px solid #e2e8f0;font-size:.68rem;font-weight:800;color:#475569;text-transform:uppercase;letter-spacing:.05em">Exercises</th>
                        <th style="padding:12px 16px;border:none;border-bottom:1px solid #e2e8f0;font-size:.68rem;font-weight:800;color:#475569;text-transform:uppercase;letter-spacing:.05em">Usage</th>
                        <th style="padding:12px 16px;border:none;border-bottom:1px solid #e2e8f0;font-size:.68rem;font-weight:800;color:#475569;text-transform:uppercase;letter-spacing:.05em">Status</th>
                        <th style="padding:12px 16px;border:none;border-bottom:1px solid #e2e8f0;font-size:.68rem;font-weight:800;color:#475569;text-transform:uppercase;letter-spacing:.05em;text-align:right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($templates as $template)
                        <tr>
                            <td style="padding:14px 16px;border-bottom:1px solid #f1f5f9">
                                <div class="d-flex gap-3 align-items-start">
                                    <div style="width:40px;height:40px;border-radius:10px;background:#f1f5f9;border:1px solid #e2e8f0;display:flex;align-items:center;justify-content:center;color:#64748b;flex-shrink:0"><i class="fas fa-clipboard-list"></i></div>
                                    <div class="min-w-0">
                                        <div style="font-weight:700;color:#0f172a">{{ $template->name }}</div>
                                        <small style="font-size:.76rem;color:#64748b">{{ Str::limit($template->description, 50) }}</small>
                                        <div style="font-size:.72rem;color:#94a3b8;margin-top:2px"><i class="fas fa-user me-1"></i>{{ $template->creator->name ?? 'System' }}</div>
                                    </div>
                                </div>
                            </td>
                            <td style="padding:14px 16px;border-bottom:1px solid #f1f5f9"><span class="badge" style="border-radius:20px;font-size:.68rem;font-weight:700;padding:4px 10px;background:#eff6ff;color:#1d4ed8;border:1px solid #dbeafe">{{ ucfirst(str_replace('_', ' ', $template->category)) }}</span></td>
                            <td style="padding:14px 16px;border-bottom:1px solid #f1f5f9">
                                @if($template->diagnosis_type)
                                    <span class="badge" style="border-radius:20px;font-size:.68rem;font-weight:700;padding:4px 10px;background:#f5f3ff;color:#6d28d9;border:1px solid #ddd6fe">{{ ucfirst(str_replace('_', ' ', $template->diagnosis_type)) }}</span>
                                @else
                                    <span style="font-size:.78rem;color:#94a3b8">General</span>
                                @endif
                            </td>
                            <td style="padding:14px 16px;border-bottom:1px solid #f1f5f9"><span style="font-size:.82rem;color:#334155">{{ $template->duration_weeks }} weeks</span><br><small style="font-size:.72rem;color:#64748b">{{ $template->frequency_per_week }}×/week</small></td>
                            <td style="padding:14px 16px;border-bottom:1px solid #f1f5f9"><span class="badge" style="background:#f1f5f9;color:#475569;border:1px solid #e2e8f0;border-radius:8px;font-size:.72rem">{{ $template->templateExercises()->count() }} exercises</span></td>
                            <td style="padding:14px 16px;border-bottom:1px solid #f1f5f9"><span style="font-size:.78rem;color:#64748b"><strong style="color:#0f172a">{{ $template->getUsageCount() }}</strong> programs</span></td>
                            <td style="padding:14px 16px;border-bottom:1px solid #f1f5f9">
                                @if($template->is_active)
                                    <span class="badge" style="border-radius:20px;font-size:.68rem;font-weight:700;padding:4px 10px;background:#ecfdf5;color:#065f46;border:1px solid #a7f3d0"><i class="fas fa-circle me-1" style="font-size:.5rem"></i> Active</span>
                                @else
                                    <span class="badge" style="border-radius:20px;font-size:.68rem;font-weight:700;padding:4px 10px;background:#f1f5f9;color:#64748b;border:1px solid #e2e8f0"><i class="fas fa-circle me-1" style="font-size:.5rem"></i> Inactive</span>
                                @endif
                            </td>
                            <td style="padding:14px 16px;border-bottom:1px solid #f1f5f9;text-align:right">
                                <div class="d-inline-flex gap-1">
                                    <a href="{{ route('admin.hep-templates.show', $template) }}" class="btn btn-light border btn-sm d-inline-flex align-items-center justify-content-center" style="border-radius:10px;width:32px;height:32px" title="View"><i class="fas fa-eye" style="font-size:.76rem"></i></a>
                                    <a href="{{ route('admin.hep-templates.edit', $template) }}" class="btn btn-light border btn-sm d-inline-flex align-items-center justify-content-center" style="border-radius:10px;width:32px;height:32px" title="Edit"><i class="fas fa-pen" style="font-size:.76rem"></i></a>
                                    <form method="POST" action="{{ route('admin.hep-templates.toggle-active', $template) }}" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-light border btn-sm d-inline-flex align-items-center justify-content-center" style="border-radius:10px;width:32px;height:32px;color:{{ $template->is_active ? '#d97706':'#059669' }}" title="{{ $template->is_active ? 'Deactivate' : 'Activate' }}"><i class="fas {{ $template->is_active ? 'fa-pause' : 'fa-play' }}" style="font-size:.76rem"></i></button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.hep-templates.duplicate', $template) }}" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-light border btn-sm d-inline-flex align-items-center justify-content-center" style="border-radius:10px;width:32px;height:32px;color:#2563eb" title="Duplicate"><i class="fas fa-copy" style="font-size:.76rem"></i></button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.hep-templates.destroy', $template) }}" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this template?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-light border btn-sm d-inline-flex align-items-center justify-content-center" style="border-radius:10px;width:32px;height:32px;color:#dc2626" title="Delete"><i class="fas fa-trash" style="font-size:.76rem"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center py-5"><div class="d-flex flex-column align-items-center gap-2"><div style="width:48px;height:48px;border-radius:12px;background:#f8fafc;border:1px dashed #e2e8f0;display:flex;align-items:center;justify-content:center;color:#94a3b8"><i class="fas fa-clipboard-list"></i></div><span class="text-muted" style="font-size:.88rem">No templates found</span><p class="text-muted small mb-1">Get started by creating your first HEP program template.</p><a href="{{ route('admin.hep-templates.create') }}" class="btn btn-sm text-white mt-1" style="background:#0f172a;border-radius:10px;font-weight:700"><i class="fas fa-plus me-1"></i> Create First Template</a></div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($templates->hasPages())
        <div class="d-flex justify-content-center mt-3">
            {{ $templates->appends(request()->query())->links() }}
        </div>
    @endif
</div>
@endsection
