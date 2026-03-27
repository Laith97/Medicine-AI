@extends('layouts.admin')

@section('title', 'Advanced Alerts & Monitoring')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-0">
                        <i class="fas fa-exclamation-triangle text-warning me-2"></i>
                        Advanced Alerts & Monitoring
                    </h4>
                </div>
                <div class="card-body">
                    <!-- Alert Statistics -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card bg-primary text-white">
                                <div class="card-body">
                                    <h5 class="card-title">{{ $stats['total'] }}</h5>
                                    <p class="card-text">Total Alerts</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-danger text-white">
                                <div class="card-body">
                                    <h5 class="card-title">{{ $stats['by_status']['active'] }}</h5>
                                    <p class="card-text">Active Alerts</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-warning text-white">
                                <div class="card-body">
                                    <h5 class="card-title">{{ $stats['by_status']['acknowledged'] }}</h5>
                                    <p class="card-text">Acknowledged</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-success text-white">
                                <div class="card-body">
                                    <h5 class="card-title">{{ $stats['by_status']['resolved'] }}</h5>
                                    <p class="card-text">Resolved</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Filters -->
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <form method="GET" class="row g-3">
                                <div class="col-md-3">
                                    <select name="status" class="form-select">
                                        <option value="">All Statuses</option>
                                        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                                        <option value="acknowledged" {{ request('status') === 'acknowledged' ? 'selected' : '' }}>Acknowledged</option>
                                        <option value="resolved" {{ request('resolved') === 'resolved' ? 'selected' : '' }}>Resolved</option>
                                        <option value="escalated" {{ request('status') === 'escalated' ? 'selected' : '' }}>Escalated</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <select name="severity" class="form-select">
                                        <option value="">All Severities</option>
                                        <option value="critical" {{ request('severity') === 'critical' ? 'selected' : '' }}>Critical</option>
                                        <option value="high" {{ request('severity') === 'high' ? 'selected' : '' }}>High</option>
                                        <option value="medium" {{ request('severity') === 'medium' ? 'selected' : '' }}>Medium</option>
                                        <option value="low" {{ request('severity') === 'low' ? 'selected' : '' }}>Low</option>
                                        <option value="info" {{ request('severity') === 'info' ? 'selected' : '' }}>Info</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <input type="text" name="search" class="form-control" placeholder="Search alerts..." value="{{ request('search') }}">
                                </div>
                                <div class="col-md-2">
                                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Bulk Actions -->
                    <div class="row mb-3">
                        <div class="col-12">
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-warning btn-sm" onclick="bulkAcknowledge()">
                                    <i class="fas fa-check me-1"></i>Bulk Acknowledge
                                </button>
                                <button type="button" class="btn btn-success btn-sm" onclick="bulkResolve()">
                                    <i class="fas fa-check-circle me-1"></i>Bulk Resolve
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Alerts Table -->
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th><input type="checkbox" id="select-all"></th>
                                    <th>Severity</th>
                                    <th>Title</th>
                                    <th>Event Type</th>
                                    <th>Status</th>
                                    <th>Priority Score</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($alerts as $alert)
                                <tr>
                                    <td><input type="checkbox" class="alert-checkbox" value="{{ $alert->id }}"></td>
                                    <td>
                                        <span class="badge bg-{{ $alert->getSeverityColor() }}">
                                            {{ ucfirst($alert->severity) }}
                                        </span>
                                    </td>
                                    <td>
                                        <a href="{{ route('alerts.show', $alert) }}" class="text-decoration-none">
                                            {{ Str::limit($alert->title, 50) }}
                                        </a>
                                    </td>
                                    <td>{{ $alert->event_type }}</td>
                                    <td>
                                        <span class="badge bg-{{ $alert->getStatusColor() }}">
                                            {{ ucfirst($alert->status) }}
                                        </span>
                                    </td>
                                    <td>{{ $alert->priority_score }}</td>
                                    <td>{{ $alert->created_at->diffForHumans() }}</td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('alerts.show', $alert) }}" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            @if($alert->canBeAcknowledged())
                                            <button type="button" class="btn btn-sm btn-outline-warning"
                                                    onclick="acknowledgeAlert('{{ $alert->id }}')">
                                                <i class="fas fa-check"></i>
                                            </button>
                                            @endif
                                            @if($alert->canBeResolved())
                                            <button type="button" class="btn btn-sm btn-outline-success"
                                                    onclick="resolveAlert('{{ $alert->id }}')">
                                                <i class="fas fa-check-circle"></i>
                                            </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="8" class="text-center py-4">
                                        <i class="fas fa-info-circle fa-2x text-muted mb-2"></i>
                                        <p class="text-muted">No alerts found matching your criteria.</p>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    {{ $alerts->appends(request()->query())->links() }}
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modals for acknowledge/resolve -->
<div class="modal fade" id="acknowledgeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Acknowledge Alert</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="acknowledgeForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="acknowledgeNotes" class="form-label">Notes (Optional)</label>
                        <textarea class="form-control" id="acknowledgeNotes" name="notes" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">Acknowledge</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="resolveModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Resolve Alert</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="resolveForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="resolveNotes" class="form-label">Resolution Notes</label>
                        <textarea class="form-control" id="resolveNotes" name="notes" rows="3" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Resolve</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Custom Modal for Bulk Actions -->
<div class="modal fade" id="bulkConfirmModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="bulkConfirmTitle">Confirm Action</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p id="bulkConfirmMessage"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="bulkConfirmBtn">Confirm</button>
            </div>
        </div>
    </div>
</div>

<!-- Custom Modal for Resolution Notes -->
<div class="modal fade" id="bulkResolveModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Resolve Multiple Alerts</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p id="bulkResolveCount"></p>
                <div class="mb-3">
                    <label for="bulkResolveNotes" class="form-label">Resolution Notes</label>
                    <textarea class="form-control" id="bulkResolveNotes" rows="3" required></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" id="bulkResolveConfirmBtn">Resolve</button>
            </div>
        </div>
    </div>
</div>

<script>
// Inline toast notification system - uses textContent for user-provided messages
function showToast(message, type) {
    type = type || 'info';

    // Remove existing toasts
    var existingToasts = document.querySelectorAll('.inline-toast');
    existingToasts.forEach(function(t) { t.remove(); });

    var toast = document.createElement('div');
    toast.className = 'inline-toast';
    toast.setAttribute('role', 'alert');
    toast.setAttribute('aria-live', 'polite');

    var bgColor = type === 'error' ? '#dc3545' : type === 'success' ? '#198754' : type === 'warning' ? '#ffc107' : '#0caf0f';

    toast.style.cssText = 'position: fixed; top: 20px; right: 20px; max-width: 350px; background: white; border: 1px solid #e2e8f0; border-left: 4px solid ' + bgColor + '; border-radius: 8px; box-shadow: 0 10px 25px rgba(0,0,0,0.15); padding: 12px 16px; z-index: 10000; transform: translateX(400px); transition: transform 0.3s ease-in-out; display: flex; align-items: flex-start; gap: 12px; font-size: 14px;';

    // Icon container
    var iconContainer = document.createElement('div');
    iconContainer.style.cssText = 'width: 24px; height: 24px; background: ' + bgColor + '; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0;';

    var iconSvg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
    iconSvg.setAttribute('style', 'width: 14px; height: 14px; color: white;');
    iconSvg.setAttribute('fill', 'none');
    iconSvg.setAttribute('stroke', 'currentColor');
    iconSvg.setAttribute('viewBox', '0 0 24 24');

    var iconPath = document.createElementNS('http://www.w3.org/2000/svg', 'path');
    iconPath.setAttribute('stroke-linecap', 'round');
    iconPath.setAttribute('stroke-linejoin', 'round');
    iconPath.setAttribute('stroke-width', '2');

    if (type === 'error') {
        iconPath.setAttribute('d', 'M6 18L18 6M6 6l12 12');
    } else if (type === 'success') {
        iconPath.setAttribute('d', 'M5 13l4 4L19 7');
    } else if (type === 'warning') {
        iconPath.setAttribute('d', 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z');
    } else {
        iconPath.setAttribute('d', 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z');
    }

    iconSvg.appendChild(iconPath);
    iconContainer.appendChild(iconSvg);

    // Message container
    var messageContainer = document.createElement('div');
    messageContainer.style.cssText = 'flex: 1; min-width: 0;';

    var messagePara = document.createElement('p');
    messagePara.style.cssText = 'margin: 0; color: #1a202c; line-height: 1.4;';
    messagePara.textContent = message;

    messageContainer.appendChild(messagePara);

    // Close button
    var closeBtn = document.createElement('button');
    closeBtn.style.cssText = 'background: none; border: none; color: #a0aec0; cursor: pointer; padding: 0; flex-shrink: 0;';
    closeBtn.setAttribute('aria-label', 'Close');

    var closeSvg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
    closeSvg.setAttribute('style', 'width: 14px; height: 14px;');
    closeSvg.setAttribute('fill', 'none');
    closeSvg.setAttribute('stroke', 'currentColor');
    closeSvg.setAttribute('viewBox', '0 0 24 24');

    var closePath = document.createElementNS('http://www.w3.org/2000/svg', 'path');
    closePath.setAttribute('stroke-linecap', 'round');
    closePath.setAttribute('stroke-linejoin', 'round');
    closePath.setAttribute('stroke-width', '2');
    closePath.setAttribute('d', 'M6 18L18 6M6 6l12 12');

    closeSvg.appendChild(closePath);
    closeBtn.appendChild(closeSvg);
    closeBtn.onclick = function() { toast.remove(); };

    toast.appendChild(iconContainer);
    toast.appendChild(messageContainer);
    toast.appendChild(closeBtn);

    document.body.appendChild(toast);

    // Animate in
    setTimeout(function() {
        toast.style.transform = 'translateX(0)';
    }, 100);

    // Auto remove after 4 seconds
    setTimeout(function() {
        toast.style.transform = 'translateX(400px)';
        setTimeout(function() { toast.remove(); }, 300);
    }, 4000);
}

// Promise-based confirmation modal
function showConfirmModal(title, message) {
    return new Promise(function(resolve) {
        var modalEl = document.getElementById('bulkConfirmModal');
        var modal = new bootstrap.Modal(modalEl);

        document.getElementById('bulkConfirmTitle').textContent = title;
        document.getElementById('bulkConfirmMessage').textContent = message;

        var confirmBtn = document.getElementById('bulkConfirmBtn');
        var newConfirmBtn = confirmBtn.cloneNode(true);
        confirmBtn.parentNode.replaceChild(newConfirmBtn, confirmBtn);

        var handler = function() {
            modal.hide();
            newConfirmBtn.removeEventListener('click', handler);
            setTimeout(function() { resolve(true); }, 150);
        };

        newConfirmBtn.addEventListener('click', handler);

        var hiddenHandler = function() {
            modalEl.removeEventListener('hidden.bs.modal', hiddenHandler);
            setTimeout(function() { resolve(false); }, 150);
        };
        modalEl.addEventListener('hidden.bs.modal', hiddenHandler);

        modal.show();
    });
}

var currentAlertId = null;

function acknowledgeAlert(alertId) {
    currentAlertId = alertId;
    $('#acknowledgeModal').modal('show');
}

function resolveAlert(alertId) {
    currentAlertId = alertId;
    $('#resolveModal').modal('show');
}

async function bulkAcknowledge() {
    var selectedAlerts = Array.from(document.querySelectorAll('.alert-checkbox:checked')).map(function(cb) { return cb.value; });
    if (selectedAlerts.length === 0) {
        showToast('Please select alerts to acknowledge', 'warning');
        return;
    }

    var confirmed = await showConfirmModal('Acknowledge Alerts', 'Acknowledge ' + selectedAlerts.length + ' alert(s)?');
    if (!confirmed) return;

    try {
        var response = await fetch('/admin/alerts/bulk-acknowledge', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({
                alert_ids: selectedAlerts,
                notes: 'Bulk acknowledged'
            })
        });
        var data = await response.json();
        if (data.success) {
            location.reload();
        } else {
            showToast('Failed to acknowledge alerts', 'error');
        }
    } catch (error) {
        showToast('Failed to acknowledge alerts', 'error');
    }
}

async function bulkResolve() {
    var selectedAlerts = Array.from(document.querySelectorAll('.alert-checkbox:checked')).map(function(cb) { return cb.value; });
    if (selectedAlerts.length === 0) {
        showToast('Please select alerts to resolve', 'warning');
        return;
    }

    var modalEl = document.getElementById('bulkResolveModal');
    var modal = new bootstrap.Modal(modalEl);

    document.getElementById('bulkResolveCount').textContent = 'You are about to resolve ' + selectedAlerts.length + ' alert(s). Please provide resolution notes.';
    document.getElementById('bulkResolveNotes').value = '';

    var confirmBtn = document.getElementById('bulkResolveConfirmBtn');
    var newConfirmBtn = confirmBtn.cloneNode(true);
    confirmBtn.parentNode.replaceChild(newConfirmBtn, confirmBtn);

    var resolveHandler = async function() {
        var notes = document.getElementById('bulkResolveNotes').value.trim();
        if (!notes) {
            showToast('Please enter resolution notes', 'warning');
            return;
        }

        modal.hide();
        newConfirmBtn.removeEventListener('click', resolveHandler);

        try {
            var response = await fetch('/admin/alerts/bulk-resolve', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    alert_ids: selectedAlerts,
                    notes: notes
                })
            });
            var data = await response.json();
            if (data.success) {
                location.reload();
            } else {
                showToast('Failed to resolve alerts', 'error');
            }
        } catch (error) {
            showToast('Failed to resolve alerts', 'error');
        }
    };

    newConfirmBtn.addEventListener('click', resolveHandler);

    var hiddenHandler = function() {
        modalEl.removeEventListener('hidden.bs.modal', hiddenHandler);
    };
    modalEl.addEventListener('hidden.bs.modal', hiddenHandler);

    modal.show();
}

// Handle form submissions
document.getElementById('acknowledgeForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    var formData = new FormData(this);

    try {
        var response = await fetch('/admin/alerts/' + currentAlertId + '/acknowledge', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: formData
        });
        var data = await response.json();
        if (data.success) {
            $('#acknowledgeModal').modal('hide');
            location.reload();
        } else {
            showToast('Failed to acknowledge alert', 'error');
        }
    } catch (error) {
        showToast('Failed to acknowledge alert', 'error');
    }
});

document.getElementById('resolveForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    var formData = new FormData(this);

    try {
        var response = await fetch('/admin/alerts/' + currentAlertId + '/resolve', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: formData
        });
        var data = await response.json();
        if (data.success) {
            $('#resolveModal').modal('hide');
            location.reload();
        } else {
            showToast('Failed to resolve alert', 'error');
        }
    } catch (error) {
        showToast('Failed to resolve alert', 'error');
    }
});

// Select all checkbox
document.getElementById('select-all').addEventListener('change', function() {
    var checkboxes = document.querySelectorAll('.alert-checkbox');
    checkboxes.forEach(function(cb) { cb.checked = this.checked; }, this);
});
</script>
@endsection
