@extends('layouts.admin')

@section('title', 'Submission Monitoring Dashboard')

@push('styles')
<style>
    .status-card {
        border: none;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07);
        border-radius: 12px;
        transition: transform 0.3s ease;
    }

    .status-card:hover {
        transform: translateY(-2px);
    }

    .status-indicator {
        width: 12px;
        height: 12px;
        border-radius: 50%;
        display: inline-block;
        margin-right: 8px;
    }

    .status-indicator.success { background: #198754; }
    .status-indicator.pending { background: #ffc107; }
    .status-indicator.failed { background: #dc3545; }
    .status-indicator.processing { background: #0dcaf0; }

    .progress-ring {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        display: inline-block;
        position: relative;
    }

    .progress-ring-circle {
        width: 100%;
        height: 100%;
        border-radius: 50%;
        background: conic-gradient(#007bff 0deg, #e9ecef 0deg);
        transition: background 0.3s ease;
    }

    .progress-ring-text {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        font-size: 12px;
        font-weight: 600;
        color: #495057;
    }

    .submission-item {
        border-left: 4px solid #dee2e6;
        padding: 1rem;
        margin-bottom: 0.5rem;
        background: white;
        border-radius: 8px;
        transition: all 0.3s ease;
    }

    .submission-item.success { border-left-color: #198754; }
    .submission-item.pending { border-left-color: #ffc107; }
    .submission-item.failed { border-left-color: #dc3545; }
    .submission-item.processing { border-left-color: #0dcaf0; }

    .submission-item:hover {
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .filter-tabs .nav-link {
        border: none;
        border-radius: 8px 8px 0 0;
        margin-right: 4px;
        color: #6c757d;
    }

    .filter-tabs .nav-link.active {
        background: #007bff;
        color: white;
    }

    .real-time-indicator {
        width: 8px;
        height: 8px;
        background: #198754;
        border-radius: 50%;
        display: inline-block;
        animation: pulse 2s infinite;
    }

    @keyframes pulse {
        0% { opacity: 1; }
        50% { opacity: 0.5; }
        100% { opacity: 1; }
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

    .timeline-item {
        position: relative;
        padding-left: 2rem;
        margin-bottom: 1rem;
    }

    .timeline-item::before {
        content: '';
        position: absolute;
        left: 0.5rem;
        top: 0.5rem;
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: #007bff;
    }

    .timeline-item.success::before { background: #198754; }
    .timeline-item.failed::before { background: #dc3545; }
    .timeline-item.pending::before { background: #ffc107; }
    .timeline-item.processing::before { background: #0dcaf0; }
</style>
@endpush

@section('content')
<div class="submission-monitoring">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <div>
                    <h2 class="mb-1">Submission Monitoring Dashboard</h2>
                    <p class="text-muted mb-0">
                        Real-time tracking of clearinghouse submissions
                        <span class="ms-2"><span class="real-time-indicator"></span> Live</span>
                    </p>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-primary" onclick="refreshData()">
                        <i class="fas fa-sync-alt me-2"></i>Refresh
                    </button>
                    <button type="button" class="btn btn-outline-secondary" onclick="exportData()">
                        <i class="fas fa-download me-2"></i>Export
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Key Metrics -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="metric-card">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <div class="metric-value text-success" id="totalSuccess">0</div>
                        <div class="metric-label">Successful Today</div>
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
                        <div class="metric-value text-warning" id="totalPending">0</div>
                        <div class="metric-label">Pending</div>
                    </div>
                    <div class="text-warning">
                        <i class="fas fa-clock fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="metric-card">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <div class="metric-value text-danger" id="totalFailed">0</div>
                        <div class="metric-label">Failed Today</div>
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
                        <div class="metric-value text-info" id="avgProcessingTime">0s</div>
                        <div class="metric-label">Avg Processing Time</div>
                    </div>
                    <div class="text-info">
                        <i class="fas fa-tachometer-alt fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Status Overview -->
    <div class="row mb-4">
        <div class="col-lg-8 mb-4">
            <div class="card status-card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-chart-pie me-2"></i>Submission Status Overview
                    </h5>
                </div>
                <div class="card-body">
                    <canvas id="statusChart" height="200"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-4 mb-4">
            <div class="card status-card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-clock me-2"></i>Processing Times
                    </h5>
                </div>
                <div class="card-body">
                    <div class="text-center mb-3">
                        <div class="progress-ring" id="processingProgress">
                            <div class="progress-ring-circle"></div>
                            <div class="progress-ring-text">0%</div>
                        </div>
                    </div>
                    <div class="small text-muted text-center mb-3">Completion Rate</div>
                    <div class="row text-center">
                        <div class="col-6">
                            <div class="fw-bold text-success" id="fastestTime">0s</div>
                            <small class="text-muted">Fastest</small>
                        </div>
                        <div class="col-6">
                            <div class="fw-bold text-warning" id="slowestTime">0s</div>
                            <small class="text-muted">Slowest</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters and Submissions List -->
    <div class="card status-card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <h5 class="card-title mb-0">
                    <i class="fas fa-list me-2"></i>Recent Submissions
                </h5>
                <div class="d-flex gap-2 align-items-center">
                    <select class="form-select form-select-sm" id="accountFilter" style="width: auto;">
                        <option value="">All Accounts</option>
                        @foreach($accounts ?? [] as $account)
                        <option value="{{ $account->id }}">{{ $account->account_name }}</option>
                        @endforeach
                    </select>
                    <select class="form-select form-select-sm" id="statusFilter" style="width: auto;">
                        <option value="">All Statuses</option>
                        <option value="success">Successful</option>
                        <option value="pending">Pending</option>
                        <option value="failed">Failed</option>
                        <option value="processing">Processing</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="card-body">
            <!-- Filter Tabs -->
            <ul class="nav nav-tabs filter-tabs mb-3" id="statusTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="all-tab" data-bs-toggle="tab" data-bs-target="#all" type="button" role="tab">
                        All <span class="badge bg-secondary ms-1" id="allCount">0</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="processing-tab" data-bs-toggle="tab" data-bs-target="#processing" type="button" role="tab">
                        <span class="status-indicator processing"></span>Processing <span class="badge bg-info ms-1" id="processingCount">0</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="pending-tab" data-bs-toggle="tab" data-bs-target="#pending" type="button" role="tab">
                        <span class="status-indicator pending"></span>Pending <span class="badge bg-warning ms-1" id="pendingCount">0</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="success-tab" data-bs-toggle="tab" data-bs-target="#success" type="button" role="tab">
                        <span class="status-indicator success"></span>Success <span class="badge bg-success ms-1" id="successCount">0</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="failed-tab" data-bs-toggle="tab" data-bs-target="#failed" type="button" role="tab">
                        <span class="status-indicator failed"></span>Failed <span class="badge bg-danger ms-1" id="failedCount">0</span>
                    </button>
                </li>
            </ul>

            <!-- Submissions Content -->
            <div class="tab-content" id="statusTabsContent">
                <div class="tab-pane fade show active" id="all" role="tabpanel">
                    <div id="allSubmissions" class="submissions-list">
                        <!-- Submissions will be loaded here -->
                    </div>
                </div>
                <div class="tab-pane fade" id="processing" role="tabpanel">
                    <div id="processingSubmissions" class="submissions-list">
                        <!-- Processing submissions will be loaded here -->
                    </div>
                </div>
                <div class="tab-pane fade" id="pending" role="tabpanel">
                    <div id="pendingSubmissions" class="submissions-list">
                        <!-- Pending submissions will be loaded here -->
                    </div>
                </div>
                <div class="tab-pane fade" id="success" role="tabpanel">
                    <div id="successSubmissions" class="submissions-list">
                        <!-- Successful submissions will be loaded here -->
                    </div>
                </div>
                <div class="tab-pane fade" id="failed" role="tabpanel">
                    <div id="failedSubmissions" class="submissions-list">
                        <!-- Failed submissions will be loaded here -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
let statusChart;
let refreshInterval;
let currentFilters = {
    account: '',
    status: ''
};

document.addEventListener('DOMContentLoaded', function() {
    initializeChart();
    loadMonitoringData();
    setupFilters();
    startAutoRefresh();
});

function initializeChart() {
    const ctx = document.getElementById('statusChart').getContext('2d');
    statusChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Successful', 'Pending', 'Failed', 'Processing'],
            datasets: [{
                data: [0, 0, 0, 0],
                backgroundColor: ['#198754', '#ffc107', '#dc3545', '#0dcaf0'],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
}

function loadMonitoringData() {
    fetch('/admin/clearinghouse/monitoring/data')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateMetrics(data.metrics);
                updateChart(data.chartData);
                updateSubmissions(data.submissions);
            }
        })
        .catch(error => {
            // console.error('Error loading monitoring data:', error);
        });
}

function updateMetrics(metrics) {
    document.getElementById('totalSuccess').textContent = metrics.successful || 0;
    document.getElementById('totalPending').textContent = metrics.pending || 0;
    document.getElementById('totalFailed').textContent = metrics.failed || 0;
    document.getElementById('avgProcessingTime').textContent = metrics.avgProcessingTime || '0s';
    document.getElementById('fastestTime').textContent = metrics.fastestTime || '0s';
    document.getElementById('slowestTime').textContent = metrics.slowestTime || '0s';

    // Update progress ring
    const completionRate = metrics.completionRate || 0;
    const progressRing = document.querySelector('.progress-ring-circle');
    progressRing.style.background = `conic-gradient(#007bff ${completionRate * 3.6}deg, #e9ecef 0deg)`;
    document.querySelector('.progress-ring-text').textContent = Math.round(completionRate) + '%';
}

function updateChart(chartData) {
    statusChart.data.datasets[0].data = [
        chartData.successful || 0,
        chartData.pending || 0,
        chartData.failed || 0,
        chartData.processing || 0
    ];
    statusChart.update();
}

function updateSubmissions(submissions) {
    const containers = {
        all: document.getElementById('allSubmissions'),
        processing: document.getElementById('processingSubmissions'),
        pending: document.getElementById('pendingSubmissions'),
        success: document.getElementById('successSubmissions'),
        failed: document.getElementById('failedSubmissions')
    };

    // Clear all containers
    Object.values(containers).forEach(container => container.innerHTML = '');

    // Group submissions by status
    const grouped = {
        all: submissions,
        processing: submissions.filter(s => s.status === 'processing'),
        pending: submissions.filter(s => s.status === 'pending'),
        success: submissions.filter(s => s.status === 'success'),
        failed: submissions.filter(s => s.status === 'failed')
    };

    // Update counts
    document.getElementById('allCount').textContent = grouped.all.length;
    document.getElementById('processingCount').textContent = grouped.processing.length;
    document.getElementById('pendingCount').textContent = grouped.pending.length;
    document.getElementById('successCount').textContent = grouped.success.length;
    document.getElementById('failedCount').textContent = grouped.failed.length;

    // Render submissions
    Object.keys(grouped).forEach(status => {
        const container = containers[status];
        const items = grouped[status];

        if (items.length === 0) {
            container.innerHTML = `
                <div class="text-center py-4 text-muted">
                    <i class="fas fa-inbox fa-2x mb-2"></i>
                    <div>No ${status === 'all' ? '' : status} submissions found</div>
                </div>
            `;
        } else {
            container.innerHTML = items.map(submission => renderSubmissionItem(submission)).join('');
        }
    });
}

function renderSubmissionItem(submission) {
    const statusClass = submission.status.toLowerCase();
    const statusIcon = {
        success: 'check-circle',
        pending: 'clock',
        failed: 'exclamation-triangle',
        processing: 'spinner fa-spin'
    }[statusClass] || 'question-circle';

    const statusColor = {
        success: 'success',
        pending: 'warning',
        failed: 'danger',
        processing: 'info'
    }[statusClass] || 'secondary';

    return `
        <div class="submission-item ${statusClass}" onclick="showSubmissionDetails(${submission.id})">
            <div class="d-flex justify-content-between align-items-start">
                <div class="flex-grow-1">
                    <div class="d-flex align-items-center mb-1">
                        <i class="fas fa-${statusIcon} text-${statusColor} me-2"></i>
                        <strong>${submission.patient_name || 'Unknown Patient'}</strong>
                        <small class="text-muted ms-2">${submission.claim_number || 'N/A'}</small>
                    </div>
                    <div class="small text-muted mb-1">
                        <i class="fas fa-building me-1"></i>${submission.account_name || 'Unknown Account'} •
                        <i class="fas fa-calendar me-1"></i>${submission.submitted_at ? new Date(submission.submitted_at).toLocaleString() : 'Unknown'}
                    </div>
                    ${submission.error_message ? `<div class="small text-danger"><i class="fas fa-exclamation-circle me-1"></i>${submission.error_message}</div>` : ''}
                </div>
                <div class="text-end">
                    <div class="badge bg-${statusColor}">${submission.status}</div>
                    ${submission.processing_time ? `<div class="small text-muted mt-1">${submission.processing_time}</div>` : ''}
                </div>
            </div>
        </div>
    `;
}

function setupFilters() {
    document.getElementById('accountFilter').addEventListener('change', function() {
        currentFilters.account = this.value;
        loadMonitoringData();
    });

    document.getElementById('statusFilter').addEventListener('change', function() {
        currentFilters.status = this.value;
        loadMonitoringData();
    });
}

function startAutoRefresh() {
    refreshInterval = setInterval(loadMonitoringData, 30000); // Refresh every 30 seconds
}

function refreshData() {
    const button = event.target.closest('button');
    const originalHTML = button.innerHTML;
    button.disabled = true;
    button.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Refreshing...';

    loadMonitoringData();

    setTimeout(() => {
        button.disabled = false;
        button.innerHTML = originalHTML;
    }, 1000);
}

function exportData() {
    const button = event.target.closest('button');
    const originalHTML = button.innerHTML;
    button.disabled = true;
    button.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Exporting...';

    // Create download link
    const link = document.createElement('a');
    link.href = '/admin/clearinghouse/monitoring/export';
    link.download = `submissions-${new Date().toISOString().split('T')[0]}.csv`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);

    setTimeout(() => {
        button.disabled = false;
        button.innerHTML = originalHTML;
    }, 1000);
}

function showSubmissionDetails(submissionId) {
    // This would open a modal with detailed submission information
    fetch(`/admin/clearinghouse/submissions/${submissionId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Show modal with submission details
                // console.log('Submission details:', data.submission);
                // Implementation for modal display would go here
            }
        })
        .catch(error => {
            // console.error('Error loading submission details:', error);
        });
}

// Cleanup on page unload
window.addEventListener('beforeunload', function() {
    if (refreshInterval) {
        clearInterval(refreshInterval);
    }
});
</script>
@endsection
