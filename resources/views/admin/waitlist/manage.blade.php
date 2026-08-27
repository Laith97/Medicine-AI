@extends('layouts.admin')

@section('title', 'Manage Waitlist')

@section('content')
<div class="container-fluid px-0">
    {{-- Header compatible --}}
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4" style="background:linear-gradient(135deg,#1e293b 0%,#334155 100%);border-radius:16px;padding:1.4rem 1.6rem;box-shadow:0 8px 24px rgba(15,23,42,0.12)">
        <div class="d-flex align-items-center gap-3">
            <div style="width:44px;height:44px;border-radius:12px;background:rgba(255,255,255,0.14);display:flex;align-items:center;justify-content:center"><i class="fas fa-users" style="color:#fff;font-size:1.1rem"></i></div>
            <div>
                <h1 style="font-size:1.35rem;font-weight:800;color:#fff;letter-spacing:-0.02em;margin:0">Waitlist Management</h1>
                <p style="font-size:0.78rem;color:rgba(255,255,255,0.75);margin:2px 0 0" id="doctorInfo">Loading doctor information...</p>
            </div>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-sm text-white" style="background:rgba(255,255,255,0.14);border:1px solid rgba(255,255,255,0.18);border-radius:10px;font-weight:700" onclick="goBack()"><i class="fas fa-arrow-left me-1"></i> Back</button>
            <button class="btn btn-sm" style="background:#fff;color:#0f172a;border-radius:10px;font-weight:800;border:1px solid #e2e8f0" onclick="refreshWaitlist()"><i class="fas fa-sync-alt me-1"></i> Refresh</button>
        </div>
    </div>

    <!-- Quick Stats compatible -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-xl-3"><div class="card border-0 shadow-sm" style="border-radius:14px"><div class="card-body p-3 d-flex align-items-center gap-3"><div style="width:42px;height:42px;border-radius:12px;background:#eff6ff;border:1px solid #dbeafe;color:#2563eb;display:flex;align-items:center;justify-content:center"><i class="fas fa-users"></i></div><div><div style="font-weight:800;color:#0f172a" id="totalPatients">0</div><small class="text-muted">Total Patients</small></div></div></div></div>
        <div class="col-6 col-xl-3"><div class="card border-0 shadow-sm" style="border-radius:14px"><div class="card-body p-3 d-flex align-items-center gap-3"><div style="width:42px;height:42px;border-radius:12px;background:#ecfdf5;border:1px solid #a7f3d0;color:#059669;display:flex;align-items:center;justify-content:center"><i class="fas fa-clock"></i></div><div><div style="font-weight:800;color:#0f172a" id="avgWaitTime">0</div><small class="text-muted">Avg Wait (days)</small></div></div></div></div>
        <div class="col-6 col-xl-3"><div class="card border-0 shadow-sm" style="border-radius:14px"><div class="card-body p-3 d-flex align-items-center gap-3"><div style="width:42px;height:42px;border-radius:12px;background:#fffbeb;border:1px solid #fde68a;color:#d97706;display:flex;align-items:center;justify-content:center"><i class="fas fa-exclamation"></i></div><div><div style="font-weight:800;color:#0f172a" id="priorityCases">0</div><small class="text-muted">Priority Cases</small></div></div></div></div>
        <div class="col-6 col-xl-3"><div class="card border-0 shadow-sm" style="border-radius:14px"><div class="card-body p-3 d-flex align-items-center gap-3"><div style="width:42px;height:42px;border-radius:12px;background:#f5f3ff;border:1px solid #ddd6fe;color:#7c3aed;display:flex;align-items:center;justify-content:center"><i class="fas fa-percentage"></i></div><div><div style="font-weight:800;color:#0f172a" id="fillRate">0%</div><small class="text-muted">Fill Rate</small></div></div></div></div>
    </div>

    <!-- Filters and Actions -->
    <div class="row g-4 mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm" style="border-radius:14px;overflow:hidden">
                <div class="card-body p-3">
                    <div class="row g-3 align-items-center">
                        <div class="col-md-6">
                            <div class="d-flex gap-2">
                                <select class="form-select" id="priorityFilter" style="border-radius:10px"><option value="">All Priorities</option><option value="urgent">Urgent</option><option value="high">High</option><option value="medium">Medium</option><option value="low">Low</option></select>
                                <select class="form-select" id="statusFilter" style="border-radius:10px"><option value="">All Statuses</option><option value="active">Active</option><option value="paused">Paused</option><option value="cancelled">Cancelled</option></select>
                            </div>
                        </div>
                        <div class="col-md-6 d-flex gap-2 justify-content-md-end">
                            <input type="text" class="form-control" id="searchInput" placeholder="Search patients..." style="border-radius:10px;max-width:220px">
                            <button class="btn btn-light border" style="border-radius:10px;font-weight:600" onclick="exportWaitlist()"><i class="fas fa-download me-1"></i> Export</button>
                            <button class="btn" style="background:#0f172a;color:#fff;border-radius:10px;font-weight:700" onclick="addPatientToWaitlist()"><i class="fas fa-plus me-1"></i> Add Patient</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Waitlist Table -->
    <div class="row g-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm" style="border-radius:14px;overflow:hidden">
                <div class="card-header bg-white d-flex justify-content-between align-items-center" style="padding:1rem 1.25rem;border-bottom:1px solid #eef2f7">
                    <h5 class="mb-0 d-flex align-items-center gap-2" style="font-weight:800;color:#0f172a;font-size:0.95rem"><i class="fas fa-list" style="color:#64748b"></i> Patient Waitlist <span class="badge bg-light border text-muted" style="border-radius:20px" id="tableCount">0</span></h5>
                    <div class="text-muted small" id="paginationInfo">Showing 0 to 0 of 0 entries</div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0" id="waitlistTable" style="font-size:0.84rem">
                            <thead style="background:#f8fafc"><tr style="font-size:0.75rem;letter-spacing:0.04em;color:#64748b;text-transform:uppercase"><th style="padding:0.9rem 1.25rem;border:none"><input type="checkbox" id="selectAll"></th><th style="padding:0.9rem 1.25rem;border:none">Patient</th><th style="padding:0.9rem 1.25rem;border:none">Priority</th><th style="padding:0.9rem 1.25rem;border:none">Wait Time</th><th style="padding:0.9rem 1.25rem;border:none">Status</th><th style="padding:0.9rem 1.25rem;border:none">Service Type</th><th style="padding:0.9rem 1.25rem;border:none">Actions</th></tr></thead>
                            <tbody id="waitlistTableBody"></tbody>
                        </table>
                    </div>
                    <div class="d-flex justify-content-center p-3 border-top" style="background:#f8fafc" id="paginationNav"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bulk Actions Bar -->
    <div class="row mt-3" id="bulkActionsBar" style="display:none">
        <div class="col-12">
            <div class="card border-0 shadow-sm" style="border-radius:14px;background:#0f172a;color:#fff">
                <div class="card-body py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <span id="selectedCount" style="font-weight:700">0 patients selected</span>
                    <div class="d-flex gap-2 flex-wrap">
                        <button class="btn btn-sm bg-white text-dark" style="border-radius:8px;font-weight:600" onclick="bulkChangePriority()">Change Priority</button>
                        <button class="btn btn-sm" style="background:rgba(255,255,255,0.14);border:1px solid rgba(255,255,255,0.2);color:#fff;border-radius:8px" onclick="bulkChangeStatus()">Change Status</button>
                        <button class="btn btn-sm" style="background:#10b981;color:#fff;border-radius:8px" onclick="bulkAssignSlots()">Assign Slots</button>
                        <button class="btn btn-sm btn-danger" style="border-radius:8px" onclick="bulkRemove()">Remove</button>
                        <button class="btn btn-sm btn-light" style="border-radius:8px" onclick="clearSelection()">Clear</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
let currentDoctorId = null;
let currentPage = 1;
let selectedPatients = new Set();
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    currentDoctorId = urlParams.get('doctor_id') || getDoctorIdFromPath();
    if (currentDoctorId) { loadWaitlistData(); loadDoctorInfo(); } else { showAlert('Doctor ID not found - showing all waitlists','info'); loadWaitlistData(); }
    setupEventListeners();
});
function getDoctorIdFromPath(){ const p=window.location.pathname; const m=p.match(/manage\/(\d+)/); return m?m[1]:null; }
function setupEventListeners(){
    document.getElementById('priorityFilter').addEventListener('change', () => loadWaitlistData());
    document.getElementById('statusFilter').addEventListener('change', () => loadWaitlistData());
    document.getElementById('searchInput').addEventListener('input', debounce(() => loadWaitlistData(), 300));
    document.getElementById('selectAll').addEventListener('change', function(){ const cbs=document.querySelectorAll('.patient-checkbox'); cbs.forEach(cb=>{cb.checked=this.checked; updateSelection(cb.value,this.checked);}); updateBulkActionsBar(); });
}
function loadDoctorInfo(){ if(!currentDoctorId) return; fetch(`/api/admin/doctors`).then(r=>r.json()).then(d=>{ const doc=(d.doctors||[]).find(x=>x.id==currentDoctorId); if(doc){ document.getElementById('doctorInfo').textContent=doc.name+' - '+(doc.specialty||'General'); document.title='Manage Waitlist - '+doc.name; } }).catch(()=>{}); }
function loadWaitlistData(page=1){
    currentPage=page;
    const params=new URLSearchParams({doctor_id:currentDoctorId||'',page,priority:document.getElementById('priorityFilter').value,status:document.getElementById('statusFilter').value,search:document.getElementById('searchInput').value});
    fetch(`/api/admin/waitlist/manage?${params}`).then(r=>r.json()).then(data=>{ updateStats(data.stats); updateWaitlistTable(data.patients); updatePagination(data.pagination); document.getElementById('tableCount').textContent=data.pagination.total||0; }).catch(e=>{ console.error(e); showAlert('Error loading waitlist','danger'); });
}
function updateStats(s){ document.getElementById('totalPatients').textContent=s.totalPatients||0; document.getElementById('avgWaitTime').textContent=Math.max(0,Math.round(s.avgWaitTime||0))+' days'; document.getElementById('priorityCases').textContent=s.priorityCases||0; document.getElementById('fillRate').textContent=`${s.fillRate||0}%`; }
function updateWaitlistTable(patients){
    const tb=document.getElementById('waitlistTableBody'); tb.innerHTML='';
    if(!patients||!patients.length){ tb.innerHTML='<tr><td colspan="7" class="text-center py-5"><div style="width:56px;height:56px;border-radius:16px;background:#f8fafc;border:1px dashed #e2e8f0;display:flex;align-items:center;justify-content:center;margin:0 auto 12px;color:#94a3b8"><i class="fas fa-users"></i></div><p class="text-muted small mb-0">No patients in waitlist</p></td></tr>'; return; }
    patients.forEach(p=>{
        tb.insertAdjacentHTML('beforeend', `<tr style="border-bottom:1px solid #f8fafc"><td style="padding:0.9rem 1.25rem"><input type="checkbox" class="patient-checkbox" value="${p.id}" onchange="updateSelection(${p.id},this.checked)"></td><td style="padding:0.9rem 1.25rem"><div class="d-flex align-items-center gap-3"><div style="width:40px;height:40px;border-radius:12px;background:linear-gradient(135deg,#667eea,#764ba2);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:800">${(p.name||'?').charAt(0).toUpperCase()}</div><div><div style="font-weight:700;color:#0f172a;font-size:0.84rem">${p.name}</div><div style="font-size:.76rem;color:#64748b">${p.email}</div></div></div></td><td style="padding:0.9rem 1.25rem"><span class="badge" style="background:${p.priority==='urgent'?'#fef2f2':p.priority==='high'?'#fffbeb':'#f8fafc'};border:1px solid ${p.priority==='urgent'?'#fecaca':p.priority==='high'?'#fde68a':'#e2e8f0'};color:${p.priority==='urgent'?'#dc2626':p.priority==='high'?'#92400e':'#475569'};border-radius:20px">${p.priority}</span></td><td style="padding:0.9rem 1.25rem;color:#0f172a;font-weight:600">${Math.max(0,Math.round(p.waitTime||0))} days</td><td style="padding:0.9rem 1.25rem"><span class="badge" style="background:${p.status==='active'?'#ecfdf5':'#f8fafc'};border:1px solid ${p.status==='active'?'#a7f3d0':'#e2e8f0'};color:${p.status==='active'?'#065f46':'#64748b'};border-radius:20px">${p.status}</span></td><td style="padding:0.9rem 1.25rem"><span class="badge bg-light border text-muted" style="border-radius:20px">${p.serviceType}</span></td><td style="padding:0.9rem 1.25rem"><div class="btn-group btn-group-sm"><button class="btn btn-light border" style="border-radius:8px" onclick="viewPatient(${p.id})"><i class="fas fa-eye" style="font-size:0.75rem"></i></button><button class="btn btn-light border" style="border-radius:8px" onclick="editPatient(${p.id})"><i class="fas fa-edit" style="font-size:0.75rem"></i></button><button class="btn btn-light border" style="border-radius:8px" onclick="assignSlot(${p.id})"><i class="fas fa-calendar-check" style="font-size:0.75rem"></i></button></div></td></tr>`);
    });
}
function updatePagination(p){
    document.getElementById('paginationInfo').textContent=`Showing ${p.from||0} to ${p.to||0} of ${p.total||0} entries`;
    const nav=document.getElementById('paginationNav'); nav.innerHTML='';
    if(p.last_page>1){
        const ul=document.createElement('ul'); ul.className='pagination pagination-sm mb-0';
        const prev=document.createElement('li'); prev.className=`page-item ${p.current_page===1?'disabled':''}`; prev.innerHTML=`<a class="page-link" href="#" onclick="loadWaitlistData(${p.current_page-1});return false;">Previous</a>`; ul.appendChild(prev);
        for(let i=Math.max(1,p.current_page-2); i<=Math.min(p.last_page,p.current_page+2); i++){ const li=document.createElement('li'); li.className=`page-item ${i===p.current_page?'active':''}`; li.innerHTML=`<a class="page-link" href="#" onclick="loadWaitlistData(${i});return false;">${i}</a>`; ul.appendChild(li); }
        const next=document.createElement('li'); next.className=`page-item ${p.current_page===p.last_page?'disabled':''}`; next.innerHTML=`<a class="page-link" href="#" onclick="loadWaitlistData(${p.current_page+1});return false;">Next</a>`; ul.appendChild(next);
        nav.appendChild(ul);
    }
}
function updateSelection(id,sel){ if(sel) selectedPatients.add(String(id)); else selectedPatients.delete(String(id)); updateBulkActionsBar(); }
function updateBulkActionsBar(){ const bar=document.getElementById('bulkActionsBar'); const c=document.getElementById('selectedCount'); if(selectedPatients.size>0){ bar.style.display='block'; c.textContent=`${selectedPatients.size} patient${selectedPatients.size>1?'s':''} selected`; } else bar.style.display='none'; }
function clearSelection(){ selectedPatients.clear(); document.querySelectorAll('.patient-checkbox').forEach(cb=>cb.checked=false); document.getElementById('selectAll').checked=false; updateBulkActionsBar(); }
function viewPatient(id){ window.open(`/admin/patients/${id}`, '_blank'); }
function editPatient(id){ window.open(`/admin/patients/${id}/edit`, '_blank'); }
function assignSlot(id){ if(!confirm('Assign next available slot?')) return; fetch(`/api/admin/waitlist/assign-slot`,{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').getAttribute('content')},body:JSON.stringify({patientId:id,doctorId:currentDoctorId})}).then(r=>r.json()).then(d=>{ if(d.success){showAlert('Slot assigned','success'); loadWaitlistData();} else showAlert('Error','danger'); }); }
function changePriority(id){ const p=prompt('Enter priority (low,medium,high,urgent):'); if(p&&['low','medium','high','urgent'].includes(p)) fetch(`/api/admin/waitlist/update-priority`,{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').getAttribute('content')},body:JSON.stringify({patientId:id,priority:p})}).then(r=>r.json()).then(d=>{ if(d.success){showAlert('Priority updated','success'); loadWaitlistData();}}); }
function changeStatus(id){ const s=prompt('Enter status (active,paused,cancelled):'); if(s&&['active','paused','cancelled'].includes(s)) fetch(`/api/admin/waitlist/update-status`,{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').getAttribute('content')},body:JSON.stringify({patientId:id,status:s})}).then(r=>r.json()).then(d=>{ if(d.success){showAlert('Status updated','success'); loadWaitlistData();}}); }
function removeFromWaitlist(id){ if(!confirm('Remove from waitlist?')) return; fetch(`/api/admin/waitlist/remove-patient`,{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').getAttribute('content')},body:JSON.stringify({patientId:id,doctorId:currentDoctorId})}).then(r=>r.json()).then(d=>{ if(d.success){showAlert('Removed','success'); loadWaitlistData();}}); }
function bulkChangePriority(){ const p=prompt('Enter priority (low,medium,high,urgent):'); if(p) fetch(`/api/admin/waitlist/bulk-update`,{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').getAttribute('content')},body:JSON.stringify({patientIds:Array.from(selectedPatients),action:'priority',value:p})}).then(r=>r.json()).then(d=>{ if(d.success){showAlert('Bulk updated','success'); clearSelection(); loadWaitlistData();}}); }
function bulkChangeStatus(){ const s=prompt('Enter status (active,paused,cancelled):'); if(s) fetch(`/api/admin/waitlist/bulk-update`,{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').getAttribute('content')},body:JSON.stringify({patientIds:Array.from(selectedPatients),action:'status',value:s})}).then(r=>r.json()).then(d=>{ if(d.success){showAlert('Bulk updated','success'); clearSelection(); loadWaitlistData();}}); }
function bulkAssignSlots(){ if(!confirm(`Assign slots to ${selectedPatients.size} patients?`)) return; fetch(`/api/admin/waitlist/bulk-update`,{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').getAttribute('content')},body:JSON.stringify({patientIds:Array.from(selectedPatients),action:'assign_slots'})}).then(r=>r.json()).then(d=>{ if(d.success){showAlert('Slots assigned','success'); clearSelection(); loadWaitlistData();}}); }
function bulkRemove(){ if(!confirm(`Remove ${selectedPatients.size} patients?`)) return; fetch(`/api/admin/waitlist/bulk-update`,{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').getAttribute('content')},body:JSON.stringify({patientIds:Array.from(selectedPatients),action:'remove'})}).then(r=>r.json()).then(d=>{ if(d.success){showAlert('Removed','success'); clearSelection(); loadWaitlistData();}}); }
function debounce(f,w){ let t; return (...a)=>{ clearTimeout(t); t=setTimeout(()=>f(...a),w); }; }
function goBack(){ window.location.href='/admin/waitlist/dashboard'; }
function refreshWaitlist(){ loadWaitlistData(); showAlert('Waitlist refreshed','success'); }
function exportWaitlist(){ const p=new URLSearchParams({doctor_id:currentDoctorId||'',priority:document.getElementById('priorityFilter').value,status:document.getElementById('statusFilter').value,search:document.getElementById('searchInput').value}); window.open(`/api/admin/waitlist/export?${p}`, '_blank'); }
function addPatientToWaitlist(){ window.location.href=`/admin/waitlist/add-patient?doctor_id=${currentDoctorId||''}`; }
function showAlert(m,t='info'){ const h=`<div class="alert alert-${t} alert-dismissible fade show" role="alert" style="border-radius:12px">${m}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>`; const c=document.querySelector('.admin-content'); if(c) c.insertAdjacentHTML('afterbegin',h); }
</script>
@endsection
