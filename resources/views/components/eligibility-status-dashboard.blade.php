@props(['patientId' => null])

<div class="eligibility-status-dashboard">
    <div class="row">
        <!-- Eligibility Status Card -->
        <div class="col-md-8 mb-4">
            <div class="card eligibility-card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-shield-check me-2"></i>Eligibility Status
                    </h5>
                </div>
                <div class="card-body">
                    <div id="eligibilityStatusContainer">
                        <div class="eligibility-loading">
                            <div class="text-center py-4">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Loading eligibility status...</span>
                                </div>
                                <p class="mt-2 text-muted">Checking eligibility status...</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions Card -->
        <div class="col-md-4 mb-4">
            <div class="card quick-actions-card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-bolt me-2"></i>Quick Actions
                    </h5>
                </div>
                <div class="card-body">
                    <button type="button" class="btn btn-primary w-100 mb-3" id="checkEligibilityBtn"
                            onclick="checkEligibility()">
                        <i class="fas fa-sync-alt me-2"></i>Check Eligibility Now
                    </button>

                    <button type="button" class="btn btn-outline-secondary w-100 mb-3"
                            onclick="showInsuranceModal()">
                        <i class="fas fa-plus me-2"></i>Add Insurance
                    </button>

                    <div class="eligibility-history-link">
                        <a href="#" class="text-decoration-none" onclick="showEligibilityHistory()">
                            <i class="fas fa-history me-2"></i>View Eligibility History
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Eligibility Details (Hidden by default, shown when expanded) -->
    <div id="eligibilityDetails" class="row" style="display: none;">
        <div class="col-12">
            <div class="card eligibility-details-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-info-circle me-2"></i>Eligibility Details
                    </h5>
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="hideEligibilityDetails()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="card-body">
                    <div id="eligibilityDetailsContent">
                        <!-- Details will be loaded here -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let eligibilityCheckInterval;

function loadEligibilityStatus() {
    const container = document.getElementById('eligibilityStatusContainer');
    const patientId = '{{ $patientId }}';

    if (!patientId) {
        container.innerHTML = `
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle me-2"></i>
                No patient selected for eligibility check.
            </div>
        `;
        return;
    }

    fetch(`/api/eligibility/${patientId}/status`, {
        method: 'GET',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            renderEligibilityStatuses(data.data.eligibility_statuses);
        } else {
            container.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    ${data.error || 'Failed to load eligibility status'}
                </div>
            `;
        }
    })
    .catch(error => {
        console.error('Error loading eligibility status:', error);
        container.innerHTML = `
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle me-2"></i>
                Error loading eligibility status. Please try again.
            </div>
        `;
    });
}

function renderEligibilityStatuses(eligibilityStatuses) {
    const container = document.getElementById('eligibilityStatusContainer');

    if (!eligibilityStatuses || eligibilityStatuses.length === 0) {
        container.innerHTML = `
            <div class="text-center py-4">
                <i class="fas fa-shield-alt fa-2x text-muted mb-3"></i>
                <h6 class="text-muted">No Insurance Information</h6>
                <p class="text-muted small">Add insurance information to enable eligibility checking</p>
            </div>
        `;
        return;
    }

    // For now, show the first insurance status (can be enhanced to show all)
    const primaryInsurance = eligibilityStatuses[0];
    const latestCheck = primaryInsurance.latest_check;

    let statusClass = 'secondary';
    let statusIcon = 'question-circle';
    let statusText = 'Not Checked';

    if (latestCheck) {
        switch (latestCheck.status?.toLowerCase()) {
            case 'eligible':
                statusClass = 'success';
                statusIcon = 'check-circle';
                statusText = 'Eligible';
                break;
            case 'ineligible':
                statusClass = 'danger';
                statusIcon = 'times-circle';
                statusText = 'Ineligible';
                break;
            case 'pending':
                statusClass = 'warning';
                statusIcon = 'clock';
                statusText = 'Pending';
                break;
            case 'error':
                statusClass = 'danger';
                statusIcon = 'exclamation-triangle';
                statusText = 'Error';
                break;
        }
    }

    const lastChecked = latestCheck ?
        new Date(latestCheck.check_date).toLocaleString() : 'Never';

    container.innerHTML = `
        <div class="eligibility-status-display">
            <div class="status-indicator mb-3">
                <span class="badge badge-lg bg-${statusClass}">
                    <i class="fas fa-${statusIcon} me-2"></i>${statusText}
                </span>
            </div>

            <div class="eligibility-info">
                <div class="row">
                    <div class="col-md-6">
                        <div class="info-item">
                            <label class="text-muted">Last Checked</label>
                            <div class="fw-semibold">${lastChecked}</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-item">
                            <label class="text-muted">Insurance Provider</label>
                            <div class="fw-semibold">${primaryInsurance.provider_name}</div>
                        </div>
                    </div>
                </div>

                <div class="row mt-2">
                    <div class="col-md-6">
                        <div class="info-item">
                            <label class="text-muted">Policy Number</label>
                            <div class="fw-semibold">${primaryInsurance.policy_number || 'N/A'}</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-item">
                            <label class="text-muted">Service Type</label>
                            <div class="fw-semibold">${latestCheck?.service_type || 'N/A'}</div>
                        </div>
                    </div>
                </div>

                ${eligibilityStatuses.length > 1 ? `
                    <div class="mt-3 p-2 bg-light rounded">
                        <small class="text-muted">
                            <i class="fas fa-info-circle me-1"></i>
                            ${eligibilityStatuses.length} insurance policies found. Showing primary insurance.
                        </small>
                    </div>
                ` : ''}

                <div class="eligibility-actions mt-3">
                    <button type="button" class="btn btn-sm btn-outline-primary me-2"
                            onclick="showEligibilityDetails()">
                        <i class="fas fa-info-circle me-1"></i>Details
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary"
                            onclick="refreshEligibilityStatus()">
                        <i class="fas fa-sync-alt me-1"></i>Refresh
                    </button>
                </div>
            </div>
        </div>
    `;
}

function checkEligibility() {
    const btn = document.getElementById('checkEligibilityBtn');
    const patientId = '{{ $patientId }}';

    if (!patientId) {
        showErrorMessage('No patient selected for eligibility check');
        return;
    }

    // Disable button and show loading state
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Checking...';

    // First get patient's insurance information to determine which insurance to check
    fetch(`/api/patient-insurance?patient_id=${patientId}`, {
        method: 'GET',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(insuranceData => {
        if (insuranceData.success && insuranceData.data && insuranceData.data.length > 0) {
            // Use the first insurance for now (can be enhanced to let user choose)
            const primaryInsurance = insuranceData.data[0];

            // Now perform the eligibility check
            return fetch('/api/eligibility/check', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    patient_insurance_id: primaryInsurance.id,
                    service_type: 'general', // Default service type, can be made configurable
                    force_refresh: true
                })
            });
        } else {
            throw new Error('No insurance information found for this patient');
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // The API returns data directly, not a batch_id for polling
            // Just refresh the status after a short delay
            setTimeout(() => {
                loadEligibilityStatus();
                resetCheckButton();
            }, 2000);
        } else {
            showErrorMessage(data.error || 'Failed to check eligibility');
            resetCheckButton();
        }
    })
    .catch(error => {
        console.error('Error checking eligibility:', error);
        showErrorMessage('Error checking eligibility. Please try again.');
        resetCheckButton();
    });
}

// Removed polling function as the check API returns results directly

function resetCheckButton() {
    const btn = document.getElementById('checkEligibilityBtn');
    btn.disabled = false;
    btn.innerHTML = '<i class="fas fa-sync-alt me-2"></i>Check Eligibility Now';
}

function showEligibilityDetails() {
    document.getElementById('eligibilityDetails').style.display = 'block';
    // Load detailed eligibility information
    loadEligibilityDetails();
}

function hideEligibilityDetails() {
    document.getElementById('eligibilityDetails').style.display = 'none';
}

function loadEligibilityDetails() {
    const container = document.getElementById('eligibilityDetailsContent');
    const patientId = '{{ $patientId }}';

    container.innerHTML = `
        <div class="text-center py-4">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading details...</span>
            </div>
            <p class="mt-2">Loading eligibility details...</p>
        </div>
    `;

    // This would load detailed eligibility information
    // For now, just show a placeholder
    setTimeout(() => {
        container.innerHTML = `
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                Detailed eligibility information would be displayed here.
            </div>
        `;
    }, 1000);
}

function refreshEligibilityStatus() {
    loadEligibilityStatus();
}

function showInsuranceModal() {
    // This would open the insurance management modal
    alert('Insurance modal would open here');
}

function showEligibilityHistory() {
    // This would show eligibility history
    showErrorMessage('Eligibility history feature coming soon');
}

function showErrorMessage(message) {
    const container = document.getElementById('eligibilityStatusContainer');
    const errorDiv = document.createElement('div');
    errorDiv.className = 'alert alert-danger alert-dismissible fade show mt-3';
    errorDiv.innerHTML = `
        <i class="fas fa-exclamation-circle me-2"></i>
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;
    container.appendChild(errorDiv);

    // Auto-dismiss after 5 seconds
    setTimeout(() => {
        if (errorDiv.parentNode) {
            errorDiv.remove();
        }
    }, 5000);
}

// Initialize when component loads
document.addEventListener('DOMContentLoaded', function() {
    loadEligibilityStatus();
});
</script>

<style>
.eligibility-card {
    border: none;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07);
    border-radius: 12px;
}

.quick-actions-card {
    border: none;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07);
    border-radius: 12px;
}

.eligibility-details-card {
    border: none;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07);
    border-radius: 12px;
}

.badge-lg {
    font-size: 1rem;
    padding: 0.5rem 1rem;
}

.info-item {
    margin-bottom: 1rem;
}

.info-item label {
    display: block;
    font-size: 0.875rem;
    margin-bottom: 0.25rem;
}

.coverage-summary {
    padding: 1rem;
    background: #f8f9fa;
    border-radius: 8px;
}

.eligibility-actions {
    padding-top: 1rem;
    border-top: 1px solid #dee2e6;
}
</style>
