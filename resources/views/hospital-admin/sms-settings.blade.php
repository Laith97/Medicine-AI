@extends('layouts.app')

@section('title', 'Hospital SMS Provider Settings')

@push('styles')
<link rel="stylesheet" href="{{ asset('js/sms-settings.css') }}">
<style>
    .sms-settings-card {
        background: #fff;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
        padding: 2rem;
        margin-bottom: 2rem;
    }

    .provider-badge {
        display: inline-flex;
        align-items: center;
        padding: 0.5rem 1rem;
        border-radius: 50px;
        font-size: 0.875rem;
        font-weight: 500;
    }

    .provider-badge.custom {
        background: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }

    .provider-badge.inherited {
        background: #e2e3e5;
        color: #383d41;
        border: 1px solid #d6d8db;
    }

    .provider-badge.inherited-system {
        background: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }

    .hierarchy-indicator {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 1rem;
        background: #f8f9fa;
        border-radius: 8px;
        margin-bottom: 1.5rem;
    }

    .hierarchy-level {
        display: flex;
        flex-direction: column;
        align-items: center;
        padding: 0.75rem 1.5rem;
        border-radius: 8px;
        background: #fff;
        border: 2px solid #e9ecef;
        min-width: 120px;
    }

    .hierarchy-level.active {
        border-color: #28a745;
        background: #d4edda;
    }

    .hierarchy-level.inactive {
        opacity: 0.6;
    }

    .hierarchy-arrow {
        color: #6c757d;
        font-size: 1.5rem;
    }

    .provider-logo {
        width: 32px;
        height: 32px;
        border-radius: 6px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        font-size: 0.75rem;
        color: #fff;
    }

    .twilio { background: #F22F46; }
    .plivo { background: #00A8E8; }
    .messagebird { background: #1496FF; }
    .unifonic { background: #4CAF50; }
    .smsgatewayhub { background: #FF9800; }
    .log { background: #6c757d; }

    .override-count {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 24px;
        height: 24px;
        border-radius: 50%;
        font-size: 0.75rem;
        font-weight: bold;
    }

    .override-count.has-overrides {
        background: #ffc107;
        color: #212529;
    }

    .override-count.no-overrides {
        background: #e9ecef;
        color: #6c757d;
    }

    .doctor-override-row {
        background: #fff3cd;
    }

    .doctor-override-row td {
        font-style: italic;
    }
</style>
@endpush

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <!-- Page Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-1 text-gray-800">
                        <i class="fas fa-sms mr-2"></i>SMS Provider Settings
                    </h1>
                    <p class="text-muted mb-0">Configure hospital-wide SMS provider and view doctor overrides</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <!-- Current Provider Status -->
            <div class="sms-settings-card">
                <h5 class="mb-4">
                    <i class="fas fa-info-circle mr-2"></i>Current SMS Provider Status
                </h5>

                <!-- Hierarchy Visualization -->
                <div class="hierarchy-indicator">
                    <div class="hierarchy-level {{ $effectiveProvider['source'] === 'system' ? 'active' : 'inactive' }}">
                        <span class="text-xs text-muted mb-1">SYSTEM</span>
                        <strong>{{ $systemProvider ?? 'Not Set' }}</strong>
                    </div>
                    <span class="hierarchy-arrow">→</span>
                    <div class="hierarchy-level {{ $effectiveProvider['source'] === 'hospital' ? 'active' : 'inactive' }}">
                        <span class="text-xs text-muted mb-1">HOSPITAL</span>
                        <strong>{{ $hospitalProvider ?? 'Inherits' }}</strong>
                    </div>
                </div>

                <!-- Effective Provider Display -->
                <div class="alert {{ $hospitalProvider ? 'alert-success' : 'alert-info' }} d-flex align-items-center">
                    <div class="me-3">
                        @if($effectiveProvider['provider'])
                            @php
                                $providerClass = strtolower($effectiveProvider['provider']);
                            @endphp
                            <div class="provider-logo {{ $providerClass }}">
                                {{ strtoupper(substr($effectiveProvider['provider'], 0, 2)) }}
                            </div>
                        @else
                            <i class="fas fa-globe fa-2x text-muted"></i>
                        @endif
                    </div>
                    <div>
                        <strong>Effective Provider:</strong> {{ $effectiveProvider['provider'] ?? 'System Default' }}
                        <br>
                        <small class="text-muted">
                            Source: {{ ucfirst($effectiveProvider['source']) }}
                            @if($effectiveProvider['source'] === 'system')
                                (System Default)
                            @endif
                        </small>
                    </div>
                </div>
            </div>

            <!-- Provider Selection Form -->
            <div class="sms-settings-card">
                <h5 class="mb-4">
                    <i class="fas fa-cog mr-2"></i>Configure Hospital SMS Provider
                </h5>

                <form id="smsSettingsForm">
                    @csrf
                    @method('PUT')

                    <div class="mb-4">
                        <label for="sms_provider" class="form-label fw-bold">
                            Select Hospital SMS Provider
                            <span class="text-muted fw-normal">(Leave empty to inherit from system)</span>
                        </label>
                        <select id="sms_provider" name="sms_provider" class="form-select">
                            <option value="">-- Inherit from System --</option>
                            <option value="twilio" {{ $hospitalProvider === 'twilio' ? 'selected' : '' }}>
                                Twilio
                            </option>
                            <option value="plivo" {{ $hospitalProvider === 'plivo' ? 'selected' : '' }}>
                                Plivo
                            </option>
                            <option value="messagebird" {{ $hospitalProvider === 'messagebird' ? 'selected' : '' }}>
                                MessageBird
                            </option>
                            <option value="unifonic" {{ $hospitalProvider === 'unifonic' ? 'selected' : '' }}>
                                Unifonic
                            </option>
                            <option value="smsgatewayhub" {{ $hospitalProvider === 'smsgatewayhub' ? 'selected' : '' }}>
                                SMS Gateway Hub
                            </option>
                            <option value="log" {{ $hospitalProvider === 'log' ? 'selected' : '' }}>
                                Log Only (Development)
                            </option>
                        </select>
                        <div class="form-text">
                            Select a specific provider to set as hospital default, or leave empty to inherit from system settings.
                        </div>
                    </div>

                    <!-- Provider Info Box -->
                    <div id="providerInfo" class="alert alert-info d-none">
                        <h6 class="alert-heading">
                            <i class="fas fa-info-circle mr-2"></i>Provider Information
                        </h6>
                        <p id="providerDescription" class="mb-0"></p>
                    </div>

                    <!-- Action Buttons -->
                    <div class="d-flex gap-3">
                        <button type="submit" class="btn btn-primary" id="saveBtn">
                            <i class="fas fa-save mr-2"></i>Save Settings
                        </button>
                        @if($hospitalProvider)
                            <button type="button" class="btn btn-outline-warning" id="revertBtn">
                                <i class="fas fa-undo mr-2"></i>Revert to System Default
                            </button>
                        @endif
                    </div>
                </form>

                <!-- Success/Error Messages -->
                <div id="messageContainer" class="mt-4 d-none"></div>
            </div>

            <!-- Doctor Overrides Table -->
            <div class="sms-settings-card">
                <h5 class="mb-4">
                    <i class="fas fa-users mr-2"></i>Doctor Provider Overrides
                    @if($doctorOverrides->count() > 0)
                        <span class="override-count has-overrides ml-2">{{ $doctorOverrides->count() }}</span>
                    @else
                        <span class="override-count no-overrides ml-2">0</span>
                    @endif
                </h5>

                @if($doctorOverrides->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Doctor</th>
                                    <th>Custom Provider</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($doctorOverrides as $doctor)
                                    <tr class="doctor-override-row">
                                        <td>
                                            <strong>Dr. {{ $doctor->user->name }}</strong>
                                            <br>
                                            <small class="text-muted">{{ $doctor->specialty->name ?? 'General' }}</small>
                                        </td>
                                        <td>
                                            <span class="provider-badge custom">{{ ucfirst($doctor->sms_provider) }}</span>
                                        </td>
                                        <td>
                                            <span class="badge bg-warning">Custom Override</span>
                                        </td>
                                        <td>
                                            <a href="{{ route('hospital-admin.doctors.show', $doctor) }}" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-eye mr-1"></i>View
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-4 text-muted">
                        <i class="fas fa-check-circle fa-3x mb-3 text-success"></i>
                        <p class="mb-0">No doctors have custom SMS provider overrides.</p>
                        <p class="small">All doctors in this hospital will use the hospital's SMS provider.</p>
                    </div>
                @endif
            </div>
        </div>

        <div class="col-lg-4">
            <!-- System Settings Info -->
            <div class="sms-settings-card">
                <h6 class="mb-3">
                    <i class="fas fa-globe mr-2"></i>System Settings
                </h6>
                <div>
                    <strong>System Default Provider:</strong>
                    @if($systemProvider)
                        <span class="provider-badge inherited-system">{{ ucfirst($systemProvider) }}</span>
                    @else
                        <span class="badge bg-secondary">Not Configured</span>
                    @endif
                </div>
                <hr>
                <h6 class="mb-3">
                    <i class="fas fa-chart-pie mr-2"></i>Statistics
                </h6>
                <div class="row">
                    <div class="col-6">
                        <div class="text-center">
                            <div class="h4 mb-0">{{ $totalDoctors }}</div>
                            <small class="text-muted">Total Doctors</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="text-center">
                            <div class="h4 mb-0">{{ $doctorOverrides->count() }}</div>
                            <small class="text-muted">With Overrides</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Help Card -->
            <div class="sms-settings-card bg-light">
                <h6 class="mb-3">
                    <i class="fas fa-question-circle mr-2"></i>How It Works
                </h6>
                <ul class="mb-0 small">
                    <li class="mb-2">
                        <strong>Hospital Default:</strong> Sets the SMS provider for all doctors in this hospital
                    </li>
                    <li class="mb-2">
                        <strong>Doctor Override:</strong> Individual doctors can set their own provider
                    </li>
                    <li class="mb-2">
                        <strong>Priority Order:</strong> Doctor Override → Hospital Default → System Default
                    </li>
                    <li class="mb-2">
                        <strong>Log Mode:</strong> Development option that logs SMS without sending
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="{{ asset('js/sms-settings.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize hospital admin SMS settings
    SmsSettings.init({
        formId: 'smsSettingsForm',
        saveUrl: '/api/hospital/{{ $hospitalId }}/sms-settings',
        revertUrl: '/api/hospital/{{ $hospitalId }}/sms-settings'
    });
});
</script>
@endpush