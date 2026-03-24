@extends('layouts.admin')

@section('title', 'Compliance Dashboard - Payer Rules')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-0">
                        <i class="fas fa-shield-alt me-2"></i>Compliance Dashboard
                    </h4>
                    <p class="card-subtitle mb-0">Monitor payer rules compliance and effectiveness</p>
                </div>
                <div class="card-body">
                    <!-- Filters -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <label for="start_date" class="form-label">Start Date</label>
                            <input type="date" class="form-control" id="start_date" name="start_date"
                                   value="{{ $startDate }}">
                        </div>
                        <div class="col-md-3">
                            <label for="end_date" class="form-label">End Date</label>
                            <input type="date" class="form-control" id="end_date" name="end_date"
                                   value="{{ $endDate }}">
                        </div>
                        <div class="col-md-3">
                            <label for="payer_id" class="form-label">Payer</label>
                            <select class="form-control" id="payer_id" name="payer_id">
                                <option value="">All Payers</option>
                                @foreach($payers as $payer)
                                    <option value="{{ $payer->id }}" {{ $payerId == $payer->id ? 'selected' : '' }}>
                                        {{ $payer->name }} ({{ $payer->payer_id }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="button" class="btn btn-primary me-2" onclick="refreshDashboard()">
                                <i class="fas fa-sync-alt"></i> Refresh
                            </button>
                            <button type="button" class="btn btn-outline-secondary" onclick="exportReport()">
                                <i class="fas fa-download"></i> Export
                            </button>
                        </div>
                    </div>

                    <!-- Key Metrics Cards -->
                    <div class="row mb-4">
                        <div class="col-md-2">
                            <div class="card bg-primary text-white">
                                <div class="card-body">
                                    <h5 class="card-title">{{ number_format($complianceMetrics['total_applications']) }}</h5>
                                    <p class="card-text">Total Applications</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="card bg-success text-white">
                                <div class="card-body">
                                    <h5 class="card-title">{{ $complianceMetrics['trigger_rate'] }}%</h5>
                                    <p class="card-text">Trigger Rate</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="card bg-warning text-white">
                                <div class="card-body">
                                    <h5 class="card-title">{{ $complianceMetrics['denial_rate'] }}%</h5>
                                    <p class="card-text">Denial Rate</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="card bg-info text-white">
                                <div class="card-body">
                                    <h5 class="card-title">{{ $complianceMetrics['warning_rate'] }}%</h5>
                                    <p class="card-text">Warning Rate</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="card bg-danger text-white">
                                <div class="card-body">
                                    <h5 class="card-title">{{ $complianceMetrics['hipaa_compliance_rate'] }}%</h5>
                                    <p class="card-text">HIPAA Compliance</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="card bg-secondary text-white">
                                <div class="card-body">
                                    <h5 class="card-title">{{ $complianceMetrics['avg_execution_time'] }}ms</h5>
                                    <p class="card-text">Avg Execution Time</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Charts Row -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Rule Effectiveness</h5>
                                </div>
                                <div class="card-body">
                                    <canvas id="ruleEffectivenessChart" width="400" height="300"></canvas>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Outcome Distribution</h5>
                                </div>
                                <div class="card-body">
                                    <canvas id="outcomeDistributionChart" width="400" height="300"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Rules Needing Attention -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Rules Needing Attention</h5>
                                    <small class="text-muted">Rules with effectiveness score below 30%</small>
                                </div>
                                <div class="card-body">
                                    <div id="rulesNeedingAttentionTable">
                                        <div class="text-center">
                                            <div class="spinner-border" role="status">
                                                <span class="visually-hidden">Loading...</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- HIPAA Compliance Section -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">HIPAA Compliance Metrics</h5>
                                </div>
                                <div class="card-body">
                                    <div id="hipaaComplianceMetrics">
                                        <div class="text-center">
                                            <div class="spinner-border" role="status">
                                                <span class="visually-hidden">Loading...</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Audit Trail Summary -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Audit Trail Summary</h5>
                                </div>
                                <div class="card-body">
                                    <canvas id="auditTrailChart" width="400" height="200"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
let ruleEffectivenessChart;
let outcomeDistributionChart;
let auditTrailChart;

$(document).ready(function() {
    loadDashboardData();
});

function refreshDashboard() {
    const startDate = $('#start_date').val();
    const endDate = $('#end_date').val();
    const payerId = $('#payer_id').val();

    // Update URL without page reload
    const url = new URL(window.location);
    url.searchParams.set('start_date', startDate);
    url.searchParams.set('end_date', endDate);
    if (payerId) {
        url.searchParams.set('payer_id', payerId);
    } else {
        url.searchParams.delete('payer_id');
    }
    window.history.pushState({}, '', url);

    loadDashboardData();
}

function loadDashboardData() {
    const startDate = $('#start_date').val();
    const endDate = $('#end_date').val();
    const payerId = $('#payer_id').val();

    // Load comprehensive metrics
    $.get('/admin/compliance/metrics', {
        start_date: startDate,
        end_date: endDate,
        payer_id: payerId
    }).done(function(data) {
        updateCharts(data);
    });

    // Load rules needing attention
    $.get('/admin/compliance/rules-needing-attention', {
        threshold: 30.0,
        limit: 10
    }).done(function(data) {
        updateRulesNeedingAttention(data);
    });

    // Load HIPAA compliance metrics
    $.get('/admin/compliance/hipaa-compliance', {
        start_date: startDate,
        end_date: endDate
    }).done(function(data) {
        updateHipaaMetrics(data);
    });

    // Load audit trail
    $.get('/admin/compliance/audit-trail', {
        start_date: startDate,
        end_date: endDate
    }).done(function(data) {
        updateAuditTrail(data);
    });
}

function updateCharts(data) {
    // Rule Effectiveness Chart
    const ctx1 = document.getElementById('ruleEffectivenessChart').getContext('2d');
    if (ruleEffectivenessChart) {
        ruleEffectivenessChart.destroy();
    }

    const ruleLabels = data.rule_performance.slice(0, 10).map(rule => rule.rule_name);
    const ruleScores = data.rule_performance.slice(0, 10).map(rule => rule.effectiveness_score);

    ruleEffectivenessChart = new Chart(ctx1, {
        type: 'bar',
        data: {
            labels: ruleLabels,
            datasets: [{
                label: 'Effectiveness Score',
                data: ruleScores,
                backgroundColor: 'rgba(54, 162, 235, 0.5)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100
                }
            }
        }
    });

    // Outcome Distribution Chart
    const ctx2 = document.getElementById('outcomeDistributionChart').getContext('2d');
    if (outcomeDistributionChart) {
        outcomeDistributionChart.destroy();
    }

    const outcomeLabels = data.outcome_analysis.outcome_distribution.map(item => item.outcome);
    const outcomeData = data.outcome_analysis.outcome_distribution.map(item => item.percentage);

    outcomeDistributionChart = new Chart(ctx2, {
        type: 'doughnut',
        data: {
            labels: outcomeLabels,
            datasets: [{
                data: outcomeData,
                backgroundColor: [
                    'rgba(255, 99, 132, 0.5)',
                    'rgba(54, 162, 235, 0.5)',
                    'rgba(255, 205, 86, 0.5)',
                    'rgba(75, 192, 192, 0.5)'
                ],
                borderColor: [
                    'rgba(255, 99, 132, 1)',
                    'rgba(54, 162, 235, 1)',
                    'rgba(255, 205, 86, 1)',
                    'rgba(75, 192, 192, 1)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true
        }
    });
}

function updateRulesNeedingAttention(data) {
    let html = '';

    if (data.rules.length === 0) {
        html = '<div class="alert alert-success">All rules are performing well!</div>';
    } else {
        html = '<div class="table-responsive"><table class="table table-striped">';
        html += '<thead><tr><th>Rule Name</th><th>Payer</th><th>Effectiveness Score</th><th>Actions</th></tr></thead>';
        html += '<tbody>';

        data.rules.forEach(function(rule) {
            html += `<tr>
                <td>${rule.rule_name}</td>
                <td>${rule.payer_name}</td>
                <td><span class="badge bg-danger">${rule.effectiveness_score}%</span></td>
                <td>
                    <button class="btn btn-sm btn-outline-primary" onclick="viewRuleReport(${rule.rule_id})">
                        View Report
                    </button>
                </td>
            </tr>`;
        });

        html += '</tbody></table></div>';
    }

    $('#rulesNeedingAttentionTable').html(html);
}

function updateHipaaMetrics(data) {
    const html = `
        <div class="row">
            <div class="col-md-3">
                <div class="card ${data.hipaa_compliance_rate >= 95 ? 'border-success' : 'border-danger'}">
                    <div class="card-body text-center">
                        <h4 class="text-${data.hipaa_compliance_rate >= 95 ? 'success' : 'danger'}">
                            ${data.hipaa_compliance_rate}%
                        </h4>
                        <p class="mb-0">HIPAA Compliance Rate</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body text-center">
                        <h4 class="text-primary">${data.phi_records_count}</h4>
                        <p class="mb-0">PHI Records Processed</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card ${data.retention_compliance_rate >= 95 ? 'border-success' : 'border-warning'}">
                    <div class="card-body text-center">
                        <h4 class="text-${data.retention_compliance_rate >= 95 ? 'success' : 'warning'}">
                            ${data.retention_compliance_rate}%
                        </h4>
                        <p class="mb-0">Retention Compliance</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body text-center">
                        <h4 class="text-info">${data.user_acknowledgment_rate}%</h4>
                        <p class="mb-0">User Acknowledgments</p>
                    </div>
                </div>
            </div>
        </div>
    `;

    $('#hipaaComplianceMetrics').html(html);
}

function updateAuditTrail(data) {
    const ctx = document.getElementById('auditTrailChart').getContext('2d');
    if (auditTrailChart) {
        auditTrailChart.destroy();
    }

    const dates = data.audit_trail.map(item => item.date);
    const completeness = data.audit_trail.map(item => item.audit_completeness);

    auditTrailChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: dates,
            datasets: [{
                label: 'Audit Completeness (%)',
                data: completeness,
                borderColor: 'rgba(75, 192, 192, 1)',
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100
                }
            }
        }
    });
}

function viewRuleReport(ruleId) {
    const startDate = $('#start_date').val();
    const endDate = $('#end_date').val();

    $.get(`/admin/compliance/rule-report/${ruleId}`, {
        start_date: startDate,
        end_date: endDate
    }).done(function(data) {
        // Show modal with rule report
        showRuleReportModal(data);
    });
}

function showRuleReportModal(data) {
    // Implementation for rule report modal
    // console.log('Rule report:', data);
    // You can implement a modal here to show detailed rule information
}

function exportReport() {
    const startDate = $('#start_date').val();
    const endDate = $('#end_date').val();
    const payerId = $('#payer_id').val();

    const url = `/admin/compliance/export?start_date=${startDate}&end_date=${endDate}&format=json`;
    if (payerId) {
        url += `&payer_id=${payerId}`;
    }

    window.open(url, '_blank');
}
</script>
@endpush
