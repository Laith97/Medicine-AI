@extends('layouts.admin')
@section('title','Admin Dashboard')
@section('content')
<div class="container-fluid px-4 py-4">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4" style="background:linear-gradient(135deg,#1e293b 0%,#334155 100%);border-radius:16px;padding:1.4rem 1.6rem;box-shadow:0 8px 24px rgba(15,23,42,0.12)">
        <div class="d-flex align-items-center gap-3">
            <div style="width:44px;height:44px;border-radius:12px;background:rgba(255,255,255,0.14);display:flex;align-items:center;justify-content:center"><i class="fas fa-chart-pie" style="color:#fff;font-size:1.1rem"></i></div>
            <div>
                <h1 style="font-size:1.35rem;font-weight:800;color:#fff;letter-spacing:-0.02em;margin:0">Admin Dashboard</h1>
                <p style="font-size:0.78rem;color:rgba(255,255,255,0.75);margin:2px 0 0">Full control · {{ $stats['total_users'] }} users · {{ $stats['recent_users'] }} new this week</p>
            </div>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.users.create') }}" class="btn btn-sm text-white" style="background:rgba(255,255,255,0.14);border:1px solid rgba(255,255,255,0.18);color:#fff;border-radius:10px;font-weight:700"><i class="fas fa-plus me-1"></i> New User</a>
            <a href="{{ route('admin.users.index') }}" class="btn btn-sm" style="background:rgba(255,255,255,0.12);border:1px solid rgba(255,255,255,0.18);color:#fff;border-radius:10px;font-weight:700">Manage Users</a>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-6 col-xl-3"><div class="card border-0 shadow-sm" style="border-radius:14px"><div class="card-body p-3 d-flex align-items-center gap-3"><div style="width:42px;height:42px;border-radius:12px;background:#eff6ff;border:1px solid #dbeafe;color:#2563eb;display:flex;align-items:center;justify-content:center"><i class="fas fa-users"></i></div><div><div style="font-weight:800;color:#0f172a">{{ $stats['total_users'] }}</div><small class="text-muted">Total Users</small></div></div></div></div>
        <div class="col-6 col-xl-3"><div class="card border-0 shadow-sm" style="border-radius:14px"><div class="card-body p-3 d-flex align-items-center gap-3"><div style="width:42px;height:42px;border-radius:12px;background:#fef2f2;border:1px solid #fecaca;color:#dc2626;display:flex;align-items:center;justify-content:center"><i class="fas fa-shield-alt"></i></div><div><div style="font-weight:800;color:#0f172a">{{ $stats['admin_users'] }}</div><small class="text-muted">Admins</small></div></div></div></div>
        <div class="col-6 col-xl-3"><div class="card border-0 shadow-sm" style="border-radius:14px"><div class="card-body p-3 d-flex align-items-center gap-3"><div style="width:42px;height:42px;border-radius:12px;background:#f5f3ff;border:1px solid #ddd6fe;color:#7c3aed;display:flex;align-items:center;justify-content:center"><i class="fas fa-user-injured"></i></div><div><div style="font-weight:800;color:#0f172a">{{ $stats['regular_users'] }}</div><small class="text-muted">Patients/Doctors</small></div></div></div></div>
        <div class="col-6 col-xl-3"><div class="card border-0 shadow-sm" style="border-radius:14px"><div class="card-body p-3 d-flex align-items-center gap-3"><div style="width:42px;height:42px;border-radius:12px;background:#ecfdf5;border:1px solid #a7f3d0;color:#059669;display:flex;align-items:center;justify-content:center"><i class="fas fa-clock"></i></div><div><div style="font-weight:800;color:#0f172a">{{ $stats['recent_users'] }}</div><small class="text-muted">New This Week</small></div></div></div></div>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm" style="border-radius:14px;overflow:hidden">
                <div class="card-header bg-white d-flex justify-content-between align-items-center" style="padding:1rem 1.25rem;border-bottom:1px solid #eef2f7">
                    <h5 class="mb-0 d-flex align-items-center gap-2" style="font-weight:800;color:#0f172a;font-size:0.95rem"><i class="fas fa-users" style="color:#64748b"></i> Recent Users <span class="badge bg-light border text-muted" style="border-radius:20px">{{ $recentUsers->count() }}</span></h5>
                    <a href="{{ route('admin.users.index') }}" class="btn btn-light border btn-sm" style="border-radius:10px;font-weight:600">View All</a>
                </div>
                <div class="list-group list-group-flush">
                    @forelse($recentUsers as $user)
                        <div class="list-group-item border-0 px-3 py-3 d-flex justify-content-between align-items-center" style="border-bottom:1px solid #f8fafc!important">
                            <div class="d-flex align-items-center gap-3">
                                <div style="width:40px;height:40px;border-radius:12px;background:linear-gradient(135deg,#667eea,#764ba2);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:800">{{ strtoupper(substr($user->name,0,1)) }}</div>
                                <div>
                                    <div style="font-weight:700;color:#0f172a">{{ $user->name }}</div>
                                    <div style="font-size:.78rem;color:#64748b">{{ $user->email }} · <span class="badge bg-light border text-muted" style="font-size:.68rem">{{ ucfirst($user->role) }}</span></div>
                                </div>
                            </div>
                            <div class="text-end"><div style="font-size:.76rem;color:#64748b">{{ $user->created_at->diffForHumans() }}</div><a href="{{ route('admin.users.show',$user) }}" class="btn btn-sm btn-light border mt-1" style="border-radius:8px;font-size:.72rem">View</a></div>
                        </div>
                    @empty
                        <div class="text-center py-5"><div style="width:56px;height:56px;border-radius:16px;background:#f8fafc;border:1px dashed #e2e8f0;display:flex;align-items:center;justify-content:center;margin:0 auto 12px;color:#94a3b8"><i class="fas fa-users"></i></div><p class="text-muted small mb-0">No users yet</p></div>
                    @endforelse
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm" style="border-radius:14px;overflow:hidden">
                <div class="card-header bg-white" style="padding:1rem 1.25rem;border-bottom:1px solid #eef2f7"><h5 class="mb-0" style="font-weight:800;color:#0f172a;font-size:0.95rem">Quick Actions</h5></div>
                <div class="card-body p-3 d-flex flex-column gap-2">
                    <a href="{{ route('admin.users.index') }}" class="d-flex align-items-center gap-3 p-3 text-decoration-none" style="background:#eff6ff;border:1px solid #dbeafe;border-radius:12px;color:#1e40af;font-weight:700"><span style="width:36px;height:36px;border-radius:10px;background:#2563eb;color:#fff;display:flex;align-items:center;justify-content:center"><i class="fas fa-users"></i></span> Manage Users <i class="fas fa-chevron-right ms-auto" style="font-size:.7rem;opacity:.6"></i></a>
                    <a href="{{ route('admin.users.create') }}" class="d-flex align-items-center gap-3 p-3 text-decoration-none" style="background:#ecfdf5;border:1px solid #a7f3d0;border-radius:12px;color:#065f46;font-weight:700"><span style="width:36px;height:36px;border-radius:10px;background:#10b981;color:#fff;display:flex;align-items:center;justify-content:center"><i class="fas fa-user-plus"></i></span> Create User <i class="fas fa-chevron-right ms-auto" style="font-size:.7rem;opacity:.6"></i></a>
                    <a href="{{ route('admin.appointments.index') }}" class="d-flex align-items-center gap-3 p-3 text-decoration-none" style="background:#f5f3ff;border:1px solid #ddd6fe;border-radius:12px;color:#5b21b6;font-weight:700"><span style="width:36px;height:36px;border-radius:10px;background:#7c3aed;color:#fff;display:flex;align-items:center;justify-content:center"><i class="fas fa-calendar-check"></i></span> Appointments <i class="fas fa-chevron-right ms-auto" style="font-size:.7rem;opacity:.6"></i></a>
                    <a href="{{ route('admin.usage-analytics') }}" class="d-flex align-items-center gap-3 p-3 text-decoration-none" style="background:#fffbeb;border:1px solid #fde68a;border-radius:12px;color:#92400e;font-weight:700"><span style="width:36px;height:36px;border-radius:10px;background:#f59e0b;color:#fff;display:flex;align-items:center;justify-content:center"><i class="fas fa-chart-line"></i></span> Usage Analytics <i class="fas fa-chevron-right ms-auto" style="font-size:.7rem;opacity:.6"></i></a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
