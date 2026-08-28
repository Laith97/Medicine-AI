@extends('master')

@section('title', 'Sub-User Details')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/doctor-design-system.css') }}">
<link rel="stylesheet" href="{{ asset('css/doctor-dashboard.css') }}">
<link rel="stylesheet" href="{{ asset('css/cases-overview.css') }}">
<style>
.dashboard-header{background:linear-gradient(135deg,#2c5aa0 0%,#1e3a8a 100%)!important;border-radius:12px!important;padding:2.5rem!important;margin-bottom:2rem!important;box-shadow:0 4px 15px rgba(44,90,160,0.15)!important;position:relative;overflow:hidden}
.dashboard-header::before{content:'';position:absolute;top:0;left:0;right:0;height:3px;background:linear-gradient(90deg,#10b981 0%,#059669 100%)}
.dashboard-header h2{color:#fff!important;font-weight:600!important;font-size:1.9rem!important;margin-bottom:0.4rem!important}
.dashboard-header p{color:rgba(255,255,255,0.9)!important;font-size:0.92rem!important;margin:0!important}
.table-card{background:#fff;border:1px solid #eef2f7;border-radius:12px;padding:1.3rem;box-shadow:0 1px 4px rgba(15,23,42,0.04);margin-bottom:1.25rem}
.section-head-modern{display:flex;align-items:center;gap:0.75rem;margin:-1.3rem -1.3rem 1.1rem -1.3rem;padding:1rem 1.3rem;background:#f8fafc;border-bottom:1px solid #e2e8f0;border-radius:12px 12px 0 0}
.section-head-modern .head-icon{width:38px;height:38px;border-radius:9px;display:flex;align-items:center;justify-content:center;font-size:0.95rem;flex-shrink:0;background:#1e293b!important;color:#fff!important;border:1px solid #1e293b!important}
.section-head-modern h5{color:#0f172a!important;font-weight:800!important;letter-spacing:-0.01em;margin:0;font-size:1rem}
.section-head-modern p{color:#475569!important;font-size:0.78rem;margin:2px 0 0;font-weight:500}
.info-row{display:flex;align-items:center;gap:0.75rem;padding:0.85rem 0;border-bottom:1px solid #f1f5f9}
.info-row:last-child{border-bottom:none}
.info-row-icon{width:32px;height:32px;border-radius:8px;display:flex;align-items:center;justify-content:center;background:#f8fafc;border:1px solid #eef2f7;color:#64748b;font-size:0.8rem;flex-shrink:0}
</style>
@endpush

@section('content')
<div class="container-fluid" style="background-color: var(--bg-secondary, #f8f9fa);">
    <div class="container py-4">
        <div class="dashboard-header">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <h2><i class="fas fa-user me-2"></i>{{ $subUser->name }}</h2>
                    <p><span class="badge" style="background:rgba(255,255,255,0.15);border:1px solid rgba(255,255,255,0.25);color:#fff;border-radius:99px;padding:0.3rem 0.7rem;font-size:0.72rem;font-weight:700">{{ ucfirst($subUser->sub_user_role) }}</span> <span class="ms-2"><i class="far fa-clock me-1"></i>Created {{ $subUser->created_at->diffForHumans() }}</span></p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('sub-users.edit', $subUser) }}" class="btn" style="background:#fff;color:#1e293b;border:1px solid #fff;border-radius:10px;padding:0.5rem 1rem;font-weight:700;font-size:0.83rem"><i class="fas fa-pen me-2"></i>Edit</a>
                    <a href="{{ route('sub-users.index') }}" class="btn" style="background:rgba(255,255,255,0.15);border:1px solid rgba(255,255,255,0.32);color:#fff;border-radius:10px;padding:0.5rem 1rem;font-weight:600;font-size:0.83rem"><i class="fas fa-arrow-left me-2"></i>Back</a>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-6">
                <div class="table-card h-100">
                    <div class="section-head-modern"><div class="d-flex align-items-center gap-3"><div class="head-icon"><i class="fas fa-user"></i></div><div><h5>Basic Information</h5><p>Contact · role · status</p></div></div></div>
                    <div class="info-row"><div class="info-row-icon"><i class="fas fa-user"></i></div><div class="flex-grow-1"><small style="font-size:0.70rem;font-weight:700;letter-spacing:0.06em;color:#64748b">FULL NAME</small><div style="font-weight:700;color:#0f172a">{{ $subUser->name }}</div></div></div>
                    <div class="info-row"><div class="info-row-icon"><i class="fas fa-envelope"></i></div><div class="flex-grow-1"><small style="font-size:0.70rem;font-weight:700;letter-spacing:0.06em;color:#64748b">EMAIL</small><div style="font-weight:600;color:#0f172a"><a href="mailto:{{ $subUser->email }}" style="color:#2563eb;text-decoration:none">{{ $subUser->email }}</a></div></div></div>
                    @if($subUser->phone)<div class="info-row"><div class="info-row-icon"><i class="fas fa-phone"></i></div><div class="flex-grow-1"><small style="font-size:0.70rem;font-weight:700;letter-spacing:0.06em;color:#64748b">PHONE</small><div style="font-weight:600;color:#0f172a"><a href="tel:{{ $subUser->phone }}" style="color:#2563eb;text-decoration:none">{{ $subUser->phone }}</a></div></div></div>@endif
                    <div class="info-row"><div class="info-row-icon"><i class="fas fa-briefcase"></i></div><div class="flex-grow-1"><small style="font-size:0.70rem;font-weight:700;letter-spacing:0.06em;color:#64748b">ROLE</small><div><span class="badge" style="background:#1e293b;color:#fff;border-radius:99px;padding:0.3rem 0.7rem;font-size:0.72rem;font-weight:700">{{ ucfirst($subUser->sub_user_role) }}</span></div></div></div>
                    <div class="info-row"><div class="info-row-icon"><i class="fas fa-circle-check" style="color:#10b981"></i></div><div class="flex-grow-1"><small style="font-size:0.70rem;font-weight:700;letter-spacing:0.06em;color:#64748b">STATUS</small><div><span class="badge" style="background:#d1fae5;color:#065f46;border:1px solid #a7f3d0;border-radius:99px">Active</span></div></div></div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="table-card h-100">
                    <div class="section-head-modern"><div class="d-flex align-items-center gap-3"><div class="head-icon" style="background:#f0fdf4!important;color:#059669!important;border-color:#a7f3d0!important"><i class="fas fa-shield-halved"></i></div><div><h5>Access Permissions</h5><p>{{ $subUser->permissions->count() }} permissions</p></div></div></div>
                    @if($subUser->permissions->count() > 0)
                        @php $byCat = $subUser->permissions->groupBy('category'); @endphp
                        @foreach($byCat as $cat => $perms)
                            <div class="mb-3">
                                <h6 style="font-size:0.70rem;font-weight:800;letter-spacing:0.06em;color:#475569;text-transform:uppercase;border-bottom:1px solid #f1f5f9;padding-bottom:0.4rem">{{ str_replace('_',' ', $cat) }}</h6>
                                <div class="d-flex flex-wrap gap-1 mt-2">
                                    @foreach($perms as $perm)<span class="badge" style="background:#f8fafc;color:#334155;border:1px solid #eef2f7;border-radius:99px;font-size:0.72rem;padding:0.3rem 0.6rem">{{ $perm->display_name }}</span>@endforeach
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div class="text-center py-4" style="background:#fffbeb;border:1px dashed #fde68a;border-radius:10px"><i class="fas fa-triangle-exclamation mb-2" style="color:#d97706"></i><p class="mb-0" style="font-weight:600;color:#92400e">No permissions assigned</p><small style="color:#b45309">This user cannot access any features</small></div>
                    @endif
                </div>
            </div>
        </div>

        <div class="table-card">
            <div class="section-head-modern"><div class="d-flex align-items-center gap-3"><div class="head-icon" style="background:#eff6ff!important;color:#2563eb!important;border-color:#dbeafe!important"><i class="fas fa-chart-line"></i></div><div><h5>Account Summary</h5><p>Activity · tenure</p></div></div></div>
            <div class="row text-center g-3">
                <div class="col-6 col-md-3"><div class="p-3" style="background:#f8fafc;border:1px solid #eef2f7;border-radius:10px"><div style="font-weight:800;color:#2563eb;font-size:1.4rem">{{ $subUser->permissions->count() }}</div><small style="font-size:0.68rem;font-weight:700;letter-spacing:0.06em;color:#64748b;text-transform:uppercase">Permissions</small></div></div>
                <div class="col-6 col-md-3"><div class="p-3" style="background:#f0fdf4;border:1px solid #a7f3d0;border-radius:10px"><div style="font-weight:800;color:#059669;font-size:1.4rem">Active</div><small style="font-size:0.68rem;font-weight:700;letter-spacing:0.06em;color:#64748b;text-transform:uppercase">Status</small></div></div>
                <div class="col-6 col-md-3"><div class="p-3" style="background:#eff6ff;border:1px solid #dbeafe;border-radius:10px"><div style="font-weight:800;color:#0ea5e9;font-size:1.4rem">{{ $subUser->created_at->diffInDays() }}</div><small style="font-size:0.68rem;font-weight:700;letter-spacing:0.06em;color:#64748b;text-transform:uppercase">Days Active</small></div></div>
                <div class="col-6 col-md-3"><div class="p-3" style="background:#fffbeb;border:1px solid #fde68a;border-radius:10px"><div style="font-weight:700;color:#d97706;font-size:0.85rem">{{ $subUser->updated_at->diffForHumans() }}</div><small style="font-size:0.68rem;font-weight:700;letter-spacing:0.06em;color:#64748b;text-transform:uppercase">Last Updated</small></div></div>
            </div>
        </div>

        <div class="table-card">
            <div class="section-head-modern"><div class="d-flex align-items-center gap-3"><div class="head-icon"><i class="fas fa-bolt"></i></div><div><h5>Actions</h5><p>Manage sub-user</p></div></div></div>
            <div class="d-flex justify-content-between flex-wrap gap-2">
                <a href="{{ route('sub-users.index') }}" class="btn" style="background:#fff;border:1px solid #e2e8f0;color:#475569;border-radius:10px;padding:0.6rem 1.2rem;font-weight:600"><i class="fas fa-arrow-left me-2"></i>Back to Sub-Users</a>
                <div class="d-flex gap-2">
                    <a href="{{ route('sub-users.edit', $subUser) }}" class="doctor-btn doctor-btn-primary"><i class="fas fa-pen me-2"></i>Edit Sub-User</a>
                    <button type="button" class="doctor-btn doctor-btn-danger" onclick="confirmDelete()"><i class="fas fa-trash me-2"></i>Delete</button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog"><div class="modal-content" style="border-radius:12px;overflow:hidden;border:1px solid #eef2f7">
        <div class="modal-header" style="background:#fef2f2;border-bottom:1px solid #fecaca"><h5 class="modal-title" style="color:#991b1b;font-weight:800"><i class="fas fa-triangle-exclamation me-2"></i>Confirm Deletion</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
            <p>Are you sure you want to delete <strong>{{ $subUser->name }}</strong>?</p>
            <p class="mb-0" style="background:#fef2f2;border:1px solid #fecaca;border-radius:8px;padding:0.7rem;color:#991b1b;font-size:0.84rem"><strong>Warning:</strong> This cannot be undone.</p>
        </div>
        <div class="modal-footer" style="background:#f8fafc;border-top:1px solid #eef2f7">
            <button type="button" class="btn" style="background:#fff;border:1px solid #e2e8f0;color:#475569;border-radius:10px" data-bs-dismiss="modal">Cancel</button>
            <form method="POST" action="{{ route('sub-users.destroy', $subUser) }}" class="d-inline">@csrf @method('DELETE')<button type="submit" class="btn" style="background:#dc2626;color:#fff;border-radius:10px;font-weight:700">Delete Sub-User</button></form>
        </div>
    </div></div>
</div>

<script>function confirmDelete(){ new bootstrap.Modal(document.getElementById('deleteModal')).show(); }</script>
@endsection
