@extends('layouts.admin')

@section('title', 'Waitlist Analytics')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h2 class="mb-1">Waitlist Analytics</h2>
                <p class="text-muted mb-0">Comprehensive analytics and insights for waitlist management</p>
            </div>
            <div class="d-flex gap-2">
                <select class="form-select" id="timeRangeSelect">
                    <option value="7">Last 7 days</option>
                    <option value="30" selected>Last 30 days</option>
                    <option value="90">Last 90 days</option>
                    <option value="365">Last year</option>
                </select>
                <button class="btn btn-outline-primary" onclick="refreshAnalytics()">
                    <i class="fas fa-sync-alt me-2"></i>Refresh
                </button>
                <button class="btn btn-primary" onclick="exportAnalytics()">
                    <i class="fas fa-download me-2"></i>Export Report
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Key Metrics Row -->
<div class="row mb-4">
    <div class="col-xl-3 col-lg-6 mb-4">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="card-title mb-1 text-muted">Average Wait Time</h6>
                        <h3 class="mb-0 text-primary" id="avgWaitTime">0 days</h3>
                        <small class="text-success">
                            <i class="fas fa-arrow-down me-1"></i>-8% vs last period
                        </small>
                    </div>
                    <div class="text-primary">
                        <i class="fas fa-clock fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-lg-6 mb-4">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="card-title mb-1 text-muted">Fill Rate</h6>
                        <h3 class="mb-0 text-success" id="fillRate">0%</h3>
                        <small class="text-success">
                            <i class="fas fa-arrow-up me-1"></i>+12% vs last period
                        </small>
                    </div>
                    <div class="text-success">
                        <i class="fas fa-chart-line fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-lg-6 mb-4">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="card-title mb-1 text-muted">Patient Satisfaction</h6>
                        <h3 class="mb-0 text-info" id="satisfactionScore">0/5</h3>
                        <small class="text-success">
                            <i class="fas fa-arrow-up me-1"></i>+0.3 vs last period
                        </small>
                    </div>
                    <div class="text-info">
                        <i class="fas fa-star fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-lg-6 mb-4">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="card-title mb-1 text-muted">Priority Overrides</h6>
                        <h3 class="mb-0 text-warning" id="priorityOverrides">0</h3>
                        <small class="text-muted">Admin interventions</small>
                    </div>
                    <div class="text-warning">
                        <i class="fas fa-exclamation-triangle fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Charts Row -->
<div class="row mb-4">
    <div class="col-lg-8 mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 py-3">
                <h5 class="mb-0">Wait Time Trends</h5>
            </div>
            <div class="card-body">
                <canvas id="waitTimeChart" height="300"></canvas>
            </div>
        </div>
    </div>

    <div class="col-lg-4 mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 py-3">
                <h5 class="mb-0">Priority Distribution</h5>
            </div>
            <div class="card-body">
                <canvas id="priorityChart" height="300"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Additional Analytics -->
<div class="row mb-4">
    <div class="col-lg-6 mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 py-3">
                <h5 class="mb-0">Fill Rate by Specialty</h5>
            </div>
            <div class="card-body">
                <canvas id="specialtyFillRateChart" height="250"></canvas>
            </div>
        </div>
    </div>

    <div class="col-lg-6 mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 py-3">
                <h5 class="mb-0">Patient Satisfaction Trends</h5>
            </div>
            <div class="card-body">
                <canvas id="satisfactionChart" height="250"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Insights and Recommendations -->
<div class="row mb-4">
    <div class="col-lg-6 mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 py-3">
                <h5 class="mb-0">Key Insights</h5>
            </div>
            <div class="card-body">
                <div id="insightsList">
                    <div class="d-flex align-items-start mb-3">
                        <div class="flex-shrink-0 me-3">
                            <i class="fas fa-lightbulb text-warning fa-lg"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h6 class="mb-1">Loading insights...</h6>
                            <p class="text-muted small mb-0">Analyzing waitlist data</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-6 mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 py-3">
                <h5 class="mb-0">Recommendations</h5>
            </div>
            <div class="card-body">
                <div id="recommendationsList">
                    <div class="d-flex align-items-start mb-3">
                        <div class="flex-shrink-0 me-3">
                            <i class="fas fa-clipboard-check text-success fa-lg"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h6 class="mb-1">Loading recommendations...</h6>
                            <p class="text-muted small mb-0">Generating optimization suggestions</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Top Performers and Bottlenecks -->
<div class="row">
    <div class="col-lg-6 mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 py-3">
                <h5 class="mb-0">Top Performing Doctors</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr>
                                <th>Doctor</th>
                                <th>Fill Rate</th>
                                <th>Avg Wait Time</th>
                            </tr>
                        </thead>
                        <tbody id="topPerformersTable">
                            <tr>
                                <td colspan="3" class="text-center text-muted py-3">Loading...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-6 mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 py-3">
                <h5 class="mb-0">Bottlenecks</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr>
                                <th>Issue</th>
                                <th>Impact</th>
                                <th>Recommendation</th>
                            </tr>
                        </thead>
                        <tbody id="bottlenecksTable">
                            <tr>
                                <td colspan="3" class="text-center text-muted py-3">Loading...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
let waitTimeChart, priorityChart, specialtyFillRateChart, satisfactionChart;

document.addEventListener('DOMContentLoaded', function() {
    initializeCharts();
    loadAnalyticsData();

    // Time range change handler
    document.getElementById('timeRangeSelect').addEventListener('change', function() {
        loadAnalyticsData();
    });
});

function initializeCharts() {
    // Wait Time Trends Chart
    const waitTimeCtx = document.getElementById('waitTimeChart').getContext('2d');
    waitTimeChart = new Chart(waitTimeCtx, {
        type: 'line',
        data: {
            labels: [],
            datasets: [{
                label: 'Average Wait Time (days)',
                data: [],
                borderColor: '#007bff',
                backgroundColor: 'rgba(0, 123, 255, 0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Days'
                    }
                }
            }
        }
    });

    // Priority Distribution Chart
    const priorityCtx = document.getElementById('priorityChart').getContext('2d');
    priorityChart = new Chart(priorityCtx, {
        type: 'doughnut',
        data: {
            labels: ['Low', 'Medium', 'High', 'Urgent'],
            datasets: [{
                data: [],
                backgroundColor: [
                    '#6c757d',
                    '#17a2b8',
                    '#ffc107',
                    '#dc3545'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });

    // Specialty Fill Rate Chart
    const specialtyCtx = document.getElementById('specialtyFillRateChart').getContext('2d');
    specialtyFillRateChart = new Chart(specialtyCtx, {
        type: 'bar',
        data: {
            labels: [],
            datasets: [{
                label: 'Fill Rate (%)',
                data: [],
                backgroundColor: '#28a745',
                borderColor: '#28a745',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100,
                    title: {
                        display: true,
                        text: 'Fill Rate (%)'
                    }
                }
            }
        }
    });

    // Satisfaction Trends Chart
    const satisfactionCtx = document.getElementById('satisfactionChart').getContext('2d');
    satisfactionChart = new Chart(satisfactionCtx, {
        type: 'line',
        data: {
            labels: [],
            datasets: [{
                label: 'Patient Satisfaction',
                data: [],
                borderColor: '#17a2b8',
                backgroundColor: 'rgba(23, 162, 184, 0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    max: 5,
                    title: {
                        display: true,
                        text: 'Rating (1-5)'
                    }
                }
            }
        }
    });
}

function loadAnalyticsData() {
    const timeRange = document.getElementById('timeRangeSelect').value;

    fetch(`/api/admin/waitlist/analytics?timeframe=${timeRange}`)
        .then(response => response.json())
        .then(data => {
            updateMetrics(data.metrics);
            updateCharts(data.charts);
            updateInsights(data.insights);
            updateRecommendations(data.recommendations);
            updateTopPerformers(data.topPerformers);
            updateBottlenecks(data.bottlenecks);
        })
        .catch(error => {
            console.error('Error loading analytics data:', error);
            showAlert('Error loading analytics data', 'danger');
        });
}

function updateMetrics(metrics) {
    document.getElementById('avgWaitTime').textContent = `${metrics.avgWaitTime || 0} days`;
    document.getElementById('fillRate').textContent = `${metrics.fillRate || 0}%`;
    document.getElementById('satisfactionScore').textContent = `${metrics.satisfactionScore || 0}/5`;
    document.getElementById('priorityOverrides').textContent = metrics.priorityOverrides || 0;
}

function updateCharts(charts) {
    // Update Wait Time Chart
    waitTimeChart.data.labels = charts.waitTime.labels;
    waitTimeChart.data.datasets[0].data = charts.waitTime.data;
    waitTimeChart.update();

    // Update Priority Chart
    priorityChart.data.datasets[0].data = charts.priority.data;
    priorityChart.update();

    // Update Specialty Fill Rate Chart
    specialtyFillRateChart.data.labels = charts.specialty.labels;
    specialtyFillRateChart.data.datasets[0].data = charts.specialty.data;
    specialtyFillRateChart.update();

    // Update Satisfaction Chart
    satisfactionChart.data.labels = charts.satisfaction.labels;
    satisfactionChart.data.datasets[0].data = charts.satisfaction.data;
    satisfactionChart.update();
}

function updateInsights(insights) {
    const container = document.getElementById('insightsList');
    container.innerHTML = '';

    insights.forEach(insight => {
        const insightHtml = `
            <div class="d-flex align-items-start mb-3">
                <div class="flex-shrink-0 me-3">
                    <i class="fas fa-${insight.icon} text-${insight.color} fa-lg"></i>
                </div>
                <div class="flex-grow-1">
                    <h6 class="mb-1">${insight.title}</h6>
                    <p class="text-muted small mb-0">${insight.description}</p>
                </div>
            </div>
        `;
        container.insertAdjacentHTML('beforeend', insightHtml);
    });
}

function updateRecommendations(recommendations) {
    const container = document.getElementById('recommendationsList');
    container.innerHTML = '';

    recommendations.forEach(rec => {
        const recHtml = `
            <div class="d-flex align-items-start mb-3">
                <div class="flex-shrink-0 me-3">
                    <i class="fas fa-${rec.icon} text-${rec.color} fa-lg"></i>
                </div>
                <div class="flex-grow-1">
                    <h6 class="mb-1">${rec.title}</h6>
                    <p class="text-muted small mb-0">${rec.description}</p>
                    ${rec.action ? `<button class="btn btn-sm btn-outline-${rec.color} mt-2">${rec.action}</button>` : ''}
                </div>
            </div>
        `;
        container.insertAdjacentHTML('beforeend', recHtml);
    });
}

function updateTopPerformers(performers) {
    const tbody = document.getElementById('topPerformersTable');
    tbody.innerHTML = '';

    performers.forEach(performer => {
        const row = `
            <tr>
                <td>${performer.name}</td>
                <td>${performer.fillRate}%</td>
                <td>${performer.avgWaitTime} days</td>
            </tr>
        `;
        tbody.insertAdjacentHTML('beforeend', row);
    });
}

function updateBottlenecks(bottlenecks) {
    const tbody = document.getElementById('bottlenecksTable');
    tbody.innerHTML = '';

    bottlenecks.forEach(bottleneck => {
        const row = `
            <tr>
                <td>${bottleneck.issue}</td>
                <td><span class="badge bg-${bottleneck.severity}">${bottleneck.impact}</span></td>
                <td>${bottleneck.recommendation}</td>
            </tr>
        `;
        tbody.insertAdjacentHTML('beforeend', row);
    });
}

function refreshAnalytics() {
    loadAnalyticsData();
    showAlert('Analytics refreshed', 'success');
}

function exportAnalytics() {
    const timeRange = document.getElementById('timeRangeSelect').value;
    window.open(`/api/admin/waitlist/analytics/export?timeframe=${timeRange}`, '_blank');
}

function showAlert(message, type = 'info') {
    const alertHtml = `
        <div class="alert alert-${type} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    const container = document.querySelector('.admin-content .p-4');
    container.insertAdjacentHTML('afterbegin', alertHtml);

    setTimeout(() => {
        const alert = container.querySelector('.alert');
        if (alert) alert.remove();
    }, 5000);
}
</script>
@endsection
