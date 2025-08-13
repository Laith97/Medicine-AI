@extends('layouts.app')

@section('title', 'Landing Page Analytics')

@push('styles')
<style>
    .analytics-card {
        transition: transform 0.2s;
    }
    .analytics-card:hover {
        transform: translateY(-2px);
    }
    .stat-icon {
        font-size: 2rem;
        margin-bottom: 1rem;
    }
    .chart-container {
        position: relative;
        height: 400px;
        width: 100%;
    }
    .device-chart {
        max-height: 300px;
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-sm-flex align-items-center justify-content-between mb-4">
                <h1 class="h3 mb-0 text-gray-800">
                    <i class="fas fa-chart-line me-2"></i>Landing Page Analytics
                </h1>
                <div class="d-flex gap-2">
                    <select id="periodSelector" class="form-select form-select-sm" style="width: auto;">
                        <option value="7">Last 7 days</option>
                        <option value="30" selected>Last 30 days</option>
                        <option value="90">Last 90 days</option>
                    </select>
                    <a href="{{ route('doctor.landing-page.index') }}" class="btn btn-primary btn-sm">
                        <i class="fas fa-edit me-1"></i>Edit Landing Page
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2 analytics-card">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total Visits
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="totalVisits">
                                {{ $stats['total_visits'] }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-eye fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2 analytics-card">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Unique Visitors
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="uniqueVisitors">
                                {{ $stats['unique_visitors'] }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2 analytics-card">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Avg. Session Time
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="avgSessionTime">
                                {{ $stats['avg_session_time'] }}s
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clock fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2 analytics-card">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Bounce Rate
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="bounceRate">
                                {{ $stats['bounce_rate'] }}%
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-chart-line fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Daily Visits Chart -->
        <div class="col-xl-8 col-lg-7">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Daily Visits Overview</h6>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="visitsChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Device Stats -->
        <div class="col-xl-4 col-lg-5">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Devices</h6>
                </div>
                <div class="card-body device-chart">
                    <canvas id="deviceChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Top Referrers and Browser Stats -->
    <div class="row">
        <div class="col-lg-6 mb-4">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Top Referrers</h6>
                </div>
                <div class="card-body">
                    @forelse($topReferrers as $referrer)
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-truncate" style="max-width: 70%;" title="{{ $referrer->referrer_url }}">
                                {{ parse_url($referrer->referrer_url, PHP_URL_HOST) ?: 'Direct' }}
                            </span>
                            <span class="badge bg-primary">{{ $referrer->visits }}</span>
                        </div>
                    @empty
                        <p class="text-muted text-center">No referrer data available</p>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="col-lg-6 mb-4">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Browsers</h6>
                </div>
                <div class="card-body">
                    @forelse($browserStats as $browser)
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span>{{ $browser->browser }}</span>
                            <span class="badge bg-success">{{ $browser->visits }}</span>
                        </div>
                    @empty
                        <p class="text-muted text-center">No browser data available</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    @if($doctor->landingPage && $doctor->landingPage->is_published)
        <div class="row">
            <div class="col-12">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Landing Page Links</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Public URL:</label>
                                <div class="input-group">
                                    <input type="text" class="form-control"
                                           value="{{ route('doctor.landing', $doctor->landingPage->username) }}" readonly>
                                    <button class="btn btn-outline-secondary" type="button"
                                            onclick="copyToClipboard(this.previousElementSibling.value)">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Initialize charts
    let visitsChart, deviceChart;

    document.addEventListener('DOMContentLoaded', function() {
        initializeCharts();

        // Period selector
        document.getElementById('periodSelector').addEventListener('change', function() {
            updateAnalytics(this.value);
        });
    });

    function initializeCharts() {
        // Daily visits chart
        const visitsCtx = document.getElementById('visitsChart').getContext('2d');
        const dailyVisits = @json($dailyVisits);

        visitsChart = new Chart(visitsCtx, {
            type: 'line',
            data: {
                labels: dailyVisits.map(item => new Date(item.date).toLocaleDateString()),
                datasets: [{
                    label: 'Total Visits',
                    data: dailyVisits.map(item => item.visits),
                    borderColor: 'rgb(78, 115, 223)',
                    backgroundColor: 'rgba(78, 115, 223, 0.1)',
                    fill: true
                }, {
                    label: 'Unique Visitors',
                    data: dailyVisits.map(item => item.unique_visitors),
                    borderColor: 'rgb(28, 200, 138)',
                    backgroundColor: 'rgba(28, 200, 138, 0.1)',
                    fill: true
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

        // Device chart
        const deviceCtx = document.getElementById('deviceChart').getContext('2d');
        const deviceStats = @json($deviceStats);

        deviceChart = new Chart(deviceCtx, {
            type: 'doughnut',
            data: {
                labels: deviceStats.map(item => item.device_type),
                datasets: [{
                    data: deviceStats.map(item => item.visits),
                    backgroundColor: [
                        'rgb(78, 115, 223)',
                        'rgb(28, 200, 138)',
                        'rgb(255, 193, 7)'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });
    }

    function updateAnalytics(period) {
        fetch(`{{ route('doctor.landing-page.analytics.data') }}?period=${period}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update stats
                    document.getElementById('totalVisits').textContent = data.stats.total_visits;
                    document.getElementById('uniqueVisitors').textContent = data.stats.unique_visitors;
                    document.getElementById('avgSessionTime').textContent = data.stats.avg_session_time + 's';
                    document.getElementById('bounceRate').textContent = data.stats.bounce_rate + '%';

                    // Update charts
                    updateCharts(data.dailyVisits, data.deviceStats);
                }
            })
            .catch(error => {
                console.error('Error updating analytics:', error);
            });
    }

    function updateCharts(dailyVisits, deviceStats) {
        // Update visits chart
        visitsChart.data.labels = dailyVisits.map(item => new Date(item.date).toLocaleDateString());
        visitsChart.data.datasets[0].data = dailyVisits.map(item => item.visits);
        visitsChart.data.datasets[1].data = dailyVisits.map(item => item.unique_visitors);
        visitsChart.update();

        // Update device chart
        deviceChart.data.labels = deviceStats.map(item => item.device_type);
        deviceChart.data.datasets[0].data = deviceStats.map(item => item.visits);
        deviceChart.update();
    }

    function copyToClipboard(text) {
        navigator.clipboard.writeText(text).then(function() {
            // You could show a toast notification here
            console.log('URL copied to clipboard');
        });
    }
</script>
@endpush
