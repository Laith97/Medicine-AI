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
// Enhanced appointment action functions with proper timeout and error handling
function createTimeoutPromise(promise, timeoutMs = 30000) { // Increased to 30 seconds
    return Promise.race([
        promise,
        new Promise((_, reject) => 
            setTimeout(() => reject(new Error('Request timeout - server may be slow, please try again')), timeoutMs)
        )
    ]);
}

function resetButtonState(button, originalHTML) {
    if (button) {
        button.disabled = false;
        button.innerHTML = originalHTML;
    }
}

function confirmAppointment(appointmentId) {
    if (!confirm('Are you sure you want to confirm this appointment?')) {
        return;
    }

    const btn = event.target.closest('button');
    if (!btn) return;

    const originalHTML = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Confirming...';

    const form = document.createElement('form');
    form.method = 'POST';
    form.action = `/doctor/appointments/${appointmentId}/confirm`;

    const csrfToken = document.createElement('input');
    csrfToken.type = 'hidden';
    csrfToken.name = '_token';
    csrfToken.value = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    form.appendChild(csrfToken);
    document.body.appendChild(form);

    const fetchPromise = fetch(form.action, {
        method: 'POST',
        body: new FormData(form),
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    });

    createTimeoutPromise(fetchPromise)
    .then(response => {
        if (response.ok) {
            // Try to parse JSON response first
            return response.json().then(data => {
                if (data.success !== false) {
                    showNotification('Appointment confirmed successfully!', 'success');
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    throw new Error(data.message || 'Failed to confirm appointment');
                }
            }).catch(() => {
                // Fallback if response is not JSON
                showNotification('Appointment confirmed successfully!', 'success');
                setTimeout(() => window.location.reload(), 1000);
            });
        } else {
            throw new Error('Server returned error status: ' + response.status);
        }
    })
    .catch(error => {
        resetButtonState(btn, originalHTML);
        showNotification('Failed to confirm appointment. Please try again.', 'error');
        console.error('Confirm appointment error:', error);
    })
    .finally(() => {
        if (document.body.contains(form)) {
            document.body.removeChild(form);
        }
    });
}

function cancelAppointment(appointmentId) {
    const form = document.getElementById('cancelForm');
    form.action = `/doctor/appointments/${appointmentId}/cancel`;
    new bootstrap.Modal(document.getElementById('cancelModal')).show();
}

function submitCancellation() {
    const form = document.getElementById('cancelForm');
    const submitBtn = document.querySelector('#cancelModal .btn-danger');
    if (!submitBtn) return;
    
    const originalText = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Cancelling...';

    const fetchPromise = fetch(form.action, {
        method: 'POST',
        body: new FormData(form),
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    });

    createTimeoutPromise(fetchPromise)
    .then(response => {
        if (response.ok) {
            // Try to parse JSON response first
            return response.json().then(data => {
                if (data.success !== false) {
                    showNotification('Appointment cancelled successfully!', 'success');
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    throw new Error(data.message || 'Failed to cancel appointment');
                }
            }).catch(() => {
                // Fallback if response is not JSON
                showNotification('Appointment cancelled successfully!', 'success');
                setTimeout(() => window.location.reload(), 1000);
            });
        } else {
            throw new Error('Server returned error status: ' + response.status);
        }
    })
    .catch(error => {
        resetButtonState(submitBtn, originalText);
        showNotification('Failed to cancel appointment. Please try again.', 'error');
        console.error('Cancel appointment error:', error);
    });
}

function completeAppointment(appointmentId) {
    const form = document.getElementById('completeForm');
    form.action = `/doctor/appointments/${appointmentId}/complete`;
    form.reset();

    const modal = new bootstrap.Modal(document.getElementById('completeModal'));
    modal.show();

    // Remove existing handlers
    const newForm = form.cloneNode(true);
    form.parentNode.replaceChild(newForm, form);
    const updatedForm = document.getElementById('completeForm');
    const submitBtn = updatedForm.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;

    updatedForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Completing...';

        const fetchPromise = fetch(updatedForm.action, {
            method: 'POST',
            body: new FormData(updatedForm),
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        });

        createTimeoutPromise(fetchPromise)
        .then(response => {
            if (response.ok) {
                // Try to parse JSON response first
                return response.json().then(data => {
                    if (data.success !== false) {
                        showNotification('Appointment completed successfully!', 'success');
                        modal.hide();
                        setTimeout(() => window.location.reload(), 1000);
                    } else {
                        throw new Error(data.message || 'Failed to complete appointment');
                    }
                }).catch(() => {
                    // Fallback if response is not JSON
                    showNotification('Appointment completed successfully!', 'success');
                    modal.hide();
                    setTimeout(() => window.location.reload(), 1000);
                });
            } else {
                throw new Error('Server returned error status: ' + response.status);
            }
        })
        .catch(error => {
            resetButtonState(submitBtn, originalText);
            showNotification('Failed to complete appointment. Please try again.', 'error');
            console.error('Complete appointment error:', error);
        });
    });
}

function markNoShow(appointmentId) {
    if (!confirm('Are you sure you want to mark this appointment as no show? This action cannot be undone.')) {
        return;
    }

    const btn = event.target.closest('button');
    if (!btn) return;

    const originalHTML = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Processing...';

    const form = document.createElement('form');
    form.method = 'POST';
    form.action = `/doctor/appointments/${appointmentId}/no-show`;

    const csrfToken = document.createElement('input');
    csrfToken.type = 'hidden';
    csrfToken.name = '_token';
    csrfToken.value = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    form.appendChild(csrfToken);
    document.body.appendChild(form);

    const fetchPromise = fetch(form.action, {
        method: 'POST',
        body: new FormData(form),
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    });

    createTimeoutPromise(fetchPromise)
    .then(response => {
        if (response.ok) {
            // Try to parse JSON response first
            return response.json().then(data => {
                if (data.success !== false) {
                    showNotification('Appointment marked as no show successfully!', 'success');
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    throw new Error(data.message || 'Failed to mark appointment as no show');
                }
            }).catch(() => {
                // Fallback if response is not JSON
                showNotification('Appointment marked as no show successfully!', 'success');
                setTimeout(() => window.location.reload(), 1000);
            });
        } else {
            throw new Error('Server returned error status: ' + response.status);
        }
    })
    .catch(error => {
        resetButtonState(btn, originalHTML);
        showNotification('Failed to mark appointment as no show. Please try again.', 'error');
        console.error('Mark no-show error:', error);
    })
    .finally(() => {
        if (document.body.contains(form)) {
            document.body.removeChild(form);
        }
    });
}

// Notification System
function showNotification(message, type = 'info') {
    const alertTypes = {
        success: 'alert-success',
        info: 'alert-info',
        warning: 'alert-warning',
        error: 'alert-danger'
    };

    const icons = {
        success: 'fas fa-check-circle',
        info: 'fas fa-info-circle',
        warning: 'fas fa-exclamation-triangle',
        error: 'fas fa-times-circle'
    };

    // Create notification element
    const notification = document.createElement('div');
    notification.className = `alert ${alertTypes[type]} alert-dismissible fade show position-fixed`;
    notification.style.cssText = 'top: 100px; right: 20px; z-index: 9999; min-width: 300px; max-width: 400px; margin-top: 10px;';

    const contentDiv = document.createElement('div');
    contentDiv.className = 'd-flex align-items-center';

    const icon = document.createElement('i');
    icon.className = `${icons[type]} me-2`;

    const span = document.createElement('span');
    span.textContent = message;

    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'btn-close';
    button.setAttribute('data-bs-dismiss', 'alert');
    button.setAttribute('aria-label', 'Close');

    contentDiv.appendChild(icon);
    contentDiv.appendChild(span);
    contentDiv.appendChild(button);
    notification.appendChild(contentDiv);

    document.body.appendChild(notification);

    setTimeout(() => {
        if (notification.parentNode) {
            notification.remove();
        }
    }, 3000);
}

// Prescription delete functionality
let prescriptionToDelete = null;

function deletePrescription(prescriptionId, medicationName) {
    prescriptionToDelete = prescriptionId;
    // Sanitize the medication name to prevent XSS by using textContent instead of innerHTML
    const cleanName = medicationName.replace(/</g, '&lt;').replace(/>/g, '&gt;');
    document.getElementById('deletePrescriptionName').textContent = cleanName;
    new bootstrap.Modal(document.getElementById('deletePrescriptionModal')).show();
}

function confirmDeletePrescription() {
    if (!prescriptionToDelete) return;

    const confirmBtn = document.getElementById('confirmDeleteBtn');
    const originalText = confirmBtn.innerHTML;
    confirmBtn.disabled = true;
    confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Deleting...';

    fetch(`/prescriptions/${prescriptionToDelete}`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Close modal
            bootstrap.Modal.getInstance(document.getElementById('deletePrescriptionModal')).hide();

            // Remove prescription from DOM
            const prescriptionCard = document.querySelector(`[data-prescription-id="${prescriptionToDelete}"]`);
            if (prescriptionCard) {
                prescriptionCard.remove();
            } else {
                // Fallback: reload the page
                location.reload();
            }

            showNotification('Prescription deleted successfully!', 'success');
        } else {
            throw new Error(data.message || 'Failed to delete prescription');
        }
    })
    .catch(error => {
        console.error('Delete error:', error);
        showNotification(error.message || 'Failed to delete prescription. Please try again.', 'error');
    })
    .finally(() => {
        confirmBtn.disabled = false;
        confirmBtn.innerHTML = originalText;
        prescriptionToDelete = null;
    });
}

// Notification System
function showNotification(message, type = 'info') {
    const alertTypes = {
        success: 'alert-success',
        info: 'alert-info',
        warning: 'alert-warning',
        error: 'alert-danger'
    };

    const icons = {
        success: 'fas fa-check-circle',
        info: 'fas fa-info-circle',
        warning: 'fas fa-exclamation-triangle',
        error: 'fas fa-times-circle'
    };

    // Create notification element
    const notification = document.createElement('div');
    notification.className = `alert ${alertTypes[type]} alert-dismissible fade show position-fixed`;
    // Position below the top navigation bar (assuming ~80px height) and to the right
    notification.style.cssText = 'top: 100px; right: 20px; z-index: 9999; min-width: 300px; max-width: 400px; margin-top: 10px;';

    // Create content safely to prevent XSS
    const contentDiv = document.createElement('div');
    contentDiv.className = 'd-flex align-items-center';

    const icon = document.createElement('i');
    icon.className = `${icons[type]} me-2`;

    const span = document.createElement('span');
    // Sanitize message to prevent XSS
    span.textContent = message.replace(/</g, '&lt;').replace(/>/g, '&gt;');

    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'btn-close';
    button.setAttribute('data-bs-dismiss', 'alert');
    button.setAttribute('aria-label', 'Close');

    contentDiv.appendChild(icon);
    contentDiv.appendChild(span);
    contentDiv.appendChild(button);
    notification.appendChild(contentDiv);

    document.body.appendChild(notification);

    // Auto remove after 3 seconds
    setTimeout(() => {
        if (notification.parentNode) {
            notification.remove();
        }
    }, 3000);
}


// Initialize tooltips and other Bootstrap components
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Bootstrap tooltips if any
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Initialize form transformation
    initializeFormTransformation();

    // Debug ML Risk Assessment Data
    debugMLRiskAssessment();
});

function debugMLRiskAssessment() {
    console.log('🔍 ML RISK ASSESSMENT DEBUG');
    console.log('===========================');

    // Check if risk scores exist
    @php
        $riskScore = $appointment->patient?->patientRiskScores?->where('appointment_id', $appointment->id)?->first();
    @endphp

    @if($riskScore)
        console.log('✅ Risk Scores Found:', {
            no_show_risk: '{{ number_format($riskScore->no_show_risk * 100, 1) }}%',
            hospitalization_risk: '{{ number_format($riskScore->hospitalization_risk * 100, 1) }}%'
        });
    @else
        console.log('❌ NO RISK SCORES FOUND - ML prediction has not run');
    @endif

    // Check training data adequacy
    @php
        $service = app(\App\Services\PredictiveAnalyticsService::class);
        $reflection = new ReflectionClass($service);
        $method = $reflection->getMethod('checkTrainingDataAdequacy');
        $method->setAccessible(true);
        $adequacy = $method->invoke($service);
    @endphp

    console.log('🎓 Training Data Status:', {
        adequate: {{ $adequacy['adequate'] ? 'true' : 'false' }},
        total_appointments: {{ $adequacy['total_appointments'] }},
        using_fallback: '{{ !$adequacy['adequate'] ? 'YES (Rule-based)' : 'NO (ML)' }}'
    });

    // Show what SHOULD be calculated
    @php
        if ($appointment->patient) {
            $result = $service->predictRisks($appointment->patient, $appointment);
            $expectedNoShow = number_format($result['no_show_risk'] * 100, 1);
            $expectedHospitalization = number_format($result['hospitalization_risk'] * 100, 1);
        } else {
            $expectedNoShow = 'N/A';
            $expectedHospitalization = 'N/A';
        }
    @endphp

    // Feature Extraction Debug
    @php
        if ($appointment->patient) {
            $extractor = app(\App\Services\FeatureExtractor::class);
            $features = $extractor->extractFeatures($appointment->patient, $appointment);
            $hasHighRisk = $extractor->hasHighRiskCondition($appointment->patient);
        } else {
            $features = [0,0,0,0,0];
            $hasHighRisk = false;
        }
    @endphp
    console.log('🔧 ML Features Extracted:', {
        features_array: {{ json_encode($features) }},
        breakdown: {
            no_show_count: {{ $features[0] ?? 0 }},
            last_visit_days: {{ $features[1] ?? 0 }},
            age: {{ $features[2] ?? 0 }},
            gender_encoded: {{ $features[3] ?? 0 }},
            chronic_conditions_from_appointments: {{ $features[4] ?? 0 }}
        },
        has_high_risk_conditions: {{ $hasHighRisk ? 'true' : 'false' }}
    });

    console.log('🎯 Expected Calculation:', {
        no_show_risk: '{{ $expectedNoShow }}%',
        hospitalization_risk: '{{ $expectedHospitalization }}%'
    });

    @if($riskScore)
        console.log('📊 Match Check:', {
            scores_match: '{{ ($expectedNoShow === number_format($riskScore->no_show_risk * 100, 1) && $expectedHospitalization === number_format($riskScore->hospitalization_risk * 100, 1)) ? 'YES' : 'NO' }}'
        });
    @endif
}

// Dynamic form transformation
function initializeFormTransformation() {
    // Handle form field transformation
    const formSelect = document.getElementById('form');
    if (formSelect) {
        formSelect.addEventListener('change', function() {
            if (this.value === 'other') {
                transformToTextInput(this, 'form');
            } else {
                ensureSelectField(this, 'form');
            }
        });
    }

    // Handle route field transformation
    const routeSelect = document.getElementById('route');
    if (routeSelect) {
        routeSelect.addEventListener('change', function() {
            if (this.value === 'other') {
                transformToTextInput(this, 'route');
            } else {
                ensureSelectField(this, 'route');
            }
        });
    }

    // Handle frequency field transformation
    const frequencySelect = document.getElementById('frequency');
    if (frequencySelect) {
        frequencySelect.addEventListener('change', function() {
            if (this.value === 'other') {
                transformToTextInput(this, 'frequency');
            } else {
                ensureSelectField(this, 'frequency');
            }
        });
    }

    // Handle duration field transformation
    const durationSelect = document.getElementById('duration');
    if (durationSelect) {
        durationSelect.addEventListener('change', function() {
            if (this.value === 'other') {
                transformToTextInput(this, 'duration');
            } else {
                ensureSelectField(this, 'duration');
            }
        });
    }
}

function transformToTextInput(selectElement, fieldType) {
    const parent = selectElement.parentElement;
    const currentValue = selectElement.value;

    // Create text input
    const textInput = document.createElement('input');
    textInput.type = 'text';
    textInput.className = 'form-control';
    textInput.id = fieldType;
    textInput.name = fieldType;
    textInput.required = true;

    // Set field-specific placeholder
    const placeholders = {
        'form': 'Enter custom form (e.g., Suppository, Patch)',
        'route': 'Enter custom route (e.g., Topical, Sublingual)',
        'frequency': 'Enter custom frequency (e.g., Every 4 hours)',
        'duration': 'Enter custom duration (e.g., 3 weeks)'
    };
    textInput.placeholder = placeholders[fieldType] || 'Enter custom value';

    // Preserve any existing custom value or set default
    if (currentValue === 'other' || !currentValue) {
        textInput.value = '';
    } else {
        textInput.value = currentValue;
    }

    // Replace select with input
    parent.replaceChild(textInput, selectElement);

    // Focus on the new input
    textInput.focus();
}

function ensureSelectField(currentElement, fieldType) {
    if (currentElement.tagName === 'SELECT') return;

    const parent = currentElement.parentElement;
    const currentValue = currentElement.value;

    // Create select element
    const selectElement = document.createElement('select');
    selectElement.className = 'form-select';
    selectElement.id = fieldType;
    selectElement.name = fieldType;
    selectElement.required = true;

    // Define options for each field type
    const fieldOptions = {
        'form': [
            { value: '', text: 'Select form' },
            { value: 'tablet', text: 'Tablet' },
            { value: 'capsule', text: 'Capsule' },
            { value: 'liquid', text: 'Liquid' },
            { value: 'injection', text: 'Injection' },
            { value: 'cream', text: 'Cream/Ointment' },
            { value: 'inhaler', text: 'Inhaler' },
            { value: 'patch', text: 'Patch' },
            { value: 'other', text: 'Other' }
        ],
        'route': [
            { value: '', text: 'Select route' },
            { value: 'oral', text: 'Oral' },
            { value: 'topical', text: 'Topical' },
            { value: 'intravenous', text: 'Intravenous' },
            { value: 'intramuscular', text: 'Intramuscular' },
            { value: 'subcutaneous', text: 'Subcutaneous' },
            { value: 'inhalation', text: 'Inhalation' },
            { value: 'rectal', text: 'Rectal' },
            { value: 'other', text: 'Other' }
        ],
        'frequency': [
            { value: '', text: 'Select frequency' },
            { value: 'once daily', text: 'Once daily' },
            { value: 'twice daily', text: 'Twice daily' },
            { value: 'three times daily', text: 'Three times daily' },
            { value: 'four times daily', text: 'Four times daily' },
            { value: 'every 6 hours', text: 'Every 6 hours' },
            { value: 'every 8 hours', text: 'Every 8 hours' },
            { value: 'every 12 hours', text: 'Every 12 hours' },
            { value: 'as needed', text: 'As needed' },
            { value: 'other', text: 'Other' }
        ],
        'duration': [
            { value: '', text: 'Select duration' },
            { value: '3 days', text: '3 days' },
            { value: '7 days', text: '7 days' },
            { value: '10 days', text: '10 days' },
            { value: '14 days', text: '14 days' },
            { value: '1 month', text: '1 month' },
            { value: '2 months', text: '2 months' },
            { value: '3 months', text: '3 months' },
            { value: '6 months', text: '6 months' },
            { value: 'other', text: 'Other' }
        ]
    };

    const options = fieldOptions[fieldType] || [];
    options.forEach(option => {
        const optionElement = document.createElement('option');
        optionElement.value = option.value;
        optionElement.textContent = option.text;
        if (option.value === currentValue) {
            optionElement.selected = true;
        }
        selectElement.appendChild(optionElement);
    });

    // Replace input with select
    parent.replaceChild(selectElement, currentElement);
}

function resetFormField() {
    const fields = ['form', 'route', 'frequency', 'duration'];
    fields.forEach(fieldType => {
        const element = document.getElementById(fieldType);
        if (element && element.tagName !== 'SELECT') {
            ensureSelectField(element, fieldType);
        }
    });
}

// Diagnosis form functionality
function toggleDiagnosisForm() {
    const diagnosisSection = document.getElementById('diagnosis-section');
    const isVisible = diagnosisSection.style.display !== 'none';

    if (isVisible) {
        // Hide the form
        diagnosisSection.style.display = 'none';
        // Scroll to the Next Steps section
        document.querySelector('.table-card.mb-4').scrollIntoView({ behavior: 'smooth' });
    } else {
        // Show the form
        diagnosisSection.style.display = 'block';
        // Scroll to the diagnosis section
        diagnosisSection.scrollIntoView({ behavior: 'smooth' });
        // Focus on the diagnosis text area
        setTimeout(() => {
            document.getElementById('diagnosis_text').focus();
        }, 300);
    }
}

// Voice recording functionality for diagnosis
let mediaRecorder = null;
let audioChunks = [];
let isRecording = false;

document.addEventListener('DOMContentLoaded', function() {
    // Check if there are validation errors and show the diagnosis form if needed
    const hasErrors = @json($errors->any());
    if (hasErrors) {
        const diagnosisSection = document.getElementById('diagnosis-section');
        diagnosisSection.style.display = 'block';

        // Scroll to the diagnosis section to make errors visible
        diagnosisSection.scrollIntoView({ behavior: 'smooth' });
    }

    // Initialize voice recording buttons
    const startRecordingBtn = document.getElementById('startRecording');
    const stopRecordingBtn = document.getElementById('stopRecording');
    const playRecordingBtn = document.getElementById('playRecording');
    const audioPlayback = document.getElementById('audioPlayback');
    const recordingStatus = document.getElementById('recordingStatus');

    if (startRecordingBtn) {
        startRecordingBtn.addEventListener('click', startVoiceRecording);
    }
    if (stopRecordingBtn) {
        stopRecordingBtn.addEventListener('click', stopVoiceRecording);
    }
    if (playRecordingBtn) {
        playRecordingBtn.addEventListener('click', function() {
            if (audioPlayback.src) {
                audioPlayback.play();
            }
        });
    }

    function startVoiceRecording() {
        navigator.mediaDevices.getUserMedia({ audio: true })
            .then(stream => {
                mediaRecorder = new MediaRecorder(stream);
                audioChunks = [];

                mediaRecorder.ondataavailable = event => {
                    audioChunks.push(event.data);
                };

                mediaRecorder.onstop = () => {
                    const audioBlob = new Blob(audioChunks, { type: 'audio/wav' });
                    const audioUrl = URL.createObjectURL(audioBlob);
                    audioPlayback.src = audioUrl;
                    audioPlayback.style.display = 'block';
                    playRecordingBtn.style.display = 'inline-block';

                    // Create a file input for the recorded audio
                    const fileInput = document.getElementById('voice_files');
                    const file = new File([audioBlob], 'voice_recording.wav', { type: 'audio/wav' });

                    // Create a DataTransfer to set the file
                    const dt = new DataTransfer();
                    dt.items.add(file);
                    fileInput.files = dt.files;
                };

                mediaRecorder.start();
                isRecording = true;

                startRecordingBtn.style.display = 'none';
                stopRecordingBtn.style.display = 'inline-block';
                recordingStatus.textContent = 'Recording... Click "Stop Recording" when finished.';
                recordingStatus.style.color = 'red';
            })
            .catch(error => {
                console.error('Error accessing microphone:', error);
                recordingStatus.textContent = 'Error: Could not access microphone. Please check permissions.';
                recordingStatus.style.color = 'red';
            });
    }

    function stopVoiceRecording() {
        if (mediaRecorder && isRecording) {
            mediaRecorder.stop();
            mediaRecorder.stream.getTracks().forEach(track => track.stop());
            isRecording = false;

            stopRecordingBtn.style.display = 'none';
            startRecordingBtn.style.display = 'inline-block';
            recordingStatus.textContent = 'Recording saved. You can play it back or upload additional files.';
            recordingStatus.style.color = 'green';
        }
    }
});

// Workflow selector functionality
document.addEventListener('DOMContentLoaded', function() {
    const workflowButtons = document.querySelectorAll('.workflow-btn');
    const workflowText = document.getElementById('workflow-text');

    const workflowDescriptions = {
        'manual': 'Manual Entry: Fill the form directly with your prescription details.',
        'ai-first': 'AI First: Click AI button first, then review and use suggestions to fill the form.',
        'ai-assisted': 'AI Assisted: Fill some form fields, then use AI for additional guidance.',
        'explore': 'Explore AI: Review AI suggestions for learning, then fill form manually.'
    };

    workflowButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Remove active class from all buttons
            workflowButtons.forEach(btn => btn.classList.remove('active'));
            // Add active class to clicked button
            this.classList.add('active');

            // Update description
            const workflow = this.dataset.workflow;
            workflowText.textContent = workflowDescriptions[workflow];

            // Optional: Show/hide AI section based on workflow
            const aiSection = document.querySelector('.ai-section');
            if (aiSection) {
                if (workflow === 'manual') {
                    aiSection.style.display = 'none';
                } else {
                    aiSection.style.display = 'block';
                }
            }
        });
    });

    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Initialize AI Medical Copilot save button
    const saveButton = document.getElementById('saveCopilotAnalysisSection');
    if (saveButton) {
        saveButton.addEventListener('click', function() {
            const appointmentId = window.currentAppointmentId; // This should be set when opening the section

            if (!appointmentId) {
                showNotification('Error: Appointment ID not found', 'error');
                return;
            }

            // Collect current analysis data from the UI
            const analysisData = collectAnalysisData();

            // Include checkboxes for clinical note inclusion
            const includeInNote = {
                summary: document.getElementById('includeSummaryInNoteSection').checked,
                considerations: document.getElementById('includeConsiderationsInNoteSection').checked,
                questions: document.getElementById('includeQuestionsInNoteSection').checked,
                red_flags: document.getElementById('includeRedFlagsInNoteSection').checked
            };

            // Save the analysis
            saveAICopilotAnalysis(appointmentId, analysisData, includeInNote);
        });
    }
});

// Function to submit diagnosis form via AJAX
function submitDiagnosisForm() {
    const form = document.getElementById('diagnosisForm');
    const formData = new FormData(form);

    // Disable the submit button to prevent multiple submissions
    const submitButton = document.querySelector('#diagnosisForm button[type="button"][onclick="submitDiagnosisForm()"]');
    const originalButtonText = submitButton.innerHTML;
    submitButton.disabled = true;
    submitButton.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Creating Diagnosis...';

    fetch(form.action, {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json'
        }
    })
    .then(async response => {
        // Clone the response to handle both JSON and text cases
        const responseClone = response.clone();

        // Check if the response is OK
        if (!response.ok) {
            // For 422 validation errors, Laravel returns JSON with validation errors
            if (response.status === 422) {
                const errorData = await response.json();
                // Return the error data in a consistent format
                return {
                    success: false,
                    message: 'Validation failed',
                    errors: errorData.errors || {}
                };
            } else {
                // For other error statuses, try to get error response as JSON first
                try {
                    const errorData = await response.json();
                    return {
                        success: false,
                        message: errorData.message || `HTTP error! status: ${response.status}`,
                        errors: errorData.errors || {}
                    };
                } catch (jsonError) {
                    // If JSON parsing fails, get response as text from the clone
                    try {
                        const errorText = await responseClone.text();
                        return {
                            success: false,
                            message: `HTTP error! status: ${response.status}, message: ${errorText.substring(0, 200)}...`,
                            errors: {}
                        };
                    } catch (textError) {
                        // If both fail, return a generic error
                        return {
                            success: false,
                            message: `HTTP error! status: ${response.status}`,
                            errors: {}
                        };
                    }
                }
            }
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            // Show success toast notification
            showNotification(data.message || 'Diagnosis created successfully!', 'success');

            // Reset and hide the form
            form.reset();
            document.getElementById('diagnosis-section').style.display = 'none';

            // Reload the page after a delay to show updated content
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        } else {
            // Clear previous validation errors
            clearValidationErrors();

            // Show error notification
            let errorMessage = data.message || 'Failed to create diagnosis. Please try again.';

            // Handle Laravel's validation error format and display on form
            if (data.errors) {
                // Format validation errors and display on form fields
                for (const field in data.errors) {
                    displayValidationError(field, data.errors[field].join(', '));
                    errorMessage += ' ' + data.errors[field].join(', ');
                }
            }

            showNotification(errorMessage, 'error');
        }
    })
    .catch(error => {
        console.error('Error creating diagnosis:', error);
        // Extract error message from the error object
        let errorMessage = 'An error occurred while creating the diagnosis. Please try again.';
        if (error.message) {
            errorMessage = error.message;
        }
        showNotification(errorMessage, 'error');
    })
    .finally(() => {
        // Re-enable the submit button
        submitButton.disabled = false;
        submitButton.innerHTML = originalButtonText;
    });
}

// Helper function to display validation error for a specific field
function displayValidationError(fieldName, errorMessage) {
    // Find the input field by name
    let field = document.querySelector(`[name="${fieldName}"]`);

    // Special handling for nested array fields like patient_data[height]
    if (!field && fieldName.includes('[')) {
        const normalizedFieldName = fieldName.replace(/\[/g, '\\[').replace(/\]/g, '\\]');
        field = document.querySelector(`[name="${normalizedFieldName}"]`);
    }

    if (field) {
        // Add error styling to the field
        field.classList.add('is-invalid');

        // Check if error feedback element already exists
        let errorElement = field.parentNode.querySelector('.invalid-feedback');

        if (!errorElement) {
            // Create error feedback element
            errorElement = document.createElement('div');
            errorElement.className = 'invalid-feedback';
            field.parentNode.appendChild(errorElement);
        }

        // Set the error message
        errorElement.textContent = errorMessage;
    }

    // Special handling for array fields like voice_files[]
    if (fieldName === 'voice_files') {
        const fields = document.querySelectorAll('[name="voice_files[]"]');
        fields.forEach(fileField => {
            fileField.classList.add('is-invalid');
        });
    }
}

// Helper function to clear validation errors
function clearValidationErrors() {
    // Remove error styling and messages from all fields
    const invalidFields = document.querySelectorAll('.is-invalid');
    invalidFields.forEach(field => {
        field.classList.remove('is-invalid');
    });

    // Remove all error message elements
    const errorMessages = document.querySelectorAll('.invalid-feedback');
    errorMessages.forEach(element => {
        element.remove();
    });
}

// AI Medical Copilot section functionality
function toggleAIMedicalCopilotForm() {
    const copilotSection = document.getElementById('ai-medical-copilot-section');
    const isVisible = copilotSection.style.display !== 'none';

    if (isVisible) {
        // Hide the form
        copilotSection.style.display = 'none';
        // Scroll to the Next Steps section
        document.querySelector('.table-card.mb-4').scrollIntoView({ behavior: 'smooth' });
    } else {
        // Show the form
        copilotSection.style.display = 'block';
        // Scroll to the AI copilot section
        copilotSection.scrollIntoView({ behavior: 'smooth' });

        // Initialize the AI Medical Copilot for this appointment
        const appointmentId = {{ $appointment->id }};
        initializeAIMedicalCopilot(appointmentId);
    }
}

// Function to initialize AI Medical Copilot
function initializeAIMedicalCopilot(appointmentId) {
    // Show loading state
    document.getElementById('copilotLoadingSection').style.display = 'block';
    document.getElementById('copilotContentSection').style.display = 'none';
    document.getElementById('copilotErrorSection').style.display = 'none';

    // Collect structured data from the appointment
    const structuredData = collectStructuredData(appointmentId);

    // Call AI Medical Copilot API
    callAIMedicalCopilotAPI(appointmentId, structuredData);
}

// Function to collect structured data from the appointment
function collectStructuredData(appointmentId) {
    // This would be populated with actual data from the appointment
    // For now, we'll use sample data that matches the required structure

    return {
        complaint: {
            chief_complaint: document.querySelector('[data-appointment-reason]')?.textContent || '{{ $appointment->reason }}',
            onset: 'recent',
            severity: 'moderate',
            associated_symptoms: []
        },
        vitals: {
            bp: '',
            hr: null,
            spo2: null,
            temperature: null
        },
        history: {
            chronic_conditions: [],
            medications: [],
            allergies: []
        },
        labs: {},
        previous_visits: {
            last_diagnoses: [],
            recent_er_visits: [],
            patterns: []
        }
    };
}

// Function to call AI Medical Copilot API
function callAIMedicalCopilotAPI(appointmentId, structuredData) {
    fetch(`/ai/appointments/${appointmentId}/medical-copilot`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            complaint: structuredData.complaint,
            vitals: structuredData.vitals,
            history: structuredData.history,
            labs: structuredData.labs,
            previous_visits: structuredData.previous_visits
        })
    })
    .then(response => response.json())
    .then(response => {
        // Hide loading, show content
        document.getElementById('copilotLoadingSection').style.display = 'none';
        document.getElementById('copilotContentSection').style.display = 'block';

        // Check for errors
        if (response.error) {
            showCopilotErrorSection(response.message || response.error);
            return;
        }

        if (response.disabled) {
            showCopilotErrorSection('AI Medical Copilot is currently disabled');
            return;
        }

        // Store the response for saving
        window.currentCopilotResponse = response;
        window.currentAppointmentId = appointmentId;

        // Populate the UI with AI analysis
        populateCopilotUISection(response);

        // Log success
        console.log('AI Medical Copilot analysis successful', response);
    })
    .catch(error => {
        // Hide loading, show error
        document.getElementById('copilotLoadingSection').style.display = 'none';

        const errorMessage = error.message || 'Failed to connect to AI Medical Copilot';
        showCopilotErrorSection(errorMessage);

        // Log error
        console.error('AI Medical Copilot error:', errorMessage);
    });
}

// Function to show error in section
function showCopilotErrorSection(message) {
    document.getElementById('copilotErrorMessageSection').textContent = message;
    document.getElementById('copilotErrorSection').style.display = 'block';
    document.getElementById('copilotContentSection').style.display = 'none';
}

// Function to populate UI with AI analysis in section
function populateCopilotUISection(response) {
    // Medical Case Summary
    const summaryContent = `
        <p class="mb-0">${response.medical_case_summary || 'No summary available'}</p>
        <div class="mt-2 small text-muted">
            <i class="fas fa-info-circle me-1"></i>
            Smart summary for quick case understanding
        </div>
    `;
    document.getElementById('copilotSummarySection').innerHTML = summaryContent;

    // Differential Considerations
    let considerationsHtml = '<p><strong>Possible considerations (not diagnoses):</strong></p>';
    if (Array.isArray(response.differential_considerations) && response.differential_considerations.length > 0) {
        considerationsHtml += '<ul class="copilot-list">';
        response.differential_considerations.forEach(item => {
            if (typeof item === 'object' && item.consideration) {
                considerationsHtml += `<li>
                    <strong>${item.consideration}</strong>
                    ${item.rationale ? `<br><small class="text-muted">${item.rationale}</small>` : ''}
                </li>`;
            } else {
                // Fallback for string format
                considerationsHtml += `<li>${item}</li>`;
            }
        });
        considerationsHtml += '</ul>';
    } else {
        considerationsHtml = '<p class="text-muted">No specific considerations identified based on current data.</p>';
    }
    document.getElementById('copilotConsiderationsSection').innerHTML = considerationsHtml;

    // Follow-up Questions
    let questionsHtml = '<p><strong>Questions to help complete the clinical picture:</strong></p>';
    if (Array.isArray(response.follow_up_questions) && response.follow_up_questions.length > 0) {
        questionsHtml += '<ul class="copilot-list">';
        response.follow_up_questions.forEach(question => {
            questionsHtml += `<li>${question}</li>`;
        });
        questionsHtml += '</ul>';
    } else {
        questionsHtml = '<p class="text-muted">No additional questions suggested based on current information.</p>';
    }
    document.getElementById('copilotQuestionsSection').innerHTML = questionsHtml;

    // Red Flags
    let redFlagsHtml = '<p><strong>Potential red flags detected:</strong></p>';
    if (Array.isArray(response.red_flags) && response.red_flags.length > 0) {
        redFlagsHtml += '<ul class="copilot-list">';
        response.red_flags.forEach(flag => {
            redFlagsHtml += `<li>${flag}</li>`;
        });
        redFlagsHtml += '</ul>';
    } else {
        redFlagsHtml = '<p class="text-success">No immediate red flags detected based on available data.</p>';
    }
    document.getElementById('copilotRedFlagsSection').innerHTML = redFlagsHtml;

    // Compliance label
    if (response.compliance && response.compliance.label) {
        document.getElementById('copilotComplianceLabelSection').textContent = response.compliance.label;
    }

    // Patient History (if available in response)
    if (response.patient_history) {
        const history = response.patient_history;
        let historyHtml = '';

        if (Array.isArray(history.previous_diagnoses) && history.previous_diagnoses.length > 0) {
            historyHtml += '<h6 class="text-primary mb-2"><i class="fas fa-stethoscope me-1"></i>Previous Diagnoses:</h6>';
            historyHtml += '<ul class="copilot-list mb-3">';
            history.previous_diagnoses.forEach(diagnosis => {
                historyHtml += `<li>${diagnosis}</li>`;
            });
            historyHtml += '</ul>';
        }

        if (Array.isArray(history.chronic_conditions) && history.chronic_conditions.length > 0) {
            historyHtml += '<h6 class="text-primary mb-2"><i class="fas fa-heartbeat me-1"></i>Chronic Conditions:</h6>';
            historyHtml += '<ul class="copilot-list mb-3">';
            history.chronic_conditions.forEach(condition => {
                historyHtml += `<li>${condition}</li>`;
            });
            historyHtml += '</ul>';
        }

        if (Array.isArray(history.previous_ai_analyses) && history.previous_ai_analyses.length > 0) {
            historyHtml += '<h6 class="text-primary mb-2"><i class="fas fa-brain me-1"></i>Previous AI Analyses:</h6>';
            history.previous_ai_analyses.forEach(analysis => {
                historyHtml += `<div class="border-start border-info border-3 ps-3 mb-3">
                    <small class="text-muted">${analysis.generated_at}</small>
                    <p class="mb-1">${analysis.summary}</p>
                    ${Array.isArray(analysis.red_flags) && analysis.red_flags.length > 0 ?
                        `<small class="text-danger">⚠️ Red flags: ${analysis.red_flags.join(', ')}</small>` :
                        '<small class="text-success">✓ No red flags</small>'}
                </div>`;
            });
        }

        if (!historyHtml) {
            historyHtml = '<p class="text-muted">No significant patient history available.</p>';
        }

        document.getElementById('copilotHistorySection').innerHTML = historyHtml;
    }

    // Add disclaimer if available
    if (response.legal_disclaimer) {
        const disclaimerDiv = document.createElement('div');
        disclaimerDiv.className = 'copilot-disclaimer mt-3';
        disclaimerDiv.innerHTML = `<i class="fas fa-shield-alt me-1"></i> ${response.legal_disclaimer}`;
        document.querySelector('.copilot-disclaimer').parentNode.appendChild(disclaimerDiv);
    }

    // Initialize tab functionality for the section
    initializeCopilotTabFunctionality();
}

// Function to initialize tab functionality for the section
function initializeCopilotTabFunctionality() {
    // Add event listeners to the tab buttons
    const tabButtons = document.querySelectorAll('#ai-medical-copilot-section .copilot-tab');
    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            const tabId = this.getAttribute('data-tab');

            // Update tab buttons
            tabButtons.forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');

            // Update tab content
            const tabContents = document.querySelectorAll('#ai-medical-copilot-section .copilot-tab-content');
            tabContents.forEach(content => {
                content.classList.remove('active');
                if (content.getAttribute('data-tab-content') === tabId) {
                    content.classList.add('active');
                }
            });
        });
    });
}


// Function to collect analysis data from the current UI state
function collectAnalysisData() {
    // Extract data from the global response object if available
    if (window.currentCopilotResponse) {
        return window.currentCopilotResponse;
    }

    // Fallback: extract from current display (less reliable)
    return {
        medical_case_summary: document.querySelector('#copilotSummarySection p')?.textContent.trim() || 'No summary available',
        differential_considerations: extractListItems('#copilotConsiderationsSection li'),
        follow_up_questions: extractListItems('#copilotQuestionsSection li'),
        red_flags: extractListItems('#copilotRedFlagsSection li'),
        disclaimer: 'This content is generated by AI Medical Copilot for clinical decision support only. All medical decisions must be made by qualified healthcare professionals.',
        compliance: {
            ai_generated: true,
            physician_verification_required: true,
            label: 'AI-generated draft. Physician verified.',
            timestamp: new Date().toISOString(),
            generated_by: 'AI Medical Copilot',
            version: 'ai-copilot-clinical-v1.1'
        },
        legal_disclaimer: 'This content is generated by AI Medical Copilot for clinical decision support only. All medical decisions must be made by qualified healthcare professionals.'
    };
}

// Helper function to extract list items from HTML
function extractListItems(selector) {
    const items = [];
    const elements = document.querySelectorAll(selector);
    elements.forEach(element => {
        const text = element.cloneNode(true).textContent.trim();
        if (text && text !== 'Loading...' && !text.includes('Loading')) {
            items.push(text);
        }
    });
    return items;
}

// Function to save AI copilot analysis
function saveAICopilotAnalysis(appointmentId, analysisData, includeInNote) {
    fetch(`/ai/appointments/${appointmentId}/ai-analyses/save`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            analysis_data: analysisData,
            include_in_note: includeInNote
        })
    })
    .then(response => response.json())
    .then(response => {
        if (response.success) {
            showNotification('AI Medical Copilot analysis saved successfully!', 'success');

            // Close the section after a short delay
            setTimeout(() => {
                document.getElementById('ai-medical-copilot-section').style.display = 'none';
            }, 1500);
        } else {
            showNotification(response.message || 'Failed to save analysis', 'error');
        }
    })
    .catch(error => {
        const errorMessage = error.message || 'Failed to save AI analysis';
        showNotification(errorMessage, 'error');
        console.error('Save AI analysis error:', errorMessage);
    });
}

// Function to view patient's AI analysis history
function viewPatientAIAnalyses(patientId) {
    // Show the AI history section
    const historySection = document.getElementById('ai-history-section');
    let shouldScroll = true;

    if (!historySection) {
        // Create the AI history section if it doesn't exist
        createAIHistorySection();
    } else {
        // Check if section is currently visible
        const isVisible = historySection.style.display !== 'none';
        if (!isVisible) {
            // If it's hidden, show it
            historySection.style.display = 'block';
        } else {
            // If it's already visible, we might want to scroll anyway to bring it into view
            shouldScroll = true;
        }
    }

    // Load patient AI analyses
    loadPatientAIAnalyses(patientId);

    // Small delay to ensure DOM is updated before scrolling
    setTimeout(() => {
        if (shouldScroll) {
            const section = document.getElementById('ai-history-section');
            if (section) {
                section.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }
    }, 100);
}

// Function to create AI history section
function createAIHistorySection() {
    // Check if section already exists
    if (document.getElementById('ai-history-section')) {
        return;
    }

    // Create the section element
    const section = document.createElement('div');
    section.id = 'ai-history-section';
    section.className = 'table-card';
    section.style.display = 'block'; // Start visible to show loading

    section.innerHTML = `
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="mb-0 fw-bold text-info">
                    <i class="fas fa-history me-2"></i>Patient AI Analysis History
                </h4>
                <p class="mb-0 text-muted small">Previous AI Medical Copilot analyses for this patient</p>
            </div>
            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="toggleAIHistorySection()">
                <i class="fas fa-times me-1"></i>Close
            </button>
        </div>

        <div id="aiHistoryContentSection">
            <div class="text-center py-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2">Loading AI analysis history...</p>
            </div>
        </div>
    `;

    // Insert the section after the AI Medical Copilot section or at the end of content
    const copilotSection = document.getElementById('ai-medical-copilot-section');
    const diagnosisSection = document.getElementById('diagnosis-section');

    if (copilotSection) {
        copilotSection.parentNode.insertBefore(section, copilotSection.nextSibling);
    } else if (diagnosisSection) {
        diagnosisSection.parentNode.insertBefore(section, diagnosisSection.nextSibling);
    } else {
        // If neither section exists, append to the main content area
        const mainContent = document.querySelector('.dashboard-container .container');
        if (mainContent) {
            mainContent.appendChild(section);
        }
    }
}

// Function to toggle AI history section
function toggleAIHistorySection() {
    const historySection = document.getElementById('ai-history-section');
    if (!historySection) return;

    const isVisible = historySection.style.display !== 'none';

    if (isVisible) {
        // Hide the section
        historySection.style.display = 'none';
        // Scroll to the Next Steps section
        document.querySelector('.table-card.mb-4').scrollIntoView({ behavior: 'smooth' });
    } else {
        // Show the section
        historySection.style.display = 'block';
        // Scroll to the AI history section
        historySection.scrollIntoView({ behavior: 'smooth' });
    }
}

// Function to load patient AI analyses
function loadPatientAIAnalyses(patientId) {
    const contentElement = document.getElementById('aiHistoryContentSection');
    if (!contentElement) return;

    contentElement.innerHTML = `
        <div class="text-center py-4">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2">Loading AI analysis history...</p>
        </div>
    `;

    // Log for debugging
    console.log('Loading AI analyses for patient ID:', patientId);
    console.log('Fetching from URL:', `/ai/patients/${patientId}/ai-analyses`);

    fetch(`/ai/patients/${patientId}/ai-analyses`, {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json'
        }
    })
    .then(response => {
        console.log('Response status:', response.status);
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(response => {
        console.log('API Response:', response);
        // The response is paginated, so we need to use response.data which contains the analyses
        if (response.data !== undefined) {
            displayAIAnalysesSection(response.data || []);
        } else {
            contentElement.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    ${response.message || 'Failed to load AI analysis history'}
                </div>
            `;
        }
    })
    .catch(error => {
        console.error('Detailed error:', error);
        contentElement.innerHTML = `
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle me-2"></i>
                Failed to load AI analysis history: ${error.message}. Please check browser console for details.
            </div>
        `;
        console.error('Load AI analysis error:', error);
    });
}

// Function to display AI analyses in the section
function displayAIAnalysesSection(analyses) {
    const contentElement = document.getElementById('aiHistoryContentSection');
    if (!contentElement) return;

    if (!analyses || analyses.length === 0) {
        contentElement.innerHTML = `
            <div class="text-center py-5">
                <i class="fas fa-brain fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">No AI Analyses Found</h5>
                <p class="text-muted">This patient hasn't had any AI Medical Copilot analyses saved yet.</p>
            </div>
        `;
        return;
    }

    let html = '<div class="ai-analyses-timeline">';

    analyses.forEach(analysis => {
        const analysisData = typeof analysis.analysis_data === 'string' ?
            JSON.parse(analysis.analysis_data) : analysis.analysis_data;

        html += `
            <div class="ai-analysis-card mb-4">
                <div class="card border-primary">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0">
                                <i class="fas fa-brain me-2"></i>AI Medical Copilot Analysis
                            </h6>
                            <small>${new Date(analysis.generated_at).toLocaleDateString()} at ${new Date(analysis.generated_at).toLocaleTimeString()}</small>
                        </div>
                        <div class="d-flex gap-2">
                            ${analysis.status === 'reviewed' ?
                                '<span class="badge bg-success"><i class="fas fa-check-circle me-1"></i>Reviewed</span>' :
                                '<span class="badge bg-warning"><i class="fas fa-clock me-1"></i>Pending Review</span>'}
                            <a href="/ai/ai-analyses/${analysis.id}" class="btn btn-sm btn-primary" target="_blank">
                                <i class="fas fa-eye me-1"></i>View Details
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="text-primary"><i class="fas fa-file-medical me-1"></i>Summary</h6>
                                <p class="mb-3">${analysisData.medical_case_summary || 'No summary available'}</p>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-warning"><i class="fas fa-list-check me-1"></i>Key Considerations</h6>
                                <ul class="mb-3 small">
                                    ${displayConsiderationsSection(analysisData.differential_considerations || [])}
                                </ul>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="text-info"><i class="fas fa-question-circle me-1"></i>Follow-up Questions</h6>
                                <ul class="mb-3 small">
                                    ${displayQuestionsSection(analysisData.follow_up_questions || [])}
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-danger"><i class="fas fa-flag me-1"></i>Red Flags</h6>
                                <ul class="mb-3 small">
                                    ${displayRedFlagsSection(analysisData.red_flags || [])}
                                </ul>
                            </div>
                        </div>
                        ${analysis.reviewed_at ? `
                            <div class="border-top pt-3 mt-3">
                                <h6 class="text-success"><i class="fas fa-user-md me-1"></i>Physician Review</h6>
                                <p class="mb-1 small text-muted">Reviewed by Dr. ${analysis.reviewer?.name || 'Unknown'} on ${new Date(analysis.reviewed_at).toLocaleDateString()}</p>
                                ${analysis.doctor_notes ? `<p class="mb-0">${analysis.doctor_notes}</p>` : '<p class="text-muted small">No additional notes</p>'}
                            </div>
                        ` : ''}
                    </div>
                </div>
            </div>
        `;
    });

    html += '</div>';
    contentElement.innerHTML = html;
}

// Helper functions for displaying analysis components in the section
function displayConsiderationsSection(considerations) {
    if (!considerations || considerations.length === 0) return '<li class="text-muted">No considerations recorded</li>';

    return considerations.slice(0, 3).map(item => {
        if (typeof item === 'object' && item.consideration) {
            return `<li><strong>${item.consideration}</strong><br><small class="text-muted">${item.rationale || ''}</small></li>`;
        } else {
            return `<li>${item}</li>`;
        }
    }).join('');
}

function displayQuestionsSection(questions) {
    if (!questions || questions.length === 0) return '<li class="text-muted">No questions recorded</li>';
    return questions.slice(0, 3).map(question => `<li>${question}</li>`).join('');
}

function displayRedFlagsSection(flags) {
    if (!flags || flags.length === 0) return '<li class="text-success">No red flags detected</li>';
    return flags.slice(0, 3).map(flag => `<li class="text-danger">${flag}</li>`).join('');
</script>
<script>
// Tab-aware overrides for new design - keep old functions but also switch tabs
(function(){
    const origToggleDiagnosis = window.toggleDiagnosisForm;
    window.toggleDiagnosisForm = function(){
        document.querySelector('[data-bs-target="#tab-diagnosis"]')?.click();
        const el=document.getElementById('diagnosis-section');
        if(el) setTimeout(()=>el.scrollIntoView({behavior:'smooth'}),200);
        if(typeof origToggleDiagnosis==='function' && document.getElementById('diagnosis-section')?.style.display==='none'){
            // Old logic also toggles display, keep it visible
            document.getElementById('diagnosis-section').style.display='block';
        }
    };
    const origToggleAI = window.toggleAIMedicalCopilotForm;
    window.toggleAIMedicalCopilotForm = function(){
        document.querySelector('[data-bs-target="#tab-ai"]')?.click();
        if(typeof origToggleAI==='function') try{origToggleAI();}catch(e){}
    };
})();
function viewPatientAIAnalyses(pid){ window.location.href=`/ai/patients/${pid}/ai-analyses`; }
</script>
@endpush
