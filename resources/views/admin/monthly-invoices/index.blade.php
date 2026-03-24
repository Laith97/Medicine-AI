@extends('layouts.admin')

@section('title', 'Subscription Management')

@push('styles')
<style>
    /* Page-specific styles if needed */
</style>
@endpush

@section('content')
<div class="admin-page">
    <div class="admin-container">
        <!-- Header -->
        <div class="admin-header">
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <div>
                    <h1 class="text-white">Subscription Management</h1>
                    <p class="mb-0">Manage user subscriptions and billing settings (monthly & yearly)</p>
                </div>
                <div class="d-flex gap-2 mt-2 mt-md-0 flex-wrap">
                    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#generateInvoicesModal">
                        <i class="fas fa-plus me-1"></i> Generate Invoices
                    </button>
                    <button type="button" class="btn btn-warning btn-sm" onclick="processOverdue()" title="Process overdue invoices and send reminders to users">
                        <i class="fas fa-exclamation-triangle me-1"></i> Process Overdue
                    </button>
                    <button type="button" class="btn btn-success btn-sm" onclick="processPayments()" title="Check for paid invoices and remove user restrictions">
                        <i class="fas fa-sync me-1"></i> Process Payments
                    </button>
                </div>
            </div>
        </div>

        <!-- Info Alert -->
        <div class="admin-alert alert-info">
            <i class="fas fa-info-circle"></i>
            <div>
                <h6 class="mb-2">Button Functions:</h6>
                <div class="row">
                    <div class="col-md-4">
                        <strong>Generate Invoices:</strong> Creates new invoices for all active users based on their chosen billing cycle (monthly or yearly).
                    </div>
                    <div class="col-md-4">
                        <strong>Process Overdue:</strong> Identifies overdue invoices, sends reminder notifications to users, and may restrict access for non-payment.
                    </div>
                    <div class="col-md-4">
                        <strong>Process Payments:</strong> Checks Stripe for paid invoices, updates payment status, and removes restrictions from users who have paid.
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="admin-stats">
            <div class="admin-stat-card">
                <i class="fas fa-users"></i>
                <h3>{{ $totalActiveUsers }}</h3>
                <p>Active Users</p>
            </div>
            <div class="admin-stat-card">
                <i class="fas fa-ban"></i>
                <h3>{{ $totalRestrictedUsers }}</h3>
                <p>Restricted Users</p>
            </div>
            <div class="admin-stat-card">
                <i class="fas fa-calendar-month"></i>
                <h3>${{ number_format($totalMonthlyRevenue, 0) }}</h3>
                <p>Monthly Potential</p>
            </div>
            <div class="admin-stat-card">
                <i class="fas fa-calendar-year"></i>
                <h3>${{ number_format($totalYearlyRevenue, 0) }}</h3>
                <p>Yearly Potential</p>
            </div>
            <div class="admin-stat-card">
                <i class="fas fa-user-md"></i>
                <h3>{{ $users->total() }}</h3>
                <p>Total Users</p>
            </div>
        </div>

        <!-- Filters -->
        <div class="admin-card">
            <div class="admin-card-header">
                <h5 class="mb-0">Filters</h5>
            </div>
            <div class="admin-card-body" style="padding: 1rem 1.5rem;">
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
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="fas fa-search me-1"></i> Filter
                            </button>
                            <a href="{{ route('admin.monthly-invoices.index') }}" class="btn btn-secondary btn-sm">
                                <i class="fas fa-times me-1"></i> Clear
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Users Table -->
        <div class="admin-card">
            <div class="admin-card-header">
                <h5 class="mb-0">Users & Monthly Invoice Settings</h5>
                <button type="button" class="btn btn-sm btn-outline-primary" onclick="showBulkUpdateModal()">
                    <i class="fas fa-edit me-1"></i> Bulk Update
                </button>
            </div>
            <div class="admin-card-body">
                @if($users->count() > 0)
                    <form id="bulkForm">
                        <div class="admin-table-container">
                            <table class="admin-table invoices-table">
                                    <thead>
                                        <tr>
                                            <th>
                                                <input type="checkbox" id="selectAll" class="form-check-input">
                                            </th>
                                            <th>User</th>
                                            <th>Pricing (M/Y)</th>
                                            <th>Current Cycle</th>
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
                                                    <div class="user-info">
                                                        <h6>{{ $user->name }}</h6>
                                                        <small class="text-muted">{{ $user->email }}</small>
                                                        @if($user->phone)
                                                            <br>
                                                            <small class="text-muted">{{ $user->phone }}</small>
                                                        @endif
                                                    </div>
                                                </td>
                                                <td>
                                                    @if($setting)
                                                        @php
                                                            $monthlyPrice = $setting->monthly_price ?? 0;
                                                            $yearlyPrice = $setting->yearly_price ?? 0;
                                                        @endphp
                                                        <div class="d-flex flex-column">
                                                            <span class="admin-badge {{ $monthlyPrice > 0 ? 'success' : 'secondary' }} mb-1">
                                                                <i class="bi bi-calendar-month"></i>${{ number_format($monthlyPrice, 0) }}/mo
                                                            </span>
                                                            <span class="admin-badge {{ $yearlyPrice > 0 ? 'info' : 'secondary' }}">
                                                                <i class="bi bi-calendar-year"></i>${{ number_format($yearlyPrice, 0) }}/yr
                                                            </span>
                                                        </div>
                                                    @else
                                                        <span class="admin-badge secondary">Not configured</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($setting && $setting->billing_amount > 0)
                                                        @php
                                                            $billingAmount = $setting->billing_amount;
                                                            $monthlyPrice = $setting->monthly_price ?? 0;
                                                            $yearlyPrice = $setting->yearly_price ?? 0;
                                                            
                                                            if ($billingAmount == $yearlyPrice && $yearlyPrice > 0) {
                                                                $currentCycle = 'yearly';
                                                                $badgeClass = 'info';
                                                                $icon = 'bi-calendar-year';
                                                                $text = 'Yearly';
                                                            } elseif ($billingAmount == $monthlyPrice && $monthlyPrice > 0) {
                                                                $currentCycle = 'monthly';
                                                                $badgeClass = 'success';
                                                                $icon = 'bi-calendar-month';
                                                                $text = 'Monthly';
                                                            } else {
                                                                $currentCycle = 'unknown';
                                                                $badgeClass = 'warning';
                                                                $icon = 'bi-question-circle';
                                                                $text = 'Unknown';
                                                            }
                                                        @endphp
                                                        <span class="admin-badge {{ $badgeClass }}">
                                                            <i class="bi {{ $icon }}"></i> {{ $text }}
                                                        </span>
                                                        <br><small class="text-muted">${{ number_format($billingAmount, 2) }}</small>
                                                    @else
                                                        <span class="admin-badge secondary">Not chosen</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($setting)
                                                        <span class="admin-badge info">{{ $setting->grace_period_days }} days</span>
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($setting)
                                                        <span class="admin-badge info">{{ $setting->reminder_frequency_days }} days</span>
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($setting)
                                                        @if($setting->is_restricted)
                                                            <span class="admin-badge danger">Restricted</span>
                                                        @elseif($setting->is_active)
                                                            <span class="admin-badge success">Active</span>
                                                        @else
                                                            <span class="admin-badge secondary">Inactive</span>
                                                        @endif
                                                    @else
                                                        <span class="admin-badge secondary">Not configured</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <div class="admin-actions">
                                                        <a href="{{ route('admin.monthly-invoices.edit', $user) }}" class="admin-btn primary">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        @if($setting && $setting->is_restricted)
                                                            <form method="POST" action="{{ route('admin.monthly-invoices.unrestrict', $user) }}" class="d-inline">
                                                                @csrf
                                                                <button type="submit" class="admin-btn success" 
                                                                        onclick="return confirm('Are you sure you want to unrestrict this user?')"
                                                                        title="Unrestrict User">
                                                                    <i class="fas fa-unlock"></i>
                                                                </button>
                                                            </form>
                                                        @else
                                                            <button type="button" class="admin-btn warning" 
                                                                    onclick="showRestrictModal({{ $user->id }}, '{{ $user->name }}')"
                                                                    title="Restrict User">
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
                    @else
                        <div class="admin-empty-state">
                            <i class="fas fa-users"></i>
                            <p>No users match your current filters.</p>
                        </div>
                    @endif
                </div>
                
                <!-- Pagination -->
                @if($users->hasPages())
                    <div class="admin-pagination">
                        {{ $users->appends(request()->query())->links() }}
                        <div class="pagination-info">
                            Showing {{ $users->firstItem() }} to {{ $users->lastItem() }} of {{ $users->total() }} users
                        </div>
                    </div>
                @endif
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
                    <h5 class="modal-title">Generate Invoices</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="month" class="form-label">Billing Period</label>
                        <input type="month" name="month" id="month" class="form-control" 
                               value="{{ now()->format('Y-m') }}" required>
                        <div class="form-text">
                            Select the billing period. The system will generate invoices based on each user's chosen billing cycle:
                            <br>• <strong>Monthly users:</strong> Will get invoices for this month
                            <br>• <strong>Yearly users:</strong> Will get invoices if their annual billing is due
                        </div>
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
                    
                    <!-- Restriction Settings (shown only for restrict action) -->
                    <div id="bulk_restriction_settings" style="display: none;">
                        <div class="mb-3">
                            <label class="form-label">Pages to Restrict</label>
                            <div class="form-text mb-2">Select which pages should be restricted for users</div>
                            @foreach(\App\Models\MonthlyInvoiceSetting::getAvailablePages() as $route => $name)
                                <div class="form-check">
                                    <input type="checkbox" name="restricted_pages[]" value="{{ $route }}" 
                                           class="form-check-input" id="bulk_page_{{ $route }}" checked>
                                    <label class="form-check-label" for="bulk_page_{{ $route }}">
                                        {{ $name }}
                                    </label>
                                </div>
                            @endforeach
                        </div>
                        
                        <div class="mb-3">
                            <label for="bulk_restriction_message" class="form-label">Custom Restriction Message</label>
                            <textarea name="restriction_message" id="bulk_restriction_message" class="form-control" rows="3"
                                      placeholder="Leave empty for default message"></textarea>
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
    const restrictionDiv = document.getElementById('bulk_restriction_settings');
    
    if (this.value === 'activate') {
        settingsDiv.style.display = 'block';
        restrictionDiv.style.display = 'none';
    } else if (this.value === 'restrict') {
        settingsDiv.style.display = 'none';
        restrictionDiv.style.display = 'block';
    } else {
        settingsDiv.style.display = 'none';
        restrictionDiv.style.display = 'none';
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
            // console.error('Error:', error);
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
            // console.error('Error:', error);
            alert('❌ An error occurred while processing payments: ' + error.message);
        });
    }
}

// Initialize selected count
updateSelectedCount();
</script>
@endpush