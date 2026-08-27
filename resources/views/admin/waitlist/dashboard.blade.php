@extends('layouts.admin')

@section('title', 'Waitlist Management Dashboard')

@section('content')
<div class="container-fluid px-0">
    {{-- Header - compatible with admin/dashboard --}}
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4" style="background:linear-gradient(135deg,#1e293b 0%,#334155 100%);border-radius:16px;padding:1.4rem 1.6rem;box-shadow:0 8px 24px rgba(15,23,42,0.12)">
        <div class="d-flex align-items-center gap-3">
            <div style="width:44px;height:44px;border-radius:12px;background:rgba(255,255,255,0.14);display:flex;align-items:center;justify-content:center"><i class="fas fa-list-ol" style="color:#fff;font-size:1.1rem"></i></div>
            <div>
                <h1 style="font-size:1.35rem;font-weight:800;color:#fff;letter-spacing:-0.02em;margin:0">Waitlist Management</h1>
                <p style="font-size:0.78rem;color:rgba(255,255,255,0.75);margin:2px 0 0">Monitor and manage patient waitlists across all doctors</p>
            </div>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-sm text-white" style="background:rgba(255,255,255,0.14);border:1px solid rgba(255,255,255,0.18);color:#fff;border-radius:10px;font-weight:700" onclick="refreshDashboard()"><i class="fas fa-sync-alt me-1"></i> Refresh</button>
            <a href="{{ route('admin.waitlist.analytics') }}" class="btn btn-sm" style="background:#fff;color:#0f172a;border-radius:10px;font-weight:800;border:1px solid #e2e8f0"><i class="fas fa-chart-bar me-1"></i> View Analytics</a>
        </div>
    </div>

    <!-- Statistics Cards - compatible 14px radius 42px icon -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-xl-3"><div class="card border-0 shadow-sm" style="border-radius:14px"><div class="card-body p-3 d-flex align-items-center gap-3"><div style="width:42px;height:42px;border-radius:12px;background:#eff6ff;border:1px solid #dbeafe;color:#2563eb;display:flex;align-items:center;justify-content:center"><i class="fas fa-users"></i></div><div><div style="font-weight:800;color:#0f172a" id="totalWaitlisted">{{ $statistics['totalWaitlisted'] ?? 0 }}</div><small class="text-muted">Total Waitlisted</small><div style="font-size:0.70rem;color:#059669;font-weight:700"><i class="fas fa-arrow-up me-1"></i>+12% from last month</div></div></div></div></div>
        <div class="col-6 col-xl-3"><div class="card border-0 shadow-sm" style="border-radius:14px"><div class="card-body p-3 d-flex align-items-center gap-3"><div style="width:42px;height:42px;border-radius:12px;background:#ecfdf5;border:1px solid #a7f3d0;color:#059669;display:flex;align-items:center;justify-content:center"><i class="fas fa-clock"></i></div><div><div style="font-weight:800;color:#0f172a"><span id="avgWaitTime">{{ $statistics['avgWaitTime'] ?? 0 }} days</span></div><small class="text-muted">Avg Wait Time</small><div style="font-size:0.70rem;color:#dc2626;font-weight:700"><i class="fas fa-arrow-down me-1"></i>-5% from last month</div></div></div></div></div>
        <div class="col-6 col-xl-3"><div class="card border-0 shadow-sm" style="border-radius:14px"><div class="card-body p-3 d-flex align-items-center gap-3"><div style="width:42px;height:42px;border-radius:12px;background:#fffbeb;border:1px solid #fde68a;color:#d97706;display:flex;align-items:center;justify-content:center"><i class="fas fa-chart-line"></i></div><div><div style="font-weight:800;color:#0f172a" id="fillRate">{{ $statistics['fillRate'] ?? 0 }}%</div><small class="text-muted">Fill Rate</small><div style="font-size:0.70rem;color:#059669;font-weight:700"><i class="fas fa-arrow-up me-1"></i>+8% from last month</div></div></div></div></div>
        <div class="col-6 col-xl-3"><div class="card border-0 shadow-sm" style="border-radius:14px"><div class="card-body p-3 d-flex align-items-center gap-3"><div style="width:42px;height:42px;border-radius:12px;background:#f5f3ff;border:1px solid #ddd6fe;color:#7c3aed;display:flex;align-items:center;justify-content:center"><i class="fas fa-star"></i></div><div><div style="font-weight:800;color:#0f172a" id="satisfactionScore">{{ $statistics['satisfactionScore'] ?? '0' }}/5</div><small class="text-muted">Satisfaction</small><div style="font-size:0.70rem;color:#059669;font-weight:700"><i class="fas fa-arrow-up me-1"></i>+0.2 from last month</div></div></div></div></div>
    </div>

    <!-- Active Waitlists by Doctor -->
    <div class="row g-4 mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm" style="border-radius:14px;overflow:hidden">
                <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap gap-2" style="padding:1rem 1.25rem;border-bottom:1px solid #eef2f7">
                    <h5 class="mb-0 d-flex align-items-center gap-2" style="font-weight:800;color:#0f172a;font-size:0.95rem"><i class="fas fa-stethoscope" style="color:#64748b"></i> Active Waitlists by Doctor <span class="badge bg-light border text-muted" style="border-radius:20px" id="waitlistCountBadge">{{ count($waitlists ?? []) }}</span></h5>
                    <div class="d-flex gap-2">
                        <select class="form-select form-select-sm" id="doctorFilter" style="width:auto;border-radius:10px"><option value="">All Doctors</option></select>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0" id="waitlistTable" style="font-size:0.84rem">
                            <thead style="background:#f8fafc">
                                <tr style="font-size:0.75rem;letter-spacing:0.04em;color:#64748b;text-transform:uppercase">
                                    <th style="padding:0.9rem 1.25rem;border:none">Doctor</th>
                                    <th style="padding:0.9rem 1.25rem;border:none">Specialty</th>
                                    <th style="padding:0.9rem 1.25rem;border:none">Waitlisted</th>
                                    <th style="padding:0.9rem 1.25rem;border:none">Avg Wait</th>
                                    <th style="padding:0.9rem 1.25rem;border:none">Fill Rate</th>
                                    <th style="padding:0.9rem 1.25rem;border:none">Priority</th>
                                    <th style="padding:0.9rem 1.25rem;border:none">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="waitlistTableBody">
                                @forelse($waitlists ?? [] as $w)
                                    <tr style="border-bottom:1px solid #f8fafc">
                                        <td style="padding:0.9rem 1.25rem">
                                            <div class="d-flex align-items-center gap-3">
                                                <div style="width:40px;height:40px;border-radius:12px;background:linear-gradient(135deg,#667eea,#764ba2);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:800">{{ strtoupper(substr($w['doctor']['name'] ?? '?',0,1)) }}</div>
                                                <div>
                                                    <div style="font-weight:700;color:#0f172a;font-size:0.84rem">{{ $w['doctor']['name'] }}</div>
                                                    <div style="font-size:.76rem;color:#64748b">{{ $w['doctor']['email'] }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td style="padding:0.9rem 1.25rem"><span class="badge bg-light border text-muted" style="border-radius:20px;font-weight:600">{{ $w['doctor']['specialty'] ?? 'N/A' }}</span></td>
                                        <td style="padding:0.9rem 1.25rem"><span class="badge" style="background:#eff6ff;border:1px solid #dbeafe;color:#1e40af;border-radius:20px">{{ $w['patientCount'] }}</span></td>
                                        <td style="padding:0.9rem 1.25rem;color:#0f172a;font-weight:600">{{ $w['avgWaitTime'] }} days</td>
                                        <td style="padding:0.9rem 1.25rem"><div class="d-flex align-items-center gap-2"><div class="progress" style="width:80px;height:6px;background:#f1f5f9;border-radius:20px"><div class="progress-bar" style="width:{{ $w['fillRate'] }}%;background:#10b981;border-radius:20px"></div></div><small style="font-weight:700;color:#0f172a">{{ $w['fillRate'] }}%</small></div></td>
                                        <td style="padding:0.9rem 1.25rem"><span class="badge" style="background:#fffbeb;border:1px solid #fde68a;color:#92400e;border-radius:20px">{{ $w['priorityCases'] }}</span></td>
                                        <td style="padding:0.9rem 1.25rem">
                                            <div class="btn-group btn-group-sm">
                                                <a href="/admin/waitlist/manage/{{ $w['doctor']['id'] }}" class="btn btn-light border" style="border-radius:8px"><i class="fas fa-eye" style="font-size:0.75rem"></i></a>
                                                <a href="/admin/waitlist/manage/{{ $w['doctor']['id'] }}" class="btn btn-light border" style="border-radius:8px"><i class="fas fa-cog" style="font-size:0.75rem"></i></a>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr id="emptyRow"><td colspan="7" class="text-center py-5"><div style="width:56px;height:56px;border-radius:16px;background:#f8fafc;border:1px dashed #e2e8f0;display:flex;align-items:center;justify-content:center;margin:0 auto 12px;color:#94a3b8"><i class="fas fa-list-ol"></i></div><p class="text-muted small mb-0">No active waitlists found</p></td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="text-center py-4" id="loadingSpinner" style="display:none">
                        <div class="spinner-border text-primary" role="status" style="width:2rem;height:2rem;border-width:0.2em"><span class="visually-hidden">Loading...</span></div>
                        <div class="text-muted small mt-2">Loading waitlists...</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activity + Quick Actions - compatible with dashboard 8+4 -->
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm" style="border-radius:14px;overflow:hidden">
                <div class="card-header bg-white d-flex justify-content-between align-items-center" style="padding:1rem 1.25rem;border-bottom:1px solid #eef2f7">
                    <h5 class="mb-0 d-flex align-items-center gap-2" style="font-weight:800;color:#0f172a;font-size:0.95rem"><i class="fas fa-history" style="color:#64748b"></i> Recent Activity</h5>
                    <span class="badge bg-light border text-muted" style="border-radius:20px">Last 10</span>
                </div>
                <div class="list-group list-group-flush" id="recentActivity" style="padding:0.5rem 0"></div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm" style="border-radius:14px;overflow:hidden">
                <div class="card-header bg-white" style="padding:1rem 1.25rem;border-bottom:1px solid #eef2f7"><h5 class="mb-0" style="font-weight:800;color:#0f172a;font-size:0.95rem">Quick Actions</h5></div>
                <div class="card-body p-3 d-flex flex-column gap-2">
                    <a href="{{ route('admin.waitlist.analytics') }}" class="d-flex align-items-center gap-3 p-3 text-decoration-none" style="background:#f5f3ff;border:1px solid #ddd6fe;border-radius:12px;color:#5b21b6;font-weight:700;font-size:0.84rem"><span style="width:36px;height:36px;border-radius:10px;background:#7c3aed;color:#fff;display:flex;align-items:center;justify-content:center"><i class="fas fa-chart-bar"></i></span> View Analytics <i class="fas fa-chevron-right ms-auto" style="font-size:.7rem;opacity:.6"></i></a>
                    <button class="d-flex align-items-center gap-3 p-3 text-start w-100" style="background:#fffbeb;border:1px solid #fde68a;border-radius:12px;color:#92400e;font-weight:700;font-size:0.84rem" onclick="exportWaitlistData()"><span style="width:36px;height:36px;border-radius:10px;background:#f59e0b;color:#fff;display:flex;align-items:center;justify-content:center"><i class="fas fa-download"></i></span> Export Data <i class="fas fa-chevron-right ms-auto" style="font-size:.7rem;opacity:.6"></i></button>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
let waitlistData = [];
let doctorsData = [];

document.addEventListener('DOMContentLoaded', function() {
    loadDashboardData();
    loadDoctorsList();
});

function loadDashboardData() {
    const spinner = document.getElementById('loadingSpinner');
    if(spinner) spinner.style.display = 'block';
    fetch('/api/admin/waitlist/dashboard')
        .then(response => response.json())
        .then(data => {
            if(spinner) spinner.style.display = 'none';
            updateStatistics(data.statistics);
            updateWaitlistTable(data.waitlists);
            updateRecentActivity(data.recentActivity);
            const badge = document.getElementById('waitlistCountBadge');
            if(badge) badge.textContent = (data.waitlists||[]).length;
        })
        .catch(error => {
            if(spinner) spinner.style.display = 'none';
            console.error('Error loading dashboard data:', error);
            showAlert('Error loading dashboard data', 'danger');
            document.getElementById('waitlistTableBody').innerHTML = '<tr><td colspan="7" class="text-center text-muted py-4">Failed to load data</td></tr>';
        });
}

function updateStatistics(stats) {
    document.getElementById('totalWaitlisted').textContent = stats.totalWaitlisted ?? 0;
    document.getElementById('avgWaitTime').textContent = `${stats.avgWaitTime ?? 0} days`;
    document.getElementById('fillRate').textContent = `${stats.fillRate ?? 0}%`;
    document.getElementById('satisfactionScore').textContent = `${stats.satisfactionScore ?? 0}/5`;
}

function updateWaitlistTable(waitlists) {
    const tbody = document.getElementById('waitlistTableBody');
    tbody.innerHTML = '';
    if (!waitlists || waitlists.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center py-5"><div style="width:56px;height:56px;border-radius:16px;background:#f8fafc;border:1px dashed #e2e8f0;display:flex;align-items:center;justify-content:center;margin:0 auto 12px;color:#94a3b8"><i class="fas fa-list-ol"></i></div><p class="text-muted small mb-0">No active waitlists found</p></td></tr>';
        return;
    }
    waitlists.forEach(waitlist => {
        const initial = (waitlist.doctor.name || '?').charAt(0).toUpperCase();
        const row = `
            <tr style="border-bottom:1px solid #f8fafc">
                <td style="padding:0.9rem 1.25rem">
                    <div class="d-flex align-items-center gap-3">
                        <div style="width:40px;height:40px;border-radius:12px;background:linear-gradient(135deg,#667eea,#764ba2);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:800">${initial}</div>
                        <div>
                            <div style="font-weight:700;color:#0f172a;font-size:0.84rem">${waitlist.doctor.name}</div>
                            <div style="font-size:.76rem;color:#64748b">${waitlist.doctor.email}</div>
                        </div>
                    </div>
                </td>
                <td style="padding:0.9rem 1.25rem"><span class="badge bg-light border text-muted" style="border-radius:20px;font-weight:600">${waitlist.doctor.specialty || 'N/A'}</span></td>
                <td style="padding:0.9rem 1.25rem"><span class="badge" style="background:#eff6ff;border:1px solid #dbeafe;color:#1e40af;border-radius:20px">${waitlist.patientCount}</span></td>
                <td style="padding:0.9rem 1.25rem;color:#0f172a;font-weight:600">${waitlist.avgWaitTime} days</td>
                <td style="padding:0.9rem 1.25rem"><div class="d-flex align-items-center gap-2"><div class="progress" style="width:80px;height:6px;background:#f1f5f9;border-radius:20px"><div class="progress-bar" style="width:${waitlist.fillRate}%;background:#10b981;border-radius:20px"></div></div><small style="font-weight:700;color:#0f172a">${waitlist.fillRate}%</small></div></td>
                <td style="padding:0.9rem 1.25rem"><span class="badge" style="background:#fffbeb;border:1px solid #fde68a;color:#92400e;border-radius:20px">${waitlist.priorityCases}</span></td>
                <td style="padding:0.9rem 1.25rem">
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-light border" style="border-radius:8px" onclick="viewWaitlistDetails(${waitlist.doctor.id})"><i class="fas fa-eye" style="font-size:0.75rem"></i></button>
                        <button class="btn btn-light border" style="border-radius:8px" onclick="manageWaitlist(${waitlist.doctor.id})"><i class="fas fa-cog" style="font-size:0.75rem"></i></button>
                    </div>
                </td>
            </tr>`;
        tbody.insertAdjacentHTML('beforeend', row);
    });
}

function updateRecentActivity(activities) {
    const container = document.getElementById('recentActivity');
    container.innerHTML = '';
    if (!activities || activities.length === 0) {
        container.innerHTML = '<div class="text-center py-4"><div style="width:48px;height:48px;border-radius:12px;background:#f8fafc;border:1px dashed #e2e8f0;display:flex;align-items:center;justify-content:center;margin:0 auto 10px;color:#94a3b8"><i class="fas fa-history"></i></div><p class="text-muted small mb-0">No recent activity</p></div>';
        return;
    }
    activities.forEach(activity => {
        const item = `
            <div class="list-group-item border-0 px-3 py-3 d-flex justify-content-between align-items-center" style="border-bottom:1px solid #f8fafc!important">
                <div class="d-flex align-items-center gap-3">
                    <div style="width:36px;height:36px;border-radius:10px;background:#eff6ff;border:1px solid #dbeafe;color:#2563eb;display:flex;align-items:center;justify-content:center"><i class="fas fa-${activity.icon}"></i></div>
                    <div>
                        <div style="font-weight:700;color:#0f172a;font-size:0.84rem">${activity.title}</div>
                        <div style="font-size:.76rem;color:#64748b">${activity.description}</div>
                    </div>
                </div>
                <div style="font-size:.76rem;color:#94a3b8;white-space:nowrap">${activity.time}</div>
            </div>`;
        container.insertAdjacentHTML('beforeend', item);
    });
}

function loadDoctorsList() {
    fetch('/api/admin/doctors')
        .then(response => response.json())
        .then(data => {
            const select = document.getElementById('doctorFilter');
            (data.doctors||[]).forEach(doctor => {
                const option = document.createElement('option');
                option.value = doctor.id;
                option.textContent = doctor.name;
                select.appendChild(option);
            });
        })
        .catch(error => console.error('Error loading doctors:', error));
}

function refreshDashboard() { loadDashboardData(); showAlert('Dashboard refreshed','success'); }
function viewWaitlistDetails(doctorId) { window.location.href = `/admin/waitlist/manage/${doctorId}`; }
function manageWaitlist(doctorId) { window.location.href = `/admin/waitlist/manage/${doctorId}`; }
function exportWaitlistData() { window.open('/api/admin/waitlist/export', '_blank'); }
function showAlert(message, type = 'info') {
    const html = `<div class="alert alert-${type} alert-dismissible fade show" role="alert" style="border-radius:12px">${message}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>`;
    const c = document.querySelector('.admin-content');
    if(c) c.insertAdjacentHTML('afterbegin', html);
}
</script>
@endsection
