@extends('layouts.admin')

@section('title', 'Waitlist Management Dashboard')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h2 class="mb-1">Waitlist Management Dashboard</h2>
                <p class="text-muted mb-0">Monitor and manage patient waitlists across all doctors</p>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-outline-primary" onclick="refreshDashboard()">
                    <i class="fas fa-sync-alt me-2"></i>Refresh
                </button>
                <button class="btn btn-primary" onclick="openBulkOperationsModal()">
                    <i class="fas fa-tasks me-2"></i>Bulk Operations
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-xl-3 col-lg-6 col-md-6 mb-4">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <div class="bg-primary bg-opacity-10 p-3 rounded">
                            <i class="fas fa-users text-primary fa-2x"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h6 class="card-title mb-1 text-muted">Total Waitlisted Patients</h6>
                        <h3 class="mb-0 text-primary" id="totalWaitlisted">0</h3>
                        <small class="text-success">
                            <i class="fas fa-arrow-up me-1"></i>+12% from last month
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-lg-6 col-md-6 mb-4">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <div class="bg-success bg-opacity-10 p-3 rounded">
                            <i class="fas fa-clock text-success fa-2x"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h6 class="card-title mb-1 text-muted">Average Wait Time</h6>
                        <h3 class="mb-0 text-success" id="avgWaitTime">0 days</h3>
                        <small class="text-danger">
                            <i class="fas fa-arrow-down me-1"></i>-5% from last month
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-lg-6 col-md-6 mb-4">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <div class="bg-warning bg-opacity-10 p-3 rounded">
                            <i class="fas fa-chart-line text-warning fa-2x"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h6 class="card-title mb-1 text-muted">Fill Rate</h6>
                        <h3 class="mb-0 text-warning" id="fillRate">0%</h3>
                        <small class="text-success">
                            <i class="fas fa-arrow-up me-1"></i>+8% from last month
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-lg-6 col-md-6 mb-4">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <div class="bg-info bg-opacity-10 p-3 rounded">
                            <i class="fas fa-star text-info fa-2x"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h6 class="card-title mb-1 text-muted">Patient Satisfaction</h6>
                        <h3 class="mb-0 text-info" id="satisfactionScore">0/5</h3>
                        <small class="text-success">
                            <i class="fas fa-arrow-up me-1"></i>+0.2 from last month
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Active Waitlists by Doctor -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Active Waitlists by Doctor</h5>
                    <div class="d-flex gap-2">
                        <select class="form-select form-select-sm" id="doctorFilter" style="width: auto;">
                            <option value="">All Doctors</option>
                        </select>
                        <select class="form-select form-select-sm" id="specialtyFilter" style="width: auto;">
                            <option value="">All Specialties</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover mb-0" id="waitlistTable">
                        <thead class="table-light">
                            <tr>
                                <th>Doctor</th>
                                <th>Specialty</th>
                                <th>Waitlisted Patients</th>
                                <th>Avg Wait Time</th>
                                <th>Fill Rate</th>
                                <th>Priority Cases</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="waitlistTableBody">
                            <!-- Data will be loaded via AJAX -->
                        </tbody>
                    </table>
                </div>
                <div class="text-center mt-3" id="loadingSpinner">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Activity -->
<div class="row">
    <div class="col-lg-8 mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 py-3">
                <h5 class="mb-0">Recent Waitlist Activity</h5>
            </div>
            <div class="card-body">
                <div class="list-group list-group-flush" id="recentActivity">
                    <!-- Activity items will be loaded via AJAX -->
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4 mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 py-3">
                <h5 class="mb-0">Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <button class="btn btn-outline-primary" onclick="openPriorityAdjustmentModal()">
                        <i class="fas fa-sort-amount-up me-2"></i>Adjust Priorities
                    </button>
                    <button class="btn btn-outline-success" onclick="openSlotAssignmentModal()">
                        <i class="fas fa-calendar-check me-2"></i>Force Slot Assignment
                    </button>
                    <button class="btn btn-outline-warning" onclick="openAnalyticsModal()">
                        <i class="fas fa-chart-bar me-2"></i>View Analytics
                    </button>
                    <button class="btn btn-outline-info" onclick="exportWaitlistData()">
                        <i class="fas fa-download me-2"></i>Export Data
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modals will be included here -->
@include('admin.waitlist.modals')

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
    fetch('/api/admin/waitlist/dashboard')
        .then(response => response.json())
        .then(data => {
            updateStatistics(data.statistics);
            updateWaitlistTable(data.waitlists);
            updateRecentActivity(data.recentActivity);
        })
        .catch(error => {
            // console.error('Error loading dashboard data:', error);
            showAlert('Error loading dashboard data', 'danger');
        });
}

function updateStatistics(stats) {
    document.getElementById('totalWaitlisted').textContent = stats.totalWaitlisted || 0;
    document.getElementById('avgWaitTime').textContent = `${stats.avgWaitTime || 0} days`;
    document.getElementById('fillRate').textContent = `${stats.fillRate || 0}%`;
    document.getElementById('satisfactionScore').textContent = `${stats.satisfactionScore || 0}/5`;
}

function updateWaitlistTable(waitlists) {
    const tbody = document.getElementById('waitlistTableBody');
    tbody.innerHTML = '';

    if (waitlists.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted py-4">No active waitlists found</td></tr>';
        return;
    }

    waitlists.forEach(waitlist => {
        const row = `
            <tr>
                <td>
                    <div class="d-flex align-items-center">
                        <div class="avatar-circle bg-primary text-white me-2">
                            ${waitlist.doctor.name.charAt(0)}
                        </div>
                        <div>
                            <div class="fw-semibold">${waitlist.doctor.name}</div>
                            <small class="text-muted">${waitlist.doctor.email}</small>
                        </div>
                    </div>
                </td>
                <td>${waitlist.doctor.specialty || 'N/A'}</td>
                <td>
                    <span class="badge bg-primary">${waitlist.patientCount}</span>
                </td>
                <td>${waitlist.avgWaitTime} days</td>
                <td>
                    <div class="progress" style="width: 80px;">
                        <div class="progress-bar bg-success" role="progressbar"
                             style="width: ${waitlist.fillRate}%">
                            ${waitlist.fillRate}%
                        </div>
                    </div>
                </td>
                <td>
                    <span class="badge bg-warning">${waitlist.priorityCases}</span>
                </td>
                <td>
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-outline-primary" onclick="viewWaitlistDetails(${waitlist.doctor.id})">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn btn-outline-secondary" onclick="manageWaitlist(${waitlist.doctor.id})">
                            <i class="fas fa-cog"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
        tbody.insertAdjacentHTML('beforeend', row);
    });
}

function updateRecentActivity(activities) {
    const container = document.getElementById('recentActivity');
    container.innerHTML = '';

    if (activities.length === 0) {
        container.innerHTML = '<div class="text-center text-muted py-3">No recent activity</div>';
        return;
    }

    activities.forEach(activity => {
        const item = `
            <div class="list-group-item border-0 px-0">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <div class="bg-light rounded-circle p-2 me-3">
                            <i class="fas fa-${activity.icon} text-primary"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1">
                        <div class="fw-semibold">${activity.title}</div>
                        <small class="text-muted">${activity.description}</small>
                    </div>
                    <div class="flex-shrink-0 text-muted small">
                        ${activity.time}
                    </div>
                </div>
            </div>
        `;
        container.insertAdjacentHTML('beforeend', item);
    });
}

function loadDoctorsList() {
    fetch('/api/admin/doctors')
        .then(response => response.json())
        .then(data => {
            const select = document.getElementById('doctorFilter');
            data.doctors.forEach(doctor => {
                const option = document.createElement('option');
                option.value = doctor.id;
                option.textContent = doctor.name;
                select.appendChild(option);
            });
        })
        .catch(error => // console.error('Error loading doctors:', error));
}

function refreshDashboard() {
    loadDashboardData();
    showAlert('Dashboard refreshed', 'success');
}

function viewWaitlistDetails(doctorId) {
    window.location.href = `/admin/waitlist/manage/${doctorId}`;
}

function manageWaitlist(doctorId) {
    window.location.href = `/admin/waitlist/manage/${doctorId}`;
}

function openBulkOperationsModal() {
    // Implementation will be in modals file
    $('#bulkOperationsModal').modal('show');
}

function openPriorityAdjustmentModal() {
    $('#priorityAdjustmentModal').modal('show');
}

function openSlotAssignmentModal() {
    $('#slotAssignmentModal').modal('show');
}

function openAnalyticsModal() {
    $('#analyticsModal').modal('show');
}

function exportWaitlistData() {
    window.open('/api/admin/waitlist/export', '_blank');
}

function showAlert(message, type = 'info') {
    const alertHtml = `
        <div class="alert alert-${type} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    document.querySelector('.admin-content .p-4').insertAdjacentHTML('afterbegin', alertHtml);
}
</script>
@endsection
