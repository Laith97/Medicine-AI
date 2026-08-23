@extends('master')

@section('title', 'Clinical Early Warning System')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/doctor-dashboard.css') }}">
<link rel="stylesheet" href="{{ asset('css/cases-overview.css') }}">
<style>
    .clinical-card { border-radius: 12px; overflow: hidden; }
    .clinical-card .card-header { background: #fff; border-bottom: 1px solid #e9ecef; padding: 0.85rem 1.25rem; }
    .clinical-placeholder { padding: 2rem; text-align: center; background: #fff; border: 1px solid #eef0f3; border-radius: 12px; }
</style>
@endpush

@section('content')
<div class="container-fluid" style="background-color: var(--bg-secondary, #f8f9fa);">
    <div class="container py-4">
        <div class="dashboard-header cases-header-compact">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2><i class="fas fa-heartbeat me-2"></i>Clinical Monitoring</h2>
                    <p>Real-time patient risk assessment and early warning system</p>
                </div>
                <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-3 py-2 rounded-pill fw-semibold d-none d-md-inline-flex">
                    <span class="bg-success rounded-circle d-inline-block me-2" style="width:8px;height:8px;"></span> LIVE
                </span>
            </div>
        </div>

        <div class="row g-3">
            @if($patientId)
            <div class="col-12">
                <div class="card border-0 shadow-sm clinical-card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 fw-semibold"><i class="fas fa-user-injured me-2 text-primary"></i>Patient Dashboard</h6>
                        <span class="doctor-badge doctor-badge-primary">ID #{{ $patientId }}</span>
                    </div>
                    <div id="clinical-dashboard-root" data-patient-id="{{ $patientId }}" class="p-3">
                        <div class="clinical-placeholder">
                            <div class="spinner-border text-primary mb-3" role="status" style="width:1.5rem;height:1.5rem;"></div>
                            <p class="text-muted small mb-0">Loading Patient Dashboard...</p>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <div class="col-12 col-xl-8">
                <div class="card border-0 shadow-sm clinical-card">
                    <div class="card-header">
                        <h6 class="mb-0 fw-semibold"><i class="fas fa-bell me-2 text-warning"></i>Alert Management</h6>
                    </div>
                    <div id="alert-management-root" class="p-3">
                        <div class="clinical-placeholder">
                            <div class="spinner-border text-warning mb-3" role="status" style="width:1.5rem;height:1.5rem;"></div>
                            <p class="text-muted small mb-0">Loading Alert Manager...</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-xl-4">
                <div class="card border-0 shadow-sm clinical-card">
                    <div class="card-header">
                        <h6 class="mb-0 fw-semibold"><i class="fas fa-sliders-h me-2 text-secondary"></i>Configuration</h6>
                    </div>
                    <div id="clinical-config-root" class="p-3">
                        <div class="clinical-placeholder">
                            <div class="spinner-border text-secondary mb-3" role="status" style="width:1.5rem;height:1.5rem;"></div>
                            <p class="text-muted small mb-0">Loading Configuration...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
