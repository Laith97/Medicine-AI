@extends('layouts.admin')

@section('title', 'Security Dashboard')

@section('content')
<div class="container-fluid px-0">
    {{-- Header compatible --}}
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4" style="background:linear-gradient(135deg,#1e293b 0%,#334155 100%);border-radius:16px;padding:1.4rem 1.6rem;box-shadow:0 8px 24px rgba(15,23,42,0.12)">
        <div class="d-flex align-items-center gap-3">
            <div style="width:44px;height:44px;border-radius:12px;background:rgba(255,255,255,0.14);display:flex;align-items:center;justify-content:center"><i class="fas fa-shield-alt" style="color:#fff;font-size:1.1rem"></i></div>
            <div>
                <h1 style="font-size:1.35rem;font-weight:800;color:#fff;letter-spacing:-0.02em;margin:0">Security Dashboard</h1>
                <p style="font-size:0.78rem;color:rgba(255,255,255,0.75);margin:2px 0 0">Audit logs and security reports · HIPAA compliant</p>
            </div>
        </div>
        <a href="{{ route('security.export', request()->query()) }}" class="btn btn-sm" style="background:#fff;color:#0f172a;border-radius:10px;font-weight:800;border:1px solid #e2e8f0"><i class="fas fa-download me-1"></i> Export CSV</a>
    </div>

    <!-- Filter Section -->
    <div class="card border-0 shadow-sm mb-4" style="border-radius:14px;overflow:hidden">
        <div class="card-header bg-white" style="padding:1rem 1.25rem;border-bottom:1px solid #eef2f7">
            <h5 class="mb-0 d-flex align-items-center gap-2" style="font-weight:800;color:#0f172a;font-size:0.95rem"><i class="fas fa-filter" style="color:#64748b"></i> Filter Options</h5>
        </div>
        <div class="card-body p-3">
            <form method="GET" action="{{ route('security.dashboard') }}">
                <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label for="time_range" class="form-label" style="font-weight:700;color:#0f172a;font-size:0.84rem">Time Range</label>
                        <select name="time_range" id="time_range" class="form-select" style="border-radius:10px">
                            <option value="1_hour" {{ request('time_range') == '1_hour' ? 'selected' : '' }}>Last 1 Hour</option>
                            <option value="24_hours" {{ request('time_range', '24_hours') == '24_hours' ? 'selected' : '' }}>Last 24 Hours</option>
                            <option value="7_days" {{ request('time_range') == '7_days' ? 'selected' : '' }}>Last 7 Days</option>
                            <option value="30_days" {{ request('time_range') == '30_days' ? 'selected' : '' }}>Last 30 Days</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="action_type" class="form-label" style="font-weight:700;color:#0f172a;font-size:0.84rem">Action Type</label>
                        <select name="action_type" id="action_type" class="form-select" style="border-radius:10px">
                            <option value="all">All Actions</option>
                            @foreach($actionTypes as $type)
                                <option value="{{ $type }}" {{ request('action_type') == $type ? 'selected' : '' }}>{{ $type }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="user_id" class="form-label" style="font-weight:700;color:#0f172a;font-size:0.84rem">User ID</label>
                        <input type="text" name="user_id" id="user_id" class="form-control" style="border-radius:10px" value="{{ request('user_id') }}" placeholder="Enter User ID">
                    </div>
                    <div class="col-md-3 d-flex gap-2">
                        <button type="submit" class="btn flex-fill" style="background:#0f172a;color:#fff;border-radius:10px;font-weight:700"><i class="fas fa-search me-1"></i> Filter</button>
                        <a href="{{ route('security.dashboard') }}" class="btn btn-light border flex-fill" style="border-radius:10px;font-weight:600">Reset</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Security Reports Section -->
    <div class="row g-3 mb-4">
        <div class="col-12"><h5 style="font-weight:800;color:#0f172a;font-size:0.95rem" class="mb-0">Security Reports</h5></div>
        <div class="col-md-6 col-xl-4"><div class="card border-0 shadow-sm" style="border-radius:14px"><div class="card-body p-3 d-flex align-items-center gap-3"><div style="width:42px;height:42px;border-radius:12px;background:#fef2f2;border:1px solid #fecaca;color:#dc2626;display:flex;align-items:center;justify-content:center"><i class="fas fa-exclamation-triangle"></i></div><div><div style="font-weight:800;color:#0f172a">{{ $unauthorizedAccessReports->count() }}</div><small class="text-muted">Unauthorized Access</small><div style="font-size:0.70rem;color:#dc2626;font-weight:700">{{ $unauthorizedAccessReports->where('severity','high')->count() }} High</div></div></div></div></div>
        <div class="col-md-6 col-xl-4"><div class="card border-0 shadow-sm" style="border-radius:14px"><div class="card-body p-3 d-flex align-items-center gap-3"><div style="width:42px;height:42px;border-radius:12px;background:#fffbeb;border:1px solid #fde68a;color:#d97706;display:flex;align-items:center;justify-content:center"><i class="fas fa-user-secret"></i></div><div><div style="font-weight:800;color:#0f172a">{{ $frequentImpersonationReports->count() }}</div><small class="text-muted">Frequent Impersonation</small><div style="font-size:0.70rem;color:#d97706;font-weight:700">{{ $frequentImpersonationReports->where('severity','medium')->count() }} Medium</div></div></div></div></div>
        <div class="col-md-6 col-xl-4"><div class="card border-0 shadow-sm" style="border-radius:14px"><div class="card-body p-3 d-flex align-items-center gap-3"><div style="width:42px;height:42px;border-radius:12px;background:#eff6ff;border:1px solid #dbeafe;color:#2563eb;display:flex;align-items:center;justify-content:center"><i class="fas fa-random"></i></div><div><div style="font-weight:800;color:#0f172a">{{ $unusualAssignmentReports->count() }}</div><small class="text-muted">Unusual Assignments</small><div style="font-size:0.70rem;color:#dc2626;font-weight:700">{{ $unusualAssignmentReports->where('severity','high')->count() }} High</div></div></div></div></div>
    </div>

    <!-- Audit Logs Section -->
    <div class="card border-0 shadow-sm" style="border-radius:14px;overflow:hidden">
        <div class="card-header bg-white d-flex justify-content-between align-items-center" style="padding:1rem 1.25rem;border-bottom:1px solid #eef2f7">
            <h5 class="mb-0 d-flex align-items-center gap-2" style="font-weight:800;color:#0f172a;font-size:0.95rem"><i class="fas fa-history" style="color:#64748b"></i> Recent Audit Logs <span class="badge bg-light border text-muted" style="border-radius:20px">{{ $auditLogs->total() }}</span></h5>
            <span class="text-muted small">Showing {{ $auditLogs->count() }} of {{ $auditLogs->total() }}</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0" style="font-size:0.84rem">
                    <thead style="background:#f8fafc"><tr style="font-size:0.75rem;letter-spacing:0.04em;color:#64748b;text-transform:uppercase"><th style="padding:0.9rem 1.25rem;border:none">ID</th><th style="padding:0.9rem 1.25rem;border:none">Action</th><th style="padding:0.9rem 1.25rem;border:none">User</th><th style="padding:0.9rem 1.25rem;border:none">Doctor</th><th style="padding:0.9rem 1.25rem;border:none">Patient</th><th style="padding:0.9rem 1.25rem;border:none">Timestamp</th><th style="padding:0.9rem 1.25rem;border:none">IP</th><th style="padding:0.9rem 1.25rem;border:none">Actions</th></tr></thead>
                    <tbody>
                        @forelse($auditLogs as $log)
                            <tr style="border-bottom:1px solid #f8fafc">
                                <td style="padding:0.8rem 1.25rem;color:#0f172a;font-weight:600">{{ $log->id }}</td>
                                <td style="padding:0.8rem 1.25rem"><span class="badge" style="background:#f8fafc;border:1px solid #e2e8f0;color:#475569;border-radius:20px;font-weight:600">{{ $log->action }}</span></td>
                                <td style="padding:0.8rem 1.25rem">@if($log->user)<span style="font-weight:600;color:#0f172a">{{ $log->user->name }}</span><br><small style="color:#64748b">{{ $log->user->email }}</small>@else<span class="text-muted">N/A</span>@endif</td>
                                <td style="padding:0.8rem 1.25rem">@if($log->doctor){{ $log->doctor->name }}@else<span class="text-muted">N/A</span>@endif</td>
                                <td style="padding:0.8rem 1.25rem">@if($log->patient){{ $log->patient->name }}@else<span class="text-muted">N/A</span>@endif</td>
                                <td style="padding:0.8rem 1.25rem"><span style="color:#0f172a">{{ $log->created_at->format('Y-m-d H:i') }}</span><br><small style="color:#64748b">{{ $log->created_at->diffForHumans() }}</small></td>
                                <td style="padding:0.8rem 1.25rem"><code style="background:#f8fafc;padding:0.2rem 0.4rem;border-radius:6px;font-size:0.74rem">{{ $log->ip_address }}</code></td>
                                <td style="padding:0.8rem 1.25rem"><a href="{{ route('security.audit-logs.show', $log) }}" class="btn btn-sm btn-light border" style="border-radius:8px"><i class="fas fa-eye" style="font-size:0.75rem"></i> View</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center py-5"><div style="width:56px;height:56px;border-radius:16px;background:#f8fafc;border:1px dashed #e2e8f0;display:flex;align-items:center;justify-content:center;margin:0 auto 12px;color:#94a3b8"><i class="fas fa-shield-alt"></i></div><p class="text-muted small mb-0">No audit logs found</p><p class="text-muted small">Try adjusting filters</p></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-3 d-flex justify-content-center border-top" style="background:#f8fafc">{{ $auditLogs->appends(request()->query())->links() }}</div>
        </div>
    </div>
</div>
@endsection
