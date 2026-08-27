@extends('layouts.admin')
@section('title','Kiosks')
@section('content')
<div class="container-fluid px-4 py-4">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4" style="background:linear-gradient(135deg,#1e293b 0%,#334155 100%);border-radius:16px;padding:1.4rem 1.6rem;box-shadow:0 8px 24px rgba(15,23,42,0.12)">
        <div class="d-flex align-items-center gap-3">
            <div style="width:44px;height:44px;border-radius:12px;background:rgba(255,255,255,0.14);display:flex;align-items:center;justify-content:center"><i class="fas fa-desktop" style="color:#fff;font-size:1.1rem"></i></div>
            <div>
                <h1 style="font-size:1.35rem;font-weight:800;color:#fff;letter-spacing:-0.02em;margin:0">Kiosks</h1>
                <p style="font-size:0.78rem;color:rgba(255,255,255,0.75);margin:2px 0 0">{{ $kiosks->count() }} total · {{ $kiosks->where('status','active')->count() }} active · {{ $kiosks->filter->isOnline()->count() }} online</p>
            </div>
        </div>
        <a href="{{ route('admin.kiosks.create') }}" class="btn btn-sm text-white" style="background:rgba(255,255,255,0.14);border:1px solid rgba(255,255,255,0.18);color:#fff;border-radius:10px;font-weight:700"><i class="fas fa-plus me-1"></i> Add Kiosk</a>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-2"><div class="card border-0 shadow-sm" style="border-radius:14px"><div class="card-body p-3 text-center"><div style="width:42px;height:42px;border-radius:12px;background:#eff6ff;border:1px solid #dbeafe;color:#2563eb;display:flex;align-items:center;justify-content:center;margin:0 auto 8px"><i class="fas fa-desktop"></i></div><div style="font-size:1.5rem;font-weight:800;color:#0f172a" id="totalKiosks">{{ $kiosks->count() }}</div><div style="font-size:.68rem;color:#64748b;font-weight:700;letter-spacing:.05em;text-transform:uppercase">Total</div></div></div></div>
        <div class="col-6 col-lg-2"><div class="card border-0 shadow-sm" style="border-radius:14px"><div class="card-body p-3 text-center"><div style="width:42px;height:42px;border-radius:12px;background:#ecfdf5;border:1px solid #a7f3d0;color:#059669;display:flex;align-items:center;justify-content:center;margin:0 auto 8px"><i class="fas fa-check-circle"></i></div><div style="font-size:1.5rem;font-weight:800;color:#0f172a" id="activeKiosks">{{ $kiosks->where('status','active')->count() }}</div><div style="font-size:.68rem;color:#64748b;font-weight:700;letter-spacing:.05em;text-transform:uppercase">Active</div></div></div></div>
        <div class="col-6 col-lg-2"><div class="card border-0 shadow-sm" style="border-radius:14px"><div class="card-body p-3 text-center"><div style="width:42px;height:42px;border-radius:12px;background:#f0fdf5;border:1px solid #a7f3d0;color:#059669;display:flex;align-items:center;justify-content:center;margin:0 auto 8px"><i class="fas fa-wifi"></i></div><div style="font-size:1.5rem;font-weight:800;color:#0f172a" id="onlineKiosks">{{ $kiosks->filter->isOnline()->count() }}</div><div style="font-size:.68rem;color:#64748b;font-weight:700;letter-spacing:.05em;text-transform:uppercase">Online</div></div></div></div>
        <div class="col-6 col-lg-2"><div class="card border-0 shadow-sm" style="border-radius:14px"><div class="card-body p-3 text-center"><div style="width:42px;height:42px;border-radius:12px;background:#fef2f2;border:1px solid #fecaca;color:#dc2626;display:flex;align-items:center;justify-content:center;margin:0 auto 8px"><i class="fas fa-wifi" style="opacity:.5"></i></div><div style="font-size:1.5rem;font-weight:800;color:#0f172a" id="offlineKiosks">{{ $kiosks->filter(function($k){return !$k->isOnline();})->count() }}</div><div style="font-size:.68rem;color:#64748b;font-weight:700;letter-spacing:.05em;text-transform:uppercase">Offline</div></div></div></div>
        <div class="col-6 col-lg-2"><div class="card border-0 shadow-sm" style="border-radius:14px"><div class="card-body p-3 text-center"><div style="width:42px;height:42px;border-radius:12px;background:#f5f3ff;border:1px solid #ddd6fe;color:#7c3aed;display:flex;align-items:center;justify-content:center;margin:0 auto 8px"><i class="fas fa-clock"></i></div><div style="font-size:1.5rem;font-weight:800;color:#0f172a" id="totalSessions">—</div><div style="font-size:.68rem;color:#64748b;font-weight:700;letter-spacing:.05em;text-transform:uppercase">Today Sessions</div></div></div></div>
        <div class="col-6 col-lg-2"><div class="card border-0 shadow-sm" style="border-radius:14px"><div class="card-body p-3 text-center"><div style="width:42px;height:42px;border-radius:12px;background:#fffbeb;border:1px solid #fde68a;color:#d97706;display:flex;align-items:center;justify-content:center;margin:0 auto 8px"><i class="fas fa-dollar-sign"></i></div><div style="font-size:1.5rem;font-weight:800;color:#0f172a" id="totalRevenue">$0</div><div style="font-size:.68rem;color:#64748b;font-weight:700;letter-spacing:.05em;text-transform:uppercase">Today Revenue</div></div></div></div>
    </div>

    <div class="card border-0 shadow-sm" style="border-radius:14px;overflow:hidden">
        <div class="card-body p-3" style="background:#fff">
            <div class="row g-2 align-items-end">
                <div class="col-md-6">
                    <label class="form-label fw-bold" style="font-size:.72rem;letter-spacing:.04em;text-transform:uppercase;color:#64748b">Search</label>
                    <div class="position-relative">
                        <i class="fas fa-search" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:.78rem"></i>
                        <input type="text" id="kioskSearch" class="form-control" placeholder="Search name, location, serial…" style="border-radius:10px;padding-left:34px;border:1px solid #e2e8f0;height:38px;font-size:.88rem" oninput="filterKiosks()">
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold" style="font-size:.72rem;letter-spacing:.04em;text-transform:uppercase;color:#64748b">Status</label>
                    <select id="kioskStatusFilter" class="form-select" style="border-radius:10px;border:1px solid #e2e8f0;height:38px;font-size:.88rem" onchange="filterKiosks()">
                        <option value="">All status</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button class="btn flex-grow-1 text-white" style="border-radius:10px;background:#0f172a;border:none;font-weight:700;height:38px" onclick="filterKiosks()"><i class="fas fa-filter me-1"></i> Filter</button>
                    <button class="btn btn-light border" style="border-radius:10px;font-weight:600;height:38px" onclick="document.getElementById('kioskSearch').value='';document.getElementById('kioskStatusFilter').value='';filterKiosks()">Reset</button>
                </div>
            </div>
        </div>
        <div class="table-responsive" style="border-top:1px solid #f1f5f9">
            <table class="table mb-0" style="font-size:.84rem" id="kiosksTable">
                <thead>
                    <tr style="background:#f8fafc">
                        <th style="padding:12px 16px;border:none;border-bottom:1px solid #e2e8f0;font-size:.68rem;font-weight:800;color:#475569;text-transform:uppercase;letter-spacing:.05em">#</th>
                        <th style="padding:12px 16px;border:none;border-bottom:1px solid #e2e8f0;font-size:.68rem;font-weight:800;color:#475569;text-transform:uppercase;letter-spacing:.05em">Kiosk</th>
                        <th style="padding:12px 16px;border:none;border-bottom:1px solid #e2e8f0;font-size:.68rem;font-weight:800;color:#475569;text-transform:uppercase;letter-spacing:.05em">Location</th>
                        <th style="padding:12px 16px;border:none;border-bottom:1px solid #e2e8f0;font-size:.68rem;font-weight:800;color:#475569;text-transform:uppercase;letter-spacing:.05em">Serial</th>
                        <th style="padding:12px 16px;border:none;border-bottom:1px solid #e2e8f0;font-size:.68rem;font-weight:800;color:#475569;text-transform:uppercase;letter-spacing:.05em">Status</th>
                        <th style="padding:12px 16px;border:none;border-bottom:1px solid #e2e8f0;font-size:.68rem;font-weight:800;color:#475569;text-transform:uppercase;letter-spacing:.05em">Online</th>
                        <th style="padding:12px 16px;border:none;border-bottom:1px solid #e2e8f0;font-size:.68rem;font-weight:800;color:#475569;text-transform:uppercase;letter-spacing:.05em">Last Ping</th>
                        <th style="padding:12px 16px;border:none;border-bottom:1px solid #e2e8f0;font-size:.68rem;font-weight:800;color:#475569;text-transform:uppercase;letter-spacing:.05em;text-align:right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($kiosks as $kiosk)
                        <tr data-kiosk-id="{{ $kiosk->id }}" data-name="{{ strtolower($kiosk->name) }}" data-location="{{ strtolower($kiosk->location ?? '') }}" data-serial="{{ strtolower($kiosk->serial_number) }}" data-status="{{ $kiosk->status }}">
                            <td style="padding:14px 16px;border-bottom:1px solid #f1f5f9"><span class="badge" style="background:#f1f5f9;color:#475569;border:1px solid #e2e8f0;border-radius:8px">#{{ $kiosk->id }}</span></td>
                            <td style="padding:14px 16px;border-bottom:1px solid #f1f5f9">
                                <div style="font-weight:700;color:#0f172a">{{ $kiosk->name }}</div>
                                @if($kiosk->configuration && isset($kiosk->configuration['software_version']))
                                    <small class="text-muted" style="font-size:.72rem">v{{ $kiosk->configuration['software_version'] }}</small>
                                @endif
                            </td>
                            <td style="padding:14px 16px;border-bottom:1px solid #f1f5f9"><span style="font-size:.82rem;color:#334155">{{ $kiosk->location ?? '—' }}</span></td>
                            <td style="padding:14px 16px;border-bottom:1px solid #f1f5f9"><code style="background:#f1f5f9;padding:2px 6px;border-radius:6px;font-size:.76rem;color:#475569">{{ $kiosk->serial_number }}</code></td>
                            <td style="padding:14px 16px;border-bottom:1px solid #f1f5f9"><span class="badge" style="border-radius:20px;font-size:.68rem;font-weight:700;padding:4px 10px;background:{{ $kiosk->isActive()?'#ecfdf5':'#f1f5f9' }};color:{{ $kiosk->isActive()?'#065f46':'#64748b' }};border:1px solid {{ $kiosk->isActive()?'#a7f3d0':'#e2e8f0' }}">{{ ucfirst($kiosk->status) }}</span></td>
                            <td style="padding:14px 16px;border-bottom:1px solid #f1f5f9">@if($kiosk->isOnline())<span class="badge" style="background:#ecfdf5;color:#065f46;border:1px solid #a7f3d0;border-radius:20px"><i class="fas fa-circle me-1" style="font-size:.5rem"></i> Online</span>@else<span class="badge" style="background:#fef2f2;color:#991b1b;border:1px solid #fecaca;border-radius:20px"><i class="fas fa-circle me-1" style="font-size:.5rem"></i> Offline</span>@endif</td>
                            <td style="padding:14px 16px;border-bottom:1px solid #f1f5f9"><span style="font-size:.78rem;color:#64748b" title="{{ $kiosk->last_ping?->format('M j, Y g:i A') }}">{{ $kiosk->last_ping?->diffForHumans() ?? 'Never' }}</span></td>
                            <td style="padding:14px 16px;border-bottom:1px solid #f1f5f9;text-align:right">
                                <div class="d-inline-flex gap-1">
                                    <a href="{{ route('admin.kiosks.show',$kiosk) }}" class="btn btn-light border btn-sm d-inline-flex align-items:center justify-content:center" style="border-radius:10px;width:32px;height:32px" title="View"><i class="fas fa-eye" style="font-size:.76rem"></i></a>
                                    <a href="{{ route('admin.kiosks.edit',$kiosk) }}" class="btn btn-light border btn-sm d-inline-flex align-items:center justify-content:center" style="border-radius:10px;width:32px;height:32px" title="Edit"><i class="fas fa-pen" style="font-size:.76rem"></i></a>
                                    <div class="dropdown">
                                        <button class="btn btn-light border btn-sm d-inline-flex align-items:center justify-content:center" data-bs-toggle="dropdown" style="border-radius:10px;width:32px;height:32px"><i class="fas fa-ellipsis-h" style="font-size:.72rem"></i></button>
                                        <ul class="dropdown-menu dropdown-menu-end shadow-sm" style="border-radius:12px;border:1px solid #e2e8f0">
                                            <li><a class="dropdown-item" href="#" onclick="event.preventDefault();sendCommand({{ $kiosk->id }},'restart')"><i class="fas fa-power-off me-2 text-warning"></i>Restart</a></li>
                                            <li><a class="dropdown-item" href="#" onclick="event.preventDefault();sendCommand({{ $kiosk->id }},'update')"><i class="fas fa-download me-2 text-info"></i>Update</a></li>
                                            <li><a class="dropdown-item" href="#" onclick="event.preventDefault();sendCommand({{ $kiosk->id }},'diagnostics')"><i class="fas fa-stethoscope me-2 text-primary"></i>Diagnostics</a></li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li><a class="dropdown-item text-danger" href="#" onclick="event.preventDefault();deleteKiosk({{ $kiosk->id }})"><i class="fas fa-trash me-2"></i>Delete</a></li>
                                        </ul>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center py-5"><div class="d-flex flex-column align-items-center gap-2"><div style="width:48px;height:48px;border-radius:12px;background:#f8fafc;border:1px dashed #e2e8f0;display:flex;align-items:center;justify-content:center;color:#94a3b8"><i class="fas fa-desktop"></i></div><span class="text-muted">No kiosks — create your first</span><a href="{{ route('admin.kiosks.create') }}" class="btn btn-sm text-white mt-1" style="background:#0f172a;border-radius:10px">Add Kiosk</a></div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@endsection
@push('scripts')
<script>
function filterKiosks(){
    const q=document.getElementById('kioskSearch')?.value.toLowerCase().trim()||'';
    const status=document.getElementById('kioskStatusFilter')?.value||'';
    document.querySelectorAll('#kiosksTable tbody tr[data-kiosk-id]').forEach(tr=>{
        const hay=(tr.dataset.name+' '+tr.dataset.location+' '+tr.dataset.serial).toLowerCase();
        const okQ=!q||hay.includes(q);
        const okS=!status||tr.dataset.status===status;
        tr.style.display=(okQ&&okS)?'':'none';
    });
}
let refreshTimer;
function startRealtime(){ refreshTimer=setInterval(()=>{ fetch('/admin/kiosks/statistics').then(r=>r.json()).then(d=>{ if(d.success){ document.getElementById('totalKiosks').textContent=d.data.total_kiosks; document.getElementById('activeKiosks').textContent=d.data.active_kiosks; document.getElementById('onlineKiosks').textContent=d.data.online_kiosks; document.getElementById('offlineKiosks').textContent=d.data.total_kiosks-d.data.online_kiosks; if(d.data.total_sessions_today!==undefined) document.getElementById('totalSessions').textContent=d.data.total_sessions_today; if(d.data.total_revenue_today!==undefined) document.getElementById('totalRevenue').textContent='$'+Number(d.data.total_revenue_today).toFixed(2);} }).catch(()=>{}); },30000); }
document.addEventListener('DOMContentLoaded', startRealtime);
window.addEventListener('beforeunload', ()=>clearInterval(refreshTimer));
function sendCommand(id,cmd){
    if(cmd==='shutdown' && !confirm('Shutdown this kiosk?')) return;
    const btn=event.target.closest('a'); const orig=btn.innerHTML; btn.innerHTML='<i class="fas fa-spinner fa-spin me-1"></i>Sending'; btn.style.pointerEvents='none';
    fetch(`/api/kiosks/${id}/command`,{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content},body:JSON.stringify({command:cmd})})
    .then(r=>r.json()).then(d=> showToast(d.success?`Command "${cmd}" sent`:`Failed: ${d.message}`, d.success?'success':'error'))
    .catch(()=>showToast('Error sending command','error')).finally(()=>{btn.innerHTML=orig;btn.style.pointerEvents='auto'});
}
function deleteKiosk(id){
    if(!confirm('Delete this kiosk? This cannot be undone.')) return;
    fetch(`/admin/kiosks/${id}`,{method:'DELETE',headers:{'X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content}})
    .then(r=>{ if(r.ok){ showToast('Kiosk deleted','success'); setTimeout(()=>location.reload(),800);} else showToast('Failed to delete','error'); })
    .catch(()=>showToast('Error deleting','error'));
}
function showToast(msg,type='info'){
    const div=document.createElement('div'); div.className=`toast-pro ${type}`; div.style.cssText='position:fixed;top:20px;right:20px;z-index:9999;min-width:300px;background:#fff;border:1px solid #eef2f7;border-left:4px solid '+(type==='success'?'#10b981':type==='error'?'#ef4444':'#0ea5e9')+';border-radius:14px;box-shadow:0 12px 32px rgba(15,23,42,.12);padding:12px 14px;display:flex;gap:10px';
    div.innerHTML=`<div class="t-icon" style="width:32px;height:32px;border-radius:10px;background:${type==='success'?'#ecfdf5':'#fef2f2'};color:${type==='success'?'#059669':'#dc2626'};display:flex;align-items:center;justify-content:center"><i class="fas fa-${type==='success'?'check':'exclamation-circle'}"></i></div><div style="flex:1"><div style="font-weight:800;font-size:.84rem">${type==='success'?'Success':'Error'}</div><div style="font-size:.82rem;color:#475569">${msg}</div></div><button onclick="this.parentElement.remove()" style="width:28px;height:28px;border-radius:8px;border:1px solid #e2e8f0;background:#fff">×</button>`;
    document.body.appendChild(div); setTimeout(()=>div.remove(),4000);
}
</script>
@endpush
