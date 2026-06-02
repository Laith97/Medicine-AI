@extends('master')

@section('title', 'Waitlist Dashboard')

@section('styles')
<style>
.waitlist-card {
    transition: transform 0.2s ease-in-out;
    border: 1px solid #e3e6f0;
}

.waitlist-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.priority-badge {
    font-size: 0.75rem;
    font-weight: 600;
    padding: 0.25rem 0.5rem;
    border-radius: 0.375rem;
}

.priority-urgent {
    background-color: #dc2626;
    color: white;
}

.priority-high {
    background-color: #ea580c;
    color: white;
}

.priority-medium {
    background-color: #d97706;
    color: white;
}

.priority-low {
    background-color: #65a30d;
    color: white;
}

.position-indicator {
    font-size: 1.5rem;
    font-weight: 700;
    color: #2563eb;
}

.wait-time-circle {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 700;
    font-size: 1.25rem;
}

.status-indicator {
    display: inline-block;
    width: 8px;
    height: 8px;
    border-radius: 50%;
    margin-right: 8px;
}

.status-active { background-color: #10b981; }
.status-paused { background-color: #f59e0b; }
.status-cancelled { background-color: #ef4444; }
.status-fulfilled { background-color: #6366f1; }

.offer-countdown {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    color: white;
    padding: 1rem;
    border-radius: 0.5rem;
    text-align: center;
    font-weight: 600;
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

.recent-entry {
    border-left: 4px solid #3b82f6;
    background-color: #f8fafc;
    padding: 1rem;
    margin-bottom: 0.5rem;
    border-radius: 0 0.375rem 0.375rem 0;
}

.entry-offered { border-left-color: #f59e0b; background-color: #fffbeb; }
.entry-accepted { border-left-color: #10b981; background-color: #f0fdf4; }
.entry-declined { border-left-color: #ef4444; background-color: #fef2f2; }
</style>
@endsection

@section('content')
<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-1">Waitlist Dashboard</h2>
                    <p class="text-muted mb-0">Manage your appointment waitlists and preferences</p>
                </div>
                <div>
                    <a href="{{ route('patient.waitlist.join') }}" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Join New Waitlist
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card waitlist-card">
                <div class="card-body text-center">
                    <h3 class="text-primary mb-2">{{ $stats['total_active_waitlists'] }}</h3>
                    <p class="text-muted mb-0">Active Waitlists</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card waitlist-card">
                <div class="card-body text-center">
                    <h3 class="text-warning mb-2">{{ $stats['pending_offers'] }}</h3>
                    <p class="text-muted mb-0">Pending Offers</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card waitlist-card">
                <div class="card-body text-center">
                    <h3 class="text-success mb-2">{{ $stats['total_entries'] }}</h3>
                    <p class="text-muted mb-0">Total Opportunities</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card waitlist-card">
                <div class="card-body text-center">
                    <h3 class="text-info mb-2">{{ $preferences->count() }}</h3>
                    <p class="text-muted mb-0">Saved Preferences</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Active Waitlists -->
        <div class="col-lg-8 mb-4">
            <div class="card waitlist-card">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="mb-0">Your Active Waitlists</h5>
                </div>
                <div class="card-body">
                    @if($activeWaitlists->isEmpty())
                        <div class="empty-state">
                            <i class="fas fa-list-ul"></i>
                            <h4>No Active Waitlists</h4>
                            <p>You haven't joined any waitlists yet. Find a doctor and get in line for your appointment.</p>
                            <a href="{{ route('patient.waitlist.join') }}" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>Join a Waitlist
                            </a>
                        </div>
                    @else
                        @foreach($activeWaitlists as $waitlist)
                            <div class="waitlist-item mb-3 p-3 border rounded" id="waitlist-{{ $waitlist->id }}">
                                <div class="row align-items-center">
                                    <div class="col-md-6">
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-circle bg-primary text-white me-3">
                                                {{ $waitlist->doctor->name[0] }}
                                            </div>
                                            <div>
                                                <h6 class="mb-1">{{ $waitlist->doctor->name }}</h6>
                                                <p class="text-muted mb-1">{{ $waitlist->doctor->specialty }}</p>
                                                <div class="d-flex align-items-center">
                                                    <span class="priority-badge priority-{{ $waitlist->priority_level }} me-2">
                                                        {{ ucfirst($waitlist->priority_level) }}
                                                    </span>
                                                    <span class="text-muted small">{{ $waitlist->service_type }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3 text-center">
                                        <div class="position-indicator" id="position-{{ $waitlist->id }}">
                                            <span id="position-text-{{ $waitlist->id }}">Loading...</span>
                                        </div>
                                        <small class="text-muted">Position</small>
                                    </div>
                                    <div class="col-md-3 text-end">
                                        <a href="{{ route('patient.waitlist.status', $waitlist->id) }}"
                                           class="btn btn-outline-primary btn-sm">
                                            View Details
                                        </a>
                                    </div>
                                </div>

                                <!-- Offer Alert -->
                                @if($waitlist->entries->where('status', 'offered')->count() > 0)
                                    <div class="offer-countdown mt-3" id="offer-alert-{{ $waitlist->id }}">
                                        <i class="fas fa-clock me-2"></i>
                                        You have {{ $waitlist->entries->where('status', 'offered')->count() }}
                                        pending offer(s) -
                                        <a href="{{ route('patient.waitlist.status', $waitlist->id) }}" class="text-white fw-bold">
                                            Respond Now
                                        </a>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>
        </div>

        <!-- Recent Activity & Quick Actions -->
        <div class="col-lg-4">
            <!-- Recent Activity -->
            <div class="card waitlist-card mb-4">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="mb-0">Recent Activity</h5>
                </div>
                <div class="card-body">
                    @if($recentEntries->isEmpty())
                        <p class="text-muted text-center">No recent activity</p>
                    @else
                        @foreach($recentEntries->take(5) as $entry)
                            <div class="recent-entry entry-{{ $entry->status }}">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="mb-1">{{ $entry->waitlist->doctor->name }}</h6>
                                        <p class="mb-1 small">
                                            {{ \Carbon\Carbon::parse($entry->slot_date)->format('M j, Y') }}
                                            at {{ \Carbon\Carbon::parse($entry->slot_time)->format('g:i A') }}
                                        </p>
                                        <span class="badge bg-{{ $entry->status === 'offered' ? 'warning' : ($entry->status === 'accepted' ? 'success' : 'danger') }}">
                                            {{ ucfirst($entry->status) }}
                                        </span>
                                    </div>
                                    @if($entry->status === 'offered')
                                        <a href="{{ route('patient.waitlist.offer', $entry->id) }}" class="btn btn-sm btn-primary">
                                            View
                                        </a>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card waitlist-card">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="mb-0">Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="{{ route('patient.waitlist.join') }}" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Join New Waitlist
                        </a>
                        <a href="{{ route('patient.waitlist.preferences') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-cog me-2"></i>Manage Preferences
                        </a>
                        <button class="btn btn-outline-info" onclick="refreshPositions()">
                            <i class="fas fa-sync-alt me-2"></i>Refresh Positions
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
// Auto-refresh waitlist positions every 30 seconds
setInterval(refreshPositions, 30000);

function refreshPositions() {
    @foreach($activeWaitlists as $waitlist)
        updateWaitlistPosition({{ $waitlist->id }});
    @endforeach
}

function updateWaitlistPosition(waitlistId) {
    fetch(`/api/patient/waitlist/position/${waitlistId}`)
        .then(response => response.json())
        .then(data => {
            if (data.position) {
                const positionElement = document.getElementById(`position-text-${waitlistId}`);
                if (positionElement) {
                    positionElement.textContent = `${data.position.position} of ${data.position.total_waitlisted}`;
                }
            }
        })
        .catch(error => {
            console.error('Error updating position:', error);
        });
}

// Initial load of positions
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(refreshPositions, 1000);
});

// Add click handlers for quick actions
document.querySelectorAll('[data-action]').forEach(button => {
    button.addEventListener('click', function() {
        const action = this.dataset.action;
        const waitlistId = this.dataset.waitlistId;

        switch(action) {
            case 'view-details':
                window.location.href = `/patient/waitlist/status/${waitlistId}`;
                break;
            case 'leave-waitlist':
                if (confirm('Are you sure you want to leave this waitlist?')) {
                    leaveWaitlist(waitlistId);
                }
                break;
        }
    });
});

function leaveWaitlist(waitlistId) {
    fetch(`/patient/waitlist/leave/${waitlistId}`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Content-Type': 'application/json',
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById(`waitlist-${waitlistId}`).remove();
            location.reload(); // Refresh the page to update statistics
        } else {
            alert('Error leaving waitlist: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while leaving the waitlist.');
    });
}
</script>
@endsection
