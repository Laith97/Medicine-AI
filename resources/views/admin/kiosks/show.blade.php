@extends('layouts.admin')

@section('title', 'Kiosk Details - ' . $kiosk->name)

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <!-- Kiosk Header -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-desktop mr-2"></i>
                        {{ $kiosk->name }}
                        <span class="badge badge-{{ $kiosk->isActive() ? 'success' : 'secondary' }} ml-2">
                            {{ $kiosk->status }}
                        </span>
                        @if($kiosk->isOnline())
                            <span class="badge badge-success ml-2">Online</span>
                        @else
                            <span class="badge badge-danger ml-2">Offline</span>
                        @endif
                    </h3>
                    <div class="card-tools">
                        <a href="{{ route('kiosks.edit', $kiosk) }}" class="btn btn-warning btn-sm">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                        <button class="btn btn-info btn-sm" onclick="refreshKioskData()">
                            <i class="fas fa-sync"></i> Refresh
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <dl class="row">
                                <dt class="col-sm-4">Serial Number:</dt>
                                <dd class="col-sm-8">{{ $kiosk->serial_number }}</dd>

                                <dt class="col-sm-4">Location:</dt>
                                <dd class="col-sm-8">{{ $kiosk->location ?? 'Not specified' }}</dd>

                                <dt class="col-sm-4">Status:</dt>
                                <dd class="col-sm-8">
                                    <span class="badge badge-{{ $kiosk->isActive() ? 'success' : 'secondary' }}">
                                        {{ $kiosk->status }}
                                    </span>
                                </dd>

                                <dt class="col-sm-4">Last Ping:</dt>
                                <dd class="col-sm-8">
                                    @if($kiosk->last_ping)
                                        {{ $kiosk->last_ping->format('M j, Y g:i A') }}
                                        ({{ $kiosk->last_ping->diffForHumans() }})
                                    @else
                                        Never
                                    @endif
                                </dd>

                                <dt class="col-sm-4">Created:</dt>
                                <dd class="col-sm-8">{{ $kiosk->created_at->format('M j, Y g:i A') }}</dd>
                            </dl>
                        </div>
                        <div class="col-md-6">
                            <h5>Configuration</h5>
                            @if($kiosk->configuration)
                                <pre class="bg-light p-2 rounded">{{ json_encode($kiosk->configuration, JSON_PRETTY_PRINT) }}</pre>
                            @else
                                <p class="text-muted">No configuration set</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row">
        <div class="col-lg-3 col-6">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>{{ $stats['total_sessions'] }}</h3>
                    <p>Total Sessions</p>
                </div>
                <div class="icon">
                    <i class="fas fa-clock"></i>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>{{ $stats['active_sessions'] }}</h3>
                    <p>Active Sessions</p>
                </div>
                <div class="icon">
                    <i class="fas fa-play"></i>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3>{{ $stats['total_checkins'] }}</h3>
                    <p>Total Check-ins</p>
                </div>
                <div class="icon">
                    <i class="fas fa-user-check"></i>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-danger">
                <div class="inner">
                    <h3>${{ number_format($stats['total_revenue'], 2) }}</h3>
                    <p>Total Revenue</p>
                </div>
                <div class="icon">
                    <i class="fas fa-dollar-sign"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Sessions -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Recent Sessions</h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Session ID</th>
                                    <th>Start Time</th>
                                    <th>Duration</th>
                                    <th>Status</th>
                                    <th>Check-ins</th>
                                    <th>Payments</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($kiosk->sessions as $session)
                                <tr>
                                    <td>{{ $session->session_id }}</td>
                                    <td>{{ $session->start_time->format('M j, Y g:i A') }}</td>
                                    <td>{{ $session->getDurationInMinutes() }} min</td>
                                    <td>
                                        <span class="badge badge-{{ $session->status === 'active' ? 'success' : 'secondary' }}">
                                            {{ $session->status }}
                                        </span>
                                    </td>
                                    <td>{{ $session->checkins->count() }}</td>
                                    <td>{{ $session->payments->count() }}</td>
                                    <td>
                                        <button class="btn btn-sm btn-info" onclick="viewSession('{{ $session->session_id }}')">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="text-center">No sessions found</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Control Panel -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Kiosk Control Panel</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <button class="btn btn-success btn-block" onclick="sendCommand('restart')">
                                <i class="fas fa-power-off"></i> Restart Kiosk
                            </button>
                        </div>
                        <div class="col-md-3">
                            <button class="btn btn-warning btn-block" onclick="sendCommand('update')">
                                <i class="fas fa-download"></i> Update Software
                            </button>
                        </div>
                        <div class="col-md-3">
                            <button class="btn btn-info btn-block" onclick="sendCommand('diagnostics')">
                                <i class="fas fa-stethoscope"></i> Run Diagnostics
                            </button>
                        </div>
                        <div class="col-md-3">
                            <button class="btn btn-danger btn-block" onclick="sendCommand('shutdown')" id="shutdownBtn">
                                <i class="fas fa-stop"></i> Shutdown
                            </button>
                        </div>
                    </div>
                    <div class="mt-3">
                        <div id="commandStatus" class="alert" style="display: none;"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function refreshKioskData() {
    location.reload();
}

function viewSession(sessionId) {
    // Open session details in a modal or new page
    window.open(`/admin/kiosk-sessions/${sessionId}`, '_blank');
}

function sendCommand(command) {
    if (command === 'shutdown' && !confirm('Are you sure you want to shutdown this kiosk?')) {
        return;
    }

    const statusDiv = document.getElementById('commandStatus');
    statusDiv.style.display = 'block';
    statusDiv.className = 'alert alert-info';
    statusDiv.innerHTML = 'Sending command...';

    fetch(`/api/kiosks/{{ $kiosk->id }}/command`, {
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
            statusDiv.className = 'alert alert-success';
            statusDiv.innerHTML = `Command "${command}" sent successfully`;
        } else {
            statusDiv.className = 'alert alert-danger';
            statusDiv.innerHTML = `Failed to send command: ${data.message}`;
        }
    })
    .catch(error => {
        statusDiv.className = 'alert alert-danger';
        statusDiv.innerHTML = 'Error sending command';
        // console.error('Error:', error);
    });
}

// Auto-refresh status every 30 seconds
setInterval(() => {
    // Update online/offline status
    fetch(`/api/kiosks/{{ $kiosk->id }}/status`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const status = data.data.is_online ? 'Online' : 'Offline';
                const badgeClass = data.data.is_online ? 'success' : 'danger';
                // Update status badges if needed
            }
        })
        .catch(console.error);
}, 30000);
</script>
@endsection
