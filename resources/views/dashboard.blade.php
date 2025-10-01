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
            <!-- Subscription CTA (no free trial) -->
            @if(isset($trialInfo) && $trialInfo['is_in_trial'])
                <!-- If some legacy users still in trial, show a neutral banner -->
                <div class="alert alert-info alert-dismissible fade show" role="alert" style="border-radius: 20px; border: none; box-shadow: 0 8px 25px rgba(13, 202, 240, 0.2);">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <i class="fas fa-info-circle fa-2x text-info"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h5 class="alert-heading mb-2">
                                <i class="fas fa-clock me-2"></i>Trial Period Active
                            </h5>
                            <p class="mb-2">Some accounts may still have an active trial. You can subscribe anytime to Monthly or Yearly.</p>
                            <div class="d-flex gap-2">
                                <a href="{{ route('subscription.pricing') }}" class="btn btn-info btn-sm">
                                    <i class="fas fa-credit-card me-1"></i>Choose Monthly or Yearly
                                </a>
                                <a href="{{ route('subscription.manage') }}" class="btn btn-outline-info btn-sm">
                                    <i class="fas fa-cog me-1"></i>Manage Subscription
                                </a>
                            </div>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @elseif($trialInfo['has_active_subscription'] && !$trialInfo['is_in_trial'])
                <!-- Active Subscription Banner -->
                <div class="alert alert-success alert-dismissible fade show" role="alert" style="border-radius: 20px; border: none; box-shadow: 0 8px 25px rgba(25, 135, 84, 0.2);">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <i class="fas fa-check-circle fa-2x text-success"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h5 class="alert-heading mb-2">
                                <i class="fas fa-star me-2"></i>Subscription Active
                            </h5>
                            <p class="mb-2">
                                Your subscription is active and all features are available. 
                                @if(Auth::user()->monthlyInvoiceSetting && Auth::user()->monthlyInvoiceSetting->subscription_ends_at)
                                    <strong>Expires: {{ Auth::user()->monthlyInvoiceSetting->subscription_ends_at->format('M d, Y') }}</strong>
                                @endif
                            </p>
                            
                            @if(config('app.debug'))
                                <div class="alert alert-warning mt-2 p-2 small">
                                    <strong>DEBUG:</strong> has_active_subscription=true, is_in_trial=false, sub_ends={{ Auth::user()->monthlyInvoiceSetting ? Auth::user()->monthlyInvoiceSetting->subscription_ends_at : 'null' }}
                                </div>
                            @endif
                            
                            <div class="d-flex gap-2">
                                <a href="{{ route('subscription.manage') }}" class="btn btn-success btn-sm">
                                    <i class="fas fa-cog me-1"></i>Manage Subscription
                                </a>
                                <a href="{{ route('invoices.index') }}" class="btn btn-outline-success btn-sm">
                                    <i class="fas fa-file-invoice me-1"></i>View Invoices
                                </a>
                            </div>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @elseif($trialInfo['trial_status'] === 'expired' && Auth::user()->isRestricted())
                <!-- Restriction Warning -->
                <div class="alert alert-danger alert-dismissible fade show" role="alert" style="border-radius: 20px; border: none; box-shadow: 0 8px 25px rgba(220, 53, 69, 0.2);">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <i class="fas fa-ban fa-2x text-danger"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h5 class="alert-heading mb-2">
                                <i class="fas fa-exclamation-triangle me-2"></i>Free Trial Expired - Account Restricted
                            </h5>
                            <p class="mb-2">Your free trial has ended. {{ Auth::user()->getRestrictionMessage() }}</p>
                            <div class="d-flex gap-2">
                                <a href="{{ route('subscription.pricing') }}" class="btn btn-danger btn-sm">
                                    <i class="fas fa-credit-card me-1"></i> Pay Outstanding Invoices
                                </a>
                                <a href="{{ route('access.restricted') }}" class="btn btn-outline-danger btn-sm">
                                    <i class="fas fa-info-circle me-1"></i> View Details
                                </a>
                            </div>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @elseif(Auth::user()->isInGracePeriod())
                <!-- Grace Period Warning -->
                <div class="alert alert-warning alert-dismissible fade show" role="alert" style="border-radius: 20px; border: none; box-shadow: 0 8px 25px rgba(255, 193, 7, 0.2);">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <i class="fas fa-clock fa-2x text-warning"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h5 class="alert-heading mb-2">
                                <i class="fas fa-exclamation-triangle me-2"></i>Subscription Expired - Grace Period
                            </h5>
                            <p class="mb-2">
                                <strong>Your subscription expired on {{ Auth::user()->getSubscriptionEndDate() ? Auth::user()->getSubscriptionEndDate()->format('M d, Y') : 'Unknown Date' }}</strong>
                                <br>
                                You have <strong>{{ Auth::user()->getDaysRemainingInCurrentPeriod() }} days remaining</strong> in your grace period
                            </p>
                            <div class="d-flex gap-2">
                                <a href="{{ route('subscription.manage') }}" class="btn btn-warning btn-sm">
                                    <i class="fas fa-credit-card me-1"></i> Renew Subscription
                                </a>
                                <a href="{{ route('invoices.index') }}" class="btn btn-outline-warning btn-sm">
                                    <i class="fas fa-file-invoice-dollar me-1"></i> View Invoices
                                </a>
                            </div>
                        </div>
                    </div>
                    <!-- Note: No close button - notification persists until payment -->
                </div>
            @elseif(Auth::user()->isInWarningPeriod())
                <!-- Warning Period Alert -->
                <div class="alert alert-danger alert-dismissible fade show" role="alert" style="border-radius: 20px; border: none; box-shadow: 0 8px 25px rgba(220, 53, 69, 0.2);">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <i class="fas fa-exclamation-triangle fa-2x text-danger"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h5 class="alert-heading mb-2">
                                <i class="fas fa-exclamation-triangle me-2"></i>Final Warning - Account Will Be Restricted Soon
                            </h5>
                            <p class="mb-2">
                                <strong>Your subscription expired on {{ Auth::user()->getSubscriptionEndDate() ? Auth::user()->getSubscriptionEndDate()->format('M d, Y') : 'Unknown Date' }}</strong>
                                <br>
                                You have <strong>{{ Auth::user()->getDaysRemainingInCurrentPeriod() }} days remaining</strong> before your account is restricted
                            </p>
                            <div class="d-flex gap-2">
                                <a href="{{ route('subscription.manage') }}" class="btn btn-danger btn-sm">
                                    <i class="fas fa-credit-card me-1"></i> Renew Now
                                </a>
                                <a href="{{ route('invoices.index') }}" class="btn btn-outline-danger btn-sm">
                                    <i class="fas fa-file-invoice-dollar me-1"></i> Pay Invoices
                                </a>
                            </div>
                        </div>
                    </div>
                    <!-- Note: No close button - notification persists until payment -->
                </div>
            @elseif(Auth::user()->getOverdueInvoicesCount() > 0)
                <!-- Overdue Warning -->
                <div class="alert alert-warning alert-dismissible fade show" role="alert" style="border-radius: 20px; border: none; box-shadow: 0 8px 25px rgba(255, 193, 7, 0.2);">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <i class="fas fa-exclamation-triangle fa-2x text-warning"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h5 class="alert-heading mb-2">
                                <i class="fas fa-clock me-2"></i>Overdue Invoices
                            </h5>
                            <p class="mb-2">You have {{ Auth::user()->getOverdueInvoicesCount() }} overdue invoice(s). Please pay them to avoid service interruption.</p>
                            <a href="{{ route('invoices.index') }}" class="btn btn-warning btn-sm">
                                <i class="fas fa-file-invoice-dollar me-1"></i> View Invoices
                            </a>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @elseif(Auth::user()->getTotalUnpaidMonthlyAmount() > 0)
                <!-- Monthly Invoice Reminder -->
                <div class="alert alert-info alert-dismissible fade show" role="alert" style="border-radius: 20px; border: none; box-shadow: 0 8px 25px rgba(13, 202, 240, 0.2);">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <i class="fas fa-calendar-alt fa-2x text-info"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h5 class="alert-heading mb-2">
                                <i class="fas fa-info-circle me-2"></i>Monthly Service Fee Due
                            </h5>
                            <p class="mb-2">You have ${{ number_format(Auth::user()->getTotalUnpaidMonthlyAmount(), 2) }} in unpaid monthly service fees.</p>
                            <a href="{{ route('invoices.index') }}" class="btn btn-info btn-sm">
                                <i class="fas fa-credit-card me-1"></i> Pay Now
                            </a>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif
        @endauth

        <!-- Quick Actions Card -->
        <div class="chart-card">
            <h4><i class="fas fa-bolt me-2"></i>Quick Actions</h4>
            <div class="d-flex flex-wrap gap-2 mt-3">
                @if(auth()->user()->canAccessRoute('ai.ask-ai'))
                    <a href="{{ route('ai.ask-ai') }}" class="btn-custom-primary">
                        <i class="fas fa-user-plus me-2"></i> Add New Patient
                    </a>
                @endif
                
                @if(auth()->user()->canAccessRoute('diagnosis'))
                    <a href="{{ route('diagnosis.create') }}" class="btn-custom-primary">
                        <i class="fas fa-file-medical me-2"></i> Create Diagnosis
                    </a>
                @endif
                
                @if(auth()->user()->canAccessRoute('cases'))
                    <a href="{{ route('cases') }}" class="btn-custom-secondary">
                        <i class="fas fa-list me-2"></i> View All Cases
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

        <!-- Statistics Section -->
        <div class="row mb-4 mb-md-5">
            <div class="col-md-3 mb-4">
                <div class="stats-card">
                    <div class="stats-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <p class="stats-number">
                        @php
                            // Count distinct patients from combined records
                            $patientKeys = [];
                            foreach ($records as $record) {
                                if (isset($record->patient_key) && $record->patient_key) {
                                    $patientKeys[$record->patient_key] = true;
                                } elseif (isset($record->patient_id)) {
                                    $patientKeys['diagnosis_' . $record->patient_id] = true;
                                }
                            }
                            echo count($patientKeys);
                        @endphp
                    </p>
                    <p class="stats-label">Total Patients</p>
                </div>
            </div>

            <div class="col-md-3 mb-4">
                <div class="stats-card">
                    <div class="stats-icon">
                        <i class="fas fa-calendar-days"></i>
                    </div>
                    <p class="stats-number">
                        @if(count($records) > 0)
                            {{ $records->first()->created_at->format('M d') }}
                        @else
                            N/A
                        @endif
                    </p>
                    <p class="stats-label">Latest Case</p>
                </div>
            </div>

            <div class="col-md-3 mb-4">
                <div class="stats-card">
                    <div class="stats-icon">
                        <i class="fas fa-venus-mars"></i>
                    </div>
                    <p class="stats-number">
                        @php
                            // Calculate male percentage based on distinct patients
                            $uniquePatients = [];
                            $maleCount = 0;
                            foreach ($records as $record) {
                                $key = isset($record->patient_key) && $record->patient_key ? $record->patient_key : ('diagnosis_' . ($record->patient_id ?? 'unknown'));
                                if (!isset($uniquePatients[$key])) {
                                    $uniquePatients[$key] = $record;
                                    if (($record->gender ?? null) === 'male') {
                                        $maleCount++;
                                    }
                                }
                            }
                            $totalUniquePatients = count($uniquePatients);
                            $ratio = $totalUniquePatients > 0 ? round(($maleCount / $totalUniquePatients) * 100) : 0;
                        @endphp
                        {{ $ratio }}%
                    </p>
                    <p class="stats-label">Male Patients</p>
                </div>
            </div>

            <div class="col-md-3 mb-4">
                <div class="stats-card">
                    <div class="stats-icon">
                        <i class="fas fa-user-doctor"></i>
                    </div>
                    <p class="stats-number">
                        @php
                            // Calculate average age based on distinct patients
                            $uniquePatients = [];
                            $ages = [];
                            foreach ($records as $record) {
                                $key = isset($record->patient_key) && $record->patient_key ? $record->patient_key : ('diagnosis_' . ($record->patient_id ?? 'unknown'));
                                if (!isset($uniquePatients[$key]) && isset($record->age) && $record->age) {
                                    $uniquePatients[$key] = true;
                                    $ages[] = $record->age;
                                }
                            }
                            $avgAge = count($ages) > 0 ? round(array_sum($ages) / count($ages)) : 0;
                        @endphp
                        {{ $avgAge }}
                    </p>
                    <p class="stats-label">Avg. Patient Age</p>
                </div>
            </div>
        </div>

        @if($doctorData)
        <!-- Doctor-Specific Dashboard Sections -->
        <div class="row mb-5">
            <div class="col-12">
                <div class="dashboard-header" style="background: linear-gradient(135deg, #DE6262 0%, #c55252 100%);">
                    <h3 style="margin: 0; color: white; font-size: 1.8rem;">
                        <i class="fas fa-stethoscope me-2"></i>
                        Dashboard
                    </h3>
                    <p style="margin: 0.5rem 0 0 0; opacity: 0.9; color: white;">
                        Manage your account
                    </p>
                </div>
            </div>
        </div>

        <!-- Doctor Statistics Cards -->
        <div class="row mb-5">
            <div class="col-md-3 mb-4">
                <div class="stats-card">
                    <div class="stats-icon" style="background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);">
                        <i class="fas fa-calendar-day"></i>
                    </div>
                    <p class="stats-number">{{ $doctorData['stats']['today_appointments'] }}</p>
                    <p class="stats-label">Today's Appointments</p>
                </div>
            </div>

            <div class="col-md-3 mb-4">
                <div class="stats-card">
                    <div class="stats-icon" style="background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%);">
                        <i class="fas fa-clock"></i>
                    </div>
                    <p class="stats-number">{{ $doctorData['stats']['pending_appointments'] }}</p>
                    <p class="stats-label">Pending Approval</p>
                </div>
            </div>

            <div class="col-md-3 mb-4">
                <div class="stats-card">
                    <div class="stats-icon" style="background: linear-gradient(135deg, #27ae60 0%, #229954 100%);">
                        <i class="fas fa-star"></i>
                    </div>
                    <p class="stats-number">{{ number_format($doctorData['stats']['average_rating'], 1) }}</p>
                    <p class="stats-label">Average Rating</p>
                </div>
            </div>

            <div class="col-md-3 mb-4">
                <div class="stats-card">
                    <div class="stats-icon" style="background: linear-gradient(135deg, #8e44ad 0%, #7d3c98 100%);">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <p class="stats-number">${{ number_format($doctorData['stats']['revenue_this_month'], 0) }}</p>
                    <p class="stats-label">This Month Revenue</p>
                </div>
            </div>
        </div>

        <!-- Diagnosis Statistics Cards -->
        <div class="row mb-5">
            <div class="col-md-3 mb-4">
                <div class="stats-card">
                    <div class="stats-icon" style="background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);">
                        <i class="fas fa-file-medical"></i>
                    </div>
                    <p class="stats-number">{{ auth()->user()->doctorDiagnoses()->count() }}</p>
                    <p class="stats-label">Total Diagnoses</p>
                </div>
            </div>

            <div class="col-md-3 mb-4">
                <div class="stats-card">
                    <div class="stats-icon" style="background: linear-gradient(135deg, #16a085 0%, #138d75 100%);">
                        <i class="fas fa-calendar-day"></i>
                    </div>
                    <p class="stats-number">{{ auth()->user()->doctorDiagnoses()->whereDate('created_at', today())->count() }}</p>
                    <p class="stats-label">Today's Diagnoses</p>
                </div>
            </div>

            <div class="col-md-3 mb-4">
                <div class="stats-card">
                    <div class="stats-icon" style="background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%);">
                        <i class="fas fa-comments"></i>
                    </div>
                    <p class="stats-number">{{ auth()->user()->doctorDiagnoses()->withCount('followUps')->get()->sum('follow_ups_count') }}</p>
                    <p class="stats-label">Follow-up Questions</p>
                </div>
            </div>

            <div class="col-md-3 mb-4">
                <div class="stats-card">
                    <div class="stats-icon" style="background: linear-gradient(135deg, #9b59b6 0%, #8e44ad 100%);">
                        <i class="fas fa-star"></i>
                    </div>
                    <p class="stats-number">
                        @php
                            // Use existing review system instead of diagnosis-specific ratings
                            $doctorReviews = auth()->user()->doctor ? auth()->user()->doctor->reviews() : collect();
                            $avgRating = $doctorReviews->avg('rating');
                        @endphp
                        {{ $avgRating ? number_format($avgRating, 1) : 'N/A' }}
                    </p>
                    <p class="stats-label">Doctor Rating</p>
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
        <!-- Cases Over Time Chart -->
        <div class="row mb-5">
            <div class="col-lg-8 mb-4">
                <div class="chart-card">
                    <h6 class="chart-title">Cases Over Time</h6>
                    <div id="casesChart" style="height: 300px;"></div>
                </div>
            </div>
            <div class="col-lg-4 mb-4">
                <div class="stats-card">
                    <div class="stats-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <p class="stats-number">{{ $weeklyCount }}</p>
                    <p class="stats-label">Cases This Week</p>
                </div>
            </div>
        </div>

        <!-- Advanced Statistics & Filters -->
        <div class="chart-card mb-5">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="chart-title mb-0">Advanced Statistics</h6>
                <div class="filter-controls">
                    <button class="btn btn-sm btn-outline-secondary me-2" id="refresh-stats">
                        <i class="fas fa-sync-alt me-1"></i> Refresh
                    </button>

                </div>
            </div>

            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="card filter-card">
                        <div class="card-body">
                            <h6 class="mb-3"><i class="fas fa-filter me-2"></i>Filter Data</h6>
                            <form id="stats-filter-form" class="row g-3">
                                <div class="col-md-3">
                                    <label class="form-label">Date Range</label>
                                    <select class="form-select" id="date-range-select">
                                        <option value="7">Last 7 days</option>
                                        <option value="30" selected>Last 30 days</option>
                                        <option value="90">Last 3 months</option>
                                        <option value="180">Last 6 months</option>
                                        <option value="365">Last year</option>
                                        <option value="custom">Custom range</option>
                                    </select>
                                </div>
                                <div class="col-md-3 custom-date-range" style="display: none;">
                                    <label class="form-label">From</label>
                                    <input type="date" class="form-control" id="date-from">
                                </div>
                                <div class="col-md-3 custom-date-range" style="display: none;">
                                    <label class="form-label">To</label>
                                    <input type="date" class="form-control" id="date-to">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Gender</label>
                                    <select class="form-select" id="gender-filter">
                                        <option value="all" selected>All</option>
                                        <option value="male">Male</option>
                                        <option value="female">Female</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Age Group</label>
                                    <select class="form-select" id="age-filter">
                                        <option value="all" selected>All</option>
                                        <option value="0-18">0-18</option>
                                        <option value="19-35">19-35</option>
                                        <option value="36-50">36-50</option>
                                        <option value="51-65">51-65</option>
                                        <option value="66+">66+</option>
                                    </select>
                                </div>
                                <div class="col-md-12 text-end">
                                    <button type="submit" class="btn btn-primary-custom btn-sm">
                                        <i class="fas fa-search me-1"></i> Apply Filters
                                    </button>
                                    <button type="reset" class="btn btn-secondary-custom btn-sm">
                                        <i class="fas fa-undo me-1"></i> Reset
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="stats-card">
                        <h6 class="mb-3"><i class="fas fa-chart-pie me-2"></i>Patient Demographics</h6>
                        <div id="demographicsChart" style="height: 250px;"></div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="stats-card">
                        <h6 class="mb-3"><i class="fas fa-chart-bar me-2"></i>Age Distribution</h6>
                        <div id="ageDistributionChart" style="height: 250px;"></div>
                    </div>
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="stats-card">
                        <h6 class="mb-3"><i class="fas fa-calendar-days me-2"></i>Patient Visits Over Time</h6>
                        <div id="visitsTimelineChart" style="height: 250px;"></div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4">
                    <div class="stats-card text-center">
                        <div class="stats-icon mx-auto">
                            <i class="fas fa-user-plus"></i>
                        </div>
                        <h3 class="stats-number" id="new-patients-count">{{ $records->where('created_at', '>=', now()->subDays(30))->groupBy('patient_key')->count() }}</h3>
                        <p class="stats-label">New Patients (30 days)</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stats-card text-center">
                        <div class="stats-icon mx-auto">
                            <i class="fas fa-redo"></i>
                        </div>
                        <h3 class="stats-number" id="return-visits-count">
                            @php
                                $returnVisits = $records->where('created_at', '>=', now()->subDays(30))->count() - $records->where('created_at', '>=', now()->subDays(30))->groupBy('patient_key')->count();
                                echo $returnVisits > 0 ? $returnVisits : 0;
                            @endphp
                        </h3>
                        <p class="stats-label">Return Visits (30 days)</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stats-card text-center">
                        <div class="stats-icon mx-auto">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <h3 class="stats-number" id="growth-rate">
                            @php
                                $currentMonth = $records->where('created_at', '>=', now()->startOfMonth())->count();
                                $lastMonth = $records->where('created_at', '>=', now()->subMonth()->startOfMonth())
                                    ->where('created_at', '<', now()->startOfMonth())->count();
                                $growthRate = $lastMonth > 0 ? round((($currentMonth - $lastMonth) / $lastMonth) * 100) : 0;
                                echo $growthRate > 0 ? '+'.$growthRate : $growthRate;
                            @endphp%
                        </h3>
                        <p class="stats-label">Monthly Growth Rate</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Consolidated Patient List with Advanced Features -->
        <div class="table-card mb-5">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="table-title mb-0">Patient List</h6>
                <div>
                    <div class="input-group input-group-sm me-2 d-inline-flex" style="width: 200px;">
                        <input type="text" class="form-control" id="patient-search" placeholder="Search patients...">
                        <button class="btn btn-outline-secondary" type="button" id="search-btn">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                    <a href="{{ route('cases') }}" class="btn-secondary-custom btn-sm">
                        <i class="fas fa-external-link-alt me-1"></i> View All
                    </a>
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="card filter-card">
                        <div class="card-body">
                            <h6 class="mb-3"><i class="fas fa-filter me-2"></i>Filter Patients</h6>
                            <form id="patient-filter-form" class="row g-3">
                                <div class="col-md-3">
                                    <label class="form-label">Date Range</label>
                                    <select class="form-select" id="patient-date-range">
                                        <option value="all" selected>All Time</option>
                                        <option value="7">Last 7 days</option>
                                        <option value="30">Last 30 days</option>
                                        <option value="90">Last 3 months</option>
                                        <option value="custom">Custom range</option>
                                    </select>
                                </div>
                                <div class="col-md-3 patient-custom-date" style="display: none;">
                                    <label class="form-label">From</label>
                                    <input type="date" class="form-control" id="patient-date-from">
                                </div>
                                <div class="col-md-3 patient-custom-date" style="display: none;">
                                    <label class="form-label">To</label>
                                    <input type="date" class="form-control" id="patient-date-to">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Gender</label>
                                    <select class="form-select" id="patient-gender-filter">
                                        <option value="all" selected>All</option>
                                        <option value="male">Male</option>
                                        <option value="female">Female</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Age Group</label>
                                    <select class="form-select" id="patient-age-filter">
                                        <option value="all" selected>All</option>
                                        <option value="0-18">0-18</option>
                                        <option value="19-35">19-35</option>
                                        <option value="36-50">36-50</option>
                                        <option value="51-65">51-65</option>
                                        <option value="66+">66+</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Visit Count</label>
                                    <select class="form-select" id="patient-visit-filter">
                                        <option value="all" selected>All</option>
                                        <option value="1">Single Visit</option>
                                        <option value="multiple">Multiple Visits</option>
                                    </select>
                                </div>
                                <div class="col-md-12 text-end">
                                    <button type="submit" class="btn btn-primary-custom btn-sm">
                                        <i class="fas fa-search me-1"></i> Apply Filters
                                    </button>
                                    <button type="reset" class="btn btn-secondary-custom btn-sm">
                                        <i class="fas fa-undo me-1"></i> Reset
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
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
                                <a href="{{ route('ai.ask-ai') }}" id="new-visit-btn" class="btn btn-primary-custom">
                                    <i class="fas fa-plus me-1"></i> New Visit
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            @else
                <div class="empty-state">
                    <i class="fas fa-user-doctor"></i>
                    <h5>No patients yet</h5>
                    <p>Start by adding your first patient</p>
                    <a href="{{ route('ai.ask-ai') }}" class="btn-primary-custom mt-3">
                        <i class="fas fa-plus me-2"></i> Add First Patient
                    </a>
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

