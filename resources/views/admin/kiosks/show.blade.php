@extends('layouts.admin')
@section('title','Kiosk — '.$kiosk->name)
@section('content')
<div class="container-fluid px-4 py-4">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4" style="background:linear-gradient(135deg,#1e293b 0%,#334155 100%);border-radius:16px;padding:1.4rem 1.6rem;box-shadow:0 8px 24px rgba(15,23,42,0.12)">
        <div class="d-flex align-items-center gap-3">
            <div style="width:44px;height:44px;border-radius:12px;background:rgba(255,255,255,0.14);display:flex;align-items:center;justify-content:center"><i class="fas fa-desktop" style="color:#fff;font-size:1.1rem"></i></div>
            <div>
                <h1 style="font-size:1.35rem;font-weight:800;color:#fff;letter-spacing:-0.02em;margin:0">{{ $kiosk->name }} <span class="badge" style="background:{{ $kiosk->isActive()?'#ecfdf5':'#f1f5f9' }};color:{{ $kiosk->isActive()?'#065f46':'#64748b' }};border:1px solid {{ $kiosk->isActive()?'#a7f3d0':'#e2e8f0' }};border-radius:20px;font-size:.68rem;vertical-align:middle">{{ ucfirst($kiosk->status) }}</span> @if($kiosk->isOnline())<span class="badge" style="background:#ecfdf5;color:#065f46;border:1px solid #a7f3d0;border-radius:20px;font-size:.68rem">Online</span>@else<span class="badge" style="background:#fef2f2;color:#991b1b;border:1px solid #fecaca;border-radius:20px;font-size:.68rem">Offline</span>@endif</h1>
                <p style="font-size:0.78rem;color:rgba(255,255,255,0.75);margin:2px 0 0">{{ $kiosk->serial_number }} · {{ $kiosk->location ?? 'No location' }} · Last ping {{ $kiosk->last_ping?->diffForHumans() ?? 'Never' }}</p>
            </div>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.kiosks.edit', $kiosk) }}" class="btn btn-sm text-white" style="background:rgba(255,255,255,0.14);border:1px solid rgba(255,255,255,0.18);border-radius:10px;font-weight:700"><i class="fas fa-pen me-1"></i> Edit</a>
            <a href="{{ route('admin.kiosks.index') }}" class="btn btn-sm" style="background:rgba(255,255,255,0.12);border:1px solid rgba(255,255,255,0.18);color:#fff;border-radius:10px;font-weight:700"><i class="fas fa-arrow-left me-1"></i> Back</a>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100" style="border-radius:14px">
                <div class="card-body p-4">
                    <div style="font-size:.68rem;font-weight:800;color:#64748b;text-transform:uppercase;letter-spacing:.05em">Serial Number</div>
                    <div style="font-weight:800;color:#0f172a;font-family:monospace">{{ $kiosk->serial_number }}</div>
                    <div class="mt-3" style="font-size:.68rem;font-weight:800;color:#64748b;text-transform:uppercase;letter-spacing:.05em">Location</div>
                    <div style="font-weight:600;color:#334155">{{ $kiosk->location ?? '—' }}</div>
                    <div class="mt-3 d-flex gap-2">
                        <span class="badge" style="background:{{ $kiosk->isActive()?'#ecfdf5':'#f1f5f9' }};color:{{ $kiosk->isActive()?'#065f46':'#64748b' }};border:1px solid {{ $kiosk->isActive()?'#a7f3d0':'#e2e8f0' }};border-radius:20px">{{ ucfirst($kiosk->status) }}</span>
                        @if($kiosk->isOnline())<span class="badge" style="background:#ecfdf5;color:#065f46;border:1px solid #a7f3d0;border-radius:20px">Online</span>@else<span class="badge" style="background:#fef2f2;color:#991b1b;border:1px solid #fecaca;border-radius:20px">Offline</span>@endif
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100" style="border-radius:14px">
                <div class="card-body p-4">
                    <div style="font-size:.68rem;font-weight:800;color:#64748b;text-transform:uppercase;letter-spacing:.05em">Last Ping</div>
                    <div style="font-weight:700;color:#0f172a">{{ $kiosk->last_ping?->format('M j, Y g:i A') ?? 'Never' }}</div>
                    <small class="text-muted" style="font-size:.74rem">{{ $kiosk->last_ping?->diffForHumans() ?? '' }}</small>
                    <div class="mt-3" style="font-size:.68rem;font-weight:800;color:#64748b;text-transform:uppercase;letter-spacing:.05em">Created</div>
                    <div style="font-size:.84rem;color:#334155">{{ $kiosk->created_at->format('M j, Y g:i A') }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100" style="border-radius:14px">
                <div class="card-body p-4">
                    <div style="font-size:.68rem;font-weight:800;color:#64748b;text-transform:uppercase;letter-spacing:.05em">Configuration</div>
                    @if($kiosk->configuration)
                        <pre style="background:#f8fafc;border:1px solid #eef2f7;border-radius:10px;padding:10px;font-size:.76rem;max-height:120px;overflow:auto">{{ json_encode($kiosk->configuration, JSON_PRETTY_PRINT) }}</pre>
                    @else
                        <p class="text-muted small mb-0">No configuration</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3"><div class="card border-0 shadow-sm" style="border-radius:14px"><div class="card-body p-3 text-center"><div style="font-size:1.5rem;font-weight:800;color:#0f172a">{{ $stats['total_sessions'] }}</div><div style="font-size:.68rem;color:#64748b;font-weight:700;letter-spacing:.05em;text-transform:uppercase">Total Sessions</div></div></div></div>
        <div class="col-6 col-lg-3"><div class="card border-0 shadow-sm" style="border-radius:14px"><div class="card-body p-3 text-center"><div style="font-size:1.5rem;font-weight:800;color:#0f172a">{{ $stats['active_sessions'] }}</div><div style="font-size:.68rem;color:#64748b;font-weight:700;letter-spacing:.05em;text-transform:uppercase">Active Sessions</div></div></div></div>
        <div class="col-6 col-lg-3"><div class="card border-0 shadow-sm" style="border-radius:14px"><div class="card-body p-3 text-center"><div style="font-size:1.5rem;font-weight:800;color:#0f172a">{{ $stats['total_checkins'] }}</div><div style="font-size:.68rem;color:#64748b;font-weight:700;letter-spacing:.05em;text-transform:uppercase">Total Check-ins</div></div></div></div>
        <div class="col-6 col-lg-3"><div class="card border-0 shadow-sm" style="border-radius:14px"><div class="card-body p-3 text-center"><div style="font-size:1.5rem;font-weight:800;color:#059669">${{ number_format($stats['total_revenue'],2) }}</div><div style="font-size:.68rem;color:#64748b;font-weight:700;letter-spacing:.05em;text-transform:uppercase">Total Revenue</div></div></div></div>
    </div>

    <div class="card border-0 shadow-sm mb-4" style="border-radius:14px;overflow:hidden">
        <div class="card-header bg-white" style="padding:1rem 1.25rem;border-bottom:1px solid #eef2f7"><h6 class="mb-0" style="font-weight:800;color:#0f172a;font-size:.95rem"><i class="fas fa-clock me-2" style="color:#64748b"></i>Recent Sessions</h6></div>
        <div class="table-responsive">
            <table class="table mb-0" style="font-size:.84rem">
                <thead><tr style="background:#f8fafc"><th style="padding:12px 16px;border:none;border-bottom:1px solid #e2e8f0;font-size:.68rem;font-weight:800;color:#475569;text-transform:uppercase;letter-spacing:.05em">Session ID</th><th style="padding:12px 16px;border:none;border-bottom:1px solid #e2e8f0;font-size:.68rem;font-weight:800;color:#475569;text-transform:uppercase;letter-spacing:.05em">Start</th><th style="padding:12px 16px;border:none;border-bottom:1px solid #e2e8f0;font-size:.68rem;font-weight:800;color:#475569;text-transform:uppercase;letter-spacing:.05em">Duration</th><th style="padding:12px 16px;border:none;border-bottom:1px solid #e2e8f0;font-size:.68rem;font-weight:800;color:#475569;text-transform:uppercase;letter-spacing:.05em">Status</th><th style="padding:12px 16px;border:none;border-bottom:1px solid #e2e8f0;font-size:.68rem;font-weight:800;color:#475569;text-transform:uppercase;letter-spacing:.05em">Check-ins</th><th style="padding:12px 16px;border:none;border-bottom:1px solid #e2e8f0;font-size:.68rem;font-weight:800;color:#475569;text-transform:uppercase;letter-spacing:.05em">Payments</th></tr></thead>
                <tbody>
                    @forelse($kiosk->sessions as $session)
                        <tr>
                            <td style="padding:12px 16px;border-bottom:1px solid #f1f5f9"><code style="background:#f1f5f9;padding:2px 6px;border-radius:6px;font-size:.74rem">{{ Str::limit($session->session_id,16) }}</code></td>
                            <td style="padding:12px 16px;border-bottom:1px solid #f1f5f9"><span style="font-size:.82rem;color:#334155">{{ $session->start_time->format('M j, g:i A') }}</span></td>
                            <td style="padding:12px 16px;border-bottom:1px solid #f1f5f9"><span style="font-size:.82rem;color:#334155">{{ $session->getDurationInMinutes() }} min</span></td>
                            <td style="padding:12px 16px;border-bottom:1px solid #f1f5f9"><span class="badge" style="border-radius:20px;background:{{ $session->status==='active'?'#ecfdf5':'#f1f5f9' }};color:{{ $session->status==='active'?'#065f46':'#64748b' }};border:1px solid {{ $session->status==='active'?'#a7f3d0':'#e2e8f0' }}">{{ ucfirst($session->status) }}</span></td>
                            <td style="padding:12px 16px;border-bottom:1px solid #f1f5f9">{{ $session->checkins->count() }}</td>
                            <td style="padding:12px 16px;border-bottom:1px solid #f1f5f9">{{ $session->payments->count() }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center py-4 text-muted">No sessions</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card border-0 shadow-sm" style="border-radius:14px;overflow:hidden">
        <div class="card-header bg-white" style="padding:1rem 1.25rem;border-bottom:1px solid #eef2f7"><h6 class="mb-0" style="font-weight:800;color:#0f172a;font-size:.95rem"><i class="fas fa-gamepad me-2" style="color:#7c3aed"></i>Kiosk Control</h6></div>
        <div class="card-body p-4">
            <div class="row g-2">
                <div class="col-6 col-md-3"><button class="btn w-100" style="background:#ecfdf5;border:1px solid #a7f3d0;color:#065f46;border-radius:12px;font-weight:700;padding:10px" onclick="sendCommand('restart')"><i class="fas fa-power-off me-1"></i> Restart</button></div>
                <div class="col-6 col-md-3"><button class="btn w-100" style="background:#fffbeb;border:1px solid #fde68a;color:#92400e;border-radius:12px;font-weight:700;padding:10px" onclick="sendCommand('update')"><i class="fas fa-download me-1"></i> Update</button></div>
                <div class="col-6 col-md-3"><button class="btn w-100" style="background:#eff6ff;border:1px solid #dbeafe;color:#2563eb;border-radius:12px;font-weight:700;padding:10px" onclick="sendCommand('diagnostics')"><i class="fas fa-stethoscope me-1"></i> Diagnostics</button></div>
                <div class="col-6 col-md-3"><button class="btn w-100" style="background:#fef2f2;border:1px solid #fecaca;color:#991b1b;border-radius:12px;font-weight:700;padding:10px" onclick="sendCommand('shutdown')"><i class="fas fa-stop me-1"></i> Shutdown</button></div>
            </div>
            <div id="commandStatus" class="alert mt-3" style="display:none;border-radius:12px"></div>
        </div>
    </div>
</div>
@endsection
@push('scripts')
<script>
function sendCommand(cmd){
    if(cmd==='shutdown' && !confirm('Shutdown this kiosk?')) return;
    const el=document.getElementById('commandStatus'); el.style.display='block'; el.className='alert alert-info'; el.style.borderRadius='12px'; el.textContent='Sending '+cmd+'…';
    fetch(`/api/kiosks/{{ $kiosk->id }}/command`,{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content},body:JSON.stringify({command:cmd})})
    .then(r=>r.json()).then(d=>{
        el.className='alert '+(d.success?'alert-success':'alert-danger'); el.style.borderRadius='12px'; el.textContent=d.success?`Command "${cmd}" sent`:`Failed: ${d.message}`;
    }).catch(()=>{ el.className='alert alert-danger'; el.textContent='Error sending command'; });
}
</script>
@endpush
