@extends('layouts.admin')

@section('title', 'Waitlist Analytics')

@section('content')
<div class="container-fluid px-0">
    {{-- Header compatible --}}
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4" style="background:linear-gradient(135deg,#1e293b 0%,#334155 100%);border-radius:16px;padding:1.4rem 1.6rem;box-shadow:0 8px 24px rgba(15,23,42,0.12)">
        <div class="d-flex align-items-center gap-3">
            <div style="width:44px;height:44px;border-radius:12px;background:rgba(255,255,255,0.14);display:flex;align-items:center;justify-content:center"><i class="fas fa-chart-line" style="color:#fff;font-size:1.1rem"></i></div>
            <div>
                <h1 style="font-size:1.35rem;font-weight:800;color:#fff;letter-spacing:-0.02em;margin:0">Waitlist Analytics</h1>
                <p style="font-size:0.78rem;color:rgba(255,255,255,0.75);margin:2px 0 0">Comprehensive analytics and insights for waitlist management</p>
            </div>
        </div>
        <div class="d-flex gap-2 align-items-center">
            <select class="form-select form-select-sm" id="timeRangeSelect" style="width:auto;border-radius:10px;background:#fff;font-weight:600">
                <option value="7">Last 7 days</option>
                <option value="30" selected>Last 30 days</option>
                <option value="90">Last 90 days</option>
                <option value="365">Last year</option>
            </select>
            <button class="btn btn-sm text-white" style="background:rgba(255,255,255,0.14);border:1px solid rgba(255,255,255,0.18);border-radius:10px;font-weight:700" onclick="refreshAnalytics()"><i class="fas fa-sync-alt me-1"></i> Refresh</button>
            <button class="btn btn-sm" style="background:#fff;color:#0f172a;border-radius:10px;font-weight:800;border:1px solid #e2e8f0" onclick="exportAnalytics()"><i class="fas fa-download me-1"></i> Export</button>
        </div>
    </div>

    <!-- Key Metrics Row compatible -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-xl-3"><div class="card border-0 shadow-sm" style="border-radius:14px"><div class="card-body p-3 d-flex align-items-center gap-3"><div style="width:42px;height:42px;border-radius:12px;background:#eff6ff;border:1px solid #dbeafe;color:#2563eb;display:flex;align-items:center;justify-content:center"><i class="fas fa-clock"></i></div><div><div style="font-weight:800;color:#0f172a" id="avgWaitTime">0 days</div><small class="text-muted">Avg Wait Time</small><div style="font-size:0.70rem;color:#059669;font-weight:700"><i class="fas fa-arrow-down me-1"></i>-8% vs last period</div></div></div></div></div>
        <div class="col-6 col-xl-3"><div class="card border-0 shadow-sm" style="border-radius:14px"><div class="card-body p-3 d-flex align-items-center gap-3"><div style="width:42px;height:42px;border-radius:12px;background:#ecfdf5;border:1px solid #a7f3d0;color:#059669;display:flex;align-items:center;justify-content:center"><i class="fas fa-chart-line"></i></div><div><div style="font-weight:800;color:#0f172a" id="fillRate">0%</div><small class="text-muted">Fill Rate</small><div style="font-size:0.70rem;color:#059669;font-weight:700"><i class="fas fa-arrow-up me-1"></i>+12% vs last period</div></div></div></div></div>
        <div class="col-6 col-xl-3"><div class="card border-0 shadow-sm" style="border-radius:14px"><div class="card-body p-3 d-flex align-items-center gap-3"><div style="width:42px;height:42px;border-radius:12px;background:#f5f3ff;border:1px solid #ddd6fe;color:#7c3aed;display:flex;align-items:center;justify-content:center"><i class="fas fa-star"></i></div><div><div style="font-weight:800;color:#0f172a" id="satisfactionScore">0/5</div><small class="text-muted">Satisfaction</small><div style="font-size:0.70rem;color:#059669;font-weight:700"><i class="fas fa-arrow-up me-1"></i>+0.3 vs last period</div></div></div></div></div>
        <div class="col-6 col-xl-3"><div class="card border-0 shadow-sm" style="border-radius:14px"><div class="card-body p-3 d-flex align-items-center gap-3"><div style="width:42px;height:42px;border-radius:12px;background:#fffbeb;border:1px solid #fde68a;color:#d97706;display:flex;align-items:center;justify-content:center"><i class="fas fa-exclamation-triangle"></i></div><div><div style="font-weight:800;color:#0f172a" id="priorityOverrides">0</div><small class="text-muted">Priority Overrides</small><div style="font-size:0.70rem;color:#64748b;font-weight:600">Admin interventions</div></div></div></div></div>
    </div>

    <!-- Charts Row -->
    <div class="row g-4 mb-4">
        <div class="col-lg-8"><div class="card border-0 shadow-sm" style="border-radius:14px;overflow:hidden"><div class="card-header bg-white d-flex align-items-center gap-2" style="padding:1rem 1.25rem;border-bottom:1px solid #eef2f7"><span style="width:32px;height:32px;border-radius:10px;background:#eff6ff;border:1px solid #dbeafe;color:#2563eb;display:flex;align-items:center;justify-content:center"><i class="fas fa-chart-line"></i></span><h5 class="mb-0" style="font-weight:800;color:#0f172a;font-size:0.95rem">Wait Time Trends</h5></div><div class="card-body"><canvas id="waitTimeChart" height="300"></canvas></div></div></div>
        <div class="col-lg-4"><div class="card border-0 shadow-sm" style="border-radius:14px;overflow:hidden"><div class="card-header bg-white d-flex align-items-center gap-2" style="padding:1rem 1.25rem;border-bottom:1px solid #eef2f7"><span style="width:32px;height:32px;border-radius:10px;background:#f5f3ff;border:1px solid #ddd6fe;color:#7c3aed;display:flex;align-items:center;justify-content:center"><i class="fas fa-chart-pie"></i></span><h5 class="mb-0" style="font-weight:800;color:#0f172a;font-size:0.95rem">Priority Distribution</h5></div><div class="card-body"><canvas id="priorityChart" height="300"></canvas></div></div></div>
    </div>

    <!-- Additional Analytics -->
    <div class="row g-4 mb-4">
        <div class="col-lg-6"><div class="card border-0 shadow-sm" style="border-radius:14px;overflow:hidden"><div class="card-header bg-white" style="padding:1rem 1.25rem;border-bottom:1px solid #eef2f7"><h5 class="mb-0" style="font-weight:800;color:#0f172a;font-size:0.95rem">Fill Rate by Specialty</h5></div><div class="card-body"><canvas id="specialtyFillRateChart" height="250"></canvas></div></div></div>
        <div class="col-lg-6"><div class="card border-0 shadow-sm" style="border-radius:14px;overflow:hidden"><div class="card-header bg-white" style="padding:1rem 1.25rem;border-bottom:1px solid #eef2f7"><h5 class="mb-0" style="font-weight:800;color:#0f172a;font-size:0.95rem">Patient Satisfaction Trends</h5></div><div class="card-body"><canvas id="satisfactionChart" height="250"></canvas></div></div></div>
    </div>

    <!-- Insights and Recommendations -->
    <div class="row g-4 mb-4">
        <div class="col-lg-6"><div class="card border-0 shadow-sm" style="border-radius:14px;overflow:hidden"><div class="card-header bg-white d-flex align-items-center gap-2" style="padding:1rem 1.25rem;border-bottom:1px solid #eef2f7"><span style="width:32px;height:32px;border-radius:10px;background:#fffbeb;border:1px solid #fde68a;color:#d97706;display:flex;align-items:center;justify-content:center"><i class="fas fa-lightbulb"></i></span><h5 class="mb-0" style="font-weight:800;color:#0f172a;font-size:0.95rem">Key Insights</h5></div><div class="card-body" id="insightsList"><div class="d-flex align-items-start gap-3"><div style="width:36px;height:36px;border-radius:10px;background:#fffbeb;border:1px solid #fde68a;color:#d97706;display:flex;align-items:center;justify-content:center"><i class="fas fa-lightbulb"></i></div><div><h6 style="font-weight:700;color:#0f172a;font-size:0.84rem">Loading insights...</h6><p class="text-muted small mb-0">Analyzing waitlist data</p></div></div></div></div></div>
        <div class="col-lg-6"><div class="card border-0 shadow-sm" style="border-radius:14px;overflow:hidden"><div class="card-header bg-white d-flex align-items-center gap-2" style="padding:1rem 1.25rem;border-bottom:1px solid #eef2f7"><span style="width:32px;height:32px;border-radius:10px;background:#ecfdf5;border:1px solid #a7f3d0;color:#059669;display:flex;align-items:center;justify-content:center"><i class="fas fa-clipboard-check"></i></span><h5 class="mb-0" style="font-weight:800;color:#0f172a;font-size:0.95rem">Recommendations</h5></div><div class="card-body" id="recommendationsList"><div class="d-flex align-items-start gap-3"><div style="width:36px;height:36px;border-radius:10px;background:#ecfdf5;border:1px solid #a7f3d0;color:#059669;display:flex;align-items:center;justify-content:center"><i class="fas fa-clipboard-check"></i></div><div><h6 style="font-weight:700;color:#0f172a;font-size:0.84rem">Loading recommendations...</h6><p class="text-muted small mb-0">Generating suggestions</p></div></div></div></div></div>
    </div>

    <!-- Top Performers and Bottlenecks -->
    <div class="row g-4">
        <div class="col-lg-6"><div class="card border-0 shadow-sm" style="border-radius:14px;overflow:hidden"><div class="card-header bg-white" style="padding:1rem 1.25rem;border-bottom:1px solid #eef2f7"><h5 class="mb-0" style="font-weight:800;color:#0f172a;font-size:0.95rem">Top Performing Doctors</h5></div><div class="card-body p-0"><div class="table-responsive"><table class="table table-hover mb-0" style="font-size:0.84rem"><thead style="background:#f8fafc"><tr style="font-size:0.75rem;letter-spacing:0.04em;color:#64748b;text-transform:uppercase"><th style="padding:0.9rem 1.25rem;border:none">Doctor</th><th style="padding:0.9rem 1.25rem;border:none">Fill Rate</th><th style="padding:0.9rem 1.25rem;border:none">Avg Wait</th></tr></thead><tbody id="topPerformersTable"><tr><td colspan="3" class="text-center text-muted py-4">Loading...</td></tr></tbody></table></div></div></div></div>
        <div class="col-lg-6"><div class="card border-0 shadow-sm" style="border-radius:14px;overflow:hidden"><div class="card-header bg-white" style="padding:1rem 1.25rem;border-bottom:1px solid #eef2f7"><h5 class="mb-0" style="font-weight:800;color:#0f172a;font-size:0.95rem">Bottlenecks</h5></div><div class="card-body p-0"><div class="table-responsive"><table class="table table-hover mb-0" style="font-size:0.84rem"><thead style="background:#f8fafc"><tr style="font-size:0.75rem;letter-spacing:0.04em;color:#64748b;text-transform:uppercase"><th style="padding:0.9rem 1.25rem;border:none">Issue</th><th style="padding:0.9rem 1.25rem;border:none">Impact</th><th style="padding:0.9rem 1.25rem;border:none">Recommendation</th></tr></thead><tbody id="bottlenecksTable"><tr><td colspan="3" class="text-center text-muted py-4">Loading...</td></tr></tbody></table></div></div></div></div>
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
    document.getElementById('timeRangeSelect').addEventListener('change', function() { loadAnalyticsData(); });
});
function initializeCharts() {
    const waitTimeCtx = document.getElementById('waitTimeChart').getContext('2d');
    waitTimeChart = new Chart(waitTimeCtx, { type: 'line', data: { labels: [], datasets: [{ label: 'Avg Wait Time (days)', data: [], borderColor: '#0f172a', backgroundColor: 'rgba(15,23,42,0.08)', tension: 0.4, fill: true, borderWidth:2 }] }, options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true, title: { display: true, text: 'Days' } } } } });
    const priorityCtx = document.getElementById('priorityChart').getContext('2d');
    priorityChart = new Chart(priorityCtx, { type: 'doughnut', data: { labels: ['Low','Medium','High','Urgent'], datasets: [{ data: [], backgroundColor: ['#94a3b8','#38bdf8','#f59e0b','#dc2626'], borderWidth:1 }] }, options: { responsive: true, maintainAspectRatio: false } });
    const specialtyCtx = document.getElementById('specialtyFillRateChart').getContext('2d');
    specialtyFillRateChart = new Chart(specialtyCtx, { type: 'bar', data: { labels: [], datasets: [{ label: 'Fill Rate (%)', data: [], backgroundColor: '#0f172a', borderColor: '#0f172a', borderWidth: 1, borderRadius:8 }] }, options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true, max: 100, title: { display: true, text: 'Fill Rate (%)' } } } } });
    const satisfactionCtx = document.getElementById('satisfactionChart').getContext('2d');
    satisfactionChart = new Chart(satisfactionCtx, { type: 'line', data: { labels: [], datasets: [{ label: 'Satisfaction', data: [], borderColor: '#7c3aed', backgroundColor: 'rgba(124,58,237,0.08)', tension: 0.4, fill: true, borderWidth:2 }] }, options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true, max: 5, title: { display: true, text: 'Rating (1-5)' } } } } });
}
function loadAnalyticsData() {
    const timeRange = document.getElementById('timeRangeSelect').value;
    fetch(`/api/admin/waitlist/analytics?timeframe=${timeRange}`)
        .then(r => r.json()).then(data => {
            updateMetrics(data.metrics);
            updateCharts(data.charts);
            updateInsights(data.insights);
            updateRecommendations(data.recommendations);
            updateTopPerformers(data.topPerformers);
            updateBottlenecks(data.bottlenecks);
        }).catch(e => { console.error(e); showAlert('Error loading analytics','danger'); });
}
function updateMetrics(m){ document.getElementById('avgWaitTime').textContent = `${m.avgWaitTime ?? 0} days`; document.getElementById('fillRate').textContent = `${m.fillRate ?? 0}%`; document.getElementById('satisfactionScore').textContent = `${m.satisfactionScore ?? 0}/5`; document.getElementById('priorityOverrides').textContent = m.priorityOverrides ?? 0; }
function updateCharts(c){ waitTimeChart.data.labels=c.waitTime.labels; waitTimeChart.data.datasets[0].data=c.waitTime.data; waitTimeChart.update(); priorityChart.data.datasets[0].data=c.priority.data; priorityChart.update(); specialtyFillRateChart.data.labels=c.specialty.labels; specialtyFillRateChart.data.datasets[0].data=c.specialty.data; specialtyFillRateChart.update(); satisfactionChart.data.labels=c.satisfaction.labels; satisfactionChart.data.datasets[0].data=c.satisfaction.data; satisfactionChart.update(); }
function updateInsights(list){ const el=document.getElementById('insightsList'); el.innerHTML=''; (list||[]).forEach(i=>{ el.insertAdjacentHTML('beforeend', `<div class="d-flex align-items-start gap-3 mb-3"><div style="width:36px;height:36px;border-radius:10px;background:#fffbeb;border:1px solid #fde68a;color:#d97706;display:flex;align-items:center;justify-content:center"><i class="fas fa-${i.icon}"></i></div><div><h6 style="font-weight:700;color:#0f172a;font-size:0.84rem">${i.title}</h6><p class="text-muted small mb-0">${i.description}</p></div></div>`); }); }
function updateRecommendations(list){ const el=document.getElementById('recommendationsList'); el.innerHTML=''; (list||[]).forEach(r=>{ el.insertAdjacentHTML('beforeend', `<div class="d-flex align-items-start gap-3 mb-3"><div style="width:36px;height:36px;border-radius:10px;background:#ecfdf5;border:1px solid #a7f3d0;color:#059669;display:flex;align-items:center;justify-content:center"><i class="fas fa-${r.icon}"></i></div><div><h6 style="font-weight:700;color:#0f172a;font-size:0.84rem">${r.title}</h6><p class="text-muted small mb-0">${r.description}</p>${r.action?`<span class="badge bg-light border text-muted mt-2" style="border-radius:20px">${r.action}</span>`:''}</div></div>`); }); }
function updateTopPerformers(list){ const tb=document.getElementById('topPerformersTable'); tb.innerHTML=''; (list||[]).forEach(p=>{ tb.insertAdjacentHTML('beforeend', `<tr style="border-bottom:1px solid #f8fafc"><td style="padding:0.8rem 1.25rem;font-weight:600;color:#0f172a">${p.name}</td><td style="padding:0.8rem 1.25rem"><span class="badge" style="background:#ecfdf5;border:1px solid #a7f3d0;color:#065f46;border-radius:20px">${p.fillRate}%</span></td><td style="padding:0.8rem 1.25rem;color:#64748b">${p.avgWaitTime} days</td></tr>`); }); if(!list||!list.length) tb.innerHTML='<tr><td colspan="3" class="text-center text-muted py-4">No data</td></tr>'; }
function updateBottlenecks(list){ const tb=document.getElementById('bottlenecksTable'); tb.innerHTML=''; (list||[]).forEach(b=>{ tb.insertAdjacentHTML('beforeend', `<tr style="border-bottom:1px solid #f8fafc"><td style="padding:0.8rem 1.25rem;color:#0f172a">${b.issue}</td><td style="padding:0.8rem 1.25rem"><span class="badge" style="background:${b.severity==='warning'?'#fffbeb':'#f8fafc'};border:1px solid ${b.severity==='warning'?'#fde68a':'#e2e8f0'};color:${b.severity==='warning'?'#92400e':'#64748b'};border-radius:20px">${b.impact}</span></td><td style="padding:0.8rem 1.25rem;color:#475569;font-size:0.82rem">${b.recommendation}</td></tr>`); }); if(!list||!list.length) tb.innerHTML='<tr><td colspan="3" class="text-center text-muted py-4">No bottlenecks</td></tr>'; }
function refreshAnalytics(){ loadAnalyticsData(); showAlert('Analytics refreshed','success'); }
function exportAnalytics(){ const t=document.getElementById('timeRangeSelect').value; window.open(`/api/admin/waitlist/analytics/export?timeframe=${t}`, '_blank'); }
function showAlert(m,t='info'){ const h=`<div class="alert alert-${t} alert-dismissible fade show" role="alert" style="border-radius:12px">${m}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>`; const c=document.querySelector('.admin-content'); if(c) c.insertAdjacentHTML('afterbegin',h); }
</script>
@endsection
