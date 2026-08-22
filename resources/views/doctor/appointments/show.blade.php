@extends('master')

@section('title', 'Appointment #' . $appointment->id . ' - ' . $appointment->patient_name)

@push('styles')
<link rel="stylesheet" href="{{ asset('css/doctor-design-system.css') }}">
<link rel="stylesheet" href="{{ asset('css/doctor-dashboard.css') }}">
<style>
.appointment-show-header {
    background: linear-gradient(135deg, #2c5aa0 0%, #1e3a8a 100%);
    border-radius: var(--radius-lg, 12px);
    padding: 1.75rem 2rem;
    color: #fff;
    margin-bottom: 1.5rem;
}
.appointment-show-header .status-badge {
    font-size: 0.8rem;
    padding: 0.4rem 0.9rem;
    border-radius: 50px;
    font-weight: 600;
    letter-spacing: 0.02em;
}
.status-badge.status-pending{ background:#fff3cd; color:#856404; }
.status-badge.status-confirmed{ background:#d1ecf1; color:#0c5460; }
.status-badge.status-completed{ background:#d4edda; color:#155724; }
.status-badge.status-cancelled{ background:#f8d7da; color:#721c24; }
.status-badge.status-no_show{ background:#e2e3e5; color:#383d41; }
.appointment-meta-grid{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(220px,1fr));
    gap:1rem;
}
.appointment-quick-actions .btn{
    border-radius:50px;
    font-size:0.88rem;
    font-weight:500;
    padding:0.45rem 1rem;
}
.doctor-tabs-container .nav-link{
    font-weight:500;
    color:var(--gray-500);
    border:none;
    border-bottom:2px solid transparent;
    background:transparent;
}
.doctor-tabs-container .nav-link.active{
    color:var(--primary-color);
    border-bottom-color:var(--primary-color);
    background:transparent;
}
.doctor-card{
    border:1px solid var(--border-light);
    border-radius:var(--radius-lg);
    background:var(--bg-primary);
    box-shadow:var(--shadow-sm);
}
.doctor-card-header{
    padding:1.1rem 1.25rem;
    border-bottom:1px solid var(--border-light);
    background:var(--gray-50);
    border-radius:var(--radius-lg) var(--radius-lg) 0 0;
}
.doctor-card-header h5{ font-size:0.98rem; font-weight:600; margin:0; color:var(--gray-800); }
.doctor-card-body{ padding:1.25rem; }
.info-icon{
    width:42px; height:42px;
    border-radius:10px;
    display:inline-flex; align-items:center; justify-content:center;
    background:var(--bg-secondary);
}
.prescription-workflow .workflow-btn{
    border:1px solid var(--border-medium);
    border-radius:var(--radius-md);
    padding:0.5rem 0.9rem;
    font-size:0.85rem;
    background:#fff;
}
.prescription-workflow .workflow-btn.active{
    background:var(--primary-color);
    color:#fff;
    border-color:var(--primary-color);
}
@media(max-width:768px){
    .appointment-show-header{ padding:1.25rem; }
    .appointment-show-header h1{ font-size:1.4rem; }
}
</style>
@endpush

@section('content')
<div class="doctor-dashboard-container" style="background:var(--bg-secondary); min-height:100vh;">
<div class="doctor-content-wrapper container py-4">

    <!-- Header - matches cases.blade.php dashboard-header -->
    <div class="appointment-show-header">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div class="d-flex align-items-center gap-3">
                <a href="{{ route('doctor.appointments.index') }}" class="btn btn-light btn-sm shadow-sm" style="border-radius:50px;">
                    <i class="fas fa-arrow-left me-1"></i> Back
                </a>
                <div>
                    <h1 class="h4 mb-1 fw-bold text-white"><i class="fas fa-calendar-check me-2 opacity-75"></i>Appointment #{{ $appointment->id }}</h1>
                    <p class="mb-0 text-white-50 small">{{ $appointment->appointment_date->format('l, M j, Y \a\t g:i A') }} • {{ ucfirst(str_replace('_',' ',$appointment->appointment_type)) }} • {{ $appointment->appointment_duration ?? 30 }} min</p>
                </div>
            </div>
            <div class="d-flex flex-column align-items-end gap-2">
                <span class="status-badge status-{{ $appointment->status }}">
                    <i class="fas fa-{{ $appointment->status=='pending'?'clock':($appointment->status=='confirmed'?'check-circle':($appointment->status=='completed'?'check-double':($appointment->status=='cancelled'?'times-circle':'user-times'))) }} me-1"></i>
                    {{ ucfirst(str_replace('_',' ', $appointment->status)) }}
                </span>
                @if($appointment->status=='completed')
                    <span class="badge bg-success bg-opacity-25 border border-success-subtle text-white small"><i class="fas fa-check me-1"></i>Completed</span>
                @endif
            </div>
        </div>
        @if(auth()->check() && auth()->user()->isDoctor())
        <div class="appointment-quick-actions d-flex flex-wrap gap-2 mt-3">
            @if($appointment->status=='pending')
                <button onclick="confirmAppointment({{ $appointment->id }})" class="btn btn-success btn-sm"><i class="fas fa-check me-1"></i>Confirm</button>
            @endif
            @if($appointment->status=='confirmed' && (!$appointment->appointment_date || !$appointment->appointment_date->isFuture()))
                <button onclick="completeAppointment({{ $appointment->id }})" class="btn btn-primary btn-sm"><i class="fas fa-check-circle me-1"></i>Complete</button>
                <button onclick="markNoShow({{ $appointment->id }})" class="btn btn-light btn-sm"><i class="fas fa-user-times me-1"></i>No Show</button>
            @endif
            @if(in_array($appointment->status,['pending','confirmed']))
                <button onclick="cancelAppointment({{ $appointment->id }})" class="btn btn-outline-light btn-sm"><i class="fas fa-times me-1"></i>Cancel</button>
            @endif
            @if($appointment->status==='confirmed')
                <span class="ms-auto small text-white-50 d-flex align-items-center"><i class="fas fa-video me-1"></i> Call available</span>
            @endif
        </div>
        @endif
    </div>

    @if($appointment->status==='confirmed')
        @include('components.appointment-call-buttons', ['appointment'=>$appointment])
    @endif

    <!-- Completed Next Steps - compact -->
    @if($appointment->status=='completed')
    <div class="doctor-card mb-4">
        <div class="doctor-card-header d-flex justify-content-between align-items-center">
            <h5><i class="fas fa-rocket me-2 text-success"></i>Next Steps</h5>
            <small class="text-muted">Appointment completed</small>
        </div>
        <div class="doctor-card-body">
            <div class="row g-3">
                <div class="col-6 col-lg-3">
                    <button onclick="toggleAIMedicalCopilotForm()" class="doctor-quick-nav-card w-100 h-100 text-center p-3" style="min-height:110px; border:1px solid var(--border-light); border-radius:var(--radius-md); background:#fff;">
                        <div class="nav-icon bg-primary bg-opacity-10 mx-auto mb-2" style="width:42px;height:42px;border-radius:10px;display:flex;align-items:center;justify-content:center;"><i class="fas fa-brain text-primary"></i></div>
                        <h6 class="nav-title mb-1" style="font-size:0.9rem;">AI Copilot</h6><small class="text-muted">Decision Support</small>
                    </button>
                </div>
                <div class="col-6 col-lg-3">
                    <button onclick="viewPatientAIAnalyses({{ $appointment->patient_id }})" class="doctor-quick-nav-card w-100 h-100 text-center p-3" style="min-height:110px; border:1px solid var(--border-light); border-radius:var(--radius-md); background:#fff;">
                        <div class="nav-icon bg-info bg-opacity-10 mx-auto mb-2" style="width:42px;height:42px;border-radius:10px;display:flex;align-items:center;justify-content:center;"><i class="fas fa-history text-info"></i></div>
                        <h6 class="nav-title mb-1" style="font-size:0.9rem;">AI History</h6><small class="text-muted">Past analyses</small>
                    </button>
                </div>
                <div class="col-6 col-lg-3">
                    <button onclick="toggleDiagnosisForm()" class="doctor-quick-nav-card w-100 h-100 text-center p-3" style="min-height:110px; border:1px solid var(--border-light); border-radius:var(--radius-md); background:#fff;">
                        <div class="nav-icon bg-warning bg-opacity-10 mx-auto mb-2" style="width:42px;height:42px;border-radius:10px;display:flex;align-items:center;justify-content:center;"><i class="fas fa-stethoscope text-warning"></i></div>
                        <h6 class="nav-title mb-1" style="font-size:0.9rem;">Diagnosis</h6><small class="text-muted">Create diagnosis</small>
                    </button>
                </div>
                <div class="col-6 col-lg-3">
                    <a href="{{ route('doctor.follow-ups.create', $appointment) }}" class="doctor-quick-nav-card w-100 h-100 text-center p-3 d-flex flex-column align-items-center justify-content-center text-decoration-none" style="min-height:110px; border:1px solid var(--border-light); border-radius:var(--radius-md); background:#fff;">
                        <div class="nav-icon bg-success bg-opacity-10 mx-auto mb-2" style="width:42px;height:42px;border-radius:10px;display:flex;align-items:center;justify-content:center;"><i class="fas fa-calendar-plus text-success"></i></div>
                        <h6 class="nav-title mb-1" style="font-size:0.9rem;">Follow-up</h6><small class="text-muted">Schedule next</small>
                    </a>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Tabs - unified design system -->
    <div class="doctor-tabs-container doctor-card">
        <ul class="nav doctor-nav-tabs px-3 pt-3" role="tablist">
            <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-overview" type="button"><i class="fas fa-info-circle me-1"></i>Overview</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-ai" type="button"><i class="fas fa-brain me-1"></i>AI Analytics</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-prescriptions" type="button"><i class="fas fa-prescription-bottle me-1"></i>Prescriptions</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-diagnosis" type="button"><i class="fas fa-stethoscope me-1"></i>Diagnosis</button></li>
        </ul>
        <div class="tab-content p-3 p-md-4">

            <!-- OVERVIEW -->
            <div class="tab-pane fade show active" id="tab-overview">
                <div class="row g-4">
                    <div class="col-lg-6">
                        <div class="doctor-card h-100">
                            <div class="doctor-card-header"><h5><i class="fas fa-calendar-alt me-2 text-primary"></i>Appointment</h5></div>
                            <div class="doctor-card-body">
                                <div class="appointment-meta-grid">
                                    <div class="d-flex gap-3 align-items-start">
                                        <div class="info-icon bg-primary bg-opacity-10"><i class="fas fa-clock text-primary"></i></div>
                                        <div><small class="text-muted d-block">Duration</small><strong>{{ $appointment->appointment_duration ?? 30 }} min</strong></div>
                                    </div>
                                    <div class="d-flex gap-3 align-items-start">
                                        <div class="info-icon bg-success bg-opacity-10"><i class="fas fa-tag text-success"></i></div>
                                        <div><small class="text-muted d-block">Type</small><span class="badge bg-primary">{{ ucfirst(str_replace('_',' ',$appointment->appointment_type)) }}</span></div>
                                    </div>
                                    <div class="d-flex gap-3 align-items-start">
                                        <div class="info-icon bg-info bg-opacity-10"><i class="fas fa-calendar text-info"></i></div>
                                        <div><small class="text-muted d-block">Date</small><strong>{{ $appointment->appointment_date->format('M j, Y g:i A') }}</strong></div>
                                    </div>
                                    <div class="d-flex gap-3 align-items-start">
                                        <div class="info-icon bg-warning bg-opacity-10"><i class="fas fa-info-circle text-warning"></i></div>
                                        <div><small class="text-muted d-block">Status</small><strong class="text-capitalize">{{ str_replace('_',' ',$appointment->status) }}</strong></div>
                                    </div>
                                </div>
                                <hr class="my-3">
                                <h6 class="fw-semibold mb-2"><i class="fas fa-clipboard-list me-2 text-primary"></i>Reason for Visit</h6>
                                <div class="bg-light p-3 rounded" style="border-left:4px solid var(--primary-color);"><p class="mb-0 lh-base">{{ e($appointment->reason) }}</p></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="doctor-card h-100">
                            <div class="doctor-card-header"><h5><i class="fas fa-user-injured me-2 text-primary"></i>Patient</h5></div>
                            <div class="doctor-card-body">
                                <div class="d-flex gap-3 align-items-center mb-3">
                                    <div class="bg-primary bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center" style="width:56px;height:56px;"><i class="fas fa-user text-primary fs-5"></i></div>
                                    <div>
                                        <h6 class="mb-0 fw-semibold">{{ e($appointment->patient_name) }}</h6>
                                        <small class="text-muted">Patient #{{ $appointment->patient_id ?? '-' }}</small>
                                    </div>
                                    @if($appointment->patient_id)
                                    <a href="{{ route('doctor.patients.show', $appointment->patient_id) }}" class="btn btn-outline-primary btn-sm ms-auto">View profile</a>
                                    @endif
                                </div>
                                <div class="vstack gap-2">
                                    <div class="d-flex align-items-center gap-2"><i class="fas fa-envelope text-muted" style="width:18px;"></i><span>{{ e($appointment->patient_email) }}</span></div>
                                    @if($appointment->patient_phone)
                                    <div class="d-flex align-items-center gap-2"><i class="fas fa-phone text-muted" style="width:18px;"></i><span>{{ e($appointment->patient_phone) }}</span></div>
                                    @endif
                                    @if($appointment->patient)
                                        <div class="d-flex align-items-center gap-2"><i class="fas fa-venus-mars text-muted" style="width:18px;"></i><span>{{ $appointment->patient->gender ?? '-' }} • {{ $appointment->patient->age ?? '-' }} yrs</span></div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- AI ANALYTICS -->
            <div class="tab-pane fade" id="tab-ai">
                @php $riskScore = $appointment->patient?->patientRiskScores?->where('appointment_id',$appointment->id)?->first(); @endphp
                @if($riskScore)
                <div class="row g-4 mb-4">
                    <div class="col-md-6">
                        <div class="doctor-card text-center h-100">
                            <div class="doctor-card-body p-4">
                                <div class="bg-warning bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width:64px;height:64px;"><i class="fas fa-user-times text-warning fa-lg"></i></div>
                                <h6 class="text-warning fw-bold mb-2">No-Show Risk</h6>
                                <div class="display-6 fw-bold text-warning mb-2">{{ number_format($riskScore->no_show_risk*100,1) }}<small class="fs-6">%</small></div>
                                <p class="text-muted small mb-0">Probability of missing appointment</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="doctor-card text-center h-100">
                            <div class="doctor-card-body p-4">
                                <div class="bg-danger bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width:64px;height:64px;"><i class="fas fa-hospital text-danger fa-lg"></i></div>
                                <h6 class="text-danger fw-bold mb-2">Hospitalization Risk</h6>
                                <div class="display-6 fw-bold text-danger mb-2">{{ number_format($riskScore->hospitalization_risk*100,1) }}<small class="fs-6">%</small></div>
                                <p class="text-muted small mb-0">Probability requiring hospitalization</p>
                            </div>
                        </div>
                    </div>
                </div>
                @php $maxRisk = max($riskScore->no_show_risk,$riskScore->hospitalization_risk); @endphp
                <div class="doctor-card">
                    <div class="doctor-card-body d-flex justify-content-between align-items-center flex-wrap gap-3">
                        <div>
                            @if($maxRisk<0.3)
                                <span class="badge bg-success fs-6 px-3 py-2"><i class="fas fa-shield-alt me-1"></i>Low Risk</span><small class="text-muted d-block mt-1">Strong compliance patterns</small>
                            @elseif($maxRisk<0.7)
                                <span class="badge bg-warning text-dark fs-6 px-3 py-2"><i class="fas fa-exclamation-triangle me-1"></i>Medium Risk</span><small class="text-muted d-block mt-1">Consider reminders</small>
                            @else
                                <span class="badge bg-danger fs-6 px-3 py-2"><i class="fas fa-exclamation-circle me-1"></i>High Risk</span><small class="text-muted d-block mt-1">Immediate attention</small>
                            @endif
                        </div>
                        <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#mlExplanationModal"><i class="fas fa-info-circle me-1"></i>How is this calculated?</button>
                    </div>
                </div>
                @else
                <div class="doctor-empty-state text-center py-5">
                    <div class="bg-info bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width:64px;height:64px;"><i class="fas fa-brain text-info fa-lg"></i></div>
                    <h5 class="text-muted">AI Analysis in Progress</h5><p class="text-muted">Risk predictions are being calculated...</p><div class="spinner-border text-info" role="status"></div>
                </div>
                @endif
            </div>

            <!-- PRESCRIPTIONS -->
            <div class="tab-pane fade" id="tab-prescriptions">
                @include('doctor.appointments.partials.prescriptions-tab', ['appointment'=>$appointment])
            </div>

            <!-- DIAGNOSIS -->
            <div class="tab-pane fade" id="tab-diagnosis">
                <div id="diagnosis-section">
                    @include('doctor.appointments.partials.diagnosis-tab', ['appointment'=>$appointment])
                </div>
            </div>

        </div>
    </div>

</div>
</div>
@endsection

@push('scripts')
<script>
function confirmAppointment(id){ if(!confirm('Confirm this appointment?')) return; fetch(`{{ url('doctor/appointments') }}/${id}/confirm`,{method:'POST',headers:{'X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content,'Accept':'application/json'}}).then(r=>r.json()).then(d=>{ if(d.success) location.reload(); else alert(d.message); }); }
function cancelAppointment(id){ const r=prompt('Cancellation reason (optional):'); if(r===null) return; fetch(`{{ url('doctor/appointments') }}/${id}/cancel`,{method:'POST',headers:{'X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content,'Content-Type':'application/json','Accept':'application/json'},body:JSON.stringify({cancellation_reason:r})}).then(r=>r.json()).then(d=>{ if(d.success) location.reload(); else alert(d.message); }); }
function completeAppointment(id){ if(!confirm('Mark as completed?')) return; fetch(`{{ url('doctor/appointments') }}/${id}/complete`,{method:'POST',headers:{'X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content,'Accept':'application/json'}}).then(r=>r.json()).then(d=>{ if(d.success) location.reload(); else alert(d.message); }); }
function markNoShow(id){ if(!confirm('Mark as No Show?')) return; fetch(`{{ url('doctor/appointments') }}/${id}/no-show`,{method:'POST',headers:{'X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content,'Accept':'application/json'}}).then(r=>r.json()).then(d=>{ if(d.success) location.reload(); else alert(d.message); }); }
function toggleDiagnosisForm(){ const el=document.getElementById('diagnosis-section'); if(el) el.scrollIntoView({behavior:'smooth'}); document.querySelector('[data-bs-target="#tab-diagnosis"]')?.click(); }
function toggleAIMedicalCopilotForm(){ document.querySelector('[data-bs-target="#tab-ai"]')?.click(); }
function viewPatientAIAnalyses(pid){ window.location.href=`/ai/patients/${pid}/ai-analyses`; }
</script>
@endpush

