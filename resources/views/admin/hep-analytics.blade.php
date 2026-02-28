@extends('layouts.admin')

@section('title', 'HEP Analytics & Reporting')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">HEP Analytics & Reporting Dashboard</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" data-card-widget="collapse">
                            <i class="fas fa-minus"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Filters -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <label for="hospital-filter">Hospital</label>
                            <select id="hospital-filter" class="form-control">
                                <option value="">All Hospitals</option>
                                <!-- Hospital options will be populated via JavaScript -->
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="period-filter">Time Period</label>
                            <select id="period-filter" class="form-control">
                                <option value="7d">Last 7 days</option>
                                <option value="30d" selected>Last 30 days</option>
                                <option value="90d">Last 90 days</option>
                                <option value="1y">Last year</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label>&nbsp;</label>
                            <button id="refresh-btn" class="btn btn-primary btn-block">
                                <i class="fas fa-sync-alt"></i> Refresh Data
                            </button>
                        </div>
                        <div class="col-md-3">
                            <label>&nbsp;</label>
                            <div class="btn-group btn-block">
                                <button id="export-research-btn" class="btn btn-success">
                                    <i class="fas fa-download"></i> Export Research
                                </button>
                                <button id="clear-cache-btn" class="btn btn-warning">
                                    <i class="fas fa-trash"></i> Clear Cache
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Loading indicator -->
                    <div id="loading-indicator" class="text-center" style="display: none;">
                        <div class="spinner-border" role="status">
                            <span class="sr-only">Loading...</span>
                        </div>
                        <p>Loading analytics data...</p>
                    </div>

                    <!-- Error message -->
                    <div id="error-message" class="alert alert-danger" style="display: none;"></div>

                    <!-- Analytics Content -->
                    <div id="analytics-content">
                        <!-- Clinical Effectiveness Section -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header">
                                        <h4 class="card-title">Clinical Effectiveness by Diagnosis</h4>
                                    </div>
                                    <div class="card-body">
                                        <div id="clinical-effectiveness-chart" style="height: 400px;">
                                            <canvas id="clinical-effectiveness-canvas"></canvas>
                                        </div>
                                        <div class="mt-3">
                                            <div class="row" id="clinical-metrics">
                                                <!-- Metrics will be populated via JavaScript -->
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Adherence Patterns Section -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h4 class="card-title">Adherence Distribution</h4>
                                    </div>
                                    <div class="card-body">
                                        <div id="adherence-chart" style="height: 300px;">
                                            <canvas id="adherence-canvas"></canvas>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h4 class="card-title">Weekly Activity Patterns</h4>
                                    </div>
                                    <div class="card-body">
                                        <div id="weekly-patterns-chart" style="height: 300px;">
                                            <canvas id="weekly-patterns-canvas"></canvas>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Clinician Metrics Section -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header">
                                        <h4 class="card-title">Clinician Performance Metrics</h4>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table id="clinician-metrics-table" class="table table-bordered table-striped">
                                                <thead>
                                                    <tr>
                                                        <th>Clinician</th>
                                                        <th>Total Programs</th>
                                                        <th>Avg Duration (Weeks)</th>
                                                        <th>Completion Rate</th>
                                                        <th>Efficiency Score</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <!-- Table data will be populated via JavaScript -->
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Key Metrics Summary -->
                        <div class="row">
                            <div class="col-md-3">
                                <div class="small-box bg-info">
                                    <div class="inner">
                                        <h3 id="total-programs">0</h3>
                                        <p>Total Programs</p>
                                    </div>
                                    <div class="icon">
                                        <i class="fas fa-clipboard-list"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="small-box bg-success">
                                    <div class="inner">
                                        <h3 id="overall-success-rate">0%</h3>
                                        <p>Success Rate</p>
                                    </div>
                                    <div class="icon">
                                        <i class="fas fa-check-circle"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="small-box bg-warning">
                                    <div class="inner">
                                        <h3 id="avg-adherence">0%</h3>
                                        <p>Avg Adherence</p>
                                    </div>
                                    <div class="icon">
                                        <i class="fas fa-clock"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="small-box bg-danger">
                                    <div class="inner">
                                        <h3 id="avg-pain-reduction">0</h3>
                                        <p>Avg Pain Reduction</p>
                                    </div>
                                    <div class="icon">
                                        <i class="fas fa-chart-line"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Export Modal -->
<div class="modal fade" id="exportModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Export HEP Data</h4>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="export-form">
                    <div class="form-group">
                        <label for="export-format">Format</label>
                        <select id="export-format" name="format" class="form-control" required>
                            <option value="csv">CSV</option>
                            <option value="json">JSON</option>
                            <option value="xml">XML</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="export-anonymize">Anonymize Data</label>
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="export-anonymize" name="anonymize" checked>
                            <label class="custom-control-label" for="export-anonymize">Remove personally identifiable information</label>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Filters</label>
                        <div class="row">
                            <div class="col-md-6">
                                <input type="date" class="form-control" name="filters[date_from]" placeholder="From Date">
                            </div>
                            <div class="col-md-6">
                                <input type="date" class="form-control" name="filters[date_to]" placeholder="To Date">
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="confirm-export">Export</button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
$(document).ready(function() {
    let clinicalChart = null;
    let adherenceChart = null;
    let weeklyPatternsChart = null;

    // Load initial data
    loadAnalyticsData();

    // Refresh button
    $('#refresh-btn').click(function() {
        loadAnalyticsData();
    });

    // Clear cache button
    $('#clear-cache-btn').click(function() {
        clearAnalyticsCache();
    });

    // Export research button
    $('#export-research-btn').click(function() {
        $('#exportModal').modal('show');
    });

    // Confirm export
    $('#confirm-export').click(function() {
        performExport();
    });

    function loadAnalyticsData() {
        const hospitalId = $('#hospital-filter').val();
        const period = $('#period-filter').val();

        $('#loading-indicator').show();
        $('#error-message').hide();
        $('#analytics-content').hide();

        $.ajax({
            url: '/api/hep/analytics/dashboard',
            method: 'GET',
            data: {
                hospital_id: hospitalId,
                period: period
            },
            success: function(response) {
                if (response.success) {
                    updateDashboard(response.data);
                } else {
                    showError(response.message || 'Failed to load analytics data');
                }
            },
            error: function(xhr) {
                showError('Failed to load analytics data: ' + xhr.responseJSON?.message);
            },
            complete: function() {
                $('#loading-indicator').hide();
                $('#analytics-content').show();
            }
        });
    }

    function updateDashboard(data) {
        // Update key metrics
        $('#total-programs').text(data.clinical_effectiveness.total_programs_analyzed);
        $('#overall-success-rate').text(data.clinical_effectiveness.overall_success_rate + '%');
        $('#avg-adherence').text(data.adherence_patterns.average_adherence_rate + '%');
        $('#avg-pain-reduction').text(data.clinical_effectiveness.pain_reduction_average);

        // Update clinical effectiveness chart
        updateClinicalEffectivenessChart(data.clinical_effectiveness.diagnosis_effectiveness);

        // Update adherence chart
        updateAdherenceChart(data.adherence_patterns.adherence_distribution);

        // Update weekly patterns chart
        updateWeeklyPatternsChart(data.adherence_patterns.weekly_patterns);

        // Update clinician metrics table
        updateClinicianMetricsTable(data.clinician_metrics.clinician_performance);
    }

    function updateClinicalEffectivenessChart(diagnosisData) {
        const ctx = document.getElementById('clinical-effectiveness-canvas').getContext('2d');

        if (clinicalChart) {
            clinicalChart.destroy();
        }

        const labels = diagnosisData.map(d => d.diagnosis || 'Unknown');
        const successRates = diagnosisData.map(d => d.success_rate);

        clinicalChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Success Rate (%)',
                    data: successRates,
                    backgroundColor: 'rgba(54, 162, 235, 0.5)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100
                    }
                }
            }
        });
    }

    function updateAdherenceChart(adherenceData) {
        const ctx = document.getElementById('adherence-canvas').getContext('2d');

        if (adherenceChart) {
            adherenceChart.destroy();
        }

        const labels = ['Excellent (90-100%)', 'Good (70-89%)', 'Fair (50-69%)', 'Poor (<50%)'];
        const data = [
            adherenceData.excellent || 0,
            adherenceData.good || 0,
            adherenceData.fair || 0,
            adherenceData.poor || 0
        ];

        adherenceChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: data,
                    backgroundColor: [
                        'rgba(40, 167, 69, 0.8)',
                        'rgba(23, 162, 184, 0.8)',
                        'rgba(255, 193, 7, 0.8)',
                        'rgba(220, 53, 69, 0.8)'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });
    }

    function updateWeeklyPatternsChart(weeklyData) {
        const ctx = document.getElementById('weekly-patterns-canvas').getContext('2d');

        if (weeklyPatternsChart) {
            weeklyPatternsChart.destroy();
        }

        const days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        const sessions = days.map(day => {
            const dayData = weeklyData.find(d => d.day_name === day);
            return dayData ? dayData.sessions : 0;
        });

        weeklyPatternsChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: days,
                datasets: [{
                    label: 'Sessions per Day',
                    data: sessions,
                    borderColor: 'rgba(75, 192, 192, 1)',
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }

    function updateClinicianMetricsTable(clinicianData) {
        const tbody = $('#clinician-metrics-table tbody');
        tbody.empty();

        clinicianData.forEach(clinician => {
            tbody.append(`
                <tr>
                    <td>${clinician.clinician_name}</td>
                    <td>${clinician.total_programs}</td>
                    <td>${clinician.average_duration_weeks}</td>
                    <td>${clinician.completion_rate}%</td>
                    <td>${clinician.efficiency_score}</td>
                </tr>
            `);
        });
    }

    function clearAnalyticsCache() {
        $.ajax({
            url: '/api/hep/analytics/clear-cache',
            method: 'POST',
            success: function(response) {
                if (response.success) {
                    alert('Analytics cache cleared successfully');
                    loadAnalyticsData();
                } else {
                    showError(response.message || 'Failed to clear cache');
                }
            },
            error: function(xhr) {
                showError('Failed to clear cache: ' + xhr.responseJSON?.message);
            }
        });
    }

    function performExport() {
        const formData = new FormData(document.getElementById('export-form'));

        $.ajax({
            url: '/api/hep/export/research',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    $('#exportModal').modal('hide');
                    // Trigger download
                    window.open(response.data.url, '_blank');
                } else {
                    showError(response.message || 'Export failed');
                }
            },
            error: function(xhr) {
                showError('Export failed: ' + xhr.responseJSON?.message);
            }
        });
    }

    function showError(message) {
        $('#error-message').text(message).show();
    }
});
</script>
@endsection
