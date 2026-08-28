@extends('master')

@section('title', 'Sub-Users Management')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/doctor-design-system.css') }}">
<link rel="stylesheet" href="{{ asset('css/doctor-dashboard.css') }}">
<link rel="stylesheet" href="{{ asset('css/cases-overview.css') }}">
<style>
.dashboard-header{background:linear-gradient(135deg,#2c5aa0 0%,#1e3a8a 100%)!important;border-radius:12px!important;padding:2.5rem!important;margin-bottom:2rem!important;box-shadow:0 4px 15px rgba(44,90,160,0.15)!important;position:relative;overflow:hidden}
.dashboard-header::before{content:'';position:absolute;top:0;left:0;right:0;height:3px;background:linear-gradient(90deg,#10b981 0%,#059669 100%)}
.dashboard-header h2{color:#fff!important;font-weight:600!important;font-size:2rem!important;margin-bottom:0.4rem!important}
.dashboard-header p{color:rgba(255,255,255,0.9)!important;font-size:0.92rem!important;margin:0!important}
.avatar-circle{width:50px;height:50px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:1rem;color:#fff;flex-shrink:0}
.card-team{transition:transform .15s,box-shadow .15s;border:1px solid #eef2f7;border-radius:12px;overflow:hidden;background:#fff}
.card-team:hover{transform:translateY(-2px);box-shadow:0 8px 20px rgba(15,23,42,0.08)}
</style>
@endpush

@section('content')
@php
    $total = $subUsers->count();
    $roles = $subUsers->pluck('sub_user_role')->unique()->count();
    $avgPerms = $total ? round($subUsers->avg(fn($u)=>$u->permissions->count()),1) : 0;
@endphp
<div class="container-fluid" style="background-color: var(--bg-secondary, #f8f9fa);">
    <div class="container py-4">
        <div class="dashboard-header cases-header-compact">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <h2><i class="fas fa-users me-2"></i>Sub-Users Management</h2>
                    <p>Manage your team members and their access permissions</p>
                </div>
                <a href="{{ route('sub-users.create') }}" class="doctor-btn doctor-btn-primary doctor-btn-sm" style="background:#fff;color:#1e293b;border-color:#fff"><i class="fas fa-plus me-1"></i> Add Sub-User</a>
            </div>
        </div>

        <div class="row g-2 mb-3 cases-stats-compact">
            <div class="col-6 col-lg-3">
                <div class="stats-card stats-card--compact">
                    <div class="stats-icon stats-icon--sm" style="background: linear-gradient(135deg, #2c5aa0 0%, #1e3a8a 100%);"><i class="fas fa-users"></i></div>
                    <div class="stats-text"><p class="stats-number">{{ $total }}</p><p class="stats-label">Total Team</p></div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="stats-card stats-card--compact">
                    <div class="stats-icon stats-icon--sm" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);"><i class="fas fa-user-check"></i></div>
                    <div class="stats-text"><p class="stats-number">{{ $total }}</p><p class="stats-label">Active</p></div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="stats-card stats-card--compact">
                    <div class="stats-icon stats-icon--sm" style="background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);"><i class="fas fa-briefcase"></i></div>
                    <div class="stats-text"><p class="stats-number">{{ $roles }}</p><p class="stats-label">Roles</p></div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="stats-card stats-card--compact">
                    <div class="stats-icon stats-icon--sm" style="background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%);"><i class="fas fa-shield-halved"></i></div>
                    <div class="stats-text"><p class="stats-number">{{ $avgPerms }}</p><p class="stats-label">Avg Permissions</p></div>
                </div>
            </div>
        </div>

        @if($subUsers->count() > 0)
            <div class="row g-3">
                @foreach($subUsers as $subUser)
                    <div class="col-md-6 col-lg-4">
                        <div class="card-team h-100 shadow-sm">
                            <div class="p-3">
                                <div class="d-flex align-items-center gap-3 mb-3">
                                    @php $initials = strtoupper(substr($subUser->name,0,2)); $roleColor = ['secretary'=>'#0ea5e9','assistant'=>'#6366f1','nurse'=>'#10b981','receptionist'=>'#f59e0b','coordinator'=>'#8b5cf6'][$subUser->sub_user_role] ?? '#64748b'; @endphp
                                    <div class="avatar-circle" style="background: {{ $roleColor }}">{{ $initials }}</div>
                                    <div class="flex-grow-1 min-w-0">
                                        <h6 class="mb-0" style="font-weight:800;color:#0f172a;font-size:0.95rem">{{ $subUser->name }}</h6>
                                        <small style="color:#fff;background:{{ $roleColor }};padding:0.15rem 0.45rem;border-radius:99px;font-size:0.68rem;font-weight:700">{{ ucfirst($subUser->sub_user_role) }}</small>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <small style="font-size:0.70rem;font-weight:700;letter-spacing:0.06em;color:#64748b;text-transform:uppercase">Email</small>
                                    <div style="font-size:0.88rem;color:#334155;word-break:break-all">{{ $subUser->email }}</div>
                                    @if($subUser->phone)<div class="mt-1"><small style="font-size:0.70rem;font-weight:700;letter-spacing:0.06em;color:#64748b">Phone</small><div style="font-size:0.84rem;color:#334155">{{ $subUser->phone }}</div></div>@endif
                                </div>
                                <div class="mb-3">
                                    <small style="font-size:0.70rem;font-weight:700;letter-spacing:0.06em;color:#64748b">PERMISSIONS ({{ $subUser->permissions->count() }})</small>
                                    <div class="mt-1 d-flex flex-wrap gap-1">
                                        @forelse($subUser->permissions->take(3) as $permission)
                                            <span class="badge" style="background:#f8fafc;color:#334155;border:1px solid #eef2f7;border-radius:99px;font-size:0.68rem;padding:0.25rem 0.5rem">{{ $permission->display_name }}</span>
                                        @empty
                                            <span style="color:#94a3b8;font-size:0.78rem">No permissions</span>
                                        @endforelse
                                        @if($subUser->permissions->count() > 3)<span class="badge" style="background:#f1f5f9;color:#475569;border-radius:99px;font-size:0.68rem">+{{ $subUser->permissions->count() - 3 }} more</span>@endif
                                    </div>
                                </div>
                                <div class="d-flex gap-2">
                                    <a href="{{ route('sub-users.show', $subUser) }}" class="doctor-btn doctor-btn-outline doctor-btn-sm flex-fill" style="justify-content:center"><i class="fas fa-eye me-1"></i>View</a>
                                    <a href="{{ route('sub-users.edit', $subUser) }}" class="doctor-btn doctor-btn-outline doctor-btn-sm flex-fill" style="justify-content:center"><i class="fas fa-pen me-1"></i>Edit</a>
                                    <button type="button" class="doctor-btn doctor-btn-danger doctor-btn-sm" style="min-width:36px" onclick="confirmDelete({{ $subUser->id }})"><i class="fas fa-trash"></i></button>
                                </div>
                            </div>
                            <div class="px-3 py-2 d-flex align-items-center gap-2" style="background:#f8fafc;border-top:1px solid #eef2f7"><i class="fas fa-clock" style="color:#94a3b8;font-size:0.75rem"></i><small style="color:#64748b;font-size:0.76rem">Created {{ $subUser->created_at->diffForHumans() }}</small></div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="card border-0 shadow-sm cases-panel">
                <div class="doctor-empty-state" style="padding:3rem 1.5rem;text-align:center">
                    <div class="d-inline-flex align-items-center justify-content-center rounded-3 mb-3" style="width:64px;height:64px;background:#f8fafc;border:1px solid #eef2f7;color:#94a3b8"><i class="fas fa-users" style="font-size:1.6rem"></i></div>
                    <h5 style="font-weight:800;color:#0f172a">No Sub-Users Yet</h5>
                    <p style="color:#64748b;max-width:480px;margin:0 auto 1.2rem">Create sub-users to help manage your practice more efficiently — nurses, receptionists, assistants with fine-grained permissions.</p>
                    <a href="{{ route('sub-users.create') }}" class="doctor-btn doctor-btn-primary"><i class="fas fa-plus me-1"></i> Add Your First Sub-User</a>
                </div>
            </div>
        @endif
    </div>
</div>

<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="border-radius:12px;border:1px solid #eef2f7;overflow:hidden">
            <div class="modal-header" style="background:#fef2f2;border-bottom:1px solid #fecaca"><h5 class="modal-title" style="color:#991b1b;font-weight:800"><i class="fas fa-triangle-exclamation me-2"></i>Confirm Deletion</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <p>Are you sure you want to delete this sub-user? This action cannot be undone.</p>
                <p class="mb-0" style="background:#fef2f2;border:1px solid #fecaca;border-radius:8px;padding:0.7rem;color:#991b1b;font-size:0.84rem"><strong>Warning:</strong> The sub-user will lose access to all assigned permissions and data.</p>
            </div>
            <div class="modal-footer" style="background:#f8fafc;border-top:1px solid #eef2f7">
                <button type="button" class="btn" style="background:#fff;border:1px solid #e2e8f0;color:#475569;border-radius:10px;padding:0.5rem 1rem;font-weight:600" data-bs-dismiss="modal">Cancel</button>
                <form id="deleteForm" method="POST" class="d-inline">@csrf @method('DELETE')<button type="submit" class="btn" style="background:#dc2626;color:#fff;border-radius:10px;padding:0.5rem 1rem;font-weight:700">Delete Sub-User</button></form>
            </div>
        </div>
    </div>
</div>

<script>
function confirmDelete(id){ const f=document.getElementById('deleteForm'); f.action=`/sub-users/${id}`; new bootstrap.Modal(document.getElementById('deleteModal')).show(); }
</script>
@endsection
