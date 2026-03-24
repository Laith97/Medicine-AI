@extends('layouts.admin')

@section('title', 'Provider Configuration')

@push('styles')
<style>
    .provider-card {
        border: none;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07);
        border-radius: 12px;
        transition: transform 0.3s ease;
    }

    .provider-card:hover {
        transform: translateY(-2px);
    }

    .provider-logo {
        width: 60px;
        height: 60px;
        border-radius: 12px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1.5rem;
        font-weight: 600;
    }

    .provider-status {
        position: absolute;
        top: 10px;
        right: 10px;
        padding: 4px 8px;
        border-radius: 12px;
        font-size: 0.75rem;
        font-weight: 600;
    }

    .provider-status.enabled {
        background: rgba(25, 135, 84, 0.1);
        color: #198754;
    }

    .provider-status.disabled {
        background: rgba(108, 117, 125, 0.1);
        color: #6c757d;
    }

    .provider-status.configuring {
        background: rgba(255, 193, 7, 0.1);
        color: #ffc107;
    }

    .config-section {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 1rem;
        margin-bottom: 1rem;
    }

    .config-field {
        margin-bottom: 1rem;
    }

    .config-field label {
        font-weight: 600;
        color: #495057;
        margin-bottom: 0.5rem;
        display: block;
    }

    .config-field .form-text {
        font-size: 0.75rem;
        color: #6c757d;
    }

    .endpoint-test-result {
        padding: 0.5rem;
        border-radius: 4px;
        font-size: 0.875rem;
        margin-top: 0.5rem;
    }

    .endpoint-test-result.success {
        background: rgba(25, 135, 84, 0.1);
        color: #198754;
        border: 1px solid rgba(25, 135, 84, 0.2);
    }

    .endpoint-test-result.error {
        background: rgba(220, 53, 69, 0.1);
        color: #dc3545;
        border: 1px solid rgba(220, 53, 69, 0.2);
    }

    .rate-limit-display {
        background: white;
        border: 1px solid #dee2e6;
        border-radius: 6px;
        padding: 0.75rem;
        margin-bottom: 0.5rem;
    }

    .rate-limit-bar {
        height: 8px;
        background: #e9ecef;
        border-radius: 4px;
        overflow: hidden;
        margin-top: 0.5rem;
    }

    .rate-limit-fill {
        height: 100%;
        background: linear-gradient(90deg, #28a745 0%, #ffc107 70%, #dc3545 100%);
        transition: width 0.3s ease;
    }

    .feature-toggle {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0.75rem;
        background: white;
        border: 1px solid #dee2e6;
        border-radius: 6px;
        margin-bottom: 0.5rem;
    }

    .feature-toggle .form-check-input {
        width: 1.2em;
        height: 1.2em;
        margin-left: 0.5rem;
    }

    .modal-config-section {
        max-height: 60vh;
        overflow-y: auto;
    }

    .config-tabs .nav-link {
        border: none;
        border-radius: 6px 6px 0 0;
        color: #6c757d;
        margin-right: 4px;
    }

    .config-tabs .nav-link.active {
        background: #007bff;
        color: white;
    }

    .metric-card {
        background: white;
        border-radius: 12px;
        padding: 1.5rem;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07);
        border: none;
    }

    .metric-value {
        font-size: 2rem;
        font-weight: 700;
        color: #2c3e50;
    }

    .metric-label {
        font-size: 0.875rem;
        color: #6c757d;
        margin-top: 0.25rem;
    }
</style>
@endpush

@section('content')
<div class="provider-configuration">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <div>
                    <h2 class="mb-1">Provider Configuration</h2>
                    <p class="text-muted mb-0">Configure clearinghouse provider settings and endpoints</p>
                </div>
                <button type="button" class="btn btn-primary" onclick="showAddProviderModal()">
                    <i class="fas fa-plus me-2"></i>Add Provider
                </button>
            </div>
        </div>
    </div>

    <!-- Provider Metrics -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="metric-card">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <div class="metric-value text-primary" id="totalProviders">0</div>
                        <div class="metric-label">Total Providers</div>
                    </div>
                    <div class="text-primary">
                        <i class="fas fa-building fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="metric-card">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <div class="metric-value text-success" id="activeProviders">0</div>
                        <div class="metric-label">Active Providers</div>
                    </div>
                    <div class="text-success">
                        <i class="fas fa-check-circle fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="metric-card">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <div class="metric-value text-warning" id="configuringProviders">0</div>
                        <div class="metric-label">Configuring</div>
                    </div>
                    <div class="text-warning">
                        <i class="fas fa-cog fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="metric-card">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <div class="metric-value text-info" id="totalEndpoints">0</div>
                        <div class="metric-label">Configured Endpoints</div>
                    </div>
                    <div class="text-info">
                        <i class="fas fa-globe fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Providers Grid -->
    <div class="row" id="providersContainer">
        @forelse($providers ?? [] as $provider)
        <div class="col-lg-6 col-xl-4 mb-4">
            <div class="card provider-card position-relative">
                <div class="provider-status {{ strtolower($provider->status ?? 'disabled') }}">
                    {{ ucfirst($provider->status ?? 'Disabled') }}
                </div>
                <div class="card-body text-center">
                    <div class="provider-logo mb-3">
                        {{ substr($provider->name ?? 'P', 0, 1) }}
                    </div>
                    <h5 class="card-title mb-1">{{ $provider->name ?? 'Unknown Provider' }}</h5>
                    <p class="text-muted small mb-3">{{ $provider->description ?? 'No description available' }}</p>

                    <div class="row text-center mb-3">
                        <div class="col-6">
                            <div class="fw-bold text-primary">{{ $provider->active_accounts ?? 0 }}</div>
                            <small class="text-muted">Active Accounts</small>
                        </div>
                        <div class="col-6">
                            <div class="fw-bold text-success">{{ $provider->successful_submissions ?? 0 }}</div>
                            <small class="text-muted">Success Rate</small>
                        </div>
                    </div>

                    <div class="d-flex gap-2 justify-content-center">
                        <button type="button" class="btn btn-sm btn-outline-primary"
                                onclick="configureProvider({{ $provider->id }})">
                            <i class="fas fa-cog me-1"></i>Configure
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary"
                                onclick="testProvider({{ $provider->id }})">
                            <i class="fas fa-plug me-1"></i>Test
                        </button>
                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button"
                                    data-bs-toggle="dropdown">
                                <i class="fas fa-ellipsis-v"></i>
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="#" onclick="editProvider({{ $provider->id }})">
                                    <i class="fas fa-edit me-2"></i>Edit
                                </a></li>
                                <li><a class="dropdown-item" href="#" onclick="duplicateProvider({{ $provider->id }})">
                                    <i class="fas fa-copy me-2"></i>Duplicate
                                </a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger" href="#" onclick="deleteProvider({{ $provider->id }})">
                                    <i class="fas fa-trash me-2"></i>Delete
                                </a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @empty
        <div class="col-12">
            <div class="text-center py-5">
                <i class="fas fa-building fa-4x text-muted mb-3"></i>
                <h4 class="text-muted">No Providers Configured</h4>
                <p class="text-muted mb-4">Get started by adding your first clearinghouse provider</p>
                <button type="button" class="btn btn-primary" onclick="showAddProviderModal()">
                    <i class="fas fa-plus me-2"></i>Add Your First Provider
                </button>
            </div>
        </div>
        @endforelse
    </div>
</div>

<!-- Provider Configuration Modal -->
<div class="modal fade" id="providerModal" tabindex="-1" size="xl">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="providerModalTitle">Configure Provider</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="providerForm">
                @csrf
                <input type="hidden" id="provider_id" name="provider_id">
                <div class="modal-body modal-config-section">
                    <!-- Configuration Tabs -->
                    <ul class="nav nav-tabs config-tabs mb-4" id="configTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="basic-tab" data-bs-toggle="tab" data-bs-target="#basic" type="button" role="tab">
                                Basic Settings
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="endpoints-tab" data-bs-toggle="tab" data-bs-target="#endpoints" type="button" role="tab">
                                API Endpoints
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="rate-limits-tab" data-bs-toggle="tab" data-bs-target="#rate-limits" type="button" role="tab">
                                Rate Limits
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="features-tab" data-bs-toggle="tab" data-bs-target="#features" type="button" role="tab">
                                Features
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content" id="configTabsContent">
                        <!-- Basic Settings Tab -->
                        <div class="tab-pane fade show active" id="basic" role="tabpanel">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="provider_name" class="form-label">
                                        <i class="fas fa-tag me-2"></i>Provider Name <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" class="form-control" id="provider_name" name="provider_name" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="provider_code" class="form-label">
                                        <i class="fas fa-code me-2"></i>Provider Code <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" class="form-control" id="provider_code" name="provider_code" required>
                                    <div class="form-text">Unique identifier for this provider</div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="provider_description" class="form-label">
                                    <i class="fas fa-info-circle me-2"></i>Description
                                </label>
                                <textarea class="form-control" id="provider_description" name="provider_description" rows="3"></textarea>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="provider_website" class="form-label">
                                        <i class="fas fa-globe me-2"></i>Website
                                    </label>
                                    <input type="url" class="form-control" id="provider_website" name="provider_website">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="support_email" class="form-label">
                                        <i class="fas fa-envelope me-2"></i>Support Email
                                    </label>
                                    <input type="email" class="form-control" id="support_email" name="support_email">
                                </div>
                            </div>
                        </div>

                        <!-- API Endpoints Tab -->
                        <div class="tab-pane fade" id="endpoints" role="tabpanel">
                            <div class="config-section">
                                <h6 class="mb-3">Production Endpoints</h6>

                                <div class="config-field">
                                    <label>Submission Endpoint</label>
                                    <input type="url" class="form-control" id="submission_endpoint" name="submission_endpoint"
                                           placeholder="https://api.provider.com/submit">
                                    <div class="form-text">Endpoint for claim submissions</div>
                                </div>

                                <div class="config-field">
                                    <label>Status Check Endpoint</label>
                                    <input type="url" class="form-control" id="status_endpoint" name="status_endpoint"
                                           placeholder="https://api.provider.com/status">
                                    <div class="form-text">Endpoint for checking submission status</div>
                                </div>

                                <div class="config-field">
                                    <label>Eligibility Check Endpoint</label>
                                    <input type="url" class="form-control" id="eligibility_endpoint" name="eligibility_endpoint"
                                           placeholder="https://api.provider.com/eligibility">
                                    <div class="form-text">Endpoint for eligibility verification</div>
                                </div>
                            </div>

                            <div class="config-section">
                                <h6 class="mb-3">Sandbox/Test Endpoints</h6>

                                <div class="config-field">
                                    <label>Test Submission Endpoint</label>
                                    <input type="url" class="form-control" id="test_submission_endpoint" name="test_submission_endpoint">
                                    <div class="form-text">Sandbox endpoint for testing submissions</div>
                                </div>

                                <div class="config-field">
                                    <label>Test Status Endpoint</label>
                                    <input type="url" class="form-control" id="test_status_endpoint" name="test_status_endpoint">
                                    <div class="form-text">Sandbox endpoint for testing status checks</div>
                                </div>
                            </div>

                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-outline-primary btn-sm" onclick="testEndpoints()">
                                    <i class="fas fa-plug me-2"></i>Test Endpoints
                                </button>
                                <div id="endpointTestResult"></div>
                            </div>
                        </div>

                        <!-- Rate Limits Tab -->
                        <div class="tab-pane fade" id="rate-limits" role="tabpanel">
                            <div class="config-section">
                                <h6 class="mb-3">API Rate Limits</h6>

                                <div class="rate-limit-display">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span class="fw-semibold">Requests per Minute</span>
                                        <span id="rpmDisplay">0 / 60</span>
                                    </div>
                                    <div class="rate-limit-bar">
                                        <div class="rate-limit-fill" id="rpmBar" style="width: 0%"></div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="config-field">
                                            <label>Max Requests per Minute</label>
                                            <input type="number" class="form-control" id="max_rpm" name="max_rpm" value="60" min="1">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="config-field">
                                            <label>Max Requests per Hour</label>
                                            <input type="number" class="form-control" id="max_rph" name="max_rph" value="1000" min="1">
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="config-field">
                                            <label>Burst Limit</label>
                                            <input type="number" class="form-control" id="burst_limit" name="burst_limit" value="10" min="1">
                                            <div class="form-text">Maximum concurrent requests</div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="config-field">
                                            <label>Cooldown Period (seconds)</label>
                                            <input type="number" class="form-control" id="cooldown_period" name="cooldown_period" value="60" min="0">
                                            <div class="form-text">Wait time after hitting rate limit</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Features Tab -->
                        <div class="tab-pane fade" id="features" role="tabpanel">
                            <div class="config-section">
                                <h6 class="mb-3">Supported Features</h6>

                                <div class="feature-toggle">
                                    <div>
                                        <strong>Real-time Status Updates</strong>
                                        <div class="small text-muted">Receive real-time submission status updates</div>
                                    </div>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="feature_realtime_status" name="feature_realtime_status">
                                    </div>
                                </div>

                                <div class="feature-toggle">
                                    <div>
                                        <strong>Batch Submissions</strong>
                                        <div class="small text-muted">Support for submitting multiple claims at once</div>
                                    </div>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="feature_batch_submissions" name="feature_batch_submissions">
                                    </div>
                                </div>

                                <div class="feature-toggle">
                                    <div>
                                        <strong>Eligibility Checking</strong>
                                        <div class="small text-muted">Real-time insurance eligibility verification</div>
                                    </div>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="feature_eligibility_check" name="feature_eligibility_check">
                                    </div>
                                </div>

                                <div class="feature-toggle">
                                    <div>
                                        <strong>Attachment Support</strong>
                                        <div class="small text-muted">Support for submitting claims with attachments</div>
                                    </div>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="feature_attachments" name="feature_attachments">
                                    </div>
                                </div>

                                <div class="feature-toggle">
                                    <div>
                                        <strong>ERA/835 Support</strong>
                                        <div class="small text-muted">Electronic Remittance Advice processing</div>
                                    </div>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="feature_era_support" name="feature_era_support">
                                    </div>
                                </div>
                            </div>

                            <div class="config-section">
                                <h6 class="mb-3">Additional Settings</h6>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="config-field">
                                            <label>Response Timeout (seconds)</label>
                                            <input type="number" class="form-control" id="response_timeout" name="response_timeout" value="30" min="5">
                                            <div class="form-text">Maximum time to wait for API responses</div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="config-field">
                                            <label>Retry Attempts</label>
                                            <input type="number" class="form-control" id="retry_attempts" name="retry_attempts" value="3" min="0">
                                            <div class="form-text">Number of retry attempts for failed requests</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Cancel
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Save Configuration
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let providerModal;

document.addEventListener('DOMContentLoaded', function() {
    providerModal = new bootstrap.Modal(document.getElementById('providerModal'));
    loadProviders();
});

function showAddProviderModal() {
    document.getElementById('providerModalTitle').textContent = 'Add New Provider';
    document.getElementById('providerForm').reset();
    document.getElementById('provider_id').value = '';
    providerModal.show();
}

function configureProvider(providerId) {
    document.getElementById('providerModalTitle').textContent = 'Configure Provider';

    // Load provider configuration
    fetch(`/admin/clearinghouse/providers/${providerId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const provider = data.provider;
                populateProviderForm(provider);
                providerModal.show();
            }
        })
        .catch(error => {
            // console.error('Error loading provider:', error);
            alert('Error loading provider configuration');
        });
}

function populateProviderForm(provider) {
    document.getElementById('provider_id').value = provider.id;
    document.getElementById('provider_name').value = provider.name || '';
    document.getElementById('provider_code').value = provider.code || '';
    document.getElementById('provider_description').value = provider.description || '';
    document.getElementById('provider_website').value = provider.website || '';
    document.getElementById('support_email').value = provider.support_email || '';

    // Endpoints
    document.getElementById('submission_endpoint').value = provider.endpoints?.submission || '';
    document.getElementById('status_endpoint').value = provider.endpoints?.status || '';
    document.getElementById('eligibility_endpoint').value = provider.endpoints?.eligibility || '';
    document.getElementById('test_submission_endpoint').value = provider.endpoints?.test_submission || '';
    document.getElementById('test_status_endpoint').value = provider.endpoints?.test_status || '';

    // Rate limits
    document.getElementById('max_rpm').value = provider.rate_limits?.max_rpm || 60;
    document.getElementById('max_rph').value = provider.rate_limits?.max_rph || 1000;
    document.getElementById('burst_limit').value = provider.rate_limits?.burst_limit || 10;
    document.getElementById('cooldown_period').value = provider.rate_limits?.cooldown_period || 60;

    // Features
    document.getElementById('feature_realtime_status').checked = provider.features?.realtime_status || false;
    document.getElementById('feature_batch_submissions').checked = provider.features?.batch_submissions || false;
    document.getElementById('feature_eligibility_check').checked = provider.features?.eligibility_check || false;
    document.getElementById('feature_attachments').checked = provider.features?.attachments || false;
    document.getElementById('feature_era_support').checked = provider.features?.era_support || false;

    // Additional settings
    document.getElementById('response_timeout').value = provider.settings?.response_timeout || 30;
    document.getElementById('retry_attempts').value = provider.settings?.retry_attempts || 3;
}

function testProvider(providerId) {
    const button = event.target.closest('button');
    const originalHTML = button.innerHTML;
    button.disabled = true;
    button.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Testing...';

    fetch(`/admin/clearinghouse/providers/${providerId}/test`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Provider test successful!');
            loadProviders();
        } else {
            alert('Provider test failed: ' + (data.message || 'Unknown error'));
        }
    })
    .catch(error => {
        // console.error('Error testing provider:', error);
        alert('Error testing provider');
    })
    .finally(() => {
        button.disabled = false;
        button.innerHTML = originalHTML;
    });
}

function testEndpoints() {
    const endpoints = {
        submission: document.getElementById('submission_endpoint').value,
        status: document.getElementById('status_endpoint').value,
        eligibility: document.getElementById('eligibility_endpoint').value
    };

    const resultDiv = document.getElementById('endpointTestResult');
    resultDiv.innerHTML = '<div class="endpoint-test-result">Testing endpoints...</div>';

    fetch('/admin/clearinghouse/providers/test-endpoints', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json'
        },
        body: JSON.stringify({ endpoints })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            resultDiv.innerHTML = '<div class="endpoint-test-result success">All endpoints are reachable!</div>';
        } else {
            resultDiv.innerHTML = `<div class="endpoint-test-result error">Endpoint test failed: ${data.message}</div>`;
        }
    })
    .catch(error => {
        // console.error('Error testing endpoints:', error);
        resultDiv.innerHTML = '<div class="endpoint-test-result error">Error testing endpoints</div>';
    });
}

function loadProviders() {
    fetch('/admin/clearinghouse/providers/data')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateMetrics(data.metrics);
                renderProviders(data.providers);
            }
        })
        .catch(error => {
            // console.error('Error loading providers:', error);
        });
}

function updateMetrics(metrics) {
    document.getElementById('totalProviders').textContent = metrics.total || 0;
    document.getElementById('activeProviders').textContent = metrics.active || 0;
    document.getElementById('configuringProviders').textContent = metrics.configuring || 0;
    document.getElementById('totalEndpoints').textContent = metrics.endpoints || 0;
}

function renderProviders(providers) {
    // This would re-render the providers grid - simplified for now
    location.reload(); // Simple refresh for now
}

function editProvider(providerId) {
    configureProvider(providerId);
}

function duplicateProvider(providerId) {
    if (confirm('Create a duplicate of this provider configuration?')) {
        fetch(`/admin/clearinghouse/providers/${providerId}/duplicate`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                loadProviders();
            } else {
                alert('Error duplicating provider');
            }
        })
        .catch(error => {
            // console.error('Error duplicating provider:', error);
            alert('Error duplicating provider');
        });
    }
}

function deleteProvider(providerId) {
    if (!confirm('Are you sure you want to delete this provider? This will affect all associated accounts.')) {
        return;
    }

    fetch(`/admin/clearinghouse/providers/${providerId}`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            loadProviders();
        } else {
            alert('Error deleting provider');
        }
    })
    .catch(error => {
        // console.error('Error deleting provider:', error);
        alert('Error deleting provider');
    });
}

// Handle form submission
document.getElementById('providerForm').addEventListener('submit', function(e) {
    e.preventDefault();

    const formData = new FormData(this);
    const providerId = formData.get('provider_id');
    const method = providerId ? 'PUT' : 'POST';
    const url = providerId ? `/admin/clearinghouse/providers/${providerId}` : '/admin/clearinghouse/providers';

    // Collect complex data
    const providerData = {
        name: formData.get('provider_name'),
        code: formData.get('provider_code'),
        description: formData.get('provider_description'),
        website: formData.get('provider_website'),
        support_email: formData.get('support_email'),
        endpoints: {
            submission: formData.get('submission_endpoint'),
            status: formData.get('status_endpoint'),
            eligibility: formData.get('eligibility_endpoint'),
            test_submission: formData.get('test_submission_endpoint'),
            test_status: formData.get('test_status_endpoint')
        },
        rate_limits: {
            max_rpm: parseInt(formData.get('max_rpm')),
            max_rph: parseInt(formData.get('max_rph')),
            burst_limit: parseInt(formData.get('burst_limit')),
            cooldown_period: parseInt(formData.get('cooldown_period'))
        },
        features: {
            realtime_status: document.getElementById('feature_realtime_status').checked,
            batch_submissions: document.getElementById('feature_batch_submissions').checked,
            eligibility_check: document.getElementById('feature_eligibility_check').checked,
            attachments: document.getElementById('feature_attachments').checked,
            era_support: document.getElementById('feature_era_support').checked
        },
        settings: {
            response_timeout: parseInt(formData.get('response_timeout')),
            retry_attempts: parseInt(formData.get('retry_attempts'))
        }
    };

    fetch(url, {
        method: method,
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json'
        },
        body: JSON.stringify(providerData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            providerModal.hide();
            loadProviders();
        } else {
            alert('Error saving provider: ' + (data.message || 'Unknown error'));
        }
    })
    .catch(error => {
        // console.error('Error saving provider:', error);
        alert('Error saving provider');
    });
});
</script>
@endsection
