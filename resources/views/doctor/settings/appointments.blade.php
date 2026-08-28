@extends('master')

@section('title', 'Appointment Settings')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/doctor-design-system.css') }}">
<link rel="stylesheet" href="{{ asset('css/doctor-dashboard.css') }}">
<link rel="stylesheet" href="{{ asset('css/cases-overview.css') }}">
<style>
.dashboard-header{background:linear-gradient(135deg,#2c5aa0 0%,#1e3a8a 100%)!important;border-radius:12px!important;padding:2.5rem!important;margin-bottom:2rem!important;box-shadow:0 4px 15px rgba(44,90,160,0.15)!important;position:relative;overflow:hidden}
.dashboard-header::before{content:'';position:absolute;top:0;left:0;right:0;height:3px;background:linear-gradient(90deg,#10b981 0%,#059669 100%)}
.dashboard-header h2{color:#fff!important;font-weight:600!important;font-size:2rem!important;margin-bottom:0.4rem!important}
.dashboard-header p{color:rgba(255,255,255,0.9)!important;font-size:0.92rem!important;margin:0!important}
.table-card{background:#fff;border:1px solid #eef2f7;border-radius:12px;padding:1.3rem;box-shadow:0 1px 4px rgba(15,23,42,0.04);margin-bottom:1.25rem}
.section-head-modern{display:flex;align-items:center;gap:0.75rem;margin:-1.3rem -1.3rem 1.1rem -1.3rem;padding:1rem 1.3rem;background:#f8fafc;border-bottom:1px solid #e2e8f0;border-radius:12px 12px 0 0}
.section-head-modern .head-icon{width:38px;height:38px;border-radius:9px;display:flex;align-items:center;justify-content:center;font-size:0.95rem;flex-shrink:0;background:#1e293b!important;color:#fff!important;border:1px solid #1e293b!important}
.section-head-modern h5{color:#0f172a!important;font-weight:800!important;letter-spacing:-0.01em;margin:0;font-size:1rem}
.section-head-modern p{color:#475569!important;font-size:0.78rem;margin:2px 0 0;font-weight:500}
.appointment-type-card{border:1px solid #e2e8f0;border-radius:12px;background:#fff;transition:all .15s;cursor:pointer}
.appointment-type-card.enabled{border-color:#10b981;background:#f0fdf4;box-shadow:0 2px 8px rgba(16,185,129,0.08)}
.appointment-type-card.disabled{border-color:#eef2f7;background:#f8fafc;opacity:0.95}
.appointment-type-card:hover{border-color:#cbd5e1;box-shadow:0 4px 12px rgba(15,23,42,0.06);transform:translateY(-1px)}
.appointment-type-icon{width:44px;height:44px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1.1rem;flex-shrink:0}
.form-switch .form-check-input{width:2.2rem;height:1.15rem;cursor:pointer}
.form-switch .form-check-input:checked{background-color:#10b981;border-color:#10b981}
</style>
@endpush

@section('content')
<div class="container-fluid" style="background-color: var(--bg-secondary, #f8f9fa);">
    <div class="container py-4">
        <div class="dashboard-header cases-header-compact">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div class="d-flex align-items-center gap-3">
                    <a href="{{ route('doctor.dashboard') }}" class="btn" style="background:rgba(255,255,255,0.15);border:1px solid rgba(255,255,255,0.32);color:#fff;border-radius:10px;padding:0.5rem 1rem;font-weight:600;font-size:0.83rem"><i class="fas fa-arrow-left me-2"></i>Dashboard</a>
                    <div>
                        <h2 class="h1 mb-1" style="font-size:1.8rem">Appointment Settings</h2>
                        <p>Manage your appointment type preferences</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-8">
                <div class="table-card">
                    <div class="section-head-modern"><div class="d-flex align-items-center gap-3"><div class="head-icon"><i class="fas fa-calendar-check"></i></div><div><h5>Appointment Types</h5><p>Enable the types patients can book</p></div></div></div>
                    <p style="color:#64748b;font-size:0.84rem;background:#f8fafc;border:1px solid #eef2f7;border-radius:8px;padding:0.7rem 0.9rem"><i class="fas fa-circle-info me-1"></i>Only enabled types appear when patients book appointments.</p>

                    @if ($errors->any())
                        <div class="alert alert-danger" style="border-radius:10px"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
                    @endif
                    @if (session('success'))
                        <div class="alert alert-success d-flex align-items-center gap-2" style="border-radius:10px;background:#ecfdf5;border:1px solid #a7f3d0;color:#065f46"><i class="fas fa-check-circle"></i><div>{{ session('success') }}</div></div>
                    @endif

                    <form method="POST" action="{{ route('doctor.settings.appointments.update') }}">
                        @csrf @method('PUT')
                        <div class="row g-3">
                            @foreach($appointmentTypes as $type => $label)
                                <div class="col-md-6">
                                    <label class="appointment-type-card p-3 d-flex align-items-center gap-3 {{ $doctor->isAppointmentTypeEnabled($type) ? 'enabled' : 'disabled' }}" for="type_{{ $type }}" style="cursor:pointer;margin:0">
                                        <div class="appointment-type-icon" style="background:{{ $type === 'in_person' ? '#eff6ff;color:#2563eb;border:1px solid #dbeafe' : ($type === 'video_call' ? '#f0fdf4;color:#059669;border:1px solid #a7f3d0' : '#eff6ff;color:#0ea5e9;border:1px solid #bae6fd') }}">
                                            @if($type === 'in_person')<i class="fas fa-hospital"></i>
                                            @elseif($type === 'video_call')<i class="fas fa-video"></i>
                                            @else <i class="fas fa-phone"></i>@endif
                                        </div>
                                        <div class="flex-grow-1 min-w-0">
                                            <div style="font-weight:800;color:#0f172a;font-size:0.92rem">{{ $label }}</div>
                                            <small style="color:#64748b;font-size:0.76rem;display:block">
                                                @if($type === 'in_person') Face-to-face at your clinic
                                                @elseif($type === 'video_call') Online video consultations
                                                @else Phone call consultations @endif
                                            </small>
                                        </div>
                                        <div class="form-check form-switch m-0">
                                            <input class="form-check-input" type="checkbox" name="appointment_types[]" value="{{ $type }}" id="type_{{ $type }}" {{ $doctor->isAppointmentTypeEnabled($type) ? 'checked' : '' }}>
                                        </div>
                                    </label>
                                </div>
                            @endforeach
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top" style="border-color:#eef2f7!important">
                            <small style="color:#64748b"><i class="fas fa-info-circle me-1"></i>At least one type must be enabled</small>
                            <button type="submit" class="doctor-btn doctor-btn-primary"><i class="fas fa-save me-2"></i>Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="col-lg-4">
                @php $enabledTypes = $doctor->getEnabledAppointmentTypes(); $totalTypes = count($appointmentTypes); $enabledCount = count($enabledTypes); @endphp
                <div class="table-card">
                    <div class="section-head-modern"><div class="d-flex align-items-center gap-3"><div class="head-icon" style="background:#f0fdf4!important;color:#059669!important;border-color:#a7f3d0!important"><i class="fas fa-chart-bar"></i></div><div><h5>Current Status</h5><p>{{ $enabledCount }}/{{ $totalTypes }} enabled</p></div></div></div>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1"><small style="font-size:0.70rem;font-weight:700;letter-spacing:0.06em;color:#64748b">ENABLED TYPES</small><small style="font-weight:700;color:#0f172a">{{ $enabledCount }}/{{ $totalTypes }}</small></div>
                        <div class="progress" style="height:8px;background:#f1f5f9;border-radius:99px"><div class="progress-bar" style="width: {{ ($enabledCount / $totalTypes) * 100 }}%;background:linear-gradient(90deg,#10b981,#059669);border-radius:99px"></div></div>
                    </div>
                    <div>
                        @if(count($enabledTypes) > 0)
                            <small style="font-size:0.70rem;font-weight:700;letter-spacing:0.06em;color:#64748b">ENABLED:</small>
                            <div class="d-flex flex-column gap-2 mt-2">
                                @foreach($enabledTypes as $type)
                                    <div class="d-flex align-items-center gap-2 p-2" style="background:#f8fafc;border:1px solid #eef2f7;border-radius:8px">
                                        @if($type === 'in_person')<i class="fas fa-hospital" style="color:#2563eb"></i>
                                        @elseif($type === 'video_call')<i class="fas fa-video" style="color:#059669"></i>
                                        @else <i class="fas fa-phone" style="color:#0ea5e9"></i>@endif
                                        <small style="font-weight:600;color:#334155">{{ $appointmentTypes[$type] }}</small>
                                        <span class="ms-auto badge" style="background:#d1fae5;color:#065f46;border:1px solid #a7f3d0;border-radius:99px;font-size:0.65rem">Enabled</span>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-4" style="background:#fffbeb;border:1px dashed #fde68a;border-radius:10px">
                                <i class="fas fa-triangle-exclamation mb-2" style="color:#d97706"></i>
                                <p class="mb-0" style="font-weight:600;color:#92400e;font-size:0.88rem">No types enabled</p>
                                <small style="color:#b45309">Enable at least one to allow bookings</small>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="table-card">
                    <div class="section-head-modern"><div class="d-flex align-items-center gap-3"><div class="head-icon" style="background:#fffbeb!important;color:#d97706!important;border-color:#fde68a!important"><i class="fas fa-lightbulb"></i></div><div><h5>Tips</h5><p>Best practices</p></div></div></div>
                    <div class="d-flex flex-column gap-3">
                        <div class="d-flex gap-2"><div class="d-flex align-items-center justify-content-center" style="width:28px;height:28px;background:#f0fdf4;border:1px solid #a7f3d0;border-radius:8px;color:#059669;flex-shrink:0"><i class="fas fa-check" style="font-size:0.7rem"></i></div><small style="color:#475569">Enable multiple types to give patients more flexibility</small></div>
                        <div class="d-flex gap-2"><div class="d-flex align-items-center justify-content-center" style="width:28px;height:28px;background:#f0fdf4;border:1px solid #a7f3d0;border-radius:8px;color:#059669;flex-shrink:0"><i class="fas fa-check" style="font-size:0.7rem"></i></div><small style="color:#475569">Video calls help reach patients who can't visit in person</small></div>
                        <div class="d-flex gap-2"><div class="d-flex align-items-center justify-content-center" style="width:28px;height:28px;background:#f0fdf4;border:1px solid #a7f3d0;border-radius:8px;color:#059669;flex-shrink:0"><i class="fas fa-check" style="font-size:0.7rem"></i></div><small style="color:#475569">You can change these settings anytime based on availability</small></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function(){
  document.querySelectorAll('input[name="appointment_types[]"]').forEach(function(cb){
    const card = cb.closest('.appointment-type-card');
    function upd(){
      if(cb.checked){ card.classList.add('enabled'); card.classList.remove('disabled'); }
      else { card.classList.remove('enabled'); card.classList.add('disabled'); }
    }
    upd(); cb.addEventListener('change', upd);
    card.addEventListener('click', function(e){ if(e.target!==cb) cb.click(); });
  });
});
</script>
@endpush
