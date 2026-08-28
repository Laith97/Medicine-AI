@extends('master')

@section('title', 'Kiosk Management - Doctor Dashboard')

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
.section-head-modern{display:flex;align-items:center;justify-content:space-between;gap:0.75rem;margin:-1.3rem -1.3rem 1.1rem -1.3rem;padding:1rem 1.3rem;background:#f8fafc;border-bottom:1px solid #e2e8f0;border-radius:12px 12px 0 0;flex-wrap:wrap}
.section-head-modern .head-left{display:flex;align-items:center;gap:0.75rem}
.section-head-modern .head-icon{width:38px;height:38px;border-radius:9px;display:flex;align-items:center;justify-content:center;font-size:0.95rem;flex-shrink:0;background:#1e293b!important;color:#fff!important;border:1px solid #1e293b!important}
.section-head-modern h5{color:#0f172a!important;font-weight:800!important;letter-spacing:-0.01em;margin:0;font-size:1rem}
.section-head-modern p{color:#475569!important;font-size:0.78rem;margin:2px 0 0;font-weight:500}
</style>
@endpush

@section('content')
<div class="container-fluid" style="background-color: var(--bg-secondary, #f8f9fa);">
    <div class="container py-4">
        <div class="dashboard-header cases-header-compact">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <h2><i class="fas fa-cogs me-2"></i>Kiosk Management Dashboard</h2>
                    <p>Manage kiosk settings, sessions and status</p>
                </div>
                <a href="{{ route('doctor.kiosk.setup') }}" class="doctor-btn doctor-btn-primary doctor-btn-sm" style="background:#fff;color:#1e293b;border-color:#fff"><i class="fas fa-cog me-1"></i> Setup Configuration</a>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2" style="border-radius:10px;background:#ecfdf5;border:1px solid #a7f3d0;color:#065f46"><i class="fas fa-check-circle"></i><div>{{ session('success') }}</div><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        @endif
        @if(session('warning'))
            <div class="alert alert-warning alert-dismissible fade show" style="border-radius:10px;background:#fffbeb;border:1px solid #fde68a;color:#92400e"><i class="fas fa-exclamation-triangle me-2"></i>{{ session('warning') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        @endif
        @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" style="border-radius:10px"><h6 style="font-weight:800"><i class="fas fa-exclamation-triangle me-1"></i>Please correct errors:</h6><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        @endif

        <!-- Stats like appointments/index -->
        <div class="row g-2 mb-3 cases-stats-compact">
            <div class="col-6 col-lg-3">
                <div class="stats-card stats-card--compact">
                    <div class="stats-icon stats-icon--sm" style="background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%);"><i class="fas fa-calendar-day"></i></div>
                    <div class="stats-text"><p class="stats-number">{{ $stats['today_sessions'] ?? 0 }}</p><p class="stats-label">Today Sessions</p></div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="stats-card stats-card--compact">
                    <div class="stats-icon stats-icon--sm" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);"><i class="fas fa-users"></i></div>
                    <div class="stats-text"><p class="stats-number">{{ $stats['total_sessions'] ?? 0 }}</p><p class="stats-label">Total Sessions</p></div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="stats-card stats-card--compact">
                    <div class="stats-icon stats-icon--sm" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);"><i class="fas fa-calendar-check"></i></div>
                    <div class="stats-text"><p class="stats-number">{{ $stats['appointments_created'] ?? 0 }}</p><p class="stats-label">Appointments</p></div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="stats-card stats-card--compact">
                    <div class="stats-icon stats-icon--sm" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);"><i class="fas fa-credit-card"></i></div>
                    <div class="stats-text"><p class="stats-number">{{ $stats['payments_processed'] ?? 0 }}</p><p class="stats-label">Payments</p></div>
                </div>
            </div>
        </div>

        @if($kioskConfig)
        <!-- Configuration Summary -->
        <div class="table-card">
            <div class="section-head-modern"><div class="head-left"><div class="head-icon"><i class="fas fa-hospital"></i></div><div><h5>Kiosk Configuration</h5><p>Clinic · theme · automation</p></div></div>
            <a href="{{ route('doctor.kiosk.setup') }}" class="doctor-btn doctor-btn-outline doctor-btn-sm"><i class="fas fa-pen me-1"></i>Edit</a></div>
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="p-3" style="background:#f8fafc;border:1px solid #eef2f7;border-radius:10px">
                        <small style="font-size:0.70rem;font-weight:700;letter-spacing:0.06em;color:#64748b">CLINIC</small>
                        <div style="font-weight:700;color:#0f172a;margin-top:0.2rem">{{ $kioskConfig->clinic_name }}</div>
                        <div style="font-size:0.82rem;color:#475569">{{ $kioskConfig->clinic_address }}</div>
                        <div class="mt-2 d-flex align-items-center gap-2"><i class="fas fa-phone" style="color:#64748b;font-size:0.8rem"></i><span style="font-size:0.84rem;color:#334155">{{ $kioskConfig->contact_phone }}</span></div>
                        <div style="font-size:0.82rem;color:#64748b;margin-top:0.4rem"><i class="fas fa-desktop me-1"></i>{{ $kioskConfig->kiosk_display_name }}</div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="p-3" style="background:#fff;border:1px solid #eef2f7;border-radius:10px">
                        <div class="d-flex gap-3 mb-3">
                            <div class="d-flex align-items-center gap-2"><span class="color-preview" style="width:22px;height:22px;background:{{ $kioskConfig->primary_color }};border:1px solid #e2e8f0;border-radius:6px;display:inline-block"></span><span style="font-size:0.82rem;font-weight:600;color:#334155">{{ $kioskConfig->primary_color }}</span><small style="color:#64748b">Primary</small></div>
                            <div class="d-flex align-items-center gap-2"><span style="width:22px;height:22px;background:{{ $kioskConfig->secondary_color }};border:1px solid #e2e8f0;border-radius:6px;display:inline-block"></span><span style="font-size:0.82rem;font-weight:600;color:#334155">{{ $kioskConfig->secondary_color }}</span><small style="color:#64748b">Secondary</small></div>
                        </div>
                        <div class="d-flex flex-column gap-2" style="font-size:0.84rem">
                            <div class="d-flex justify-content-between"><span style="color:#64748b">Auto-approve</span><span class="badge" style="background:{{ $kioskConfig->auto_approve_appointments ? '#d1fae5;color:#065f46;border:1px solid #a7f3d0' : '#f1f5f9;color:#475569;border:1px solid #e2e8f0' }};border-radius:99px">{{ $kioskConfig->auto_approve_appointments ? 'Yes' : 'No' }}</span></div>
                            <div class="d-flex justify-content-between"><span style="color:#64748b">Payment upfront</span><span class="badge" style="background:{{ $kioskConfig->require_payment_upfront ? '#d1fae5;color:#065f46;border:1px solid #a7f3d0' : '#f1f5f9;color:#475569;border:1px solid #e2e8f0' }};border-radius:99px">{{ $kioskConfig->require_payment_upfront ? 'Yes' : 'No' }}</span></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Security -->
        <div class="table-card">
            <div class="section-head-modern" style="background:#fffbeb;border-color:#fde68a"><div class="head-left"><div class="head-icon" style="background:#f59e0b!important;color:#fff!important;border-color:#f59e0b!important"><i class="fas fa-shield-halved"></i></div><div><h5 style="color:#92400e!important">Security & Access</h5><p style="color:#b45309!important">Token · URL · QR</p></div></div></div>
            <div class="p-3 mb-3" style="background:#eff6ff;border:1px solid #dbeafe;border-radius:10px">
                <div class="d-flex align-items-center gap-2 mb-2"><i class="fas fa-link" style="color:#2563eb"></i><strong style="font-size:0.82rem;color:#1e40af">Kiosk Access URL</strong></div>
                <code style="word-break:break-all;background:#fff;border:1px solid #dbeafe;border-radius:8px;padding:0.6rem 0.8rem;display:block;font-size:0.78rem;color:#1e293b">{{ route('kiosk.welcome') }}?token={{ $kioskConfig->kiosk_token }}&doctor={{ $kioskConfig->doctor_id }}</code>
            </div>
            <div class="row g-2">
                <div class="col-md-6"><button type="button" class="doctor-btn doctor-btn-outline" style="width:100%;justify-content:center" onclick="regenerateToken()"><i class="fas fa-key me-2"></i>Regenerate Token</button></div>
                <div class="col-md-6"><button type="button" class="doctor-btn doctor-btn-primary" style="width:100%;justify-content:center;background:#1e293b;border-color:#1e293b" onclick="generateQRCode()"><i class="fas fa-qrcode me-2"></i>Generate QR</button></div>
            </div>
        </div>
        @else
            <div class="table-card text-center py-5">
                <div class="d-inline-flex align-items-center justify-content-center rounded-3 mb-3" style="width:56px;height:56px;background:#fffbeb;border:1px solid #fde68a;color:#d97706"><i class="fas fa-triangle-exclamation" style="font-size:1.4rem"></i></div>
                <h5 style="font-weight:800;color:#92400e">Kiosk not configured yet</h5>
                <p style="color:#64748b">Please set up your kiosk configuration first</p>
                <a href="{{ route('doctor.kiosk.setup') }}" class="doctor-btn doctor-btn-primary"><i class="fas fa-cog me-1"></i> Setup Kiosk</a>
            </div>
        @endif

        <!-- Recent Sessions -->
        <div class="table-card">
            <div class="section-head-modern"><div class="head-left"><div class="head-icon"><i class="fas fa-clock-rotate-left"></i></div><div><h5>Recent Kiosk Sessions</h5><p>Last activity</p></div></div></div>
            <div class="doctor-table-container" style="background:#fff;margin:-0.2rem -1.3rem -1.3rem -1.3rem;border-radius:0 0 12px 12px;overflow:hidden">
                <div class="table-responsive">
                    <table class="doctor-table table-hover mb-0" style="width:100%">
                        <thead style="background: linear-gradient(135deg, #f8f9fa 0%, #f1f5f9 100%);">
                            <tr>
                                <th style="font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#64748b;padding:0.9rem 1rem;border-bottom:2px solid #e2e8f0">Kiosk ID</th>
                                <th style="font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#64748b;padding:0.9rem 1rem;border-bottom:2px solid #e2e8f0;white-space:nowrap"><i class="far fa-calendar me-1 opacity-60"></i>Started</th>
                                <th style="font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#64748b;padding:0.9rem 1rem;border-bottom:2px solid #e2e8f0">Duration</th>
                                <th style="font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#64748b;padding:0.9rem 1rem;border-bottom:2px solid #e2e8f0">Status</th>
                                <th class="text-end" style="font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#64748b;padding:0.9rem 1rem;border-bottom:2px solid #e2e8f0">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if($recentSessions && $recentSessions->count() > 0)
                                @foreach($recentSessions as $session)
                                    <tr>
                                        <td><span style="font-family:ui-monospace;font-size:0.82rem;color:#334155">{{ $session->kiosk_id }}</span></td>
                                        <td><small style="color:#334155">{{ $session->created_at ? \Carbon\Carbon::parse($session->created_at)->format('M d, Y g:i A') : 'N/A' }}</small><br><small style="color:#94a3b8">@if($session->ended_at) Ended {{ \Carbon\Carbon::parse($session->ended_at)->format('g:i A') }} @endif</small></td>
                                        <td>@if($session->ended_at && $session->created_at) <span class="badge" style="background:#f1f5f9;color:#475569;border:1px solid #e2e8f0;border-radius:99px">{{ \Carbon\Carbon::parse($session->created_at)->diffInMinutes(\Carbon\Carbon::parse($session->ended_at)) }} min</span> @else <span class="badge" style="background:#fef3c7;color:#92400e;border:1px solid #fde68a;border-radius:99px">Active</span> @endif</td>
                                        <td><span class="doctor-badge {{ $session->ended_at ? 'doctor-badge-success' : 'doctor-badge-warning' }}">{{ $session->ended_at ? 'Completed' : 'Active' }}</span></td>
                                        <td class="text-end"><a href="#" class="doctor-btn doctor-btn-outline doctor-btn-sm" title="View"><i class="fas fa-eye"></i></a></td>
                                    </tr>
                                @endforeach
                            @else
                                <tr><td colspan="5" class="text-center py-4" style="color:#64748b">No recent kiosk sessions</td></tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function regenerateToken(){
  if(!confirm('Regenerate kiosk access token? Current URL will be invalidated.')) return;
  const btn=event.target.closest('button'); const orig=btn.innerHTML; btn.disabled=true; btn.innerHTML='<i class="fas fa-spinner fa-spin me-1"></i>Regenerating...';
  fetch('{{ route('doctor.kiosk.regenerate-token') }}',{method:'POST',headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json','Content-Type':'application/json'}}).then(r=>{if(!r.ok)throw new Error();return r.json();}).then(d=>{
    if(d.success&&d.new_token){ showNotification('Token regenerated!','success'); setTimeout(()=>location.reload(),1200);} else showNotification(d.message||'Error','error');
  }).catch(()=>showNotification('Error regenerating token','error')).finally(()=>{btn.disabled=false; btn.innerHTML=orig;});
}
function generateQRCode(){
  const token='{{ $kioskConfig->kiosk_token ?? "" }}';
  if(!token){ showNotification('Save configuration first','warning'); return; }
  const url='{{ route('kiosk.welcome') }}?token='+token+'&doctor={{ $kioskConfig->doctor_id ?? auth()->id() }}';
  const qr=`https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=${encodeURIComponent(url)}`;
  const w=window.open(qr,'_blank','width=350,height=350'); if(!w) showNotification('Allow popups to view QR','warning');
}
function showNotification(msg,type='info'){
  document.querySelectorAll('.custom-notification').forEach(el=>el.remove());
  const n=document.createElement('div'); n.className=`alert alert-${type} custom-notification`;
  n.style.cssText='position:fixed;top:16px;right:16px;z-index:9999;min-width:300px;box-shadow:0 8px 20px rgba(0,0,0,0.12);border-radius:10px';
  n.innerHTML=`<i class="fas fa-${type==='success'?'check-circle':type==='error'?'exclamation-triangle':'info-circle'} me-2"></i>${msg}`;
  document.body.appendChild(n); setTimeout(()=>{ if(n.parentNode) n.remove(); },4000);
}
</script>
@endsection
