@extends('layouts.patient')

@section('title', 'Join Waitlist')

@section('styles')
<style>
.doctor-card {
    transition: all 0.3s ease;
    cursor: pointer;
    border: 2px solid transparent;
}

.doctor-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
    border-color: #3b82f6;
}

.doctor-card.selected {
    border-color: #3b82f6;
    background-color: #eff6ff;
}

.doctor-avatar {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.5rem;
    font-weight: 700;
}

.form-section {
    background: #f8fafc;
    border-radius: 0.5rem;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
}

.form-section-title {
    font-weight: 600;
    color: #374151;
    margin-bottom: 1rem;
    border-bottom: 2px solid #e5e7eb;
    padding-bottom: 0.5rem;
}

.priority-option {
    border: 2px solid #e5e7eb;
    border-radius: 0.5rem;
    padding: 1rem;
    text-align: center;
    cursor: pointer;
    transition: all 0.2s;
}

.priority-option:hover {
    border-color: #3b82f6;
    background-color: #eff6ff;
}

.priority-option.selected {
    border-color: #3b82f6;
    background-color: #eff6ff;
}

.priority-urgent { border-left: 4px solid #dc2626; }
.priority-high { border-left: 4px solid #ea580c; }
.priority-medium { border-left: 4px solid #d97706; }
.priority-low { border-left: 4px solid #65a30d; }

.time-slot-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
    gap: 0.75rem;
}

.time-slot {
    padding: 0.75rem;
    border: 1px solid #d1d5db;
    border-radius: 0.375rem;
    text-align: center;
    cursor: pointer;
    transition: all 0.2s;
    font-size: 0.875rem;
}

.time-slot:hover {
    border-color: #3b82f6;
    background-color: #eff6ff;
}

.time-slot.selected {
    border-color: #3b82f6;
    background-color: #eff6ff;
    color: #3b82f6;
    font-weight: 600;
}

.day-selector {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.day-chip {
    padding: 0.5rem 1rem;
    border: 1px solid #d1d5db;
    border-radius: 1.5rem;
    cursor: pointer;
    transition: all 0.2s;
    font-size: 0.875rem;
}

.day-chip:hover {
    border-color: #3b82f6;
}

.day-chip.selected {
    border-color: #3b82f6;
    background-color: #3b82f6;
    color: white;
}

.suggestion-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 0.5rem;
    padding: 1rem;
    margin-bottom: 1rem;
}

.recommendation-score {
    background: rgba(255, 255, 255, 0.2);
    border-radius: 0.25rem;
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
    font-weight: 600;
}

.loading-spinner {
    border: 3px solid #f3f3f3;
    border-top: 3px solid #3b82f6;
    border-radius: 50%;
    width: 30px;
    height: 30px;
    animation: spin 1s linear infinite;
    margin: 0 auto;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
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
                    <h2 class="mb-1">Join Waitlist</h2>
                    <p class="text-muted mb-0">Find a doctor and join their appointment waitlist</p>
                </div>
                <div>
                    <a href="{{ route('patient.waitlist.dashboard') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>

    <form id="waitlistForm" method="POST" action="{{ route('patient.waitlist.join') }}">
        @csrf

        <!-- Step 1: Select Doctor -->
        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header bg-white border-0 py-3">
                        <h5 class="mb-0">Step 1: Select Doctor</h5>
                    </div>
                    <div class="card-body">
                        <!-- Doctor Search -->
                        <div class="mb-4">
                            <input type="text" class="form-control" id="doctorSearch"
                                   placeholder="Search doctors by name or specialty...">
                        </div>

                        <!-- Doctor Grid -->
                        <div class="row" id="doctorGrid">
                            @foreach($doctors as $doctor)
                                <div class="col-md-6 col-lg-4 mb-3 doctor-option"
                                     data-doctor-id="{{ $doctor->id }}"
                                     data-doctor-name="{{ strtolower($doctor->user->name) }}"
                                     data-specialty="{{ strtolower($doctor->specialty) }}">
                                    <div class="card doctor-card h-100" onclick="selectDoctor({{ $doctor->id }})">
                                        <div class="card-body text-center">
                                            <div class="doctor-avatar mx-auto mb-3">
                                                {{ $doctor->user->name[0] }}
                                            </div>
                                            <h6 class="mb-1">{{ $doctor->user->name }}</h6>
                                            <p class="text-muted small mb-2">{{ $doctor->specialty }}</p>
                                            <p class="text-muted small mb-0">
                                                <i class="fas fa-envelope me-1"></i>{{ $doctor->user->email }}
                                            </p>
                                            <input type="radio" name="doctor_id" value="{{ $doctor->id }}"
                                                   class="d-none" id="doctor-{{ $doctor->id }}">
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        @error('doctor_id')
                            <div class="text-danger small mt-2">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Step 2: Service & Priority -->
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header bg-white border-0 py-3">
                        <h5 class="mb-0">Step 2: Service & Priority</h5>
                    </div>
                    <div class="card-body">
                        <!-- Service Type -->
                        <div class="form-section">
                            <div class="form-section-title">Service Type</div>
                            <div class="mb-3">
                                <select name="service_type" class="form-select" required>
                                    <option value="">Select Service</option>
                                    <option value="consultation" {{ old('service_type') == 'consultation' ? 'selected' : '' }}>
                                        Consultation
                                    </option>
                                    <option value="follow-up" {{ old('service_type') == 'follow-up' ? 'selected' : '' }}>
                                        Follow-up Visit
                                    </option>
                                    <option value="urgent-care" {{ old('service_type') == 'urgent-care' ? 'selected' : '' }}>
                                        Urgent Care
                                    </option>
                                </select>
                                @error('service_type')
                                    <div class="text-danger small">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Priority Level -->
                        <div class="form-section">
                            <div class="form-section-title">Priority Level</div>
                            <div class="row g-2">
                                <div class="col-6">
                                    <div class="priority-option priority-urgent" onclick="selectPriority('urgent')">
                                        <div class="fw-bold">Urgent</div>
                                        <small class="text-muted">ASAP</small>
                                    </div>
                                    <input type="radio" name="priority_level" value="urgent"
                                           class="d-none" id="priority-urgent">
                                </div>
                                <div class="col-6">
                                    <div class="priority-option priority-high" onclick="selectPriority('high')">
                                        <div class="fw-bold">High</div>
                                        <small class="text-muted">Soon</small>
                                    </div>
                                    <input type="radio" name="priority_level" value="high"
                                           class="d-none" id="priority-high">
                                </div>
                                <div class="col-6">
                                    <div class="priority-option priority-medium" onclick="selectPriority('medium')">
                                        <div class="fw-bold">Medium</div>
                                        <small class="text-muted">Normal</small>
                                    </div>
                                    <input type="radio" name="priority_level" value="medium"
                                           class="d-none" id="priority-medium">
                                </div>
                                <div class="col-6">
                                    <div class="priority-option priority-low" onclick="selectPriority('low')">
                                        <div class="fw-bold">Low</div>
                                        <small class="text-muted">Flexible</small>
                                    </div>
                                    <input type="radio" name="priority_level" value="low"
                                           class="d-none" id="priority-low">
                                </div>
                            </div>
                            @error('priority_level')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Max Wait Days -->
                        <div class="form-section">
                            <div class="form-section-title">Maximum Wait Time</div>
                            <select name="max_wait_days" class="form-select">
                                <option value="7">1 week</option>
                                <option value="14">2 weeks</option>
                                <option value="30" selected>1 month</option>
                                <option value="60">2 months</option>
                                <option value="90">3 months</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Step 3: Preferences (Optional) -->
        <div class="row mt-4" id="preferencesSection" style="display: none;">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-white border-0 py-3">
                        <h5 class="mb-0">Step 3: Preferences (Optional)</h5>
                        <small class="text-muted">Set preferences to get better slot recommendations</small>
                    </div>
                    <div class="card-body">
                        <!-- AI Suggestions -->
                        @if(isset($existingPreferences) && $existingPreferences)
                            <div class="suggestion-card">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h6 class="mb-0">AI Suggestions Based on Your History</h6>
                                    <span class="recommendation-score">95% Match</span>
                                </div>
                                <p class="mb-2">Based on your previous appointments, we recommend:</p>
                                <div class="row">
                                    <div class="col-md-6">
                                        <small><strong>Preferred Time:</strong> {{ implode(', ', $existingPreferences->preferred_times ?? ['Morning']) }}</small>
                                    </div>
                                    <div class="col-md-6">
                                        <small><strong>Preferred Days:</strong> {{ implode(', ', $existingPreferences->preferred_days ?? ['Weekdays']) }}</small>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <!-- Time Preferences -->
                        <div class="form-section">
                            <div class="form-section-title">Preferred Times</div>
                            <div class="time-slot-grid">
                                <div class="time-slot" onclick="toggleTimeSlot(this)" data-time="morning">
                                    <i class="fas fa-sun mb-1"></i><br>Morning<br><small>6AM - 12PM</small>
                                </div>
                                <div class="time-slot" onclick="toggleTimeSlot(this)" data-time="afternoon">
                                    <i class="fas fa-cloud-sun mb-1"></i><br>Afternoon<br><small>12PM - 5PM</small>
                                </div>
                                <div class="time-slot" onclick="toggleTimeSlot(this)" data-time="evening">
                                    <i class="fas fa-moon mb-1"></i><br>Evening<br><small>5PM - 10PM</small>
                                </div>
                            </div>
                            <input type="hidden" name="preferred_times" id="preferredTimesInput">
                        </div>

                        <!-- Day Preferences -->
                        <div class="form-section">
                            <div class="form-section-title">Preferred Days</div>
                            <div class="day-selector">
                                <div class="day-chip" onclick="toggleDay(this)" data-day="monday">Mon</div>
                                <div class="day-chip" onclick="toggleDay(this)" data-day="tuesday">Tue</div>
                                <div class="day-chip" onclick="toggleDay(this)" data-day="wednesday">Wed</div>
                                <div class="day-chip" onclick="toggleDay(this)" data-day="thursday">Thu</div>
                                <div class="day-chip" onclick="toggleDay(this)" data-day="friday">Fri</div>
                                <div class="day-chip" onclick="toggleDay(this)" data-day="saturday">Sat</div>
                                <div class="day-chip" onclick="toggleDay(this)" data-day="sunday">Sun</div>
                            </div>
                            <input type="hidden" name="preferred_days" id="preferredDaysInput">
                        </div>

                        <!-- Notification Preferences -->
                        <div class="form-section">
                            <div class="form-section-title">Notification Preferences</div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="emailNotifications" name="notifications[]" value="email" checked>
                                        <label class="form-check-label" for="emailNotifications">
                                            Email Notifications
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="smsNotifications" name="notifications[]" value="sms">
                                        <label class="form-check-label" for="smsNotifications">
                                            SMS Notifications
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Submit Section -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1">Ready to Join?</h6>
                                <p class="text-muted mb-0">Click join waitlist to get in line for your appointment</p>
                            </div>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-outline-secondary" onclick="skipPreferences()">
                                    Skip Preferences
                                </button>
                                <button type="submit" class="btn btn-primary" id="submitBtn">
                                    <i class="fas fa-plus me-2"></i>Join Waitlist
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

@endsection

@section('scripts')
<script>
let selectedDoctorId = null;
let selectedPriority = 'medium';
let selectedTimes = [];
let selectedDays = [];

function selectDoctor(doctorId) {
    // Remove previous selection
    document.querySelectorAll('.doctor-card').forEach(card => {
        card.classList.remove('selected');
    });

    // Select new doctor
    const selectedCard = document.querySelector(`[data-doctor-id="${doctorId}"] .doctor-card`);
    if (selectedCard) {
        selectedCard.classList.add('selected');
    }

    // Update radio button
    document.getElementById(`doctor-${doctorId}`).checked = true;
    selectedDoctorId = doctorId;

    // Show preferences section
    document.getElementById('preferencesSection').style.display = 'block';

    // Load doctor-specific recommendations
    loadDoctorRecommendations(doctorId);
}

function selectPriority(priority) {
    // Remove previous selection
    document.querySelectorAll('.priority-option').forEach(option => {
        option.classList.remove('selected');
    });

    // Select new priority
    const selectedOption = document.querySelector(`.priority-${priority}`);
    if (selectedOption) {
        selectedOption.classList.add('selected');
    }

    // Update radio button
    document.getElementById(`priority-${priority}`).checked = true;
    selectedPriority = priority;
}

function toggleTimeSlot(element) {
    const time = element.dataset.time;
    element.classList.toggle('selected');

    if (element.classList.contains('selected')) {
        if (!selectedTimes.includes(time)) {
            selectedTimes.push(time);
        }
    } else {
        selectedTimes = selectedTimes.filter(t => t !== time);
    }

    updatePreferencesInput();
}

function toggleDay(element) {
    const day = element.dataset.day;
    element.classList.toggle('selected');

    if (element.classList.contains('selected')) {
        if (!selectedDays.includes(day)) {
            selectedDays.push(day);
        }
    } else {
        selectedDays = selectedDays.filter(d => d !== day);
    }

    updatePreferencesInput();
}

function updatePreferencesInput() {
    document.getElementById('preferredTimesInput').value = JSON.stringify(selectedTimes);
    document.getElementById('preferredDaysInput').value = JSON.stringify(selectedDays);
}

function skipPreferences() {
    document.getElementById('preferencesSection').style.display = 'none';
    document.getElementById('waitlistForm').submit();
}

function loadDoctorRecommendations(doctorId) {
    // This would load AI-powered recommendations for the selected doctor
    console.log('Loading recommendations for doctor:', doctorId);
    // Implementation would fetch recommendations via AJAX
}

function searchDoctors(query) {
    const doctorOptions = document.querySelectorAll('.doctor-option');
    const searchTerm = query.toLowerCase();

    doctorOptions.forEach(option => {
        const doctorName = option.dataset.doctorName;
        const specialty = option.dataset.specialty;

        if (doctorName.includes(searchTerm) || specialty.includes(searchTerm)) {
            option.style.display = 'block';
        } else {
            option.style.display = 'none';
        }
    });
}

// Form validation
document.getElementById('waitlistForm').addEventListener('submit', function(e) {
    const doctorSelected = selectedDoctorId !== null;
    const prioritySelected = selectedPriority !== null;

    if (!doctorSelected) {
        e.preventDefault();
        alert('Please select a doctor before joining the waitlist.');
        return false;
    }

    if (!prioritySelected) {
        e.preventDefault();
        alert('Please select a priority level.');
        return false;
    }

    // Show loading state
    const submitBtn = document.getElementById('submitBtn');
    submitBtn.innerHTML = '<div class="loading-spinner me-2"></div>Joining...';
    submitBtn.disabled = true;
});

// Initialize defaults
document.addEventListener('DOMContentLoaded', function() {
    // Set default priority
    selectPriority('medium');

    // Add search functionality
    const searchInput = document.getElementById('doctorSearch');
    searchInput.addEventListener('input', function() {
        searchDoctors(this.value);
    });
});
</script>
@endsection
