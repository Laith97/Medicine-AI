@extends('master')

@section('title', 'Clinical Early Warning System')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/doctor-dashboard.css') }}">
<link rel="stylesheet" href="{{ asset('css/cases-overview.css') }}">
<style>
    /* Clinical Monitoring — premium compact aligned with cases-overview + doctor-dashboard */
    .clinical-stats .stats-card--compact {
        position: relative;
        overflow: hidden;
    }
    .clinical-stats .stats-card--compact::before {
        content: '';
        position: absolute;
        top: 0; left: 0; right: 0;
        height: 3px;
        opacity: 0.95;
    }
    .clinical-stats .stats-card--compact.stat-monitored::before { background: linear-gradient(90deg, #0ea5e9 0%, #2563eb 100%); }
    .clinical-stats .stats-card--compact.stat-alerts::before { background: linear-gradient(90deg, #f59e0b 0%, #ef4444 100%); }
    .clinical-stats .stats-card--compact.stat-thresholds::before { background: linear-gradient(90deg, #10b981 0%, #059669 100%); }

    .clinical-card {
        border-radius: 12px !important;
        overflow: hidden;
        border: 1px solid #eef0f3 !important;
        background: #ffffff;
        box-shadow: 0 6px 20px rgba(44,62,80,0.05), 0 1px 6px rgba(44,62,80,0.04) !important;
        transition: transform 0.25s ease, box-shadow 0.25s ease, border-color 0.2s ease;
        height: 100%;
    }
    .clinical-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 12px 28px rgba(44,62,80,0.08), 0 4px 12px rgba(44,62,80,0.05) !important;
        border-color: #e6e8eb !important;
    }
    .clinical-card__head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        padding: 0.85rem 1.15rem;
        background: #ffffff;
        border-bottom: 1px solid #f1f5f9;
        flex-wrap: wrap;
    }
    .clinical-card__head-left {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        min-width: 0;
    }
    .clinical-icon-box {
        width: 38px;
        height: 38px;
        border-radius: 9px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.95rem;
        flex-shrink: 0;
        border: 1px solid;
    }
    .clinical-icon-box.icon-patient {
        background: #f8fafc;
        color: #1e40af;
        border-color: #dbeafe;
    }
    .clinical-icon-box.icon-alerts {
        background: #fffbeb;
        color: #b45309;
        border-color: #fde68a;
    }
    .clinical-icon-box.icon-config {
        background: #f8fafc;
        color: #475569;
        border-color: #e2e8f0;
    }
    .clinical-card__title {
        font-size: 0.92rem;
        font-weight: 700;
        color: #1e293b;
        letter-spacing: -0.01em;
        margin: 0;
        line-height: 1.2;
        white-space: nowrap;
    }
    .clinical-card__subtitle {
        font-size: 0.74rem;
        color: #94a3b8;
        font-weight: 500;
        margin: 2px 0 0;
        line-height: 1.2;
    }
    .clinical-card__meta {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        font-size: 0.72rem;
        font-weight: 600;
        padding: 0.32rem 0.65rem;
        border-radius: 99px;
        border: 1px solid #e2e8f0;
        background: #f8fafc;
        color: #475569;
        white-space: nowrap;
    }
    .clinical-card__body {
        padding: 0;
        background: #ffffff;
    }
    /* React root inner spacing — keep p-3 for mount but remove if React replaces */
    .clinical-card__body > [id$="-root"] { padding: 1rem; }
    .clinical-placeholder {
        padding: 2rem 1.25rem;
        text-align: center;
        background: #fcfdff;
        border: 1px dashed #e2e8f0;
        border-radius: 12px;
        margin: 1rem;
    }
    .clinical-placeholder .clinical-placeholder-icon {
        width: 42px;
        height: 42px;
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1rem;
        margin-bottom: 0.75rem;
        border: 1px solid;
    }
    .clinical-placeholder--patient .clinical-placeholder-icon { background: #eff6ff; color: #2563eb; border-color: #dbeafe; }
    .clinical-placeholder--alerts .clinical-placeholder-icon { background: #fffbeb; color: #d97706; border-color: #fde68a; }
    .clinical-placeholder--config .clinical-placeholder-icon { background: #f8fafc; color: #64748b; border-color: #e2e8f0; }

    .clinical-toolbar {
        background: #ffffff;
        border-bottom: 1px solid #eef2f7;
        padding: 0.65rem 1.15rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        flex-wrap: wrap;
    }
    .clinical-toolbar__hint {
        font-size: 0.76rem;
        color: #94a3b8;
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 0.35rem;
    }
    .clinical-table-container {
        background: #ffffff;
    }
    .pulse-dot {
        width: 8px; height: 8px;
        border-radius: 50%;
        background: #10b981;
        box-shadow: 0 0 0 0 rgba(16,185,129,0.6);
        animation: pulse-live 1.8s infinite;
        display: inline-block;
    }
    @keyframes pulse-live {
        0% { box-shadow: 0 0 0 0 rgba(16,185,129,0.55); }
        70% { box-shadow: 0 0 0 7px rgba(16,185,129,0); }
        100% { box-shadow: 0 0 0 0 rgba(16,185,129,0); }
    }
    @media (max-width: 768px) {
        .clinical-card__head { padding: 0.85rem 1rem; }
        .clinical-placeholder { margin: 0.75rem; padding: 1.5rem 1rem; }
    }
</style>
@endpush

@section('content')
<div class="container-fluid" style="background-color: var(--bg-secondary, #f8f9fa);">
    <div class="container py-4">
        {{-- Header — cases-header-compact premium, matches cases + appointments/show --}}
        <div class="dashboard-header cases-header-compact" style="position:relative; overflow:hidden;">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <h2><i class="fas fa-heartbeat me-2"></i>Clinical Monitoring</h2>
                    <p>Real-time patient risk assessment and early warning system</p>
                    <div class="d-flex align-items-center gap-2 mt-2" style="font-size:0.78rem; color:rgba(255,255,255,0.85);">
                        <span class="d-inline-flex align-items-center gap-2">
                            <span class="pulse-dot"></span> Telemetry active
                        </span>
                        <span class="d-none d-sm-inline" style="opacity:0.55;">·</span>
                        <span class="d-none d-sm-inline"><i class="far fa-clock me-1"></i>Updates every 60s</span>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-3 py-2 rounded-pill fw-semibold d-inline-flex align-items-center">
                        <span class="bg-success rounded-circle d-inline-block me-2" style="width:8px;height:8px;"></span> LIVE
                    </span>
                    <span class="badge bg-white bg-opacity-10 text-white border border-white border-opacity-25 px-3 py-2 rounded-pill fw-semibold d-none d-md-inline-flex" style="background:rgba(255,255,255,0.12) !important; color:#fff !important; border-color:rgba(255,255,255,0.25) !important;">
                        <i class="fas fa-shield-alt me-2"></i>NEWS2 · qSOFA · AKI
                    </span>
                </div>
            </div>
        </div>

        {{-- Stats row — compact like cases-stats-compact --}}
        <div class="row g-2 mb-3 cases-stats-compact clinical-stats">
            <div class="col-12 col-md-4">
                <div class="stats-card stats-card--compact stat-monitored">
                    <div class="stats-icon stats-icon--sm" style="background: linear-gradient(135deg, #0ea5e9 0%, #2563eb 100%);">
                        <i class="fas fa-procedures"></i>
                    </div>
                    <div class="stats-text">
                        <p class="stats-number">
                            @isset($totalMonitored) {{ $totalMonitored }} @else — @endisset
                        </p>
                        <p class="stats-label">Total Monitored</p>
                    </div>
                    <span class="ms-auto d-none d-lg-inline-flex align-items-center" style="font-size:0.70rem; font-weight:600; color:#64748b; background:#f1f5f9; border:1px solid #e2e8f0; border-radius:99px; padding:0.25rem 0.55rem;">
                        <span class="pulse-dot me-2" style="width:6px;height:6px;"></span> Live
                    </span>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="stats-card stats-card--compact stat-alerts">
                    <div class="stats-icon stats-icon--sm" style="background: linear-gradient(135deg, #f59e0b 0%, #ef4444 100%);">
                        <i class="fas fa-bell"></i>
                    </div>
                    <div class="stats-text">
                        <p class="stats-number">
                            @isset($activeAlerts) {{ $activeAlerts }} @else — @endisset
                        </p>
                        <p class="stats-label">Active Alerts</p>
                    </div>
                    <span class="ms-auto d-none d-lg-inline-flex" style="font-size:0.70rem; font-weight:700; color:#b45309; background:#fffbeb; border:1px solid #fde68a; border-radius:99px; padding:0.25rem 0.55rem;">
                        <i class="fas fa-exclamation-triangle me-1"></i> Triaged
                    </span>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="stats-card stats-card--compact stat-thresholds">
                    <div class="stats-icon stats-icon--sm" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
                        <i class="fas fa-sliders-h"></i>
                    </div>
                    <div class="stats-text">
                        <p class="stats-number">
                            @isset($configuredThresholds) {{ $configuredThresholds }} @else — @endisset
                        </p>
                        <p class="stats-label">Configured Thresholds</p>
                    </div>
                    <span class="ms-auto d-none d-lg-inline-flex" style="font-size:0.70rem; font-weight:600; color:#065f46; background:#ecfdf5; border:1px solid #a7f3d0; border-radius:99px; padding:0.25rem 0.55rem;">
                        <i class="fas fa-check-circle me-1"></i> Applied
                    </span>
                </div>
            </div>
        </div>

        {{-- Patient Dashboard — full width, premium clinical-card like info-card-premium --}}
        @if($patientId)
        <div class="mb-3">
            <div class="card border-0 shadow-sm clinical-card cases-panel">
                <div class="clinical-card__head">
                    <div class="clinical-card__head-left">
                        <div class="clinical-icon-box icon-patient">
                            <i class="fas fa-user-injured"></i>
                        </div>
                        <div>
                            <h6 class="clinical-card__title">Patient Dashboard</h6>
                            <p class="clinical-card__subtitle">Early Warning Scores · Trends · Insights</p>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <span class="clinical-card__meta">
                            <i class="fas fa-hashtag" style="font-size:0.70rem;"></i> ID #{{ $patientId }}
                        </span>
                        <span class="clinical-card__meta" style="background:#eff6ff; color:#1d4ed8; border-color:#dbeafe;">
                            <span class="pulse-dot" style="width:6px;height:6px;"></span> Monitoring
                        </span>
                    </div>
                </div>
                <div class="clinical-toolbar">
                    <span class="clinical-toolbar__hint">
                        <i class="fas fa-chart-line"></i> NEWS2 / qSOFA / AKI · Historical scores & predictive risk
                    </span>
                    <span class="clinical-toolbar__hint d-none d-md-inline-flex">
                        <i class="fas fa-sync-alt me-1"></i> Auto-refresh on vitals update
                    </span>
                </div>
                <div class="clinical-card__body clinical-table-container">
                    <div id="clinical-dashboard-root" data-patient-id="{{ $patientId }}">
                        <div class="clinical-placeholder clinical-placeholder--patient">
                            <div class="clinical-placeholder-icon"><i class="fas fa-heartbeat"></i></div>
                            <div class="spinner-border text-primary mb-2" role="status" style="width:1.5rem;height:1.5rem; border-width:0.18em;"></div>
                            <p class="text-muted small mb-1 fw-semibold" style="font-size:0.84rem; color:#334155 !important;">Loading Patient Dashboard…</p>
                            <p class="small mb-0" style="font-size:0.74rem; color:#94a3b8;">Fetching vitals, lab results and risk trends</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif

        {{-- Main grid — responsive col-12 col-xl-8 + col-12 col-xl-4, equal-height cards --}}
        <div class="row g-3 align-items-stretch">
            <div class="col-12 col-xl-8 d-flex">
                <div class="card border-0 shadow-sm clinical-card cases-panel w-100">
                    <div class="clinical-card__head">
                        <div class="clinical-card__head-left">
                            <div class="clinical-icon-box icon-alerts">
                                <i class="fas fa-bell"></i>
                            </div>
                            <div>
                                <h6 class="clinical-card__title">Alert Management</h6>
                                <p class="clinical-card__subtitle">Triggered & escalated alerts · Acknowledge queue</p>
                            </div>
                        </div>
                        <span class="clinical-card__meta d-none d-sm-inline-flex" style="background:#fffbeb; color:#92400e; border-color:#fde68a;">
                            <i class="fas fa-exclamation-circle"></i> Requires action
                        </span>
                    </div>
                    <div class="clinical-toolbar">
                        <span class="clinical-toolbar__hint">
                            <i class="fas fa-list-ul me-1"></i> Real-time alert feed
                        </span>
                        <span class="clinical-toolbar__hint d-none d-sm-inline-flex" style="font-size:0.72rem;">
                            Sort by severity · Age
                        </span>
                    </div>
                    <div class="clinical-card__body clinical-table-container">
                        <div id="alert-management-root">
                            <div class="clinical-placeholder clinical-placeholder--alerts">
                                <div class="clinical-placeholder-icon"><i class="fas fa-bell"></i></div>
                                <div class="spinner-border text-warning mb-2" role="status" style="width:1.5rem;height:1.5rem; border-width:0.18em;"></div>
                                <p class="text-muted small mb-1 fw-semibold" style="font-size:0.84rem; color:#334155 !important;">Loading Alert Manager…</p>
                                <p class="small mb-0" style="font-size:0.74rem; color:#94a3b8;">Pulling triggered & escalated alerts</p>
                            </div>
                        </div>
                    </div>
                    <div class="px-3 py-2 d-flex justify-content-between align-items-center" style="background:var(--gray-50, #f8f9fa); border-top:1px solid var(--border-light, rgba(0,0,0,0.05)); font-size:0.75rem; color:var(--gray-500, #6c757d); border-radius:0 0 12px 12px;">
                        <span><i class="fas fa-info-circle me-1"></i> Acknowledge or escalate from the list</span>
                        <span class="d-none d-sm-inline">Auto-polling · 30s</span>
                    </div>
                </div>
            </div>

            <div class="col-12 col-xl-4 d-flex">
                <div class="card border-0 shadow-sm clinical-card cases-panel w-100">
                    <div class="clinical-card__head">
                        <div class="clinical-card__head-left">
                            <div class="clinical-icon-box icon-config">
                                <i class="fas fa-sliders-h"></i>
                            </div>
                            <div>
                                <h6 class="clinical-card__title">Configuration</h6>
                                <p class="clinical-card__subtitle">Alert rules & thresholds</p>
                            </div>
                        </div>
                        <span class="clinical-card__meta d-none d-sm-inline-flex">
                            <i class="fas fa-cog"></i> Rules
                        </span>
                    </div>
                    <div class="clinical-toolbar">
                        <span class="clinical-toolbar__hint">
                            <i class="fas fa-shield-alt me-1"></i> ClinicalAlertRule
                        </span>
                        <span class="clinical-toolbar__hint d-none d-sm-inline-flex" style="font-size:0.72rem;">
                            Hospital scope
                        </span>
                    </div>
                    <div class="clinical-card__body clinical-table-container">
                        <div id="clinical-config-root">
                            <div class="clinical-placeholder clinical-placeholder--config">
                                <div class="clinical-placeholder-icon"><i class="fas fa-sliders-h"></i></div>
                                <div class="spinner-border text-secondary mb-2" role="status" style="width:1.5rem;height:1.5rem; border-width:0.18em;"></div>
                                <p class="text-muted small mb-1 fw-semibold" style="font-size:0.84rem; color:#334155 !important;">Loading Configuration…</p>
                                <p class="small mb-0" style="font-size:0.74rem; color:#94a3b8;">Fetching thresholds & rule conditions</p>
                            </div>
                        </div>
                    </div>
                    <div class="px-3 py-2" style="background:#f8fafc; border-top:1px solid #f1f5f9; font-size:0.73rem; color:#64748b; border-radius:0 0 12px 12px;">
                        <i class="fas fa-lock me-1"></i> Edits require <strong style="color:#334155;">doctor / admin</strong> role
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-3 d-flex flex-wrap gap-2 align-items-center justify-content-between" style="font-size:0.74rem; color:#94a3b8;">
            <span><i class="fas fa-database me-1"></i> Clinical indicators · EarlyWarningScore · ClinicalAlert pipeline</span>
            <span class="d-inline-flex align-items-center gap-2">
                <span class="d-inline-flex align-items-center gap-1"><span style="width:8px;height:8px;border-radius:50%;background:#10b981;display:inline-block;"></span> Low</span>
                <span class="d-inline-flex align-items-center gap-1"><span style="width:8px;height:8px;border-radius:50%;background:#f59e0b;display:inline-block;"></span> Medium</span>
                <span class="d-inline-flex align-items-center gap-1"><span style="width:8px;height:8px;border-radius:50%;background:#ef4444;display:inline-block;"></span> High</span>
                <span class="ms-1">· LHS: vitals/lab/note → stream → ProcessClinicalDataJob</span>
            </span>
        </div>
    </div>
</div>
@endsection
