@extends('layouts.admin')

@section('title', 'Monthly Invoice Management')

@push('styles')
<style>
    /* Compact table styles */
    .table th {
        padding: 0.5rem 0.4rem;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        white-space: nowrap;
        border-bottom: 2px solid #dee2e6;
    }

    .table td {
        padding: 0.4rem 0.4rem;
        font-size: 0.8rem;
        vertical-align: middle;
        border-bottom: 1px solid #f1f3f4;
    }

    .table td strong {
        font-size: 0.85rem;
    }

    .table td small {
        font-size: 0.7rem;
    }

    .table .badge {
        font-size: 0.65rem;
        padding: 0.2rem 0.4rem;
    }

    .table .btn {
        padding: 0.2rem 0.4rem;
        font-size: 0.7rem;
    }

    /* Column widths for monthly invoices table */
    .table th:nth-child(1), .table td:nth-child(1) { width: 5%; }
    .table th:nth-child(2), .table td:nth-child(2) { width: 20%; }
    .table th:nth-child(3), .table td:nth-child(3) { width: 15%; }
    .table th:nth-child(4), .table td:nth-child(4) { width: 12%; }
    .table th:nth-child(5), .table td:nth-child(5) { width: 12%; }
    .table th:nth-child(6), .table td:nth-child(6) { width: 12%; }
    .table th:nth-child(7), .table td:nth-child(7) { width: 12%; }
    .table th:nth-child(8), .table td:nth-child(8) { width: 12%; }

    /* Pagination styling */
    .pagination {
        margin-bottom: 0;
    }

    .pagination .page-link {
        color: #DE6262;
        border: 1px solid #dee2e6;
        padding: 0.5rem 0.75rem;
        font-size: 0.875rem;
        border-radius: 0.375rem;
        margin: 0 0.125rem;
    }

    .pagination .page-link:hover {
        color: white;
        background-color: #DE6262;
        border-color: #DE6262;
    }

    .pagination .page-item.active .page-link {
        background-color: #DE6262;
        border-color: #DE6262;
        color: white;
    }

    .pagination .page-item.disabled .page-link {
        color: #6c757d;
        background-color: #fff;
        border-color: #dee2e6;
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">Monthly Invoice Management</h1>
                <div class="btn-group">
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#generateInvoicesModal">
                        <i class="fas fa-plus"></i> Generate Monthly Invoices
                    </button>
                    <button type="button" class="btn btn-warning" onclick="processOverdue()" title="Process overdue invoices and send reminders to users">
                        <i class="fas fa-exclamation-triangle"></i> Process Overdue
                    </button>
                    <button type="button" class="btn btn-success" onclick="processPayments()" title="Check for paid invoices and remove user restrictions">
                        <i class="fas fa-sync"></i> Process Payments
                    </button>
                </div>
            </div>

            <!-- Info Alert -->
            <div class="alert alert-info mb-4">
                <h6><i class="fas fa-info-circle me-2"></i>Button Functions:</h6>
                <div class="row">
                    <div class="col-md-4">
                        <strong>Generate Monthly Invoices:</strong> Creates new monthly invoices for all active users based on their subscription settings.
                    </div>
                    <div class="col-md-4">
                        <strong>Process Overdue:</strong> Identifies overdue invoices, sends reminder notifications to users, and may restrict access for non-payment.
                    </div>
                    <div class="col-md-4">
                        <strong>Process Payments:</strong> Checks Stripe for paid invoices, updates payment status, and removes restrictions from users who have paid.
                    </div>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4 class="card-title">{{ $totalActiveUsers }}</h4>
                                    <p class="card-text">Active Users</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-users fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-danger text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4 class="card-title">{{ $totalRestrictedUsers }}</h4>
                                    <p class="card-text">Restricted Users</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-ban fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4 class="card-title">${{ number_format($totalMonthlyRevenue, 2) }}</h4>
                                    <p class="card-text">Monthly Revenue</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-dollar-sign fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-info text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4 class="card-title">{{ $users->total() }}</h4>
                                    <p class="card-text">Total Users</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-user-md fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" action="{{ route('admin.monthly-invoices.index') }}" class="row g-3">
                        <div class="col-md-3">
                            <label for="status" class="form-label">Status</label>
                            <select name="status" id="status" class="form-select">
                                <option value="">All Users</option>
                                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                                <option value="restricted" {{ request('status') === 'restricted' ? 'selected' : '' }}>Restricted</option>
                                <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="search" class="form-label">Search</label>
                            <input type="text" name="search" id="search" class="form-control" 
                                   placeholder="Search by name or email..." value="{{ request('search') }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i> Filter
                                </button>
                                <a href="{{ route('admin.monthly-invoices.index') }}" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Clear
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Users Table -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Users & Monthly Invoice Settings</h5>
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="showBulkUpdateModal()">
                        <i class="fas fa-edit"></i> Bulk Update
                    </button>
                </div>
                <div class="card-body">
                    @if($users->count() > 0)
                        <form id="bulkForm">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>
                                                <input type="checkbox" id="selectAll" class="form-check-input">
                                            </th>
                                            <th>User</th>
                                            <th>Monthly Amount</th>
                                            <th>Grace Period</th>
                                            <th>Reminder Frequency</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($users as $user)
                                            @php
                                                $setting = $user->monthlyInvoiceSetting;
                                            @endphp
                                            <tr>
                                                <td>
                                                    <input type="checkbox" name="user_ids[]" value="{{ $user->id }}" class="form-check-input user-checkbox">
                                                </td>
                                                <td>
                                                    <div>
                                                        <strong>{{ $user->name }}</strong>
                                                        <br>
                                                        <small class="text-muted">{{ $user->email }}</small>
                                                        @if($user->phone)
                                                            <br>
                                                            <small class="text-muted">{{ $user->phone }}</small>
                                                        @endif
                                                    </div>
                                                </td>
                                                <td>
                                                    @if($setting)
                                                        <span class="fw-bold">{{ $setting->getAmountWithPeriod() }}</span>
                                                    @else
                                                        <span class="text-muted">Not configured</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($setting)
                                                        {{ $setting->grace_period_days }} days
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($setting)
                                                        {{ $setting->reminder_frequency_days }} days
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($setting)
                                                        @if($setting->is_restricted)
                                                            <span class="badge bg-danger">Restricted</span>
                                                        @elseif($setting->is_active)
                                                            <span class="badge bg-success">Active</span>
                                                        @else
                                                            <span class="badge bg-secondary">Inactive</span>
                                                        @endif
                                                    @else
                                                        <span class="badge bg-light text-dark">Not configured</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <a href="{{ route('admin.monthly-invoices.edit', $user) }}" class="btn btn-outline-primary">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        @if($setting && $setting->is_restricted)
                                                            <form method="POST" action="{{ route('admin.monthly-invoices.unrestrict', $user) }}" class="d-inline">
                                                                @csrf
                                                                <button type="submit" class="btn btn-outline-success" 
                                                                        onclick="return confirm('Are you sure you want to unrestrict this user?')">
                                                                    <i class="fas fa-unlock"></i>
                                                                </button>
                                                            </form>
                                                        @else
                                                            <button type="button" class="btn btn-outline-warning" 
                                                                    onclick="showRestrictModal({{ $user->id }}, '{{ $user->name }}')">
                                                                <i class="fas fa-ban"></i>
                                                            </button>
                                                        @endif
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </form>

                        <!-- Pagination -->
                        <div class="d-flex justify-content-center">
                            {{ $users->appends(request()->query())->links() }}
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="fas fa-users fa-3x text-muted mb-3"></i>
                            <h5>No users found</h5>
                            <p class="text-muted">No users match your current filters.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Generate Invoices Modal -->
<div class="modal fade" id="generateInvoicesModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.monthly-invoices.generate') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Generate Monthly Invoices</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="month" class="form-label">Month</label>
                        <input type="month" name="month" id="month" class="form-control" 
                               value="{{ now()->format('Y-m') }}" required>
                        <div class="form-text">Select the month for which to generate invoices.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Generate Invoices</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Bulk Update Modal -->
<div class="modal fade" id="bulkUpdateModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.monthly-invoices.bulk-update') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Bulk Update Settings</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="bulk_action" class="form-label">Action</label>
                        <select name="action" id="bulk_action" class="form-select" required>
                            <option value="">Select action...</option>
                            <option value="activate">Activate Monthly Invoicing</option>
                            <option value="deactivate">Deactivate Monthly Invoicing</option>
                            <option value="restrict">Restrict Access</option>
                            <option value="unrestrict">Remove Restrictions</option>
                        </select>
                    </div>
                    
                    <div id="bulk_settings" style="display: none;">
                        <div class="mb-3">
                            <label for="bulk_billing_amount" class="form-label">Billing Amount ($)</label>
                            <input type="number" name="billing_amount" id="bulk_billing_amount" 
                                   class="form-control" step="0.01" min="0">
                        </div>
                        <div class="mb-3">
                            <label for="bulk_grace_period" class="form-label">Grace Period (days)</label>
                            <input type="number" name="grace_period_days" id="bulk_grace_period" 
                                   class="form-control" min="1" max="30">
                        </div>
                        <div class="mb-3">
                            <label for="bulk_reminder_frequency" class="form-label">Reminder Frequency (days)</label>
                            <input type="number" name="reminder_frequency_days" id="bulk_reminder_frequency" 
                                   class="form-control" min="1" max="14">
                        </div>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <span id="selected_count">0</span> users selected
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="bulkUpdateBtn">Update Selected Users</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Restrict User Modal -->
<div class="modal fade" id="restrictUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="restrictForm">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Restrict User Access</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Restrict access for: <strong id="restrict_user_name"></strong></p>
                    
                    <div class="mb-3">
                        <label class="form-label">Restricted Pages</label>
                        @foreach(\App\Models\MonthlyInvoiceSetting::getAvailablePages() as $route => $name)
                            <div class="form-check">
                                <input type="checkbox" name="restricted_pages[]" value="{{ $route }}" 
                                       class="form-check-input" id="page_{{ $route }}" checked>
                                <label class="form-check-label" for="page_{{ $route }}">
                                    {{ $name }}
                                </label>
                            </div>
                        @endforeach
                    </div>
                    
                    <div class="mb-3">
                        <label for="restriction_message" class="form-label">Custom Restriction Message</label>
                        <textarea name="restriction_message" id="restriction_message" class="form-control" rows="3"
                                  placeholder="Leave empty for default message"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">Restrict User</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
// Select all functionality
document.getElementById('selectAll').addEventListener('change', function() {
    const checkboxes = document.querySelectorAll('.user-checkbox');
    checkboxes.forEach(checkbox => {
        checkbox.checked = this.checked;
    });
    updateSelectedCount();
});

// Update selected count
function updateSelectedCount() {
    const selected = document.querySelectorAll('.user-checkbox:checked').length;
    document.getElementById('selected_count').textContent = selected;
}

// Listen for individual checkbox changes
document.querySelectorAll('.user-checkbox').forEach(checkbox => {
    checkbox.addEventListener('change', updateSelectedCount);
});

// Show/hide bulk settings based on action
document.getElementById('bulk_action').addEventListener('change', function() {
    const settingsDiv = document.getElementById('bulk_settings');
    if (this.value === 'activate') {
        settingsDiv.style.display = 'block';
    } else {
        settingsDiv.style.display = 'none';
    }
});

// Handle bulk update form submission
document.getElementById('bulkUpdateBtn').addEventListener('click', function(e) {
    e.preventDefault();
    
    // Get selected user IDs
    const selectedCheckboxes = document.querySelectorAll('.user-checkbox:checked');
    const selectedIds = Array.from(selectedCheckboxes).map(cb => cb.value);
    
    if (selectedIds.length === 0) {
        alert('Please select at least one user to update.');
        return;
    }
    
    // Add hidden inputs for user IDs
    const form = document.querySelector('#bulkUpdateModal form');
    
    // Remove existing user_ids inputs
    const existingInputs = form.querySelectorAll('input[name="user_ids[]"]');
    existingInputs.forEach(input => input.remove());
    
    // Add new user_ids inputs
    selectedIds.forEach(id => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'user_ids[]';
        input.value = id;
        form.appendChild(input);
    });
    
    // Submit the form
    form.submit();
});

// Show bulk update modal
function showBulkUpdateModal() {
    const selected = document.querySelectorAll('.user-checkbox:checked').length;
    if (selected === 0) {
        alert('Please select at least one user to update.');
        return;
    }
    
    const modal = new bootstrap.Modal(document.getElementById('bulkUpdateModal'));
    modal.show();
}

// Show restrict modal
function showRestrictModal(userId, userName) {
    document.getElementById('restrict_user_name').textContent = userName;
    document.getElementById('restrictForm').action = `/admin/monthly-invoices/${userId}/restrict`;
    new bootstrap.Modal(document.getElementById('restrictUserModal')).show();
}

// Process overdue invoices
function processOverdue() {
    if (confirm('This will process all overdue invoices and send reminders. Continue?')) {
        fetch('{{ route("admin.monthly-invoices.process-overdue") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                alert('✅ ' + data.message);
                location.reload();
            } else {
                alert('❌ Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('❌ An error occurred while processing overdue invoices: ' + error.message);
        });
    }
}

// Process payments
function processPayments() {
    if (confirm('This will check for paid invoices and remove restrictions. Continue?')) {
        fetch('{{ route("admin.monthly-invoices.process-payments") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                alert('✅ ' + data.message);
                location.reload();
            } else {
                alert('❌ Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('❌ An error occurred while processing payments: ' + error.message);
        });
    }
}

// Initialize selected count
updateSelectedCount();
</script>
@endpush