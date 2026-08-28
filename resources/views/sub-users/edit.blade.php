@extends('master')

@section('title', 'Edit Sub-User')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/doctor-design-system.css') }}">
<link rel="stylesheet" href="{{ asset('css/doctor-dashboard.css') }}">
<link rel="stylesheet" href="{{ asset('css/cases-overview.css') }}">
<style>
.dashboard-header{background:linear-gradient(135deg,#2c5aa0 0%,#1e3a8a 100%)!important;border-radius:12px!important;padding:2.5rem!important;margin-bottom:2rem!important;box-shadow:0 4px 15px rgba(44,90,160,0.15)!important;position:relative;overflow:hidden}
.dashboard-header::before{content:'';position:absolute;top:0;left:0;right:0;height:3px;background:linear-gradient(90deg,#f59e0b 0%,#d97706 100%)}
.dashboard-header h2{color:#fff!important;font-weight:600!important;font-size:2rem!important;margin-bottom:0.4rem!important}
.dashboard-header p{color:rgba(255,255,255,0.9)!important;font-size:0.92rem!important;margin:0!important}
.table-card{background:#fff;border:1px solid #eef2f7;border-radius:12px;padding:1.3rem;box-shadow:0 1px 4px rgba(15,23,42,0.04);margin-bottom:1.25rem}
.section-head-modern{display:flex;align-items:center;gap:0.75rem;margin:-1.3rem -1.3rem 1.1rem -1.3rem;padding:1rem 1.3rem;background:#fffbeb;border-bottom:1px solid #fde68a;border-radius:12px 12px 0 0}
.section-head-modern .head-icon{width:38px;height:38px;border-radius:9px;display:flex;align-items:center;justify-content:center;font-size:0.95rem;flex-shrink:0;background:#f59e0b!important;color:#fff!important;border:1px solid #f59e0b!important}
.section-head-modern h5{color:#92400e!important;font-weight:800!important;letter-spacing:-0.01em;margin:0;font-size:1rem}
.section-head-modern p{color:#b45309!important;font-size:0.78rem;margin:2px 0 0;font-weight:500}
.note-label{font-size:0.70rem;font-weight:700;letter-spacing:0.06em;color:#64748b;margin-bottom:0.35rem;text-transform:uppercase}
.form-control,.form-select{border:1px solid #e2e8f0;border-radius:10px;padding:0.6rem 0.9rem;font-size:0.92rem;background:#f8fafc}
.form-control:focus,.form-select:focus{border-color:#f59e0b;box-shadow:0 0 0 3px rgba(245,158,11,0.12);background:#fff}
.perm-card{border:1px solid #eef2f7;border-radius:10px;padding:0.75rem 0.9rem;background:#fff}
.perm-card:has(input:checked){background:#fffbeb;border-color:#fde68a}
</style>
@endpush

@section('content')
<div class="container-fluid" style="background-color: var(--bg-secondary, #f8f9fa);">
    <div class="container py-4">
        <div class="dashboard-header">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <h2><i class="fas fa-pen me-2"></i>Edit Sub-User</h2>
                    <p>Update {{ $subUser->name }}'s information and permissions</p>
                </div>
                <a href="{{ route('sub-users.index') }}" class="btn" style="background:rgba(255,255,255,0.15);border:1px solid rgba(255,255,255,0.32);color:#fff;border-radius:10px;padding:0.5rem 1rem;font-weight:600;font-size:0.83rem"><i class="fas fa-arrow-left me-2"></i>Back</a>
            </div>
        </div>

        <form method="POST" action="{{ route('sub-users.update', $subUser) }}">
            @csrf @method('PUT')
            <div class="row g-4">
                <div class="col-lg-8">
                    <div class="table-card">
                        <div class="section-head-modern"><div class="d-flex align-items-center gap-3"><div class="head-icon"><i class="fas fa-user"></i></div><div><h5>Basic Information</h5><p>Name · email · role</p></div></div></div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="name" class="form-label note-label">Full Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $subUser->name) }}" required>
                                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label note-label">Email Address <span class="text-danger">*</span></label>
                                <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email', $subUser->email) }}" required>
                                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="phone" class="form-label note-label">Phone Number</label>
                                <input type="tel" class="form-control @error('phone') is-invalid @enderror" id="phone" name="phone" value="{{ old('phone', $subUser->phone) }}">
                                @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="sub_user_role" class="form-label note-label">Role/Position <span class="text-danger">*</span></label>
                                <select class="form-select @error('sub_user_role') is-invalid @enderror" id="sub_user_role" name="sub_user_role" required>
                                    <option value="">Select Role</option>
                                    <option value="secretary" {{ old('sub_user_role', $subUser->sub_user_role) == 'secretary' ? 'selected' : '' }}>Secretary</option>
                                    <option value="assistant" {{ old('sub_user_role', $subUser->sub_user_role) == 'assistant' ? 'selected' : '' }}>Assistant</option>
                                    <option value="nurse" {{ old('sub_user_role', $subUser->sub_user_role) == 'nurse' ? 'selected' : '' }}>Nurse</option>
                                    <option value="receptionist" {{ old('sub_user_role', $subUser->sub_user_role) == 'receptionist' ? 'selected' : '' }}>Receptionist</option>
                                    <option value="coordinator" {{ old('sub_user_role', $subUser->sub_user_role) == 'coordinator' ? 'selected' : '' }}>Coordinator</option>
                                    <option value="other" {{ old('sub_user_role', $subUser->sub_user_role) == 'other' ? 'selected' : '' }}>Other</option>
                                </select>
                                @error('sub_user_role')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>

                    <div class="table-card" style="border-color:#fde68a">
                        <div class="section-head-modern" style="background:#fffbeb;border-color:#fde68a"><div class="d-flex align-items-center gap-3"><div class="head-icon" style="background:#fff!important;color:#d97706!important;border-color:#fde68a!important"><i class="fas fa-lock"></i></div><div><h5 style="color:#92400e!important">Change Password</h5><p style="color:#b45309!important">Leave blank to keep current</p></div></div></div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="password" class="form-label note-label">New Password</label>
                                <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password">
                                @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="password_confirmation" class="form-label note-label">Confirm New Password</label>
                                <input type="password" class="form-control" id="password_confirmation" name="password_confirmation">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="table-card" style="background:#fffbeb;border-color:#fde68a">
                        <div class="section-head-modern" style="background:#fffbeb;border-color:#fde68a"><div class="d-flex align-items-center gap-3"><div class="head-icon"><i class="fas fa-bolt"></i></div><div><h5>Actions</h5><p>Save changes</p></div></div></div>
                        <div class="d-grid gap-2">
                            <button type="submit" class="doctor-btn doctor-btn-primary" style="justify-content:center;background:#f59e0b;border-color:#f59e0b"><i class="fas fa-save me-2"></i>Update Sub-User</button>
                            <a href="{{ route('sub-users.index') }}" class="doctor-btn doctor-btn-outline" style="justify-content:center">Cancel</a>
                        </div>
                    </div>
                </div>

                <div class="col-12">
                    <div class="table-card">
                        <div class="section-head-modern" style="background:#f8fafc;border-color:#e2e8f0"><div class="d-flex align-items-center gap-3"><div class="head-icon" style="background:#1e293b!important;color:#fff!important"><i class="fas fa-shield-halved"></i></div><div><h5 style="color:#0f172a!important">Access Permissions</h5><p style="color:#475569!important">Select features this sub-user can access</p></div></div>
                        <div class="d-flex gap-2 flex-wrap">
                            <button type="button" class="doctor-btn doctor-btn-outline doctor-btn-sm" onclick="selectAllPermissions()"><i class="fas fa-check-double me-1"></i>Select All</button>
                            <button type="button" class="doctor-btn doctor-btn-outline doctor-btn-sm" onclick="clearAllPermissions()"><i class="fas fa-times me-1"></i>Clear All</button>
                            <button type="button" class="doctor-btn doctor-btn-outline doctor-btn-sm" onclick="selectCorePermissions()"><i class="fas fa-star me-1"></i>Core Only</button>
                        </div>
                        </div>
                        <p style="font-size:0.78rem;color:#64748b;background:#fffbeb;border:1px solid #fde68a;border-radius:8px;padding:0.6rem 0.8rem"><i class="fas fa-circle-info me-1"></i><strong>Note:</strong> AI Assistant, Diagnoses, and Voice Assistant are restricted to main users only.</p>
                        @php $currentPermissions = $subUser->permissions->pluck('id')->toArray(); @endphp
                        @if($availablePermissions->count() > 0)
                            @foreach($availablePermissions as $category => $permissions)
                                <div class="mb-4">
                                    <h6 style="font-size:0.72rem;font-weight:800;letter-spacing:0.06em;color:#475569;text-transform:uppercase;border-bottom:1px solid #f1f5f9;padding-bottom:0.4rem">{{ str_replace('_',' ', $category) }}</h6>
                                    <div class="row g-2">
                                        @foreach($permissions as $permission)
                                            <div class="col-md-6 col-lg-4">
                                                <label class="perm-card d-flex gap-2" for="permission_{{ $permission->id }}" style="cursor:pointer">
                                                    <input class="form-check-input mt-1" type="checkbox" name="permissions[]" value="{{ $permission->id }}" id="permission_{{ $permission->id }}" {{ in_array($permission->id, old('permissions', $currentPermissions)) ? 'checked' : '' }}>
                                                    <div>
                                                        <div style="font-weight:700;color:#0f172a;font-size:0.84rem">{{ $permission->display_name }}</div>
                                                        @if($permission->description)<small style="color:#64748b;font-size:0.76rem">{{ $permission->description }}</small>@endif
                                                    </div>
                                                </label>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <div class="text-center py-4" style="background:#f8fafc;border:1px dashed #e2e8f0;border-radius:10px;color:#64748b">No permissions available</div>
                        @endif
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
function selectAllPermissions(){ document.querySelectorAll('input[name="permissions[]"]').forEach(c=>c.checked=true); }
function clearAllPermissions(){ document.querySelectorAll('input[name="permissions[]"]').forEach(c=>c.checked=false); }
function selectCorePermissions(){ clearAllPermissions(); const cores=['dashboard','settings','cases']; document.querySelectorAll('input[name="permissions[]"]').forEach(c=>{ const l=c.nextElementSibling?.textContent?.toLowerCase()||c.parentElement.textContent.toLowerCase(); if(cores.some(k=>l.includes(k))) c.checked=true; }); }
</script>
@endsection
