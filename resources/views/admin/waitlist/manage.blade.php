@extends('layouts.admin')

@section('title', 'Manage Waitlist - :doctor')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h2 class="mb-1">Waitlist Management</h2>
                <p class="text-muted mb-0" id="doctorInfo">Loading doctor information...</p>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-outline-secondary" onclick="goBack()">
                    <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                </button>
                <button class="btn btn-primary" onclick="refreshWaitlist()">
                    <i class="fas fa-sync-alt me-2"></i>Refresh
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Quick Stats -->
<div class="row mb-4">
    <div class="col-xl-3 col-lg-6 mb-3">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-body text-center">
                <div class="display-4 text-primary mb-2" id="totalPatients">0</div>
                <h6 class="text-muted">Total Patients</h6>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-lg-6 mb-3">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-body text-center">
                <div class="display-4 text-success mb-2" id="avgWaitTime">0</div>
                <h6 class="text-muted">Avg Wait Time (days)</h6>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-lg-6 mb-3">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-body text-center">
                <div class="display-4 text-warning mb-2" id="priorityCases">0</div>
                <h6 class="text-muted">Priority Cases</h6>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-lg-6 mb-3">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-body text-center">
                <div class="display-4 text-info mb-2" id="fillRate">0%</div>
                <h6 class="text-muted">Fill Rate</h6>
            </div>
        </div>
    </div>
</div>

<!-- Filters and Actions -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <div class="d-flex gap-2">
                            <select class="form-select" id="priorityFilter">
                                <option value="">All Priorities</option>
                                <option value="urgent">Urgent</option>
                                <option value="high">High</option>
                                <option value="medium">Medium</option>
                                <option value="low">Low</option>
                            </select>
                            <select class="form-select" id="statusFilter">
                                <option value="">All Statuses</option>
                                <option value="active">Active</option>
                                <option value="paused">Paused</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6 text-end">
                        <div class="d-flex gap-2 justify-content-end">
                            <input type="text" class="form-control" id="searchInput" placeholder="Search patients...">
                            <button class="btn btn-outline-primary" onclick="exportWaitlist()">
                                <i class="fas fa-download me-2"></i>Export
                            </button>
                            <button class="btn btn-success" onclick="addPatientToWaitlist()">
                                <i class="fas fa-plus me-2"></i>Add Patient
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Waitlist Table -->
<div class="row">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 py-3">
                <h5 class="mb-0">Patient Waitlist</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover mb-0" id="waitlistTable">
                        <thead class="table-light">
                            <tr>
                                <th>
                                    <input type="checkbox" id="selectAll">
                                </th>
                                <th>Patient</th>
                                <th>Priority</th>
                                <th>Wait Time</th>
                                <th>Status</th>
                                <th>Next Available</th>
                                <th>Service Type</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="waitlistTableBody">
                            <!-- Data will be loaded via AJAX -->
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="d-flex justify-content-between align-items-center mt-3">
                    <div class="text-muted small" id="paginationInfo">
                        Showing 0 to 0 of 0 entries
                    </div>
                    <nav id="paginationNav">
                        <!-- Pagination will be loaded via AJAX -->
                    </nav>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bulk Actions Bar (shown when items selected) -->
<div class="row mt-3" id="bulkActionsBar" style="display: none;">
    <div class="col-12">
        <div class="card border-primary">
            <div class="card-body py-2">
                <div class="d-flex justify-content-between align-items-center">
                    <span id="selectedCount">0 patients selected</span>
                    <div class="d-flex gap-2">
                        <button class="btn btn-sm btn-outline-primary" onclick="bulkChangePriority()">
                            Change Priority
                        </button>
                        <button class="btn btn-sm btn-outline-warning" onclick="bulkChangeStatus()">
                            Change Status
                        </button>
                        <button class="btn btn-sm btn-outline-success" onclick="bulkAssignSlots()">
                            Assign Slots
                        </button>
                        <button class="btn btn-sm btn-outline-danger" onclick="bulkRemove()">
                            Remove
                        </button>
                        <button class="btn btn-sm btn-outline-secondary" onclick="clearSelection()">
                            Clear Selection
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
let currentDoctorId = null;
let currentPage = 1;
let selectedPatients = new Set();

document.addEventListener('DOMContentLoaded', function() {
    // Get doctor ID from URL
    const urlParams = new URLSearchParams(window.location.search);
    currentDoctorId = urlParams.get('doctor_id') || getDoctorIdFromPath();

    if (currentDoctorId) {
        loadWaitlistData();
        loadDoctorInfo();
    } else {
        showAlert('Doctor ID not found', 'danger');
    }

    // Setup event listeners
    setupEventListeners();
});

function getDoctorIdFromPath() {
    const path = window.location.pathname;
    const match = path.match(/manage\/(\d+)/);
    return match ? match[1] : null;
}

function setupEventListeners() {
    // Filters
    document.getElementById('priorityFilter').addEventListener('change', () => loadWaitlistData());
    document.getElementById('statusFilter').addEventListener('change', () => loadWaitlistData());

    // Search
    document.getElementById('searchInput').addEventListener('input', debounce(() => loadWaitlistData(), 300));

    // Select all checkbox
    document.getElementById('selectAll').addEventListener('change', function() {
        const checkboxes = document.querySelectorAll('.patient-checkbox');
        checkboxes.forEach(cb => {
            cb.checked = this.checked;
            updateSelection(cb.value, this.checked);
        });
        updateBulkActionsBar();
    });
}

function loadDoctorInfo() {
    fetch(`/api/admin/doctors/${currentDoctorId}`)
        .then(response => response.json())
        .then(data => {
            document.getElementById('doctorInfo').textContent = `${data.doctor.name} - ${data.doctor.specialty}`;
            document.title = `Manage Waitlist - ${data.doctor.name}`;
        })
        .catch(error => // console.error('Error loading doctor info:', error));
}

function loadWaitlistData(page = 1) {
    currentPage = page;
    const priority = document.getElementById('priorityFilter').value;
    const status = document.getElementById('statusFilter').value;
    const search = document.getElementById('searchInput').value;

    const params = new URLSearchParams({
        doctor_id: currentDoctorId,
        page,
        priority,
        status,
        search
    });

    fetch(`/api/admin/waitlist/manage?${params}`)
        .then(response => response.json())
        .then(data => {
            updateStats(data.stats);
            updateWaitlistTable(data.patients);
            updatePagination(data.pagination);
        })
        .catch(error => {
            // console.error('Error loading waitlist data:', error);
            showAlert('Error loading waitlist data', 'danger');
        });
}

function updateStats(stats) {
    document.getElementById('totalPatients').textContent = stats.totalPatients || 0;
    document.getElementById('avgWaitTime').textContent = stats.avgWaitTime || 0;
    document.getElementById('priorityCases').textContent = stats.priorityCases || 0;
    document.getElementById('fillRate').textContent = `${stats.fillRate || 0}%`;
}

function updateWaitlistTable(patients) {
    const tbody = document.getElementById('waitlistTableBody');
    tbody.innerHTML = '';

    if (patients.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" class="text-center text-muted py-4">No patients in waitlist</td></tr>';
        return;
    }

    patients.forEach(patient => {
        const row = `
            <tr>
                <td>
                    <input type="checkbox" class="patient-checkbox" value="${patient.id}"
                           onchange="updateSelection(${patient.id}, this.checked)">
                </td>
                <td>
                    <div class="d-flex align-items-center">
                        <div class="avatar-circle bg-primary text-white me-2">
                            ${patient.name.charAt(0)}
                        </div>
                        <div>
                            <div class="fw-semibold">${patient.name}</div>
                            <small class="text-muted">${patient.email}</small>
                        </div>
                    </div>
                </td>
                <td>
                    <span class="badge bg-${getPriorityColor(patient.priority)}">${patient.priority}</span>
                </td>
                <td>${patient.waitTime} days</td>
                <td>
                    <span class="badge bg-${getStatusColor(patient.status)}">${patient.status}</span>
                </td>
                <td>${patient.nextAvailable || 'N/A'}</td>
                <td>${patient.serviceType}</td>
                <td>
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-outline-primary" onclick="viewPatient(${patient.id})">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn btn-outline-secondary" onclick="editPatient(${patient.id})">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-outline-success" onclick="assignSlot(${patient.id})">
                            <i class="fas fa-calendar-check"></i>
                        </button>
                        <div class="dropdown">
                            <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                <i class="fas fa-ellipsis-v"></i>
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="#" onclick="changePriority(${patient.id})">Change Priority</a></li>
                                <li><a class="dropdown-item" href="#" onclick="changeStatus(${patient.id})">Change Status</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger" href="#" onclick="removeFromWaitlist(${patient.id})">Remove</a></li>
                            </ul>
                        </div>
                    </div>
                </td>
            </tr>
        `;
        tbody.insertAdjacentHTML('beforeend', row);
    });
}

function updatePagination(pagination) {
    const info = document.getElementById('paginationInfo');
    const nav = document.getElementById('paginationNav');

    info.textContent = `Showing ${pagination.from || 0} to ${pagination.to || 0} of ${pagination.total || 0} entries`;

    // Simple pagination (can be enhanced)
    nav.innerHTML = '';
    if (pagination.last_page > 1) {
        const ul = document.createElement('ul');
        ul.className = 'pagination pagination-sm mb-0';

        // Previous
        const prevLi = document.createElement('li');
        prevLi.className = `page-item ${pagination.current_page === 1 ? 'disabled' : ''}`;
        prevLi.innerHTML = `<a class="page-link" href="#" onclick="loadWaitlistData(${pagination.current_page - 1})">Previous</a>`;
        ul.appendChild(prevLi);

        // Pages
        for (let i = Math.max(1, pagination.current_page - 2); i <= Math.min(pagination.last_page, pagination.current_page + 2); i++) {
            const li = document.createElement('li');
            li.className = `page-item ${i === pagination.current_page ? 'active' : ''}`;
            li.innerHTML = `<a class="page-link" href="#" onclick="loadWaitlistData(${i})">${i}</a>`;
            ul.appendChild(li);
        }

        // Next
        const nextLi = document.createElement('li');
        nextLi.className = `page-item ${pagination.current_page === pagination.last_page ? 'disabled' : ''}`;
        nextLi.innerHTML = `<a class="page-link" href="#" onclick="loadWaitlistData(${pagination.current_page + 1})">Next</a>`;
        ul.appendChild(nextLi);

        nav.appendChild(ul);
    }
}

function updateSelection(patientId, selected) {
    if (selected) {
        selectedPatients.add(patientId);
    } else {
        selectedPatients.delete(patientId);
    }
    updateBulkActionsBar();
}

function updateBulkActionsBar() {
    const bar = document.getElementById('bulkActionsBar');
    const count = document.getElementById('selectedCount');

    if (selectedPatients.size > 0) {
        bar.style.display = 'block';
        count.textContent = `${selectedPatients.size} patient${selectedPatients.size > 1 ? 's' : ''} selected`;
    } else {
        bar.style.display = 'none';
    }
}

function clearSelection() {
    selectedPatients.clear();
    document.querySelectorAll('.patient-checkbox').forEach(cb => cb.checked = false);
    document.getElementById('selectAll').checked = false;
    updateBulkActionsBar();
}

// Action functions
function viewPatient(patientId) {
    window.open(`/admin/patients/${patientId}`, '_blank');
}

function editPatient(patientId) {
    window.open(`/admin/patients/${patientId}/edit`, '_blank');
}

function assignSlot(patientId) {
    if (confirm('Assign the next available slot to this patient?')) {
        fetch(`/api/admin/waitlist/assign-slot`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({ patientId, doctorId: currentDoctorId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert('Slot assigned successfully', 'success');
                loadWaitlistData();
            } else {
                showAlert('Error assigning slot', 'danger');
            }
        });
    }
}

function changePriority(patientId) {
    const priority = prompt('Enter new priority (low, medium, high, urgent):');
    if (priority && ['low', 'medium', 'high', 'urgent'].includes(priority)) {
        updatePatientPriority(patientId, priority);
    }
}

function changeStatus(patientId) {
    const status = prompt('Enter new status (active, paused, cancelled):');
    if (status && ['active', 'paused', 'cancelled'].includes(status)) {
        updatePatientStatus(patientId, status);
    }
}

function removeFromWaitlist(patientId) {
    if (confirm('Remove this patient from the waitlist?')) {
        fetch(`/api/admin/waitlist/remove-patient`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({ patientId, doctorId: currentDoctorId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert('Patient removed from waitlist', 'success');
                loadWaitlistData();
            } else {
                showAlert('Error removing patient', 'danger');
            }
        });
    }
}

function updatePatientPriority(patientId, priority) {
    fetch(`/api/admin/waitlist/update-priority`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({ patientId, priority })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('Priority updated successfully', 'success');
            loadWaitlistData();
        } else {
            showAlert('Error updating priority', 'danger');
        }
    });
}

function updatePatientStatus(patientId, status) {
    fetch(`/api/admin/waitlist/update-status`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({ patientId, status })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('Status updated successfully', 'success');
            loadWaitlistData();
        } else {
            showAlert('Error updating status', 'danger');
        }
    });
}

// Bulk operations
function bulkChangePriority() {
    const priority = prompt('Enter new priority for selected patients (low, medium, high, urgent):');
    if (priority && ['low', 'medium', 'high', 'urgent'].includes(priority)) {
        bulkUpdate('priority', priority);
    }
}

function bulkChangeStatus() {
    const status = prompt('Enter new status for selected patients (active, paused, cancelled):');
    if (status && ['active', 'paused', 'cancelled'].includes(status)) {
        bulkUpdate('status', status);
    }
}

function bulkAssignSlots() {
    if (confirm(`Assign slots to ${selectedPatients.size} selected patients?`)) {
        bulkUpdate('assign_slots', null);
    }
}

function bulkRemove() {
    if (confirm(`Remove ${selectedPatients.size} selected patients from waitlist?`)) {
        bulkUpdate('remove', null);
    }
}

function bulkUpdate(action, value) {
    fetch(`/api/admin/waitlist/bulk-update`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            patientIds: Array.from(selectedPatients),
            action,
            value
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('Bulk operation completed successfully', 'success');
            clearSelection();
            loadWaitlistData();
        } else {
            showAlert('Error performing bulk operation', 'danger');
        }
    });
}

// Utility functions
function getPriorityColor(priority) {
    const colors = {
        'urgent': 'danger',
        'high': 'warning',
        'medium': 'info',
        'low': 'secondary'
    };
    return colors[priority] || 'secondary';
}

function getStatusColor(status) {
    const colors = {
        'active': 'success',
        'paused': 'warning',
        'cancelled': 'danger'
    };
    return colors[status] || 'secondary';
}

function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

function goBack() {
    window.location.href = '/admin/waitlist/dashboard';
}

function refreshWaitlist() {
    loadWaitlistData();
    showAlert('Waitlist refreshed', 'success');
}

function exportWaitlist() {
    const params = new URLSearchParams({
        doctor_id: currentDoctorId,
        priority: document.getElementById('priorityFilter').value,
        status: document.getElementById('statusFilter').value,
        search: document.getElementById('searchInput').value
    });
    window.open(`/api/admin/waitlist/export?${params}`, '_blank');
}

function addPatientToWaitlist() {
    // This would open a modal or redirect to add patient page
    window.location.href = `/admin/waitlist/add-patient?doctor_id=${currentDoctorId}`;
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

    // Auto remove after 5 seconds
    setTimeout(() => {
        const alert = container.querySelector('.alert');
        if (alert) alert.remove();
    }, 5000);
}
</script>
@endsection
