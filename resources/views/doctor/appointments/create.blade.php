@extends('master')

@section('title', 'Book Appointment')

@push('styles')
<link rel="stylesheet" href="{{ asset('demos/medical/medical.css') }}">
<style>
.booking-hero {
    background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
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

.patient-option {
    border: 2px solid #e9ecef;
    border-radius: 12px;
    padding: 1.5rem;
    cursor: pointer;
    transition: all 0.3s ease;
    background: white;
}

.patient-option:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(44, 62, 80, 0.15);
}

.patient-option.selected {
    border-color: #2c3e50;
    background: linear-gradient(135deg, rgba(44, 62, 80, 0.05) 0%, rgba(52, 73, 94, 0.05) 100%);
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
    border-color: #2c3e50;
    background: rgba(44, 62, 80, 0.05);
}

.date-option.selected {
    border-color: #2c3e50;
    background: linear-gradient(135deg, rgba(44, 62, 80, 0.1) 0%, rgba(52, 73, 94, 0.1) 100%);
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
    border-color: #2c3e50;
    background: rgba(44, 62, 80, 0.05);
    color: #2c3e50;
}

.time-slot.selected {
    background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
    border-color: #2c3e50;
    color: white;
}

.btn-book {
    background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
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
    box-shadow: 0 8px 25px rgba(44, 62, 80, 0.4);
    color: white;
}

.btn-book:disabled {
    background: #6c757d;
    transform: none;
    box-shadow: none;
}

.form-control:focus, .form-select:focus {
    border-color: #2c3e50;
    box-shadow: 0 0 0 0.2rem rgba(44, 62, 80, 0.25);
}

.form-check-input:checked {
    background-color: #2c3e50;
    border-color: #2c3e50;
}

.section-title {
    font-size: 1.25rem;
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 1.5rem;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid #2c3e50;
    display: inline-block;
}

.summary-card {
    background: linear-gradient(135deg, rgba(44, 62, 80, 0.05) 0%, rgba(52, 73, 94, 0.05) 100%);
    border: 1px solid rgba(44, 62, 80, 0.2);
    border-radius: 12px;
    padding: 1.5rem;
}

.info-item {
    padding: 0.75rem 0;
    border-bottom: 1px solid rgba(44, 62, 80, 0.1);
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
    border-color: #2c3e50;
    background: rgba(44, 62, 80, 0.05);
}

.appointment-type-card.selected {
    border-color: #2c3e50;
    background: linear-gradient(135deg, rgba(44, 62, 80, 0.1) 0%, rgba(52, 73, 94, 0.1) 100%);
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
    background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
    color: white;
}

.progress-step.completed {
    background: #28a745;
    color: white;
}

.doctor-avatar {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    border: 3px solid white;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.patient-search-container {
    position: relative;
}

.patient-search-results {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: white;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    max-height: 200px;
    overflow-y: auto;
    z-index: 1000;
    display: none;
}

.patient-result {
    padding: 0.75rem 1rem;
    cursor: pointer;
    border-bottom: 1px solid #f8f9fa;
}

.patient-result:hover {
    background: rgba(44, 62, 80, 0.05);
}

.patient-result:last-child {
    border-bottom: none;
}
</style>
@endpush

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
</style>
<div class="container-fluid" style="background-color: #f8f9fa;">
    <div class="container">
        <div class="dashboard-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2><i class="fas fa-calendar-plus me-2"></i>Book Appointment</h2>
                    <p class="text-muted mb-0">Schedule a new appointment for your patient</p>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container-fluid">
    <div class="container">
        <!-- Progress Indicator -->
        <div class="progress-indicator">
            <div class="progress-step active" id="step1">1</div>
            <div class="progress-step" id="step2">2</div>
            <div class="progress-step" id="step3">3</div>
        </div>

        <!-- Patient Selection -->
        <div class="section-card mb-4">
            <h2 class="section-title">Select Patient</h2>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <div class="patient-option" data-type="existing">
                        <div class="d-flex align-items-start">
                            <div class="icon-circle bg-primary text-white me-3">
                                <i class="fas fa-users"></i>
                            </div>
                            <div>
                                <h3 class="h5 mb-2">Existing Patient</h3>
                                <p class="text-muted mb-0">Select from patients who have visited before.</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="patient-option" data-type="new">
                        <div class="d-flex align-items-start">
                            <div class="icon-circle bg-success text-white me-3">
                                <i class="fas fa-user-plus"></i>
                            </div>
                            <div>
                                <h3 class="h5 mb-2">New Patient</h3>
                                <p class="text-muted mb-0">Create a new patient record for this appointment.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Booking Form -->
        <form method="POST" action="{{ route('doctor.appointments.store') }}" id="appointmentForm">
            @csrf
            <input type="hidden" name="patient_type" id="patientType" value="existing">

            <div class="row">
                <!-- Left Column - Form -->
                <div class="col-lg-8">
                    <!-- Existing Patient Selection -->
                    <div class="section-card" id="existingPatientSection">
                        <h2 class="section-title">Select Existing Patient</h2>
                        <div class="patient-search-container mb-3">
                            <label for="patient_search" class="form-label fw-medium">
                                Search Patient <span class="text-danger">*</span>
                            </label>
                            <input type="text" id="patient_search" class="form-control"
                                   placeholder="Search by name, email, or phone..." autocomplete="off">
                            <input type="hidden" name="existing_patient_id" id="existing_patient_id">
                            <div class="patient-search-results" id="patientSearchResults"></div>
                        </div>
                        <div id="selectedPatientInfo" style="display: none;">
                            <div class="alert alert-info">
                                <h6 class="alert-heading">Selected Patient:</h6>
                                <p class="mb-0" id="selectedPatientDetails"></p>
                            </div>
                        </div>
                    </div>

                    <!-- New Patient Information -->
                    <div class="section-card" id="newPatientSection" style="display: none;">
                        <h2 class="section-title">New Patient Information</h2>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            Please provide complete patient information. A secure password will be auto-generated and sent to the patient via email.
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="patient_name" class="form-label fw-medium">
                                    Full Name <span class="text-danger">*</span>
                                </label>
                                <input type="text" name="patient_name" id="patient_name"
                                       class="form-control"
                                       placeholder="Enter patient's full name" value="{{ old('patient_name') }}">
                                @error('patient_name')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="patient_email" class="form-label fw-medium">
                                    Email Address <span class="text-danger">*</span>
                                </label>
                                <input type="email" name="patient_email" id="patient_email"
                                       class="form-control"
                                       placeholder="Enter patient's email" value="{{ old('patient_email') }}">
                                @error('patient_email')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="patient_phone" class="form-label fw-medium">
                                    Phone Number <span class="text-danger">*</span>
                                </label>
                                <input type="tel" name="patient_phone" id="patient_phone"
                                       class="form-control"
                                       placeholder="Enter patient's phone number" value="{{ old('patient_phone') }}">
                                @error('patient_phone')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="patient_date_of_birth" class="form-label fw-medium">
                                    Date of Birth <span class="text-danger">*</span>
                                </label>
                                <input type="date" name="patient_date_of_birth" id="patient_date_of_birth"
                                       class="form-control"
                                       max="{{ date('Y-m-d') }}" value="{{ old('patient_date_of_birth') }}">
                                @error('patient_date_of_birth')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="patient_gender" class="form-label fw-medium">
                                    Gender <span class="text-danger">*</span>
                                </label>
                                <select name="patient_gender" id="patient_gender" class="form-select">
                                    <option value="">Select gender</option>
                                    <option value="male" {{ old('patient_gender') == 'male' ? 'selected' : '' }}>Male</option>
                                    <option value="female" {{ old('patient_gender') == 'female' ? 'selected' : '' }}>Female</option>
                                    <option value="other" {{ old('patient_gender') == 'other' ? 'selected' : '' }}>Other</option>
                                </select>
                                @error('patient_gender')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-12">
                                <div class="form-check">
                                    <input type="checkbox" name="patient_terms" id="patient_terms" class="form-check-input">
                                    <label for="patient_terms" class="form-check-label">
                                        I confirm that I have obtained consent from the patient to create this account and collect their information.
                                        By checking this box, I acknowledge that the patient will receive login credentials and account access.
                                    </label>
                                    @error('patient_terms')
                                        <div class="text-danger small mt-1">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

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
                                    <p class="text-muted">Please check your availability settings to add time slots.</p>
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
                            @error('appointment_date')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
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
                                        'description' => 'Clinic visit'
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
                        @error('appointment_type')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror

                        @if(count($enabledTypes) === 0)
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                No appointment types are enabled. Please check your appointment settings.
                            </div>
                        @endif
                    </div>

                    <!-- Medical Information -->
                    <div class="section-card">
                        <h2 class="section-title">Appointment Details</h2>
                        <div class="row g-3">
                            <div class="col-12">
                                <label for="reason" class="form-label fw-medium">
                                    Reason for Visit <span class="text-danger">*</span>
                                </label>
                                <textarea name="reason" id="reason" rows="3" required
                                          class="form-control"
                                          placeholder="Please describe the reason for the appointment...">{{ old('reason') }}</textarea>
                                @error('reason')
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

                            <!-- Patient Info -->
                            <div class="mb-4 pb-3 border-bottom">
                                <div class="fw-medium mb-2">Patient:</div>
                                <div id="summaryPatient">Not selected</div>
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
                                    <span class="fw-bold" style="color: #2c3e50;">${{ number_format($doctor->consultation_fee / 100, 2) }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Important Information -->
                        <div class="section-card">
                            <h3 class="h6 fw-semibold mb-3" style="color: #2c3e50;">Appointment Status</h3>
                            <div class="small text-muted">
                                <div class="d-flex mb-2">
                                    <i class="fas fa-info-circle me-2 mt-1" style="color: #2c3e50;"></i>
                                    <span>Appointment will be {{ $doctor->auto_approve_appointments ? 'confirmed' : 'pending approval' }}</span>
                                </div>
                                <div class="d-flex">
                                    <i class="fas fa-clock me-2 mt-1" style="color: #2c3e50;"></i>
                                    <span>Patient will receive notification</span>
                                </div>
                            </div>
                        </div>

                        <!-- Book Button -->
                        <button type="submit" class="btn btn-book w-100 mb-3" id="submitButton" disabled>
                            <i class="fas fa-calendar-plus me-2"></i>
                            Book Appointment
                        </button>
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
    const summaryPatient = document.getElementById('summaryPatient');

    let selectedDate = null;
    let selectedTime = null;
    let selectedPatient = null;

    // Patient type selection
    const patientOptions = document.querySelectorAll('.patient-option');
    const existingPatientSection = document.getElementById('existingPatientSection');
    const newPatientSection = document.getElementById('newPatientSection');
    const patientTypeInput = document.getElementById('patientType');

    patientOptions.forEach(option => {
        option.addEventListener('click', function() {
            // Remove previous selection
            patientOptions.forEach(opt => {
                opt.classList.remove('border-primary', 'bg-primary', 'bg-opacity-10', 'border-success', 'bg-success');
            });

            // Add selection to current
            const type = this.dataset.type;
            patientTypeInput.value = type;

            if (type === 'existing') {
                this.classList.add('border-primary', 'bg-primary', 'bg-opacity-10');
                existingPatientSection.style.display = 'block';
                newPatientSection.style.display = 'none';

                // Remove required attributes from new patient fields
                const newPatientFields = newPatientSection.querySelectorAll('input[required], select[required], textarea[required]');
                newPatientFields.forEach(field => {
                    field.required = false;
                    field.removeAttribute('required');
                });

                selectedPatient = null;
                updateSummaryPatient();
                updateSubmitButton();
            } else {
                this.classList.add('border-success', 'bg-success', 'bg-opacity-10');
                existingPatientSection.style.display = 'none';
                newPatientSection.style.display = 'block';

                // Add required attributes to new patient fields
                const newPatientFields = newPatientSection.querySelectorAll('input[name="patient_name"], input[name="patient_email"], input[name="patient_phone"], input[name="patient_date_of_birth"], select[name="patient_gender"], input[name="patient_terms"]');
                newPatientFields.forEach(field => {
                    field.required = true;
                    field.setAttribute('required', 'required');
                });

                selectedPatient = { name: 'New Patient', email: 'To be created' };
                updateSummaryPatient();
                updateSubmitButton();
            }
        });
    });

    // Set default selection to existing patient
    if (patientOptions.length > 0) {
        patientOptions[0].click();

        // Ensure new patient fields are not required by default
        setTimeout(() => {
            const newPatientFields = newPatientSection.querySelectorAll('input[required], select[required], textarea[required]');
            newPatientFields.forEach(field => {
                field.required = false;
                field.removeAttribute('required');
            });
        }, 100);
    }

    // Patient search functionality
    const patientSearchInput = document.getElementById('patient_search');
    const patientSearchResults = document.getElementById('patientSearchResults');
    const existingPatientIdInput = document.getElementById('existing_patient_id');
    const selectedPatientInfo = document.getElementById('selectedPatientInfo');
    const selectedPatientDetails = document.getElementById('selectedPatientDetails');

    let searchTimeout;

    patientSearchInput.addEventListener('input', function() {
        const query = this.value.trim();

        if (query.length < 2) {
            patientSearchResults.style.display = 'none';
            return;
        }

        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            searchPatients(query);
        }, 300);
    });

    function searchPatients(query) {
        fetch(`{{ route('doctor.patients.search') }}?query=${encodeURIComponent(query)}`, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            credentials: 'same-origin' // Include cookies
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            displaySearchResults(data);
        })
        .catch(error => {
            console.error('Search error:', error);
            // Show user-friendly error
            displaySearchResults([]);
        });
    }

    function displaySearchResults(patients) {
        patientSearchResults.innerHTML = '';

        if (patients.length === 0) {
            patientSearchResults.innerHTML = '<div class="p-3 text-muted">No patients found</div>';
            patientSearchResults.style.display = 'block';
            return;
        }

        patients.forEach(patient => {
            const resultDiv = document.createElement('div');
            resultDiv.className = 'patient-result';
            resultDiv.innerHTML = `
                <div class="fw-medium">${patient.name}</div>
                <div class="small text-muted">${patient.email}</div>
            `;
            resultDiv.addEventListener('click', () => selectPatient(patient));
            patientSearchResults.appendChild(resultDiv);
        });

        patientSearchResults.style.display = 'block';
    }

    function selectPatient(patient) {
        selectedPatient = patient;
        patientSearchInput.value = patient.name;
        existingPatientIdInput.value = patient.id;
        patientSearchResults.style.display = 'none';

        selectedPatientDetails.innerHTML = `
            <strong>${patient.name}</strong><br>
            <small class="text-muted">${patient.email}</small>
        `;
        selectedPatientInfo.style.display = 'block';

        updateSummaryPatient();
        updateSubmitButton();
    }

    // Close search results when clicking outside
    document.addEventListener('click', function(e) {
        if (!patientSearchInput.contains(e.target) && !patientSearchResults.contains(e.target)) {
            patientSearchResults.style.display = 'none';
        }
    });

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
                    btn.classList.remove('selected');
                });

                // Add selection to current
                this.classList.add('selected');

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

    function updateSummaryPatient() {
        if (selectedPatient) {
            if (selectedPatient.id) {
                summaryPatient.innerHTML = `<strong>${selectedPatient.name}</strong><br><small class="text-muted">${selectedPatient.email}</small>`;
            } else {
                summaryPatient.innerHTML = `<em class="text-muted">${selectedPatient.name}</em>`;
            }
        } else {
            summaryPatient.textContent = 'Not selected';
        }
    }

    function updateSubmitButton() {
        const hasPatient = selectedPatient !== null;
        const hasDateTime = selectedDate && selectedTime;

        submitButton.disabled = !(hasPatient && hasDateTime);
    }
});
</script>

<style>
.patient-option {
    transition: all 0.3s ease;
    cursor: pointer;
}

.patient-option:hover {
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