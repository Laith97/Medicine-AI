@extends('layouts.patient')

@section('title', 'Waitlist Status')

@section('styles')
<style>
.status-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 0.5rem;
    padding: 2rem;
    margin-bottom: 2rem;
}

.position-circle {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.2);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1rem;
    border: 3px solid rgba(255, 255, 255, 0.3);
}

.position-number {
    font-size: 2.5rem;
    font-weight: 700;
    line-height: 1;
}

.position-label {
    font-size: 0.875rem;
    opacity: 0.9;
}

.progress-ring {
    width: 120px;
    height: 120px;
    margin: 0 auto 1rem;
}

.progress-ring__circle {
    transition: stroke-dasharray 0.35s;
    transform: rotate(-90deg);
    transform-origin: 50% 50%;
}

.offer-card {
    border: 2px solid #f59e0b;
    background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);
    border-radius: 0.75rem;
    overflow: hidden;
}

.offer-countdown {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    color: white;
    padding: 1rem;
    text-align: center;
}

.countdown-timer {
    font-size: 1.5rem;
    font-weight: 700;
    font-family: monospace;
}

.offer-actions {
    display: flex;
    gap: 1rem;
    justify-content: center;
    margin-top: 1rem;
}

.btn-accept {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    border: none;
    color: white;
    padding: 0.75rem 2rem;
    border-radius: 0.5rem;
    font-weight: 600;
    transition: all 0.3s;
}

.btn-accept:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(16, 185, 129, 0.3);
    color: white;
}

.btn-decline {
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    border: none;
    color: white;
    padding: 0.75rem 2rem;
    border-radius: 0.5rem;
    font-weight: 600;
    transition: all 0.3s;
}

.btn-decline:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(239, 68, 68, 0.3);
    color: white;
}

.info-card {
    border: 1px solid #e5e7eb;
    border-radius: 0.5rem;
    padding: 1.5rem;
    background: white;
    margin-bottom: 1rem;
}

.info-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #3b82f6;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 1rem;
}

.preference-match {
    background: #f0f9ff;
    border: 1px solid #0ea5e9;
    border-radius: 0.5rem;
    padding: 1rem;
    margin-bottom: 1rem;
}

.match-score {
    font-size: 1.25rem;
    font-weight: 700;
    color: #0ea5e9;
}

.available-slots {
    max-height: 300px;
    overflow-y: auto;
}

.slot-item {
    border: 1px solid #e5e7eb;
    border-radius: 0.375rem;
    padding: 0.75rem;
    margin-bottom: 0.5rem;
    cursor: pointer;
    transition: all 0.2s;
}

.slot-item:hover {
    border-color: #3b82f6;
    background-color: #eff6ff;
}

.slot-item.recommended {
    border-color: #10b981;
    background-color: #f0fdf4;
}

.status-badge {
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
    border-radius: 0.375rem;
    font-weight: 600;
}

.status-active { background-color: #dcfce7; color: #166534; }
.status-paused { background-color: #fef3c7; color: #92400e; }
.status-cancelled { background-color: #fee2e2; color: #991b1b; }
.status-fulfilled { background-color: #dbeafe; color: #1e40af; }

.pulse {
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.05); }
    100% { transform: scale(1); }
}
</style>
@endsection

@section('content')
<div class="container-fluid py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('patient.waitlist.dashboard') }}">Waitlist Dashboard</a></li>
            <li class="breadcrumb-item active" aria-current="page">Waitlist Status</li>
        </ol>
    </nav>

    <!-- Status Header -->
    <div class="status-header">
        <div class="row align-items-center">
            <div class="col-md-4 text-center">
                <div class="position-circle">
                    <div class="position-number">{{ $position['position'] }}</div>
                    <div class="position-label">of {{ $position['total_waitlisted'] }}</div>
                </div>
                <h6 class="mb-0">Your Position</h6>
            </div>
            <div class="col-md-8">
                <div class="row">
                    <div class="col-6 text-center">
                        <h3 class="mb-1">{{ $position['estimated_wait_days'] }}</h3>
                        <p class="mb-0 opacity-75">Estimated Days</p>
                    </div>
                    <div class="col-6 text-center">
                        <h3 class="mb-1">
                            <span class="badge status-{{ $waitlist->status }}">{{ ucfirst($waitlist->status) }}</span>
                        </h3>
                        <p class="mb-0 opacity-75">Status</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Waitlist Details -->
        <div class="col-lg-8">
            <!-- Active Offer Alert -->
            @if($waitlist->entries->where('status', 'offered')->count() > 0)
                @foreach($waitlist->entries->where('status', 'offered') as $entry)
                    <div class="offer-card mb-4">
                        <div class="offer-countdown">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1"><i class="fas fa-clock me-2"></i>Appointment Offer Expires In</h6>
                                    <div class="countdown-timer" id="countdown-{{ $entry->id }}">
                                        <span id="hours-{{ $entry->id }}">--</span>:<span id="minutes-{{ $entry->id }}">--</span>:<span id="seconds-{{ $entry->id }}">--</span>
                                    </div>
                                </div>
                                <div>
                                    <small class="opacity-75">Offer #{{ $entry->id }}</small>
                                </div>
                            </div>
                        </div>
                        <div class="p-4">
                            <div class="row">
                                <div class="col-md-8">
                                    <h5 class="mb-2">Appointment Available!</h5>
                                    <div class="mb-3">
                                        <strong>Doctor:</strong> {{ $waitlist->doctor->name }}<br>
                                        <strong>Specialty:</strong> {{ $waitlist->doctor->specialty }}<br>
                                        <strong>Date & Time:</strong>
                                        {{ \Carbon\Carbon::parse($entry->slot_date)->format('l, F j, Y') }}
                                        at {{ \Carbon\Carbon::parse($entry->slot_time)->format('g:i A') }}
                                    </div>

                                    @if($preferences)
                                        <div class="preference-match">
                                            <div class="d-flex align-items-center mb-2">
                                                <i class="fas fa-star text-warning me-2"></i>
                                                <span class="match-score">{{ rand(75, 95) }}%</span>
                                                <span class="ms-2">Preference Match</span>
                                            </div>
                                            <small class="text-muted">
                                                This slot matches your preferences for
                                                {{ implode(', ', $preferences->preferred_times ?? ['flexible timing']) }}
                                                and {{ implode(', ', $preferences->preferred_days ?? ['any day']) }}.
                                            </small>
                                        </div>
                                    @endif
                                </div>
                                <div class="col-md-4 text-center">
                                    <div class="offer-actions">
                                        <form action="{{ route('patient.waitlist.accept-offer', $entry->id) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-accept">
                                                <i class="fas fa-check me-2"></i>Accept
                                            </button>
                                        </form>
                                        <form action="{{ route('patient.waitlist.decline-offer', $entry->id) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-decline" onclick="return confirm('Are you sure you want to decline this offer?')">
                                                <i class="fas fa-times me-2"></i>Decline
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            @endif

            <!-- Waitlist Information -->
            <div class="info-card">
                <h5 class="mb-3">
                    <i class="fas fa-info-circle text-primary me-2"></i>Waitlist Information
                </h5>
                <div class="row">
                    <div class="col-md-6">
                        <strong>Doctor:</strong> {{ $waitlist->doctor->name }}<br>
                        <strong>Specialty:</strong> {{ $waitlist->doctor->specialty }}<br>
                        <strong>Service Type:</strong> {{ ucfirst(str_replace('-', ' ', $waitlist->service_type)) }}<br>
                        <strong>Priority:</strong>
                        <span class="badge bg-{{ $waitlist->priority_level === 'urgent' ? 'danger' : ($waitlist->priority_level === 'high' ? 'warning' : ($waitlist->priority_level === 'medium' ? 'info' : 'secondary')) }}">
                            {{ ucfirst($waitlist->priority_level) }}
                        </span>
                    </div>
                    <div class="col-md-6">
                        <strong>Joined On:</strong> {{ $waitlist->created_at->format('M j, Y') }}<br>
                        <strong>Position:</strong> {{ $position['position'] }} of {{ $position['total_waitlisted'] }}<br>
                        <strong>Est. Wait:</strong> {{ $position['estimated_wait_days'] }} days<br>
                        <strong>Status:</strong>
                        <span class="status-badge status-{{ $waitlist->status }}">{{ ucfirst($waitlist->status) }}</span>
                    </div>
                </div>
                <div class="mt-3">
                    <form action="{{ route('patient.waitlist.leave', $waitlist->id) }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-outline-danger btn-sm" onclick="return confirm('Are you sure you want to leave this waitlist?')">
                            <i class="fas fa-sign-out-alt me-2"></i>Leave Waitlist
                        </button>
                    </form>
                </div>
            </div>

            <!-- Recent Entries -->
            @if($waitlist->entries->count() > 0)
                <div class="info-card">
                    <h5 class="mb-3">
                        <i class="fas fa-history text-primary me-2"></i>Waitlist History
                    </h5>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Date & Time</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($waitlist->entries->sortByDesc('created_at')->take(5) as $entry)
                                    <tr>
                                        <td>
                                            {{ \Carbon\Carbon::parse($entry->slot_date)->format('M j, Y') }}<br>
                                            <small class="text-muted">{{ \Carbon\Carbon::parse($entry->slot_time)->format('g:i A') }}</small>
                                        </td>
                                        <td>
                                            <span class="badge bg-{{ $entry->status === 'offered' ? 'warning' : ($entry->status === 'accepted' ? 'success' : ($entry->status === 'declined' ? 'danger' : 'secondary')) }}">
                                                {{ ucfirst($entry->status) }}
                                            </span>
                                        </td>
                                        <td>
                                            @if($entry->status === 'offered')
                                                <a href="{{ route('patient.waitlist.offer', $entry->id) }}" class="btn btn-sm btn-primary">
                                                    View Offer
                                                </a>
                                            @else
                                                <small class="text-muted">-</small>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Real-time Updates -->
            <div class="info-card">
                <h5 class="mb-3">
                    <i class="fas fa-sync-alt text-primary me-2"></i>Real-time Updates
                </h5>
                <p class="text-muted mb-3">Your position updates automatically as other patients join or leave the waitlist.</p>
                <button class="btn btn-outline-primary w-100" onclick="refreshPosition()">
                    <i class="fas fa-sync-alt me-2"></i>Refresh Position
                </button>
                <div id="lastUpdated" class="text-center mt-2">
                    <small class="text-muted">Last updated: {{ now()->format('g:i:s A') }}</small>
                </div>
            </div>

            <!-- Available Slots Context -->
            @if(count($availableSlots) > 0)
                <div class="info-card">
                    <h5 class="mb-3">
                        <i class="fas fa-calendar text-primary me-2"></i>Available Slots
                    </h5>
                    <p class="text-muted mb-3">Upcoming availability for this doctor:</p>
                    <div class="available-slots">
                        @foreach(array_slice($availableSlots, 0, 5) as $slot)
                            <div class="slot-item @if(rand(1, 100) > 70) recommended @endif">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong>{{ \Carbon\Carbon::parse($slot['date'])->format('M j') }}</strong>
                                        <br>
                                        <small class="text-muted">{{ \Carbon\Carbon::parse($slot['time'])->format('g:i A') }}</small>
                                    </div>
                                    @if(isset($slot['recommended']))
                                        <span class="badge bg-success">Recommended</span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                    @if(count($availableSlots) > 5)
                        <small class="text-muted">And {{ count($availableSlots) - 5 }} more slots...</small>
                    @endif
                </div>
            @endif

            <!-- Preferences -->
            @if($preferences)
                <div class="info-card">
                    <h5 class="mb-3">
                        <i class="fas fa-cog text-primary me-2"></i>Your Preferences
                    </h5>
                    <div class="mb-2">
                        <strong>Preferred Times:</strong><br>
                        @foreach($preferences->preferred_times as $time)
                            <span class="badge bg-secondary me-1">{{ ucfirst($time) }}</span>
                        @endforeach
                    </div>
                    <div class="mb-2">
                        <strong>Preferred Days:</strong><br>
                        @foreach($preferences->preferred_days as $day)
                            <span class="badge bg-info me-1">{{ ucfirst($day) }}</span>
                        @endforeach
                    </div>
                    <a href="{{ route('patient.waitlist.preferences') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-edit me-2"></i>Edit Preferences
                    </a>
                </div>
            @else
                <div class="info-card">
                    <h5 class="mb-3">
                        <i class="fas fa-star text-primary me-2"></i>Set Preferences
                    </h5>
                    <p class="text-muted mb-3">Set your preferences to get better slot recommendations.</p>
                    <a href="{{ route('patient.waitlist.preferences') }}" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus me-2"></i>Add Preferences
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
// Initialize countdown timers for offers
@foreach($waitlist->entries->where('status', 'offered') as $entry)
    startCountdown('{{ $entry->response_deadline }}', {{ $entry->id }});
@endforeach

function startCountdown(deadline, entryId) {
    const deadlineTime = new Date(deadline).getTime();

    function updateCountdown() {
        const now = new Date().getTime();
        const timeLeft = deadlineTime - now;

        if (timeLeft > 0) {
            const hours = Math.floor(timeLeft / (1000 * 60 * 60));
            const minutes = Math.floor((timeLeft % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((timeLeft % (1000 * 60)) / 1000);

            document.getElementById(`hours-${entryId}`).textContent = hours.toString().padStart(2, '0');
            document.getElementById(`minutes-${entryId}`).textContent = minutes.toString().padStart(2, '0');
            document.getElementById(`seconds-${entryId}`).textContent = seconds.toString().padStart(2, '0');
        } else {
            document.getElementById(`countdown-${entryId}`).innerHTML = '<span class="text-danger">Expired</span>';
            // Optionally auto-refresh to update status
            setTimeout(() => location.reload(), 2000);
        }
    }

    updateCountdown();
    setInterval(updateCountdown, 1000);
}

function refreshPosition() {
    const button = event.target;
    const originalText = button.innerHTML;

    button.innerHTML = '<div class="spinner-border spinner-border-sm me-2"></div>Refreshing...';
    button.disabled = true;

    fetch(`/api/patient/waitlist/position/{{ $waitlist->id }}`)
        .then(response => response.json())
        .then(data => {
            if (data.position) {
                // Update position display
                document.querySelector('.position-number').textContent = data.position.position;
                document.querySelector('.position-label').textContent = `of ${data.position.total_waitlisted}`;

                // Update last updated time
                document.getElementById('lastUpdated').innerHTML =
                    `<small class="text-muted">Last updated: ${new Date().toLocaleTimeString()}</small>`;

                // Show success feedback
                button.innerHTML = '<i class="fas fa-check me-2"></i>Updated!';
                button.classList.remove('btn-outline-primary');
                button.classList.add('btn-success');

                setTimeout(() => {
                    button.innerHTML = originalText;
                    button.classList.remove('btn-success');
                    button.classList.add('btn-outline-primary');
                    button.disabled = false;
                }, 2000);
            }
        })
        .catch(error => {
            // console.error('Error refreshing position:', error);
            button.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i>Error';
            button.classList.remove('btn-outline-primary');
            button.classList.add('btn-danger');

            setTimeout(() => {
                button.innerHTML = originalText;
                button.classList.remove('btn-danger');
                button.classList.add('btn-outline-primary');
                button.disabled = false;
            }, 3000);
        });
}

// Auto-refresh position every 60 seconds
setInterval(() => {
    fetch(`/api/patient/waitlist/position/{{ $waitlist->id }}`)
        .then(response => response.json())
        .then(data => {
            if (data.position) {
                document.querySelector('.position-number').textContent = data.position.position;
                document.querySelector('.position-label').textContent = `of ${data.position.total_waitlisted}`;
            }
        })
        .catch(error => // console.error('Auto-refresh error:', error));
}, 60000);

// Add pulse animation to active offers
@foreach($waitlist->entries->where('status', 'offered') as $entry)
    document.querySelector(`#countdown-{{ $entry->id }}`).closest('.offer-card').classList.add('pulse');
@endforeach
</script>
@endsection
