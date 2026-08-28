@extends('master')

@section('title', 'Kiosk Setup - Doctor Dashboard')

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
.note-label{font-size:0.70rem;font-weight:700;letter-spacing:0.06em;color:#64748b;margin-bottom:0.35rem;text-transform:uppercase}
.form-control,.form-select{border:1px solid #e2e8f0;border-radius:10px;padding:0.6rem 0.9rem;font-size:0.92rem;background:#f8fafc}
.form-control:focus,.form-select:focus{border-color:#3b82f6;box-shadow:0 0 0 3px rgba(59,130,246,0.12);background:#fff}
.color-preview{width:20px;height:20px;border-radius:6px;border:1px solid #e2e8f0;display:inline-block;vertical-align:middle}
</style>
@endpush

@section('content')
<div class="container-fluid" style="background-color: var(--bg-secondary, #f8f9fa);">
    <div class="container py-4">
        <div class="dashboard-header cases-header-compact">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <h2><i class="fas fa-desktop me-2"></i>Kiosk Configuration</h2>
                    <p>Set up and manage your self-service kiosk</p>
                </div>
                <a href="{{ route('doctor.kiosk.management') }}" class="btn" style="background:rgba(255,255,255,0.15);border:1px solid rgba(255,255,255,0.32);color:#fff;border-radius:10px;padding:0.5rem 1rem;font-weight:600;font-size:0.83rem"><i class="fas fa-cogs me-1"></i> Management Dashboard</a>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2" role="alert" style="border-radius:10px;background:#ecfdf5;border:1px solid #a7f3d0;color:#065f46"><i class="fas fa-check-circle"></i><div>{{ session('success') }}</div><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        @endif
        @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert" style="border-radius:10px">
                <h6 style="font-weight:800"><i class="fas fa-exclamation-triangle me-1"></i>Please correct errors:</h6>
                <ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <form action="{{ route('doctor.kiosk.setup.store') }}" method="POST">
            @csrf
            <div class="row g-4">
                <!-- Clinic Information -->
                <div class="col-lg-6">
                    <div class="table-card h-100">
                        <div class="section-head-modern"><div class="d-flex align-items-center gap-3"><div class="head-icon"><i class="fas fa-hospital"></i></div><div><h5>Clinic Information</h5><p>Name · address · contact</p></div></div></div>
                        <div class="mb-3">
                            <label for="clinic_name" class="form-label note-label">Clinic Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control {{ $errors->has('clinic_name') ? 'is-invalid' : '' }}" id="clinic_name" name="clinic_name" value="{{ old('clinic_name', $kioskConfig->clinic_name ?? '') }}" required placeholder="e.g., Al-Noor Clinic">
                            @if($errors->has('clinic_name'))<div class="invalid-feedback">{{ $errors->first('clinic_name') }}</div>@endif
                            <small style="color:#64748b;font-size:0.76rem">Displayed on kiosk welcome screen</small>
                        </div>
                        <div class="mb-3">
                            <label for="clinic_address" class="form-label note-label">Clinic Address <span class="text-danger">*</span></label>
                            <textarea class="form-control {{ $errors->has('clinic_address') ? 'is-invalid' : '' }}" id="clinic_address" name="clinic_address" rows="3" required placeholder="Full street address">{{ old('clinic_address', $kioskConfig->clinic_address ?? '') }}</textarea>
                            @if($errors->has('clinic_address'))<div class="invalid-feedback">{{ $errors->first('clinic_address') }}</div>@endif
                        </div>
                        <div class="mb-3">
                            <label for="contact_phone" class="form-label note-label">Contact Phone <span class="text-danger">*</span></label>
                            <input type="tel" class="form-control {{ $errors->has('contact_phone') ? 'is-invalid' : '' }}" id="contact_phone" name="contact_phone" value="{{ old('contact_phone', $kioskConfig->contact_phone ?? '') }}" required pattern="[\+]?[1-9][\d]{0,15}" placeholder="+1-555-123-4567">
                            @if($errors->has('contact_phone'))<div class="invalid-feedback">{{ $errors->first('contact_phone') }}</div>@endif
                        </div>
                        <div class="mb-0">
                            <label for="kiosk_display_name" class="form-label note-label">Kiosk Display Name</label>
                            <input type="text" class="form-control {{ $errors->has('kiosk_display_name') ? 'is-invalid' : '' }}" id="kiosk_display_name" name="kiosk_display_name" value="{{ old('kiosk_display_name', $kioskConfig->kiosk_display_name ?? 'Welcome to Our Clinic') }}" placeholder="Welcome message">
                            <small style="color:#64748b;font-size:0.76rem">Custom welcome message on kiosk</small>
                        </div>
                    </div>
                </div>

                <!-- Kiosk Settings -->
                <div class="col-lg-6">
                    <div class="table-card h-100">
                        <div class="section-head-modern"><div class="d-flex align-items-center gap-3"><div class="head-icon" style="background:#eff6ff!important;color:#2563eb!important;border-color:#dbeafe!important"><i class="fas fa-sliders"></i></div><div><h5>Kiosk Settings</h5><p>Theme · features · accessibility</p></div></div></div>
                        <div class="row g-3 mb-3">
                            <div class="col-6">
                                <label for="primary_color" class="form-label note-label">Primary Color</label>
                                <div class="d-flex gap-2 align-items-center">
                                    <input type="color" class="form-control {{ $errors->has('primary_color') ? 'is-invalid' : '' }}" id="primary_color" name="primary_color" value="{{ old('primary_color', $kioskConfig->primary_color ?? '#2563eb') }}" style="width:56px;height:38px;padding:0.2rem;border-radius:8px">
                                    <input type="text" class="form-control" id="primary_color_text" value="{{ old('primary_color', $kioskConfig->primary_color ?? '#2563eb') }}" readonly style="flex:1;font-family:ui-monospace;font-size:0.82rem">
                                </div>
                                <small style="color:#64748b;font-size:0.76rem">Main theme color</small>
                            </div>
                            <div class="col-6">
                                <label for="secondary_color" class="form-label note-label">Secondary Color</label>
                                <div class="d-flex gap-2 align-items-center">
                                    <input type="color" class="form-control {{ $errors->has('secondary_color') ? 'is-invalid' : '' }}" id="secondary_color" name="secondary_color" value="{{ old('secondary_color', $kioskConfig->secondary_color ?? '#6b7280') }}" style="width:56px;height:38px;padding:0.2rem;border-radius:8px">
                                    <input type="text" class="form-control" id="secondary_color_text" value="{{ old('secondary_color', $kioskConfig->secondary_color ?? '#6b7280') }}" readonly style="flex:1;font-family:ui-monospace;font-size:0.82rem">
                                </div>
                                <small style="color:#64748b;font-size:0.76rem">Accent for buttons</small>
                            </div>
                        </div>
                        <div class="d-flex flex-column gap-2">
                            <label class="form-check d-flex align-items-center gap-3" style="background:#f8fafc;border:1px solid #eef2f7;border-radius:10px;padding:0.8rem 1rem;cursor:pointer">
                                <input type="hidden" name="auto_approve_appointments" value="0">
                                <input type="checkbox" class="form-check-input m-0" id="auto_approve_appointments" name="auto_approve_appointments" value="1" {{ old('auto_approve_appointments', $kioskConfig->auto_approve_appointments ?? false) ? 'checked' : '' }} style="width:1.15rem;height:1.15rem">
                                <div><div style="font-weight:700;color:#0f172a;font-size:0.88rem">Auto-approve Appointments</div><small style="color:#64748b">Automatically confirm kiosk bookings</small></div>
                            </label>
                            <label class="form-check d-flex align-items-center gap-3" style="background:#f8fafc;border:1px solid #eef2f7;border-radius:10px;padding:0.8rem 1rem;cursor:pointer">
                                <input type="hidden" name="require_payment_upfront" value="0">
                                <input type="checkbox" class="form-check-input m-0" id="require_payment_upfront" name="require_payment_upfront" value="1" {{ old('require_payment_upfront', $kioskConfig->require_payment_upfront ?? false) ? 'checked' : '' }} style="width:1.15rem;height:1.15rem">
                                <div><div style="font-weight:700;color:#0f172a;font-size:0.88rem">Require Payment Upfront</div><small style="color:#64748b">Require payment at booking</small></div>
                            </label>
                            <label class="form-check d-flex align-items-center gap-3" style="background:#f8fafc;border:1px solid #eef2f7;border-radius:10px;padding:0.8rem 1rem;cursor:pointer">
                                <input type="hidden" name="voice_instructions_enabled" value="0">
                                <input type="checkbox" class="form-check-input m-0" id="voice_instructions_enabled" name="voice_instructions_enabled" value="1" {{ old('voice_instructions_enabled', $kioskConfig->voice_instructions_enabled ?? true) ? 'checked' : '' }} style="width:1.15rem;height:1.15rem">
                                <div><div style="font-weight:700;color:#0f172a;font-size:0.88rem">Voice Instructions</div><small style="color:#64748b">Voice guidance for patients</small></div>
                            </label>
                            <label class="form-check d-flex align-items-center gap-3" style="background:#f8fafc;border:1px solid #eef2f7;border-radius:10px;padding:0.8rem 1rem;cursor:pointer">
                                <input type="hidden" name="high_contrast_mode" value="0">
                                <input type="checkbox" class="form-check-input m-0" id="high_contrast_mode" name="high_contrast_mode" value="1" {{ old('high_contrast_mode', $kioskConfig->high_contrast_mode ?? false) ? 'checked' : '' }} style="width:1.15rem;height:1.15rem">
                                <div><div style="font-weight:700;color:#0f172a;font-size:0.88rem">High Contrast Mode</div><small style="color:#64748b">Better accessibility</small></div>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Security & Access -->
            <div class="table-card">
                <div class="section-head-modern" style="background:#fffbeb;border-color:#fde68a"><div class="d-flex align-items-center gap-3"><div class="head-icon" style="background:#f59e0b!important;color:#fff!important;border-color:#f59e0b!important"><i class="fas fa-shield-halved"></i></div><div><h5 style="color:#92400e!important">Security & Access</h5><p style="color:#b45309!important">Token · URL · QR code</p></div></div></div>
                @if($kioskConfig && $kioskConfig->kiosk_token)
                    <div class="p-3 mb-3" style="background:#eff6ff;border:1px solid #dbeafe;border-radius:10px">
                        <div class="d-flex align-items-center gap-2 mb-2"><i class="fas fa-link" style="color:#2563eb"></i><strong style="font-size:0.82rem;color:#1e40af">Kiosk Access URL</strong><span class="badge ms-auto" style="background:#dbeafe;color:#1e40af;border:1px solid #bfdbfe;border-radius:99px;font-size:0.68rem">Secure</span></div>
                        <code style="word-break:break-all;background:#fff;border:1px solid #dbeafe;border-radius:8px;padding:0.6rem 0.8rem;display:block;font-size:0.78rem;color:#1e293b">{{ route('kiosk.welcome') }}?token={{ $kioskConfig->kiosk_token }}&doctor={{ auth()->id() }}</code>
                        <small style="color:#64748b;font-size:0.76rem">Share this URL or print QR for kiosk placement</small>
                    </div>
                    <div class="row g-2">
                        <div class="col-md-6"><button type="button" class="doctor-btn doctor-btn-outline" style="width:100%;justify-content:center" onclick="regenerateToken()"><i class="fas fa-key me-2"></i>Regenerate Token</button></div>
                        <div class="col-md-6"><button type="button" class="doctor-btn doctor-btn-primary" style="width:100%;justify-content:center;background:#1e293b;border-color:#1e293b" onclick="generateQRCode()"><i class="fas fa-qrcode me-2"></i>Generate QR Code</button></div>
                    </div>
                @else
                    <div class="text-center py-3" style="background:#fffbeb;border:1px dashed #fde68a;border-radius:10px"><i class="fas fa-triangle-exclamation mb-2" style="color:#d97706;font-size:1.4rem"></i><p class="mb-0" style="font-weight:600;color:#92400e">Save configuration to generate secure URL & token</p></div>
                @endif
            </div>

            <!-- Submit -->
            <div class="table-card">
                <div class="section-head-modern" style="background:#f8fafc;border-color:#e2e8f0"><div class="d-flex align-items-center gap-3"><div class="head-icon" style="background:#fff!important;color:#64748b!important;border-color:#e2e8f0!important"><i class="fas fa-floppy-disk"></i></div><div><h5 style="color:#0f172a!important">Save Configuration</h5><p style="color:#475569!important">Confirm and apply</p></div></div></div>
                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('dashboard') }}" class="btn" style="background:#fff;border:1px solid #e2e8f0;color:#475569;border-radius:10px;padding:0.6rem 1.2rem;font-weight:600"><i class="fas fa-arrow-left me-1"></i>Back to Dashboard</a>
                    <button type="submit" class="btn" style="background:#1e293b;color:#fff;border-radius:10px;padding:0.6rem 1.4rem;font-weight:700;border:1px solid #1e293b"><i class="fas fa-save me-2"></i>Save Kiosk Configuration</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function(){
  const p=document.getElementById('primary_color'), pt=document.getElementById('primary_color_text');
  const s=document.getElementById('secondary_color'), st=document.getElementById('secondary_color_text');
  if(p&&pt){ p.addEventListener('input',()=>pt.value=p.value.toUpperCase()); pt.value=p.value.toUpperCase(); }
  if(s&&st){ s.addEventListener('input',()=>st.value=s.value.toUpperCase()); st.value=s.value.toUpperCase(); }
});
function regenerateToken(){
  if(!confirm('Regenerate kiosk access token? Current URL will be invalidated.')) return;
  const btn=event.target.closest('button'); const orig=btn.innerHTML; btn.disabled=true; btn.innerHTML='<i class="fas fa-spinner fa-spin me-1"></i>Regenerating...';
  fetch('{{ route('doctor.kiosk.regenerate-token') }}',{method:'POST',headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json','Content-Type':'application/json'}}).then(r=>{if(!r.ok)throw new Error('Network error');return r.json();}).then(d=>{
    if(d.success&&d.new_token){ showNotification('Token regenerated!','success'); setTimeout(()=>location.reload(),1200);} else showNotification(d.message||'Error','error');
  }).catch(()=>showNotification('Error regenerating token','error')).finally(()=>{btn.disabled=false; btn.innerHTML=orig;});
}
function generateQRCode(){
  const token='{{ $kioskConfig->kiosk_token ?? '' }}';
  if(!token){ showNotification('Save configuration first to get token','warning'); return; }
  const url='{{ route('kiosk.welcome') }}?token='+token+'&doctor={{ auth()->id() }}';
  const qr=`https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=${encodeURIComponent(url)}`;
  const w=window.open(qr,'_blank','width=350,height=350'); if(!w) showNotification('Allow popups to view QR','warning');
}
function showNotification(msg,type='info'){
  document.querySelectorAll('.custom-notification').forEach(el=>el.remove());
  const n=document.createElement('div'); n.className=`alert alert-${type} alert-dismissible custom-notification`;
  n.style.cssText='position:fixed;top:16px;right:16px;z-index:9999;min-width:300px;box-shadow:0 8px 20px rgba(0,0,0,0.12);border-radius:10px';
  n.innerHTML=`<i class="fas fa-${type==='success'?'check-circle':type==='error'?'exclamation-triangle':'info-circle'} me-2"></i>${msg}<button type="button" class="btn-close" data-bs-dismiss="alert"></button>`;
  document.body.appendChild(n); setTimeout(()=>{ if(n.parentNode) n.remove(); },4000);
}
</script>
@endsection
