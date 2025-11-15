@extends('master')


@push('styles')
<link rel="stylesheet" href="{{ asset('css/custom-openai.css') }}">
<link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">

@endpush

@section('content')


<div class="dashboard-container">
    <div class="container">
        <!-- Enhanced Page Header -->
        <div class="dashboard-header py-2 border-bottom">
            <h2 class="h1 mb-1" style="font-weight: 700;">Dashboard</h2>
            <p>Overview of your activities</p>
        </div>

        @auth
            <!-- Subscription Status - Compact and Less Prominent -->
            @if(isset($trialInfo) && $trialInfo['is_in_trial'])
                <div class="alert alert-info alert-dismissible fade show mb-3" role="alert" style="border-radius: 10px; border: none; box-shadow: 0 4px 15px rgba(13, 202, 240, 0.15);">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-info-circle text-info me-2"></i>
                        <div class="flex-grow-1">
                            <small class="fw-bold">Trial Active</small> -
                            <a href="{{ route('subscription.pricing') }}" class="text-decoration-none">Upgrade anytime</a>
                        </div>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
                    </div>
                </div>
            @elseif($trialInfo['has_active_subscription'] && !$trialInfo['is_in_trial'])
                <div class="alert alert-success alert-dismissible fade show mb-3" role="alert" style="border-radius: 10px; border: none; box-shadow: 0 4px 15px rgba(25, 135, 84, 0.15);">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-check-circle text-success me-2"></i>
                        <div class="flex-grow-1">
                            <small class="fw-bold">Subscription Active</small>
                            @if(Auth::user()->monthlyInvoiceSetting && Auth::user()->monthlyInvoiceSetting->subscription_ends_at)
                                - Expires {{ Auth::user()->monthlyInvoiceSetting->subscription_ends_at->format('M d, Y') }}
                            @endif
                        </div>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
                    </div>
                </div>
            @elseif($trialInfo['trial_status'] === 'expired' && Auth::user()->isRestricted())
                <div class="alert alert-danger mb-3" role="alert" style="border-radius: 10px; border: none; box-shadow: 0 4px 15px rgba(220, 53, 69, 0.15);">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-ban text-danger me-2"></i>
                        <div class="flex-grow-1">
                            <small class="fw-bold">Account Restricted</small> - {{ Auth::user()->getRestrictionMessage() }}
                        </div>
                        <a href="{{ route('subscription.pricing') }}" class="btn btn-danger btn-sm ms-2">Pay Now</a>
                    </div>
                </div>
            @elseif(Auth::user()->isInGracePeriod())
                <div class="alert alert-warning mb-3" role="alert" style="border-radius: 10px; border: none; box-shadow: 0 4px 15px rgba(255, 193, 7, 0.15);">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-clock text-warning me-2"></i>
                        <div class="flex-grow-1">
                            <small class="fw-bold">Grace Period</small> - {{ Auth::user()->getDaysRemainingInCurrentPeriod() }} days left
                        </div>
                        <a href="{{ route('subscription.manage') }}" class="btn btn-warning btn-sm ms-2">Renew</a>
                    </div>
                </div>
            @elseif(Auth::user()->isInWarningPeriod())
                <div class="alert alert-danger mb-3" role="alert" style="border-radius: 10px; border: none; box-shadow: 0 4px 15px rgba(220, 53, 69, 0.15);">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-exclamation-triangle text-danger me-2"></i>
                        <div class="flex-grow-1">
                            <small class="fw-bold">Final Warning</small> - {{ Auth::user()->getDaysRemainingInCurrentPeriod() }} days left
                        </div>
                        <a href="{{ route('subscription.manage') }}" class="btn btn-danger btn-sm ms-2">Renew Now</a>
                    </div>
                </div>
            @elseif(Auth::user()->getOverdueInvoicesCount() > 0)
                <div class="alert alert-warning alert-dismissible fade show mb-3" role="alert" style="border-radius: 10px; border: none; box-shadow: 0 4px 15px rgba(255, 193, 7, 0.15);">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-exclamation-triangle text-warning me-2"></i>
                        <div class="flex-grow-1">
                            <small class="fw-bold">{{ Auth::user()->getOverdueInvoicesCount() }} Overdue Invoice(s)</small>
                        </div>
                        <a href="{{ route('invoices.index') }}" class="btn btn-warning btn-sm ms-2">View</a>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
                    </div>
                </div>
            @elseif(Auth::user()->getTotalUnpaidMonthlyAmount() > 0)
                <div class="alert alert-info alert-dismissible fade show mb-3" role="alert" style="border-radius: 10px; border: none; box-shadow: 0 4px 15px rgba(13, 202, 240, 0.15);">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-calendar-alt text-info me-2"></i>
                        <div class="flex-grow-1">
                            <small class="fw-bold">${{ number_format(Auth::user()->getTotalUnpaidMonthlyAmount(), 2) }} Due</small>
                        </div>
                        <a href="{{ route('invoices.index') }}" class="btn btn-info btn-sm ms-2">Pay Now</a>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
                    </div>
                </div>
            @endif
        @endauth

        <!-- Quick Actions Card -->
        <div class="chart-card">
            <h4><i class="fas fa-bolt me-2"></i>Quick Actions</h4>
            <div class="d-flex flex-wrap gap-2 mt-3">
                {{-- AI Ask temporarily disabled --}}
                {{-- @if(auth()->user()->canAccessRoute('ai.ask-ai'))
                    <a href="{{ route('ai.ask-ai') }}" class="btn-custom-primary">
                        <i class="fas fa-user-plus me-2"></i> Add New Patient
                    </a>
                @endif --}}
                
                @if(auth()->user()->canAccessRoute('diagnosis'))
                    <a href="{{ route('diagnosis.create') }}" class="btn-custom-primary">
                        <i class="fas fa-file-medical me-2"></i> Create Diagnosis
                    </a>
                @endif
                
                @if(auth()->user()->canAccessRoute('doctor.patient-management.index'))
                    <a href="{{ route('doctor.patient-management.index') }}" class="btn-custom-secondary">
                        <i class="fas fa-list me-2"></i> View All Patient Management
                    </a>
                @endif
                
                @if(auth()->user()->canAccessRoute('diagnosis'))
                    <a href="{{ route('diagnosis.index') }}" class="btn-custom-secondary">
                        <i class="fas fa-clipboard-list me-2"></i> View Diagnoses
                    </a>
                @endif

                <!-- Additional actions for permitted users -->
                @if(auth()->user()->canAccessRoute('doctor.appointments.index'))
                    <a href="{{ route('doctor.appointments.index') }}" class="btn-custom-secondary">
                        <i class="fas fa-calendar me-2"></i> Manage Appointments
                    </a>
                @endif
                
                @if(auth()->user()->canAccessRoute('settings'))
                    <a href="{{ route('settings') }}" class="btn-custom-secondary">
                        <i class="fas fa-cog me-2"></i> Settings
                    </a>
                @endif
            </div>
        </div>

        <!-- Key Statistics Overview -->
        <div class="row mb-4">
            <div class="col-lg-8">
                <div class="chart-card">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="chart-title mb-0">Patient Overview</h6>
                        <div class="stats-summary">
                            <span class="badge bg-primary me-2">
                                @php
                                    $patientKeys = [];
                                    foreach ($records as $record) {
                                        if (isset($record->patient_key) && $record->patient_key) {
                                            $patientKeys[$record->patient_key] = true;
                                        } elseif (isset($record->patient_id)) {
                                            $patientKeys['diagnosis_' . $record->patient_id] = true;
                                        }
                                    }
                                    echo count($patientKeys);
                                @endphp Patients
                            </span>
                            <span class="badge bg-info">
                                @php
                                    $uniquePatients = [];
                                    $ages = [];
                                    foreach ($records as $record) {
                                        $key = isset($record->patient_key) && $record->patient_key ? $record->patient_key : ('diagnosis_' . ($record->patient_id ?? 'unknown'));
                                        if (!isset($uniquePatients[$key]) && isset($record->age) && $record->age) {
                                            $uniquePatients[$key] = true;
                                            $ages[] = (float) $record->age;
                                        }
                                    }
                                    $avgAge = count($ages) > 0 ? round(array_sum($ages) / count($ages)) : 0;
                                    echo $avgAge;
                                @endphp Avg Age
                            </span>
                        </div>
                    </div>
                    <div id="patientManagementChart" style="height: 250px;"></div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="stats-card h-100">
                    <h6 class="mb-3"><i class="fas fa-chart-pie me-2"></i>Demographics</h6>
                    <div id="demographicsChart" style="height: 200px;"></div>
                </div>
            </div>
        </div>

        @if($doctorData)
        <!-- Doctor Performance Overview -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="chart-card">
                    <h6 class="chart-title mb-3">
                        <i class="fas fa-stethoscope me-2"></i>Doctor Performance Overview
                    </h6>
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <div class="stats-card text-center">
                                <div class="stats-icon mx-auto mb-2" style="background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);">
                                    <i class="fas fa-calendar-day"></i>
                                </div>
                                <h4 class="stats-number mb-1">{{ $doctorData['stats']['today_appointments'] }}</h4>
                                <p class="stats-label mb-0">Today's Appointments</p>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="stats-card text-center">
                                <div class="stats-icon mx-auto mb-2" style="background: linear-gradient(135deg, #27ae60 0%, #229954 100%);">
                                    <i class="fas fa-star"></i>
                                </div>
                                <h4 class="stats-number mb-1">{{ number_format($doctorData['stats']['average_rating'], 1) }}</h4>
                                <p class="stats-label mb-0">Average Rating</p>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="stats-card text-center">
                                <div class="stats-icon mx-auto mb-2" style="background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);">
                                    <i class="fas fa-file-medical"></i>
                                </div>
                                <h4 class="stats-number mb-1">{{ auth()->user()->doctorDiagnoses()->count() }}</h4>
                                <p class="stats-label mb-0">Total Diagnoses</p>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="stats-card text-center">
                                <div class="stats-icon mx-auto mb-2" style="background: linear-gradient(135deg, #8e44ad 0%, #7d3c98 100%);">
                                    <i class="fas fa-dollar-sign"></i>
                                </div>
                                <h4 class="stats-number mb-1">${{ number_format($doctorData['stats']['revenue_this_month'], 0) }}</h4>
                                <p class="stats-label mb-0">This Month Revenue</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Doctor Dashboard Content -->
        <div class="row mb-5">
            <!-- Today's Schedule -->
            <div class="col-lg-8 mb-4">
                <div class="table-card">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="table-title mb-0">
                            <i class="fas fa-calendar-check me-2"></i>Today's Schedule
                        </h6>
                        <span class="badge bg-primary">{{ now()->format('l, F j, Y') }}</span>
                    </div>

                    @if($doctorData['todayAppointments']->count() > 0)
                        <div class="table-responsive">
                            <table class="table custom-table mb-0">
                                <thead>
                                    <tr>
                                        <th>Time</th>
                                        <th>Patient</th>
                                        <th>Type</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($doctorData['todayAppointments'] as $appointment)
                                        <tr>
                                            <td>
                                                <strong>{{ $appointment->appointment_date->format('g:i A') }}</strong><br>
                                                <small class="text-muted">{{ $appointment->appointment_date->diffInMinutes($appointment->appointment_end) }}min</small>
                                            </td>
                                            <td>
                                                <strong>{{ $appointment->patient->name ?? 'Unknown Patient' }}</strong><br>
                                                <small class="text-muted">{{ $appointment->reason }}</small>
                                            </td>
                                            <td>
                                                <i class="fas fa-{{ $appointment->appointment_type == 'video_call' ? 'video' : ($appointment->appointment_type == 'phone_call' ? 'phone' : 'hospital') }} me-1"></i>
                                                {{ ucfirst(str_replace('_', ' ', $appointment->appointment_type)) }}
                                            </td>
                                            <td>
                                                <span class="badge {{ $appointment->status == 'confirmed' ? 'bg-success' : 'bg-warning' }}">
                                                    {{ ucfirst($appointment->status) }}
                                                </span>
                                            </td>
                                            <td>
                                                <a href="{{ route('doctor.appointments.show', $appointment) }}"
                                                   class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-eye me-1"></i>View
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="empty-state">
                            <i class="fas fa-calendar-check"></i>
                            <h5>No appointments today</h5>
                            <p>Your schedule is clear for today</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Doctor Sidebar -->
            <div class="col-lg-4">
                <!-- Quick Actions -->
                <div class="stats-card mb-4">
                    <h6 class="mb-3">
                        <i class="fas fa-bolt me-2"></i>Quick Actions
                    </h6>
                    <div class="d-grid gap-2">
                        @if(auth()->user()->canAccessRoute('doctor.appointments.index'))
                            <a href="{{ route('doctor.appointments.index') }}" class="btn btn-primary-custom btn-sm">
                                <i class="fas fa-calendar me-2"></i>View All Appointments
                            </a>
                        @endif
                        
                        @if(auth()->user()->canAccessRoute('diagnosis'))
                            <a href="{{ route('diagnosis.create') }}" class="btn btn-primary-custom btn-sm">
                                <i class="fas fa-file-medical me-2"></i>Create Diagnosis
                            </a>
                        @endif
                        
                        @if(auth()->user()->canAccessRoute('diagnosis'))
                            <a href="{{ route('diagnosis.index') }}" class="btn btn-secondary-custom btn-sm">
                                <i class="fas fa-clipboard-list me-2"></i>View Diagnoses
                            </a>
                        @endif
                        
                        @if(auth()->user()->canAccessRoute('doctor.availability.index'))
                            <a href="{{ route('doctor.availability.index') }}" class="btn btn-secondary-custom btn-sm">
                                <i class="fas fa-clock me-2"></i>Manage Availability
                            </a>
                        @endif
                        
                        @if(auth()->user()->canAccessRoute('doctor.reviews.index'))
                            <a href="{{ route('doctor.reviews.index') }}" class="btn btn-secondary-custom btn-sm">
                                <i class="fas fa-star me-2"></i>View Reviews
                            </a>
                        @endif
                        
                        @if(auth()->user()->canAccessRoute('doctor.profile.edit'))
                            <a href="{{ route('doctor.profile.edit') }}" class="btn btn-secondary-custom btn-sm">
                                <i class="fas fa-user-edit me-2"></i>Edit Profile
                            </a>
                        @endif
                        
                        @if(auth()->user()->canAccessRoute('doctor.settings.appointments'))
                            <a href="{{ route('doctor.settings.appointments') }}" class="btn btn-secondary-custom btn-sm">
                                <i class="fas fa-cog me-2"></i>Appointment Settings
                            </a>
                        @endif
                        
                        @if(auth()->user()->canAccessRoute('doctor.landing-page.index'))
                            <a href="{{ route('doctor.landing-page.index') }}" class="btn btn-secondary-custom btn-sm">
                                <i class="fas fa-globe me-2"></i>Landing Page
                            </a>
                        @endif
                        
                        @if(auth()->user()->canAccessRoute('doctor.notes.index'))
                            <a href="{{ route('doctor.notes.index') }}" class="btn btn-secondary-custom btn-sm">
                                <i class="fas fa-sticky-note me-2"></i>My Notes
                            </a>
                        @endif
                        
                        @if(auth()->user()->canAccessRoute('doctor.notes.create'))
                            <a href="{{ route('doctor.notes.create') }}" class="btn btn-secondary-custom btn-sm">
                                <i class="fas fa-plus me-2"></i>Add Note
                            </a>
                        @endif
                        
                        @if(auth()->user()->canAccessRoute('doctor.blog.index'))
                            <a href="{{ route('doctor.blog.index') }}" class="btn btn-secondary-custom btn-sm">
                                <i class="fas fa-blog me-2"></i>Manage Blog
                            </a>
                        @endif
                    </div>
                </div>

                <!-- Pending Appointments -->
                @if(auth()->user()->canAccessRoute('doctor.appointments.index') && $doctorData['pendingAppointments']->count() > 0)
                    <div class="stats-card" style="margin-bottom: 2rem; position: relative; z-index: 2;">
                        <h6 class="mb-3">
                            <i class="fas fa-clock me-2"></i>Pending Appointments
                        </h6>
                        <div class="list-group list-group-flush">
                            @foreach($doctorData['pendingAppointments'] as $appointment)
                                <div class="list-group-item border-0 px-0 py-2">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <strong class="text-dark">{{ $appointment->patient->name ?? 'Unknown Patient' }}</strong><br>
                                            <small class="text-muted">{{ $appointment->appointment_date->format('M j, g:i A') }}</small>
                                        </div>
                                        <div class="btn-group-sm">
                                            <form method="POST" action="{{ route('doctor.appointments.confirm', $appointment) }}" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-success btn-sm" title="Confirm">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            </form>
                                            <a href="{{ route('doctor.appointments.show', $appointment) }}"
                                               class="btn btn-primary btn-sm" title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <div class="text-center mt-3">
                            <a href="{{ route('doctor.appointments.index', ['status' => 'pending']) }}"
                               class="btn btn-sm btn-primary-custom">
                                View all pending →
                            </a>
                        </div>
                    </div>
                @endif

                <!-- Recent Reviews -->
                @if($doctorData['recentReviews']->count() > 0)
                    <div class="stats-card" style="margin-bottom: 2rem; position: relative; z-index: 2;">
                        <h6 class="mb-3">
                            <i class="fas fa-star me-2"></i>Recent Reviews
                        </h6>
                        <div class="list-group list-group-flush">
                            @foreach($doctorData['recentReviews'] as $review)
                                <div class="list-group-item border-0 px-0 py-2">
                                    <div class="d-flex text-warning mb-1">
                                        @for($i = 1; $i <= 5; $i++)
                                            @if($i <= $review->rating)
                                                <i class="fas fa-star"></i>
                                            @else
                                                <i class="far fa-star"></i>
                                            @endif
                                        @endfor
                                    </div>
                                    @if($review->comment)
                                        <p class="mb-1 small">{{ Str::limit($review->comment, 60) }}</p>
                                    @endif
                                    <small class="text-muted">
                                        by {{ $review->is_anonymous ? 'Anonymous' : ($review->patient->name ?? 'Unknown Patient') }} •
                                        {{ $review->created_at->diffForHumans() }}
                                    </small>
                                </div>
                            @endforeach
                        </div>
                        <div class="text-center mt-3">
                            <a href="{{ route('doctor.reviews.index') }}"
                               class="btn btn-sm btn-primary-custom">
                                View all reviews →
                            </a>
                        </div>
                    </div>
                @endif
            </div>
        </div>
        @endif

@if(isset($appointments))
<div class="row mb-5">
    <div class="col-12">
        <div class="table-card">
            <h6 class="table-title mb-0">
                <i class="fas fa-calendar-check me-2"></i>My Appointments
            </h6>
        </div>
    </div>
</div>

@if($appointments->count() > 0)
<div class="row">
    @foreach($appointments as $appointment)
    <div class="col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-header bg-primary text-white">
                <h6 class="mb-0">{{ $appointment->appointment_date->format('M d, Y g:i A') }} - {{ optional($appointment->doctor->user)->name ?? 'Unknown Doctor' }}</h6>
                <p class="mb-0 small opacity-75">{{ $appointment->reason }}</p>
            </div>
            <div class="card-body">
                <p class="mb-2"><strong>Status:</strong> 
                    <span class="badge {{ $appointment->status == 'completed' ? 'bg-success' : ($appointment->status == 'cancelled' ? 'bg-danger' : 'bg-warning') }}">
                        {{ ucfirst($appointment->status) }}
                    </span>
                </p>
                
                @if($appointment->prescription_given == true)
                <div class="prescriptions-section mt-3 border-top pt-3">
                    <h6 class="mb-3"><i class="fas fa-pills me-2 text-primary"></i>Prescriptions</h6>
                    
                    @if($appointment->prescriptions->count() > 0)
                    <div class="list-group list-group-flush" style="max-height: 300px; overflow-y: auto;">
                        @foreach($appointment->prescriptions as $prescription)
                        <div class="list-group-item px-0 py-2">
                            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                                <div class="flex-grow-1">
                                    <h6 class="mb-1 fw-bold">{{ $prescription->medication_name }}</h6>
                                    <div class="row g-2 small text-muted">
                                        <div class="col-6">
                                            <strong>Dosage:</strong> {{ $prescription->dosage }}
                                        </div>
                                        <div class="col-6">
                                            <strong>Frequency:</strong> {{ $prescription->frequency }}
                                        </div>
                                        <div class="col-6">
                                            <strong>Duration:</strong> {{ $prescription->duration }}
                                        </div>
                                        <div class="col-6">
                                            <strong>Issued:</strong> {{ \Carbon\Carbon::parse($prescription->created_at)->format('M d, Y') }}
                                        </div>
                                    </div>
                                    @if($prescription->notes)
                                    <p class="mt-2 mb-0 small"><strong>Notes:</strong> {{ $prescription->notes }}</p>
                                    @endif
                                </div>
                                <div class="ms-2 mt-1">
                                    <a href="{{ route('prescriptions.pdf', $prescription->id) }}" class="btn btn-primary btn-sm" target="_blank" title="Download Prescription PDF">
                                        <i class="fas fa-download"></i> PDF
                                    </a>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @else
                    <p class="text-muted mb-0">No prescriptions yet.</p>
                    @endif
                </div>
                @endif
            </div>
        </div>
    </div>
    @endforeach
</div>
@else
<div class="col-12">
    <div class="empty-state">
        <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
        <h5>No appointments found</h5>
        <p class="text-muted">You don't have any scheduled appointments at the moment.</p>
    </div>
</div>
@endif
@endif
        <!-- Activity Timeline -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="chart-card">
                    <h6 class="chart-title mb-3">Patient Visits Over Time</h6>
                    <div id="visitsTimelineChart" style="height: 250px;"></div>
                </div>
            </div>
        </div>

        <!-- Analytics Section -->
        <div class="row mb-4">
            <div class="col-lg-6 mb-4">
                <div class="chart-card">
                    <h6 class="chart-title mb-3">Age Distribution</h6>
                    <div id="ageDistributionChart" style="height: 250px;"></div>
                </div>
            </div>
            <div class="col-lg-6 mb-4">
                <div class="stats-card h-100">
                    <h6 class="mb-3"><i class="fas fa-chart-line me-2"></i>Key Metrics</h6>
                    <div class="row g-3">
                        <div class="col-6">
                            <div class="text-center">
                                <h4 class="stats-number mb-1" id="new-patients-count">{{ $records->where('created_at', '>=', now()->subDays(30))->groupBy('patient_key')->count() }}</h4>
                                <small class="text-muted">New Patients (30d)</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="text-center">
                                <h4 class="stats-number mb-1" id="return-visits-count">
                                    @php
                                        $returnVisits = $records->where('created_at', '>=', now()->subDays(30))->count() - $records->where('created_at', '>=', now()->subDays(30))->groupBy('patient_key')->count();
                                        echo $returnVisits > 0 ? $returnVisits : 0;
                                    @endphp
                                </h4>
                                <small class="text-muted">Return Visits (30d)</small>
                            </div>
                        </div>
                        <div class="col-12">
                            <hr class="my-2">
                        </div>
                        <div class="col-12">
                            <div class="text-center">
                                <h4 class="stats-number mb-1" id="growth-rate">
                                    @php
                                        $currentMonth = $records->where('created_at', '>=', now()->startOfMonth())->count();
                                        $lastMonth = $records->where('created_at', '>=', now()->subMonth()->startOfMonth())
                                            ->where('created_at', '<', now()->startOfMonth())->count();
                                        $growthRate = $lastMonth > 0 ? round((($currentMonth - $lastMonth) / $lastMonth) * 100) : 0;
                                        echo $growthRate > 0 ? '+'.$growthRate : $growthRate;
                                    @endphp%
                                </h4>
                                <small class="text-muted">Monthly Growth Rate</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Patient Management -->
        <div class="table-card mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="table-title mb-0">Recent Patients</h6>
                <div>
                    <div class="input-group input-group-sm me-2 d-inline-flex" style="width: 200px;">
                        <input type="text" class="form-control" id="patient-search" placeholder="Search patients...">
                        <button class="btn btn-outline-secondary" type="button" id="search-btn">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                    <a href="{{ route('doctor.patient-management.index') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-external-link-alt me-1"></i> View All
                    </a>
                </div>
            </div>

            @if(count($records) > 0)
                @php
                    // Group patients by patient_key to avoid duplication
                    $patientGroups = [];

                    foreach ($records as $record) {
                        $key = $record->patient_key ?? ($record->name . '-' . $record->age . '-' . $record->gender);

                        if (!isset($patientGroups[$key])) {
                            // Initialize with the first record
                            $patientGroups[$key] = [
                                'patient' => $record,
                                'visits' => [],
                                'visit_count' => 0,
                                'last_visit' => $record->created_at
                            ];
                        }

                        // Add this record to the visits array
                        $patientGroups[$key]['visits'][] = $record;
                        $patientGroups[$key]['visit_count']++;

                        // Update last visit date if this record is more recent
                        if ($record->created_at > $patientGroups[$key]['last_visit']) {
                            $patientGroups[$key]['last_visit'] = $record->created_at;
                        }
                    }

                    // Sort by most recent visit
                    uasort($patientGroups, function($a, $b) {
                        return $b['last_visit'] <=> $a['last_visit'];
                    });

                    // Take only the first 10 for display
                    $patientGroups = array_slice($patientGroups, 0, 10, true);
                @endphp

                <div class="table-responsive">
                    <table class="table custom-table mb-0" id="patients-table">
                        <thead>
                            <tr>
                                <th><a href="#" class="sort-link" data-sort="name">Patient Name <i class="fas fa-sort"></i></a></th>
                                <th><a href="#" class="sort-link" data-sort="age">Age <i class="fas fa-sort"></i></a></th>
                                <th><a href="#" class="sort-link" data-sort="gender">Gender <i class="fas fa-sort"></i></a></th>
                                <th><a href="#" class="sort-link" data-sort="visits">Total Visits <i class="fas fa-sort"></i></a></th>
                                <th><a href="#" class="sort-link" data-sort="last-visit">Last Visit <i class="fas fa-sort"></i></a></th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($patientGroups as $key => $group)
                                <tr data-patient-key="{{ $key }}" data-visits="{{ count($group['visits']) }}" data-last-visit="{{ $group['last_visit']->timestamp }}">
                                    <td>{{ $group['patient']->name ?? 'N/A' }}</td>
                                    <td>{{ $group['patient']->age ?? 'N/A' }}</td>
                                    <td>
                                        <span class="badge" style="background-color: {{ $group['patient']->gender == 'male' ? '#3498db' : '#e74c3c' }}; color: white;">
                                            {{ ucfirst($group['patient']->gender ?? 'N/A') }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary">{{ $group['visit_count'] }}</span>
                                    </td>
                                    <td data-date="{{ $group['last_visit']->timestamp }}">{{ $group['last_visit'] ? $group['last_visit']->format('M d, Y') : 'N/A' }}</td>
                                    <td>
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-sm btn-view-patient btn-primary-custom"
                                                    data-patient-key="{{ $key }}"
                                                    data-patient-name="{{ $group['patient']->name }}"
                                                    data-patient-age="{{ $group['patient']->age }}"
                                                    data-patient-gender="{{ $group['patient']->gender }}">
                                                <i class="fas fa-eye me-1"></i>View
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="d-flex justify-content-between align-items-center mt-3">
                    <div class="showing-entries">
                        Showing <span id="showing-count">{{ count($patientGroups) }}</span> of {{ count(array_unique($records->pluck('patient_key')->toArray())) }} patients
                    </div>
                    <div class="table-pagination">
                        <button class="btn btn-sm btn-outline-secondary me-1" id="prev-page" disabled>
                            <i class="fas fa-chevron-left"></i>
                        </button>
                        <span id="current-page">1</span> / <span id="total-pages">1</span>
                        <button class="btn btn-sm btn-outline-secondary ms-1" id="next-page" disabled>
                            <i class="fas fa-chevron-right"></i>
                        </button>
                    </div>
                </div>

                <!-- Patient Details Modal -->
                <div class="modal fade" id="patientModal" tabindex="-1" aria-labelledby="patientModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header" style="background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%); color: white;">
                                <h5 class="modal-title" id="patientModalLabel" style="color: #fff">Patient Details</h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="row mb-4">
                                    <div class="col-md-4">
                                        <div class="patient-info-card">
                                            <h6 class="text-muted">Patient Name</h6>
                                            <p class="patient-name fs-5 fw-bold">-</p>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="patient-info-card">
                                            <h6 class="text-muted">Age</h6>
                                            <p class="patient-age fs-5 fw-bold">-</p>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="patient-info-card">
                                            <h6 class="text-muted">Gender</h6>
                                            <p class="patient-gender fs-5 fw-bold">-</p>
                                        </div>
                                    </div>
                                </div>

                                <h6 class="mb-3 border-bottom pb-2">Visit History</h6>
                                <div class="table-responsive">
                                    <table class="table table-hover" id="visit-history-table">
                                        <thead>
                                            <tr>
                                                <th>Visit #</th>
                                                <th>Date</th>
                                                <th>Symptoms</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody id="visit-history-body">
                                            <!-- Visit history will be populated dynamically -->
                                        </tbody>
                                    </table>
                                </div>

                                <div id="visit-details-section" class="mt-4" style="display: none;">
                                    <h6 class="mb-3 border-bottom pb-2">Visit Details</h6>
                                    <div id="visit-details-content" class="response-text">
                                        <!-- Visit details will be populated dynamically -->
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                {{-- AI Ask temporarily disabled --}}
                                {{-- <a href="{{ route('ai.ask-ai') }}" id="new-visit-btn" class="btn btn-primary-custom">
                                    <i class="fas fa-plus me-1"></i> New Visit
                                </a> --}}
                            </div>
                        </div>
                    </div>
                </div>
            @else
                <div class="empty-state">
                    <i class="fas fa-user-doctor"></i>
                    <h5>No patients yet</h5>
                    <p>Start by adding your first patient</p>
                    {{-- AI Ask temporarily disabled --}}
                    {{-- <a href="{{ route('ai.ask-ai') }}" class="btn-primary-custom mt-3">
                        <i class="fas fa-plus me-2"></i> Add First Patient
                    </a> --}}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Make PHP variables available to JavaScript
    window.chartLabels = @json($chartLabels ?? []);
    window.chartData = @json($chartData ?? []);
    window.records = @json($records ?? []);
    window.weeklyCount = @json($weeklyCount ?? 0);
    window.doctorData = @json($doctorData ?? null);
    window.trialInfo = @json($trialInfo ?? null);
</script>
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="{{ asset('js/dashboard.js') }}"></script>

