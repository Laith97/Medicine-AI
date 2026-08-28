@extends('master')

@section('title', 'Landing Page Analytics')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/doctor-design-system.css') }}">
<link rel="stylesheet" href="{{ asset('css/doctor-dashboard.css') }}">
<link rel="stylesheet" href="{{ asset('css/cases-overview.css') }}">
<style>
.app-main { background-color: var(--bg-secondary, #f8f9fa); }
.analytics-card { border-radius: 12px; overflow: hidden; border: 1px solid #eef0f3; background:#fff; }
.analytics-card .ac-head { display:flex; align-items:center; justify-content:space-between; gap:0.75rem; padding:0.85rem 1.1rem; background:#ffffff; border-bottom:1px solid #f1f5f9; }
.analytics-card .ac-head .head-left { display:flex; align-items:center; gap:0.75rem; min-width:0; }
.analytics-card .ac-head .head-icon { width:38px; height:38px; border-radius:9px; display:flex; align-items:center; justify-content:center; font-size:0.95rem; flex-shrink:0; border:1px solid; }
.analytics-card .ac-head h6 { margin:0; font-size:0.90rem; font-weight:800; color:#1e293b; letter-spacing:-0.01em; line-height:1.2; }
.analytics-card .ac-head p { margin:1px 0 0; font-size:0.72rem; color:#94a3b8; font-weight:500; }
.analytics-card .ac-body { padding:1.1rem; }
.analytics-period { display:inline-flex; gap:0.35rem; background:#f8fafc; border:1px solid #eef2f7; border-radius:10px; padding:0.3rem; }
.analytics-period .btn { font-weight:700; font-size:0.78rem; padding:0.38rem 0.85rem; border-radius:8px; border:1px solid transparent; background:transparent; color:#64748b; line-height:1; transition:all 0.18s ease; }
.analytics-period .btn.active { background:#1e293b; color:#fff; border-color:#1e293b; box-shadow:0 2px 8px rgba(15,23,42,0.12); }
.analytics-period .btn:hover:not(.active) { background:#fff; color:#1e293b; border-color:#e2e8f0; }
.analytics-toolbar-panel.cases-panel { border:1px solid #eef0f3; }
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
</style>
@endpush

@section('content')
<div class="container-fluid" style="background-color: var(--bg-secondary, #f8f9fa);">
    <div class="container py-4">

        <div class="dashboard-header cases-header-compact">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <h2><i class="fas fa-globe me-2"></i>Landing Page Analytics</h2>
                    <p>Traffic, devices and referrers for your public landing page</p>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <a href="{{ route('doctor.landing-page.index') }}" class="btn" style="background:#fff;border:1px solid #e2e8f0;color:#1e293b;border-radius:10px;padding:0.5rem 0.95rem;font-weight:700;font-size:0.82rem;"><i class="fas fa-edit me-2"></i>Edit Landing Page</a>
                    <span class="doctor-badge doctor-badge-success d-none d-md-inline-flex"><i class="fas fa-chart-line me-1"></i> Insights</span>
                </div>
            </div>
        </div>

        <!-- Toolbar — period selector -->
        <div class="card border-0 shadow-sm cases-panel analytics-toolbar-panel mb-3">
            <div class="cases-toolbar">
                <div class="cases-toolbar__title">
                    <h5 class="mb-0 fw-semibold"><i class="fas fa-chart-line me-2 text-primary"></i>Overview</h5>
                    <span class="cases-toolbar__meta">— Filter by period · live charts</span>
                </div>
                <div class="cases-toolbar__controls">
                    <span class="small text-muted fw-semibold d-none d-md-inline" style="font-size:0.72rem; letter-spacing:0.04em; text-transform:uppercase;">Period</span>
                    <div class="btn-group analytics-period" role="group">
                        <button type="button" class="btn" data-period="7" id="period7">7 Days</button>
                        <button type="button" class="btn active" data-period="30" id="period30">30 Days</button>
                        <button type="button" class="btn" data-period="90" id="period90">90 Days</button>
                    </div>
                    <select id="periodSelector" class="form-select form-select-sm d-none">
                        <option value="7">Last 7 days</option>
                        <option value="30" selected>Last 30 days</option>
                        <option value="90">Last 90 days</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Stats — compact like analytics/index -->
        <div class="row g-2 mb-3 cases-stats-compact">
            <div class="col-6 col-lg-3">
                <div class="stats-card stats-card--compact">
                    <div class="stats-icon stats-icon--sm" style="background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);">
                        <i class="fas fa-eye"></i>
                    </div>
                    <div class="stats-text">
                        <p class="stats-number" id="totalVisits">{{ $stats['total_visits'] ?? 0 }}</p>
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
                        <p class="stats-number" id="uniqueVisitors">{{ $stats['unique_visitors'] ?? 0 }}</p>
                        <p class="stats-label">Unique Visitors</p>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="stats-card stats-card--compact">
                    <div class="stats-icon stats-icon--sm" style="background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stats-text">
                        <p class="stats-number" id="avgSessionTime">{{ $stats['avg_session_time'] ?? 0 }}s</p>
                        <p class="stats-label">Avg. Session</p>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="stats-card stats-card--compact">
                    <div class="stats-icon stats-icon--sm" style="background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%);">
                        <i class="fas fa-sign-out-alt"></i>
                    </div>
                    <div class="stats-text">
                        <p class="stats-number" id="bounceRate">{{ $stats['bounce_rate'] ?? 0 }}%</p>
                        <p class="stats-label">Bounce Rate</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm analytics-card h-100">
                    <div class="ac-head">
                        <div class="head-left">
                            <div class="head-icon" style="background:#eff6ff; color:#2563eb; border-color:#dbeafe;"><i class="fas fa-chart-line"></i></div>
                            <div>
                                <h6>Daily Visits Overview</h6>
                                <p>Visits & unique visitors over time</p>
                            </div>
                        </div>
                        <span class="analytics-badge analytics-badge--neutral d-none d-sm-inline"><i class="fas fa-calendar-alt me-1"></i> Trend</span>
                    </div>
                    <div class="ac-body">
                        <div style="height:320px; position:relative;">
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
                                <h6>Devices</h6>
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

        <div class="row g-3">
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm analytics-card h-100">
                    <div class="ac-head">
                        <div class="head-left">
                            <div class="head-icon" style="background:#fef3f2; color:#dc2626; border-color:#fee2e2;"><i class="fas fa-link"></i></div>
                            <div>
                                <h6>Top Referrers</h6>
                                <p>Where your traffic comes from</p>
                            </div>
                        </div>
                        <span class="analytics-badge analytics-badge--neutral">{{ isset($topReferrers) ? $topReferrers->count() : 0 }} sources</span>
                    </div>
                    <div class="p-0">
                        @if(isset($topReferrers) && $topReferrers->count() > 0)
                            <div class="analytics-list">
                                @foreach($topReferrers as $referrer)
                                    <div class="analytics-list-item">
                                        <div class="d-flex align-items-center gap-2 min-w-0 flex-grow-1">
                                            <div class="item-icon"><i class="fas fa-external-link-alt"></i></div>
                                            <div class="min-w-0">
                                                <p class="item-title">{{ parse_url($referrer->referrer_url, PHP_URL_HOST) ?: 'Direct' }}</p>
                                                <p class="item-sub">{{ Str::limit($referrer->referrer_url, 50) }}</p>
                                            </div>
                                        </div>
                                        <span class="analytics-badge analytics-badge--primary">{{ $referrer->visits }} visits</span>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="analytics-empty">
                                <div class="empty-icon"><i class="fas fa-link"></i></div>
                                <h6 class="fw-bold mb-1" style="font-size:0.90rem; color:#1e293b;">No referrer data yet</h6>
                                <p class="small text-muted mb-0">Referrer insights will appear once traffic is recorded.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm analytics-card h-100">
                    <div class="ac-head">
                        <div class="head-left">
                            <div class="head-icon" style="background:#f8fafc; color:#475569; border-color:#e2e8f0;"><i class="fab fa-chrome"></i></div>
                            <div>
                                <h6>Browsers</h6>
                                <p>Visits by browser</p>
                            </div>
                        </div>
                        <span class="analytics-badge analytics-badge--neutral">{{ isset($browserStats) ? $browserStats->count() : 0 }} browsers</span>
                    </div>
                    <div class="p-0">
                        @if(isset($browserStats) && $browserStats->count() > 0)
                            <div class="analytics-list">
                                @foreach($browserStats as $browser)
                                    <div class="analytics-list-item">
                                        <div class="d-flex align-items-center gap-2 min-w-0 flex-grow-1">
                                            <div class="item-icon"><i class="fas fa-globe"></i></div>
                                            <div class="min-w-0">
                                                <p class="item-title">{{ $browser->browser }}</p>
                                                <p class="item-sub">Browser share</p>
                                            </div>
                                        </div>
                                        <span class="analytics-badge analytics-badge--neutral">{{ $browser->visits }} visits</span>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="analytics-empty">
                                <div class="empty-icon"><i class="fab fa-chrome"></i></div>
                                <h6 class="fw-bold mb-1" style="font-size:0.90rem; color:#1e293b;">No browser data yet</h6>
                                <p class="small text-muted mb-0">Browser breakdown will appear once traffic is recorded.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        @if(isset($doctor) && $doctor->landingPage && $doctor->landingPage->is_published)
            <div class="card border-0 shadow-sm analytics-card mt-3">
                <div class="ac-head">
                    <div class="head-left">
                        <div class="head-icon" style="background:#eff6ff; color:#2563eb; border-color:#dbeafe;"><i class="fas fa-link"></i></div>
                        <div>
                            <h6>Public URL</h6>
                            <p>Share your landing page</p>
                        </div>
                    </div>
                    <span class="analytics-badge analytics-badge--primary"><i class="fas fa-check-circle me-1"></i>Live</span>
                </div>
                <div class="ac-body">
                    <div class="input-group" style="max-width:520px;">
                        <input type="text" class="form-control" value="{{ route('doctor.landing', $doctor->landingPage->username) }}" readonly style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px 0 0 10px;">
                        <button class="btn" style="background:#fff;border:1px solid #e2e8f0;border-left:0;border-radius:0 10px 10px 0;" type="button" onclick="copyToClipboard(this.previousElementSibling.value)"><i class="fas fa-copy"></i></button>
                    </div>
                </div>
            </div>
        @endif

    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
(function(){
    let visitsChart, deviceChart;
    function initCharts(){
        const visitsCtx = document.getElementById('visitsChart');
        if(!visitsCtx) return;
        const dailyVisits = @json($dailyVisits ?? []);
        visitsChart = new Chart(visitsCtx.getContext('2d'), {
            type: 'line',
            data: {
                labels: dailyVisits.map(i => new Date(i.date).toLocaleDateString()),
                datasets: [{
                    label: 'Total Visits',
                    data: dailyVisits.map(i => i.visits),
                    borderColor: '#007bff', backgroundColor: 'rgba(0,123,255,0.08)',
                    tension: 0.4, fill: true, borderWidth: 2, pointRadius: 0, pointHoverRadius: 4
                }, {
                    label: 'Unique Visitors',
                    data: dailyVisits.map(i => i.unique_visitors),
                    borderColor: '#28a745', backgroundColor: 'rgba(40,167,69,0.08)',
                    tension: 0.4, fill: true, borderWidth: 2, pointRadius: 0, pointHoverRadius: 4
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                interaction:{ intersect:false, mode:'index' },
                plugins:{ legend:{ position:'top', labels:{ usePointStyle:true, boxWidth:8, font:{size:11, weight:'600'} } } },
                scales:{
                    x:{ grid:{ display:false }, ticks:{ font:{size:11}, color:'#64748b' } },
                    y:{ beginAtZero:true, grid:{ color:'#f1f5f9' }, ticks:{ font:{size:11}, color:'#64748b' } }
                }
            }
        });
        const deviceCtx = document.getElementById('deviceChart');
        if(!deviceCtx) return;
        const deviceStats = @json($deviceStats ?? []);
        deviceChart = new Chart(deviceCtx.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: deviceStats.map(i => i.device_type),
                datasets: [{ data: deviceStats.map(i => i.visits), backgroundColor:['#007bff','#28a745','#ffc107','#dc3545','#6f42c1'], borderWidth:0, hoverOffset:6 }]
            },
            options:{ responsive:true, maintainAspectRatio:true, cutout:'62%', plugins:{ legend:{ position:'bottom', labels:{ usePointStyle:true, boxWidth:8, padding:14, font:{size:11, weight:'600'}, color:'#475569' } } } }
        });
    }
    function updateAnalytics(period){
        fetch(`{{ route('doctor.landing-page.analytics.data') }}?period=${period}`)
            .then(r=>r.json()).then(d=>{
                if(d.success){
                    const s=d.stats||{};
                    const el=(id,v)=>{ const e=document.getElementById(id); if(e) e.textContent=v; };
                    el('totalVisits', s.total_visits ?? 0);
                    el('uniqueVisitors', s.unique_visitors ?? 0);
                    el('avgSessionTime', (s.avg_session_time ?? 0) + 's');
                    el('bounceRate', (s.bounce_rate ?? 0) + '%');
                    // update hidden select too
                    const sel=document.getElementById('periodSelector'); if(sel) sel.value=period;
                    if(d.dailyVisits && visitsChart){
                        visitsChart.data.labels = d.dailyVisits.map(i=> new Date(i.date).toLocaleDateString());
                        visitsChart.data.datasets[0].data = d.dailyVisits.map(i=> i.visits);
                        visitsChart.data.datasets[1].data = d.dailyVisits.map(i=> i.unique_visitors);
                        visitsChart.update();
                    }
                    if(d.deviceStats && deviceChart){
                        deviceChart.data.labels = d.deviceStats.map(i=> i.device_type);
                        deviceChart.data.datasets[0].data = d.deviceStats.map(i=> i.visits);
                        deviceChart.update();
                    }
                }
            }).catch(e=>console.error(e));
    }
    document.addEventListener('DOMContentLoaded', function(){
        initCharts();
        // period buttons (new) and legacy select
        document.querySelectorAll('.analytics-period .btn').forEach(btn=>{
            btn.addEventListener('click', function(){
                document.querySelectorAll('.analytics-period .btn').forEach(b=>b.classList.remove('active'));
                this.classList.add('active');
                updateAnalytics(this.dataset.period);
            });
        });
        const sel=document.getElementById('periodSelector');
        if(sel){ sel.addEventListener('change', function(){ updateAnalytics(this.value); }); }
    });
    window.copyToClipboard=function(text){
        navigator.clipboard.writeText(text).then(()=>console.log('copied'));
    };
})();
</script>
@endpush
