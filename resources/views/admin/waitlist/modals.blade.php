<!-- Bulk Operations Modal -->
<div class="modal fade" id="bulkOperationsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Bulk Operations</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6>Priority Adjustments</h6>
                        <div class="mb-3">
                            <label class="form-label">Select Patients</label>
                            <select class="form-select" id="bulkPatientSelect" multiple>
                                <!-- Options loaded dynamically -->
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">New Priority Level</label>
                            <select class="form-select" id="bulkPriorityLevel">
                                <option value="low">Low</option>
                                <option value="medium">Medium</option>
                                <option value="high">High</option>
                                <option value="urgent">Urgent</option>
                            </select>
                        </div>
                        <button class="btn btn-primary" onclick="applyBulkPriority()">Apply Priority</button>
                    </div>
                    <div class="col-md-6">
                        <h6>Status Updates</h6>
                        <div class="mb-3">
                            <label class="form-label">New Status</label>
                            <select class="form-select" id="bulkStatus">
                                <option value="active">Active</option>
                                <option value="paused">Paused</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Reason (Optional)</label>
                            <textarea class="form-control" id="bulkReason" rows="3"></textarea>
                        </div>
                        <button class="btn btn-warning" onclick="applyBulkStatus()">Apply Status</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Priority Adjustment Modal -->
<div class="modal fade" id="priorityAdjustmentModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Manual Priority Adjustments</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Filter by Doctor</label>
                    <select class="form-select" id="priorityDoctorFilter">
                        <option value="">All Doctors</option>
                    </select>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover" id="priorityTable">
                        <thead>
                            <tr>
                                <th>Patient</th>
                                <th>Doctor</th>
                                <th>Current Priority</th>
                                <th>Wait Time</th>
                                <th>Medical Urgency</th>
                                <th>New Priority</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="priorityTableBody">
                            <!-- Data loaded dynamically -->
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="savePriorityAdjustments()">Save Changes</button>
            </div>
        </div>
    </div>
</div>

<!-- Slot Assignment Modal -->
<div class="modal fade" id="slotAssignmentModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Force Slot Assignment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Admin Override:</strong> This action will bypass normal scheduling rules and force-assign a slot.
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Select Patient</label>
                            <select class="form-select" id="slotPatientSelect">
                                <option value="">Choose patient...</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Select Doctor</label>
                            <select class="form-select" id="slotDoctorSelect">
                                <option value="">Choose doctor...</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Appointment Date</label>
                            <input type="date" class="form-control" id="slotDate">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Appointment Time</label>
                            <input type="time" class="form-control" id="slotTime">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Service Type</label>
                            <select class="form-select" id="slotServiceType">
                                <option value="consultation">Consultation</option>
                                <option value="follow_up">Follow-up</option>
                                <option value="procedure">Procedure</option>
                                <option value="emergency">Emergency</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Override Reason (Required)</label>
                    <textarea class="form-control" id="slotOverrideReason" rows="3" required></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" onclick="forceAssignSlot()">
                    <i class="fas fa-exclamation-triangle me-2"></i>Force Assign Slot
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Analytics Modal -->
<div class="modal fade" id="analyticsModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Waitlist Analytics</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-4">
                    <div class="col-md-3">
                        <select class="form-select" id="analyticsTimeframe">
                            <option value="7">Last 7 days</option>
                            <option value="30">Last 30 days</option>
                            <option value="90">Last 90 days</option>
                            <option value="365">Last year</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" id="analyticsDoctor">
                            <option value="">All Doctors</option>
                        </select>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">Wait Time Distribution</h6>
                            </div>
                            <div class="card-body">
                                <canvas id="waitTimeChart"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">Fill Rate Trends</h6>
                            </div>
                            <div class="card-body">
                                <canvas id="fillRateChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">Patient Satisfaction Scores</h6>
                            </div>
                            <div class="card-body">
                                <canvas id="satisfactionChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mt-4">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">Top Metrics</h6>
                            </div>
                            <div class="card-body">
                                <div id="topMetrics">
                                    <!-- Metrics loaded dynamically -->
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">Bottlenecks</h6>
                            </div>
                            <div class="card-body">
                                <div id="bottlenecks">
                                    <!-- Bottlenecks loaded dynamically -->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Modal functionality
function applyBulkPriority() {
    const patientIds = Array.from(document.getElementById('bulkPatientSelect').selectedOptions).map(option => option.value);
    const priority = document.getElementById('bulkPriorityLevel').value;

    if (patientIds.length === 0) {
        alert('Please select at least one patient');
        return;
    }

    fetch('/api/admin/waitlist/bulk-priority', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({ patientIds, priority })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            $('#bulkOperationsModal').modal('hide');
            showAlert('Priority adjustments applied successfully', 'success');
            loadDashboardData();
        } else {
            showAlert('Error applying priority adjustments', 'danger');
        }
    });
}

function applyBulkStatus() {
    const status = document.getElementById('bulkStatus').value;
    const reason = document.getElementById('bulkReason').value;

    fetch('/api/admin/waitlist/bulk-status', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({ status, reason })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            $('#bulkOperationsModal').modal('hide');
            showAlert('Status updates applied successfully', 'success');
            loadDashboardData();
        } else {
            showAlert('Error applying status updates', 'danger');
        }
    });
}

function savePriorityAdjustments() {
    // Collect all priority changes
    const changes = [];
    document.querySelectorAll('#priorityTableBody tr').forEach(row => {
        const patientId = row.dataset.patientId;
        const newPriority = row.querySelector('.priority-select').value;
        const currentPriority = row.dataset.currentPriority;

        if (newPriority !== currentPriority) {
            changes.push({ patientId, priority: newPriority });
        }
    });

    if (changes.length === 0) {
        showAlert('No changes to save', 'info');
        return;
    }

    fetch('/api/admin/waitlist/priority-adjustments', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({ changes })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            $('#priorityAdjustmentModal').modal('hide');
            showAlert('Priority adjustments saved successfully', 'success');
            loadDashboardData();
        } else {
            showAlert('Error saving priority adjustments', 'danger');
        }
    });
}

function forceAssignSlot() {
    const patientId = document.getElementById('slotPatientSelect').value;
    const doctorId = document.getElementById('slotDoctorSelect').value;
    const date = document.getElementById('slotDate').value;
    const time = document.getElementById('slotTime').value;
    const serviceType = document.getElementById('slotServiceType').value;
    const reason = document.getElementById('slotOverrideReason').value;

    if (!patientId || !doctorId || !date || !time || !reason) {
        showAlert('Please fill in all required fields', 'warning');
        return;
    }

    fetch('/api/admin/waitlist/force-assign', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            patientId, doctorId, date, time, serviceType, reason
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            $('#slotAssignmentModal').modal('hide');
            showAlert('Slot assigned successfully', 'success');
            loadDashboardData();
        } else {
            showAlert('Error assigning slot: ' + (data.message || 'Unknown error'), 'danger');
        }
    });
}

// Load data for modals when opened
$('#bulkOperationsModal').on('show.bs.modal', function() {
    loadBulkPatients();
});

$('#priorityAdjustmentModal').on('show.bs.modal', function() {
    loadPriorityData();
});

$('#slotAssignmentModal').on('show.bs.modal', function() {
    loadSlotAssignmentData();
});

$('#analyticsModal').on('show.bs.modal', function() {
    loadAnalyticsData();
});

function loadBulkPatients() {
    fetch('/api/admin/waitlist/patients')
        .then(response => response.json())
        .then(data => {
            const select = document.getElementById('bulkPatientSelect');
            select.innerHTML = '';
            data.patients.forEach(patient => {
                const option = document.createElement('option');
                option.value = patient.id;
                option.textContent = `${patient.name} (${patient.doctor})`;
                select.appendChild(option);
            });
        });
}

function loadPriorityData() {
    fetch('/api/admin/waitlist/priority-data')
        .then(response => response.json())
        .then(data => {
            const tbody = document.getElementById('priorityTableBody');
            tbody.innerHTML = '';

            data.patients.forEach(patient => {
                const row = `
                    <tr data-patient-id="${patient.id}" data-current-priority="${patient.currentPriority}">
                        <td>${patient.name}</td>
                        <td>${patient.doctor}</td>
                        <td><span class="badge bg-${getPriorityColor(patient.currentPriority)}">${patient.currentPriority}</span></td>
                        <td>${patient.waitTime} days</td>
                        <td><span class="badge bg-${patient.urgency === 'high' ? 'danger' : 'warning'}">${patient.urgency}</span></td>
                        <td>
                            <select class="form-select form-select-sm priority-select">
                                <option value="low" ${patient.currentPriority === 'low' ? 'selected' : ''}>Low</option>
                                <option value="medium" ${patient.currentPriority === 'medium' ? 'selected' : ''}>Medium</option>
                                <option value="high" ${patient.currentPriority === 'high' ? 'selected' : ''}>High</option>
                                <option value="urgent" ${patient.currentPriority === 'urgent' ? 'selected' : ''}>Urgent</option>
                            </select>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary" onclick="viewPatientDetails(${patient.id})">
                                <i class="fas fa-eye"></i>
                            </button>
                        </td>
                    </tr>
                `;
                tbody.insertAdjacentHTML('beforeend', row);
            });
        });
}

function loadSlotAssignmentData() {
    // Load patients and doctors for slot assignment
    Promise.all([
        fetch('/api/admin/waitlist/patients'),
        fetch('/api/admin/doctors')
    ])
    .then(responses => Promise.all(responses.map(r => r.json())))
    .then(([patientsData, doctorsData]) => {
        // Populate patient select
        const patientSelect = document.getElementById('slotPatientSelect');
        patientSelect.innerHTML = '<option value="">Choose patient...</option>';
        patientsData.patients.forEach(patient => {
            const option = document.createElement('option');
            option.value = patient.id;
            option.textContent = patient.name;
            patientSelect.appendChild(option);
        });

        // Populate doctor select
        const doctorSelect = document.getElementById('slotDoctorSelect');
        doctorSelect.innerHTML = '<option value="">Choose doctor...</option>';
        doctorsData.doctors.forEach(doctor => {
            const option = document.createElement('option');
            option.value = doctor.id;
            option.textContent = doctor.name;
            doctorSelect.appendChild(option);
        });
    });
}

function loadAnalyticsData() {
    const timeframe = document.getElementById('analyticsTimeframe').value;
    const doctorId = document.getElementById('analyticsDoctor').value;

    fetch(`/api/admin/waitlist/analytics?timeframe=${timeframe}&doctor_id=${doctorId}`)
        .then(response => response.json())
        .then(data => {
            renderCharts(data);
            renderMetrics(data);
        });
}

function renderCharts(data) {
    // This would use Chart.js to render the charts
    // Implementation depends on Chart.js being included
    // console.log('Analytics data:', data);
}

function renderMetrics(data) {
    const metricsContainer = document.getElementById('topMetrics');
    const bottlenecksContainer = document.getElementById('bottlenecks');

    // Render metrics and bottlenecks
    // Implementation based on data structure
}

function getPriorityColor(priority) {
    const colors = {
        'low': 'secondary',
        'medium': 'warning',
        'high': 'danger',
        'urgent': 'danger'
    };
    return colors[priority] || 'secondary';
}

function viewPatientDetails(patientId) {
    window.open(`/admin/patients/${patientId}`, '_blank');
}
</script>
