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

<script>
let currentAlertId = null;

function acknowledgeAlert(alertId) {
    currentAlertId = alertId;
    $('#acknowledgeModal').modal('show');
}

function resolveAlert(alertId) {
    currentAlertId = alertId;
    $('#resolveModal').modal('show');
}

function bulkAcknowledge() {
    const selectedAlerts = Array.from(document.querySelectorAll('.alert-checkbox:checked')).map(cb => cb.value);
    if (selectedAlerts.length === 0) {
        alert('Please select alerts to acknowledge');
        return;
    }

    if (confirm(`Acknowledge ${selectedAlerts.length} alert(s)?`)) {
        fetch('/admin/alerts/bulk-acknowledge', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({
                alert_ids: selectedAlerts,
                notes: 'Bulk acknowledged'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Failed to acknowledge alerts');
            }
        });
    }
}

function bulkResolve() {
    const selectedAlerts = Array.from(document.querySelectorAll('.alert-checkbox:checked')).map(cb => cb.value);
    if (selectedAlerts.length === 0) {
        alert('Please select alerts to resolve');
        return;
    }

    const notes = prompt('Enter resolution notes:');
    if (!notes) return;

    fetch('/admin/alerts/bulk-resolve', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({
            alert_ids: selectedAlerts,
            notes: notes
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Failed to resolve alerts');
        }
    });
}

// Handle form submissions
document.getElementById('acknowledgeForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);

    fetch(`/admin/alerts/${currentAlertId}/acknowledge`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            $('#acknowledgeModal').modal('hide');
            location.reload();
        } else {
            alert('Failed to acknowledge alert');
        }
    });
});

document.getElementById('resolveForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);

    fetch(`/admin/alerts/${currentAlertId}/resolve`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            $('#resolveModal').modal('hide');
            location.reload();
        } else {
            alert('Failed to resolve alert');
        }
    });
});

// Select all checkbox
document.getElementById('select-all').addEventListener('change', function() {
    const checkboxes = document.querySelectorAll('.alert-checkbox');
    checkboxes.forEach(cb => cb.checked = this.checked);
});
</script>
@endsection
