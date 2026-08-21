@extends('master')

@section('content')
<style>
.app-main {
    background-color: #f8f9fa;
}
.dashboard-header {
    background: linear-gradient(135deg, #2c5aa0 0%, #1e3a8a 100%);
    border-radius: 12px;
    padding: 2.5rem;
    margin-bottom: 2rem;
}
.dashboard-header h2 {
    color: #ffffff;
    font-weight: 600;
    margin-bottom: 0.25rem;
}
.dashboard-header p {
    color: rgba(255, 255, 255, 0.85);
    margin-bottom: 0;
}
.dashboard-header .btn {
    background: rgba(255, 255, 255, 0.2);
    border: 1px solid rgba(255, 255, 255, 0.3);
    color: white;
    font-weight: 500;
    padding: 0.5rem 1.25rem;
    border-radius: 8px;
    transition: all 0.2s ease;
}
.dashboard-header .btn:hover {
    background: rgba(255, 255, 255, 0.3);
    color: white;
}
.stat-card {
    background: #ffffff;
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
    border: 1px solid rgba(0, 0, 0, 0.04);
    height: 100%;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
}
.stat-icon {
    width: 48px;
    height: 48px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    margin-bottom: 1rem;
}
.stat-value {
    font-size: 2rem;
    font-weight: 700;
    color: #1e3a8a;
    line-height: 1;
    margin-bottom: 0.25rem;
}
.stat-label {
    font-size: 0.875rem;
    color: #6b7280;
    font-weight: 500;
}
.stat-trend {
    font-size: 0.75rem;
    margin-top: 0.5rem;
    font-weight: 500;
}
.stat-trend.up { color: #10b981; }
.stat-trend.down { color: #ef4444; }
.section-card {
    background: #ffffff;
    border-radius: 12px;
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
    border: 1px solid rgba(0, 0, 0, 0.04);
    overflow: hidden;
}
.section-header {
    padding: 1.25rem 1.5rem;
    border-bottom: 1px solid #f0f0f0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.section-header h5 {
    margin: 0;
    font-weight: 600;
    color: #1e3a8a;
}
.section-body {
    padding: 1.25rem 1.5rem;
}
.appointment-item {
    display: flex;
    align-items: center;
    padding: 0.875rem 0;
    border-bottom: 1px solid #f5f5f5;
}
.appointment-item:last-child {
    border-bottom: none;
}
.appointment-time {
    min-width: 70px;
    font-weight: 600;
    color: #1e3a8a;
    font-size: 0.9rem;
}
.appointment-info {
    flex-grow: 1;
}
.appointment-name {
    font-weight: 500;
    color: #374151;
    margin-bottom: 0.125rem;
}
.appointment-type {
    font-size: 0.8rem;
    color: #9ca3af;
}
.status-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 500;
}
.status-badge.pending {
    background: #fef3c7;
    color: #d97706;
}
.status-badge.confirmed {
    background: #dbeafe;
    color: #2563eb;
}
.status-badge.completed {
    background: #d1fae5;
    color: #059669;
}
.status-badge.cancelled {
    background: #fee2e2;
    color: #dc2626;
}
.status-badge.no-show {
    background: #f3f4f6;
    color: #6b7280;
}
.status-badge.scheduled {
    background: #e0e7ff;
    color: #4f46e5;
}
.review-item {
    padding: 1rem 0;
    border-bottom: 1px solid #f5f5f5;
}
.review-item:last-child {
    border-bottom: none;
}
.review-stars {
    color: #fbbf24;
    margin-bottom: 0.25rem;
}
.review-text {
    color: #6b7280;
    font-size: 0.875rem;
    margin-bottom: 0.25rem;
}
.review-author {
    font-size: 0.8rem;
    color: #9ca3af;
}
.quick-action-btn {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 1.25rem;
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    text-decoration: none;
    color: #374151;
    transition: all 0.2s ease;
    text-align: center;
}
.quick-action-btn:hover {
    border-color: #2c5aa0;
    color: #2c5aa0;
    box-shadow: 0 4px 12px rgba(44, 90, 160, 0.15);
}
.quick-action-btn i {
    font-size: 1.5rem;
    margin-bottom: 0.5rem;
    color: #2c5aa0;
}
.quick-action-btn span {
    font-size: 0.8rem;
    font-weight: 500;
}
.chart-container {
    position: relative;
    height: 200px;
}
.empty-state {
    text-align: center;
    padding: 3rem 1.5rem;
    color: #9ca3af;
}
.empty-state i {
    font-size: 3rem;
    margin-bottom: 1rem;
    opacity: 0.5;
}
.empty-state h6 {
    color: #6b7280;
    margin-bottom: 0.5rem;
}
.alert-banner {
    border-radius: 12px;
    padding: 1rem 1.5rem;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 1rem;
}
.alert-banner.warning {
    background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
    border: 1px solid #f59e0b;
}
.alert-banner.danger {
    background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
    border: 1px solid #dc2626;
}
.alert-banner.info {
    background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
    border: 1px solid #2563eb;
}
.alert-banner i {
    font-size: 1.5rem;
}
.alert-banner .content {
    flex-grow: 1;
}
.alert-banner .content h6 {
    margin: 0 0 0.25rem 0;
    font-weight: 600;
}
.alert-banner .content p {
    margin: 0;
    font-size: 0.875rem;
}
</style>

<div class="container-fluid" style="background-color: #f8f9fa;">
    <div class="container">
        <div class="dashboard-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2><i class="fas fa-tachometer-alt me-2"></i>Dashboard</h2>
                    <p>Welcome back, {{ auth()->user()->name }}</p>
                </div>
                <a href="{{ route('ai.ambient-listening.index') }}" class="btn">
                    <i class="fas fa-microphone me-2"></i>New Consultation
                </a>
            </div>
        </div>
    </div>

    <div class="container">
        {{-- Doctor Dashboard Metrics --}}
        @if(auth()->user()->isDoctor() && isset($doctorMetrics))
            {{-- Main Stats Row --}}
            <div class="row mb-4">
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="stat-card">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%); color: #2563eb;">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <div class="stat-value">{{ $doctorMetrics['today_appointments'] }}</div>
                        <div class="stat-label">Today's Appointments</div>
                        <div class="stat-trend">
                            <i class="fas fa-calendar-week me-1"></i>{{ $doctorMetrics['week_appointments'] }} this week
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="stat-card">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); color: #d97706;">
                            <i class="fas fa-hourglass-half"></i>
                        </div>
                        <div class="stat-value">{{ $doctorMetrics['pending_count'] }}</div>
                        <div class="stat-label">Pending Approval</div>
                        @if($doctorMetrics['completed_without_diagnosis'] > 0)
                            <div class="stat-trend down">
                                <i class="fas fa-exclamation-triangle me-1"></i>{{ $doctorMetrics['completed_without_diagnosis'] }} need diagnosis
                            </div>
                        @endif
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="stat-card">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%); color: #059669;">
                            <i class="fas fa-user-md"></i>
                        </div>
                        <div class="stat-value">{{ $doctorMetrics['total_patients'] }}</div>
                        <div class="stat-label">Total Patients</div>
                        <div class="stat-trend up">
                            <i class="fas fa-user-plus me-1"></i>{{ $doctorMetrics['new_patients_this_month'] }} new this month
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="stat-card">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #f3e8ff 0%, #e9d5ff 100%); color: #9333ea;">
                            <i class="fas fa-dollar-sign"></i>
                        </div>
                        <div class="stat-value">${{ number_format($doctorMetrics['month_revenue'], 0) }}</div>
                        <div class="stat-label">Revenue This Month</div>
                        <div class="stat-trend">
                            <i class="fas fa-calendar-alt me-1"></i>This month
                        </div>
                    </div>
                </div>
            </div>

            {{-- Second Row --}}
            <div class="row mb-4">
                <div class="col-lg-4 col-md-6 mb-3">
                    <div class="stat-card">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); color: #d97706;">
                            <i class="fas fa-star"></i>
                        </div>
                        <div class="stat-value">{{ $doctorMetrics['total_reviews'] > 0 ? $doctorMetrics['avg_rating'] . '/5' : 'N/A' }}</div>
                        <div class="stat-label">Average Rating</div>
                        <div class="stat-trend">
                            @if($doctorMetrics['total_reviews'] > 0)
                                <i class="fas fa-comment me-1"></i>{{ $doctorMetrics['total_reviews'] }} reviews
                            @else
                                <i class="fas fa-star-half-alt me-1"></i>No reviews yet
                            @endif
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 mb-3">
                    <div class="stat-card">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%); color: #2563eb;">
                            <i class="fas fa-clipboard-list"></i>
                        </div>
                        <div class="stat-value">{{ $records->count() }}</div>
                        <div class="stat-label">Total Cases</div>
                        <div class="stat-trend">
                            <i class="fas fa-file-medical me-1"></i>All time
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 mb-3">
                    <div class="stat-card">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #fce7f3 0%, #fbcfe8 100%); color: #db2777;">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <div class="stat-value">{{ $doctorMetrics['month_completed'] }}</div>
                        <div class="stat-label">Completed This Month</div>
                        <div class="stat-trend">
                            <i class="fas fa-calendar me-1"></i>{{ now()->format('F Y') }}
                        </div>
                    </div>
                </div>
            </div>

            {{-- Main Content Row --}}
            <div class="row mb-4">
                {{-- Today's Schedule --}}
                <div class="col-lg-8 mb-4">
                    <div class="section-card">
                        <div class="section-header">
                            <h5><i class="fas fa-calendar-alt me-2 text-primary"></i>Today's Schedule</h5>
                            <a href="{{ route('doctor.appointments.index') }}" class="btn btn-sm btn-outline-primary">View All</a>
                        </div>
                        <div class="section-body">
                            @if($doctorMetrics['today_appointments_list']->count() > 0)
                                @foreach($doctorMetrics['today_appointments_list'] as $appointment)
                                    <div class="appointment-item">
                                        <div class="appointment-time">
                                            {{ \Carbon\Carbon::parse($appointment->appointment_date)->format('h:i A') }}
                                        </div>
                                        <div class="appointment-info">
                                            <div class="appointment-name">{{ $appointment->patient?->name ?? 'Guest Patient' }}</div>
                                            <div class="appointment-type">
                                                {{ $appointment->appointment_type ? Str::headline($appointment->appointment_type) : 'Consultation' }}
                                                @if($appointment->reason) - {{ Str::limit($appointment->reason, 30) }}@endif
                                            </div>
                                        </div>
                                        <span class="status-badge {{ $appointment->status }}">
                                            {{ Str::headline($appointment->status) }}
                                        </span>
                                    </div>
                                @endforeach
                            @else
                                <div class="empty-state">
                                    <i class="fas fa-calendar-times"></i>
                                    <h6>No Appointments Today</h6>
                                    <p>You don't have any scheduled appointments for today.</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Upcoming This Week --}}
                <div class="col-lg-4 mb-4">
                    <div class="section-card">
                        <div class="section-header">
                            <h5><i class="fas fa-calendar-week me-2 text-primary"></i>Upcoming (7 Days)</h5>
                        </div>
                        <div class="section-body">
                            @if($doctorMetrics['upcoming_appointments']->count() > 0)
                                @foreach($doctorMetrics['upcoming_appointments'] as $appointment)
                                    <div class="appointment-item">
                                        <div class="appointment-time">
                                            {{ \Carbon\Carbon::parse($appointment->appointment_date)->format('D, M d') }}
                                        </div>
                                        <div class="appointment-info">
                                            <div class="appointment-name">{{ $appointment->patient?->name ?? 'Guest' }}</div>
                                            <div class="appointment-type">
                                                {{ \Carbon\Carbon::parse($appointment->appointment_date)->format('h:i A') }}
                                                @if($appointment->appointment_type) · {{ Str::headline($appointment->appointment_type) }}@endif
                                            </div>
                                        </div>
                                        @if($appointment->status)
                                            <span class="status-badge {{ $appointment->status }}">{{ Str::headline($appointment->status) }}</span>
                                        @endif
                                    </div>
                                @endforeach
                            @else
                                <div class="empty-state">
                                    <i class="fas fa-calendar"></i>
                                    <h6>No Upcoming</h6>
                                    <p>No appointments scheduled for the next 7 days.</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Third Row --}}
            <div class="row mb-4">
                {{-- Recent Diagnoses --}}
                <div class="col-lg-6 mb-4">
                    <div class="section-card">
                        <div class="section-header">
                            <h5><i class="fas fa-file-medical me-2 text-primary"></i>Recent Diagnoses</h5>
                            <a href="{{ route('doctor.cases.overview') }}" class="btn btn-sm btn-outline-primary">View All</a>
                        </div>
                        <div class="section-body">
                            @if($doctorMetrics['recent_diagnoses']->count() > 0)
                                @foreach($doctorMetrics['recent_diagnoses'] as $diagnosis)
                                    <div class="appointment-item">
                                        <div class="appointment-info">
                                            <div class="appointment-name">{{ $diagnosis->patient->name ?? 'Unknown Patient' }}</div>
                                            <div class="appointment-type">
                                                {{ Str::limit($diagnosis->diagnosis_text ?? 'No diagnosis text', 50) }}
                                            </div>
                                        </div>
                                        <small class="text-muted">{{ $diagnosis->created_at->diffForHumans() }}</small>
                                    </div>
                                @endforeach
                            @else
                                <div class="empty-state">
                                    <i class="fas fa-folder-open"></i>
                                    <h6>No Diagnoses Yet</h6>
                                    <p>Start a consultation to create your first diagnosis.</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Recent Reviews --}}
                <div class="col-lg-6 mb-4">
                    <div class="section-card">
                        <div class="section-header">
                            <h5><i class="fas fa-star me-2 text-primary"></i>Recent Reviews</h5>
                            <a href="{{ route('doctor.reviews.index') }}" class="btn btn-sm btn-outline-primary">View All</a>
                        </div>
                        <div class="section-body">
                            @if($doctorMetrics['recent_reviews']->count() > 0)
                                @foreach($doctorMetrics['recent_reviews'] as $review)
                                    <div class="review-item">
                                        <div class="review-stars">
                                            @for($i = 1; $i <= 5; $i++)
                                                @if($i <= $review->rating)
                                                    <i class="fas fa-star"></i>
                                                @else
                                                    <i class="far fa-star"></i>
                                                @endif
                                            @endfor
                                        </div>
                                        @if($review->comment)
                                            <div class="review-text">{{ Str::limit($review->comment, 80) }}</div>
                                        @endif
                                        <div class="review-author">
                                            {{ $review->patient->name ?? 'Anonymous' }}
                                            - {{ $review->created_at->diffForHumans() }}
                                        </div>
                                    </div>
                                @endforeach
                            @else
                                <div class="empty-state">
                                    <i class="fas fa-star-half-alt"></i>
                                    <h6>No Reviews Yet</h6>
                                    <p>Patient reviews will appear here after consultations.</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Quick Actions --}}
            <div class="row mb-4">
                <div class="col-12">
                    <div class="section-card">
                        <div class="section-header">
                            <h5><i class="fas fa-bolt me-2 text-primary"></i>Quick Actions</h5>
                        </div>
                        <div class="section-body">
                            <div class="row g-3">
                                <div class="col-lg-2 col-md-3 col-4">
                                    <a href="{{ route('ai.ambient-listening.index') }}" class="quick-action-btn">
                                        <i class="fas fa-microphone"></i>
                                        <span>New Consultation</span>
                                    </a>
                                </div>
                                <div class="col-lg-2 col-md-3 col-4">
                                    <a href="{{ route('doctor.appointments.create') }}" class="quick-action-btn">
                                        <i class="fas fa-calendar-plus"></i>
                                        <span>Book Appointment</span>
                                    </a>
                                </div>
                                <div class="col-lg-2 col-md-3 col-4">
                                    <a href="{{ route('doctor.patients.index') }}" class="quick-action-btn">
                                        <i class="fas fa-users"></i>
                                        <span>Patients</span>
                                    </a>
                                </div>
                                <div class="col-lg-2 col-md-3 col-4">
                                    <a href="{{ route('doctor.cases.overview') }}" class="quick-action-btn">
                                        <i class="fas fa-folder-open"></i>
                                        <span>Cases</span>
                                    </a>
                                </div>
                                <div class="col-lg-2 col-md-3 col-4">
                                    <a href="{{ route('doctor.availability.index') }}" class="quick-action-btn">
                                        <i class="fas fa-clock"></i>
                                        <span>Availability</span>
                                    </a>
                                </div>
                                <div class="col-lg-2 col-md-3 col-4">
                                    <a href="{{ route('doctor.analytics.index') }}" class="quick-action-btn">
                                        <i class="fas fa-chart-line"></i>
                                        <span>Analytics</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Charts Section --}}
            <div class="row mb-4">
                <div class="col-lg-8 mb-4">
                    <div class="section-card">
                        <div class="section-header">
                            <h5><i class="fas fa-chart-line me-2 text-primary"></i>Appointments Trend (14 Days)</h5>
                        </div>
                        <div class="section-body">
                            <div class="chart-container" style="height: 250px;">
                                <canvas id="appointmentsTrendChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 mb-4">
                    <div class="section-card">
                        <div class="section-header">
                            <h5><i class="fas fa-pie-chart me-2 text-primary"></i>Appointment Status</h5>
                        </div>
                        <div class="section-body">
                            <div class="chart-container" style="height: 250px;">
                                <canvas id="statusBreakdownChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-lg-6 mb-4">
                    <div class="section-card">
                        <div class="section-header">
                            <h5><i class="fas fa-dollar-sign me-2 text-primary"></i>Revenue (6 Months)</h5>
                        </div>
                        <div class="section-body">
                            <div class="chart-container" style="height: 250px;">
                                <canvas id="revenueChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 mb-4">
                    <div class="section-card">
                        <div class="section-header">
                            <h5><i class="fas fa-clipboard-list me-2 text-primary"></i>Diagnoses Trend (14 Days)</h5>
                        </div>
                        <div class="section-body">
                            <div class="chart-container" style="height: 250px;">
                                <canvas id="diagnosisTrendChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        @else
            {{-- Non-Doctor Dashboard (fallback) --}}
            <div class="row mb-4">
                <div class="col-lg-4 col-md-6 mb-3">
                    <div class="stat-card">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%); color: #2563eb;">
                            <i class="fas fa-folder-open"></i>
                        </div>
                        <div class="stat-value">{{ $records->count() }}</div>
                        <div class="stat-label">Total Cases</div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-dismiss alerts after 5 seconds
    setTimeout(() => {
        document.querySelectorAll('.alert-banner').forEach(alert => {
            alert.style.transition = 'opacity 0.5s ease';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 500);
        });
    }, 8000);

    // Chart.js default settings
    Chart.defaults.font.family = "'Inter', 'Segoe UI', sans-serif";
    Chart.defaults.color = '#6b7280';

    @if(auth()->user()->isDoctor() && isset($doctorMetrics))
    // Appointments Trend Chart
    const appointmentsTrendCtx = document.getElementById('appointmentsTrendChart').getContext('2d');
    new Chart(appointmentsTrendCtx, {
        type: 'line',
        data: {
            labels: {!! json_encode($doctorMetrics['appointments_trend_labels']) !!},
            datasets: [{
                label: 'Appointments',
                data: {!! json_encode($doctorMetrics['appointments_trend_data']) !!},
                borderColor: '#2c5aa0',
                backgroundColor: 'rgba(44, 90, 160, 0.1)',
                fill: true,
                tension: 0.4,
                pointRadius: 4,
                pointBackgroundColor: '#2c5aa0',
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { stepSize: 1 },
                    grid: { color: 'rgba(0,0,0,0.05)' }
                },
                x: {
                    grid: { display: false }
                }
            }
        }
    });

    // Status Breakdown Donut Chart
    const statusBreakdownCtx = document.getElementById('statusBreakdownChart').getContext('2d');
    new Chart(statusBreakdownCtx, {
        type: 'doughnut',
        data: {
            labels: ['Pending', 'Confirmed', 'Completed', 'Cancelled', 'No Show'],
            datasets: [{
                data: [
                    {{ $doctorMetrics['status_breakdown']['pending'] }},
                    {{ $doctorMetrics['status_breakdown']['confirmed'] }},
                    {{ $doctorMetrics['status_breakdown']['completed'] }},
                    {{ $doctorMetrics['status_breakdown']['cancelled'] }},
                    {{ $doctorMetrics['status_breakdown']['no_show'] }}
                ],
                backgroundColor: [
                    '#fbbf24', // pending - yellow
                    '#60a5fa', // confirmed - blue
                    '#34d399', // completed - green
                    '#f87171', // cancelled - red
                    '#a78bfa'  // no_show - purple
                ],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { boxWidth: 12, padding: 10 }
                }
            },
            cutout: '60%'
        }
    });

    // Revenue Bar Chart
    const revenueCtx = document.getElementById('revenueChart').getContext('2d');
    new Chart(revenueCtx, {
        type: 'bar',
        data: {
            labels: {!! json_encode($doctorMetrics['revenue_labels']) !!},
            datasets: [{
                label: 'Revenue',
                data: {!! json_encode($doctorMetrics['revenue_data']) !!},
                backgroundColor: 'rgba(44, 90, 160, 0.8)',
                borderRadius: 6,
                barThickness: 30
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return '$' + context.raw.toLocaleString();
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '$' + value.toLocaleString();
                        }
                    },
                    grid: { color: 'rgba(0,0,0,0.05)' }
                },
                x: {
                    grid: { display: false }
                }
            }
        }
    });

    // Diagnosis Trend Chart
    const diagnosisTrendCtx = document.getElementById('diagnosisTrendChart').getContext('2d');
    new Chart(diagnosisTrendCtx, {
        type: 'bar',
        data: {
            labels: {!! json_encode($doctorMetrics['diagnosis_trend_labels']) !!},
            datasets: [{
                label: 'Diagnoses',
                data: {!! json_encode($doctorMetrics['diagnosis_trend_data']) !!},
                backgroundColor: 'rgba(16, 185, 129, 0.7)',
                borderRadius: 6,
                barThickness: 20
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { stepSize: 1 },
                    grid: { color: 'rgba(0,0,0,0.05)' }
                },
                x: {
                    grid: { display: false }
                }
            }
        }
    });
    @endif
});
</script>
@endpush
