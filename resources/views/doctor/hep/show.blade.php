@extends('master')

@section('title', $program->title . ' - Physical Therapy HEP Program')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/doctor-dashboard.css') }}">
<link rel="stylesheet" href="{{ asset('demos/medical/medical.css') }}">
<style>
/* Header — same gradient system as appointments/show for consistency */
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
.header-actions-wrap {
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
.dashboard-header .status-badge.status-active { color: #065f46 !important; background: #d1fae5 !important; border-color: #a7f3d0 !important; }
.dashboard-header .status-badge.status-draft { color: #92400e !important; background: #fef3c7 !important; border-color: #fde68a !important; }
.dashboard-header .status-badge.status-completed { color: #1e40af !important; background: #dbeafe !important; border-color: #bfdbfe !important; }
.dashboard-header .status-badge.status-paused { color: #6b7280 !important; background: #f1f5f9 !important; border-color: #e2e8f0 !important; }
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
.quick-actions-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 0.75rem;
}
@media (max-width: 768px) { .quick-actions-grid { grid-template-columns: 1fr; } }
.hep-actions { display: none; } /* legacy - hidden, actions moved to Quick Actions card */
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
.action-btn-primary { background: #ffffff !important; color: #1e3a8a !important; border-color: #ffffff !important; }
.action-btn-primary:hover { background: #f1f5f9 !important; color: #1e3a8a !important; border-color: #f1f5f9 !important; }
.action-btn-success { background: #10b981 !important; color: #ffffff !important; border-color: #10b981 !important; }
.action-btn-success:hover { background: #059669 !important; border-color: #059669 !important; color: #fff !important; }
.action-btn-secondary { background: #ffffff !important; color: #475569 !important; border-color: #e2e8f0 !important; }
.action-btn-secondary:hover { background: #f8fafc !important; color: #334155 !important; border-color: #cbd5e1 !important; }
.action-btn-danger { background: #ffffff !important; color: #dc2626 !important; border-color: #fecaca !important; }
.action-btn-danger:hover { background: #fef2f2 !important; color: #b91c1c !important; border-color: #fca5a5 !important; }
@media (max-width: 992px) {
    .header-actions-wrap { width: 100%; justify-content: space-between; }
}
@media (max-width: 576px) {
    .dashboard-header { padding: 1.5rem !important; }
    .header-actions-wrap { gap: 0.4rem; }
    .action-btn { flex: 1; justify-content: center; padding: 0.6rem 0.7rem !important; font-size: 0.79rem !important; }
}

/* Section headers — same as appointments/show */
.table-card .section-head-modern { display:flex; align-items:center; justify-content:space-between; gap:0.75rem; margin:-1.3rem -1.3rem 1.1rem -1.3rem; padding:1rem 1.3rem; background:#f8fafc; border-bottom:1px solid #e2e8f0; border-radius:12px 12px 0 0; flex-wrap:wrap; }
.table-card .section-head-modern .head-left { display:flex; align-items:center; gap:0.75rem; }
.table-card .section-head-modern .head-icon { width:38px; height:38px; border-radius:9px; display:flex; align-items:center; justify-content:center; font-size:0.95rem; flex-shrink:0; background:#1e293b !important; color:#fff !important; border:1px solid #1e293b !important; }
.table-card .section-head-modern h4, .table-card .section-head-modern h5 { color:#0f172a !important; font-weight:800 !important; letter-spacing:-0.01em; }
.table-card .section-head-modern p { color:#475569 !important; font-weight:500 !important; }

/* Premium exercise program styling */
.progress-circle {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: conic-gradient(#2c5aa0 0% var(--progress), #e2e8f0 var(--progress) 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
}
.progress-value {
    font-size: 1.2rem;
    font-weight: 800;
    color: #1e3a8a;
    background: white;
    width: 64px;
    height: 64px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}
.week-section {
    border: 1px solid #eef2f7;
    border-radius: 12px;
    padding: 1.1rem;
    background: #ffffff;
    box-shadow: 0 1px 4px rgba(15,23,42,0.03);
}
.week-header {
    color: #0f172a;
    margin: -1.1rem -1.1rem 1rem -1.1rem;
    padding: 0.85rem 1.1rem;
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
    border-radius: 12px 12px 0 0;
    font-weight: 800;
    font-size: 0.88rem;
    letter-spacing: -0.01em;
    display:flex;
    align-items:center;
    justify-content:space-between;
    flex-wrap:wrap;
    gap:0.5rem;
}
.week-header .week-badge {
    background: #1e293b;
    color: #fff;
    border-radius: 99px;
    padding: 0.28rem 0.65rem;
    font-size: 0.70rem;
    font-weight: 700;
    letter-spacing: 0.02em;
}
.exercises-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 0.85rem;
}
.exercise-card {
    background: #ffffff;
    border: 1px solid #eef2f7;
    border-left: 3px solid #2c5aa0;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 1px 4px rgba(15,23,42,0.04);
    transition: all 0.22s ease;
    display: flex;
    flex-direction: column;
}
.exercise-card:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(15,23,42,0.07); border-left-color: #1e3a8a; }
.exercise-card__header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    padding: 0.85rem 1rem;
    border-bottom: 1px solid #f1f5f9;
    background: linear-gradient(180deg, #ffffff 0%, #fcfdff 100%);
}
.exercise-card__title-wrap { display:flex; align-items:center; gap:0.65rem; min-width:0; flex:1; }
.exercise-card__icon {
    width: 38px; height: 38px;
    border-radius: 9px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    color: #1e293b;
    display: flex; align-items: center; justify-content: center;
    font-size: 0.95rem; flex-shrink:0;
}
.exercise-card__title {
    font-size: 0.88rem; font-weight: 800; color: #0f172a; line-height:1.25;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.exercise-card__subtitle { font-size: 0.71rem; color:#94a3b8; font-weight:500; }
.exercise-image {
    width: 44px; height: 44px;
    object-fit: cover;
    border-radius: 8px;
    border:1px solid #e2e8f0;
    flex-shrink:0;
}
.exercise-placeholder {
    width: 44px; height: 44px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
    color: #94a3b8; font-size:0.9rem; flex-shrink:0;
}
.exercise-card__body { padding: 0.85rem 1rem 0.9rem; flex:1; display:flex; flex-direction:column; }
.metrics {
    display: flex; flex-wrap: wrap; gap: 0.40rem; margin-bottom: 0.75rem;
}
.metric {
    display:inline-flex; align-items:center; gap:0.32rem;
    background: #f8fafc; border:1px solid #eef2f7;
    border-radius: 99px; padding: 0.28rem 0.60rem;
    font-size: 0.72rem; font-weight:700; color:#334155;
    letter-spacing:-0.01em;
}
.metric i { color:#64748b; font-size:0.70rem; }
.metric.metric-sets { background:#eff6ff; border-color:#dbeafe; color:#1e40af; }
.metric.metric-sets i { color:#2563eb; }
.metric.metric-reps { background:#f0fdf4; border-color:#dcfce7; color:#166534; }
.metric.metric-reps i { color:#16a34a; }
.exercise-notes {
    margin-top:auto;
    background:#f8fafc;
    border:1px solid #eef2f7;
    border-left:3px solid #e2e8f0;
    border-radius:8px;
    padding:0.60rem 0.75rem;
    font-size:0.78rem; line-height:1.45; color:#475569;
}
.exercise-notes strong {
    display:block; font-size:0.68rem; text-transform:uppercase; letter-spacing:0.05em; color:#64748b; margin-bottom:0.15rem;
}
.exercise-section {
    border: 1px solid #eef2f7;
    border-radius: 12px;
    padding: 1.25rem;
    background: white;
}
.progression-table .table th {
    background: #f8fafc;
    font-weight: 700;
    font-size: 0.72rem;
    text-transform:uppercase;
    letter-spacing:0.06em;
    color:#64748b;
    border-bottom:2px solid #e2e8f0;
}
.stat-mini {
    display: flex;
    justify-content: space-between;
    padding: 0.55rem 0;
    border-bottom: 1px solid #f1f5f9;
    font-size:0.84rem;
}
.stat-mini:last-child { border-bottom: none; }
.stat-mini .label { color:#64748b; font-weight:500; font-size:0.78rem; }
.stat-mini .value { color:#1e293b; font-weight:700; }
.view-toggle {
    background:#f8fafc; border:1px solid #eef2f7; border-radius:10px; padding:0.25rem; gap:0.25rem;
}
.view-toggle .btn {
    border-radius:8px; font-size:0.76rem; font-weight:700; padding:0.38rem 0.85rem;
    border:none !important; color:#64748b; background:transparent;
}
.view-toggle .btn.active {
    background:#ffffff; color:#0f172a; border:1px solid #e2e8f0 !important;
    box-shadow:0 1px 4px rgba(15,23,42,0.06);
}
.view-toggle .btn:hover:not(.active) { background:#ffffff; color:#1e293b; }
@media (max-width: 768px) {
    .exercises-grid { grid-template-columns: 1fr; }
    .exercise-details { grid-template-columns: 1fr; }
}
</style>
@endpush

@section('content')
<div class="dashboard-container">
    <div class="container appointment-details">
        <!-- Header — clean, single primary action like appointments/show -->
        <div class="dashboard-header">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <h2><i class="fas fa-dumbbell me-2"></i>{{ $program->title }}</h2>
                    <p>Physical Therapy — Home Exercise Program • {{ $program->duration_weeks }} weeks • {{ $program->hepExercises->count() }} exercises</p>
                </div>
                <div class="header-actions-wrap">
                    @php
                        $statusClass = $program->status === 'active' ? 'status-active' : ($program->status === 'draft' ? 'status-draft' : ($program->status === 'completed' ? 'status-completed' : 'status-paused'));
                        $statusIcon = $program->status === 'active' ? 'fa-play-circle' : ($program->status === 'draft' ? 'fa-pen' : ($program->status === 'completed' ? 'fa-check-double' : 'fa-pause-circle'));
                    @endphp
                    <span class="status-badge {{ $statusClass }}">
                        <i class="fas {{ $statusIcon }} me-1"></i>{{ ucfirst($program->status) }}
                    </span>
                    <a href="{{ route('doctor.hep.index') }}" class="btn btn-back">
                        <i class="fas fa-arrow-left me-2"></i>Back to Programs
                    </a>
                    <a href="{{ route('doctor.hep.edit', $program) }}" class="btn action-btn action-btn-primary" title="Edit program">
                        <i class="fas fa-edit"></i>Edit
                    </a>
                </div>
            </div>
        </div>

        <!-- Info Cards Grid — premium like appointments/show -->
        <div class="info-cards-grid">
            <!-- Program Overview Card -->
            <div class="info-card-premium">
                <div class="card-inner">
                    <div class="card-top">
                        <div class="d-flex align-items-center gap-3">
                            <div class="card-icon-box icon-red">
                                <i class="fas fa-clipboard-list"></i>
                            </div>
                            <div>
                                <h5 class="card-title">Program Overview</h5>
                                <div class="card-subtitle">{{ $program->diagnosis?->diagnosis_name ?? 'No diagnosis linked' }} • Created {{ $program->created_at->format('M j, Y') }}</div>
                            </div>
                        </div>
                        <div class="card-badge-duration">
                            <strong>{{ $program->duration_weeks }}</strong>
                            <span>weeks</span>
                        </div>
                    </div>
                    <div class="info-rows">
                        <div class="info-row">
                            <div class="info-row-icon"><i class="fas fa-layer-group"></i></div>
                            <div class="flex-grow-1">
                                <span class="info-row-label">Total Exercises</span>
                                <span class="info-row-value">{{ $program->hepExercises->count() }} • {{ $program->hepExercises->unique('exercise_id')->count() }} unique</span>
                            </div>
                        </div>
                        <div class="info-row">
                            <div class="info-row-icon"><i class="fas fa-calendar-week"></i></div>
                            <div class="flex-grow-1">
                                <span class="info-row-label">Weeks Covered</span>
                                <span class="info-row-value">{{ $program->hepExercises->max('week_number') ?? 0 }} / {{ $program->duration_weeks }} weeks</span>
                            </div>
                        </div>
                        <div class="info-row">
                            <div class="info-row-icon"><i class="fas fa-stethoscope"></i></div>
                            <div class="flex-grow-1">
                                <span class="info-row-label">Diagnosis</span>
                                <span class="info-row-value">{{ $program->diagnosis?->diagnosis_name ?? '—' }}</span>
                            </div>
                        </div>
                    </div>
                    @if($program->description)
                        <div class="mt-3">
                            <div class="reason-content-modern" style="padding:0.85rem 1rem 0.85rem 1.15rem;">
                                <i class="fas fa-quote-right quote-icon" style="right:10px;top:6px;font-size:1.1rem;"></i>
                                <p style="font-size:0.82rem;"><strong class="text-dark">Description:</strong> {{ $program->description }}</p>
                            </div>
                        </div>
                    @endif
                    @if($program->goals && count($program->goals) > 0)
                        <div class="mt-3 p-3" style="background:#f8fafc;border:1px solid #eef2f7;border-radius:10px;">
                            <div class="d-flex align-items-center gap-2 mb-2"><i class="fas fa-bullseye text-muted"></i><strong style="font-size:0.78rem;letter-spacing:0.05em;text-transform:uppercase;color:#475569;">Goals & Objectives</strong></div>
                            <ul class="mb-0 ps-3" style="font-size:0.82rem;color:#334155;">
                                @foreach($program->goals as $goal)
                                    <li class="mb-1">{{ $goal }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
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
                                <div class="card-subtitle">Assigned patient & contact</div>
                            </div>
                        </div>
                        @if($program->patient)
                            <span class="badge bg-light text-dark border" style="font-size:0.72rem;border-radius:99px;padding:0.35rem 0.65rem;"><i class="fas fa-user-check me-1 text-success"></i>Assigned</span>
                        @endif
                    </div>
                    <div class="info-rows">
                        @if($program->patient)
                            <div class="info-row">
                                <div class="info-row-icon"><i class="fas fa-user"></i></div>
                                <div class="flex-grow-1">
                                    <span class="info-row-label">Full Name</span>
                                    <span class="info-row-value">{{ $program->patient->name }}</span>
                                </div>
                                <a href="{{ route('doctor.patients.show', $program->patient->id) }}" class="btn btn-sm" style="background:#fff;border:1px solid #e2e8f0;color:#475569;border-radius:8px;padding:0.28rem 0.55rem;font-size:0.72rem;font-weight:600" title="View patient"><i class="fas fa-external-link-alt me-1"></i>View</a>
                            </div>
                            <div class="info-row">
                                <div class="info-row-icon"><i class="fas fa-envelope"></i></div>
                                <div class="flex-grow-1">
                                    <span class="info-row-label">Email Address</span>
                                    <span class="info-row-value"><a href="mailto:{{ $program->patient->email }}">{{ $program->patient->email }}</a></span>
                                </div>
                                <a href="mailto:{{ $program->patient->email }}" class="btn btn-sm btn-light border rounded-circle p-0 d-flex align-items-center justify-content-center" style="width:32px;height:32px;"><i class="fas fa-paper-plane small text-muted"></i></a>
                            </div>
                            @if($program->patient->phone)
                            <div class="info-row">
                                <div class="info-row-icon"><i class="fas fa-phone"></i></div>
                                <div class="flex-grow-1">
                                    <span class="info-row-label">Phone Number</span>
                                    <span class="info-row-value"><a href="tel:{{ $program->patient->phone }}">{{ $program->patient->phone }}</a></span>
                                </div>
                                <a href="tel:{{ $program->patient->phone }}" class="btn btn-sm btn-light border rounded-circle p-0 d-flex align-items-center justify-content-center" style="width:32px;height:32px;"><i class="fas fa-phone small text-muted"></i></a>
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
                        @else
                            <div class="text-center py-4" style="background:#f8fafc;border:1px dashed #e2e8f0;border-radius:12px;">
                                <div class="d-inline-flex align-items-center justify-content-center rounded-3 mb-2" style="width:42px;height:42px;background:#fff;border:1px solid #eef2f7;color:#94a3b8;"><i class="fas fa-user-plus"></i></div>
                                <p class="fw-semibold mb-1" style="font-size:0.84rem;color:#475569;">No patient assigned</p>
                                <p class="small text-muted mb-2" style="font-size:0.76rem;">Assign this program to a patient to track progress.</p>
                                <button type="button" class="btn btn-sm btn-success assign-program-btn"><i class="fas fa-user-plus me-1"></i>Assign Now</button>
                            </div>
                        @endif
                    </div>
                    @if($program->patient)
                    <div class="mt-3">
                        <div class="stat-mini"><span class="label">Duration</span><span class="value">{{ $program->duration_weeks }} weeks</span></div>
                        <div class="stat-mini"><span class="label">Total Exercises</span><span class="value">{{ $program->hepExercises->count() }}</span></div>
                        <div class="stat-mini"><span class="label">Unique Exercises</span><span class="value">{{ $program->hepExercises->unique('exercise_id')->count() }}</span></div>
                        <div class="stat-mini"><span class="label">Weeks Covered</span><span class="value">{{ $program->hepExercises->max('week_number') ?? 0 }}</span></div>
                        @if($assignment)
                            <div class="stat-mini"><span class="label">Patient Compliance</span><span class="value">{{ $progressStats['completion_percentage'] }}%</span></div>
                        @endif
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Quick Actions — moved out of header to avoid crowding (like appointments completed next-steps) -->
        <div class="table-card mb-4">
            <div class="section-head-modern">
                <div class="head-left">
                    <div class="head-icon"><i class="fas fa-bolt"></i></div>
                    <div>
                        <h5>Quick Actions</h5>
                        <p>Manage assignment & lifecycle</p>
                    </div>
                </div>
                <span class="badge bg-light text-muted border fw-medium d-none d-sm-inline" style="font-size:0.72rem;">{{ $program->hepAssignments->isEmpty() ? 'Not assigned' : 'Assigned' }}</span>
            </div>
            <div class="quick-actions-grid">
                @if($program->hepAssignments->isEmpty())
                <button type="button" class="action-tile tone-success assign-program-btn" style="border:1px solid #eef2f7;">
                    <div class="tile-icon"><i class="fas fa-user-plus"></i></div>
                    <span class="tile-title">Assign to Patient</span>
                    <span class="tile-desc">Link program to patient</span>
                </button>
                @else
                <a href="{{ route('doctor.hep.progress', $program) }}" class="action-tile tone-info" style="border:1px solid #eef2f7;text-decoration:none;">
                    <div class="tile-icon"><i class="fas fa-chart-line"></i></div>
                    <span class="tile-title">View Progress</span>
                    <span class="tile-desc">Compliance & sessions</span>
                </a>
                @endif
                <button type="button" class="action-tile tone-primary duplicate-program-btn" style="border:1px solid #eef2f7;">
                    <div class="tile-icon"><i class="fas fa-copy"></i></div>
                    <span class="tile-title">Duplicate</span>
                    <span class="tile-desc">Create a copy</span>
                </button>
                <button type="button" class="action-tile tone-danger delete-program-btn" style="border:1px solid #fee2e2;background:#fff;">
                    <div class="tile-icon" style="background:#fef2f2;color:#dc2626;border:1px solid #fecaca;"><i class="fas fa-trash"></i></div>
                    <span class="tile-title" style="color:#dc2626;">Delete Program</span>
                    <span class="tile-desc">Permanent action</span>
                </button>
            </div>
        </div>

        @if($assignment)
        <!-- Patient Progress — premium like AI analytics section -->
        <div class="table-card mb-4">
            <div class="section-head-modern">
                <div class="head-left">
                    <div class="head-icon"><i class="fas fa-chart-line"></i></div>
                    <div>
                        <h5>Patient Progress</h5>
                        <p>Compliance & weekly completion</p>
                    </div>
                </div>
                <a href="{{ route('doctor.hep.progress', $program) }}" class="btn btn-sm" style="background:#fff;border:1px solid #e2e8f0;color:#1e293b;border-radius:8px;font-size:0.78rem;font-weight:600;padding:0.35rem 0.75rem;">
                    <i class="fas fa-eye me-1"></i>Detailed Progress
                </a>
            </div>
            <div class="row text-center g-3">
                <div class="col-md-3">
                    <div class="progress-circle" data-progress="{{ $progressStats['completion_percentage'] }}">
                        <div class="progress-value">{{ $progressStats['completion_percentage'] }}%</div>
                    </div>
                    <small class="text-muted d-block mt-2 fw-semibold" style="font-size:0.74rem;letter-spacing:0.04em;text-transform:uppercase;">Overall Progress</small>
                </div>
                <div class="col-md-3">
                    <div class="p-3 rounded-3" style="background:#f8fafc;border:1px solid #eef2f7;">
                        <h3 class="text-primary mb-1" style="font-weight:800;">{{ $progressStats['completed_exercises'] }}</h3>
                        <small class="text-muted fw-medium">Exercises Completed</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="p-3 rounded-3" style="background:#f8fafc;border:1px solid #eef2f7;">
                        <h3 class="text-info mb-1" style="font-weight:800;">{{ $progressStats['current_week'] }}</h3>
                        <small class="text-muted fw-medium">Current Week</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="p-3 rounded-3" style="background:#f8fafc;border:1px solid #eef2f7;">
                        <h3 class="text-success mb-1" style="font-weight:800;">{{ $assignment->hepProgress->count() }}</h3>
                        <small class="text-muted fw-medium">Total Sessions</small>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- Exercise Program — table-card premium -->
        <div class="table-card">
            <div class="section-head-modern">
                <div class="head-left">
                    <div class="head-icon"><i class="fas fa-dumbbell"></i></div>
                    <div>
                        <h5>Exercise Program</h5>
                        <p>{{ $program->hepExercises->count() }} exercises across {{ $program->hepExercises->max('week_number') ?? 0 }} weeks</p>
                    </div>
                </div>
                <div class="btn-group view-toggle" role="group">
                    <button type="button" class="btn btn-sm btn-outline-secondary active" data-view="week">Week View</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-view="exercise">Exercise View</button>
                </div>
            </div>

            <!-- Week View -->
            <div id="week-view">
                @forelse($exercisesByWeek as $week => $exercises)
                    <div class="week-section mb-4">
                        <h6 class="week-header">
                            <span><i class="fas fa-calendar-week me-2" style="color:#2c5aa0"></i>Week {{ $week }}</span>
                            <span class="week-badge">{{ $exercises->count() }} exercises</span>
                        </h6>
                        <div class="exercises-grid">
                            @foreach($exercises as $hepExercise)
                                <div class="exercise-card">
                                    <div class="exercise-card__header">
                                        <div class="exercise-card__title-wrap">
                                            <div class="exercise-card__icon"><i class="fas fa-dumbbell"></i></div>
                                            <div class="min-w-0 flex-grow-1">
                                                <div class="exercise-card__title" title="{{ $hepExercise->exercise->name }}">{{ $hepExercise->exercise->name }}</div>
                                                <div class="exercise-card__subtitle">{{ $hepExercise->exercise->category ?? 'Functional' }} • Week {{ $hepExercise->week_number }}</div>
                                            </div>
                                        </div>
                                        @if($hepExercise->exercise->image_url)
                                            <img src="{{ $hepExercise->exercise->image_url }}" alt="{{ $hepExercise->exercise->name }}" class="exercise-image">
                                        @else
                                            <div class="exercise-placeholder">
                                                <i class="fas fa-dumbbell"></i>
                                            </div>
                                        @endif
                                    </div>
                                    <div class="exercise-card__body">
                                        <div class="metrics">
                                            <span class="metric metric-sets"><i class="fas fa-layer-group"></i>{{ $hepExercise->sets ?? '–' }} sets</span>
                                            <span class="metric metric-reps"><i class="fas fa-redo"></i>{{ $hepExercise->reps ?? '–' }} reps</span>
                                            @if($hepExercise->duration_seconds)
                                                <span class="metric"><i class="fas fa-stopwatch"></i>{{ $hepExercise->duration_seconds }}s</span>
                                            @endif
                                            @if($hepExercise->frequency)
                                                <span class="metric"><i class="fas fa-sync-alt"></i>{{ $hepExercise->frequency }}</span>
                                            @endif
                                        </div>
                                        @if($hepExercise->notes)
                                            <div class="exercise-notes">
                                                <strong>Notes</strong>{{ $hepExercise->notes }}
                                            </div>
                                        @else
                                            <div class="exercise-notes" style="opacity:0.6; border-left-color:#f1f5f9;">
                                                <strong>Notes</strong><span class="fst-italic">No additional instructions</span>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @empty
                    <div class="text-center py-5" style="background:#f8fafc;border:1px dashed #e2e8f0;border-radius:12px;">
                        <i class="fas fa-dumbbell fa-2x text-muted mb-2 d-block"></i>
                        <p class="text-muted mb-0">No exercises configured for this program.</p>
                    </div>
                @endforelse
            </div>

            <!-- Exercise View (Hidden by default) -->
            <div id="exercise-view" style="display: none;">
                <div class="exercises-list">
                    @foreach($program->hepExercises->groupBy('exercise.name') as $exerciseName => $hepExercises)
                        <div class="exercise-section mb-4">
                            <h6 class="fw-bold mb-3" style="color:#1e293b; border-bottom:2px solid #e2e8f0; padding-bottom:0.5rem; font-size:0.88rem;">
                                <i class="fas fa-dumbbell me-2 text-muted"></i>{{ $exerciseName }}
                                <span class="badge bg-light text-muted border ms-2" style="font-size:0.70rem;">{{ $hepExercises->count() }} weeks</span>
                            </h6>
                            <div class="progression-table">
                                <div class="table-responsive">
                                    <table class="table table-sm doctor-table mb-0">
                                        <thead>
                                            <tr>
                                                <th>Week</th>
                                                <th>Sets</th>
                                                <th>Reps</th>
                                                <th>Duration</th>
                                                <th>Frequency</th>
                                                <th>Notes</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($hepExercises->sortBy('week_number') as $hepExercise)
                                                <tr>
                                                    <td><span class="badge" style="background:#f1f5f9;color:#334155;border:1px solid #e2e8f0;font-size:0.72rem;">{{ $hepExercise->week_number }}</span></td>
                                                    <td>{{ $hepExercise->sets ?? '-' }}</td>
                                                    <td>{{ $hepExercise->reps ?? '-' }}</td>
                                                    <td>{{ $hepExercise->duration_seconds ? $hepExercise->duration_seconds . 's' : '-' }}</td>
                                                    <td>{{ $hepExercise->frequency ?? '-' }}</td>
                                                    <td style="max-width:200px;white-space:normal;font-size:0.78rem;">{{ $hepExercise->notes ?? '-' }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Assign Program Modal — premium -->
<div class="modal fade" id="assignProgramModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow" style="border-radius:16px;overflow:hidden;">
            <div class="modal-header" style="background:#f8fafc;border-bottom:1px solid #e2e8f0;">
                <h5 class="modal-title fw-bold" style="color:#1e293b;font-size:1rem;"><i class="fas fa-user-plus me-2 text-success"></i>Assign HEP Program</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="assignProgramForm" method="POST" action="{{ route('doctor.hep.assign', $program) }}">
                @csrf
                <div class="modal-body" style="padding:1.25rem;">
                    <div class="mb-3">
                        <label for="assign_patient_id" class="form-label fw-semibold" style="font-size:0.84rem;">Select Patient <span class="text-danger">*</span></label>
                        <select class="form-select" id="assign_patient_id" name="patient_id" required style="border-radius:10px;border:1px solid #e2e8f0;">
                            <option value="">Choose a patient...</option>
                            @foreach($patients as $patient)
                                <option value="{{ $patient->id }}">{{ $patient->name }} ({{ $patient->email }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="assign_notes" class="form-label fw-semibold" style="font-size:0.84rem;">Assignment Notes (Optional)</label>
                        <textarea class="form-control" id="assign_notes" name="notes" rows="3" placeholder="Any special instructions for the patient..." style="border-radius:10px;border:1px solid #e2e8f0;"></textarea>
                    </div>
                </div>
                <div class="modal-footer" style="background:#f8fafc;border-top:1px solid #f1f5f9;">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="border-radius:10px;">Cancel</button>
                    <button type="submit" class="btn btn-success" style="border-radius:10px;font-weight:700;"><i class="fas fa-check me-1"></i>Assign Program</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const progressCircles = document.querySelectorAll('.progress-circle');
    progressCircles.forEach(circle => {
        const progress = circle.dataset.progress;
        circle.style.setProperty('--progress', progress + '%');
    });

    const viewButtons = document.querySelectorAll('[data-view]');
    viewButtons.forEach(button => {
        button.addEventListener('click', function() {
            const view = this.dataset.view;
            viewButtons.forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');
            document.getElementById('week-view').style.display = view === 'week' ? 'block' : 'none';
            document.getElementById('exercise-view').style.display = view === 'exercise' ? 'block' : 'none';
        });
    });

    const assignBtns = document.querySelectorAll('.assign-program-btn');
    assignBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const modal = new bootstrap.Modal(document.getElementById('assignProgramModal'));
            modal.show();
        });
    });

    const assignForm = document.getElementById('assignProgramForm');
    if (assignForm) {
        assignForm.addEventListener('submit', function(e) {
            const formData = new FormData(this);
            const params = new URLSearchParams();
            for (const [key, value] of formData.entries()) {
                params.append(key, value);
            }
            e.preventDefault();
            fetch(this.action, {
                method: 'POST',
                body: params,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'Content-Type': 'application/x-www-form-urlencoded'
                }
            })
            .then(response => {
                if (response.redirected) {
                    window.location.reload();
                    return;
                }
                const contentType = response.headers.get('content-type');
                if (contentType && contentType.includes('application/json')) {
                    if (!response.ok) throw new Error('Network response was not ok');
                    return response.json();
                } else {
                    window.location.reload();
                    return;
                }
            })
            .then(data => {
                if (data && data.success) {
                    const modal = bootstrap.Modal.getInstance(document.getElementById('assignProgramModal'));
                    if (modal) modal.hide();
                    const alert = document.createElement('div');
                    alert.className = 'alert alert-success alert-dismissible fade show';
                    alert.innerHTML = `${data.message}<button type="button" class="btn-close" data-bs-dismiss="alert"></button>`;
                    const container = document.querySelector('.dashboard-container');
                    container.insertBefore(alert, container.firstChild);
                    setTimeout(() => window.location.reload(), 1200);
                } else if (data && !data.success) {
                    const alert = document.createElement('div');
                    alert.className = 'alert alert-danger alert-dismissible fade show';
                    alert.innerHTML = `Error: ${data.message || 'Failed to assign program'}<button type="button" class="btn-close" data-bs-dismiss="alert"></button>`;
                    document.querySelector('.dashboard-container').insertBefore(alert, document.querySelector('.dashboard-container').firstChild);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                const alert = document.createElement('div');
                alert.className = 'alert alert-danger alert-dismissible fade show';
                alert.innerHTML = `An error occurred while assigning the program<button type="button" class="btn-close" data-bs-dismiss="alert"></button>`;
                document.querySelector('.dashboard-container').insertBefore(alert, document.querySelector('.dashboard-container').firstChild);
            });
        });
    }

    const deleteBtn = document.querySelector('.delete-program-btn');
    if (deleteBtn) {
        deleteBtn.addEventListener('click', function() {
            if (confirm('Are you sure you want to delete this HEP program? This action cannot be undone.')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '{{ route("doctor.hep.destroy", $program) }}';
                const csrfToken = document.createElement('input');
                csrfToken.type = 'hidden';
                csrfToken.name = '_token';
                csrfToken.value = document.querySelector('meta[name="csrf-token"]').content;
                form.appendChild(csrfToken);
                const methodField = document.createElement('input');
                methodField.type = 'hidden';
                methodField.name = '_method';
                methodField.value = 'DELETE';
                form.appendChild(methodField);
                document.body.appendChild(form);
                form.submit();
            }
        });
    }

    const duplicateBtn = document.querySelector('.duplicate-program-btn');
    if (duplicateBtn) {
        duplicateBtn.addEventListener('click', function() {
            if (confirm('Duplicate this program?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '{{ route("doctor.hep.index") }}/' + {{ $program->id }} + '/duplicate';
                const csrfToken = document.createElement('input');
                csrfToken.type = 'hidden';
                csrfToken.name = '_token';
                csrfToken.value = document.querySelector('meta[name="csrf-token"]').content;
                form.appendChild(csrfToken);
                document.body.appendChild(form);
                form.submit();
            }
        });
    }
});
</script>
@endpush
