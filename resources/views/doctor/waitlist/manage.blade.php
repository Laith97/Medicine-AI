@extends('layouts.doctor')

@section('title', 'Manage Waitlist')

@section('styles')
<style>
.page-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 2rem 0;
    margin-bottom: 2rem;
    border-radius: 0.75rem;
}

.filter-card {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 0.5rem;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
}

.search-box {
    position: relative;
}

.search-box input {
    padding-left: 2.5rem;
}

.search-box .search-icon {
    position: absolute;
    left: 0.75rem;
    top: 50%;
    transform: translateY(-50%);
    color: #6b7280;
}

.filter-row {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
    align-items: end;
}

.waitlist-table {
    background: white;
    border-radius: 0.75rem;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.table {
    margin-bottom: 0;
}

.table th {
    background-color: #f8fafc;
    border-bottom: 2px solid #e5e7eb;
    font-weight: 600;
    color: #374151;
    font-size: 0.875rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    padding: 1rem 0.75rem;
}

.table td {
    padding: 1rem 0.75rem;
    vertical-align: middle;
    border-bottom: 1px solid #f3f4f6;
}

.patient-cell {
    display: flex;
    align-items: center;
}

.patient-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 600;
    margin-right: 0.75rem;
}

.patient-info h6 {
    margin-bottom: 0.25rem;
    font-size: 0.875rem;
}

.patient-info small {
    color: #6b7280;
    font-size: 0.75rem;
}

.priority-badge {
    padding: 0.25rem 0.5rem;
    border-radius: 0.375rem;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.025em;
}

.priority-urgent {
    background-color: #fef2f2;
    color: #dc2626;
    border: 1px solid #fecaca;
}

.priority-high {
    background-color: #fffbeb;
    color: #d97706;
    border: 1px solid #fed7aa;
}

.priority-medium {
    background-color: #eff6ff;
    color: #2563eb;
    border: 1px solid #bfdbfe;
}

.priority-low {
    background-color: #f0fdf4;
    color: #16a34a;
    border: 1px solid #bbf7d0;
}

.status-badge {
    padding: 0.25rem 0.5rem;
    border-radius: 0.375rem;
    font-size: 0.75rem;
    font-weight: 600;
}

.status-active {
    background-color: #dcfce7;
    color: #166534;
}

.status-paused {
    background-color: #fef3c7;
    color: #92400e;
}

.status-cancelled {
    background-color: #fee2e2;
    color: #991b1b;
}

.action-buttons {
    display: flex;
    gap: 0.5rem;
}

.btn-sm {
    padding: 0.375rem 0.75rem;
    font-size: 0.75rem;
    border-radius: 0.375rem;
}

.bulk-actions {
    background: #f8fafc;
    border: 1px solid #e5e7eb;
    border-radius: 0.5rem;
    padding: 1rem;
    margin-bottom: 1rem;
    display: none;
}

.bulk-actions.active {
    display: block;
}

.select-checkbox {
    width: 18px;
    height: 18px;
    accent-color: #3b82f6;
}

.available-slots {
    max-height: 200px;
    overflow-y: auto;
    border: 1px solid #e5e7eb;
    border-radius: 0.5rem;
    padding: 0.5rem;
}

.slot-item {
    padding: 0.5rem;
    border-radius: 0.375rem;
    cursor: pointer;
    transition: all 0.2s;
    margin-bottom: 0.25rem;
}

.slot-item:hover {
    background-color: #eff6ff;
    border-color: #3b82f6;
}

.slot-item.selected {
    background-color: #eff6ff;
    border: 1px solid #3b82f6;
}

.pagination-container {
    display: flex;
    justify-content-between;
    align-items: center;
    margin-top: 1.5rem;
    padding-top: 1.5rem;
    border-top: 1px solid #e5e7eb;
}

.results-info {
    color: #6b7280;
    font-size: 0.875rem;
}

.empty-state {
    text-align: center;
    padding: 3rem;
    color: #6b7280;
}

.empty-state i {
    font-size: 4rem;
    margin-bottom: 1rem;
    color: #d1d5db;
}

.quick-stats {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 0.75rem;
    padding: 1rem;
    margin-bottom: 1.5rem;
}

.quick-stat-item {
    text-align: center;
    padding: 0.5rem;
}

.quick-stat-number {
    font-size: 1.5rem;
    font-weight: 700;
    margin-bottom: 0.25rem;
}

.quick-stat-label {
    font-size: 0.75rem;
    opacity: 0.9;
}

.sortable-header {
    cursor: pointer;
    user-select: none;
}

.sortable-header:hover {
    background-color: #f1f5f9;
}

.sort-icon {
    margin-left: 0.25rem;
    opacity: 0.5;
}

.sort-icon.active {
    opacity: 1;
    color: #3b82f6;
}
</style>
@endsection

@section('content')
<div class="container-fluid" style="background-color: #f8f9fa;">
    <div class="container">
    <!-- Page Header -->
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col">
                <h2 class="mb-1">Manage Waitlist</h2>
                <p class="mb-0 opacity-75">Comprehensive patient waitlist management and slot assignment</p>
            </div>
            <div class="col-auto">
                <a href="{{ route('doctor.waitlist.dashboard') }}" class="btn btn-light">
                    <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                </a>
            </div>
        </div>
    </div>
    </div>
</div>

    <!-- Quick Stats -->
    <div class="quick-stats">
        <div class="row">
            <div class="col-md-3">
                <div class="quick-stat-item">
                    <div class="quick-stat-number">{{ $waitlists->total() }}</div>
                    <div class="quick-stat-label">Total Patients</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="quick-stat-item">
                    <div class="quick-stat-number">{{ $waitlists->where('priority_level', 'urgent')->count() }}</div>
                    <div class="quick-stat-label">Urgent Cases</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="quick-stat-item">
                    <div class="quick-stat-number">{{ count($availableSlots) }}</div>
                    <div class="quick-stat-label">Available Slots</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="quick-stat-item">
                    <div class="quick-stat-number">{{ $waitlists->where('status', 'active')->count() }}</div>
                    <div class="quick-stat-label">Active Waitlists</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters and Search -->
    <div class="filter-card">
        <form method="GET" id="filterForm">
            <div class="filter-row">
                <div class="flex-grow-1">
                    <label class="form-label">Search Patients</label>
                    <div class="search-box">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" class="form-control" name="search"
                               placeholder="Search by name or email..."
                               value="{{ request('search') }}">
                    </div>
                </div>
                <div style="width: 150px;">
                    <label class="form-label">Status</label>
                    <select class="form-select" name="status">
                        <option value="">All Statuses</option>
                        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="paused" {{ request('status') === 'paused' ? 'selected' : '' }}>Paused</option>
                        <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                    </select>
                </div>
                <div style="width: 150px;">
                    <label class="form-label">Priority</label>
                    <select class="form-select" name="priority">
                        <option value="">All Priorities</option>
                        <option value="urgent" {{ request('priority') === 'urgent' ? 'selected' : '' }}>Urgent</option>
                        <option value="high" {{ request('priority') === 'high' ? 'selected' : '' }}>High</option>
                        <option value="medium" {{ request('priority') === 'medium' ? 'selected' : '' }}>Medium</option>
                        <option value="low" {{ request('priority') === 'low' ? 'selected' : '' }}>Low</option>
                    </select>
                </div>
                <div style="width: 150px;">
                    <label class="form-label">Sort By</label>
                    <select class="form-select" name="sort_by">
                        <option value="priority" {{ request('sort_by', 'priority') === 'priority' ? 'selected' : '' }}>Priority</option>
                        <option value="created_at" {{ request('sort_by') === 'created_at' ? 'selected' : '' }}>Date Joined</option>
                        <option value="patient_name" {{ request('sort_by') === 'patient_name' ? 'selected' : '' }}>Patient Name</option>
                    </select>
                </div>
                <div>
                    <label class="form-label">&nbsp;</label>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-filter me-2"></i>Apply Filters
                        </button>
                        <button type="button" class="btn btn-outline-secondary" onclick="clearFilters()">
                            <i class="fas fa-times me-2"></i>Clear
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- Bulk Actions -->
    <div class="bulk-actions" id="bulkActions">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <span id="selectedCount">0</span> patients selected
            </div>
            <div class="d-flex gap-2">
                <select class="form-select form-select-sm" style="width: auto;" id="bulkActionSelect">
                    <option value="">Bulk Actions...</option>
                    <option value="update_priority">Update Priority</option>
                    <option value="update_status">Update Status</option>
                    <option value="remove_patients">Remove from Waitlist</option>
                </select>
                <button class="btn btn-primary btn-sm" onclick="executeBulkAction()">
                    <i class="fas fa-play me-2"></i>Execute
                </button>
            </div>
        </div>
    </div>

    <!-- Waitlist Table -->
    <div class="waitlist-table">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th style="width: 40px;">
                            <input type="checkbox" class="select-checkbox" id="selectAll">
                        </th>
                        <th>Patient</th>
                        <th>Service Type</th>
                        <th class="sortable-header" onclick="sortTable('priority')">
                            Priority
                            <i class="fas fa-sort sort-icon {{ request('sort_by') === 'priority' ? 'active' : '' }}"></i>
                        </th>
                        <th class="sortable-header" onclick="sortTable('status')">
                            Status
                            <i class="fas fa-sort sort-icon {{ request('sort_by') === 'status' ? 'active' : '' }}"></i>
                        </th>
                        <th>Joined Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @if($waitlists->isEmpty())
                        <tr>
                            <td colspan="7">
                                <div class="empty-state">
                                    <i class="fas fa-users"></i>
                                    <h4>No Patients Found</h4>
                                    <p>No patients match your current filters.</p>
                                </div>
                            </td>
                        </tr>
                    @else
                        @foreach($waitlists as $waitlist)
                            <tr>
                                <td>
                                    <input type="checkbox" class="select-checkbox patient-checkbox"
                                           value="{{ $waitlist->id }}" onchange="updateBulkActions()">
                                </td>
                                <td>
                                    <div class="patient-cell">
                                        <div class="patient-avatar">
                                            {{ $waitlist->patient->name[0] }}
                                        </div>
                                        <div class="patient-info">
                                            <h6>{{ $waitlist->patient->name }}</h6>
                                            <small>{{ $waitlist->patient->email }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="text-capitalize">{{ str_replace('-', ' ', $waitlist->service_type) }}</span>
                                </td>
                                <td>
                                    <span class="priority-badge priority-{{ $waitlist->priority_level }}">
                                        {{ ucfirst($waitlist->priority_level) }}
                                    </span>
                                </td>
                                <td>
                                    <span class="status-badge status-{{ $waitlist->status }}">
                                        {{ ucfirst($waitlist->status) }}
                                    </span>
                                </td>
                                <td>
                                    {{ $waitlist->created_at->format('M j, Y') }}
                                    <br>
                                    <small class="text-muted">{{ $waitlist->created_at->diffForHumans() }}</small>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="{{ route('doctor.waitlist.show-patient', $waitlist->id) }}"
                                           class="btn btn-outline-primary btn-sm">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <button class="btn btn-outline-success btn-sm"
                                                onclick="offerSlot({{ $waitlist->id }})"
                                                title="Offer Slot">
                                            <i class="fas fa-calendar-plus"></i>
                                        </button>
                                        <div class="dropdown">
                                            <button class="btn btn-outline-secondary btn-sm dropdown-toggle"
                                                    data-bs-toggle="dropdown">
                                                <i class="fas fa-ellipsis-v"></i>
                                            </button>
                                            <ul class="dropdown-menu">
                                                <li><a class="dropdown-item" href="#" onclick="updatePriority({{ $waitlist->id }})">
                                                    <i class="fas fa-arrow-up me-2"></i>Change Priority
                                                </a></li>
                                                <li><a class="dropdown-item" href="#" onclick="updateStatus({{ $waitlist->id }})">
                                                    <i class="fas fa-pause me-2"></i>Update Status
                                                </a></li>
                                                <li><hr class="dropdown-divider"></li>
                                                <li><a class="dropdown-item text-danger" href="#" onclick="removePatient({{ $waitlist->id }})">
                                                    <i class="fas fa-trash me-2"></i>Remove
                                                </a></li>
                                            </ul>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    @endif
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($waitlists->hasPages())
            <div class="pagination-container">
                <div class="results-info">
                    Showing {{ $waitlists->firstItem() ?? 0 }} to {{ $waitlists->lastItem() ?? 0 }}
                    of {{ $waitlists->total() }} results
                </div>
                <div>
                    {{ $waitlists->appends(request()->query())->links() }}
                </div>
            </div>
        @endif
    </div>

    <!-- Available Slots Sidebar -->
    @if(count($availableSlots) > 0)
        <div class="mt-4">
            <div class="card">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="mb-0">
                        <i class="fas fa-calendar text-primary me-2"></i>Available Slots (Next 14 Days)
                    </h5>
                </div>
                <div class="card-body">
                    <div class="available-slots">
                        @foreach($availableSlots as $index => $slot)
                            <div class="slot-item" onclick="selectSlot('{{ $slot['date'] }}', '{{ $slot['time'] }}', {{ $index }})">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong>{{ \Carbon\Carbon::parse($slot['date'])->format('M j, Y') }}</strong>
                                        <br>
                                        <small class="text-muted">{{ \Carbon\Carbon::parse($slot['time'])->format('g:i A') }}</small>
                                    </div>
                                    <span class="badge bg-success">Available</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
    </div>
</div>

@endsection

<!-- Offer Slot Modal -->
<div class="modal fade" id="offerSlotModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Offer Appointment Slot</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="offerSlotForm">
                    @csrf
                    <input type="hidden" id="offerWaitlistId" name="waitlist_id">
                    <div class="mb-3">
                        <label for="offerPatient" class="form-label">Patient</label>
                        <input type="text" class="form-control" id="offerPatient" readonly>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <label for="offerDate" class="form-label">Date</label>
                            <input type="date" class="form-control" id="offerDate" name="slot_date" required>
                        </div>
                        <div class="col-md-6">
                            <label for="offerTime" class="form-label">Time</label>
                            <input type="time" class="form-control" id="offerTime" name="slot_time" required>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="sendOffer()">Send Offer</button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
let selectedSlots = [];

function sortTable(column) {
    const url = new URL(window.location);
    const currentSort = url.searchParams.get('sort_by');
    const currentOrder = url.searchParams.get('sort_order', 'asc');

    if (currentSort === column) {
        url.searchParams.set('sort_order', currentOrder === 'asc' ? 'desc' : 'asc');
    } else {
        url.searchParams.set('sort_by', column);
        url.searchParams.set('sort_order', 'asc');
    }

    window.location = url.toString();
}

function clearFilters() {
    window.location.href = '{{ route("doctor.waitlist.manage") }}';
}

function updateBulkActions() {
    const checkboxes = document.querySelectorAll('.patient-checkbox:checked');
    const bulkActions = document.getElementById('bulkActions');
    const selectedCount = document.getElementById('selectedCount');

    selectedCount.textContent = checkboxes.length;
    bulkActions.classList.toggle('active', checkboxes.length > 0);
}

function executeBulkAction() {
    const action = document.getElementById('bulkActionSelect').value;
    const checkboxes = document.querySelectorAll('.patient-checkbox:checked');

    if (!action) {
        alert('Please select a bulk action.');
        return;
    }

    if (checkboxes.length === 0) {
        alert('Please select at least one patient.');
        return;
    }

    const waitlistIds = Array.from(checkboxes).map(cb => cb.value);

    if (action === 'remove_patients') {
        if (!confirm(`Remove ${waitlistIds.length} patients from the waitlist?`)) {
            return;
        }
    }

    fetch('/api/doctor/waitlist/bulk-operations', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            operation: action,
            waitlist_ids: waitlistIds,
            value: action.includes('update_') ? prompt('Enter new value:') : null
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    });
}

function offerSlot(waitlistId) {
    // This would fetch patient details and show the offer modal
    fetch(`/api/doctor/waitlist/patient/${waitlistId}`)
        .then(response => response.json())
        .then(data => {
            if (data.waitlist) {
                document.getElementById('offerWaitlistId').value = waitlistId;
                document.getElementById('offerPatient').value = data.waitlist.patient.name;
                new bootstrap.Modal(document.getElementById('offerSlotModal')).show();
            }
        });
}

function sendOffer() {
    const form = document.getElementById('offerSlotForm');
    const formData = new FormData(form);

    fetch('/api/doctor/waitlist/offer-slot', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    });
}

function selectSlot(date, time, index) {
    // Clear previous selections
    document.querySelectorAll('.slot-item').forEach(item => item.classList.remove('selected'));

    // Select current slot
    document.querySelectorAll('.slot-item')[index].classList.add('selected');

    // Fill the form
    document.getElementById('offerDate').value = date;
    document.getElementById('offerTime').value = time;
}

function updatePriority(waitlistId) {
    const newPriority = prompt('Enter new priority (urgent, high, medium, low):');
    if (newPriority && ['urgent', 'high', 'medium', 'low'].includes(newPriority)) {
        fetch(`/api/doctor/waitlist/update-priority/${waitlistId}`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ priority_level: newPriority })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        });
    }
}

function updateStatus(waitlistId) {
    const newStatus = prompt('Enter new status (active, paused, cancelled):');
    if (newStatus && ['active', 'paused', 'cancelled'].includes(newStatus)) {
        fetch(`/api/doctor/waitlist/update-status/${waitlistId}`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ status: newStatus })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        });
    }
}

function removePatient(waitlistId) {
    if (confirm('Are you sure you want to remove this patient from the waitlist?')) {
        fetch(`/api/doctor/waitlist/remove-patient/${waitlistId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        });
    }
}

// Select all functionality
document.getElementById('selectAll').addEventListener('change', function() {
    const checkboxes = document.querySelectorAll('.patient-checkbox');
    checkboxes.forEach(cb => cb.checked = this.checked);
    updateBulkActions();
});

// Auto-submit form on filter change
document.querySelectorAll('#filterForm select').forEach(select => {
    select.addEventListener('change', function() {
        document.getElementById('filterForm').submit();
    });
});

// Debounced search
let searchTimeout;
document.querySelector('input[name="search"]').addEventListener('input', function() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        document.getElementById('filterForm').submit();
    }, 500);
});
</script>
@endsection
