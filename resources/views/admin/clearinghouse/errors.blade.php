@extends('layouts.admin')

@section('title', 'Error Reporting & Alerts')

@push('styles')
<style>
    .error-card {
        border: none;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07);
        border-radius: 12px;
        transition: transform 0.3s ease;
    }

    .error-card:hover {
        transform: translateY(-2px);
    }

    .error-severity {
        padding: 4px 8px;
        border-radius: 12px;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
    }

    .error-severity.critical {
        background: rgba(220, 53, 69, 0.1);
        color: #dc3545;
    }

    .error-severity.high {
        background: rgba(255, 193, 7, 0.1);
        color: #ffc107;
    }

    .error-severity.medium {
        background: rgba(13, 202, 240, 0.1);
        color: #0dcaf0;
    }

    .error-severity.low {
        background: rgba(25, 135, 84, 0.1);
        color: #198754;
    }

    .alert-config-card {
        background: white;
        border-radius: 12px;
        padding: 1.5rem;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07);
        border: none;
    }

    .alert-toggle {
        width: 50px;
        height: 24px;
        border-radius: 12px;
        background: #dee2e6;
        position: relative;
        cursor: pointer;
        transition: background 0.3s ease;
    }

    .alert-toggle.active {
        background: #007bff;
    }

    .alert-toggle-slider {
        width: 20px;
        height: 20px;
        border-radius: 50%;
        background: white;
        position: absolute;
        top: 2px;
        left: 2px;
        transition: transform 0.3s ease;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
    }

    .alert-toggle.active .alert-toggle-slider {
        transform: translateX(26px);
    }

    .error-timeline {
        position: relative;
        padding-left: 2rem;
    }

    .error-timeline::before {
        content: '';
        position: absolute;
        left: 0.5rem;
        top: 0;
        bottom: 0;
        width: 2px;
        background: #dee2e6;
    }

    .timeline-item {
        position: relative;
        margin-bottom: 1.5rem;
    }

    .timeline-item::before {
        content: '';
        position: absolute;
        left: -2rem;
        top: 0.5rem;
        width: 12px;
        height: 12px;
        border-radius: 50%;
        background: #007bff;
        border: 2px solid white;
        box-shadow: 0 0 0 2px #dee2e6;
    }

    .timeline-item.critical::before { background: #dc3545; }
    .timeline-item.high::before { background: #ffc107; }
    .timeline-item.medium::before { background: #0dcaf0; }
    .timeline-item.low::before { background: #198754; }

    .error-details {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 1rem;
        margin-top: 0.5rem;
        font-family: 'Courier New', monospace;
        font-size: 0.875rem;
        white-space: pre-wrap;
        word-break: break-all;
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

    .filter-section {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 1rem;
        margin-bottom: 1rem;
    }

    .resolution-badge {
        padding: 2px 6px;
        border-radius: 4px;
        font-size: 0.75rem;
        font-weight: 500;
    }

    .resolution-badge.resolved {
        background: rgba(25, 135, 84, 0.1);
        color: #198754;
    }

    .resolution-badge.unresolved {
        background: rgba(220, 53, 69, 0.1);
        color: #dc3545;
    }
</style>
@endpush

@section('content')
<div class="error-reporting">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <div>
                    <h2 class="mb-1">Error Reporting & Alerts</h2>
                    <p class="text-muted mb-0">Monitor errors and configure alert notifications</p>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-primary" onclick="refreshErrors()">
                        <i class="fas fa-sync-alt me-2"></i>Refresh
                    </button>
                    <button type="button" class="btn btn-outline-secondary" onclick="clearResolvedErrors()">
                        <i class="fas fa-check-double me-2"></i>Clear Resolved
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Error Metrics -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="metric-card">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <div class="metric-value text-danger" id="totalErrors">0</div>
                        <div class="metric-label">Total Errors (24h)</div>
                    </div>
                    <div class="text-danger">
                        <i class="fas fa-exclamation-triangle fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="metric-card">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <div class="metric-value text-warning" id="criticalErrors">0</div>
                        <div class="metric-label">Critical Errors</div>
                    </div>
                    <div class="text-warning">
                        <i class="fas fa-exclamation-circle fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="metric-card">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <div class="metric-value text-success" id="resolvedErrors">0</div>
                        <div class="metric-label">Resolved Today</div>
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
                        <div class="metric-value text-info" id="avgResolutionTime">0h</div>
                        <div class="metric-label">Avg Resolution Time</div>
                    </div>
                    <div class="text-info">
                        <i class="fas fa-clock fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Error List -->
        <div class="col-lg-8 mb-4">
            <div class="card error-card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-exclamation-triangle me-2"></i>Error Log
                        </h5>
                        <div class="d-flex gap-2">
                            <select class="form-select form-select-sm" id="severityFilter" style="width: auto;">
                                <option value="">All Severities</option>
                                <option value="critical">Critical</option>
                                <option value="high">High</option>
                                <option value="medium">Medium</option>
                                <option value="low">Low</option>
                            </select>
                            <select class="form-select form-select-sm" id="statusFilter" style="width: auto;">
                                <option value="">All Status</option>
                                <option value="unresolved">Unresolved</option>
                                <option value="resolved">Resolved</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="filter-section">
                        <div class="row">
                            <div class="col-md-4">
                                <label for="dateRange" class="form-label small">Date Range</label>
                                <select class="form-select form-select-sm" id="dateRange">
                                    <option value="today">Today</option>
                                    <option value="yesterday">Yesterday</option>
                                    <option value="week">Last 7 days</option>
                                    <option value="month">Last 30 days</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="accountFilter" class="form-label small">Account</label>
                                <select class="form-select form-select-sm" id="accountFilter">
                                    <option value="">All Accounts</option>
                                    @foreach($accounts ?? [] as $account)
                                    <option value="{{ $account->id }}">{{ $account->account_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="errorTypeFilter" class="form-label small">Error Type</label>
                                <select class="form-select form-select-sm" id="errorTypeFilter">
                                    <option value="">All Types</option>
                                    <option value="connection">Connection</option>
                                    <option value="authentication">Authentication</option>
                                    <option value="validation">Validation</option>
                                    <option value="timeout">Timeout</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="error-timeline" id="errorTimeline">
                        <!-- Error items will be loaded here -->
                    </div>
                </div>
            </div>
        </div>

        <!-- Alerts Configuration -->
        <div class="col-lg-4 mb-4">
            <div class="alert-config-card">
                <h5 class="card-title mb-4">
                    <i class="fas fa-bell me-2"></i>Alert Configuration
                </h5>

                <div class="alert-settings">
                    <div class="alert-setting-item mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div>
                                <strong>Email Alerts</strong>
                                <div class="small text-muted">Receive error notifications via email</div>
                            </div>
                            <div class="alert-toggle active" onclick="toggleAlert('email')">
                                <div class="alert-toggle-slider"></div>
                            </div>
                        </div>
                        <input type="email" class="form-control form-control-sm" placeholder="alert@example.com" id="emailAlertAddress">
                    </div>

                    <div class="alert-setting-item mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div>
                                <strong>SMS Alerts</strong>
                                <div class="small text-muted">Receive critical error notifications via SMS</div>
                            </div>
                            <div class="alert-toggle" onclick="toggleAlert('sms')">
                                <div class="alert-toggle-slider"></div>
                            </div>
                        </div>
                        <input type="tel" class="form-control form-control-sm" placeholder="+1 (555) 123-4567" id="smsAlertNumber">
                    </div>

                    <div class="alert-setting-item mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div>
                                <strong>Dashboard Alerts</strong>
                                <div class="small text-muted">Show error notifications in dashboard</div>
                            </div>
                            <div class="alert-toggle active" onclick="toggleAlert('dashboard')">
                                <div class="alert-toggle-slider"></div>
                            </div>
                        </div>
                    </div>

                    <hr>

                    <div class="alert-thresholds">
                        <h6 class="mb-3">Alert Thresholds</h6>

                        <div class="mb-3">
                            <label for="criticalThreshold" class="form-label small">Critical Errors Threshold</label>
                            <input type="number" class="form-control form-control-sm" id="criticalThreshold" value="5" min="1">
                            <div class="form-text small">Alert when critical errors exceed this number in 1 hour</div>
                        </div>

                        <div class="mb-3">
                            <label for="errorRateThreshold" class="form-label small">Error Rate Threshold (%)</label>
                            <input type="number" class="form-control form-control-sm" id="errorRateThreshold" value="10" min="1" max="100">
                            <div class="form-text small">Alert when error rate exceeds this percentage</div>
                        </div>

                        <div class="mb-3">
                            <label for="responseTimeThreshold" class="form-label small">Response Time Threshold (seconds)</label>
                            <input type="number" class="form-control form-control-sm" id="responseTimeThreshold" value="30" min="1">
                            <div class="form-text small">Alert when average response time exceeds this duration</div>
                        </div>
                    </div>

                    <div class="d-grid">
                        <button type="button" class="btn btn-primary btn-sm" onclick="saveAlertSettings()">
                            <i class="fas fa-save me-2"></i>Save Settings
                        </button>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="alert-config-card mt-3">
                <h6 class="mb-3">Quick Actions</h6>
                <div class="d-grid gap-2">
                    <button type="button" class="btn btn-outline-primary btn-sm" onclick="testAlerts()">
                        <i class="fas fa-paper-plane me-2"></i>Test Alert System
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="exportErrorLog()">
                        <i class="fas fa-download me-2"></i>Export Error Log
                    </button>
                    <button type="button" class="btn btn-outline-danger btn-sm" onclick="clearAllErrors()">
                        <i class="fas fa-trash me-2"></i>Clear All Errors
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let currentFilters = {
    severity: '',
    status: '',
    dateRange: 'today',
    account: '',
    errorType: ''
};

document.addEventListener('DOMContentLoaded', function() {
    loadErrorData();
    setupFilters();
    loadAlertSettings();
});

function loadErrorData() {
    const params = new URLSearchParams(currentFilters);
    fetch(`/admin/clearinghouse/errors/data?${params}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateMetrics(data.metrics);
                renderErrorTimeline(data.errors);
            }
        })
        .catch(error => {
            // console.error('Error loading error data:', error);
        });
}

function updateMetrics(metrics) {
    document.getElementById('totalErrors').textContent = metrics.total || 0;
    document.getElementById('criticalErrors').textContent = metrics.critical || 0;
    document.getElementById('resolvedErrors').textContent = metrics.resolved || 0;
    document.getElementById('avgResolutionTime').textContent = metrics.avgResolutionTime || '0h';
}

function renderErrorTimeline(errors) {
    const container = document.getElementById('errorTimeline');

    if (errors.length === 0) {
        container.innerHTML = `
            <div class="text-center py-4 text-muted">
                <i class="fas fa-check-circle fa-2x mb-2 text-success"></i>
                <div>No errors found for the selected filters</div>
            </div>
        `;
        return;
    }

    container.innerHTML = errors.map(error => `
        <div class="timeline-item ${error.severity.toLowerCase()}" onclick="showErrorDetails(${error.id})">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <div class="flex-grow-1">
                    <div class="d-flex align-items-center mb-1">
                        <span class="error-severity ${error.severity.toLowerCase()} me-2">${error.severity}</span>
                        <strong>${error.error_type}</strong>
                        <span class="resolution-badge ${error.resolved ? 'resolved' : 'unresolved'} ms-2">
                            ${error.resolved ? 'Resolved' : 'Unresolved'}
                        </span>
                    </div>
                    <div class="small text-muted mb-1">
                        <i class="fas fa-building me-1"></i>${error.account_name || 'Unknown Account'} •
                        <i class="fas fa-clock me-1"></i>${new Date(error.created_at).toLocaleString()}
                    </div>
                    <div class="small text-truncate">${error.message}</div>
                </div>
                <div class="text-end">
                    ${error.resolved_at ? `<div class="small text-success">Resolved ${new Date(error.resolved_at).toLocaleString()}</div>` : ''}
                </div>
            </div>
            ${error.details ? `<div class="error-details">${error.details}</div>` : ''}
        </div>
    `).join('');
}

function setupFilters() {
    ['severityFilter', 'statusFilter', 'dateRange', 'accountFilter', 'errorTypeFilter'].forEach(filterId => {
        document.getElementById(filterId).addEventListener('change', function() {
            currentFilters[this.id.replace('Filter', '').toLowerCase()] = this.value;
            loadErrorData();
        });
    });
}

function showErrorDetails(errorId) {
    fetch(`/admin/clearinghouse/errors/${errorId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Show modal with error details
                // console.log('Error details:', data.error);
                // Implementation for modal display would go here
            }
        })
        .catch(error => {
            // console.error('Error loading error details:', error);
        });
}

function toggleAlert(type) {
    const toggle = event.currentTarget;
    toggle.classList.toggle('active');

    const input = document.getElementById(`${type}AlertAddress`) || document.getElementById(`${type}AlertNumber`);
    if (input) {
        input.disabled = !toggle.classList.contains('active');
    }
}

function saveAlertSettings() {
    const settings = {
        email_enabled: document.querySelector('.alert-toggle[onclick*="email"]').classList.contains('active'),
        email_address: document.getElementById('emailAlertAddress').value,
        sms_enabled: document.querySelector('.alert-toggle[onclick*="sms"]').classList.contains('active'),
        sms_number: document.getElementById('smsAlertNumber').value,
        dashboard_enabled: document.querySelector('.alert-toggle[onclick*="dashboard"]').classList.contains('active'),
        critical_threshold: document.getElementById('criticalThreshold').value,
        error_rate_threshold: document.getElementById('errorRateThreshold').value,
        response_time_threshold: document.getElementById('responseTimeThreshold').value
    };

    fetch('/admin/clearinghouse/alerts/settings', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json'
        },
        body: JSON.stringify(settings)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Alert settings saved successfully!');
        } else {
            alert('Error saving alert settings');
        }
    })
    .catch(error => {
        // console.error('Error saving alert settings:', error);
        alert('Error saving alert settings');
    });
}

function loadAlertSettings() {
    fetch('/admin/clearinghouse/alerts/settings')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const settings = data.settings;
                // Update toggle states and inputs based on loaded settings
                // Implementation would go here
            }
        })
        .catch(error => {
            // console.error('Error loading alert settings:', error);
        });
}

function refreshErrors() {
    loadErrorData();
}

function clearResolvedErrors() {
    if (!confirm('Are you sure you want to clear all resolved errors?')) {
        return;
    }

    fetch('/admin/clearinghouse/errors/clear-resolved', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            loadErrorData();
        } else {
            alert('Error clearing resolved errors');
        }
    })
    .catch(error => {
        // console.error('Error clearing resolved errors:', error);
        alert('Error clearing resolved errors');
    });
}

function testAlerts() {
    fetch('/admin/clearinghouse/alerts/test', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Test alerts sent successfully!');
        } else {
            alert('Error sending test alerts');
        }
    })
    .catch(error => {
        // console.error('Error testing alerts:', error);
        alert('Error testing alerts');
    });
}

function exportErrorLog() {
    const link = document.createElement('a');
    link.href = '/admin/clearinghouse/errors/export';
    link.download = `error-log-${new Date().toISOString().split('T')[0]}.csv`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

function clearAllErrors() {
    if (!confirm('Are you sure you want to clear ALL errors? This action cannot be undone.')) {
        return;
    }

    fetch('/admin/clearinghouse/errors/clear-all', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            loadErrorData();
        } else {
            alert('Error clearing all errors');
        }
    })
    .catch(error => {
        // console.error('Error clearing all errors:', error);
        alert('Error clearing all errors');
    });
}
</script>
@endsection
