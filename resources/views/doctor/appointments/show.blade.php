@extends('master')

@section('title', 'Appointment Details')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/doctor-dashboard.css') }}">
<link rel="stylesheet" href="{{ asset('demos/medical/medical.css') }}">
<style>
/* Match standard headers (appointments/index, patients/show) */
.dashboard-header {
    background: linear-gradient(135deg, #2c5aa0 0%, #1e3a8a 100%) !important;
    border-radius: 12px !important;
    padding: 2.5rem !important;
    margin-bottom: 2rem !important;
    box-shadow: 0 4px 15px rgba(44, 90, 160, 0.15) !important;
    position: relative;
    overflow: hidden;
}
.dashboard-header::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(90deg, #10b981 0%, #059669 100%);
}
.dashboard-header h2 {
    color: #ffffff !important;
    font-weight: 600 !important;
    font-size: 2.2rem !important;
    margin-bottom: 0.5rem !important;
    text-shadow: 0 1px 3px rgba(0,0,0,0.1);
}
.dashboard-header p {
    color: rgba(255, 255, 255, 0.9) !important;
    font-size: 1rem !important;
    font-weight: 400 !important;
    margin-bottom: 0 !important;
}
/* Header actions — professional flat design (no glassmorphism) */
.header-actions-wrap {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 0.65rem;
    min-width: 300px;
}
.header-top-row {
    display: flex;
    align-items: center;
    gap: 0.65rem;
    flex-wrap: wrap;
    justify-content: flex-end;
}
.dashboard-header .status-badge {
    background: #ffffff !important;
    color: #1e293b !important;
    border: 1px solid #e2e8f0 !important;
    box-shadow: 0 1px 3px rgba(0,0,0,0.08) !important;
    border-radius: 99px !important;
    padding: 0.38rem 0.85rem !important;
    font-size: 0.73rem !important;
    font-weight: 700 !important;
    text-transform: capitalize !important;
    letter-spacing: 0 !important;
    line-height: 1 !important;
}
.dashboard-header .status-badge.status-pending { color: #92400e !important; background: #fef3c7 !important; border-color: #fde68a !important; }
.dashboard-header .status-badge.status-confirmed { color: #065f46 !important; background: #d1fae5 !important; border-color: #a7f3d0 !important; }
.dashboard-header .status-badge.status-completed { color: #1e40af !important; background: #dbeafe !important; border-color: #bfdbfe !important; }
.dashboard-header .status-badge.status-cancelled { color: #991b1b !important; background: #fee2e2 !important; border-color: #fecaca !important; }
.dashboard-header .status-badge.status-no_show { color: #475569 !important; background: #f1f5f9 !important; border-color: #e2e8f0 !important; }
.btn-back {
    background: rgba(255,255,255,0.15) !important;
    border: 1px solid rgba(255,255,255,0.32) !important;
    color: #fff !important;
    border-radius: 10px !important;
    padding: 0.5rem 1rem !important;
    font-weight: 600 !important;
    font-size: 0.83rem !important;
    line-height: 1 !important;
    transition: all 0.18s ease !important;
    white-space: nowrap;
}
.btn-back:hover {
    background: #ffffff !important;
    border-color: #ffffff !important;
    color: #1e3a8a !important;
}
.appointment-actions {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    background: transparent;
    border: none;
    padding: 0;
    flex-wrap: wrap;
    justify-content: flex-end;
    box-shadow: none;
}
.action-btn {
    border-radius: 10px !important;
    padding: 0.52rem 1.05rem !important;
    font-size: 0.81rem !important;
    font-weight: 700 !important;
    display: inline-flex !important;
    align-items: center !important;
    gap: 0.4rem !important;
    line-height: 1 !important;
    white-space: nowrap;
    transition: all 0.18s ease !important;
    border: 1px solid transparent !important;
    cursor: pointer;
    box-shadow: 0 1px 3px rgba(0,0,0,0.10) !important;
    letter-spacing: -0.01em;
}
.action-btn:hover { transform: translateY(-1px); box-shadow: 0 4px 10px rgba(0,0,0,0.14) !important; }
.action-btn:active { transform: translateY(0); }
.action-btn-confirm {
    background: #10b981 !important;
    color: #ffffff !important;
    border-color: #10b981 !important;
}
.action-btn-confirm:hover { background: #059669 !important; border-color: #059669 !important; color: #fff !important; }
.action-btn-complete {
    background: #ffffff !important;
    color: #0f172a !important;
    border-color: #ffffff !important;
}
.action-btn-complete:hover { background: #f1f5f9 !important; color: #0f172a !important; border-color: #f1f5f9 !important; }
.action-btn-noshow {
    background: #ffffff !important;
    color: #475569 !important;
    border-color: #e2e8f0 !important;
}
.action-btn-noshow:hover { background: #f8fafc !important; color: #334155 !important; border-color: #cbd5e1 !important; }
.action-btn-danger {
    background: #ffffff !important;
    color: #dc2626 !important;
    border-color: #fecaca !important;
}
.action-btn-danger:hover { background: #fef2f2 !important; color: #b91c1c !important; border-color: #fca5a5 !important; }
.action-btn-muted {
    background: rgba(255,255,255,0.22) !important;
    color: rgba(255,255,255,0.95) !important;
    border-color: rgba(255,255,255,0.22) !important;
    cursor: default !important;
    box-shadow: none !important;
}
.action-btn-muted:hover { transform: none !important; box-shadow: none !important; }
@media (max-width: 992px) {
    .header-actions-wrap { min-width: 0; width: 100%; align-items: stretch; }
    .header-top-row { justify-content: space-between; width: 100%; }
    .appointment-actions { justify-content: flex-start; width: 100%; }
}
@media (max-width: 576px) {
    .dashboard-header { padding: 1.5rem !important; }
    .appointment-actions { gap: 0.4rem; }
    .action-btn { flex: 1; justify-content: center; padding: 0.6rem 0.7rem !important; font-size: 0.79rem !important; }
}
/* Section headers — dark premium for clarity */
.table-card .section-head-modern,
#prescriptions .section-head-modern,
#diagnosis-section .section-head-modern,
#ai-medical-copilot-section .section-head-modern { display:flex; align-items:center; justify-content:space-between; gap:0.75rem; margin:-1.3rem -1.3rem 1.1rem -1.3rem; padding:1rem 1.3rem; background:#f8fafc; border-bottom:1px solid #e2e8f0; border-radius:12px 12px 0 0; flex-wrap:wrap; }
.table-card .section-head-modern .head-left,
#prescriptions .section-head-modern .head-left,
#diagnosis-section .section-head-modern .head-left,
#ai-medical-copilot-section .section-head-modern .head-left { display:flex; align-items:center; gap:0.75rem; }
.table-card .section-head-modern .head-icon,
#prescriptions .section-head-modern .head-icon,
#diagnosis-section .section-head-modern .head-icon,
#ai-medical-copilot-section .section-head-modern .head-icon { width:38px; height:38px; border-radius:9px; display:flex; align-items:center; justify-content:center; font-size:0.95rem; flex-shrink:0; background:#1e293b !important; color:#fff !important; border:1px solid #1e293b !important; }
.table-card .section-head-modern h4,
.table-card .section-head-modern h5 { color:#0f172a !important; font-weight:800 !important; letter-spacing:-0.01em; }
.table-card .section-head-modern p { color:#475569 !important; font-weight:500 !important; }
#prescriptions .prescription-card-premium:hover { box-shadow: 0 8px 20px rgba(15,23,42,0.06); transform: translateY(-1px); }
#prescriptions .form-section { background:#fff; border:1px solid #eef2f7; border-radius:12px; padding:1.1rem; margin-bottom:1rem; box-shadow: 0 1px 4px rgba(15,23,42,0.04); }
#prescriptions .form-section-header { display:flex; align-items:center; justify-content:space-between; padding-bottom:0.65rem; margin-bottom:0.9rem; border-bottom:1px solid #f1f5f9; }
#prescriptions .form-section-title { font-size:0.86rem; font-weight:700; color:#1e293b; margin:0; }

</style>
<style>
/* AI Copilot — premium tabs like workflow */
#ai-medical-copilot-section .d-flex.border-bottom { background:#f8fafc; border:1px solid #eef2f7 !important; border-radius:10px; padding:0.3rem; gap:0.25rem; }
.copilot-tab {
    cursor: pointer;
    padding: 0.55rem 0.9rem;
    border: none;
    background-color: transparent;
    color: #64748b;
    border-radius: 8px;
    font-size:0.82rem;
    font-weight:600;
    transition: all 0.2s ease;
    border-bottom: none;
}
.copilot-tab.active {
    color: #1e293b;
    background:#fff;
    border:1px solid #e2e8f0;
    box-shadow: 0 1px 4px rgba(15,23,42,0.06);
    font-weight: 700;
}

.copilot-tab-content {
    display: none;
    padding: 1.5rem 0;
}

.copilot-tab-content.active {
    display: block;
}

.copilot-section {
    margin-bottom: 1.5rem;
    background:#fff;
    border:1px solid #eef2f7;
    border-radius:12px;
    padding:1.1rem;
    box-shadow: 0 1px 4px rgba(15,23,42,0.04);
}

.copilot-section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
    padding-bottom: 0.5rem;
    border-bottom: 1px solid #e9ecef;
}

.copilot-section-title {
    font-size: 1.1rem;
    font-weight: 600;
    color: #212529;
}

.copilot-badge {
    font-size: 0.875rem;
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
}

.copilot-content {
    background-color: #f8f9fa;
    border-radius: 0.5rem;
    padding: 1.25rem;
    border-left: 4px solid #0d6efd;
}

.copilot-list {
    list-style-type: none;
    padding-left: 0;
}

.copilot-list li {
    padding: 0.5rem 0;
    position: relative;
    padding-left: 1.5rem;
}

.copilot-list li:before {
    content: "•";
    color: #0d6efd;
    position: absolute;
    left: 0;
    font-weight: bold;
}

.copilot-warning {
    background-color: #fff3cd;
    border-left-color: #ffc107;
}

.copilot-danger {
    background-color: #f8d7da;
    border-left-color: #dc3545;
}

.copilot-success {
    background-color: #d1e7dd;
    border-left-color: #198754;
}

.copilot-info {
    background-color: #cff4fc;
    border-left-color: #0dcaf0;
}

.copilot-disclaimer {
    font-size: 0.875rem;
    color: #6c757d;
    background-color: #f8f9fa;
    padding: 1rem;
    border-radius: 0.25rem;
    margin-top: 1rem;
    border: 1px solid #e9ecef;
}

.copilot-actions {
    display: flex;
    gap: 0.75rem;
    justify-content: flex-end;
    margin-top: 1.5rem;
}

.copilot-checkbox {
    margin-right: 0.5rem;
}

.edit-copilot-btn {
    cursor: pointer;
    color: #0d6efd;
    font-size: 0.875rem;
}

.edit-copilot-btn:hover {
    text-decoration: underline;
}

.copilot-loading {
    display: none;
    text-align: center;
    padding: 2rem;
}

.copilot-loading.active {
    display: block;
}

.copilot-loading-spinner {
    width: 3rem;
    height: 3rem;
    border: 0.25rem solid #f3f3f3;
    border-top: 0.25rem solid #0d6efd;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin: 0 auto 1rem;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.copilot-error {
    color: #dc3545;
    background-color: #f8d7da;
    padding: 1rem;
    border-radius: 0.25rem;
    margin-bottom: 1rem;
    border: 1px solid #f5c2c7;
}

.copilot-compliance-label {
    font-size: 0.75rem;
    color: #6c757d;
    text-align: right;
    margin-top: 1rem;
    font-style: italic;
}
</style>
@endpush

@section('content')
<div class="dashboard-container">
    <div class="container appointment-details">
        <!-- Header — standardized like other system headers (appointments/index, patients/show) -->
        <div class="dashboard-header">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <h2><i class="fas fa-user-injured me-2"></i>{{ e($appointment->patient_name ?? 'Unknown Patient') }}</h2>
                    <p>
                        @if($appointment->appointment_type){{ ucfirst(str_replace('_', ' ', $appointment->appointment_type)) }} · @endif<i class="far fa-calendar me-1"></i>{{ $appointment->appointment_date->format('M j, Y \a\t g:i A') }}
                    </p>
                </div>

                <div class="header-actions-wrap">
                    <div class="header-top-row">
                        <span class="status-badge status-{{ $appointment->status }}">
                            <i class="fas fa-{{ $appointment->status == 'pending' ? 'clock' : ($appointment->status == 'confirmed' ? 'check-circle' : ($appointment->status == 'completed' ? 'check-double' : ($appointment->status == 'cancelled' ? 'times-circle' : 'user-times'))) }}"></i>
                            {{ ucfirst(str_replace('_', ' ', $appointment->status)) }}
                        </span>
                        <a href="{{ route('doctor.appointments.index') }}" class="btn btn-back">
                            <i class="fas fa-arrow-left me-2"></i>Back to Appointments
                        </a>
                    </div>

                    @if(auth()->check() && auth()->user()->isDoctor())
                        @if(in_array($appointment->status, ['pending','confirmed']))
                        <div class="appointment-actions" role="group" aria-label="Appointment actions">
                            @if($appointment->status == 'pending')
                                <button onclick="confirmAppointment({{ $appointment->id }})" class="btn action-btn action-btn-confirm" title="Confirm appointment">
                                    <i class="fas fa-check"></i>Confirm
                                </button>
                            @endif

                            @if($appointment->status == 'confirmed')
                                @php
                                    $canJoinVideo = $appointment->appointment_type === 'video_call' && ($appointment->getEndTime() ? now()->isBefore($appointment->getEndTime()) : now()->isBefore($appointment->appointment_date->copy()->addHour()));
                                @endphp
                                @if($canJoinVideo)
                                    <a href="{{ route('video.room', $appointment->id) }}" target="_blank" class="btn action-btn action-btn-confirm" title="Join video consultation now">
                                        <i class="fas fa-video"></i>Join Video
                                    </a>
                                @elseif($appointment->appointment_type === 'phone_call')
                                    <button onclick="headerShowPatientPhone({{ $appointment->id }}, this)" class="btn action-btn action-btn-confirm" title="Show patient phone number">
                                        <i class="fas fa-phone"></i>Call Patient
                                    </button>
                                @endif
                                @if(!$appointment->appointment_date || !$appointment->appointment_date->isFuture())
                                    <button onclick="completeAppointment({{ $appointment->id }})" class="btn action-btn action-btn-complete" title="Mark as completed">
                                        <i class="fas fa-check-circle"></i>Complete
                                    </button>
                                    <button onclick="markNoShow({{ $appointment->id }})" class="btn action-btn action-btn-noshow" title="Mark as no show">
                                        <i class="fas fa-user-times"></i>No Show
                                    </button>
                                @elseif(!in_array($appointment->appointment_type, ['video_call','phone_call']))
                                    <span class="btn action-btn action-btn-muted" title="Starts {{ $appointment->appointment_date->format('M j, g:i A') }}">
                                        <i class="fas fa-clock"></i>{{ $appointment->appointment_date->diffForHumans() }}
                                    </span>
                                @endif
                            @endif

                            <button onclick="cancelAppointment({{ $appointment->id }})" class="btn action-btn action-btn-danger" title="Cancel appointment">
                                <i class="fas fa-times"></i>Cancel
                            </button>
                        </div>
                        @elseif($appointment->status == 'completed')
                        <div class="appointment-actions" role="group" aria-label="Appointment actions">
                            <button onclick="toggleDiagnosisForm()" class="btn action-btn" style="background:#f59e0b;color:#fff;border-color:#f59e0b" title="Create diagnosis for this appointment">
                                <i class="fas fa-stethoscope"></i>New Diagnosis
                            </button>
                        </div>
                        @endif
                    @endif
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Main Content -->
            <div class="col-lg-12">
                <!-- Information Cards Grid - Premium -->
                <div class="info-cards-grid">
                    <!-- Appointment Overview Card -->
                    <div class="info-card-premium">
                        <div class="card-inner">
                            <div class="card-top">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="card-icon-box icon-red">
                                        <i class="fas fa-calendar-check"></i>
                                    </div>
                                    <div>
                                        <h5 class="card-title">Appointment Overview</h5>
                                        <div class="card-subtitle">
                                            <i class="fas fa-clock me-1"></i>{{ $appointment->appointment_date->format('l, F j, Y • g:i A') }}
                                        </div>
                                    </div>
                                </div>
                                <div class="card-badge-duration">
                                    <strong>{{ $appointment->appointment_duration ?? 30 }}</strong>
                                    <span>minutes</span>
                                </div>
                            </div>

                            <div class="info-rows">
                                <div class="info-row">
                                    <div class="info-row-icon"><i class="fas fa-layer-group"></i></div>
                                    <div class="flex-grow-1">
                                        <span class="info-row-label">Appointment Type</span>
                                        <span class="info-row-value"><span class="badge bg-light text-muted border px-3 py-1 rounded-pill fw-semibold" style="font-size:0.72rem;">{{ ucfirst(str_replace('_', ' ', $appointment->appointment_type)) }}</span></span>
                                    </div>
                                </div>
                                <div class="info-row">
                                    <div class="info-row-icon"><i class="fas fa-hashtag"></i></div>
                                    <div class="flex-grow-1">
                                        <span class="info-row-label">Ref</span>
                                        <span class="info-row-value" style="font-family: ui-monospace, SFMono-Regular, monospace; font-size:0.78rem; color:#475569;">{{ $appointment->appointment_number ?? '#'.$appointment->id }}</span>
                                    </div>
                                    <span class="badge bg-light text-dark border small">{{ ucfirst($appointment->status) }}</span>
                                </div>
                                <div class="info-row">
                                    <div class="info-row-icon"><i class="fas fa-calendar-day"></i></div>
                                    <div class="flex-grow-1">
                                        <span class="info-row-label">Scheduled Date</span>
                                        <span class="info-row-value">{{ $appointment->appointment_date->format('M j, Y') }}</span>
                                    </div>
                                    <small class="text-muted fw-medium">{{ $appointment->appointment_date->format('g:i A') }}</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Patient Information Card -->
                    <div class="info-card-premium card-patient">
                        <div class="card-inner">
                            <div class="card-top">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="card-icon-box icon-blue">
                                        <i class="fas fa-user-injured"></i>
                                    </div>
                                    <div>
                                        <h5 class="card-title">Patient Information</h5>
                                        <div class="card-subtitle">Contact & personal details</div>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    @if($appointment->patient_id)
                                    <a href="{{ route('doctor.patients.show', $appointment->patient_id) }}" class="btn btn-sm" style="background:#fff;border:1px solid #e2e8f0;color:#475569;border-radius:8px;padding:0.35rem 0.6rem;font-size:0.76rem;font-weight:600" title="View full patient profile, history & diagnoses"><i class="fas fa-external-link-alt me-1"></i>View Patient</a>
                                    @endif
                                    <div class="card-badge-duration">
                                        <strong><i class="fas fa-user"></i></strong>
                                        <span>Patient</span>
                                    </div>
                                </div>
                            </div>

                            <div class="info-rows">
                                <div class="info-row">
                                    <div class="info-row-icon"><i class="fas fa-user"></i></div>
                                    <div class="flex-grow-1">
                                        <span class="info-row-label">Full Name</span>
                                        <span class="info-row-value">{{ e($appointment->patient_name) }}</span>
                                    </div>
                                </div>
                                <div class="info-row">
                                    <div class="info-row-icon"><i class="fas fa-envelope"></i></div>
                                    <div class="flex-grow-1">
                                        <span class="info-row-label">Email Address</span>
                                        <span class="info-row-value"><a href="mailto:{{ e($appointment->patient_email) }}">{{ e($appointment->patient_email) }}</a></span>
                                    </div>
                                    <a href="mailto:{{ e($appointment->patient_email) }}" class="btn btn-sm btn-light border rounded-circle p-0 d-flex align-items-center justify-content-center" style="width:32px;height:32px;"><i class="fas fa-paper-plane small text-muted"></i></a>
                                </div>
                                @if($appointment->patient_phone)
                                <div class="info-row">
                                    <div class="info-row-icon"><i class="fas fa-phone"></i></div>
                                    <div class="flex-grow-1">
                                        <span class="info-row-label">Phone Number</span>
                                        <span class="info-row-value"><a href="tel:{{ e($appointment->patient_phone) }}">{{ e($appointment->patient_phone) }}</a></span>
                                    </div>
                                    <a href="tel:{{ e($appointment->patient_phone) }}" class="btn btn-sm btn-light border rounded-circle p-0 d-flex align-items-center justify-content-center" style="width:32px;height:32px;"><i class="fas fa-phone small text-muted"></i></a>
                                </div>
                                @else
                                <div class="info-row" style="opacity:0.65;">
                                    <div class="info-row-icon"><i class="fas fa-phone-slash"></i></div>
                                    <div class="flex-grow-1">
                                        <span class="info-row-label">Phone Number</span>
                                        <span class="info-row-value text-muted fst-italic">Not provided</span>
                                    </div>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Next Steps Section for Completed Appointments - Premium -->
                @if($appointment->status == 'completed')
                <div class="table-card mb-4">
                    <div class="section-head-modern">
                        <div class="head-left">
                            <div class="head-icon" style="background:#f8fafc; color:#475569; border:1px solid #e2e8f0;">
                                <i class="fas fa-check-double"></i>
                            </div>
                            <div>
                                <h4>Appointment Completed Successfully</h4>
                                <p>Choose your next clinical action • All tools are ready</p>
                            </div>
                        </div>
                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-3 py-2 rounded-pill fw-semibold d-none d-md-inline-flex align-items-center gap-2">
                            <span class="bg-success rounded-circle d-inline-block" style="width:8px;height:8px;"></span> Completed {{ $appointment->appointment_number ?? '#'.$appointment->id }}
                        </span>
                    </div>

                    <div class="next-steps-grid">
                        <button onclick="toggleAIMedicalCopilotForm()" class="action-tile tone-purple">
                            <div class="tile-icon"><i class="fas fa-brain"></i></div>
                            <span class="tile-title">AI Copilot</span>
                            <span class="tile-desc">Clinical decision support</span>
                        </button>
                        <button onclick="viewPatientAIAnalyses({{ $appointment->patient_id }})" class="action-tile tone-info">
                            <div class="tile-icon"><i class="fas fa-history"></i></div>
                            <span class="tile-title">View AI History</span>
                            <span class="tile-desc">Patient's saved analyses</span>
                        </button>
                        <a href="#ai-analytics" class="action-tile tone-primary">
                            <div class="tile-icon"><i class="fas fa-chart-line"></i></div>
                            <span class="tile-title">AI Analytics</span>
                            <span class="tile-desc">Risk predictions & insights</span>
                        </a>
                        <button onclick="toggleDiagnosisForm()" class="action-tile tone-warning">
                            <div class="tile-icon"><i class="fas fa-stethoscope"></i></div>
                            <span class="tile-title">Diagnosis</span>
                            <span class="tile-desc">Create medical diagnosis</span>
                        </button>
                        <a href="#prescriptions" class="action-tile tone-success">
                            <div class="tile-icon"><i class="fas fa-prescription-bottle"></i></div>
                            <span class="tile-title">Prescriptions</span>
                            <span class="tile-desc">Manage medications</span>
                        </a>
                        <a href="{{ route('doctor.follow-ups.create', $appointment) }}" class="action-tile tone-info">
                            <div class="tile-icon"><i class="fas fa-calendar-plus"></i></div>
                            <span class="tile-title">Follow-ups</span>
                            <span class="tile-desc">Schedule next appointment</span>
                        </a>
                    </div>
                </div>
                @endif


                <!-- AI Predictive Analytics Section - Compact Premium -->
                <div id="ai-analytics" class="table-card mb-4">
                    <div class="section-head-modern">
                        <div class="head-left">
                            <div class="head-icon" style="background: linear-gradient(135deg, #ede9ff 0%, #ddd6fe 100%); color: #7c3aed; border: 1px solid #ddd6fe;">
                                <i class="fas fa-brain"></i>
                            </div>
                            <div>
                                <h5 style="margin:0; font-weight:800; color:#1e293b; font-size:0.92rem; letter-spacing:-0.01em;">AI Predictive Analytics</h5>
                                <p style="margin:2px 0 0; font-size:0.74rem; color:#94a3b8; font-weight:500;">Machine Learning Risk Assessment</p>
                            </div>
                        </div>
                        @if($appointment->status == 'completed')
                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2 py-1 rounded-pill fw-semibold" style="font-size:0.70rem;">
                            <i class="fas fa-check-circle me-1"></i>Complete
                        </span>
                        @endif
                    </div>

                    @php
                        $riskScore = $appointment->patient?->patientRiskScores?->where('appointment_id', $appointment->id)?->first();
                    @endphp
                    @if($riskScore)
                        <div class="ai-risk-grid">
                            <div class="ai-risk-card risk-warning">
                                <div class="risk-icon-box"><i class="fas fa-user-times"></i></div>
                                <span class="risk-label">No-Show Risk</span>
                                <div class="risk-value">{{ number_format($riskScore->no_show_risk * 100, 1) }}<small>%</small></div>
                                <p class="risk-desc">Probability of missing appointment</p>
                                <div class="risk-bar"><div class="risk-bar-fill" style="width: {{ min(100, $riskScore->no_show_risk * 100) }}%"></div></div>
                            </div>
                            <div class="ai-risk-card risk-danger">
                                <div class="risk-icon-box"><i class="fas fa-hospital"></i></div>
                                <span class="risk-label">Hospitalization Risk</span>
                                <div class="risk-value">{{ number_format($riskScore->hospitalization_risk * 100, 1) }}<small>%</small></div>
                                <p class="risk-desc">Probability of hospitalization</p>
                                <div class="risk-bar"><div class="risk-bar-fill" style="width: {{ min(100, $riskScore->hospitalization_risk * 100) }}%"></div></div>
                            </div>
                        </div>

                        <!-- Risk Level Summary - Compact + Production Provenance -->
                        @php $maxRisk = max($riskScore->no_show_risk, $riskScore->hospitalization_risk); @endphp
                        <div class="ai-summary-row">
                            <div>
                                @if($maxRisk < 0.3)
                                    <span class="summary-badge" style="background:#ecfdf5; color:#059669; border:1px solid #a7f3d0;">
                                        <i class="fas fa-shield-alt"></i> Low Risk Patient
                                    </span>
                                    <span class="summary-hint">Strong compliance patterns detected</span>
                                @elseif($maxRisk < 0.7)
                                    <span class="summary-badge" style="background:#fffbeb; color:#b45309; border:1px solid #fde68a;">
                                        <i class="fas fa-exclamation-triangle"></i> Medium Risk Patient
                                    </span>
                                    <span class="summary-hint">Consider follow-up reminders</span>
                                @else
                                    <span class="summary-badge" style="background:#fef2f2; color:#dc2626; border:1px solid #fecaca;">
                                        <i class="fas fa-exclamation-circle"></i> High Risk Patient
                                    </span>
                                    <span class="summary-hint">Immediate attention recommended</span>
                                @endif
                                @if(isset($riskScore->prediction_method))
                                <span class="d-block mt-1" style="font-size:0.68rem; color:#94a3b8; font-weight:500;">
                                    <i class="fas fa-{{ $riskScore->prediction_method === 'ml' ? 'brain' : 'calculator' }} me-1"></i>
                                    via {{ $riskScore->prediction_method === 'ml' ? 'ML model' : 'rule-based' }}
                                    @if($riskScore->confidence) • {{ (int)($riskScore->confidence*100) }}% confidence @endif
                                    @if($riskScore->model_version) • v{{ $riskScore->model_version }} @endif
                                </span>
                                @endif
                            </div>
                            <button type="button" class="btn btn-outline-primary btn-sm" style="font-size:0.75rem; padding:0.3rem 0.65rem; border-radius:8px;" data-bs-toggle="modal" data-bs-target="#mlExplanationModal">
                                <i class="fas fa-info-circle me-1"></i>How calculated?
                            </button>
                        </div>
                    @else
                        <div class="ai-empty-state">
                            <div class="empty-icon-box"><i class="fas fa-brain"></i></div>
                            <div style="font-size:0.88rem; font-weight:700; color:#475569; margin-bottom:0.15rem;">AI Analysis in Progress</div>
                            <div style="font-size:0.74rem; color:#94a3b8; margin-bottom:0.75rem;">Risk predictions are being calculated...</div>
                            <div class="spinner-border spinner-border-sm text-primary" role="status" style="width:1.2rem; height:1.2rem; border-width:0.18em;">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Clinical Context - enriched, replaces isolated Reason card -->
                <div class="table-card mb-4">
                    <div class="section-head-modern" style="margin-bottom:1.1rem;">
                        <div class="head-left">
                            <div class="head-icon" style="background:#f8fafc; color:#475569; border:1px solid #e2e8f0;">
                                <i class="fas fa-clipboard-list"></i>
                            </div>
                            <div>
                                <h5>Clinical Context</h5>
                                <p>Chief complaint • symptoms • notes</p>
                            </div>
                        </div>
                        <span class="badge bg-light text-muted border fw-medium d-none d-sm-inline" style="font-size:0.72rem;">{{ ucfirst(str_replace('_',' ', $appointment->appointment_type ?? '')) }} · {{ $appointment->appointment_duration ?? $appointment->duration ?? 30 }} min</span>
                    </div>
                    <div class="reason-content-modern">
                        <i class="fas fa-quote-right quote-icon"></i>
                        <p><strong class="text-dark">Reason:</strong> {{ e($appointment->reason) ?: '—' }}</p>
                    </div>
                    @if($appointment->symptoms)
                    <div class="reason-content-modern mt-3" style="background:#f8fafc; border-color:#e2e8f0;">
                        <i class="fas fa-exclamation-triangle quote-icon" style="color:#475569;"></i>
                        <p><strong class="text-dark">Symptoms:</strong> {{ e($appointment->symptoms) }}</p>
                    </div>
                    @endif
                    @if($appointment->patient_notes)
                    <div class="reason-content-modern mt-3" style="background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%); border-color:#e2e8f0;">
                        <i class="fas fa-sticky-note quote-icon" style="color:#94a3b8;"></i>
                        <p><strong class="text-dark">Patient Notes:</strong> {{ e($appointment->patient_notes) }}</p>
                    </div>
                    @endif
                    @if(!$appointment->symptoms && !$appointment->patient_notes)
                    <p class="small text-muted mt-2 mb-0"><i class="fas fa-info-circle me-1"></i>No additional symptoms or notes provided for this visit.</p>
                    @endif
                </div>

                <!-- Prescriptions Section — Premium Professional -->
                @if(auth()->check() && auth()->user()->isDoctor())
                <div id="prescriptions" class="table-card">
                    <div class="section-head-modern">
                        <div class="head-left">
                            <div class="head-icon" style="background:#f8fafc; color:#475569; border:1px solid #e2e8f0;">
                                <i class="fas fa-prescription-bottle"></i>
                            </div>
                            <div>
                                <h4 style="margin:0; font-weight:800; color:#1e293b; font-size:1rem; letter-spacing:-0.01em;">Prescriptions</h4>
                                <p style="margin:2px 0 0; font-size:0.78rem; color:#64748b; font-weight:500;">@if($appointment->status == 'completed') Manage patient medications and treatments @else Prescription management @endif</p>
                            </div>
                        </div>
                        @if($appointment->status == 'completed')
                        <span class="badge bg-light text-muted border px-3 py-2 rounded-pill fw-semibold" style="font-size:0.72rem;">
                            <i class="fas fa-plus-circle me-1"></i>Ready to Prescribe
                        </span>
                        @else
                        <span class="badge bg-light text-muted border fw-medium" style="font-size:0.72rem;">{{ ucfirst($appointment->status) }}</span>
                        @endif
                    </div>

                    @if($appointment->prescriptions && $appointment->prescriptions->count() > 0)
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="fw-bold mb-0" style="font-size:0.84rem; color:#1e293b;"><i class="fas fa-list me-2 text-muted"></i>Existing Prescriptions</h6>
                            <span class="badge bg-light text-muted border" style="font-size:0.70rem;">{{ $appointment->prescriptions->count() }} active</span>
                        </div>
                        @foreach($appointment->prescriptions as $prescription)
                            <div class="prescription-card-premium mb-3" data-prescription-id="{{ $prescription->id }}" style="background:#ffffff; border:1px solid #eef2f7; border-left:3px solid #e2e8f0; border-radius:12px; padding:1rem 1.1rem; transition: box-shadow 0.2s;">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="d-flex align-items-center justify-content-center rounded-3" style="width:36px; height:36px; background:#f8fafc; color:#475569; border:1px solid #e2e8f0; font-size:0.9rem;">
                                            <i class="fas fa-pills"></i>
                                        </div>
                                        <div>
                                            <h6 class="mb-0 fw-bold" style="font-size:0.92rem; color:#1e293b;">{{ $prescription->medication_name }}</h6>
                                            <small class="text-muted" style="font-size:0.72rem;"><i class="far fa-clock me-1"></i>{{ $prescription->created_at->format('M j, Y') }} · {{ ucfirst($prescription->form ?? 'N/A') }}</small>
                                        </div>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <a href="{{ route('prescriptions.show', $prescription->id) }}?pdf=1" class="btn btn-sm fw-semibold" style="background:#eff6ff; color:#2563eb; border:1px solid #dbeafe; border-radius:8px; font-size:0.75rem;">
                                            <i class="fas fa-download me-1"></i>PDF
                                        </a>
                                        <button type="button" class="btn btn-sm fw-semibold" style="background:#fef2f2; color:#dc2626; border:1px solid #fecaca; border-radius:8px; font-size:0.75rem;" onclick="deletePrescription({{ $prescription->id }}, '{{ addslashes($prescription->medication_name) }}')">
                                            <i class="fas fa-trash me-1"></i>Delete
                                        </button>
                                    </div>
                                </div>
                                <div class="row g-2" style="font-size:0.78rem;">
                                    <div class="col-6 col-md-3">
                                        <span class="text-muted small fw-semibold" style="font-size:0.68rem; letter-spacing:0.04em; text-transform:uppercase;">Dosage</span><br>
                                        <span class="fw-semibold" style="color:#1e293b;">{{ $prescription->dosage }}</span>
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <span class="text-muted small fw-semibold" style="font-size:0.68rem; letter-spacing:0.04em; text-transform:uppercase;">Frequency</span><br>
                                        <span class="fw-semibold" style="color:#1e293b;">{{ $prescription->frequency }}</span>
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <span class="text-muted small fw-semibold" style="font-size:0.68rem; letter-spacing:0.04em; text-transform:uppercase;">Duration</span><br>
                                        <span class="fw-semibold" style="color:#1e293b;">{{ $prescription->duration }}</span>
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <span class="text-muted small fw-semibold" style="font-size:0.68rem; letter-spacing:0.04em; text-transform:uppercase;">Quantity</span><br>
                                        <span class="fw-semibold" style="color:#1e293b;">{{ $prescription->quantity ?? '—' }}</span>
                                    </div>
                                </div>
                                @if($prescription->instructions)
                                    <div class="mt-2 pt-2" style="border-top:1px solid #f1f5f9; font-size:0.78rem; color:#475569;">
                                        <i class="fas fa-sticky-note me-1 text-muted"></i><strong>Instructions:</strong> {{ $prescription->instructions }}
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    @else
                        <div class="text-center py-4" style="background:#f8fafc; border:1px dashed #e2e8f0; border-radius:12px;">
                            <div class="d-inline-flex align-items-center justify-content-center rounded-3 mb-3" style="width:48px; height:48px; background:#fff; border:1px solid #eef2f7; color:#94a3b8;">
                                <i class="fas fa-prescription-bottle-alt" style="font-size:1.3rem;"></i>
                            </div>
                            <p class="fw-semibold mb-1" style="font-size:0.88rem; color:#475569;">No prescriptions yet</p>
                            <p class="small text-muted mb-0" style="font-size:0.78rem;">Prescriptions you create will appear here with PDF export.</p>
                        </div>
                    @endif

                    <!-- Add New Prescription — Compact Premium -->
                    <div class="mt-4 pt-4" style="border-top:1px solid #f1f5f9;">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0 fw-bold" style="font-size:0.92rem; color:#1e293b;"><i class="fas fa-plus-circle me-2" style="color:#475569;"></i>Add New Prescription</h5>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-sm" style="background:#fff; border:1px solid #e2e8f0; color:#64748b; border-radius:8px; font-size:0.72rem;" data-bs-toggle="modal" data-bs-target="#prescriptionHelpModal">
                                    <i class="fas fa-question-circle me-1"></i>Help
                                </button>
                            </div>
                        </div>

                        <form id="prescriptionForm" method="POST" action="{{ route('doctor.prescriptions.store', $appointment->id) }}">
                            @csrf

                            <!-- 1. Medication — premium compact -->
                            <div class="form-section">
                                <div class="form-section-header">
                                    <h6 class="form-section-title">
                                        <i class="fas fa-pills me-2" style="color:#475569;"></i>Medication
                                    </h6>
                                    <span class="badge bg-light text-muted border fw-semibold" style="font-size:0.68rem; letter-spacing:0.04em;">REQUIRED</span>
                                </div>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="medication_name" class="form-label fw-semibold" style="font-size:0.82rem;">Medication Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="medication_name" name="medication_name" placeholder="e.g., Amoxicillin" required style="border-radius:10px; border:1px solid #e2e8f0; font-size:0.88rem;">
                                    </div>
                                    <div class="col-md-3">
                                        <label for="dosage" class="form-label fw-semibold" style="font-size:0.82rem;">Dosage <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="dosage" name="dosage" placeholder="500mg" required style="border-radius:10px; border:1px solid #e2e8f0; font-size:0.88rem;">
                                    </div>
                                    <div class="col-md-3">
                                        <label for="quantity" class="form-label fw-semibold" style="font-size:0.82rem;">Quantity <span class="text-danger">*</span></label>
                                        <input type="number" class="form-control" id="quantity" name="quantity" placeholder="30" min="1" required style="border-radius:10px; border:1px solid #e2e8f0; font-size:0.88rem;">
                                    </div>
                                    <div class="col-md-4">
                                        <label for="form" class="form-label fw-semibold" style="font-size:0.82rem;">Form <span class="text-danger">*</span></label>
                                        <select class="form-select" id="form" name="form" required style="border-radius:10px; border:1px solid #e2e8f0; font-size:0.88rem;">
                                            <option value="">Select</option>
                                            <option value="tablet">Tablet</option>
                                            <option value="capsule">Capsule</option>
                                            <option value="liquid">Liquid/Syrup</option>
                                            <option value="injection">Injection</option>
                                            <option value="cream">Cream/Ointment</option>
                                            <option value="inhaler">Inhaler</option>
                                            <option value="patch">Patch</option>
                                            <option value="other">Other</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="route" class="form-label fw-semibold" style="font-size:0.82rem;">Route <span class="text-danger">*</span></label>
                                        <select class="form-select" id="route" name="route" required style="border-radius:10px; border:1px solid #e2e8f0; font-size:0.88rem;">
                                            <option value="">Select</option>
                                            <option value="oral">Oral</option>
                                            <option value="topical">Topical</option>
                                            <option value="intravenous">Intravenous</option>
                                            <option value="intramuscular">Intramuscular</option>
                                            <option value="subcutaneous">Subcutaneous</option>
                                            <option value="inhalation">Inhalation</option>
                                            <option value="rectal">Rectal</option>
                                            <option value="other">Other</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="indication" class="form-label fw-semibold" style="font-size:0.82rem;">Indication</label>
                                        <input type="text" class="form-control" id="indication" name="indication" placeholder="Hypertension" style="border-radius:10px; border:1px solid #e2e8f0; font-size:0.88rem;">
                                    </div>
                                </div>
                            </div>

                            <!-- 2. Schedule — premium compact -->
                            <div class="form-section">
                                <div class="form-section-header">
                                    <h6 class="form-section-title">
                                        <i class="fas fa-clock me-2" style="color:#475569;"></i>Schedule & Supply
                                    </h6>
                                    <span class="badge bg-light text-muted border fw-semibold" style="font-size:0.68rem; letter-spacing:0.04em;">REQUIRED</span>
                                </div>
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label for="frequency" class="form-label fw-semibold" style="font-size:0.82rem;">Frequency <span class="text-danger">*</span></label>
                                        <select class="form-select" id="frequency" name="frequency" required style="border-radius:10px; border:1px solid #e2e8f0; font-size:0.88rem;">
                                            <option value="">Select</option>
                                            <option value="once daily">Once daily</option>
                                            <option value="twice daily">Twice daily</option>
                                            <option value="three times daily">Three times daily</option>
                                            <option value="four times daily">Four times daily</option>
                                            <option value="every 6 hours">Every 6 hours</option>
                                            <option value="every 8 hours">Every 8 hours</option>
                                            <option value="every 12 hours">Every 12 hours</option>
                                            <option value="as needed">As needed (PRN)</option>
                                            <option value="other">Other</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="duration" class="form-label fw-semibold" style="font-size:0.82rem;">Duration <span class="text-danger">*</span></label>
                                        <select class="form-select" id="duration" name="duration" required style="border-radius:10px; border:1px solid #e2e8f0; font-size:0.88rem;">
                                            <option value="">Select</option>
                                            <option value="3 days">3 days</option>
                                            <option value="7 days">7 days</option>
                                            <option value="10 days">10 days</option>
                                            <option value="14 days">14 days</option>
                                            <option value="1 month">1 month</option>
                                            <option value="2 months">2 months</option>
                                            <option value="3 months">3 months</option>
                                            <option value="6 months">6 months</option>
                                            <option value="other">Other</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label for="refills" class="form-label fw-semibold" style="font-size:0.82rem;">Refills</label>
                                        <input type="number" class="form-control" id="refills" name="refills" placeholder="0" min="0" value="0" style="border-radius:10px; border:1px solid #e2e8f0; font-size:0.88rem;">
                                    </div>
                                    <div class="col-md-2">
                                        <label for="start_date" class="form-label fw-semibold" style="font-size:0.82rem;">Start Date</label>
                                        <input type="date" class="form-control" id="start_date" name="start_date" style="border-radius:10px; border:1px solid #e2e8f0; font-size:0.88rem;">
                                    </div>
                                </div>
                            </div>

                            <!-- 3. AI — collapsible premium (kept, but compact) -->
                            @if(config('ai.prescription_suggestions.enabled', true))
                                <div class="form-section" style="background: linear-gradient(135deg, #f5f3ff 0%, #ede9fe 100%); border-color:#ddd6fe;">
                                    <div class="form-section-header" style="border-bottom-color:#ddd6fe;">
                                        <h6 class="form-section-title" style="color:#6d28d9;">
                                            <i class="fas fa-brain me-2"></i>AI Clinical Support
                                        </h6>
                                        <span class="badge bg-white text-dark border fw-semibold" style="font-size:0.68rem;">Optional</span>
                                    </div>
                                    @if($appointment->status == 'completed')
                                    <small class="text-muted d-block mb-2" style="font-size:0.76rem;">
                                        <i class="fas fa-lightbulb text-warning me-1"></i>AI can suggest medications based on appointment data
                                    </small>
                                    @endif
                                    @include('ai.prescription_suggestion')
                                </div>
                            @endif

                            <!-- 4. Directions — premium compact -->
                            <div class="form-section">
                                <div class="form-section-header">
                                    <h6 class="form-section-title">
                                        <i class="fas fa-sticky-note me-2" style="color:#475569;"></i>Directions & Notes
                                    </h6>
                                    <span class="badge bg-light text-muted border fw-semibold" style="font-size:0.68rem;">Optional</span>
                                </div>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="instructions" class="form-label fw-semibold" style="font-size:0.82rem;">Patient Instructions</label>
                                        <textarea class="form-control" id="instructions" name="instructions" rows="2" placeholder="Take with food, avoid alcohol..." style="border-radius:10px; border:1px solid #e2e8f0; font-size:0.88rem;"></textarea>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="notes" class="form-label fw-semibold" style="font-size:0.82rem;">Clinical Notes</label>
                                        <textarea class="form-control" id="notes" name="notes" rows="2" placeholder="Monitoring, warnings..." style="border-radius:10px; border:1px solid #e2e8f0; font-size:0.88rem;"></textarea>
                                    </div>
                                    <div class="col-12">
                                        <div class="d-flex align-items-center gap-2 p-2" style="background:#f8fafc; border:1px solid #eef2f7; border-radius:10px;">
                                            <input class="form-check-input m-0" type="checkbox" id="generic_allowed" name="generic_allowed" value="1" checked style="width:1.1rem; height:1.1rem;">
                                            <label class="form-check-label fw-semibold mb-0" for="generic_allowed" style="font-size:0.84rem; color:#334155;">Allow generic substitution</label>
                                            <span class="ms-auto small text-muted" style="font-size:0.72rem;">Pharmacist may substitute equivalent</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Actions — premium -->
                            <div class="d-flex flex-wrap gap-3 justify-content-between align-items-center mt-3 pt-3" style="border-top:1px solid #f1f5f9;">
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn fw-semibold" style="background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%); color:#fff; border:none; border-radius:10px; padding:0.65rem 1.4rem; font-size:0.88rem; box-shadow: 0 4px 14px rgba(37,99,235,0.25);">
                                        <i class="fas fa-save me-2"></i>Save Prescription
                                    </button>
                                    <button type="button" class="btn fw-semibold" style="background:#fff; color:#475569; border:1px solid #e2e8f0; border-radius:10px; padding:0.65rem 1.1rem; font-size:0.88rem;" onclick="resetPrescriptionForm()">
                                        <i class="fas fa-undo me-2"></i>Reset Form
                                    </button>
                                </div>
                                <div class="small d-flex align-items-center gap-2" style="color:#64748b; font-size:0.76rem;">
                                    <i class="fas fa-shield-alt text-success"></i> Clinical review required before dispensing
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                @endif

                <!-- Diagnosis Section — Premium -->
                @if(auth()->check() && auth()->user()->isDoctor())
                <div id="diagnosis-section" class="table-card" style="@if(isset($errors) && ($errors->has('diagnosis_text') || $errors->has('voice_files') || $errors->any())) display: block; @else display: none; @endif border-top: 1px solid #eef2f7;">
                    <div class="section-head-modern">
                        <div class="head-left">
                            <div class="head-icon" style="background:#f8fafc; color:#475569; border:1px solid #e2e8f0;">
                                <i class="fas fa-stethoscope"></i>
                            </div>
                            <div>
                                <h4 style="margin:0; font-weight:800; color:#1e293b; font-size:1rem; letter-spacing:-0.01em;">Create Diagnosis</h4>
                                <p style="margin:2px 0 0; font-size:0.78rem; color:#64748b; font-weight:500;">Document medical findings and diagnosis for this appointment</p>
                            </div>
                        </div>
                        <button type="button" class="btn btn-sm" style="background:#fff; border:1px solid #e2e8f0; color:#64748b; border-radius:8px; font-size:0.75rem;" onclick="toggleDiagnosisForm()">
                            <i class="fas fa-times me-1"></i>Close
                        </button>
                    </div>

                    @if (isset($errors) && $errors->any())
                        <div class="ml-warning" style="background:#fef2f2; border-color:#fecaca; margin-bottom:1rem;">
                            <i class="fas fa-exclamation-triangle" style="color:#dc2626; margin-top:2px;"></i>
                            <div>
                                <strong>Please correct:</strong>
                                <ul class="mb-0 mt-1" style="font-size:0.78rem;">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    @endif

                    <div class="ml-note" style="background:#f8fafc; border-color:#e2e8f0; margin-bottom:1rem;">
                        <i class="fas fa-user-injured" style="color:#2563eb; margin-top:2px;"></i>
                        <div style="font-size:0.84rem;"><strong style="color:#1e293b;">{{ e($appointment->patient_name) }}</strong> <span style="color:#64748b;">· {{ e(Str::limit($appointment->reason, 80)) }}</span>
                        @if($appointment->doctor_notes) <br><small style="color:#64748b; font-size:0.74rem;"><strong>Doctor Notes:</strong> {{ Str::limit($appointment->doctor_notes, 100) }}</small> @endif
                        </div>
                    </div>

                    <form id="diagnosisForm" method="POST" action="{{ route('doctor.appointments.create-diagnosis', $appointment) }}" enctype="multipart/form-data">
                        @csrf

                        <div class="form-section">
                            <div class="form-section-header">
                                <h6 class="form-section-title" style="font-size:0.86rem;"><i class="fas fa-stethoscope me-2" style="color:#475569;"></i>Diagnosis Details</h6>
                                <span class="badge bg-light text-muted border fw-semibold" style="font-size:0.68rem;">Required</span>
                            </div>
                            <label for="diagnosis_text" class="form-label fw-semibold" style="font-size:0.82rem;">Diagnosis Text <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="diagnosis_text" name="diagnosis_text" rows="5" placeholder="Enter medical diagnosis, clinical findings, and treatment plan..." required style="border-radius:10px; border:1px solid #e2e8f0; font-size:0.88rem;"></textarea>
                            <small class="form-text" style="font-size:0.72rem; color:#64748b;">Include symptoms assessment, clinical findings, diagnosis, and recommendations.</small>
                        </div>

                        <div class="form-section">
                            <div class="form-section-header">
                                <h6 class="form-section-title" style="font-size:0.86rem;"><i class="fas fa-microphone me-2" style="color:#475569;"></i>Voice Recording</h6>
                                <span class="badge bg-light text-muted border fw-semibold" style="font-size:0.68rem;">Optional</span>
                            </div>
                            <div class="p-3" style="background:#f8fafc; border:1px dashed #cbd5e1; border-radius:10px;">
                                <div class="d-flex flex-wrap gap-2 align-items-center">
                                    <button type="button" id="startRecording" class="btn btn-sm fw-semibold" style="background:#fff; border:1px solid #dbeafe; color:#2563eb; border-radius:8px;"><i class="fas fa-microphone me-1"></i>Start Recording</button>
                                    <button type="button" id="stopRecording" class="btn btn-sm fw-semibold" style="background:#fef2f2; border:1px solid #fecaca; color:#dc2626; border-radius:8px; display:none;"><i class="fas fa-stop me-1"></i>Stop</button>
                                    <button type="button" id="playRecording" class="btn btn-sm fw-semibold" style="background:#ecfdf5; border:1px solid #a7f3d0; color:#059669; border-radius:8px; display:none;"><i class="fas fa-play me-1"></i>Play Back</button>
                                    <span id="recordingStatus" class="small text-muted"></span>
                                </div>
                                <audio id="audioPlayback" controls style="display:none; max-width:100%; margin-top:0.7rem; border-radius:8px;"></audio>
                                <input type="file" id="voice_files" name="voice_files[]" multiple accept="audio/*" style="display:none;">
                                <small class="d-block mt-2" style="font-size:0.72rem; color:#64748b;">Or <a href="#" onclick="document.getElementById('voice_files').click(); return false;" style="color:#2563eb; font-weight:600;">upload audio files</a> directly.</small>
                            </div>
                        </div>

                        <div class="form-section">
                            <div class="form-section-header">
                                <h6 class="form-section-title" style="font-size:0.86rem;"><i class="fas fa-heartbeat me-2 text-danger"></i>Vitals</h6>
                                <span class="badge bg-light text-muted border fw-semibold" style="font-size:0.68rem;">Optional</span>
                            </div>
                            <div class="row g-3">
                                <div class="col-6 col-md-3">
                                    <label for="patient_data_height" class="form-label fw-semibold" style="font-size:0.82rem;">Height (cm)</label>
                                    <input type="number" class="form-control" id="patient_data_height" name="patient_data[height]" placeholder="170" style="border-radius:10px; border:1px solid #e2e8f0; font-size:0.88rem;">
                                </div>
                                <div class="col-6 col-md-3">
                                    <label for="patient_data_weight" class="form-label fw-semibold" style="font-size:0.82rem;">Weight (kg)</label>
                                    <input type="number" step="0.1" class="form-control" id="patient_data_weight" name="patient_data[weight]" placeholder="70.5" style="border-radius:10px; border:1px solid #e2e8f0; font-size:0.88rem;">
                                </div>
                                <div class="col-6 col-md-3">
                                    <label for="patient_data_blood_pressure" class="form-label fw-semibold" style="font-size:0.82rem;">Blood Pressure</label>
                                    <input type="text" class="form-control" id="patient_data_blood_pressure" name="patient_data[blood_pressure]" placeholder="120/80" style="border-radius:10px; border:1px solid #e2e8f0; font-size:0.88rem;">
                                </div>
                                <div class="col-6 col-md-3">
                                    <label for="patient_data_temperature" class="form-label fw-semibold" style="font-size:0.82rem;">Temperature (°C)</label>
                                    <input type="number" step="0.1" class="form-control" id="patient_data_temperature" name="patient_data[temperature]" placeholder="36.6" style="border-radius:10px; border:1px solid #e2e8f0; font-size:0.88rem;">
                                </div>
                            </div>
                        </div>

                        <div class="d-flex flex-wrap gap-3 justify-content-between align-items-center mt-3 pt-3" style="border-top:1px solid #f1f5f9;">
                            <div class="d-flex gap-2">
                                <button type="button" class="btn fw-semibold" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color:#fff; border:none; border-radius:10px; padding:0.6rem 1.3rem; font-size:0.88rem;" onclick="submitDiagnosisForm()">
                                    <i class="fas fa-save me-2"></i>Create Diagnosis
                                </button>
                                <button type="button" class="btn fw-semibold" style="background:#fff; border:1px solid #e2e8f0; color:#64748b; border-radius:10px; padding:0.6rem 1.1rem; font-size:0.88rem;" onclick="toggleDiagnosisForm()">
                                    <i class="fas fa-times me-1"></i>Cancel
                                </button>
                            </div>
                            <small class="d-flex align-items-center gap-1" style="color:#64748b; font-size:0.74rem;"><i class="fas fa-shield-alt text-success"></i> Patient will be notified</small>
                        </div>
                    </form>
                </div>
                @endif

                @php $appointmentDiagnoses = $appointment->diagnoses ?? $appointment->diagnosis ?? collect(); if($appointmentDiagnoses instanceof \Illuminate\Database\Eloquent\Model) $appointmentDiagnoses = collect([$appointmentDiagnoses]); @endphp
                @if($appointmentDiagnoses && count($appointmentDiagnoses) > 0)
                    <div class="mt-4 p-3" style="background:#ffffff;border:1px solid #e2e8f0;border-radius:12px">
                        <h6 style="font-size:0.9rem;font-weight:600;color:#0f172a;margin:0 0 0.75rem"><i class="fas fa-clipboard-check me-2" style="color:#10b981"></i>Existing Diagnoses</h6>
                        @foreach($appointmentDiagnoses as $diag)
                            <a href="{{ route('diagnosis.show', $diag->id) }}" class="d-flex align-items-center justify-content-between p-2 mb-2" style="background:#f8fafc;border:1px solid #f1f5f9;border-radius:8px;text-decoration:none;color:#334155">
                                <span style="font-size:0.84rem"><i class="fas fa-file-medical me-2" style="color:#64748b"></i>Diagnosis #{{ $diag->id }} — {{ \Illuminate\Support\Str::limit($diag->diagnosis_text ?? 'No text', 60) }}</span>
                                <i class="fas fa-chevron-right" style="color:#94a3b8;font-size:0.75rem"></i>
                            </a>
                        @endforeach
                    </div>
                @endif

                <!-- Ambient Listening Session - Transcript & AI Analysis (Professional Collapsible) -->
                @php
                    $ambientDiag = $appointmentDiagnoses->first(function($d){ return !empty($d->voice_transcript); });
                    $ambientTranscript = $ambientDiag->voice_transcript ?? null;
                    $ambientVt = $appointment->patient ? \App\Models\VoiceTranscription::where('patient_id', $appointment->patient->id)
                        ->where(function($q) use ($ambientDiag){ $ambientDiag ? $q->where('diagnosis_id', $ambientDiag->id)->orWhereNotNull('ai_analysis') : $q->whereNotNull('ai_analysis'); })
                        ->latest()->first() : null;
                    if(!$ambientTranscript && $ambientVt) $ambientTranscript = $ambientVt->raw_transcription ?: null;
                    $ambientAnalysis = $ambientVt->ai_analysis ?? null;
                @endphp
                @if($ambientTranscript || $ambientAnalysis)
                <div class="table-card mt-4" id="ambientSessionCard" style="overflow:visible">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div class="section-head-modern mb-0">
                            <div class="head-left">
                                <div class="head-icon" style="background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%); color:#2563eb; border:1px solid #bfdbfe;">
                                    <i class="fas fa-microphone-lines"></i>
                                </div>
                                <div>
                                    <h5 style="margin:0; font-weight:800; color:#1e293b; font-size:1rem; letter-spacing:-0.01em;">Ambient Listening Session</h5>
                                    <p style="margin:2px 0 0; font-size:0.78rem; color:#64748b; font-weight:500;">Doctor-patient conversation • AI analysis</p>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            @if($ambientTranscript)
                                <button type="button" class="btn btn-light border btn-sm" data-bs-toggle="modal" data-bs-target="#ambientConversationModal" style="border-radius:10px;font-weight:700;font-size:0.78rem"><i class="fas fa-comments me-1 text-primary"></i>View Conversation</button>
                            @endif
                            @if($ambientAnalysis)
                                <button type="button" class="btn btn-sm text-white" data-bs-toggle="modal" data-bs-target="#ambientAnalysisModal" style="background:linear-gradient(135deg,#7c3aed 0%,#4c1d95 100%);border:none;border-radius:10px;font-weight:700;font-size:0.78rem"><i class="fas fa-brain me-1"></i>View AI Analysis</button>
                            @endif
                        </div>
                    </div>
                    @if($ambientTranscript || $ambientAnalysis)
                    <div class="d-flex gap-2 mt-3 pt-3" style="border-top:1px solid #f1f5f9">
                        @if($ambientTranscript)<span class="badge bg-light text-muted border" style="border-radius:20px;font-size:0.68rem"><i class="fas fa-file-lines me-1"></i>{{ count(array_filter(preg_split('/\n+/', trim((string)$ambientTranscript)), fn($l)=>trim($l) !== '')) }} turns</span>@endif
                        <span class="badge bg-white border text-muted" style="border-radius:20px;font-size:0.68rem">{{ \Carbon\Carbon::parse($ambientVt->created_at ?? $ambientDiag->created_at ?? now())->format('M d, Y H:i') }}</span>
                    </div>
                    @endif
                </div>
                <!-- Conversation Modal -->
                @if($ambientTranscript)
                <div class="modal fade modal-premium" id="ambientConversationModal" tabindex="-1">
                    <div class="modal-dialog modal-lg modal-dialog-scrollable">
                        <div class="modal-content">
                            <div class="modal-header">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="head-icon" style="background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%); color:#2563eb; border:1px solid #bfdbfe;">
                                        <i class="fas fa-comments"></i>
                                    </div>
                                    <div>
                                        <h5 class="modal-title mb-0" style="font-size:0.95rem; font-weight:800; color:#1e293b; letter-spacing:-0.01em;">Conversation Transcript</h5>
                                        <div style="font-size:0.72rem; color:#94a3b8; font-weight:500;">Diarized Clinician / Patient chat</div>
                                    </div>
                                </div>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <textarea id="ambientConversationRaw" style="display:none">{{ $ambientTranscript }}</textarea>
                                @php $lines = preg_split('/\n+/', trim((string)$ambientTranscript)); @endphp
                                @foreach($lines as $line) @continue(!trim($line))
                                    @php
                                        // Speaker 1 = Clinician/Doctor, Speaker 2 = Patient (matches [Speaker 1]: / [Speaker 2]: diarization)
                                        $isClinician = preg_match('/^\s*\[?(Clinician|Doctor|Speaker 0|Speaker 1|دكتور|الطبيب)\]?\s*[:：]/iu', $line) && !preg_match('/^\s*\[?Speaker 2/i', $line);
                                        $isPatient = preg_match('/^\s*\[?(Patient|Speaker 2|مريض|المريض)\]?\s*[:：]/iu', $line);
                                        $cleanLine = preg_replace('/^\s*\[?(Clinician|Doctor|Patient|Speaker \d)\]?\s*[:：]\s*/iu', '', $line);
                                        $bg = $isClinician ? '#eff6ff' : ($isPatient ? '#ecfdf5' : '#fff');
                                        $border = $isClinician ? '#dbeafe' : ($isPatient ? '#a7f3d0' : '#e2e8f0');
                                        $labelBg = $isClinician ? '#2563eb' : ($isPatient ? '#059669' : '#64748b');
                                        $label = $isClinician ? 'Clinician' : ($isPatient ? 'Patient' : 'Note');
                                    @endphp
                                    <div class="mb-2 p-3" style="background:{{ $bg }};border:1px solid {{ $border }};border-radius:12px">
                                        <span style="background:{{ $labelBg }};color:#fff;border-radius:12px;padding:1px 8px;font-size:0.68rem;font-weight:800">{{ $label }}</span>
                                        <p class="mb-0 mt-1" style="font-size:0.86rem;line-height:1.6;color:#1e293b;word-break:break-word">{{ $cleanLine }}</p>
                                    </div>
                                @endforeach
                            </div>
                            <div class="modal-footer" style="border-top:1px solid #f1f5f9; padding:0.9rem 1.25rem;">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="border-radius:8px;">Close</button>
                                <button type="button" class="btn btn-primary" onclick="const t=document.getElementById('ambientConversationRaw').value; navigator.clipboard.writeText(t).then(()=>{const b=this;const o=b.innerHTML;b.innerHTML='<i class=&quot;fas fa-check me-1&quot;></i>Copied!';setTimeout(()=>b.innerHTML=o,2000)}).catch(()=>alert('Copy failed - select text manually'))" style="border-radius:8px; background:#2563eb; border-color:#2563eb;"><i class="fas fa-copy me-1"></i>Copy</button>
                            </div>
                        </div>
                    </div>
                </div>
                @endif
                <!-- AI Analysis Modal - same as AI Clinical Data Sources -->
                @if($ambientAnalysis)
                <div class="modal fade modal-premium" id="ambientAnalysisModal" tabindex="-1">
                    <div class="modal-dialog modal-lg modal-dialog-scrollable">
                        <div class="modal-content">
                            <div class="modal-header">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="head-icon" style="background: linear-gradient(135deg, #ede9ff 0%, #ddd6fe 100%); color:#7c3aed; border:1px solid #ddd6fe;">
                                        <i class="fas fa-brain"></i>
                                    </div>
                                    <div>
                                        <h5 class="modal-title mb-0" style="font-size:0.95rem; font-weight:800; color:#1e293b; letter-spacing:-0.01em;">AI Clinical Analysis</h5>
                                    </div>
                                </div>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                @php
                                    $fmt = e($ambientAnalysis);
                                    $fmt = str_replace(['&#039;','&quot;','&amp;'], ["'",'"','&'], $fmt);
                                    $fmt = preg_replace('/^🟢 (.+)$/m', '<div class="alert alert-success py-2 px-3 mb-3" style="border-radius:10px;font-size:0.82rem"><i class="fas fa-check-circle me-2"></i>$1</div>', $fmt);
                                    $fmt = preg_replace('/^🔵 (.+)$/m', '<div class="alert alert-info py-2 px-3 mb-3" style="border-radius:10px;font-size:0.82rem;background:#eff6ff;border-color:#dbeafe;color:#1e40af"><i class="fas fa-info-circle me-2"></i>$1</div>', $fmt);
                                    $fmt = preg_replace('/^📋 (.+)$/m', '<h6 style="font-weight:800;color:#1e40af;font-size:0.82rem;margin:1rem 0 0.4rem"><i class="fas fa-clipboard me-2"></i>$1</h6>', $fmt);
                                    $fmt = preg_replace('/^🔍 (.+)$/m', '<h6 style="font-weight:800;color:#0e7490;font-size:0.82rem;margin:1rem 0 0.4rem"><i class="fas fa-search me-2"></i>$1</h6>', $fmt);
                                    $fmt = preg_replace('/^🚨 (.+)$/m', '<h6 style="font-weight:800;color:#dc2626;font-size:0.82rem;margin:1rem 0 0.4rem"><i class="fas fa-exclamation-triangle me-2"></i>$1</h6>', $fmt);
                                    $fmt = preg_replace('/^⚠️ (.+)$/m', '<h6 style="font-weight:800;color:#d97706;font-size:0.82rem;margin:1rem 0 0.4rem"><i class="fas fa-exclamation-circle me-2"></i>$1</h6>', $fmt);
                                    $fmt = preg_replace('/^💡 (.+)$/m', '<h6 style="font-weight:800;color:#059669;font-size:0.82rem;margin:1rem 0 0.4rem"><i class="fas fa-lightbulb me-2"></i>$1</h6>', $fmt);
                                    $fmt = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $fmt);
                                    $fmt = preg_replace('/^• (.+)$/m', '<li>$1</li>', $fmt);
                                    $fmt = str_replace('---', '<hr style="margin:0.9rem 0;border-color:#e2e8f0">', $fmt);
                                    // Collapse 3+ newlines to 2, then nl2br, then clean big gaps
                                    $fmt = preg_replace("/\n{3,}/", "\n\n", $fmt);
                                    $fmt = nl2br($fmt);
                                    $fmt = preg_replace('/(<\/(div|h6|ul|hr)>)\s*<br\s*\/?>/i', '$1', $fmt);
                                    $fmt = preg_replace('/<br\s*\/?>\s*<br\s*\/?>/', '<br>', $fmt);
                                    $fmt = preg_replace('/<br\s*\/?>\s*(<ul)/i', '$1', $fmt);
                                    $fmt = preg_replace('/((?:<li>.*?<\/li>\s*)+)/s', '<ul style="margin:0.5rem 0 0.75rem 1.2rem;font-size:0.84rem">$1</ul>', $fmt);
                                @endphp
                                <div style="background:#fff;border:1px solid #eef2f7;border-radius:12px;padding:1.25rem;line-height:1.7;font-size:0.85rem;color:#334155">{!! $fmt !!}</div>
                                <textarea id="ambientAnalysisRaw" style="display:none">{{ $ambientAnalysis }}</textarea>
                            </div>
                            <div class="modal-footer" style="border-top:1px solid #f1f5f9; padding:0.9rem 1.25rem;">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="border-radius:8px;">Close</button>
                                <button type="button" class="btn btn-primary" onclick="const t=document.getElementById('ambientAnalysisRaw').value; navigator.clipboard.writeText(t).then(()=>{const b=this;const o=b.innerHTML;b.innerHTML='<i class=&quot;fas fa-check me-1&quot;></i>Copied!';setTimeout(()=>b.innerHTML=o,2000)}).catch(()=>alert('Copy failed - select text manually'))" style="border-radius:8px; background:#7c3aed; border-color:#7c3aed;"><i class="fas fa-copy me-1"></i>Copy Analysis</button>
                            </div>
                        </div>
                    </div>
                </div>
                @endif
                @endif

                <!-- AI Medical Copilot Section — Premium -->
                @if(auth()->check() && auth()->user()->isDoctor())
                <div id="ai-medical-copilot-section" class="table-card" style="display: none; border-top: 3px solid #7c3aed;">
                    <div class="section-head-modern">
                        <div class="head-left">
                            <div class="head-icon" style="background: linear-gradient(135deg, #ede9ff 0%, #ddd6fe 100%); color:#7c3aed; border:1px solid #ddd6fe;">
                                <i class="fas fa-brain"></i>
                            </div>
                            <div>
                                <h4 style="margin:0; font-weight:800; color:#1e293b; font-size:1rem; letter-spacing:-0.01em;">AI Medical Copilot</h4>
                                <p style="margin:2px 0 0; font-size:0.78rem; color:#64748b; font-weight:500;">AI-powered clinical decision support for this appointment</p>
                            </div>
                        </div>
                        <button type="button" class="btn btn-sm" style="background:#fff; border:1px solid #e2e8f0; color:#64748b; border-radius:8px; font-size:0.75rem;" onclick="toggleAIMedicalCopilotForm()">
                            <i class="fas fa-times me-1"></i>Close
                        </button>
                    </div>

                    <!-- Loading State -->
                    <div class="copilot-loading" id="copilotLoadingSection">
                        <div class="copilot-loading-spinner mx-auto"></div>
                        <h5 class="text-primary text-center">AI Medical Copilot is analyzing...</h5>
                        <p class="text-muted text-center">Processing clinical data and generating decision support insights</p>
                    </div>

                    <!-- Error State -->
                    <div class="copilot-error alert alert-danger" id="copilotErrorSection" style="display: none;">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <span id="copilotErrorMessageSection"></span>
                    </div>

                    <!-- Content Area -->
                    <div id="copilotContentSection" style="display: none;">
                        <!-- Tab Navigation -->
                        <div class="d-flex justify-content-start mb-3 border-bottom">
                            <button class="copilot-tab active" data-tab="summary">
                                <i class="fas fa-file-medical me-1"></i>Summary
                            </button>
                            <button class="copilot-tab" data-tab="considerations">
                                <i class="fas fa-list-check me-1"></i>Considerations
                            </button>
                            <button class="copilot-tab" data-tab="questions">
                                <i class="fas fa-question-circle me-1"></i>Questions
                            </button>
                            <button class="copilot-tab" data-tab="red-flags">
                                <i class="fas fa-flag me-1"></i>Red Flags
                            </button>
                            <button class="copilot-tab" data-tab="history">
                                <i class="fas fa-history me-1"></i>Patient History
                            </button>
                        </div>

                        <!-- Tab Content -->
                        <div id="copilotTabsSection">
                            <!-- Summary Tab -->
                            <div class="copilot-tab-content active" data-tab-content="summary">
                                <div class="copilot-section">
                                    <div class="copilot-section-header">
                                        <h6 class="copilot-section-title">
                                            <i class="fas fa-file-medical me-2"></i>Medical Case Summary
                                        </h6>
                                        <span class="badge copilot-badge bg-primary">
                                            <i class="fas fa-check-circle me-1"></i>AI-Generated
                                        </span>
                                    </div>
                                    <div class="copilot-content" id="copilotSummarySection">
                                        <p class="text-muted">Loading medical case summary...</p>
                                    </div>
                                    <div class="form-check mt-2">
                                        <input class="form-check-input copilot-checkbox" type="checkbox" id="includeSummaryInNoteSection">
                                        <label class="form-check-label" for="includeSummaryInNoteSection">
                                            Include in clinical note
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <!-- Considerations Tab -->
                            <div class="copilot-tab-content" data-tab-content="considerations">
                                <div class="copilot-section">
                                    <div class="copilot-section-header">
                                        <h6 class="copilot-section-title">
                                            <i class="fas fa-list-check me-2"></i>Differential Considerations
                                        </h6>
                                        <span class="badge copilot-badge bg-warning text-dark">
                                            <i class="fas fa-exclamation-triangle me-1"></i>Not Diagnoses
                                        </span>
                                    </div>
                                    <div class="copilot-content copilot-warning" id="copilotConsiderationsSection">
                                        <p class="text-muted">Loading differential considerations...</p>
                                    </div>
                                    <div class="copilot-disclaimer">
                                        <strong>⚠️ For clinical consideration only. Physician judgment required.</strong>
                                    </div>
                                    <div class="form-check mt-2">
                                        <input class="form-check-input copilot-checkbox" type="checkbox" id="includeConsiderationsInNoteSection">
                                        <label class="form-check-label" for="includeConsiderationsInNoteSection">
                                            Include in clinical note
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <!-- Questions Tab -->
                            <div class="copilot-tab-content" data-tab-content="questions">
                                <div class="copilot-section">
                                    <div class="copilot-section-header">
                                        <h6 class="copilot-section-title">
                                            <i class="fas fa-question-circle me-2"></i>Suggested Follow-up Questions
                                        </h6>
                                        <span class="badge copilot-badge bg-info">
                                            <i class="fas fa-lightbulb me-1"></i>Clinical Insights
                                        </span>
                                    </div>
                                    <div class="copilot-content copilot-info" id="copilotQuestionsSection">
                                        <p class="text-muted">Loading follow-up questions...</p>
                                    </div>
                                    <div class="copilot-disclaimer">
                                        <strong>💡 These questions help raise diagnostic quality and reduce oversight.</strong>
                                    </div>
                                    <div class="form-check mt-2">
                                        <input class="form-check-input copilot-checkbox" type="checkbox" id="includeQuestionsInNoteSection">
                                        <label class="form-check-label" for="includeQuestionsInNoteSection">
                                            Include in clinical note
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <!-- Red Flags Tab -->
                            <div class="copilot-tab-content" data-tab-content="red-flags">
                                <div class="copilot-section">
                                    <div class="copilot-section-header">
                                        <h6 class="copilot-section-title">
                                            <i class="fas fa-flag me-2"></i>Red Flags Detection
                                        </h6>
                                        <span class="badge copilot-badge bg-danger">
                                            <i class="fas fa-exclamation-circle me-1"></i>Urgent Attention
                                        </span>
                                    </div>
                                    <div class="copilot-content copilot-danger" id="copilotRedFlagsSection">
                                        <p class="text-muted">Loading red flags analysis...</p>
                                    </div>
                                    <div class="copilot-disclaimer">
                                        <strong>⚠️ Consider urgent evaluation if clinically indicated.</strong>
                                    </div>
                                    <div class="form-check mt-2">
                                        <input class="form-check-input copilot-checkbox" type="checkbox" id="includeRedFlagsInNoteSection">
                                        <label class="form-check-label" for="includeRedFlagsInNoteSection">
                                            Include in clinical note
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <!-- Patient History Tab -->
                            <div class="copilot-tab-content" data-tab-content="history">
                                <div class="copilot-section">
                                    <div class="copilot-section-header">
                                        <h6 class="copilot-section-title">
                                            <i class="fas fa-history me-2"></i>Patient Medical History
                                        </h6>
                                        <span class="badge copilot-badge bg-info">
                                            <i class="fas fa-database me-1"></i>Historical Data
                                        </span>
                                    </div>
                                    <div class="copilot-content" id="copilotHistorySection">
                                        <p class="text-muted">Loading patient history...</p>
                                    </div>
                                    <div class="copilot-disclaimer">
                                        <strong>📋 Patient history was used in AI analysis to provide context-aware recommendations.</strong>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Compliance Information -->
                        <div class="copilot-compliance-label">
                            <i class="fas fa-shield-alt me-1"></i>
                            <span id="copilotComplianceLabelSection">AI-generated draft. Physician verified.</span>
                        </div>

                        <!-- Action Buttons -->
                        <div class="d-flex gap-3 justify-content-between align-items-center mt-4 pt-3 border-top">
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-primary" id="saveCopilotAnalysisSection">
                                    <i class="fas fa-save me-2"></i>Save Analysis
                                </button>
                                <button type="button" class="btn btn-secondary" onclick="toggleAIMedicalCopilotForm()">
                                    <i class="fas fa-times me-2"></i>Close
                                </button>
                            </div>
                            <div class="text-muted small">
                                <i class="fas fa-shield-alt me-1"></i>
                                AI analysis will be saved and available for review
                            </div>
                        </div>
                    </div>
                </div>
                @endif
            </div>


        </div>
    </div>
</div>

<!-- Cancel Modal -->
<div class="modal fade" id="cancelModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Cancel Appointment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to cancel this appointment?</p>
                <form id="cancelForm" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Reason for cancellation (optional)</label>
                        <textarea name="cancellation_reason" class="form-control" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Keep Appointment</button>
                <button type="button" class="btn btn-danger" onclick="submitCancellation()">Cancel Appointment</button>
            </div>
        </div>
    </div>
</div>

<!-- Complete Appointment Modal -->
<div class="modal fade" id="completeModal" tabindex="-1" aria-labelledby="completeModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="completeModalLabel">Complete Appointment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="completeForm" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="doctor_notes" class="form-label">Doctor's Notes (optional)</label>
                        <textarea name="doctor_notes" id="doctor_notes" rows="4" class="form-control"
                                  placeholder="Add any notes about the appointment..."></textarea>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" name="follow_up_required" class="form-check-input" id="follow_up_required">
                        <label class="form-check-label" for="follow_up_required">
                            Follow-up appointment recommended
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Complete Appointment</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Prescription Modal -->
<div class="modal fade" id="deletePrescriptionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Delete Prescription</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete the prescription for <strong id="deletePrescriptionName"></strong>?</p>
                <p class="text-danger small">This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn" onclick="confirmDeletePrescription()">Delete Prescription</button>
            </div>
        </div>
    </div>
</div>

<!-- Prescription Help Modal -->
<div class="modal fade modal-premium" id="prescriptionHelpModal" tabindex="-1" aria-labelledby="prescriptionHelpModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div class="d-flex align-items-center gap-3">
                    <div class="head-icon" style="background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%); color:#059669; border:1px solid #a7f3d0;">
                        <i class="fas fa-prescription-bottle"></i>
                    </div>
                    <div>
                        <h5 class="modal-title mb-0" id="prescriptionHelpModalLabel" style="font-size:0.95rem; font-weight:800; color:#1e293b; letter-spacing:-0.01em;">How to Prescribe</h5>
                        <div style="font-size:0.72rem; color:#94a3b8; font-weight:500;">4 workflows • Choose your path • AI is optional</div>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="ml-intro">
                    <i class="fas fa-info-circle" style="color:#059669; margin-top:2px;"></i>
                    <div><strong style="color:#1e293b;">Overview:</strong> Create prescriptions manually or with AI assistance. AI suggestions are based on documented clinical data, not the form itself.</div>
                </div>

                <div class="ml-section-title"><i class="fas fa-list-ol" style="color:#059669;"></i> Four Ways to Prescribe</div>
                <div class="ml-method-grid">
                    <div class="ml-mini-card" style="border-color:#a7f3d0;">
                        <div class="mini-head" style="background:#ecfdf5; color:#059669;"><i class="fas fa-user-md"></i> Manual Entry</div>
                        <div class="mini-body">For experienced doctors — you know the drug.<br><small><strong>Steps:</strong> Fill form → Save. <strong>Do not</strong> press AI.<br><span style="color:#6b7280;">Ex: Refill for known HTN patient.</span></small></div>
                    </div>
                    <div class="ml-mini-card" style="border-color:#93c5fd;">
                        <div class="mini-head" style="background:#eff6ff; color:#2563eb;"><i class="fas fa-brain"></i> AI-First</div>
                        <div class="mini-body">Complex cases — need suggestions first.<br><small><strong>Steps:</strong> Click AI (form can be empty) → Review → Use Suggestion → Save.<br><span style="color:#6b7280;">Ex: Headache, nausea → migraine suggestion.</span></small></div>
                    </div>
                    <div class="ml-mini-card" style="border-color:#7dd3ce;">
                        <div class="mini-head" style="background:#ecfeff; color:#0e7490;"><i class="fas fa-handshake"></i> AI-Assisted</div>
                        <div class="mini-body">Start manually, let AI check.<br><small><strong>Steps:</strong> Fill some fields → Click AI → Accept warnings/alternatives → Save.<br><span style="color:#6b7280;">Ex: Enter Amoxicillin → allergy warning.</span></small></div>
                    </div>
                    <div class="ml-mini-card" style="border-color:#fde68a;">
                        <div class="mini-head" style="background:#fffbeb; color:#92400e;"><i class="fas fa-search"></i> Explore AI</div>
                        <div class="mini-body">Research only — see suggestions without using.<br><small><strong>Steps:</strong> Click AI → Review → Dismiss → Fill manually → Save.<br><span style="color:#6b7280;">Ex: AI suggests antibiotics for viral → you choose symptomatic care.</span></small></div>
                    </div>
                </div>

                <hr class="ml-divider">

                <div class="ml-section-title"><i class="fas fa-database" style="color:#2563eb;"></i> What Data Does AI Use?</div>
                <div class="ml-note" style="background:#f8fafc; border-color:#e2e8f0;">
                    <i class="fas fa-check-circle" style="color:#059669; margin-top:2px;"></i>
                    <div style="font-size:0.78rem; line-height:1.5;"><strong style="color:#1e293b;">Sources:</strong> Appointment symptoms, doctor notes, allergies, past meds, recent diagnosis, chronic conditions. If no documentation exists, AI gives general preventive guidance.</div>
                </div>

                <div class="ml-warning mt-3">
                    <i class="fas fa-shield-alt" style="color:#dc2626; margin-top:2px;"></i>
                    <div><strong>Safety:</strong> AI is clinical decision support only. Always verify allergies, interactions, age/weight, organ function. Confidence (High/Medium/Low) guides but does not replace judgment.</div>
                </div>

                <div class="ml-note mt-3" style="background:#eff6ff; border-color:#dbeafe;">
                    <i class="fas fa-lightbulb" style="color:#2563eb; margin-top:2px;"></i>
                    <div><strong>Tips:</strong> Use Reset to clear; modify AI-suggested dosage/frequency; form works without AI.</div>
                </div>
            </div>
            <div class="modal-footer" style="border-top:1px solid #f1f5f9; padding:0.9rem 1.25rem;">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="border-radius:8px;">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- AI Data Sources — Professional Popup (Premium) -->
<div class="modal fade modal-premium" id="aiDataSourcesModal" tabindex="-1" aria-labelledby="aiDataSourcesModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable modal-dialog-centered">
        <div class="modal-content" style="border:none;border-radius:20px;overflow:hidden;box-shadow:0 24px 64px rgba(15,23,42,0.18);">
            <!-- Premium Header -->
            <div class="modal-header" style="background: linear-gradient(135deg, #1e293b 0%, #334155 100%); border:none; padding:1.25rem 1.5rem; color:#fff;">
                <div class="d-flex align-items-center gap-3">
                    <div class="d-flex align-items-center justify-content-center" style="width:48px;height:48px;border-radius:14px;background:rgba(255,255,255,0.14);border:1px solid rgba(255,255,255,0.18);backdrop-filter:blur(8px);">
                        <i class="fas fa-database" style="font-size:1.1rem;color:#fff;"></i>
                    </div>
                    <div>
                        <h5 class="modal-title mb-0" id="aiDataSourcesModalLabel" style="font-size:1.05rem; font-weight:800; color:#fff; letter-spacing:-0.02em;">AI Clinical Data Sources</h5>
                        <div style="font-size:0.76rem; color:rgba(255,255,255,0.72); font-weight:500; margin-top:2px;">What AI analyzes • Prioritized by importance • Verified sources</div>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="d-none d-md-inline-flex align-items-center gap-1" style="background:rgba(255,255,255,0.14);border:1px solid rgba(255,255,255,0.18);border-radius:20px;padding:0.3rem 0.7rem;font-size:0.7rem;font-weight:700;color:#fff;backdrop-filter:blur(8px);"><span style="width:7px;height:7px;border-radius:50%;background:#22c55e;display:inline-block;box-shadow:0 0 0 4px rgba(34,197,94,0.2);"></span> Live Analysis</span>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close" style="opacity:0.8;"></button>
                </div>
            </div>
            <div class="modal-body" style="background:#f8fafc; padding:1.25rem 1.5rem;">
                <!-- Importance Legend - Modern Pills -->
                <div class="d-flex flex-wrap align-items-center gap-2 p-3" style="background:#fff;border:1px solid #eef2f7;border-radius:14px;box-shadow:0 2px 8px rgba(15,23,42,0.03);">
                    <div class="d-flex align-items-center gap-2" style="font-size:0.76rem;font-weight:600;color:#334155;"><i class="fas fa-circle-info" style="color:#2563eb;"></i> Importance:</div>
                    <span class="badge" style="background:#fee2e2;color:#991b1b;border:1px solid #fecaca;border-radius:20px;padding:0.3rem 0.6rem;font-size:0.68rem;font-weight:700;"><i class="fas fa-shield-halved me-1"></i>CRITICAL</span><span style="font-size:0.72rem;color:#64748b;">required — AI blocked if missing</span>
                    <span class="vr d-none d-md-block" style="height:18px;opacity:0.15;"></span>
                    <span class="badge" style="background:#fef9c3;color:#854d0e;border:1px solid #fde68a;border-radius:20px;padding:0.3rem 0.6rem;font-size:0.68rem;font-weight:700;">Important</span><span style="font-size:0.72rem;color:#64748b;">strongly recommended</span>
                    <span class="vr d-none d-md-block" style="height:18px;opacity:0.15;"></span>
                    <span class="badge" style="background:#f0fdfa;color:#115e59;border:1px solid #99f6e4;border-radius:20px;padding:0.3rem 0.6rem;font-size:0.68rem;font-weight:700;">Helpful</span><span style="font-size:0.72rem;color:#64748b;">context</span>
                </div>

                <!-- Modern Card Grid instead of table -->
                <div class="mt-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <h6 class="mb-0" style="font-weight:800;color:#0f172a;font-size:0.86rem;letter-spacing:-0.01em;"><i class="fas fa-layer-group me-2" style="color:#6366f1;"></i>Sources</h6>
                        <span class="text-muted" style="font-size:0.7rem;font-weight:600;letter-spacing:0.04em;text-transform:uppercase;">9 sources • Sorted by importance</span>
                    </div>
                    <div id="dataSourcesGrid" class="row g-3">
                        <!-- Dynamic modern cards populated by JS — table body kept hidden for backward compat -->
                        <div id="dataSourcesTableBody" style="display:none;"></div>
                    </div>
                </div>

                <!-- Data Completeness - Premium -->
                <div class="mt-3 p-3" style="background:#fff;border:1px solid #eef2f7;border-radius:14px;box-shadow:0 2px 8px rgba(15,23,42,0.03);">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="d-flex align-items-center gap-2"><span class="d-flex align-items-center justify-content-center" style="width:28px;height:28px;border-radius:10px;background:#f0fdf4;border:1px solid #dcfce7;"><i class="fas fa-chart-line" style="color:#059669;font-size:0.78rem;"></i></span><span style="font-weight:800;color:#0f172a;font-size:0.86rem;">Data Completeness</span></div>
                        <span class="badge" style="background:#f1f5f9;color:#475569;border:1px solid #e2e8f0;border-radius:20px;font-size:0.68rem;" id="dataCompletenessBadge">Analyzing...</span>
                    </div>
                    <div class="progress" style="height:10px; background:#f1f5f9; border-radius:99px; overflow:hidden; border:1px solid #e2e8f0;" id="dataCompletenessProgress">
                        <div class="progress-bar" id="dataCompletenessBar" style="width:0%; background: linear-gradient(90deg, #0ea5e9 0%, #6366f1 50%, #10b981 100%); border-radius:99px; transition:width 0.6s ease;"> </div>
                    </div>
                    <div class="d-flex align-items-start gap-2 mt-2 p-2" style="background:#f8fafc;border:1px solid #f1f5f9;border-radius:10px;font-size:0.78rem;color:#334155;" id="dataCompletenessText">Analyzing available clinical data...</div>
                </div>

                <!-- Improvement Tips - Modern -->
                <div class="mt-3 p-3 d-flex gap-3 align-items-start" style="background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%); border:1px solid #fde68a; border-radius:14px;">
                    <span class="d-flex align-items-center justify-content-center flex-shrink-0" style="width:32px;height:32px;border-radius:10px;background:#fff;border:1px solid #fde68a;"><i class="fas fa-lightbulb" style="color:#d97706;"></i></span>
                    <div style="font-size:0.8rem;color:#78350f;"><strong style="color:#92400e;">To improve:</strong> Complete allergies, update meds, add symptoms at booking, create diagnosis.<div class="mt-1 d-flex flex-wrap gap-1" id="improvementChips"><span class="badge bg-white border" style="color:#92400e;border-color:#fde68a !important;border-radius:20px;font-size:0.68rem;">Allergies</span><span class="badge bg-white border" style="color:#92400e;border-color:#fde68a !important;border-radius:20px;font-size:0.68rem;">Medications</span><span class="badge bg-white border" style="color:#92400e;border-color:#fde68a !important;border-radius:20px;font-size:0.68rem;">Symptoms</span><span class="badge bg-white border" style="color:#92400e;border-color:#fde68a !important;border-radius:20px;font-size:0.68rem;">Diagnosis</span></div></div>
                </div>

                <!-- Privacy - Subtle -->
                <div class="mt-3 d-flex gap-2 align-items-center p-2 px-3" style="background:#fff;border:1px solid #eef2f7;border-radius:12px;font-size:0.74rem;color:#64748b;">
                    <span class="d-flex align-items-center justify-content-center" style="width:26px;height:26px;border-radius:9px;background:#eff6ff;border:1px solid #dbeafe;"><i class="fas fa-shield-halved" style="color:#2563eb;font-size:0.72rem;"></i></span>
                    <span><strong style="color:#334155;">Privacy:</strong> Encrypted, HIPAA-compliant. Analysis is local — no patient data leaves your environment.</span>
                    <span class="ms-auto d-none d-md-inline-flex align-items-center gap-1" style="font-size:0.68rem;color:#94a3b8;"><i class="fas fa-lock"></i> Local</span>
                </div>
            </div>
            <div class="modal-footer" style="background:#fff;border-top:1px solid #f1f5f9; padding:0.9rem 1.25rem; gap:0.5rem;">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal" style="border-radius:10px;font-weight:600;font-size:0.84rem;">Close</button>
                <button type="button" class="btn text-white" onclick="refreshDataSources()" style="border-radius:10px;font-weight:700;font-size:0.84rem;background:linear-gradient(135deg,#2563eb 0%,#1d4ed8 100%);border:none;box-shadow:0 4px 12px rgba(37,99,235,0.2);">
                    <i class="fas fa-rotate me-1"></i>Refresh Data
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ML Explanation Modal - Compact Premium -->
<div class="modal fade modal-premium" id="mlExplanationModal" tabindex="-1" aria-labelledby="mlExplanationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div class="d-flex align-items-center gap-3">
                    <div class="head-icon" style="background: linear-gradient(135deg, #ede9ff 0%, #ddd6fe 100%); color: #7c3aed; border: 1px solid #ddd6fe;">
                        <i class="fas fa-brain"></i>
                    </div>
                    <div>
                        <h5 class="modal-title mb-0" id="mlExplanationModalLabel" style="font-size:0.95rem; font-weight:800; color:#1e293b; letter-spacing:-0.01em;">ML Risk Prediction Explanation</h5>
                        <div style="font-size:0.72rem; color:#94a3b8; font-weight:500;">9-feature model • How the score is calculated</div>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="ml-intro">
                    <i class="fas fa-info-circle" style="color:#7c3aed; margin-top:2px;"></i>
                    <div><strong style="color:#1e293b;">How it works:</strong> Model analyzes patient history, appointment patterns and clinical data to estimate no-show and hospitalization probability.</div>
                </div>

                <div class="ml-section-title"><i class="fas fa-chart-line" style="color:#7c3aed;"></i> Features Actually Analyzed</div>
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
                <div class="ml-feature-grid">
                    <div class="ml-feature">
                        <span class="ml-feature-label">No-Show Count</span>
                        <span class="ml-feature-value" style="background:#fffbeb; border-color:#fde68a; color:#92400e;">{{ $features[0] ?? 0 }}</span>
                        <span class="ml-feature-desc">Previous missed appointments</span>
                    </div>
                    <div class="ml-feature">
                        <span class="ml-feature-label">Cancellation Count</span>
                        <span class="ml-feature-value">{{ $features[1] ?? 0 }}</span>
                        <span class="ml-feature-desc">Cancelled appointments</span>
                    </div>
                    <div class="ml-feature">
                        <span class="ml-feature-label">Days Since Last Visit</span>
                        <span class="ml-feature-value" style="background:#eff6ff; border-color:#dbeafe; color:#1e40af;">{{ $features[2] ?? 0 }}</span>
                        <span class="ml-feature-desc">Days since last appointment</span>
                    </div>
                    <div class="ml-feature">
                        <span class="ml-feature-label">Visit Frequency</span>
                        <span class="ml-feature-value" style="background:#eff6ff; border-color:#dbeafe; color:#1e40af;">{{ number_format($features[3] ?? 0, 1) }}/yr</span>
                        <span class="ml-feature-desc">Avg appointments per year</span>
                    </div>
                    <div class="ml-feature">
                        <span class="ml-feature-label">Patient Age</span>
                        <span class="ml-feature-value">{{ $features[4] ?? 0 }} yrs</span>
                        <span class="ml-feature-desc">Age in years</span>
                    </div>
                    <div class="ml-feature">
                        <span class="ml-feature-label">Gender</span>
                        <span class="ml-feature-value" style="{{ ($features[5] ?? 0) == 1 ? 'background:#fef2f2; border-color:#fecaca; color:#dc2626;' : 'background:#f8fafc; border-color:#e2e8f0; color:#64748b;' }}">{{ ($features[5] ?? 0) == 1 ? 'Male' : 'Female/Other' }}</span>
                        <span class="ml-feature-desc">Encoded 1=Male, 0=Other</span>
                    </div>
                    <div class="ml-feature">
                        <span class="ml-feature-label">Chronic Conditions</span>
                        <span class="ml-feature-value" style="{{ ($features[6] ?? 0) > 0 ? 'background:#fef2f2; border-color:#fecaca; color:#dc2626;' : 'background:#ecfdf5; border-color:#a7f3d0; color:#059669;' }}">{{ $features[6] ?? 0 }}</span>
                        <span class="ml-feature-desc">High-risk from diagnoses</span>
                    </div>
                    <div class="ml-feature">
                        <span class="ml-feature-label">Current Medications</span>
                        <span class="ml-feature-value" style="background:#eff6ff; border-color:#dbeafe; color:#1e40af;">{{ $features[7] ?? 0 }}</span>
                        <span class="ml-feature-desc">Active prescriptions</span>
                    </div>
                    <div class="ml-feature">
                        <span class="ml-feature-label">Lead Time</span>
                        <span class="ml-feature-value">{{ $features[8] ?? 0 }} days</span>
                        <span class="ml-feature-desc">Booking → appointment</span>
                    </div>
                </div>
                <div class="ml-note">
                    <i class="fas fa-check-circle" style="color:#2563eb; margin-top:2px;"></i>
                    <div><strong>Enhanced 9-feature model:</strong> Includes cancellations, visit frequency, meds and lead time for improved accuracy.</div>
                </div>

                <hr class="ml-divider">

                <div class="ml-section-title"><i class="fas fa-cogs" style="color:#64748b;"></i> Prediction Method Used</div>
                @php
                    $service = app(\App\Services\PredictiveAnalyticsService::class);
                    $reflection = new ReflectionClass($service);
                    $method = $reflection->getMethod('checkTrainingDataAdequacy');
                    $method->setAccessible(true);
                    $adequacy = $method->invoke($service);
                    $usingML = $adequacy['adequate'];
                @endphp
                <div class="ml-method-grid">
                    <div class="ml-mini-card" style="border-color: {{ $usingML ? '#a7f3d0' : '#fde68a' }};">
                        <div class="mini-head" style="background: {{ $usingML ? '#ecfdf5' : '#fffbeb' }}; color: {{ $usingML ? '#059669' : '#92400e' }};">
                            <i class="fas fa-{{ $usingML ? 'brain' : 'calculator' }}"></i> {{ $usingML ? 'Machine Learning' : 'Rule-Based' }}
                        </div>
                        <div class="mini-body">
                            {{ $usingML ? 'Trained ML models active' : 'Rule-based (ML not adequately trained)' }}
                            <small class="d-block mt-1">{{ $adequacy['total_appointments'] }} appts • {{ $adequacy['no_show_count'] }} no-shows • {{ $adequacy['high_risk_count'] }} high-risk</small>
                        </div>
                    </div>
                    <div class="ml-mini-card">
                        <div class="mini-head" style="background:#eff6ff; color:#1e40af;"><i class="fas fa-chart-bar"></i> Model Status</div>
                        <div class="mini-body">
                            @if($usingML)<span style="color:#059669; font-weight:700;">✓ ML Models Active</span>@else<span style="color:#d97706; font-weight:700;">⚠ Rule-Based Fallback</span>@endif
                            <small class="d-block mt-1">Need: 50 appts, 2% no-show, 5% high-risk</small>
                        </div>
                    </div>
                </div>

                <hr class="ml-divider">

                <div class="ml-section-title"><i class="fas fa-calculator" style="color:#7c3aed;"></i> Risk Calculations</div>
                <div class="ml-risk-grid">
                    <div class="ml-mini-card" style="border-color:#fde68a;">
                        <div class="mini-head" style="background:#fffbeb; color:#92400e;"><i class="fas fa-user-times"></i> No-Show Risk</div>
                        <div class="mini-body">
                            Likelihood patient misses appointment.
                            <small class="d-block mt-2"><strong>Current:</strong> @if(isset($riskScore)) {{ number_format($riskScore->no_show_risk * 100, 1) }}% @else N/A @endif</small>
                        </div>
                    </div>
                    <div class="ml-mini-card" style="border-color:#fecaca;">
                        <div class="mini-head" style="background:#fef2f2; color:#dc2626;"><i class="fas fa-hospital"></i> Hospitalization Risk</div>
                        <div class="mini-body">
                            Likelihood requiring hospitalization.
                            <small class="d-block mt-2"><strong>Current:</strong> @if(isset($riskScore)) {{ number_format($riskScore->hospitalization_risk * 100, 1) }}% @else N/A @endif</small>
                        </div>
                    </div>
                </div>

                <div class="ml-legend" style="margin-top:0.85rem;">
                    <div class="ml-legend-item"><span class="ml-legend-dot low"></span><span><strong>Low &lt;30%:</strong> Strong compliance, stable indicators</span></div>
                    <div class="ml-legend-item"><span class="ml-legend-dot med"></span><span><strong>Medium 30-70%:</strong> Moderate concern — follow-up reminders</span></div>
                    <div class="ml-legend-item"><span class="ml-legend-dot high"></span><span><strong>High &gt;70%:</strong> Significant risk — immediate attention</span></div>
                </div>

                <div class="ml-warning">
                    <i class="fas fa-exclamation-triangle" style="margin-top:2px;"></i>
                    <div><strong>Important:</strong> Statistical estimates for clinical decision support only — not definitive medical advice.</div>
                </div>
            </div>
            <div class="modal-footer" style="background:#f8fafc; border-top:1px solid #f1f5f9; padding:0.75rem 1.25rem;">
                <button type="button" class="btn btn-secondary btn-sm" style="border-radius:8px; padding:0.35rem 0.9rem;" data-bs-dismiss="modal">Close</button>
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
                // Try to parse JSON response first - success toast handled by unified notification system (appointment-completed)
                return response.json().then(data => {
                    if (data.success !== false) {
                        modal.hide();
                        setTimeout(() => window.location.reload(), 1000);
                    } else {
                        throw new Error(data.message || 'Failed to complete appointment');
                    }
                }).catch(() => {
                    // Fallback if response is not JSON
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

    // Toast above modal - centered top, above backdrop (modal 1055/backdrop 1050)
    const notification = document.createElement('div');
    notification.className = `alert ${alertTypes[type]} alert-dismissible fade show position-fixed shadow`;
    notification.style.cssText = 'top: 85px; left: 50%; transform: translateX(-50%); z-index: 9999; min-width: 320px; max-width: 460px;';

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

    // Toast above modal - centered top, above backdrop (modal 1055)
    const notification = document.createElement('div');
    notification.className = `alert ${alertTypes[type]} alert-dismissible fade show position-fixed shadow`;
    notification.style.cssText = 'top: 85px; left: 50%; transform: translateX(-50%); z-index: 9999; min-width: 320px; max-width: 460px;';

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
    const hasErrors = @json(isset($errors) ? $errors->any() : false);
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

document.addEventListener('DOMContentLoaded', function() {
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
    section.style.display = 'block';
    section.style.marginTop = '1.25rem';

    section.innerHTML = `
        <div class="section-head-modern">
            <div class="head-left">
                <div class="head-icon" style="background:#f8fafc;color:#475569;border:1px solid #e2e8f0"><i class="fas fa-history"></i></div>
                <div>
                    <h4 style="margin:0;font-weight:800;color:#1e293b;font-size:1rem;letter-spacing:-0.01em">Patient AI Analysis History</h4>
                    <p style="margin:2px 0 0;font-size:0.78rem;color:#64748b;font-weight:500">Previous AI Medical Copilot analyses for this patient</p>
                </div>
            </div>
            <button type="button" class="btn btn-sm" style="background:#fff;border:1px solid #e2e8f0;color:#64748b;border-radius:8px;font-size:0.75rem" onclick="toggleAIHistorySection()">
                <i class="fas fa-times me-1"></i>Close
            </button>
        </div>

        <div id="aiHistoryContentSection">
            <div class="text-center py-4">
                <div class="spinner-border" role="status" style="color:#475569;width:1.4rem;height:1.4rem;border-width:0.18em"><span class="visually-hidden">Loading...</span></div>
                <p class="mt-2" style="font-size:0.84rem;color:#64748b">Loading AI analysis history...</p>
            </div>
        </div>
    `;

    // Insert as last section in main content column - after Existing Diagnoses, not inside Next Steps grid
    const mainCol = document.querySelector('.col-lg-12');
    if (mainCol) {
        mainCol.appendChild(section);
    } else {
        const copilotSection = document.getElementById('ai-medical-copilot-section');
        if (copilotSection && copilotSection.parentNode) {
            copilotSection.parentNode.insertBefore(section, copilotSection.nextSibling);
        } else {
            document.querySelector('.dashboard-container .container')?.appendChild(section);
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
            <div class="text-center py-4" style="background:#f8fafc;border:1px dashed #e2e8f0;border-radius:12px">
                <div class="d-inline-flex align-items-center justify-content-center rounded-3 mb-3" style="width:48px;height:48px;background:#fff;border:1px solid #eef2f7;color:#94a3b8"><i class="fas fa-brain" style="font-size:1.3rem"></i></div>
                <p class="fw-semibold mb-1" style="font-size:0.88rem;color:#475569">No AI analyses yet</p>
                <p class="small text-muted mb-0" style="font-size:0.78rem">This patient hasn't had any AI Medical Copilot analyses saved yet.</p>
            </div>
        `;
        return;
    }

    let html = '<div class="ai-analyses-timeline" style="display:flex;flex-direction:column;gap:0.9rem">';

    analyses.forEach(analysis => {
        const analysisData = typeof analysis.analysis_data === 'string' ?
            JSON.parse(analysis.analysis_data) : analysis.analysis_data;

        html += `
            <div class="ai-analysis-card" style="background:#fff;border:1px solid #eef2f7;border-radius:12px;overflow:hidden;box-shadow:0 1px 4px rgba(15,23,42,0.04)">
                <div class="d-flex justify-content-between align-items-center" style="padding:0.85rem 1rem;background:#f8fafc;border-bottom:1px solid #eef2f7">
                    <div>
                        <div style="font-weight:700;color:#1e293b;font-size:0.88rem"><i class="fas fa-brain me-2" style="color:#475569"></i>AI Medical Copilot Analysis</div>
                        <small style="color:#64748b;font-size:0.72rem">${new Date(analysis.generated_at).toLocaleDateString()} at ${new Date(analysis.generated_at).toLocaleTimeString()}</small>
                    </div>
                    <div class="d-flex gap-2 align-items-center">
                        ${analysis.status === 'reviewed' ?
                            '<span class="badge" style="background:#ecfdf5;color:#065f46;border:1px solid #a7f3d0;border-radius:99px;font-size:0.70rem"><i class="fas fa-check-circle me-1"></i>Reviewed</span>' :
                            '<span class="badge" style="background:#fffbeb;color:#92400e;border:1px solid #fde68a;border-radius:99px;font-size:0.70rem"><i class="fas fa-clock me-1"></i>Pending</span>'}
                        <a href="/ai/ai-analyses/${analysis.id}" class="btn btn-sm" style="background:#fff;border:1px solid #e2e8f0;color:#475569;border-radius:8px;font-size:0.74rem;padding:0.3rem 0.5rem" target="_blank">
                            <i class="fas fa-eye me-1"></i>View
                        </a>
                    </div>
                </div>
                <div class="p-3">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div style="font-size:0.78rem;font-weight:700;color:#1e293b;margin-bottom:0.35rem"><i class="fas fa-file-medical me-1" style="color:#475569"></i>Summary</div>
                            <p class="mb-0" style="font-size:0.84rem;color:#334155;line-height:1.5">${analysisData.medical_case_summary || 'No summary available'}</p>
                        </div>
                        <div class="col-md-6">
                            <div style="font-size:0.78rem;font-weight:700;color:#1e293b;margin-bottom:0.35rem"><i class="fas fa-list-check me-1" style="color:#b45309"></i>Key Considerations</div>
                            <ul class="mb-0" style="font-size:0.82rem;color:#334155;padding-left:1.1rem">
                                ${displayConsiderationsSection(analysisData.differential_considerations || [])}
                            </ul>
                        </div>
                    </div>
                    <div class="row g-3 mt-1">
                        <div class="col-md-6">
                            <div style="font-size:0.78rem;font-weight:700;color:#1e293b;margin-bottom:0.35rem"><i class="fas fa-question-circle me-1" style="color:#0e7490"></i>Follow-up Questions</div>
                            <ul class="mb-0" style="font-size:0.82rem;color:#334155;padding-left:1.1rem">
                                ${displayQuestionsSection(analysisData.follow_up_questions || [])}
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <div style="font-size:0.78rem;font-weight:700;color:#1e293b;margin-bottom:0.35rem"><i class="fas fa-flag me-1" style="color:#dc2626"></i>Red Flags</div>
                            <ul class="mb-0" style="font-size:0.82rem;color:#334155;padding-left:1.1rem">
                                ${displayRedFlagsSection(analysisData.red_flags || [])}
                            </ul>
                        </div>
                    </div>
                    ${analysis.reviewed_at ? `
                        <div class="mt-3 pt-3" style="border-top:1px solid #f1f5f9">
                            <div style="font-size:0.78rem;font-weight:700;color:#065f46"><i class="fas fa-user-md me-1"></i>Physician Review</div>
                            <p class="mb-1" style="font-size:0.72rem;color:#64748b">Reviewed by Dr. ${analysis.reviewer?.name || 'Unknown'} on ${new Date(analysis.reviewed_at).toLocaleDateString()}</p>
                            ${analysis.doctor_notes ? `<p class="mb-0" style="font-size:0.84rem;color:#334155">${analysis.doctor_notes}</p>` : '<p class="text-muted small mb-0" style="font-size:0.78rem">No additional notes</p>'}
                        </div>
                    ` : ''}
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
}

// Header phone call — works from header action button (future confirmed phone appointments)
function headerShowPatientPhone(appointmentId, btn) {
    const originalHTML = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';
    fetch(`/api/appointments/${appointmentId}/patient-phone`, {
        method: 'GET',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        }
    })
    .then(r => r.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = originalHTML;
        if (data.success && data.phone) {
            // also update the component display if present
            const nameEl = document.getElementById(`patient-name-${appointmentId}`);
            const phoneEl = document.getElementById(`patient-phone-${appointmentId}`);
            const display = document.getElementById(`phone-display-${appointmentId}`);
            const compBtn = document.getElementById(`phone-btn-${appointmentId}`);
            if (nameEl) nameEl.textContent = data.patient_name || '';
            if (phoneEl) { phoneEl.textContent = data.phone; phoneEl.href = `tel:${data.phone}`; }
            if (display) display.style.display = 'block';
            if (compBtn) compBtn.style.display = 'none';
            // also show quick alert
            if (typeof Swal !== 'undefined') {
                Swal.fire({ icon: 'success', title: data.patient_name || 'Patient', html: `<a href="tel:${data.phone}" style="font-size:1.4em;font-weight:700;">${data.phone}</a>`, confirmButtonText: 'Call' });
            } else {
                showNotification(`Patient phone: ${data.phone}`, 'success');
            }
        } else {
            if (typeof Swal !== 'undefined') Swal.fire({ icon: 'error', title: 'Error', text: data.error || 'Phone not available' });
            else showNotification(data.error || 'Phone not available', 'error');
        }
    })
    .catch(() => {
        btn.disabled = false;
        btn.innerHTML = originalHTML;
        if (typeof Swal !== 'undefined') Swal.fire({ icon: 'error', title: 'Error', text: 'Failed to get phone. Try again.' });
        else showNotification('Failed to get phone. Try again.', 'error');
    });
}
</script>
@endpush