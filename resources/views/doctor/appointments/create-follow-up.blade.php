@extends('master')

@section('title', 'Create Follow-up Appointment')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/doctor-dashboard.css') }}">
<link rel="stylesheet" href="{{ asset('demos/medical/medical.css') }}">
@endpush

@section('content')
<div class="dashboard-container">
    <div class="container">
        <!-- Header -->
        <div class="dashboard-header py-3 border-bottom d-flex justify-content-between align-items-center mb-4" style="background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%); color: white; border-radius: 10px 10px 0 0;">
            <div class="d-flex align-items-center">
                <a href="{{ route('doctor.appointments.show', $appointment) }}" class="btn btn-light me-3 shadow-sm">
                    <i class="fas fa-arrow-left me-2"></i>Back to Appointment
                </a>
                <div>
                    <h1 class="h2 mb-1 fw-bold" style="color: white;">Create Follow-up Appointment</h1>
                    <p class="mb-0 opacity-75">For Patient: {{ $appointment->patient_name }}</p>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-8">
                <!-- Main Form -->
                <div class="table-card mb-4 shadow-sm">
                    <div class="bg-gradient-info text-white p-4 rounded-top" style="background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);">
                        <h4 class="mb-0 fw-bold">
                            <i class="fas fa-calendar-plus me-2"></i>Follow-up Appointment Details
                        </h4>
                    </div>
                    <div class="p-4">
                        <form method="POST" action="{{ route('doctor.follow-ups.store', $appointment) }}">
                            @csrf

                            <!-- Original Appointment Reference -->
                            <div class="alert alert-info border-0 rounded-3 mb-4">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-info-circle me-3 text-info" style="font-size: 1.5rem;"></i>
                                    <div>
                                        <h6 class="mb-1 fw-bold text-info">Follow-up for Completed Appointment</h6>
                                        <p class="mb-0 text-muted small">
                                            Original appointment was completed on {{ $appointment->completed_at ? $appointment->completed_at->format('M j, Y \a\t g:i A') : 'N/A' }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Patient Information -->
                            <div class="row mb-4">
                                <div class="col-12">
                                    <h5 class="text-primary fw-bold mb-3">
                                        <i class="fas fa-user-injured me-2"></i>Patient Information
                                    </h5>
                                </div>
                                <div class="col-md-6">
                                    <div class="p-3 bg-light rounded">
                                        <p class="text-muted mb-1 small fw-semibold">PATIENT NAME</p>
                                        <p class="mb-0 fw-semibold h6">{{ $appointment->patient_name }}</p>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="p-3 bg-light rounded">
                                        <p class="text-muted mb-1 small fw-semibold">EMAIL</p>
                                        <p class="mb-0 fw-semibold h6">{{ $appointment->patient_email }}</p>
                                    </div>
                                </div>
                                @if($appointment->patient_phone)
                                    <div class="col-md-6 mt-3">
                                        <div class="p-3 bg-light rounded">
                                            <p class="text-muted mb-1 small fw-semibold">PHONE</p>
                                            <p class="mb-0 fw-semibold h6">{{ $appointment->patient_phone }}</p>
                                        </div>
                                    </div>
                                @endif
                            </div>

                            <!-- Date & Time -->
                            <div class="mb-4">
                                <h5 class="text-primary fw-bold mb-3">
                                    <i class="fas fa-calendar-alt me-2"></i>Appointment Schedule
                                </h5>
                                <div class="row g-3">
                                    <div class="col-md-8">
                                        <label for="appointment_date" class="form-label fw-semibold">
                                            Appointment Date & Time <span class="text-danger">*</span>
                                        </label>
                                        <input type="datetime-local" 
                                               class="form-control @error('appointment_date') is-invalid @enderror" 
                                               id="appointment_date" 
                                               name="appointment_date" 
                                               required
                                               min="{{ now()->addHour()->format('Y-m-d\TH:i') }}">
                                        @error('appointment_date')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-4">
                                        <label for="duration" class="form-label fw-semibold">
                                            Duration (minutes)
                                        </label>
                                        <select class="form-select" id="duration" name="duration">
                                            <option value="15">15 minutes</option>
                                            <option value="30" selected>30 minutes</option>
                                            <option value="45">45 minutes</option>
                                            <option value="60">60 minutes</option>
                                            <option value="90">90 minutes</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Appointment Type -->
                            <div class="mb-4">
                                <h5 class="text-primary fw-bold mb-3">
                                    <i class="fas fa-video me-2"></i>Appointment Type
                                </h5>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="appointment_type" class="form-label fw-semibold">
                                            Type <span class="text-danger">*</span>
                                        </label>
                                        <select class="form-select @error('appointment_type') is-invalid @enderror" 
                                                id="appointment_type" 
                                                name="appointment_type" 
                                                required>
                                            <option value="">Select appointment type</option>
                                            <option value="video_call">Video Call</option>
                                            <option value="phone_call">Phone Call</option>
                                            <option value="in_person">In-Person</option>
                                            <option value="follow_up">Follow-up</option>
                                        </select>
                                        @error('appointment_type')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    {{-- Consultation Fee hidden for clinic SaaS --}}
                                    <input type="hidden" name="consultation_fee" value="{{ $appointment->consultation_fee / 100 }}">
                                </div>
                            </div>

                            <!-- Reason for Follow-up -->
                            <div class="mb-4">
                                <h5 class="text-primary fw-bold mb-3">
                                    <i class="fas fa-clipboard-list me-2"></i>Reason for Follow-up
                                </h5>
                                <div class="mb-3">
                                    <label for="reason" class="form-label fw-semibold">
                                        Reason for Visit <span class="text-danger">*</span>
                                    </label>
                                    <textarea class="form-control @error('reason') is-invalid @enderror" 
                                              id="reason" 
                                              name="reason" 
                                              rows="4" 
                                              required
                                              placeholder="Please describe the reason for this follow-up appointment..."></textarea>
                                    @error('reason')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="d-flex gap-3 justify-content-between align-items-center">
                                <a href="{{ route('doctor.appointments.show', $appointment) }}" class="btn btn-outline-secondary">
                                    <i class="fas fa-times me-2"></i>Cancel
                                </a>
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary btn-lg fw-semibold">
                                        <i class="fas fa-save me-2"></i>Create Follow-up
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Original Appointment Summary -->
                <div class="table-card mb-4 shadow-sm">
                    <div class="p-4">
                        <h5 class="mb-4 text-primary fw-bold">
                            <i class="fas fa-history me-2"></i>Original Appointment
                        </h5>
                        <div class="space-y-3">
                            <div>
                                <p class="text-muted mb-1 small fw-semibold">DATE & TIME</p>
                                <p class="mb-0 fw-semibold">{{ $appointment->appointment_date->format('M j, Y \a\t g:i A') }}</p>
                            </div>
                            <div>
                                <p class="text-muted mb-1 small fw-semibold">TYPE</p>
                                <p class="mb-0 fw-semibold">{{ ucfirst(str_replace('_', ' ', $appointment->appointment_type)) }}</p>
                            </div>
                            <div>
                                <p class="text-muted mb-1 small fw-semibold">STATUS</p>
                                <span class="badge bg-success fs-6">
                                    <i class="fas fa-check-circle me-1"></i>{{ ucfirst($appointment->status) }}
                                </span>
                            </div>
                            @if($appointment->doctor_notes)
                                <div>
                                    <p class="text-muted mb-2 small fw-semibold">DOCTOR NOTES</p>
                                    <div class="bg-light p-3 rounded">
                                        <p class="mb-0 small">{{ Str::limit($appointment->doctor_notes, 200) }}</p>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Help & Tips -->
                <div class="table-card shadow-sm">
                    <div class="p-4">
                        <h5 class="mb-4 text-success fw-bold">
                            <i class="fas fa-lightbulb me-2"></i>Follow-up Tips
                        </h5>
                        <div class="space-y-3">
                            <div class="d-flex align-items-start">
                                <i class="fas fa-check-circle text-success me-2 mt-1"></i>
                                <small class="text-muted">Schedule follow-ups within 1-4 weeks for optimal patient care</small>
                            </div>
                            <div class="d-flex align-items-start">
                                <i class="fas fa-check-circle text-success me-2 mt-1"></i>
                                <small class="text-muted">Consider patient availability and urgency when setting dates</small>
                            </div>
                            <div class="d-flex align-items-start">
                                <i class="fas fa-check-circle text-success me-2 mt-1"></i>
                                <small class="text-muted">Ensure the follow-up type matches the care needed</small>
                            </div>
                            <div class="d-flex align-items-start">
                                <i class="fas fa-check-circle text-success me-2 mt-1"></i>
                                <small class="text-muted">Patient will receive automatic notifications about the new appointment</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-populate reason field with common follow-up patterns
    const reasonField = document.getElementById('reason');
    const appointmentTypeField = document.getElementById('appointment_type');
    
    appointmentTypeField.addEventListener('change', function() {
        if (!reasonField.value.trim()) {
            const followUpReasons = {
                'follow_up': 'Follow-up appointment to review treatment progress and patient response.',
                'video_call': 'Video consultation to discuss test results and treatment plan.',
                'phone_call': 'Phone consultation for medication review and patient concerns.',
                'in_person': 'In-person appointment for physical examination and treatment adjustment.'
            };
            
            const selectedType = this.value;
            if (followUpReasons[selectedType]) {
                reasonField.value = followUpReasons[selectedType];
            }
        }
    });

    // Set minimum date to current time + 1 hour
    const appointmentDateField = document.getElementById('appointment_date');
    const now = new Date();
    now.setHours(now.getHours() + 1);
    appointmentDateField.min = now.toISOString().slice(0, 16);
});
</script>
@endpush