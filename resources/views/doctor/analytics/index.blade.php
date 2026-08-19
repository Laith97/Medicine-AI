@extends('master')

@section('title', 'Analytics Dashboard')

@push('styles')
<style>
/* Professional Dashboard Header Styling */
.dashboard-header {
    background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
    border-radius: 15px;
    padding: 2rem;
    margin-bottom: 2rem;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
    border: 1px solid rgba(222, 98, 98, 0.2);
    position: relative;
    overflow: hidden;
}

.dashboard-header::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(135deg, #DE6262 0%, #2c3e50 100%);
}

.dashboard-header h2 {
    color: #ffffff;
    font-weight: 700;
    font-size: 2.5rem;
    margin-bottom: 0.5rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.dashboard-header h2::before {
    content: '📊';
    font-size: 2rem;
}

.dashboard-header p {
    color: rgba(255, 255, 255, 0.9);
    font-size: 1.1rem;
    font-weight: 500;
    margin-bottom: 0;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .dashboard-header {
        padding: 1.5rem;
        margin-bottom: 1.5rem;
    }

    .dashboard-header h2 {
        font-size: 2rem;
    }

    .dashboard-header p {
        font-size: 1rem;
    }
}
</style>
@endpush

@section('content')
<style>
.app-main {
    background-color: #f8f9fa;
}
.dashboard-header {
    background: linear-gradient(135deg, #2c5aa0 0%, #1e3a8a 100%);
    border-radius: 12px;
    padding: 2.5rem;
    margin-bottom: 2rem;
}
</style>
<div class="container-fluid" style="background-color: #f8f9fa;">
    <div class="container">
        <div class="dashboard-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2><i class="fas fa-chart-line me-2"></i>Analytics</h2>
                    <p class="text-muted mb-0">Track your practice performance and insights</p>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="container-fluid" style="background-color: #f8f9fa;">
    <div class="container">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-end mb-4">
                <div class="btn-group" role="group">
                    <button type="button" class="btn btn-outline-primary active" data-period="7">7 Days</button>
                    <button type="button" class="btn btn-outline-primary" data-period="30">30 Days</button>
                    <button type="button" class="btn btn-outline-primary" data-period="90">90 Days</button>
                </div>
            </div>

            <!-- Overview Cards -->
            <div class="row mb-4">
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h4 class="mb-0" id="total-visits">{{ $stats['total_visits'] }}</h4>
                                    <p class="mb-0">Total Visits</p>
                                </div>
                                <i class="fas fa-eye fa-2x opacity-75"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h4 class="mb-0" id="unique-visitors">{{ $stats['unique_visitors'] }}</h4>
                                    <p class="mb-0">Unique Visitors</p>
                                </div>
                                <i class="fas fa-users fa-2x opacity-75"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card bg-info text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h4 class="mb-0" id="blog-views">{{ $stats['blog_views'] }}</h4>
                                    <p class="mb-0">Blog Views</p>
                                </div>
                                <i class="fas fa-blog fa-2x opacity-75"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card bg-warning text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h4 class="mb-0" id="chat-sessions">{{ $stats['chat_sessions'] }}</h4>
                                    <p class="mb-0">Chat Sessions</p>
                                </div>
                                <i class="fas fa-comments fa-2x opacity-75"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Visits Chart -->
                <div class="col-lg-8 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Daily Visits</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="visitsChart" height="100"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Device Stats -->
                <div class="col-lg-4 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Device Types</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="deviceChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Top Blog Posts -->
                <div class="col-lg-6 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Top Blog Posts</h5>
                        </div>
                        <div class="card-body">
                            @if($topBlogPosts->count() > 0)
                                <div class="list-group list-group-flush">
                                    @foreach($topBlogPosts as $post)
                                        <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                                            <div>
                                                <h6 class="mb-1">{{ Str::limit($post->title, 40) }}</h6>
                                                <small class="text-muted">{{ $post->published_at ? $post->published_at->format('M j, Y') : 'Recently published' }}</small>
                                            </div>
                                            <span class="badge bg-primary rounded-pill">{{ $post->views_count }} views</span>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="text-center py-4">
                                    <i class="fas fa-blog fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">No blog posts yet</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Top Referrers -->
                <div class="col-lg-6 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Top Referrers</h5>
                        </div>
                        <div class="card-body">
                            @if($topReferrers->count() > 0)
                                <div class="list-group list-group-flush">
                                    @foreach($topReferrers as $referrer)
                                        <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                                            <div>
                                                <h6 class="mb-1">{{ parse_url($referrer->referrer_url, PHP_URL_HOST) ?: 'Direct' }}</h6>
                                                <small class="text-muted">{{ Str::limit($referrer->referrer_url, 50) }}</small>
                                            </div>
                                            <span class="badge bg-secondary rounded-pill">{{ $referrer->visits }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="text-center py-4">
                                    <i class="fas fa-external-link-alt fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">No referrer data yet</p>
                                </div>
                            @endif
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
$(document).ready(function() {
    let visitsChart, deviceChart;
    let currentPeriod = 7;

    // Initialize charts
    initializeCharts();

    // Period selector
    $('.btn-group button').click(function() {
        $('.btn-group button').removeClass('active');
        $(this).addClass('active');
        currentPeriod = $(this).data('period');
        loadAnalyticsData(currentPeriod);
    });

    function initializeCharts() {
        // Visits Chart
        const visitsCtx = document.getElementById('visitsChart').getContext('2d');
        visitsChart = new Chart(visitsCtx, {
            type: 'line',
            data: {
                labels: {!! json_encode($dailyVisits->pluck('date')) !!},
                datasets: [{
                    label: 'Visits',
                    data: {!! json_encode($dailyVisits->pluck('visits')) !!},
                    borderColor: '#007bff',
                    backgroundColor: 'rgba(0, 123, 255, 0.1)',
                    tension: 0.4,
                    fill: true
                }, {
                    label: 'Unique Visitors',
                    data: {!! json_encode($dailyVisits->pluck('unique_visitors')) !!},
                    borderColor: '#28a745',
                    backgroundColor: 'rgba(40, 167, 69, 0.1)',
                    tension: 0.4,
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

        // Device Chart
        const deviceCtx = document.getElementById('deviceChart').getContext('2d');
        deviceChart = new Chart(deviceCtx, {
            type: 'doughnut',
            data: {
                labels: {!! json_encode($deviceStats->pluck('device_type')) !!},
                datasets: [{
                    data: {!! json_encode($deviceStats->pluck('visits')) !!},
                    backgroundColor: ['#007bff', '#28a745', '#ffc107', '#dc3545', '#6f42c1']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true
            }
        });
    }

    function loadAnalyticsData(period) {
        $.ajax({
            url: '/doctor/analytics/data',
            method: 'GET',
            data: { period: period },
            success: function(response) {
                if (response.success) {
                    updateStats(response.stats);
                    updateCharts(response.dailyVisits, response.deviceStats);
                }
            },
            error: function() {
                console.error('Failed to load analytics data');
            }
        });
    }

    function updateStats(stats) {
        $('#total-visits').text(stats.total_visits);
        $('#unique-visitors').text(stats.unique_visitors);
        $('#blog-views').text(stats.blog_views);
        $('#chat-sessions').text(stats.chat_sessions);
    }

    function updateCharts(dailyVisits, deviceStats) {
        // Update visits chart
        visitsChart.data.labels = dailyVisits.map(item => item.date);
        visitsChart.data.datasets[0].data = dailyVisits.map(item => item.visits);
        visitsChart.data.datasets[1].data = dailyVisits.map(item => item.unique_visitors);
        visitsChart.update();

        // Update device chart
        deviceChart.data.labels = deviceStats.map(item => item.device_type);
        deviceChart.data.datasets[0].data = deviceStats.map(item => item.visits);
        deviceChart.update();
    }
});
</script>
@endpush
