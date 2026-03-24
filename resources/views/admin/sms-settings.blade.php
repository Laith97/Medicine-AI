@extends('layouts.admin')

@section('title', 'SMS Provider Settings - System Admin')

@push('styles')
<style>
    .sms-settings-card {
        background: #fff;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
        padding: 1.5rem;
        margin-bottom: 1.5rem;
    }

    .provider-badge {
        display: inline-flex;
        align-items: center;
        padding: 0.35rem 0.75rem;
        border-radius: 50px;
        font-size: 0.8rem;
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

    .provider-badge.inherited-hospital {
        background: #fff3cd;
        color: #856404;
        border: 1px solid #ffeeba;
    }

    .provider-logo {
        width: 28px;
        height: 28px;
        border-radius: 6px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        font-size: 0.65rem;
        color: #fff;
        margin-right: 0.5rem;
    }

    .twilio { background: #F22F46; }
    .plivo { background: #00A8E8; }
    .messagebird { background: #1496FF; }
    .unifonic { background: #4CAF50; }
    .smsgatewayhub { background: #FF9800; }
    .log { background: #6c757d; }

    .hierarchy-overview {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 1rem;
        padding: 1.5rem;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 10px;
        color: #fff;
        margin-bottom: 2rem;
    }

    .hierarchy-node {
        display: flex;
        flex-direction: column;
        align-items: center;
        padding: 1rem 1.5rem;
        background: rgba(255, 255, 255, 0.15);
        border-radius: 8px;
        min-width: 140px;
    }

    .hierarchy-node.active {
        background: rgba(255, 255, 255, 0.3);
        border: 2px solid #fff;
    }

    .hierarchy-node-label {
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 1px;
        opacity: 0.8;
        margin-bottom: 0.25rem;
    }

    .hierarchy-node-value {
        font-size: 1.1rem;
        font-weight: bold;
    }

    .hierarchy-arrow {
        font-size: 1.5rem;
        opacity: 0.7;
    }

    .hospital-card {
        border: 1px solid #e9ecef;
        border-radius: 8px;
        padding: 1rem;
        margin-bottom: 1rem;
        transition: all 0.2s ease;
    }

    .hospital-card:hover {
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    .hospital-card.has-overrides {
        border-left: 4px solid #ffc107;
    }

    .hospital-card.no-overrides {
        border-left: 4px solid #28a745;
    }

    .hospital-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 0.75rem;
    }

    .hospital-name {
        font-weight: 600;
        font-size: 1.1rem;
        color: #2c3e50;
    }

    .hospital-meta {
        font-size: 0.85rem;
        color: #6c757d;
    }

    .override-indicator {
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        padding: 0.25rem 0.5rem;
        border-radius: 4px;
        font-size: 0.75rem;
        font-weight: 500;
    }

    .override-indicator.yes {
        background: #fff3cd;
        color: #856404;
    }

    .override-indicator.no {
        background: #d4edda;
        color: #155724;
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
        margin-bottom: 2rem;
    }

    .stat-card {
        background: #fff;
        border-radius: 8px;
        padding: 1.25rem;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
        text-align: center;
    }

    .stat-value {
        font-size: 2rem;
        font-weight: 700;
        color: #2c3e50;
    }

    .stat-label {
        font-size: 0.85rem;
        color: #6c757d;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .stat-card.primary { border-top: 4px solid #667eea; }
    .stat-card.success { border-top: 4px solid #28a745; }
    .stat-card.warning { border-top: 4px solid #ffc107; }
    .stat-card.info { border-top: 4px solid #17a2b8; }

    .expand-btn {
        cursor: pointer;
        color: #667eea;
        font-size: 0.85rem;
    }

    .expand-btn:hover {
        text-decoration: underline;
    }

    .doctors-list {
        margin-top: 1rem;
        padding-top: 1rem;
        border-top: 1px solid #e9ecef;
    }

    .doctor-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.5rem 0;
        border-bottom: 1px solid #f0f0f0;
    }

    .doctor-item:last-child {
        border-bottom: none;
    }

    .provider-configured {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem 1rem;
        border-radius: 8px;
        background: #d4edda;
        color: #155724;
        font-weight: 500;
    }

    .provider-not-configured {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem 1rem;
        border-radius: 8px;
        background: #f8d7da;
        color: #721c24;
        font-weight: 500;
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
                        <i class="fas fa-sms mr-2"></i>SMS Provider Settings - System Overview
                    </h1>
                    <p class="text-muted mb-0">Manage system-wide SMS provider and view hospital/doctor hierarchy</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Overview -->
    <div class="stats-grid">
        <div class="stat-card primary">
            <div class="stat-value">{{ $totalHospitals }}</div>
            <div class="stat-label">Total Hospitals</div>
        </div>
        <div class="stat-card success">
            <div class="stat-value">{{ $hospitalsWithCustomProvider }}</div>
            <div class="stat-label">With Custom Provider</div>
        </div>
        <div class="stat-card warning">
            <div class="stat-value">{{ $totalDoctorsWithOverrides }}</div>
            <div class="stat-label">Doctor Overrides</div>
        </div>
        <div class="stat-card info">
            <div class="stat-value">{{ $systemProvider ?? 'Not Set' }}</div>
            <div class="stat-label">System Default</div>
        </div>
    </div>

    <!-- System Default Provider Section -->
    <div class="row">
        <div class="col-lg-4">
            <div class="sms-settings-card">
                <h5 class="mb-4">
                    <i class="fas fa-globe mr-2"></i>System Default Provider
                </h5>

                <form id="systemProviderForm">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label for="system_sms_provider" class="form-label fw-bold">Select System Provider</label>
                        <select id="system_sms_provider" name="sms_provider" class="form-select">
                            <option value="">-- No Default (Require Hospital/Doctor Override) --</option>
                            <option value="twilio" {{ $systemProvider === 'twilio' ? 'selected' : '' }}>
                                Twilio
                            </option>
                            <option value="plivo" {{ $systemProvider === 'plivo' ? 'selected' : '' }}>
                                Plivo
                            </option>
                            <option value="messagebird" {{ $systemProvider === 'messagebird' ? 'selected' : '' }}>
                                MessageBird
                            </option>
                            <option value="unifonic" {{ $systemProvider === 'unifonic' ? 'selected' : '' }}>
                                Unifonic
                            </option>
                            <option value="smsgatewayhub" {{ $systemProvider === 'smsgatewayhub' ? 'selected' : '' }}>
                                SMS Gateway Hub
                            </option>
                            <option value="log" {{ $systemProvider === 'log' ? 'selected' : '' }}>
                                Log Only (Development)
                            </option>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary w-100" id="saveSystemBtn">
                        <i class="fas fa-save mr-2"></i>Update System Default
                    </button>
                </form>

                <!-- Success/Error Messages -->
                <div id="systemMessageContainer" class="mt-3 d-none"></div>
            </div>

            <!-- Provider Configuration Status -->
            <div class="sms-settings-card">
                <h6 class="mb-3">
                    <i class="fas fa-cog mr-2"></i>Provider Configuration
                </h6>
                <div class="list-group list-group-flush">
                    @foreach($providers as $key => $provider)
                        <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <div class="d-flex align-items-center">
                                @php $providerClass = strtolower($key); @endphp
                                <div class="provider-logo {{ $providerClass }}">{{ strtoupper(substr($key, 0, 2)) }}</div>
                                <span>{{ $provider['name'] }}</span>
                            </div>
                            @if($provider['configured'])
                                <span class="provider-configured">
                                    <i class="fas fa-check-circle"></i> Configured
                                </span>
                            @else
                                <span class="provider-not-configured">
                                    <i class="fas fa-times-circle"></i> Not Configured
                                </span>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <!-- Hierarchy Overview -->
            <div class="sms-settings-card">
                <h5 class="mb-4">
                    <i class="fas fa-sitemap mr-2"></i>SMS Provider Hierarchy
                </h5>

                <div class="hierarchy-overview">
                    <div class="hierarchy-node">
                        <div class="hierarchy-node-label">System Default</div>
                        <div class="hierarchy-node-value">{{ $systemProvider ?? 'Not Set' }}</div>
                    </div>
                    <span class="hierarchy-arrow">→</span>
                    <div class="hierarchy-node">
                        <div class="hierarchy-node-label">Hospital</div>
                        <div class="hierarchy-node-value">Custom or Inherit</div>
                    </div>
                    <span class="hierarchy-arrow">→</span>
                    <div class="hierarchy-node">
                        <div class="hierarchy-node-label">Doctor</div>
                        <div class="hierarchy-node-value">Custom or Inherit</div>
                    </div>
                </div>

                <div class="alert alert-info mb-0">
                    <i class="fas fa-info-circle mr-2"></i>
                    <strong>Priority Order:</strong> Doctor Override → Hospital Default → System Default
                    <br>
                    <small>Each level can override the level above it, or inherit from it.</small>
                </div>
            </div>

            <!-- Hospitals List -->
            <div class="sms-settings-card">
                <h5 class="mb-4">
                    <i class="fas fa-building mr-2"></i>Hospitals & Provider Settings
                </h5>

                @if($hospitals->count() > 0)
                    <div id="hospitalsList">
                        @foreach($hospitals as $hospital)
                            <div class="hospital-card {{ $hospital['has_custom_provider'] || $hospital['doctor_overrides'] > 0 ? 'has-overrides' : 'no-overrides' }}">
                                <div class="hospital-header">
                                    <div>
                                        <div class="hospital-name">{{ $hospital['name'] }}</div>
                                        <div class="hospital-meta">
                                            {{ $hospital['doctor_count'] }} doctors
                                            @if($hospital['doctor_overrides'] > 0)
                                                · {{ $hospital['doctor_overrides'] }} with custom overrides
                                            @endif
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        @if($hospital['has_custom_provider'])
                                            <span class="provider-badge custom">
                                                {{ ucfirst($hospital['provider']) }}
                                            </span>
                                        @else
                                            <span class="provider-badge inherited">
                                                Inherits from System
                                            </span>
                                        @endif
                                    </div>
                                </div>

                                @if($hospital['doctor_overrides'] > 0)
                                    <div class="expand-btn" onclick="toggleDoctors({{ $hospital['id'] }})">
                                        <i class="fas fa-chevron-down mr-1"></i>
                                        Show {{ $hospital['doctor_overrides'] }} doctor(s) with custom overrides
                                    </div>
                                    <div id="doctors-{{ $hospital['id'] }}" class="doctors-list d-none">
                                        @foreach($hospital['doctors_with_overrides'] as $doctor)
                                            <div class="doctor-item">
                                                <div>
                                                    <strong>Dr. {{ $doctor['name'] }}</strong>
                                                    <span class="text-muted"> · {{ $doctor['specialty'] ?? 'General' }}</span>
                                                </div>
                                                <span class="provider-badge custom">{{ ucfirst($doctor['sms_provider']) }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-4 text-muted">
                        <i class="fas fa-building fa-3x mb-3 text-secondary"></i>
                        <p class="mb-0">No hospitals registered yet.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="{{ asset('js/sms-settings.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize system admin SMS settings
    SmsSettings.init({
        formId: 'systemProviderForm',
        saveUrl: '/api/admin/sms-settings',
        saveBtnId: 'saveSystemBtn',
        messageContainerId: 'systemMessageContainer',
        showProviderInfo: false
    });
});

// Toggle doctors list (used inline in HTML)
function toggleDoctors(hospitalId) {
    const doctorsList = document.getElementById('doctors-' + hospitalId);
    if (!doctorsList) return;

    const expandBtn = doctorsList.previousElementSibling;

    if (doctorsList.classList.contains('d-none')) {
        doctorsList.classList.remove('d-none');
        if (expandBtn && expandBtn.classList.contains('expand-btn')) {
            expandBtn.innerHTML = '<i class="fas fa-chevron-up mr-1"></i>Hide doctor overrides';
        }
    } else {
        doctorsList.classList.add('d-none');
        if (expandBtn && expandBtn.classList.contains('expand-btn')) {
            const match = expandBtn.textContent.match(/\d+/);
            const count = match ? match[0] : '0';
            expandBtn.innerHTML = '<i class="fas fa-chevron-down mr-1"></i>Show ' + count + ' doctor(s) with custom overrides';
        }
    }
}
</script>
@endpush
