@extends('layouts.admin')

@section('title', 'Kiosk Management')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Kiosk Management</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#addKioskModal">
                            <i class="fas fa-plus"></i> Add Kiosk
                        </button>
                    </div>
                </div>
                <!-- Statistics Dashboard -->
                <div class="card-body border-bottom">
                    <div class="row" id="kioskStats">
                        <div class="col-lg-2 col-6">
                            <div class="small-box bg-info">
                                <div class="inner">
                                    <h3 id="totalKiosks">{{ $kiosks->count() }}</h3>
                                    <p>Total Kiosks</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-desktop"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-2 col-6">
                            <div class="small-box bg-success">
                                <div class="inner">
                                    <h3 id="activeKiosks">{{ $kiosks->where('status', 'active')->count() }}</h3>
                                    <p>Active Kiosks</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-play"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-2 col-6">
                            <div class="small-box bg-warning">
                                <div class="inner">
                                    <h3 id="onlineKiosks">{{ $kiosks->filter->isOnline()->count() }}</h3>
                                    <p>Online Now</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-wifi"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-2 col-6">
                            <div class="small-box bg-danger">
                                <div class="inner">
                                    <h3 id="offlineKiosks">{{ $kiosks->filter(function($kiosk) { return !$kiosk->isOnline(); })->count() }}</h3>
                                    <p>Offline</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-exclamation-triangle"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-2 col-6">
                            <div class="small-box bg-primary">
                                <div class="inner">
                                    <h3 id="totalSessions">0</h3>
                                    <p>Today's Sessions</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-clock"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-2 col-6">
                            <div class="small-box bg-secondary">
                                <div class="inner">
                                    <h3 id="totalRevenue">$0</h3>
                                    <p>Today's Revenue</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-dollar-sign"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Kiosks Table -->
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="kiosksTable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Location</th>
                                    <th>Serial Number</th>
                                    <th>Status</th>
                                    <th>Online Status</th>
                                    <th>Last Ping</th>
                                    <th>Active Sessions</th>
                                    <th>Today's Activity</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($kiosks as $kiosk)
                                <tr data-kiosk-id="{{ $kiosk->id }}">
                                    <td>{{ $kiosk->id }}</td>
                                    <td>
                                        <strong>{{ $kiosk->name }}</strong>
                                        @if($kiosk->configuration && isset($kiosk->configuration['software_version']))
                                            <br><small class="text-muted">v{{ $kiosk->configuration['software_version'] }}</small>
                                        @endif
                                    </td>
                                    <td>{{ $kiosk->location ?? 'Not specified' }}</td>
                                    <td><code>{{ $kiosk->serial_number }}</code></td>
                                    <td>
                                        <span class="badge badge-{{ $kiosk->isActive() ? 'success' : 'secondary' }}">
                                            {{ $kiosk->status }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($kiosk->isOnline())
                                            <span class="badge badge-success">
                                                <i class="fas fa-circle"></i> Online
                                            </span>
                                        @else
                                            <span class="badge badge-danger">
                                                <i class="fas fa-circle"></i> Offline
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($kiosk->last_ping)
                                            <span title="{{ $kiosk->last_ping->format('M j, Y g:i A') }}">
                                                {{ $kiosk->last_ping->diffForHumans() }}
                                            </span>
                                        @else
                                            <em class="text-muted">Never</em>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="active-sessions-count" data-kiosk-id="{{ $kiosk->id }}">
                                            {{ $kiosk->sessions()->active()->count() }}
                                        </span>
                                    </td>
                                    <td>
                                        <small>
                                            Sessions: <span class="today-sessions" data-kiosk-id="{{ $kiosk->id }}">0</span><br>
                                            Check-ins: <span class="today-checkins" data-kiosk-id="{{ $kiosk->id }}">0</span><br>
                                            Revenue: $<span class="today-revenue" data-kiosk-id="{{ $kiosk->id }}">0</span>
                                        </small>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-info" onclick="viewKiosk({{ $kiosk->id }})" title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="btn btn-warning" onclick="editKiosk({{ $kiosk->id }})" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <div class="btn-group" role="group">
                                                <button type="button" class="btn btn-secondary dropdown-toggle" data-toggle="dropdown" title="Actions">
                                                    <i class="fas fa-cogs"></i>
                                                </button>
                                                <div class="dropdown-menu">
                                                    <a class="dropdown-item" href="#" onclick="sendCommand({{ $kiosk->id }}, 'restart')">
                                                        <i class="fas fa-power-off text-warning"></i> Restart
                                                    </a>
                                                    <a class="dropdown-item" href="#" onclick="sendCommand({{ $kiosk->id }}, 'update')">
                                                        <i class="fas fa-download text-info"></i> Update Software
                                                    </a>
                                                    <a class="dropdown-item" href="#" onclick="sendCommand({{ $kiosk->id }}, 'diagnostics')">
                                                        <i class="fas fa-stethoscope text-primary"></i> Run Diagnostics
                                                    </a>
                                                    <div class="dropdown-divider"></div>
                                                    <a class="dropdown-item text-danger" href="#" onclick="deleteKiosk({{ $kiosk->id }})">
                                                        <i class="fas fa-trash"></i> Delete
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="10" class="text-center">
                                        <div class="py-5">
                                            <i class="fas fa-desktop fa-3x text-muted mb-3"></i>
                                            <h4 class="text-muted">No kiosks found</h4>
                                            <p class="text-muted">Create your first kiosk to get started with kiosk management.</p>
                                        </div>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Kiosk Modal -->
<div class="modal fade" id="addKioskModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Add New Kiosk</h4>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form id="addKioskForm">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="name">Name</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    <div class="form-group">
                        <label for="location">Location</label>
                        <input type="text" class="form-control" id="location" name="location">
                    </div>
                    <div class="form-group">
                        <label for="serial_number">Serial Number</label>
                        <input type="text" class="form-control" id="serial_number" name="serial_number" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Kiosk</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Real-time updates
let updateInterval;

function startRealtimeUpdates() {
    updateInterval = setInterval(() => {
        updateKioskStats();
        updateKioskStatuses();
    }, 30000); // Update every 30 seconds
}

function stopRealtimeUpdates() {
    if (updateInterval) {
        clearInterval(updateInterval);
    }
}

function updateKioskStats() {
    fetch('/admin/kiosks/statistics')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('totalKiosks').textContent = data.data.total_kiosks;
                document.getElementById('activeKiosks').textContent = data.data.active_kiosks;
                document.getElementById('onlineKiosks').textContent = data.data.online_kiosks;
                document.getElementById('offlineKiosks').textContent = data.data.total_kiosks - data.data.online_kiosks;
                document.getElementById('totalSessions').textContent = data.data.total_sessions_today;
                document.getElementById('totalRevenue').textContent = '$' + (data.data.total_revenue_today || 0).toFixed(2);
            }
        })
        .catch(console.error);
}

function updateKioskStatuses() {
    const rows = document.querySelectorAll('#kiosksTable tbody tr[data-kiosk-id]');
    rows.forEach(row => {
        const kioskId = row.dataset.kioskId;
        fetch(`/api/kiosks/${kioskId}/status`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateKioskRow(kioskId, data.data);
                }
            })
            .catch(console.error);
    });
}

function updateKioskRow(kioskId, statusData) {
    const row = document.querySelector(`tr[data-kiosk-id="${kioskId}"]`);
    if (!row) return;

    // Update online status
    const onlineStatusCell = row.cells[5];
    const isOnline = statusData.is_online;
    onlineStatusCell.innerHTML = isOnline
        ? '<span class="badge badge-success"><i class="fas fa-circle"></i> Online</span>'
        : '<span class="badge badge-danger"><i class="fas fa-circle"></i> Offline</span>';

    // Update active sessions
    const activeSessionsCell = row.querySelector('.active-sessions-count');
    activeSessionsCell.textContent = statusData.active_session ? 1 : 0;

    // Update today's activity (simplified - would need additional API endpoint)
    // For now, just update the last ping time
    const lastPingCell = row.cells[6];
    if (statusData.kiosk.last_ping) {
        const pingDate = new Date(statusData.kiosk.last_ping);
        lastPingCell.innerHTML = `<span title="${pingDate.toLocaleString()}">${timeAgo(pingDate)}</span>`;
    }
}

function timeAgo(date) {
    const now = new Date();
    const diffInSeconds = Math.floor((now - date) / 1000);

    if (diffInSeconds < 60) return 'Just now';
    if (diffInSeconds < 3600) return `${Math.floor(diffInSeconds / 60)} minutes ago`;
    if (diffInSeconds < 86400) return `${Math.floor(diffInSeconds / 3600)} hours ago`;
    return `${Math.floor(diffInSeconds / 86400)} days ago`;
}

function viewKiosk(id) {
    window.location.href = `/admin/kiosks/${id}`;
}

function editKiosk(id) {
    window.location.href = `/admin/kiosks/${id}/edit`;
}

function sendCommand(kioskId, command) {
    if (command === 'shutdown' && !confirm('Are you sure you want to shutdown this kiosk? This will interrupt any active sessions.')) {
        return;
    }

    // Show loading state
    const button = event.target.closest('a');
    const originalHtml = button.innerHTML;
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
    button.style.pointerEvents = 'none';

    fetch(`/api/kiosks/${kioskId}/command`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({ command: command })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(`Command "${command}" sent successfully to kiosk`, 'success');
        } else {
            showNotification(`Failed to send command: ${data.message}`, 'error');
        }
    })
    .catch(error => {
        showNotification('Error sending command', 'error');
        // console.error('Error:', error);
    })
    .finally(() => {
        button.innerHTML = originalHtml;
        button.style.pointerEvents = 'auto';
    });
}

function deleteKiosk(id) {
    if (confirm('Are you sure you want to delete this kiosk? This action cannot be undone.')) {
        fetch(`/admin/kiosks/${id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => {
            if (response.ok) {
                showNotification('Kiosk deleted successfully', 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                showNotification('Failed to delete kiosk', 'error');
            }
        })
        .catch(error => {
            showNotification('Error deleting kiosk', 'error');
            // console.error('Error:', error);
        });
    }
}

function showNotification(message, type = 'info') {
    // Simple notification - you might want to use a proper notification library
    const notification = document.createElement('div');
    notification.className = `alert alert-${type === 'error' ? 'danger' : type} alert-dismissible fade show position-fixed`;
    notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    notification.innerHTML = `
        ${message}
        <button type="button" class="close" data-dismiss="alert">
            <span>&times;</span>
        </button>
    `;

    document.body.appendChild(notification);

    setTimeout(() => {
        $(notification).alert('close');
    }, 5000);
}

document.getElementById('addKioskForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);

    fetch('/admin/kiosks', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            $('#addKioskModal').modal('hide');
            showNotification('Kiosk created successfully', 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showNotification('Failed to create kiosk', 'error');
        }
    })
    .catch(error => {
        showNotification('Error creating kiosk', 'error');
        // console.error('Error:', error);
    });
});

// Start real-time updates when page loads
document.addEventListener('DOMContentLoaded', function() {
    startRealtimeUpdates();
});

// Stop updates when page unloads
window.addEventListener('beforeunload', stopRealtimeUpdates);
</script>
@endsection
