@extends('layouts.admin')

@section('title', 'Performance Metrics & KPIs')

@push('styles')
<style>
    .metric-card {
        background: white;
        border-radius: 12px;
        padding: 1.5rem;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07);
        border: none;
        transition: transform 0.3s ease;
    }

    .metric-card:hover {
        transform: translateY(-2px);
    }

    .metric-value {
        font-size: 2.5rem;
        font-weight: 700;
        color: #2c3e50;
        line-height: 1;
    }

    .metric-label {
        font-size: 0.875rem;
        color: #6c757d;
        margin-top: 0.5rem;
    }

    .metric-change {
        font-size: 0.75rem;
        font-weight: 600;
        padding: 2px 6px;
        border-radius: 10px;
        margin-left: 0.5rem;
    }

    .metric-change.positive {
        background: rgba(25, 135, 84, 0.1);
        color: #198754;
    }

    .metric-change.negative {
        background: rgba(220, 53, 69, 0.1);
        color: #dc3545;
    }

    .metric-change.neutral {
        background: rgba(108, 117, 125, 0.1);
        color: #6c757d;
    }

    .kpi-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 12px;
        padding: 1.5rem;
        position: relative;
        overflow: hidden;
    }

    .kpi-card::before {
        content: '';
        position: absolute;
        top: 0;
        right: 0;
        width: 100px;
        height: 100px;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 50%;
        transform: translate(30px, -30px);
    }

    .kpi-value {
        font-size: 3rem;
        font-weight: 700;
        line-height: 1;
        margin-bottom: 0.5rem;
    }

    .kpi-label {
        font-size: 0.875rem;
        opacity: 0.9;
    }

    .chart-container {
        position: relative;
        height: 300px;
        margin: auto;
    }

    .performance-indicator {
        display: inline-flex;
        align-items: center;
        padding: 0.375rem 0.75rem;
        background: white;
        border: 1px solid #dee2e6;
        border-radius: 20px;
        font-size: 0.875rem;
        font-weight: 500;
    }

    .performance-indicator.excellent {
        background: rgba(25, 135, 84, 0.1);
        border-color: #198754;
        color: #198754;
    }

    .performance-indicator.good {
        background: rgba(13, 202, 240, 0.1);
        border-color: #0dcaf0;
        color: #0dcaf0;
    }

    .performance-indicator.fair {
        background: rgba(255, 193, 7, 0.1);
        border-color: #ffc107;
        color: #ffc107;
    }

    .performance-indicator.poor {
        background: rgba(220, 53, 69, 0.1);
        border-color: #dc3545;
        color: #dc3545;
    }

    .trend-chart {
        height: 200px;
    }

    .time-range-selector {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 1rem;
        margin-bottom: 1rem;
    }

    .time-range-btn {
        border: none;
        background: transparent;
        color: #6c757d;
        padding: 0.5rem 1rem;
        border-radius: 6px;
        transition: all 0.3s ease;
    }

    .time-range-btn.active {
        background: #007bff;
        color: white;
    }

    .time-range-btn:hover:not(.active) {
        background: rgba(0, 123, 255, 0.1);
        color: #007bff;
    }

    .benchmark-comparison {
        background: white;
        border-radius: 8px;
        padding: 1rem;
        border: 1px solid #dee2e6;
        margin-bottom: 1rem;
    }

    .benchmark-bar {
        height: 8px;
        background: #e9ecef;
        border-radius: 4px;
        overflow: hidden;
        margin-top: 0.5rem;
    }

    .benchmark-fill {
        height: 100%;
        background: linear-gradient(90deg, #28a745 0%, #ffc107 70%, #dc3545 100%);
        transition: width 0.3s ease;
    }

    .alert-indicator {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        display: inline-block;
        margin-right: 6px;
    }

    .alert-indicator.critical { background: #dc3545; }
    .alert-indicator.warning { background: #ffc107; }
    .alert-indicator.info { background: #0dcaf0; }
</style>
@endpush

@section('content')
<div class="performance-metrics">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <div>
                    <h2 class="mb-1">Performance Metrics & KPIs</h2>
                    <p class="text-muted mb-0">Monitor clearinghouse integration performance and key indicators</p>
                </div>
                <div class="d-flex gap-2">
                    <select class="form-select" id="timeRange" style="width: auto;">
                        <option value="1h">Last Hour</option>
                        <option value="24h" selected>Last 24 Hours</option>
                        <option value="7d">Last 7 Days</option>
                        <option value="30d">Last 30 Days</option>
                        <option value="90d">Last 90 Days</option>
                    </select>
                    <button type="button" class="btn btn-outline-primary" onclick="refreshMetrics()">
                        <i class="fas fa-sync-alt me-2"></i>Refresh
                    </button>
                    <button type="button" class="btn btn-outline-secondary" onclick="exportMetrics()">
                        <i class="fas fa-download me-2"></i>Export
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Key Performance Indicators -->
    <div class="row mb-4">
        <div class="col-xl-3 col-lg-6 mb-4">
            <div class="kpi-card">
                <div class="kpi-value" id="successRate">98.5%</div>
                <div class="kpi-label">Overall Success Rate</div>
                <div class="mt-2">
                    <span class="performance-indicator excellent">
                        <i class="fas fa-trophy me-1"></i>Excellent
                    </span>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-6 mb-4">
            <div class="kpi-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                <div class="kpi-value" id="avgProcessingTime">2.3s</div>
                <div class="kpi-label">Avg Processing Time</div>
                <div class="mt-2">
                    <span class="performance-indicator good">
                        <i class="fas fa-clock me-1"></i>Good
                    </span>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-6 mb-4">
            <div class="kpi-card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                <div class="kpi-value" id="totalSubmissions">12,847</div>
                <div class="kpi-label">Total Submissions</div>
                <div class="mt-2">
                    <span class="performance-indicator excellent">
                        <i class="fas fa-chart-line me-1"></i>Trending Up
                    </span>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-6 mb-4">
            <div class="kpi-card" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
                <div class="kpi-value" id="uptime">99.9%</div>
                <div class="kpi-label">System Uptime</div>
                <div class="mt-2">
                    <span class="performance-indicator excellent">
                        <i class="fas fa-shield-alt me-1"></i>Reliable
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Detailed Metrics -->
    <div class="row mb-4">
        <div class="col-lg-3 mb-3">
            <div class="metric-card">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <div class="metric-value text-success" id="successfulSubmissions">12,543</div>
                        <div class="metric-label">Successful Submissions</div>
                    </div>
                    <div class="text-success">
                        <i class="fas fa-check-circle fa-2x opacity-50"></i>
                    </div>
                </div>
                <div class="mt-2">
                    <span class="metric-change positive">
                        <i class="fas fa-arrow-up me-1"></i>+5.2%
                    </span>
                </div>
            </div>
        </div>
        <div class="col-lg-3 mb-3">
            <div class="metric-card">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <div class="metric-value text-danger" id="failedSubmissions">304</div>
                        <div class="metric-label">Failed Submissions</div>
                    </div>
                    <div class="text-danger">
                        <i class="fas fa-exclamation-triangle fa-2x opacity-50"></i>
                    </div>
                </div>
                <div class="mt-2">
                    <span class="metric-change negative">
                        <i class="fas fa-arrow-down me-1"></i>-2.1%
                    </span>
                </div>
            </div>
        </div>
        <div class="col-lg-3 mb-3">
            <div class="metric-card">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <div class="metric-value text-warning" id="pendingSubmissions">156</div>
                        <div class="metric-label">Pending Submissions</div>
                    </div>
                    <div class="text-warning">
                        <i class="fas fa-clock fa-2x opacity-50"></i>
                    </div>
                </div>
                <div class="mt-2">
                    <span class="metric-change neutral">
                        <i class="fas fa-minus me-1"></i>0.8%
                    </span>
                </div>
            </div>
        </div>
        <div class="col-lg-3 mb-3">
            <div class="metric-card">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <div class="metric-value text-info" id="errorRate">2.4%</div>
                        <div class="metric-label">Error Rate</div>
                    </div>
                    <div class="text-info">
                        <i class="fas fa-chart-pie fa-2x opacity-50"></i>
                    </div>
                </div>
                <div class="mt-2">
                    <span class="metric-change positive">
                        <i class="fas fa-arrow-down me-1"></i>-0.3%
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row mb-4">
        <div class="col-lg-8 mb-4">
            <div class="metric-card">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-chart-line me-2"></i>Submission Trends
                    </h5>
                    <div class="time-range-selector">
                        <button class="time-range-btn active" data-range="1h">1H</button>
                        <button class="time-range-btn" data-range="24h">24H</button>
                        <button class="time-range-btn" data-range="7d">7D</button>
                        <button class="time-range-btn" data-range="30d">30D</button>
                    </div>
                </div>
                <div class="chart-container">
                    <canvas id="trendsChart"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-4 mb-4">
            <div class="metric-card">
                <h5 class="card-title mb-3">
                    <i class="fas fa-chart-pie me-2"></i>Submission Status
                </h5>
                <div class="chart-container">
                    <canvas id="statusChart" height="250"></canvas>
                </div>
                <div class="mt-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="d-flex align-items-center">
                            <span class="alert-indicator" style="background: #198754;"></span>
                            Successful
                        </span>
                        <span class="fw-semibold" id="statusSuccessful">12,543</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="d-flex align-items-center">
                            <span class="alert-indicator" style="background: #ffc107;"></span>
                            Pending
                        </span>
                        <span class="fw-semibold" id="statusPending">156</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="d-flex align-items-center">
                            <span class="alert-indicator" style="background: #dc3545;"></span>
                            Failed
                        </span>
                        <span class="fw-semibold" id="statusFailed">304</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Performance Benchmarks -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="metric-card">
                <h5 class="card-title mb-4">
                    <i class="fas fa-tachometer-alt me-2"></i>Performance Benchmarks
                </h5>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <div class="benchmark-comparison">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="fw-semibold">Response Time</span>
                                <span class="text-success fw-bold">2.3s</span>
                            </div>
                            <div class="benchmark-bar">
                                <div class="benchmark-fill" style="width: 85%"></div>
                            </div>
                            <div class="d-flex justify-content-between mt-1">
                                <small class="text-muted">Industry Avg: 3.2s</small>
                                <small class="text-success">15% faster</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="benchmark-comparison">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="fw-semibold">Success Rate</span>
                                <span class="text-success fw-bold">98.5%</span>
                            </div>
                            <div class="benchmark-bar">
                                <div class="benchmark-fill" style="width: 92%"></div>
                            </div>
                            <div class="d-flex justify-content-between mt-1">
                                <small class="text-muted">Industry Avg: 96.2%</small>
                                <small class="text-success">2.3% higher</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="benchmark-comparison">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="fw-semibold">Uptime</span>
                                <span class="text-success fw-bold">99.9%</span>
                            </div>
                            <div class="benchmark-bar">
                                <div class="benchmark-fill" style="width: 98%"></div>
                            </div>
                            <div class="d-flex justify-content-between mt-1">
                                <small class="text-muted">Industry Avg: 99.5%</small>
                                <small class="text-success">0.4% higher</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="benchmark-comparison">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="fw-semibold">Error Recovery</span>
                                <span class="text-success fw-bold">94.2%</span>
                            </div>
                            <div class="benchmark-bar">
                                <div class="benchmark-fill" style="width: 88%"></div>
                            </div>
                            <div class="d-flex justify-content-between mt-1">
                                <small class="text-muted">Industry Avg: 89.1%</small>
                                <small class="text-success">5.1% higher</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Provider Performance Table -->
    <div class="row">
        <div class="col-12">
            <div class="metric-card">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-building me-2"></i>Provider Performance
                    </h5>
                    <div class="d-flex gap-2">
                        <select class="form-select form-select-sm" id="providerSort" style="width: auto;">
                            <option value="success_rate">Sort by Success Rate</option>
                            <option value="volume">Sort by Volume</option>
                            <option value="response_time">Sort by Response Time</option>
                        </select>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Provider</th>
                                <th>Success Rate</th>
                                <th>Total Submissions</th>
                                <th>Avg Response Time</th>
                                <th>Error Rate</th>
                                <th>Status</th>
                                <th>Last Updated</th>
                            </tr>
                        </thead>
                        <tbody id="providerMetricsTable">
                            <!-- Provider metrics will be loaded here -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
let trendsChart;
let statusChart;

document.addEventListener('DOMContentLoaded', function() {
    initializeCharts();
    loadMetrics();
    setupEventListeners();
});

function initializeCharts() {
    // Trends Chart
    const trendsCtx = document.getElementById('trendsChart').getContext('2d');
    trendsChart = new Chart(trendsCtx, {
        type: 'line',
        data: {
            labels: [],
            datasets: [{
                label: 'Successful',
                data: [],
                borderColor: '#198754',
                backgroundColor: 'rgba(25, 135, 84, 0.1)',
                tension: 0.4
            }, {
                label: 'Failed',
                data: [],
                borderColor: '#dc3545',
                backgroundColor: 'rgba(220, 53, 69, 0.1)',
                tension: 0.4
            }, {
                label: 'Pending',
                data: [],
                borderColor: '#ffc107',
                backgroundColor: 'rgba(255, 193, 7, 0.1)',
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'top'
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });

    // Status Chart
    const statusCtx = document.getElementById('statusChart').getContext('2d');
    statusChart = new Chart(statusCtx, {
        type: 'doughnut',
        data: {
            labels: ['Successful', 'Pending', 'Failed'],
            datasets: [{
                data: [12543, 156, 304],
                backgroundColor: ['#198754', '#ffc107', '#dc3545'],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });
}

function loadMetrics() {
    const timeRange = document.getElementById('timeRange').value;

    fetch(`/admin/clearinghouse/metrics/data?range=${timeRange}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateKPIs(data.kpis);
                updateMetrics(data.metrics);
                updateCharts(data.charts);
                updateProviderTable(data.providers);
            }
        })
        .catch(error => {
            console.error('Error loading metrics:', error);
        });
}

function updateKPIs(kpis) {
    document.getElementById('successRate').textContent = kpis.successRate || '98.5%';
    document.getElementById('avgProcessingTime').textContent = kpis.avgProcessingTime || '2.3s';
    document.getElementById('totalSubmissions').textContent = (kpis.totalSubmissions || 12543).toLocaleString();
    document.getElementById('uptime').textContent = kpis.uptime || '99.9%';
}

function updateMetrics(metrics) {
    document.getElementById('successfulSubmissions').textContent = (metrics.successful || 12543).toLocaleString();
    document.getElementById('failedSubmissions').textContent = (metrics.failed || 304).toLocaleString();
    document.getElementById('pendingSubmissions').textContent = (metrics.pending || 156).toLocaleString();
    document.getElementById('errorRate').textContent = metrics.errorRate || '2.4%';

    // Update status chart values
    document.getElementById('statusSuccessful').textContent = (metrics.successful || 12543).toLocaleString();
    document.getElementById('statusPending').textContent = (metrics.pending || 156).toLocaleString();
    document.getElementById('statusFailed').textContent = (metrics.failed || 304).toLocaleString();
}

function updateCharts(charts) {
    // Update trends chart
    trendsChart.data.labels = charts.trends.labels || [];
    trendsChart.data.datasets[0].data = charts.trends.successful || [];
    trendsChart.data.datasets[1].data = charts.trends.failed || [];
    trendsChart.data.datasets[2].data = charts.trends.pending || [];
    trendsChart.update();

    // Update status chart
    statusChart.data.datasets[0].data = [
        charts.status.successful || 12543,
        charts.status.pending || 156,
        charts.status.failed || 304
    ];
    statusChart.update();
}

function updateProviderTable(providers) {
    const tbody = document.getElementById('providerMetricsTable');
    tbody.innerHTML = providers.map(provider => `
        <tr>
            <td>
                <div class="d-flex align-items-center">
                    <div class="provider-logo me-3" style="width: 32px; height: 32px; font-size: 0.75rem;">
                        ${provider.name.charAt(0)}
                    </div>
                    <div>
                        <div class="fw-semibold">${provider.name}</div>
                        <small class="text-muted">${provider.code}</small>
                    </div>
                </div>
            </td>
            <td>
                <span class="badge bg-${getSuccessRateColor(provider.successRate)}">
                    ${provider.successRate}%
                </span>
            </td>
            <td>${provider.totalSubmissions.toLocaleString()}</td>
            <td>${provider.avgResponseTime}s</td>
            <td>
                <span class="badge bg-${getErrorRateColor(provider.errorRate)}">
                    ${provider.errorRate}%
                </span>
            </td>
            <td>
                <span class="badge bg-${provider.status === 'active' ? 'success' : 'secondary'}">
                    ${provider.status}
                </span>
            </td>
            <td>
                <small class="text-muted">${new Date(provider.lastUpdated).toLocaleString()}</small>
            </td>
        </tr>
    `).join('');
}

function getSuccessRateColor(rate) {
    if (rate >= 98) return 'success';
    if (rate >= 95) return 'warning';
    return 'danger';
}

function getErrorRateColor(rate) {
    if (rate <= 2) return 'success';
    if (rate <= 5) return 'warning';
    return 'danger';
}

function setupEventListeners() {
    // Time range selector
    document.getElementById('timeRange').addEventListener('change', loadMetrics);

    // Time range buttons
    document.querySelectorAll('.time-range-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.time-range-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            // Update chart data based on selected range
        });
    });

    // Provider sort
    document.getElementById('providerSort').addEventListener('change', function() {
        // Sort provider table
        loadMetrics();
    });
}

function refreshMetrics() {
    const button = event.target.closest('button');
    const originalHTML = button.innerHTML;
    button.disabled = true;
    button.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Refreshing...';

    loadMetrics();

    setTimeout(() => {
        button.disabled = false;
        button.innerHTML = originalHTML;
    }, 1000);
}

function exportMetrics() {
    const timeRange = document.getElementById('timeRange').value;
    const link = document.createElement('a');
    link.href = `/admin/clearinghouse/metrics/export?range=${timeRange}`;
    link.download = `metrics-${timeRange}-${new Date().toISOString().split('T')[0]}.csv`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}
</script>
@endsection
