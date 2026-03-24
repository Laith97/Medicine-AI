@extends('layouts.doctor')

@section('title', 'Waitlist Dashboard')

@section('styles')
<style>
.stat-card {
    background: white;
    border-radius: 0.75rem;
    padding: 1.5rem;
    border: 1px solid #e5e7eb;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.stat-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
}

.stat-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(135deg, #3b82f6, #8b5cf6);
}

.stat-card.total-waitlists::before {
    background: linear-gradient(135deg, #3b82f6, #1d4ed8);
}

.stat-card.active-offers::before {
    background: linear-gradient(135deg, #f59e0b, #d97706);
}

.stat-card.avg-wait-time::before {
    background: linear-gradient(135deg, #10b981, #059669);
}

.stat-card.fulfillment-rate::before {
    background: linear-gradient(135deg, #8b5cf6, #7c3aed);
}

.stat-number {
    font-size: 2.5rem;
    font-weight: 700;
    color: #1f2937;
    margin-bottom: 0.5rem;
}

.stat-label {
    color: #6b7280;
    font-size: 0.875rem;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.stat-trend {
    position: absolute;
    top: 1rem;
    right: 1rem;
    padding: 0.25rem 0.5rem;
    border-radius: 0.375rem;
    font-size: 0.75rem;
    font-weight: 600;
}

.trend-up {
    background-color: #dcfce7;
    color: #166534;
}

.trend-down {
    background-color: #fee2e2;
    color: #991b1b;
}

.trend-stable {
    background-color: #f3f4f6;
    color: #374151;
}

.waitlist-item {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 0.75rem;
    padding: 1.5rem;
    margin-bottom: 1rem;
    transition: all 0.3s ease;
    position: relative;
}

.waitlist-item:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    transform: translateY(-2px);
}

.patient-avatar {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 700;
    margin-right: 1rem;
}

.priority-indicator {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    display: inline-block;
    margin-right: 0.5rem;
}

.priority-urgent { background-color: #dc2626; }
.priority-high { background-color: #ea580c; }
.priority-medium { background-color: #d97706; }
.priority-low { background-color: #65a30d; }

.status-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 0.375rem;
    font-size: 0.75rem;
    font-weight: 600;
}

.status-active { background-color: #dcfce7; color: #166534; }
.status-paused { background-color: #fef3c7; color: #92400e; }
.status-cancelled { background-color: #fee2e2; color: #991b1b; }

.offer-alert {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    color: white;
    border-radius: 0.5rem;
    padding: 1rem;
    margin-top: 1rem;
    text-align: center;
    animation: pulse 2s infinite;
}

.recent-entry {
    border-left: 4px solid #3b82f6;
    background-color: #f8fafc;
    padding: 1rem;
    margin-bottom: 0.75rem;
    border-radius: 0 0.5rem 0.5rem 0;
}

.entry-status-offered { border-left-color: #f59e0b; background-color: #fffbeb; }
.entry-status-accepted { border-left-color: #10b981; background-color: #f0fdf4; }
.entry-status-declined { border-left-color: #ef4444; background-color: #fef2f2; }

.activity-timeline {
    position: relative;
    padding-left: 2rem;
}

.activity-timeline::before {
    content: '';
    position: absolute;
    left: 0.5rem;
    top: 0;
    bottom: 0;
    width: 2px;
    background: linear-gradient(to bottom, #3b82f6, #e5e7eb);
}

.activity-item {
    position: relative;
    margin-bottom: 1.5rem;
}

.activity-item::before {
    content: '';
    position: absolute;
    left: -1.5rem;
    top: 0.5rem;
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #3b82f6;
}

.quick-action-card {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 0.5rem;
    padding: 1rem;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s ease;
}

.quick-action-card:hover {
    border-color: #3b82f6;
    background-color: #eff6ff;
    transform: translateY(-2px);
}

.action-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #3b82f6;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 0.5rem;
    font-size: 1.25rem;
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

.refresh-button {
    position: fixed;
    bottom: 2rem;
    right: 2rem;
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: linear-gradient(135deg, #3b82f6, #1d4ed8);
    color: white;
    border: none;
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
    cursor: pointer;
    transition: all 0.3s ease;
    z-index: 1000;
}

.refresh-button:hover {
    transform: scale(1.1);
    box-shadow: 0 6px 20px rgba(59, 130, 246, 0.4);
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.7; }
}
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
                    <p class="text-muted mb-0">Monitor and manage patient waitlists</p>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-outline-primary" onclick="exportWaitlistData()">
                        <i class="fas fa-download me-2"></i>Export
                    </button>
                    <a href="{{ route('doctor.waitlist.manage') }}" class="btn btn-primary">
                        <i class="fas fa-cog me-2"></i>Manage Waitlist
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="stat-card total-waitlists">
                <div class="stat-number">{{ $stats['total_active'] ?? 0 }}</div>
                <div class="stat-label">Total Active Waitlists</div>
                <div class="stat-trend trend-stable">Stable</div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="stat-card active-offers">
                <div class="stat-number">{{ $recentEntries->where('status', 'offered')->count() }}</div>
                <div class="stat-label">Active Offers</div>
                <div class="stat-trend trend-up">+12%</div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="stat-card avg-wait-time">
                <div class="stat-number">{{ $stats['average_wait_days'] ?? 0 }}</div>
                <div class="stat-label">Avg Wait Time (Days)</div>
                <div class="stat-trend trend-down">-8%</div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="stat-card fulfillment-rate">
                <div class="stat-number">{{ round((($stats['fulfilled_waitlists'] ?? 0) / max($stats['total_active'] + $stats['fulfilled_waitlists'], 1)) * 100) }}%</div>
                <div class="stat-label">Fulfillment Rate</div>
                <div class="stat-trend trend-up">+5%</div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Active Waitlists -->
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header bg-white border-0 py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Active Waitlists</h5>
                        <div class="d-flex gap-2">
                            <select class="form-select form-select-sm" style="width: auto;" id="priorityFilter">
                                <option value="">All Priorities</option>
                                <option value="urgent">Urgent</option>
                                <option value="high">High</option>
                                <option value="medium">Medium</option>
                                <option value="low">Low</option>
                            </select>
                            <button class="btn btn-outline-secondary btn-sm" onclick="refreshWaitlistData()">
                                <i class="fas fa-sync-alt"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    @if($activeWaitlists->isEmpty())
                        <div class="empty-state">
                            <i class="fas fa-users"></i>
                            <h4>No Active Waitlists</h4>
                            <p>Patients haven't joined your waitlist yet.</p>
                        </div>
                    @else
                        <div id="waitlistContainer">
                            @foreach($activeWaitlists as $waitlist)
                                <div class="waitlist-item" id="waitlist-{{ $waitlist->id }}">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div class="d-flex align-items-center">
                                            <div class="patient-avatar">
                                                {{ $waitlist->patient->name[0] }}
                                            </div>
                                            <div>
                                                <h6 class="mb-1">{{ $waitlist->patient->name }}</h6>
                                                <p class="text-muted mb-1">
                                                    <i class="fas fa-envelope me-1"></i>{{ $waitlist->patient->email }}
                                                </p>
                                                <div class="d-flex align-items-center">
                                                    <span class="priority-indicator priority-{{ $waitlist->priority_level }}"></span>
                                                    <span class="text-sm text-muted">{{ ucfirst($waitlist->priority_level) }} Priority</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="text-end">
                                            <div class="mb-2">
                                                <span class="badge bg-secondary">{{ ucfirst($waitlist->service_type) }}</span>
                                            </div>
                                            <div class="text-sm text-muted">
                                                Joined {{ $waitlist->created_at->diffForHumans() }}
                                            </div>
                                            <div class="mt-2">
                                                <a href="{{ route('doctor.waitlist.show-patient', $waitlist->id) }}" class="btn btn-outline-primary btn-sm">
                                                    View Details
                                                </a>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Active Offer Alert -->
                                    @if($waitlist->entries->where('status', 'offered')->count() > 0)
                                        <div class="offer-alert">
                                            <i class="fas fa-bell me-2"></i>
                                            {{ $waitlist->entries->where('status', 'offered')->count() }} active offer(s) -
                                            <a href="{{ route('doctor.waitlist.show-patient', $waitlist->id) }}" class="text-white fw-bold">
                                                Manage Offers
                                            </a>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>

                        <!-- Pagination -->
                        @if($activeWaitlists->hasPages())
                            <div class="d-flex justify-content-center mt-4">
                                {{ $activeWaitlists->links() }}
                            </div>
                        @endif
                    @endif
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Quick Actions -->
            <div class="card mb-4">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="mb-0">Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="row g-2">
                        <div class="col-6">
                            <div class="quick-action-card" onclick="openManualOfferModal()">
                                <div class="action-icon">
                                    <i class="fas fa-plus"></i>
                                </div>
                                <small>Add Patient</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="quick-action-card" onclick="bulkOperations()">
                                <div class="action-icon">
                                    <i class="fas fa-cogs"></i>
                                </div>
                                <small>Bulk Actions</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="quick-action-card" onclick="viewAnalytics()">
                                <div class="action-icon">
                                    <i class="fas fa-chart-bar"></i>
                                </div>
                                <small>Analytics</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="quick-action-card" onclick="exportWaitlistData()">
                                <div class="action-icon">
                                    <i class="fas fa-download"></i>
                                </div>
                                <small>Export Data</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Priority Distribution -->
            <div class="card mb-4">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="mb-0">Priority Distribution</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        @foreach(['urgent' => 'Urgent', 'high' => 'High', 'medium' => 'Medium', 'low' => 'Low'] as $priority => $label)
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div class="d-flex align-items-center">
                                    <span class="priority-indicator priority-{{ $priority }}"></span>
                                    <span>{{ $label }}</span>
                                </div>
                                <span class="badge bg-secondary">{{ $stats['by_priority'][$priority] ?? 0 }}</span>
                            </div>
                        @endforeach
                    </div>

                    <!-- Simple Bar Chart -->
                    <div class="priority-chart">
                        @php
                            $total = array_sum($stats['by_priority'] ?? []);
                            $maxCount = max($stats['by_priority'] ?? [1]);
                        @endphp
                        @foreach($stats['by_priority'] ?? [] as $priority => $count)
                            @if($count > 0)
                                <div class="mb-2">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <small class="text-muted">{{ ucfirst($priority) }}</small>
                                        <small class="text-muted">{{ $count }}</small>
                                    </div>
                                    <div class="progress" style="height: 6px;">
                                        <div class="progress-bar bg-{{ $priority === 'urgent' ? 'danger' : ($priority === 'high' ? 'warning' : ($priority === 'medium' ? 'info' : 'success')) }}"
                                             style="width: {{ ($count / $maxCount) * 100 }}%"></div>
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="card">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="mb-0">Recent Activity</h5>
                </div>
                <div class="card-body">
                    <div class="activity-timeline">
                        @if($recentEntries->isEmpty())
                            <p class="text-muted text-center">No recent activity</p>
                        @else
                            @foreach($recentEntries->take(5) as $entry)
                                <div class="recent-entry entry-status-{{ $entry->status }}">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h6 class="mb-1">{{ $entry->waitlist->patient->name }}</h6>
                                            <p class="mb-1 small">
                                                {{ \Carbon\Carbon::parse($entry->slot_date)->format('M j, Y') }}
                                                at {{ \Carbon\Carbon::parse($entry->slot_time)->format('g:i A') }}
                                            </p>
                                            <span class="badge bg-{{ $entry->status === 'offered' ? 'warning' : ($entry->status === 'accepted' ? 'success' : 'danger') }}">
                                                {{ ucfirst($entry->status) }}
                                            </span>
                                        </div>
                                        <small class="text-muted">{{ $entry->created_at->diffForHumans() }}</small>
                                    </div>
                                </div>
                            @endforeach
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Manual Offer Modal -->
<div class="modal fade" id="manualOfferModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Manually Offer Slot</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="manualOfferForm">
                    @csrf
                    <div class="mb-3">
                        <label for="patientSelect" class="form-label">Select Patient</label>
                        <select class="form-select" id="patientSelect" name="patient_id" required>
                            <option value="">Choose patient...</option>
                            @foreach($activeWaitlists as $waitlist)
                                <option value="{{ $waitlist->patient->id }}">{{ $waitlist->patient->name }}</option>
                            @endforeach
                        </select>
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
                <button type="button" class="btn btn-primary" onclick="sendManualOffer()">Send Offer</button>
            </div>
        </div>
    </div>
</div>

<!-- Refresh Button -->
<button class="refresh-button" onclick="refreshAllData()" title="Refresh Data">
    <i class="fas fa-sync-alt"></i>
</button>

@endsection

@section('scripts')
<script>
// Auto-refresh data every 60 seconds
setInterval(refreshAllData, 60000);

function refreshAllData() {
    refreshWaitlistData();
    updateStatistics();
}

function refreshWaitlistData() {
    const refreshBtn = document.querySelector('[onclick="refreshWaitlistData()"]');
    const originalHtml = refreshBtn.innerHTML;

    refreshBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    refreshBtn.disabled = true;

    const priorityFilter = document.getElementById('priorityFilter').value;
    const params = new URLSearchParams();
    if (priorityFilter) params.set('priority', priorityFilter);

    fetch(`/api/doctor/waitlist/dashboard?${params}`)
        .then(response => response.json())
        .then(data => {
            updateWaitlistDisplay(data.waitlists);
            refreshBtn.innerHTML = originalHtml;
            refreshBtn.disabled = false;
        })
        .catch(error => {
            // console.error('Error refreshing data:', error);
            refreshBtn.innerHTML = '<i class="fas fa-exclamation-triangle"></i>';
            setTimeout(() => {
                refreshBtn.innerHTML = originalHtml;
                refreshBtn.disabled = false;
            }, 3000);
        });
}

function updateWaitlistDisplay(waitlists) {
    const container = document.getElementById('waitlistContainer');

    if (waitlists.length === 0) {
        container.innerHTML = `
            <div class="empty-state">
                <i class="fas fa-users"></i>
                <h4>No Active Waitlists</h4>
                <p>Patients haven't joined your waitlist yet.</p>
            </div>
        `;
        return;
    }

    container.innerHTML = waitlists.map(waitlist => `
        <div class="waitlist-item" id="waitlist-${waitlist.id}">
            <div class="d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center">
                    <div class="patient-avatar">
                        ${waitlist.patient.name[0]}
                    </div>
                    <div>
                        <h6 class="mb-1">${waitlist.patient.name}</h6>
                        <p class="text-muted mb-1">
                            <i class="fas fa-envelope me-1"></i>${waitlist.patient.email}
                        </p>
                        <div class="d-flex align-items-center">
                            <span class="priority-indicator priority-${waitlist.priority_level}"></span>
                            <span class="text-sm text-muted">${waitlist.priority_level.charAt(0).toUpperCase() + waitlist.priority_level.slice(1)} Priority</span>
                        </div>
                    </div>
                </div>
                <div class="text-end">
                    <div class="mb-2">
                        <span class="badge bg-secondary">${waitlist.service_type.replace('-', ' ').replace(/\b\w/g, l => l.toUpperCase())}</span>
                    </div>
                    <div class="text-sm text-muted">
                        Joined ${new Date(waitlist.created_at).toLocaleDateString()}
                    </div>
                    <div class="mt-2">
                        <a href="/doctor/waitlist/show-patient/${waitlist.id}" class="btn btn-outline-primary btn-sm">
                            View Details
                        </a>
                    </div>
                </div>
            </div>
        </div>
    `).join('');
}

function updateStatistics() {
    fetch('/api/doctor/waitlist/stats')
        .then(response => response.json())
        .then(data => {
            // Update stat cards with new data
            document.querySelector('.total-waitlists .stat-number').textContent = data.stats.total_active || 0;
            document.querySelector('.active-offers .stat-number').textContent = data.stats.active_offers || 0;
            document.querySelector('.avg-wait-time .stat-number').textContent = data.stats.average_wait_days || 0;
            // Update fulfillment rate calculation
        })
        .catch(error => // console.error('Error updating statistics:', error));
}

function openManualOfferModal() {
    new bootstrap.Modal(document.getElementById('manualOfferModal')).show();
}

function sendManualOffer() {
    const form = document.getElementById('manualOfferForm');
    const formData = new FormData(form);

    fetch('/api/doctor/waitlist/manual-offer', {
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
            alert('Error sending offer: ' + data.message);
        }
    });
}

function bulkOperations() {
    // Implementation for bulk operations
    alert('Bulk operations feature coming soon!');
}

function viewAnalytics() {
    window.location.href = '/doctor/waitlist/analytics';
}

function exportWaitlistData() {
    window.open('/api/doctor/waitlist/export', '_blank');
}

// Filter functionality
document.getElementById('priorityFilter').addEventListener('change', refreshWaitlistData);
</script>
@endsection
