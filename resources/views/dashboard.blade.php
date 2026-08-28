@extends('master')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/doctor-design-system.css') }}">
<link rel="stylesheet" href="{{ asset('css/doctor-dashboard.css') }}">
<link rel="stylesheet" href="{{ asset('css/cases-overview.css') }}">
<style>
.dashboard-header{background:linear-gradient(135deg,#2c5aa0 0%,#1e3a8a 100%)!important;border-radius:12px!important;padding:2.5rem!important;margin-bottom:2rem!important;box-shadow:0 4px 15px rgba(44,90,160,0.15)!important;position:relative;overflow:hidden}
.dashboard-header::before{content:'';position:absolute;top:0;left:0;right:0;height:3px;background:linear-gradient(90deg,#10b981 0%,#059669 100%)}
.dashboard-header h2{color:#fff!important;font-weight:600!important;font-size:2rem!important;margin-bottom:0.4rem!important}
.dashboard-header p{color:rgba(255,255,255,0.9)!important;font-size:0.92rem!important;margin:0!important}
.table-card{background:#fff;border:1px solid #eef2f7;border-radius:12px;padding:1.3rem;box-shadow:0 1px 4px rgba(15,23,42,0.04);margin-bottom:1.25rem}
.section-head-modern{display:flex;align-items:center;justify-content:space-between;gap:0.75rem;margin:-1.3rem -1.3rem 1.1rem -1.3rem;padding:1rem 1.3rem;background:#f8fafc;border-bottom:1px solid #e2e8f0;border-radius:12px 12px 0 0;flex-wrap:wrap}
.section-head-modern .head-left{display:flex;align-items:center;gap:0.75rem}
.section-head-modern .head-icon{width:38px;height:38px;border-radius:9px;display:flex;align-items:center;justify-content:center;font-size:0.95rem;flex-shrink:0;background:#1e293b!important;color:#fff!important;border:1px solid #1e293b!important}
.section-head-modern h5{color:#0f172a!important;font-weight:800!important;letter-spacing:-0.01em;margin:0;font-size:1rem}
.section-head-modern p{color:#475569!important;font-size:0.78rem;margin:2px 0 0;font-weight:500}
.status-badge{padding:0.25rem 0.65rem;border-radius:99px;font-size:0.70rem;font-weight:700;border:1px solid transparent}
.status-badge.pending{background:#fef3c7;color:#92400e;border-color:#fde68a}
.status-badge.confirmed{background:#dbeafe;color:#1e40af;border-color:#bfdbfe}
.status-badge.completed{background:#d1fae5;color:#065f46;border-color:#a7f3d0}
.status-badge.cancelled{background:#fee2e2;color:#991b1b;border-color:#fecaca}
.status-badge.no-show{background:#f1f5f9;color:#475569;border-color:#e2e8f0}
.appointment-row{display:flex;align-items:center;gap:0.9rem;padding:0.85rem 0;border-bottom:1px solid #f1f5f9}
.appointment-row:last-child{border-bottom:none}
.appt-time{min-width:72px;font-weight:700;color:#1e293b;font-size:0.88rem}
.quick-action{flex:1;display:flex;flex-direction:column;align-items:center;gap:0.5rem;padding:1.1rem;background:#f8fafc;border:1px solid #eef2f7;border-radius:10px;text-decoration:none;color:#334155;transition:all .15s;text-align:center;font-weight:600;font-size:0.78rem}
.quick-action:hover{background:#fff;border-color:#cbd5e1;color:#1e293b;transform:translateY(-1px);box-shadow:0 4px 12px rgba(15,23,42,0.06)}
.quick-action i{font-size:1.35rem;color:#1e293b}
.chart-wrap{position:relative;height:230px}
.empty-state{text-align:center;padding:2.2rem 1rem;color:#94a3b8}
.empty-state i{font-size:2.2rem;margin-bottom:0.7rem;opacity:0.6}
</style>
@endpush

@section('content')
<div class="container-fluid" style="background-color: var(--bg-secondary, #f8f9fa);">
    <div class="container py-4">
        <div class="dashboard-header cases-header-compact">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <h2><i class="fas fa-tachometer-alt me-2"></i>Dashboard</h2>
                    <p>Welcome back, {{ auth()->user()->name }} · {{ now()->format('l, M j') }}</p>
                </div>
                <a href="{{ route('ai.ambient-listening.index') }}" class="btn" style="background:#fff;color:#1e293b;border:1px solid #fff;border-radius:10px;padding:0.5rem 1rem;font-weight:700;font-size:0.83rem;box-shadow:0 2px 8px rgba(0,0,0,0.08)"><i class="fas fa-microphone me-2"></i>New Consultation</a>
            </div>
        </div>

        <div class="container p-0">
            {{-- Doctor Metrics --}}
            @if(auth()->user()->isDoctor() && isset($doctorMetrics))
                {{-- Stats compact like appointments/index --}}
                <div class="row g-2 mb-3 cases-stats-compact">
                    <div class="col-6 col-lg-3">
                        <div class="stats-card stats-card--compact">
                            <div class="stats-icon stats-icon--sm" style="background: linear-gradient(135deg, #2c5aa0 0%, #1e3a8a 100%);"><i class="fas fa-calendar-check"></i></div>
                            <div class="stats-text"><p class="stats-number">{{ $doctorMetrics['today_appointments'] }}</p><p class="stats-label">Today</p></div>
                        </div>
                    </div>
                    <div class="col-6 col-lg-3">
                        <div class="stats-card stats-card--compact">
                            <div class="stats-icon stats-icon--sm" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);"><i class="fas fa-hourglass-half"></i></div>
                            <div class="stats-text"><p class="stats-number">{{ $doctorMetrics['pending_count'] }}</p><p class="stats-label">Pending</p></div>
                        </div>
                    </div>
                    <div class="col-6 col-lg-3">
                        <div class="stats-card stats-card--compact">
                            <div class="stats-icon stats-icon--sm" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);"><i class="fas fa-users"></i></div>
                            <div class="stats-text"><p class="stats-number">{{ $doctorMetrics['total_patients'] }}</p><p class="stats-label">Patients</p></div>
                        </div>
                    </div>
                    <div class="col-6 col-lg-3">
                        <div class="stats-card stats-card--compact">
                            <div class="stats-icon stats-icon--sm" style="background: linear-gradient(135deg, #9333ea 0%, #7c3aed 100%);"><i class="fas fa-dollar-sign"></i></div>
                            <div class="stats-text"><p class="stats-number">${{ number_format($doctorMetrics['month_revenue'], 0) }}</p><p class="stats-label">Revenue</p></div>
                        </div>
                    </div>
                </div>

                <div class="row g-2 mb-3 cases-stats-compact">
                    <div class="col-6 col-lg-4">
                        <div class="stats-card stats-card--compact">
                            <div class="stats-icon stats-icon--sm" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);"><i class="fas fa-star"></i></div>
                            <div class="stats-text"><p class="stats-number" style="font-size:1.1rem">{{ $doctorMetrics['total_reviews'] > 0 ? $doctorMetrics['avg_rating'].'/5' : 'N/A' }}</p><p class="stats-label">{{ $doctorMetrics['total_reviews'] }} reviews</p></div>
                        </div>
                    </div>
                    <div class="col-6 col-lg-4">
                        <div class="stats-card stats-card--compact">
                            <div class="stats-icon stats-icon--sm" style="background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%);"><i class="fas fa-folder-open"></i></div>
                            <div class="stats-text"><p class="stats-number">{{ $records->count() }}</p><p class="stats-label">Total Cases</p></div>
                        </div>
                    </div>
                    <div class="col-12 col-lg-4">
                        <div class="stats-card stats-card--compact">
                            <div class="stats-icon stats-icon--sm" style="background: linear-gradient(135deg, #ec4899 0%, #db2777 100%);"><i class="fas fa-check-double"></i></div>
                            <div class="stats-text"><p class="stats-number">{{ $doctorMetrics['month_completed'] }}</p><p class="stats-label">Completed {{ now()->format('M Y') }}</p></div>
                        </div>
                    </div>
                </div>

                <div class="row g-4">
                    <div class="col-lg-8">
                        <div class="table-card">
                            <div class="section-head-modern"><div class="head-left"><div class="head-icon"><i class="fas fa-calendar-alt"></i></div><div><h5>Today's Schedule</h5><p>{{ \Carbon\Carbon::now()->format('l, M j') }} · {{ $doctorMetrics['today_appointments'] }} appointments</p></div></div><a href="{{ route('doctor.appointments.index') }}" class="doctor-btn doctor-btn-outline doctor-btn-sm">View All</a></div>
                            @if($doctorMetrics['today_appointments_list']->count() > 0)
                                @foreach($doctorMetrics['today_appointments_list'] as $appointment)
                                    <div class="appointment-row">
                                        <div class="appt-time">{{ \Carbon\Carbon::parse($appointment->appointment_date)->format('h:i A') }}</div>
                                        <div class="flex-grow-1 min-w-0">
                                            <div style="font-weight:700;color:#0f172a;font-size:0.88rem">{{ $appointment->patient?->name ?? 'Guest Patient' }}</div>
                                            <small style="color:#64748b;font-size:0.78rem">{{ $appointment->appointment_type ? \Illuminate\Support\Str::headline($appointment->appointment_type) : 'Consultation' }} @if($appointment->reason) · {{ \Illuminate\Support\Str::limit($appointment->reason, 30) }} @endif</small>
                                        </div>
                                        <span class="status-badge {{ $appointment->status }}">{{ \Illuminate\Support\Str::headline($appointment->status) }}</span>
                                    </div>
                                @endforeach
                            @else
                                <div class="empty-state"><i class="fas fa-calendar-times"></i><h6 style="font-weight:800;color:#475569">No Appointments Today</h6><p style="font-size:0.84rem">You don't have any scheduled appointments for today.</p></div>
                            @endif
                        </div>

                        <div class="table-card">
                            <div class="section-head-modern"><div class="head-left"><div class="head-icon" style="background:#eff6ff!important;color:#2563eb!important;border-color:#dbeafe!important"><i class="fas fa-file-medical"></i></div><div><h5>Recent Diagnoses</h5><p>Last cases</p></div></div><a href="{{ route('doctor.cases.overview') }}" class="doctor-btn doctor-btn-outline doctor-btn-sm">View All</a></div>
                            @if($doctorMetrics['recent_diagnoses']->count() > 0)
                                @foreach($doctorMetrics['recent_diagnoses'] as $diagnosis)
                                    <div class="appointment-row">
                                        <div class="flex-grow-1 min-w-0">
                                            <div style="font-weight:700;color:#0f172a;font-size:0.88rem">{{ $diagnosis->patient->name ?? 'Unknown Patient' }}</div>
                                            <small style="color:#64748b">{{ \Illuminate\Support\Str::limit($diagnosis->diagnosis_text ?? 'No diagnosis text', 55) }}</small>
                                        </div>
                                        <small style="color:#94a3b8;white-space:nowrap">{{ $diagnosis->created_at->diffForHumans() }}</small>
                                    </div>
                                @endforeach
                            @else
                                <div class="empty-state"><i class="fas fa-folder-open"></i><h6>No Diagnoses Yet</h6><p>Start a consultation to create your first diagnosis.</p></div>
                            @endif
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="table-card">
                            <div class="section-head-modern"><div class="head-left"><div class="head-icon"><i class="fas fa-calendar-week"></i></div><div><h5>Upcoming (7 Days)</h5><p>{{ $doctorMetrics['upcoming_appointments']->count() }} appointments</p></div></div></div>
                            @if($doctorMetrics['upcoming_appointments']->count() > 0)
                                @foreach($doctorMetrics['upcoming_appointments'] as $appointment)
                                    <div class="appointment-row">
                                        <div class="appt-time" style="font-size:0.82rem">{{ \Carbon\Carbon::parse($appointment->appointment_date)->format('D, M d') }}</div>
                                        <div class="flex-grow-1 min-w-0">
                                            <div style="font-weight:700;color:#0f172a;font-size:0.88rem">{{ $appointment->patient?->name ?? 'Guest' }}</div>
                                            <small style="color:#64748b">{{ \Carbon\Carbon::parse($appointment->appointment_date)->format('h:i A') }} @if($appointment->appointment_type) · {{ \Illuminate\Support\Str::headline($appointment->appointment_type) }} @endif</small>
                                        </div>
                                        @if($appointment->status)<span class="status-badge {{ $appointment->status }}" style="font-size:0.65rem">{{ \Illuminate\Support\Str::headline($appointment->status) }}</span>@endif
                                    </div>
                                @endforeach
                            @else
                                <div class="empty-state"><i class="fas fa-calendar"></i><h6>No Upcoming</h6><p>No appointments for next 7 days.</p></div>
                            @endif
                        </div>

                        <div class="table-card">
                            <div class="section-head-modern"><div class="head-left"><div class="head-icon" style="background:#fffbeb!important;color:#d97706!important;border-color:#fde68a!important"><i class="fas fa-star"></i></div><div><h5>Recent Reviews</h5><p>Patient feedback</p></div></div><a href="{{ route('doctor.reviews.index') }}" class="doctor-btn doctor-btn-outline doctor-btn-sm">View All</a></div>
                            @if($doctorMetrics['recent_reviews']->count() > 0)
                                @foreach($doctorMetrics['recent_reviews'] as $review)
                                    <div style="padding:0.85rem 0;border-bottom:1px solid #f1f5f9">
                                        <div style="color:#f59e0b;font-size:0.82rem;margin-bottom:0.2rem">@for($i=1;$i<=5;$i++)@if($i <= $review->rating)<i class="fas fa-star"></i>@else<i class="far fa-star"></i>@endif @endfor</div>
                                        @if($review->comment)<div style="color:#475569;font-size:0.84rem">{{ \Illuminate\Support\Str::limit($review->comment, 85) }}</div>@endif
                                        <small style="color:#94a3b8;font-size:0.76rem">{{ $review->patient->name ?? 'Anonymous' }} · {{ $review->created_at->diffForHumans() }}</small>
                                    </div>
                                @endforeach
                            @else
                                <div class="empty-state"><i class="fas fa-star-half-alt"></i><h6>No Reviews Yet</h6><p>Patient reviews will appear here.</p></div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="table-card">
                    <div class="section-head-modern"><div class="head-left"><div class="head-icon"><i class="fas fa-bolt"></i></div><div><h5>Quick Actions</h5><p>Shortcuts</p></div></div></div>
                    <div class="row g-2">
                        <div class="col-6 col-md-2"><a href="{{ route('ai.ambient-listening.index') }}" class="quick-action"><i class="fas fa-microphone"></i><span>New Consultation</span></a></div>
                        <div class="col-6 col-md-2"><a href="{{ route('doctor.appointments.create') }}" class="quick-action"><i class="fas fa-calendar-plus"></i><span>Book Appointment</span></a></div>
                        <div class="col-6 col-md-2"><a href="{{ route('doctor.patients.index') }}" class="quick-action"><i class="fas fa-users"></i><span>Patients</span></a></div>
                        <div class="col-6 col-md-2"><a href="{{ route('doctor.cases.overview') }}" class="quick-action"><i class="fas fa-folder-open"></i><span>Cases</span></a></div>
                        <div class="col-6 col-md-2"><a href="{{ route('doctor.availability.index') }}" class="quick-action"><i class="fas fa-clock"></i><span>Availability</span></a></div>
                        <div class="col-6 col-md-2"><a href="{{ route('doctor.analytics.index') }}" class="quick-action"><i class="fas fa-chart-line"></i><span>Analytics</span></a></div>
                    </div>
                </div>

                <div class="row g-4">
                    <div class="col-lg-8">
                        <div class="table-card">
                            <div class="section-head-modern"><div class="head-left"><div class="head-icon"><i class="fas fa-chart-line"></i></div><div><h5>Appointments Trend (14 Days)</h5><p>Daily volume</p></div></div></div>
                            <div class="chart-wrap"><canvas id="appointmentsTrendChart"></canvas></div>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="table-card">
                            <div class="section-head-modern"><div class="head-left"><div class="head-icon"><i class="fas fa-chart-pie"></i></div><div><h5>Appointment Status</h5><p>Breakdown</p></div></div></div>
                            <div class="chart-wrap"><canvas id="statusBreakdownChart"></canvas></div>
                        </div>
                    </div>
                </div>
                <div class="row g-4">
                    <div class="col-lg-6">
                        <div class="table-card">
                            <div class="section-head-modern"><div class="head-left"><div class="head-icon" style="background:#f0fdf4!important;color:#059669!important;border-color:#a7f3d0!important"><i class="fas fa-dollar-sign"></i></div><div><h5>Revenue (6 Months)</h5><p>Monthly</p></div></div></div>
                            <div class="chart-wrap"><canvas id="revenueChart"></canvas></div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="table-card">
                            <div class="section-head-modern"><div class="head-left"><div class="head-icon"><i class="fas fa-clipboard-list"></i></div><div><h5>Diagnoses Trend (14 Days)</h5><p>Daily</p></div></div></div>
                            <div class="chart-wrap"><canvas id="diagnosisTrendChart"></canvas></div>
                        </div>
                    </div>
                </div>

            @else
                <div class="table-card text-center py-4">
                    <div class="empty-state"><i class="fas fa-folder-open"></i><h6 style="font-weight:800;color:#0f172a">No Data Yet</h6><p>{{ $records->count() }} total cases</p></div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function(){
  Chart.defaults.font.family="'Inter','Segoe UI',sans-serif"; Chart.defaults.color='#6b7280';
  @if(auth()->user()->isDoctor() && isset($doctorMetrics))
  new Chart(document.getElementById('appointmentsTrendChart').getContext('2d'),{type:'line',data:{labels:{!! json_encode($doctorMetrics['appointments_trend_labels']) !!},datasets:[{label:'Appointments',data:{!! json_encode($doctorMetrics['appointments_trend_data']) !!},borderColor:'#2c5aa0',backgroundColor:'rgba(44,90,160,0.08)',fill:true,tension:0.4,pointRadius:3,pointBackgroundColor:'#2c5aa0'}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{y:{beginAtZero:true,ticks:{stepSize:1},grid:{color:'rgba(0,0,0,0.05)'}},x:{grid:{display:false}}}}});
  new Chart(document.getElementById('statusBreakdownChart').getContext('2d'),{type:'doughnut',data:{labels:['Pending','Confirmed','Completed','Cancelled','No Show'],datasets:[{data:[{{ $doctorMetrics['status_breakdown']['pending'] }},{{ $doctorMetrics['status_breakdown']['confirmed'] }},{{ $doctorMetrics['status_breakdown']['completed'] }},{{ $doctorMetrics['status_breakdown']['cancelled'] }},{{ $doctorMetrics['status_breakdown']['no_show'] }}],backgroundColor:['#fbbf24','#60a5fa','#34d399','#f87171','#a78bfa'],borderWidth:0}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{position:'bottom',labels:{boxWidth:12,padding:10}}},cutout:'60%'}});
  new Chart(document.getElementById('revenueChart').getContext('2d'),{type:'bar',data:{labels:{!! json_encode($doctorMetrics['revenue_labels']) !!},datasets:[{label:'Revenue',data:{!! json_encode($doctorMetrics['revenue_data']) !!},backgroundColor:'rgba(44,90,160,0.8)',borderRadius:6,barThickness:30}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false},tooltip:{callbacks:{label:function(c){return '$'+c.raw.toLocaleString()}}}},scales:{y:{beginAtZero:true,ticks:{callback:function(v){return '$'+v.toLocaleString()}},grid:{color:'rgba(0,0,0,0.05)'}},x:{grid:{display:false}}}}});
  new Chart(document.getElementById('diagnosisTrendChart').getContext('2d'),{type:'bar',data:{labels:{!! json_encode($doctorMetrics['diagnosis_trend_labels']) !!},datasets:[{label:'Diagnoses',data:{!! json_encode($doctorMetrics['diagnosis_trend_data']) !!},backgroundColor:'rgba(16,185,129,0.7)',borderRadius:6,barThickness:20}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{y:{beginAtZero:true,ticks:{stepSize:1},grid:{color:'rgba(0,0,0,0.05)'}},x:{grid:{display:false}}}}});
  @endif
});
</script>
@endpush
