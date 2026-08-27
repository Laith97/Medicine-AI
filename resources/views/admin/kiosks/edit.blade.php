@extends('layouts.admin')
@section('title','Edit Kiosk — '.$kiosk->name)
@section('content')
<div class="container-fluid px-4 py-4">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4" style="background:linear-gradient(135deg,#1e293b 0%,#334155 100%);border-radius:16px;padding:1.4rem 1.6rem;box-shadow:0 8px 24px rgba(15,23,42,0.12)">
        <div class="d-flex align-items-center gap-3">
            <div style="width:44px;height:44px;border-radius:12px;background:rgba(255,255,255,0.14);display:flex;align-items:center;justify-content:center"><i class="fas fa-pen" style="color:#fff;font-size:1.1rem"></i></div>
            <div>
                <h1 style="font-size:1.35rem;font-weight:800;color:#fff;letter-spacing:-0.02em;margin:0">Edit Kiosk</h1>
                <p style="font-size:0.78rem;color:rgba(255,255,255,0.75);margin:2px 0 0">{{ $kiosk->name }} · {{ $kiosk->serial_number }}</p>
            </div>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.kiosks.show', $kiosk) }}" class="btn btn-sm" style="background:rgba(255,255,255,0.14);border:1px solid rgba(255,255,255,0.18);color:#fff;border-radius:10px;font-weight:700"><i class="fas fa-eye me-1"></i> View</a>
            <a href="{{ route('admin.kiosks.index') }}" class="btn btn-sm" style="background:rgba(255,255,255,0.12);border:1px solid rgba(255,255,255,0.18);color:#fff;border-radius:10px;font-weight:700"><i class="fas fa-arrow-left me-1"></i> Back</a>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm" style="border-radius:16px;overflow:hidden">
                <div class="card-body p-4">
                    <form id="editKioskForm" novalidate>
                        @csrf
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="name" class="form-label fw-bold" style="font-size:.78rem;color:#334155;text-transform:uppercase;letter-spacing:.04em">Kiosk Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="name" name="name" value="{{ $kiosk->name }}" required style="border-radius:10px;height:42px;border:1px solid #e2e8f0">
                            </div>
                            <div class="col-md-6">
                                <label for="serial_number" class="form-label fw-bold" style="font-size:.78rem;color:#334155;text-transform:uppercase;letter-spacing:.04em">Serial Number</label>
                                <input type="text" class="form-control" id="serial_number" name="serial_number" value="{{ $kiosk->serial_number }}" readonly style="border-radius:10px;height:42px;border:1px solid #e2e8f0;background:#f8fafc">
                                <div class="form-text" style="font-size:.72rem;color:#64748b">Cannot be changed</div>
                            </div>
                            <div class="col-12">
                                <label for="location" class="form-label fw-bold" style="font-size:.78rem;color:#334155;text-transform:uppercase;letter-spacing:.04em">Location</label>
                                <input type="text" class="form-control" id="location" name="location" value="{{ $kiosk->location }}" style="border-radius:10px;height:42px;border:1px solid #e2e8f0" placeholder="Hospital Lobby, Floor 1">
                            </div>
                            <div class="col-md-6">
                                <label for="status" class="form-label fw-bold" style="font-size:.78rem;color:#334155;text-transform:uppercase;letter-spacing:.04em">Status</label>
                                <select class="form-select" id="status" name="status" style="border-radius:10px;height:42px;border:1px solid #e2e8f0">
                                    <option value="active" {{ $kiosk->status==='active'?'selected':'' }}>Active</option>
                                    <option value="inactive" {{ $kiosk->status==='inactive'?'selected':'' }}>Inactive</option>
                                    <option value="maintenance" {{ $kiosk->status==='maintenance'?'selected':'' }}>Maintenance</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label for="configuration" class="form-label fw-bold" style="font-size:.78rem;color:#334155;text-transform:uppercase;letter-spacing:.04em">Configuration (JSON)</label>
                                <textarea class="form-control" id="configuration" name="configuration" rows="6" style="border-radius:12px;border:1px solid #e2e8f0;font-family:monospace;font-size:.82rem">{{ $kiosk->configuration ? json_encode($kiosk->configuration, JSON_PRETTY_PRINT) : '' }}</textarea>
                            </div>
                        </div>
                        <div class="row g-3 mt-1">
                            <div class="col-md-6"><div class="p-3" style="background:#f8fafc;border:1px solid #eef2f7;border-radius:12px"><div style="font-size:.68rem;font-weight:800;color:#64748b;text-transform:uppercase;letter-spacing:.05em">Last Ping</div><div style="font-weight:700;color:#0f172a">{{ $kiosk->last_ping?->format('M j, Y g:i A') ?? 'Never' }}</div><small class="text-muted">{{ $kiosk->last_ping?->diffForHumans() ?? '' }}</small></div></div>
                            <div class="col-md-6"><div class="p-3" style="background:#f8fafc;border:1px solid #eef2f7;border-radius:12px"><div style="font-size:.68rem;font-weight:800;color:#64748b;text-transform:uppercase;letter-spacing:.05em">Created</div><div style="font-weight:700;color:#0f172a">{{ $kiosk->created_at->format('M j, Y g:i A') }}</div></div></div>
                        </div>
                        <div class="d-flex justify-content-end gap-2 mt-4 pt-3" style="border-top:1px solid #f1f5f9">
                            <a href="{{ route('admin.kiosks.show', $kiosk) }}" class="btn btn-light border" style="border-radius:12px">Cancel</a>
                            <button type="submit" class="btn text-white" id="submitBtn" style="background:#0f172a;border-radius:12px;font-weight:700"><i class="fas fa-save me-1"></i> Update Kiosk</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
@push('scripts')
<script>
document.getElementById('editKioskForm')?.addEventListener('submit',function(e){
    e.preventDefault();
    const btn=document.getElementById('submitBtn'); const orig=btn.innerHTML; btn.disabled=true; btn.innerHTML='<i class="fas fa-spinner fa-spin me-1"></i> Updating...';
    const cfg=document.getElementById('configuration');
    if(cfg.value.trim()){ try{ JSON.parse(cfg.value); }catch(err){ alert('Invalid JSON'); btn.disabled=false; btn.innerHTML=orig; return; } }
    const fd=new FormData(this);
    fetch('{{ route('admin.kiosks.update', $kiosk) }}',{method:'POST',headers:{'X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content,'X-HTTP-Method-Override':'PUT'},body:fd})
    .then(r=>r.json()).then(d=>{ if(d.success){ window.location.href='{{ route('admin.kiosks.show', $kiosk) }}'; } else { alert(d.message||'Failed'); btn.disabled=false; btn.innerHTML=orig; } })
    .catch(()=>{ alert('Error'); btn.disabled=false; btn.innerHTML=orig; });
});
</script>
@endpush
