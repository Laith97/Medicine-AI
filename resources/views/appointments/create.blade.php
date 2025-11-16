@extends('master')

@section('title', 'Book Appointment with ' . $doctor->user->name)

@push('styles')
<link rel="stylesheet" href="{{ asset('demos/medical/medical.css') }}">
<style>
.booking-hero {
    background: linear-gradient(135deg, #DE6262 0%, #DE6280 100%);
    padding: 2rem 0;
    margin-bottom: 2rem;
}

.booking-card {
    background: white;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    border: none;
    overflow: hidden;
}

.section-card {
    background: white;
    border-radius: 12px;
    padding: 2rem;
    box-shadow: 0 2px 12px rgba(0,0,0,0.06);
    border: 1px solid #f0f0f0;
    margin-bottom: 1.5rem;
}

.booking-option {
    border: 2px solid #e9ecef;
    border-radius: 12px;
    padding: 1.5rem;
    cursor: pointer;
    transition: all 0.3s ease;
    background: white;
}

.booking-option:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(222, 98, 98, 0.15);
}

.booking-option.selected {
    border-color: #DE6262;
    background: linear-gradient(135deg, rgba(222, 98, 98, 0.05) 0%, rgba(222, 98, 128, 0.05) 100%);
}

.date-option {
    border: 1px solid #e9ecef;
    border-radius: 10px;
    padding: 1rem;
    cursor: pointer;
    transition: all 0.3s ease;
    background: white;
}

.date-option:hover {
    border-color: #DE6262;
    background: rgba(222, 98, 98, 0.05);
}

.date-option.selected {
    border-color: #DE6262;
    background: linear-gradient(135deg, rgba(222, 98, 98, 0.1) 0%, rgba(222, 98, 128, 0.1) 100%);
}

.time-slot {
    background: white;
    border: 2px solid #e9ecef;
    border-radius: 8px;
    padding: 0.75rem 1rem;
    cursor: pointer;
    transition: all 0.3s ease;
    font-weight: 500;
    color: #495057;
}

.time-slot:hover {
    border-color: #DE6262;
    background: rgba(222, 98, 98, 0.05);
    color: #DE6262;
}

.time-slot.selected {
    background: linear-gradient(135deg, #DE6262 0%, #DE6280 100%);
    border-color: #DE6262;
    color: white;
}

.btn-book {
    background: linear-gradient(135deg, #DE6262 0%, #DE6280 100%);
    border: none;
    border-radius: 12px;
    padding: 1rem 2rem;
    font-weight: 600;
    letter-spacing: 0.5px;
    transition: all 0.3s ease;
    color: white;
}

.btn-book:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(222, 98, 98, 0.4);
    color: white;
}

.btn-book:disabled {
    background: #6c757d;
    transform: none;
    box-shadow: none;
}

.form-control:focus, .form-select:focus {
    border-color: #DE6262;
    box-shadow: 0 0 0 0.2rem rgba(222, 98, 98, 0.25);
}

.form-check-input:checked {
    background-color: #DE6262;
    border-color: #DE6262;
}

.section-title {
    font-size: 1.25rem;
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 1.5rem;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid #DE6262;
    display: inline-block;
}

.summary-card {
    background: linear-gradient(135deg, rgba(222, 98, 98, 0.05) 0%, rgba(222, 98, 128, 0.05) 100%);
    border: 1px solid rgba(222, 98, 98, 0.2);
    border-radius: 12px;
    padding: 1.5rem;
}

.info-item {
    padding: 0.75rem 0;
    border-bottom: 1px solid rgba(222, 98, 98, 0.1);
}

.info-item:last-child {
    border-bottom: none;
}

.appointment-type-card {
    border: 2px solid #e9ecef;
    border-radius: 10px;
    padding: 1rem;
    transition: all 0.3s ease;
    cursor: pointer;
}

.appointment-type-card:hover {
    border-color: #DE6262;
    background: rgba(222, 98, 98, 0.05);
}

.appointment-type-card.selected {
    border-color: #DE6262;
    background: linear-gradient(135deg, rgba(222, 98, 98, 0.1) 0%, rgba(222, 98, 128, 0.1) 100%);
}

.icon-circle {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 1rem;
}

.progress-indicator {
    display: flex;
    justify-content: center;
    align-items: center;
    margin-bottom: 2rem;
}

.progress-step {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    color: #6c757d;
    background: #e9ecef;
    margin: 0 1rem;
    position: relative;
}

.progress-step.active {
    background: linear-gradient(135deg, #DE6262 0%, #DE6280 100%);
    color: white;
}

.progress-step.completed {
    background: #28a745;
    color: white;
}

.progress-step::after {
    content: '';
    position: absolute;
    right: -2rem;
    width: 2rem;
    height: 2px;
    background: #e9ecef;
    top: 50%;
    transform: translateY(-50%);
}

.progress-step:last-child::after {
    display: none;
}

.progress-step.completed::after {
    background: #28a745;
}

.doctor-avatar {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    border: 3px solid white;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}
</style>
@endpush

@section('content')
<div class="container-fluid">
    <!-- Back Navigation -->
    <div class="container mb-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="{{ route('doctors.show', $doctor) }}" class="text-decoration-none">
                        <i class="fas fa-arrow-left me-2"></i>Dr. {{ $doctor->user->name }}
                    </a>
                </li>
                <li class="breadcrumb-item active">Book Appointment</li>
            </ol>
        </nav>
    </div>

    <!-- Booking Hero Section -->
    <div class="booking-hero">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <div class="d-flex align-items-center text-white">
                        <div class="me-4">
                            @if($doctor->profile_image)
                                <img src="{{ asset('storage/' . $doctor->profile_image) }}"
                                     alt="{{ $doctor->user->name }}"
                                     class="doctor-avatar"
                                     style="object-fit: cover;">
                            @else
                                <div class="doctor-avatar bg-white d-flex align-items-center justify-content-center">
                                    <i class="fas fa-user-md text-primary fs-4"></i>
                                </div>
                            @endif
                        </div>
                        <div>
                            <h1 class="mb-2">Book Appointment</h1>
                            <h2 class="h4 mb-1 text-white-75">with Dr. {{ $doctor->user->name }}</h2>
                            <p class="mb-0 text-white-50">{{ $doctor->specialty->name }}</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 text-end">
                    <div class="text-white">
                        <div class="h3 mb-1">${{ number_format($doctor->consultation_fee / 100, 2) }}</div>
                        <div class="text-white-75">Consultation Fee</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Progress Indicator -->
        <div class="progress-indicator">
            <div class="progress-step active" id="step1">1</div>
            <div class="progress-step" id="step2">2</div>
            <div class="progress-step" id="step3">3</div>
        </div>

        @guest
        <!-- Booking Options -->
        <div class="section-card mb-4">
            <h2 class="section-title">Choose Booking Method</h2>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <div class="booking-option" data-type="guest">
                        <div class="d-flex align-items-start">
                            <div class="icon-circle bg-primary text-white me-3">
                                <i class="fas fa-user-clock"></i>
                            </div>
                            <div>
                                <h3 class="h5 mb-2">Book as Guest</h3>
                                <p class="text-muted mb-0">Quick booking without creating an account. Perfect for one-time visits.</p>
                                <small class="text-success mt-2 d-block">
                                    <i class="fas fa-check-circle me-1"></i>No registration required
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="booking-option" data-type="register">
                        <div class="d-flex align-items-start">
                            <div class="icon-circle bg-success text-white me-3">
                                <i class="fas fa-user-plus"></i>
                            </div>
                            <div>
                                <h3 class="h5 mb-2">Create Account & Book</h3>
                                <p class="text-muted mb-0">Get a personal dashboard to manage all your appointments and health records.</p>
                                <small class="text-success mt-2 d-block">
                                    <i class="fas fa-check-circle me-1"></i>Access to appointment history
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="text-center mt-3">
                <small class="text-muted">Already have an account?
                    <a href="{{ route('login', ['redirect' => request()->fullUrl()]) }}" class="text-decoration-none" style="color: #DE6262;">Sign in here</a>
                </small>
            </div>
        </div>
        @endguest

        <!-- Main Booking Form -->
        <form method="POST" action="{{ route('appointments.store') }}" id="appointmentForm">
            @csrf
            <input type="hidden" name="doctor_id" value="{{ $doctor->id }}">
            @guest
                <input type="hidden" name="booking_type" id="bookingType" value="guest">
            @endguest

            <div class="row">
                <!-- Left Column - Form -->
                <div class="col-lg-8">
                    @guest
                    <!-- Guest Information -->
                    <div class="section-card" id="guestInfo">
                        <h2 class="section-title">Your Information</h2>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="guest_name" class="form-label fw-medium">
                                    Full Name <span class="text-danger">*</span>
                                </label>
                                <input type="text" name="guest_name" id="guest_name" required
                                       class="form-control"
                                       placeholder="Enter your full name" value="{{ old('guest_name') }}">
                                @error('guest_name')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="guest_email" class="form-label fw-medium">
                                    Email Address <span class="text-danger">*</span>
                                </label>
                                <input type="email" name="guest_email" id="guest_email" required
                                       class="form-control"
                                       placeholder="Enter your email" value="{{ old('guest_email') }}">
                                @error('guest_email')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="guest_phone" class="form-label fw-medium">
                                    Phone Number <span class="text-danger">*</span>
                                </label>
                                <input type="tel" name="guest_phone" id="guest_phone" required
                                       class="form-control"
                                       placeholder="Enter your phone number" value="{{ old('guest_phone') }}">
                                @error('guest_phone')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="guest_date_of_birth" class="form-label fw-medium">
                                    Date of Birth <span class="text-danger">*</span>
                                </label>
                                <input type="date" name="guest_date_of_birth" id="guest_date_of_birth" required
                                       class="form-control"
                                       max="{{ date('Y-m-d') }}" value="{{ old('guest_date_of_birth') }}">
                                @error('guest_date_of_birth')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="guest_gender" class="form-label fw-medium">
                                    Gender <span class="text-danger">*</span>
                                </label>
                                <select name="guest_gender" id="guest_gender" required class="form-select">
                                    <option value="">Select gender</option>
                                    <option value="male" {{ old('guest_gender') == 'male' ? 'selected' : '' }}>Male</option>
                                    <option value="female" {{ old('guest_gender') == 'female' ? 'selected' : '' }}>Female</option>
                                    <option value="other" {{ old('guest_gender') == 'other' ? 'selected' : '' }}>Other</option>
                                </select>
                                @error('guest_gender')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="guest_address" class="form-label fw-medium">
                                    Address <span class="text-muted">(Optional)</span>
                                </label>
                                <input type="text" name="guest_address" id="guest_address"
                                       class="form-control"
                                       placeholder="Enter your address" value="{{ old('guest_address') }}">
                                @error('guest_address')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Insurance Information (Optional) -->
                    <div class="section-card" id="insuranceInfo">
                        <h2 class="section-title">Insurance Information <span class="text-muted">(Optional)</span></h2>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            Providing insurance information helps us verify eligibility and may reduce your out-of-pocket costs.
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="insurance_provider" class="form-label fw-medium">
                                    Insurance Provider
                                </label>
                                <select name="insurance_provider_id" id="insurance_provider" class="form-select">
                                    <option value="">Select insurance provider</option>
                                    @foreach(\App\Models\InsuranceProvider::all() as $provider)
                                        <option value="{{ $provider->id }}" {{ old('insurance_provider_id') == $provider->id ? 'selected' : '' }}>
                                            {{ $provider->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('insurance_provider_id')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="policy_number" class="form-label fw-medium">
                                    Policy/Member ID
                                </label>
                                <input type="text" name="policy_number" id="policy_number"
                                       class="form-control"
                                       placeholder="Enter policy or member ID" value="{{ old('policy_number') }}">
                                @error('policy_number')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="group_number" class="form-label fw-medium">
                                    Group Number <span class="text-muted">(Optional)</span>
                                </label>
                                <input type="text" name="group_number" id="group_number"
                                       class="form-control"
                                       placeholder="Enter group number" value="{{ old('group_number') }}">
                                @error('group_number')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="subscriber_id" class="form-label fw-medium">
                                    Subscriber ID <span class="text-muted">(Optional)</span>
                                </label>
                                <input type="text" name="subscriber_id" id="subscriber_id"
                                       class="form-control"
                                       placeholder="Enter subscriber ID" value="{{ old('subscriber_id') }}">
                                @error('subscriber_id')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="relationship_to_subscriber" class="form-label fw-medium">
                                    Relationship to Subscriber <span class="text-muted">(Optional)</span>
                                </label>
                                <select name="relationship_to_subscriber" id="relationship_to_subscriber" class="form-select">
                                    <option value="">Select relationship</option>
                                    <option value="self" {{ old('relationship_to_subscriber') == 'self' ? 'selected' : '' }}>Self</option>
                                    <option value="spouse" {{ old('relationship_to_subscriber') == 'spouse' ? 'selected' : '' }}>Spouse</option>
                                    <option value="child" {{ old('relationship_to_subscriber') == 'child' ? 'selected' : '' }}>Child</option>
                                    <option value="parent" {{ old('relationship_to_subscriber') == 'parent' ? 'selected' : '' }}>Parent</option>
                                    <option value="other" {{ old('relationship_to_subscriber') == 'other' ? 'selected' : '' }}>Other</option>
                                </select>
                                @error('relationship_to_subscriber')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="effective_date" class="form-label fw-medium">
                                    Effective Date <span class="text-muted">(Optional)</span>
                                </label>
                                <input type="date" name="effective_date" id="effective_date"
                                       class="form-control" value="{{ old('effective_date') }}">
                                @error('effective_date')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Registration Form (Hidden by default) -->
                    <div class="section-card" id="registrationInfo" style="display: none;">
                        <h2 class="section-title">Create Your Account</h2>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="reg_name" class="form-label fw-medium">
                                    Full Name <span class="text-danger">*</span>
                                </label>
                                <input type="text" name="reg_name" id="reg_name"
                                       class="form-control"
                                       placeholder="Enter your full name">
                            </div>
                            <div class="col-md-6">
                                <label for="reg_email" class="form-label fw-medium">
                                    Email Address <span class="text-danger">*</span>
                                </label>
                                <input type="email" name="reg_email" id="reg_email"
                                       class="form-control"
                                       placeholder="Enter your email">
                            </div>
                            <div class="col-md-6">
                                <label for="reg_password" class="form-label fw-medium">
                                    Password <span class="text-danger">*</span>
                                </label>
                                <input type="password" name="reg_password" id="reg_password"
                                       class="form-control"
                                       placeholder="Create a strong password">
                            </div>
                            <div class="col-md-6">
                                <label for="reg_password_confirmation" class="form-label fw-medium">
                                    Confirm Password <span class="text-danger">*</span>
                                </label>
                                <input type="password" name="reg_password_confirmation" id="reg_password_confirmation"
                                       class="form-control"
                                       placeholder="Confirm your password">
                            </div>
                        </div>
                    </div>
                    @endguest

                    <!-- Date & Time Selection -->
                    <div class="section-card">
                        <h2 class="section-title">Select Date & Time</h2>

                        <!-- Date Selection -->
                        <div class="mb-4">
                            <label class="form-label fw-medium mb-3">Choose Available Date</label>
                            @forelse($availableSlots as $date => $slots)
                                <div class="date-option mb-2" data-date="{{ $date }}">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <div class="fw-semibold">
                                                {{ \Carbon\Carbon::parse($date)->format('M j, Y') }}
                                            </div>
                                            <div class="small text-muted">
                                                {{ \Carbon\Carbon::parse($date)->format('l') }}
                                            </div>
                                        </div>
                                        <div class="text-end">
                                            <span class="badge bg-success">{{ $slots->count() }} slots</span>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="text-center py-5">
                                    <i class="fas fa-calendar-times display-4 text-muted mb-3"></i>
                                    <h3 class="h5 text-muted">No Available Slots</h3>
                                    <p class="text-muted">Please contact the doctor's office directly to schedule an appointment.</p>
                                </div>
                            @endforelse
                        </div>

                        <!-- Time Selection -->
                        <div id="timeSelection" style="display: none;">
                            <label class="form-label fw-medium mb-3">Choose Time Slot</label>
                            <div class="row g-2" id="timeSlots">
                                <!-- Time slots populated by JavaScript -->
                            </div>
                            <input type="hidden" name="appointment_date" id="selectedDateTime">
                        </div>
                    </div>

                    <!-- Appointment Type -->
                    <div class="section-card">
                        <h2 class="section-title">Appointment Type</h2>
                        <div class="row g-3">
                            @php
                                $appointmentTypes = [
                                    'in_person' => [
                                        'icon' => 'fas fa-hospital',
                                        'color' => 'text-primary',
                                        'title' => 'In-Person Visit',
                                        'description' => 'Visit the clinic'
                                    ],
                                    'video_call' => [
                                        'icon' => 'fas fa-video',
                                        'color' => 'text-success',
                                        'title' => 'Video Call',
                                        'description' => 'Online consultation'
                                    ],
                                    'phone_call' => [
                                        'icon' => 'fas fa-phone',
                                        'color' => 'text-info',
                                        'title' => 'Phone Call',
                                        'description' => 'Voice consultation'
                                    ]
                                ];
                                $enabledTypes = $doctor->getEnabledAppointmentTypes();
                                $firstType = reset($enabledTypes);
                            @endphp

                            @foreach($enabledTypes as $index => $type)
                                <div class="col-md-4">
                                    <div class="appointment-type-card" data-type="{{ $type }}">
                                        <div class="text-center">
                                            <i class="{{ $appointmentTypes[$type]['icon'] }} fs-2 {{ $appointmentTypes[$type]['color'] }} mb-2"></i>
                                            <h3 class="h6 mb-1">{{ $appointmentTypes[$type]['title'] }}</h3>
                                            <small class="text-muted">{{ $appointmentTypes[$type]['description'] }}</small>
                                        </div>
                                        <input type="radio" name="appointment_type" value="{{ $type }}" class="d-none" {{ $type === $firstType ? 'checked' : '' }}>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        @if(count($enabledTypes) === 0)
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                This doctor has not enabled any appointment types yet. Please contact them directly.
                            </div>
                        @endif
                    </div>

                    <!-- Medical Information -->
                    <div class="section-card">
                        <h2 class="section-title">Medical Information</h2>
                        <div class="row g-3">
                            <div class="col-12">
                                <label for="reason" class="form-label fw-medium">
                                    Reason for Visit <span class="text-danger">*</span>
                                </label>
                                <textarea name="reason" id="reason" rows="3" required
                                          class="form-control"
                                          placeholder="Please describe the reason for your visit...">{{ old('reason') }}</textarea>
                                @error('reason')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="symptoms" class="form-label fw-medium">
                                    Current Symptoms <span class="text-muted">(Optional)</span>
                                </label>
                                <textarea name="symptoms" id="symptoms" rows="3"
                                          class="form-control"
                                          placeholder="Describe any symptoms...">{{ old('symptoms') }}</textarea>
                                @error('symptoms')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="patient_notes" class="form-label fw-medium">
                                    Additional Notes <span class="text-muted">(Optional)</span>
                                </label>
                                <textarea name="patient_notes" id="patient_notes" rows="3"
                                          class="form-control"
                                          placeholder="Any additional information...">{{ old('patient_notes') }}</textarea>
                                @error('patient_notes')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column - Summary -->
                <div class="col-lg-4">
                    <div class="position-sticky" style="top: 2rem;">
                        <!-- Appointment Summary -->
                        <div class="section-card">
                            <h2 class="section-title">Appointment Summary</h2>

                            <!-- Doctor Info -->
                            <div class="d-flex align-items-center mb-4 pb-3 border-bottom">
                                @if($doctor->profile_image)
                                    <img src="{{ asset('storage/' . $doctor->profile_image) }}"
                                         alt="{{ $doctor->user->name }}"
                                         class="rounded-circle me-3"
                                         style="width: 50px; height: 50px; object-fit: cover;">
                                @else
                                    <div class="rounded-circle bg-light d-flex align-items-center justify-content-center me-3"
                                         style="width: 50px; height: 50px;">
                                        <i class="fas fa-user-md text-primary"></i>
                                    </div>
                                @endif
                                <div>
                                    <div class="fw-semibold">Dr. {{ $doctor->user->name }}</div>
                                    <small class="text-muted">{{ $doctor->specialty->name }}</small>
                                </div>
                            </div>

                            <!-- Appointment Details -->
                            <div class="summary-card">
                                <div class="info-item d-flex justify-content-between">
                                    <span class="text-muted">Date:</span>
                                    <span class="fw-medium" id="summaryDate">Not selected</span>
                                </div>
                                <div class="info-item d-flex justify-content-between">
                                    <span class="text-muted">Time:</span>
                                    <span class="fw-medium" id="summaryTime">Not selected</span>
                                </div>
                                <div class="info-item d-flex justify-content-between">
                                    <span class="text-muted">Duration:</span>
                                    <span class="fw-medium">{{ $doctor->appointment_duration }} minutes</span>
                                </div>
                                <div class="info-item d-flex justify-content-between">
                                    <span class="text-muted">Type:</span>
                                    <span class="fw-medium" id="summaryType">In-Person Visit</span>
                                </div>
                                <div class="info-item d-flex justify-content-between fs-5">
                                    <span class="fw-semibold">Total:</span>
                                    <span class="fw-bold" style="color: #DE6262;">${{ number_format($doctor->consultation_fee / 100, 2) }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Important Information -->
                        <div class="section-card">
                            <h3 class="h6 fw-semibold mb-3" style="color: #DE6262;">Important Reminders</h3>
                            <div class="small text-muted">
                                <div class="d-flex mb-2">
                                    <i class="fas fa-check-circle me-2 mt-1" style="color: #DE6262;"></i>
                                    <span>Arrive 15 minutes early for in-person visits</span>
                                </div>
                                <div class="d-flex mb-2">
                                    <i class="fas fa-check-circle me-2 mt-1" style="color: #DE6262;"></i>
                                    <span>Bring valid ID and insurance information</span>
                                </div>
                                <div class="d-flex mb-2">
                                    <i class="fas fa-check-circle me-2 mt-1" style="color: #DE6262;"></i>
                                    <span>Confirmation email will be sent shortly</span>
                                </div>
                                @if(!$doctor->auto_approve_appointments)
                                <div class="d-flex">
                                    <i class="fas fa-info-circle me-2 mt-1" style="color: #DE6262;"></i>
                                    <span>Appointment requires doctor's approval</span>
                                </div>
                                @endif
                            </div>
                        </div>

                        <!-- Book Button -->
                        <button type="submit" class="btn btn-book w-100 mb-3" id="submitButton" disabled>
                            <i class="fas fa-calendar-plus me-2"></i>
                            Confirm Booking
                        </button>

                        <p class="small text-muted text-center">
                            By booking, you agree to our
                            <a href="#" class="text-decoration-none" style="color: #DE6262;">terms of service</a>
                            and
                            <a href="#" class="text-decoration-none" style="color: #DE6262;">privacy policy</a>.
                        </p>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const availableSlots = @json($availableSlots);
    const dateOptions = document.querySelectorAll('.date-option');
    const timeSelection = document.getElementById('timeSelection');
    const timeSlots = document.getElementById('timeSlots');
    const selectedDateTimeInput = document.getElementById('selectedDateTime');
    const submitButton = document.getElementById('submitButton');

    // Summary elements
    const summaryDate = document.getElementById('summaryDate');
    const summaryTime = document.getElementById('summaryTime');
    const summaryType = document.getElementById('summaryType');

    let selectedDate = null;
    let selectedTime = null;

    @guest
    // Booking option selection
    const bookingOptions = document.querySelectorAll('.booking-option');
    const guestInfo = document.getElementById('guestInfo');
    const registrationInfo = document.getElementById('registrationInfo');
    const bookingTypeInput = document.getElementById('bookingType');

    bookingOptions.forEach(option => {
        option.addEventListener('click', function() {
            // Remove previous selection
            bookingOptions.forEach(opt => {
                opt.classList.remove('border-primary', 'bg-primary', 'bg-opacity-10', 'border-success', 'bg-success');
            });

            const type = this.dataset.type;
            bookingTypeInput.value = type;

            if (type === 'guest') {
                this.classList.add('border-primary', 'bg-primary', 'bg-opacity-10');
                guestInfo.style.display = 'block';
                registrationInfo.style.display = 'none';

                // Make guest fields required
                document.querySelectorAll('#guestInfo input[required], #guestInfo select[required]').forEach(field => {
                    field.required = true;
                });

                // Make registration fields not required
                document.querySelectorAll('#registrationInfo input').forEach(field => {
                    field.required = false;
                });
            } else {
                this.classList.add('border-success', 'bg-success', 'bg-opacity-10');
                guestInfo.style.display = 'none';
                registrationInfo.style.display = 'block';

                // Make registration fields required
                document.querySelectorAll('#registrationInfo input').forEach(field => {
                    if (field.id !== 'reg_password_confirmation') {
                        field.required = true;
                    }
                });

                // Make guest fields not required
                document.querySelectorAll('#guestInfo input, #guestInfo select').forEach(field => {
                    field.required = false;
                });
            }
        });
    });

    // Set default selection to guest
    if (bookingOptions.length > 0) {
        bookingOptions[0].click();
    }
    @endguest

    // Date selection
    dateOptions.forEach(option => {
        option.addEventListener('click', function() {
            // Remove previous selection
            dateOptions.forEach(opt => opt.classList.remove('bg-primary', 'bg-opacity-10', 'border-primary'));

            // Add selection to current
            this.classList.add('bg-primary', 'bg-opacity-10', 'border-primary');

            selectedDate = this.dataset.date;
            selectedTime = null;

            // Update summary
            const dateObj = new Date(selectedDate);
            summaryDate.textContent = dateObj.toLocaleDateString('en-US', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
            summaryTime.textContent = 'Not selected';

            // Show time selection
            showTimeSlots(selectedDate);
            updateSubmitButton();
        });
    });

    // Appointment type selection
    const appointmentTypeCards = document.querySelectorAll('.appointment-type-card');

    appointmentTypeCards.forEach(card => {
        card.addEventListener('click', function() {
            // Remove previous selection
            appointmentTypeCards.forEach(c => c.classList.remove('selected'));

            // Add selection to current card
            this.classList.add('selected');

            // Check the radio button
            const radio = this.querySelector('input[type="radio"]');
            radio.checked = true;

            // Update summary
            const typeMap = {
                'in_person': 'In-Person Visit',
                'video_call': 'Video Call',
                'phone_call': 'Phone Call'
            };
            summaryType.textContent = typeMap[radio.value];
        });
    });

    // Set default selection (first card)
    if (appointmentTypeCards.length > 0) {
        appointmentTypeCards[0].click();
    }

    function showTimeSlots(date) {
        const slots = availableSlots[date] || [];
        timeSlots.innerHTML = '';

        if (slots.length === 0) {
            timeSlots.innerHTML = '<p class="col-12 text-center text-muted">No available slots</p>';
            timeSelection.style.display = 'block';
            return;
        }

        slots.forEach(slot => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'btn btn-primary-custom col-4 time-slot';
            button.textContent = formatTime(slot.start_time);
            button.dataset.datetime = `${date} ${slot.start_time}`;
            button.dataset.time = slot.start_time;

            button.addEventListener('click', function() {
                // Remove previous selection
                document.querySelectorAll('.time-slot').forEach(btn => {
                    btn.classList.remove('active');
                });

                // Add selection to current
                this.classList.add('active');

                selectedTime = this.dataset.time;
                selectedDateTimeInput.value = this.dataset.datetime;

                // Update summary
                summaryTime.textContent = formatTime(selectedTime);
                updateSubmitButton();
            });

            timeSlots.appendChild(button);
        });

        timeSelection.style.display = 'block';
    }

    function formatTime(time) {
        const [hours, minutes] = time.split(':');
        const date = new Date();
        date.setHours(parseInt(hours), parseInt(minutes));
        return date.toLocaleTimeString('en-US', {
            hour: 'numeric',
            minute: '2-digit',
            hour12: true
        });
    }

    function updateSubmitButton() {
        if (selectedDate && selectedTime) {
            submitButton.disabled = false;
        } else {
            submitButton.disabled = true;
        }
    }
});
</script>

<style>
.booking-option {
    transition: all 0.3s ease;
    cursor: pointer;
}

.booking-option:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.time-slot {
    transition: all 0.2s ease;
}

.date-option {
    transition: all 0.2s ease;
    cursor: pointer;
}

.date-option:hover {
    background-color: var(--bs-primary-bg-subtle);
    border-color: var(--bs-primary);
}
</style>
@endsection
