@extends('master')

@section('title', 'Analytics Dashboard')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/doctor-design-system.css') }}">
<link rel="stylesheet" href="{{ asset('css/doctor-dashboard.css') }}">
<link rel="stylesheet" href="{{ asset('css/cases-overview.css') }}">
<style>
.app-main { background-color: var(--bg-secondary, #f8f9fa); }
/* analytics-card premium — matches appointments/show + cases-panel */
.analytics-card { border-radius: 12px; overflow: hidden; border: 1px solid #eef0f3; background:#fff; }
.analytics-card .ac-head { display:flex; align-items:center; justify-content:space-between; gap:0.75rem; padding:0.85rem 1.1rem; background:#ffffff; border-bottom:1px solid #f1f5f9; }
.analytics-card .ac-head .head-left { display:flex; align-items:center; gap:0.75rem; min-width:0; }
.analytics-card .ac-head .head-icon { width:38px; height:38px; border-radius:9px; display:flex; align-items:center; justify-content:center; font-size:0.95rem; flex-shrink:0; border:1px solid; }
.analytics-card .ac-head h6 { margin:0; font-size:0.90rem; font-weight:800; color:#1e293b; letter-spacing:-0.01em; line-height:1.2; }
.analytics-card .ac-head p { margin:1px 0 0; font-size:0.72rem; color:#94a3b8; font-weight:500; }
.analytics-card .ac-body { padding:1.1rem; }
/* period pills */
.analytics-period { display:inline-flex; gap:0.35rem; background:#f8fafc; border:1px solid #eef2f7; border-radius:10px; padding:0.3rem; }
.analytics-period .btn { font-weight:700; font-size:0.78rem; padding:0.38rem 0.85rem; border-radius:8px; border:1px solid transparent; background:transparent; color:#64748b; line-height:1; transition:all 0.18s ease; }
.analytics-period .btn.active { background:#1e293b; color:#fff; border-color:#1e293b; box-shadow:0 2px 8px rgba(15,23,42,0.12); }
.analytics-period .btn:hover:not(.active) { background:#fff; color:#1e293b; border-color:#e2e8f0; }
/* toolbar panel */
.analytics-toolbar-panel.cases-panel { border:1px solid #eef0f3; }
/* list modern */
.analytics-list { display:flex; flex-direction:column; }
.analytics-list-item { display:flex; align-items:center; justify-content:space-between; gap:0.75rem; padding:0.85rem 1.1rem; border-bottom:1px solid #f1f5f9; transition: background 0.15s; }
.analytics-list-item:last-child { border-bottom:none; }
.analytics-list-item:hover { background:#f8fafc; }
.analytics-list-item .item-icon { width:36px; height:36px; border-radius:9px; display:flex; align-items:center; justify-content:center; font-size:0.85rem; flex-shrink:0; background:#f8fafc; border:1px solid #eef2f7; color:#475569; }
.analytics-list-item .item-title { font-size:0.86rem; font-weight:700; color:#1e293b; margin:0; line-height:1.3; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width: 22ch; }
.analytics-list-item .item-sub { font-size:0.72rem; color:#94a3b8; font-weight:500; margin:1px 0 0; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:28ch; }
.analytics-badge { font-size:0.70rem; font-weight:700; padding:0.32rem 0.65rem; border-radius:99px; border:1px solid; white-space:nowrap; }
.analytics-badge--primary { background:#eff6ff; color:#2563eb; border-color:#dbeafe; }
.analytics-badge--neutral { background:#f8fafc; color:#475569; border-color:#e2e8f0; }
.analytics-empty { text-align:center; padding:2rem 1.5rem; }
.analytics-empty .empty-icon { width:52px; height:52px; border-radius:12px; background:#f8fafc; border:1px solid #eef2f7; color:#94a3b8; display:inline-flex; align-items:center; justify-content:center; font-size:1.4rem; margin-bottom:0.75rem; }
@media (max-width: 576px){
  .analytics-card .ac-body { padding:0.9rem; }
  .analytics-list-item .item-title { max-width: 16ch; }
  .analytics-list-item .item-sub { max-width: 20ch; }
}
</style>
@endpush

@section('content')
<div class="container-fluid" style="background-color: var(--bg-secondary, #f8f9fa);">
    <div class="container py-4">
        <div class="dashboard-header cases-header-compact">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2><i class="fas fa-chart-line me-2"></i>Analytics</h2>
                    <p>Track your practice performance and insights</p>
                </div>
                <span class="doctor-badge doctor-badge-success d-none d-md-inline-flex"><i class="fas fa-chart-bar me-1"></i> Insights</span>
            </div>
        </div>

        <!-- Period + overview toolbar — cases-panel cases-toolbar -->
        <div class="card border-0 shadow-sm cases-panel analytics-toolbar-panel mb-3">
            <div class="cases-toolbar">
                <div class="cases-toolbar__title">
                    <h5 class="mb-0 fw-semibold"><i class="fas fa-chart-line me-2 text-primary"></i>Overview</h5>
                    <span class="cases-toolbar__meta">— Filter by period · live charts</span>
                </div>
                <div class="cases-toolbar__controls">
                    <span class="small text-muted fw-semibold d-none d-md-inline" style="font-size:0.72rem; letter-spacing:0.04em; text-transform:uppercase;">Period</span>
                    <div class="btn-group analytics-period" role="group" aria-label="Period selector">
                        <button type="button" class="btn active" data-period="7">7 Days</button>
                        <button type="button" class="btn" data-period="30">30 Days</button>
                        <button type="button" class="btn" data-period="90">90 Days</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stats — stats-card--compact 42px gradient like cases-overview:41 -->
        <div class="row g-2 mb-3 cases-stats-compact">
            <div class="col-6 col-lg-3">
                <div class="stats-card stats-card--compact">
                    <div class="stats-icon stats-icon--sm" style="background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);">
                        <i class="fas fa-eye"></i>
                    </div>
                    <div class="stats-text">
                        <p class="stats-number" id="total-visits">{{ $stats['total_visits'] }}</p>
                        <p class="stats-label">Total Visits</p>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="stats-card stats-card--compact">
                    <div class="stats-icon stats-icon--sm" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%);">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stats-text">
                        <p class="stats-number" id="unique-visitors">{{ $stats['unique_visitors'] }}</p>
                        <p class="stats-label">Unique Visitors</p>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="stats-card stats-card--compact">
                    <div class="stats-icon stats-icon--sm" style="background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);">
                        <i class="fas fa-blog"></i>
                    </div>
                    <div class="stats-text">
                        <p class="stats-number" id="blog-views">{{ $stats['blog_views'] }}</p>
                        <p class="stats-label">Blog Views</p>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="stats-card stats-card--compact">
                    <div class="stats-icon stats-icon--sm" style="background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%);">
                        <i class="fas fa-comments"></i>
                    </div>
                    <div class="stats-text">
                        <p class="stats-number" id="chat-sessions">{{ $stats['chat_sessions'] }}</p>
                        <p class="stats-label">Chat Sessions</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts — card border-0 shadow-sm analytics-card 12px -->
        <div class="row g-3 mb-3">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm analytics-card h-100">
                    <div class="ac-head">
                        <div class="head-left">
                            <div class="head-icon" style="background:#eff6ff; color:#2563eb; border-color:#dbeafe;"><i class="fas fa-chart-line"></i></div>
                            <div>
                                <h6>Daily Visits</h6>
                                <p>Visits & unique visitors over time</p>
                            </div>
                        </div>
                        <span class="analytics-badge analytics-badge--neutral d-none d-sm-inline"><i class="fas fa-calendar-alt me-1"></i> Trend</span>
                    </div>
                    <div class="ac-body">
                        <div style="height:280px; position:relative;">
                            <canvas id="visitsChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm analytics-card h-100">
                    <div class="ac-head">
                        <div class="head-left">
                            <div class="head-icon" style="background:#f0fdf4; color:#059669; border-color:#dcfce7;"><i class="fas fa-mobile-alt"></i></div>
                            <div>
                                <h6>Device Types</h6>
                                <p>Traffic by device</p>
                            </div>
                        </div>
                        <span class="analytics-badge analytics-badge--neutral d-none d-sm-inline"><i class="fas fa-percentage me-1"></i> Share</span>
                    </div>
                    <div class="ac-body d-flex align-items-center justify-content-center">
                        <div style="width:100%; max-width:260px; height:260px;">
                            <canvas id="deviceChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Lists — doctor-table / list-group modern with badges + empty state -->
        <div class="row g-3">
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm analytics-card h-100">
                    <div class="ac-head">
                        <div class="head-left">
                            <div class="head-icon" style="background:#fef3f2; color:#dc2626; border-color:#fee2e2;"><i class="fas fa-fire"></i></div>
                            <div>
                                <h6>Top Blog Posts</h6>
                                <p>Most viewed articles</p>
                            </div>
                        </div>
                        <span class="analytics-badge analytics-badge--primary">{{ $topBlogPosts->count() }} posts</span>
                    </div>
                    <div class="p-0">
                        @if($topBlogPosts->count() > 0)
                            <div class="analytics-list">
                                @foreach($topBlogPosts as $post)
                                    <div class="analytics-list-item">
                                        <div class="d-flex align-items-center gap-2 min-w-0 flex-grow-1">
                                            <div class="item-icon"><i class="fas fa-file-alt"></i></div>
                                            <div class="min-w-0">
                                                <p class="item-title">{{ Str::limit($post->title, 40) }}</p>
                                                <p class="item-sub"><i class="far fa-calendar me-1"></i>{{ $post->published_at ? $post->published_at->format('M j, Y') : 'Recently published' }}</p>
                                            </div>
                                        </div>
                                        <span class="analytics-badge analytics-badge--primary"><i class="fas fa-eye me-1"></i>{{ $post->views_count }} views</span>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="analytics-empty">
                                <div class="empty-icon"><i class="fas fa-blog"></i></div>
                                <h6 class="fw-bold mb-1" style="font-size:0.90rem; color:#1e293b;">No blog posts yet</h6>
                                <p class="small text-muted mb-0">Publish articles to see top performers here.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm analytics-card h-100">
                    <div class="ac-head">
                        <div class="head-left">
                            <div class="head-icon" style="background:#f8fafc; color:#475569; border-color:#e2e8f0;"><i class="fas fa-external-link-alt"></i></div>
                            <div>
                                <h6>Top Referrers</h6>
                                <p>Where your traffic comes from</p>
                            </div>
                        </div>
                        <span class="analytics-badge analytics-badge--neutral">{{ $topReferrers->count() }} sources</span>
                    </div>
                    <div class="p-0">
                        @if($topReferrers->count() > 0)
                            <div class="analytics-list">
                                @foreach($topReferrers as $referrer)
                                    <div class="analytics-list-item">
                                        <div class="d-flex align-items-center gap-2 min-w-0 flex-grow-1">
                                            <div class="item-icon"><i class="fas fa-link"></i></div>
                                            <div class="min-w-0">
                                                <p class="item-title">{{ parse_url($referrer->referrer_url, PHP_URL_HOST) ?: 'Direct' }}</p>
                                                <p class="item-sub">{{ Str::limit($referrer->referrer_url, 50) }}</p>
                                            </div>
                                        </div>
                                        <span class="analytics-badge analytics-badge--neutral">{{ $referrer->visits }} visits</span>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="analytics-empty">
                                <div class="empty-icon"><i class="fas fa-external-link-alt"></i></div>
                                <h6 class="fw-bold mb-1" style="font-size:0.90rem; color:#1e293b;">No referrer data yet</h6>
                                <p class="small text-muted mb-0">Referrer insights will appear once traffic is recorded.</p>
                            </div>
                        @endif
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

    // Period selector — supports both .analytics-period and legacy .btn-group
    $('.analytics-period button, .btn-group button').click(function() {
        $('.analytics-period button, .btn-group button').removeClass('active');
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
                    backgroundColor: 'rgba(0, 123, 255, 0.08)',
                    tension: 0.4,
                    fill: true,
                    borderWidth: 2,
                    pointRadius: 0,
                    pointHoverRadius: 4
                }, {
                    label: 'Unique Visitors',
                    data: {!! json_encode($dailyVisits->pluck('unique_visitors')) !!},
                    borderColor: '#28a745',
                    backgroundColor: 'rgba(40, 167, 69, 0.08)',
                    tension: 0.4,
                    fill: true,
                    borderWidth: 2,
                    pointRadius: 0,
                    pointHoverRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { intersect:false, mode:'index' },
                plugins: { legend: { position: 'top', labels:{ usePointStyle:true, boxWidth:8, font:{size:11, weight:'600'} } } },
                scales: {
                    x: { grid:{ display:false }, ticks:{ font:{size:11}, color:'#64748b' } },
                    y: { beginAtZero: true, grid:{ color:'#f1f5f9' }, ticks:{ font:{size:11}, color:'#64748b' } }
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
                    backgroundColor: ['#007bff', '#28a745', '#ffc107', '#dc3545', '#6f42c1'],
                    borderWidth: 0,
                    hoverOffset: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                cutout: '62%',
                plugins: { legend:{ position:'bottom', labels:{ usePointStyle:true, boxWidth:8, padding:14, font:{size:11, weight:'600'}, color:'#475569' } } }
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
