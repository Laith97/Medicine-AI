@extends('layouts.admin')
@section('title','Add Kiosk')
@section('content')
<div class="container-fluid px-4 py-4">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4" style="background:linear-gradient(135deg,#1e293b 0%,#334155 100%);border-radius:16px;padding:1.4rem 1.6rem;box-shadow:0 8px 24px rgba(15,23,42,0.12)">
        <div class="d-flex align-items-center gap-3">
            <div style="width:44px;height:44px;border-radius:12px;background:rgba(255,255,255,0.14);display:flex;align-items:center;justify-content:center"><i class="fas fa-plus" style="color:#fff;font-size:1.1rem"></i></div>
            <div>
                <h1 style="font-size:1.35rem;font-weight:800;color:#fff;letter-spacing:-0.02em;margin:0">Add Kiosk</h1>
                <p style="font-size:0.78rem;color:rgba(255,255,255,0.75);margin:2px 0 0">Create a new kiosk for patient check-in and payments</p>
            </div>
        </div>
        <a href="{{ route('admin.kiosks.index') }}" class="btn btn-sm" style="background:rgba(255,255,255,0.12);border:1px solid rgba(255,255,255,0.18);color:#fff;border-radius:10px;font-weight:700"><i class="fas fa-arrow-left me-1"></i> Back to Kiosks</a>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm" style="border-radius:16px;overflow:hidden">
                <div class="card-body p-4">
                    <form id="createKioskForm" novalidate>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="name" class="form-label fw-bold" style="font-size:.78rem;color:#334155;text-transform:uppercase;letter-spacing:.04em">Kiosk Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="name" name="name" required style="border-radius:10px;height:42px;border:1px solid #e2e8f0" placeholder="Main Lobby Kiosk 1">
                                <div class="form-text" style="font-size:.74rem;color:#64748b">Descriptive name (e.g. Main Lobby)</div>
                            </div>
                            <div class="col-md-6">
                                <label for="serial_number" class="form-label fw-bold" style="font-size:.78rem;color:#334155;text-transform:uppercase;letter-spacing:.04em">Serial Number <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="serial_number" name="serial_number" required style="border-radius:10px;height:42px;border:1px solid #e2e8f0" placeholder="KSK-001">
                                <div class="form-text" style="font-size:.74rem;color:#64748b">Unique hardware serial</div>
                            </div>
                            <div class="col-12">
                                <label for="location" class="form-label fw-bold" style="font-size:.78rem;color:#334155;text-transform:uppercase;letter-spacing:.04em">Location</label>
                                <input type="text" class="form-control" id="location" name="location" style="border-radius:10px;height:42px;border:1px solid #e2e8f0" placeholder="Hospital Lobby, Floor 1">
                            </div>
                            <div class="col-md-6">
                                <label for="status" class="form-label fw-bold" style="font-size:.78rem;color:#334155;text-transform:uppercase;letter-spacing:.04em">Initial Status</label>
                                <select class="form-select" id="status" name="status" style="border-radius:10px;height:42px;border:1px solid #e2e8f0">
                                    <option value="inactive">Inactive — needs registration</option>
                                    <option value="active">Active</option>
                                    <option value="maintenance">Maintenance</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label for="configuration" class="form-label fw-bold" style="font-size:.78rem;color:#334155;text-transform:uppercase;letter-spacing:.04em">Configuration (JSON)</label>
                                <textarea class="form-control" id="configuration" name="configuration" rows="4" style="border-radius:12px;border:1px solid #e2e8f0;font-family:monospace;font-size:.82rem" placeholder='{"screen_resolution":"1920x1080","touch_enabled":true}'></textarea>
                                <div class="form-text" style="font-size:.74rem;color:#64748b">Optional JSON for hardware capabilities</div>
                            </div>
                        </div>
                        <div class="d-flex justify-content-end gap-2 mt-4 pt-3" style="border-top:1px solid #f1f5f9">
                            <a href="{{ route('admin.kiosks.index') }}" class="btn btn-light border" style="border-radius:12px;font-weight:600;padding:8px 16px">Cancel</a>
                            <button type="submit" class="btn text-white" id="submitBtn" style="background:#0f172a;border-radius:12px;font-weight:700;padding:8px 16px"><i class="fas fa-save me-1"></i> Create Kiosk</button>
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
document.getElementById('createKioskForm')?.addEventListener('submit',function(e){
    e.preventDefault();
    const btn=document.getElementById('submitBtn'); const orig=btn.innerHTML; btn.disabled=true; btn.innerHTML='<i class="fas fa-spinner fa-spin me-1"></i> Creating...';
    const cfg=document.getElementById('configuration');
    if(cfg.value.trim()){
        try{ JSON.parse(cfg.value); }catch(err){ alert('Invalid JSON in configuration'); btn.disabled=false; btn.innerHTML=orig; return; }
    }
    const fd=new FormData(this);
    fetch('{{ route('admin.kiosks.store') }}',{method:'POST',headers:{'X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content},body:fd})
    .then(r=>r.json()).then(d=>{
        if(d.success){ window.location.href='{{ route('admin.kiosks.index') }}'; }
        else { alert(d.message||'Failed'); btn.disabled=false; btn.innerHTML=orig; if(d.errors){ Object.entries(d.errors).forEach(([k,v])=>alert(k+': '+v.join(', '))); } }
    }).catch(()=>{ alert('Error creating kiosk'); btn.disabled=false; btn.innerHTML=orig; });
});
document.getElementById('name')?.addEventListener('input',function(){
    const s=document.getElementById('serial_number');
    if(s && !s.value){ s.value='KIOSK-'+this.value.toLowerCase().replace(/[^a-z0-9]/g,'').toUpperCase().slice(0,8)+'-'+Date.now().toString().slice(-4); }
});
</script>
@endpush
