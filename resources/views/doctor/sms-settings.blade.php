@extends('master')

@section('title', 'SMS Provider Settings')

@push('styles')
<link rel="stylesheet" href="{{ asset('js/sms-settings.css') }}">
<style>
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

    .provider-badge.inherited-doctor {
        background: #cce5ff;
        color: #004085;
        border: 1px solid #b8daff;
    }

    .provider-badge.inherited-hospital {
        background: #fff3cd;
        color: #856404;
        border: 1px solid #ffeeba;
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
</style>
@endpush

@section('content')
<div class="dashboard-container">
    <div class="container">
        <!-- Dashboard Header -->
        <div class="dashboard-header">
            <h2><i class="fas fa-sms me-2"></i>SMS Provider Settings</h2>
            <p>Configure your SMS provider for appointment notifications</p>
        </div>

        <!-- Success Message -->
        @if(session('success'))
            <div class="alert alert-success mb-4">
                <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            </div>
        @endif

        <!-- Error Messages -->
        @if($errors->any())
            <div class="alert alert-danger mb-4">
                <i class="fas fa-exclamation-circle me-2"></i>
                <ul class="mb-0">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form id="smsSettingsForm">
            @csrf
            @method('PUT')

            <!-- Current Provider Status -->
            <div class="table-card">
                <h6 class="mb-4"><i class="fas fa-info-circle me-2"></i>Current SMS Provider Status</h6>

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
                    <span class="hierarchy-arrow">→</span>
                    <div class="hierarchy-level {{ $effectiveProvider['source'] === 'doctor' ? 'active' : 'inactive' }}">
                        <span class="text-xs text-muted mb-1">DOCTOR (YOU)</span>
                        <strong>{{ $doctorProvider ?? 'Inherits' }}</strong>
                    </div>
                </div>

                <!-- Effective Provider Display -->
                <div class="alert {{ $doctorProvider ? 'alert-success' : 'alert-info' }} d-flex align-items-center">
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
                            @if($effectiveProvider['source'] !== 'doctor' && $effectiveProvider['source'] !== 'system')
                                ({{ $effectiveProvider['inherited_from'] }})
                            @endif
                        </small>
                    </div>
                </div>
            </div>

            <!-- Provider Selection Form -->
            <div class="table-card">
                <h6 class="mb-4"><i class="fas fa-cog me-2"></i>Configure Your SMS Provider</h6>

                <div class="row g-4">
                    <div class="col-12">
                        <label for="sms_provider" class="form-label fw-bold">
                            Select SMS Provider
                            <span class="text-muted fw-normal">(Leave empty to inherit from hospital/system)</span>
                        </label>
                        <select id="sms_provider" name="sms_provider" class="form-select">
                            <option value="">-- Inherit from Hospital/System --</option>
                            <option value="twilio" {{ $doctorProvider === 'twilio' ? 'selected' : '' }}>
                                Twilio
                            </option>
                            <option value="plivo" {{ $doctorProvider === 'plivo' ? 'selected' : '' }}>
                                Plivo
                            </option>
                            <option value="messagebird" {{ $doctorProvider === 'messagebird' ? 'selected' : '' }}>
                                MessageBird
                            </option>
                            <option value="unifonic" {{ $doctorProvider === 'unifonic' ? 'selected' : '' }}>
                                Unifonic
                            </option>
                            <option value="smsgatewayhub" {{ $doctorProvider === 'smsgatewayhub' ? 'selected' : '' }}>
                                SMS Gateway Hub
                            </option>
                            <option value="log" {{ $doctorProvider === 'log' ? 'selected' : '' }}>
                                Log Only (Development)
                            </option>
                        </select>
                        <div class="form-text">
                            Select a specific provider to override hospital/system settings, or leave empty to inherit.
                        </div>
                    </div>
                </div>

                <!-- Provider Info Box -->
                <div id="providerInfo" class="alert alert-info d-none">
                    <h6 class="alert-heading">
                        <i class="fas fa-info-circle me-2"></i>Provider Information
                    </h6>
                    <p id="providerDescription" class="mb-0"></p>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-8">
                    <!-- Parent Settings Info -->
                    <div class="table-card">
                        <h6 class="mb-3"><i class="fas fa-building me-2"></i>Hospital Settings</h6>
                        <div class="mb-3">
                            <strong>Hospital:</strong> {{ $hospitalName ?? 'N/A' }}
                        </div>
                        <div class="mb-3">
                            <strong>Hospital Provider:</strong>
                            @if($hospitalProvider)
                                <span class="provider-badge custom">{{ ucfirst($hospitalProvider) }}</span>
                            @else
                                <span class="provider-badge inherited">Inherits from System</span>
                            @endif
                        </div>
                        <hr>
                        <h6 class="mb-3"><i class="fas fa-globe me-2"></i>System Settings</h6>
                        <div>
                            <strong>System Provider:</strong>
                            @if($systemProvider)
                                <span class="provider-badge inherited-system">{{ ucfirst($systemProvider) }}</span>
                            @else
                                <span class="badge bg-secondary">Not Configured</span>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <!-- Help Card -->
                    <div class="table-card bg-light">
                        <h6 class="mb-3"><i class="fas fa-question-circle me-2"></i>How It Works</h6>
                        <ul class="mb-0 small">
                            <li class="mb-2">
                                <strong>Doctor Override:</strong> Your personal setting takes highest priority
                            </li>
                            <li class="mb-2">
                                <strong>Hospital Default:</strong> Used when you don't set a personal provider
                            </li>
                            <li class="mb-2">
                                <strong>System Default:</strong> Fallback when hospital doesn't specify
                            </li>
                            <li class="mb-2">
                                <strong>Log Mode:</strong> Development option that logs SMS without sending
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="d-flex gap-3">
                <button type="submit" class="btn btn-primary" id="saveBtn">
                    <i class="fas fa-save me-2"></i>Save Settings
                </button>
                @if($doctorProvider)
                    <button type="button" class="btn btn-outline-warning" id="revertBtn">
                        <i class="fas fa-undo me-2"></i>Revert to Inherited
                    </button>
                @endif
            </div>
        </form>

        <!-- Success/Error Messages -->
        <div id="messageContainer" class="mt-4 d-none"></div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/sms-settings.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize doctor SMS settings
    SmsSettings.init({
        formId: 'smsSettingsForm',
        saveUrl: '/api/doctor/sms-settings',
        revertUrl: '/api/doctor/sms-settings'
    });
});
</script>
@endpush