@extends('master')

@section('title', 'Add Availability Slot')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/custom-openai.css') }}">
<link rel="stylesheet" href="{{ asset('css/doctor-dashboard.css') }}">
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
                    <h2><i class="fas fa-calendar-plus me-2"></i>Add Availability Slot</h2>
                    <p class="text-muted mb-0">Create a new time slot for patient appointments</p>
                </div>
                <a href="{{ route('doctor.availability.index') }}" class="btn" style="background: rgba(255,255,255,0.2); border: 1px solid rgba(255,255,255,0.3); color: white;">
                    <i class="fas fa-arrow-left me-2"></i>Back to Availability
                </a>
            </div>
        </div>
    </div>
</div>

        <!-- Session Messages -->
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        
        @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <ul class="mb-0">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <!-- Form -->
        <div class="table-card">
            <h6 class="mb-4"><i class="fas fa-plus me-2"></i>New Time Slot</h6>

            <form method="POST" action="{{ route('doctor.availability.store') }}">
                @csrf

                <div class="row g-4">
                    <!-- Day of Week -->
                    <div class="col-md-6">
                        <label class="form-label">
                            Day of Week <span class="text-danger">*</span>
                        </label>
                        <select name="day_of_week" required class="form-select">
                            <option value="">Select a day</option>
                            @foreach($daysOfWeek as $day => $dayName)
                                <option value="{{ $day }}" {{ old('day_of_week') == $day ? 'selected' : '' }}>
                                    {{ $dayName }}
                                </option>
                            @endforeach
                        </select>
                        @error('day_of_week')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Start Time -->
                    <div class="col-md-6">
                        <label class="form-label">
                            Start Time <span class="text-danger">*</span>
                        </label>
                        <input type="time" name="start_time" value="{{ old('start_time') }}" required class="form-control">
                        @error('start_time')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- End Time -->
                    <div class="col-md-6">
                        <label class="form-label">
                            End Time <span class="text-danger">*</span>
                        </label>
                        <input type="time" name="end_time" value="{{ old('end_time') }}" required class="form-control">
                        @error('end_time')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Slot Duration -->
                    <div class="col-md-6">
                        <label class="form-label">
                            Slot Duration (minutes) <span class="text-danger">*</span>
                        </label>
                        <select name="slot_duration" required class="form-select">
                            <option value="15" {{ old('slot_duration') == '15' ? 'selected' : '' }}>15 minutes</option>
                            <option value="30" {{ old('slot_duration', '30') == '30' ? 'selected' : '' }}>30 minutes</option>
                            <option value="45" {{ old('slot_duration') == '45' ? 'selected' : '' }}>45 minutes</option>
                            <option value="60" {{ old('slot_duration') == '60' ? 'selected' : '' }}>60 minutes</option>
                            <option value="90" {{ old('slot_duration') == '90' ? 'selected' : '' }}>90 minutes</option>
                            <option value="120" {{ old('slot_duration') == '120' ? 'selected' : '' }}>120 minutes</option>
                        </select>
                        @error('slot_duration')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Max Bookings per Slot -->
                    <div class="col-md-6">
                        <label class="form-label">
                            Max Bookings per Slot <span class="text-danger">*</span>
                        </label>
                        <select name="max_bookings_per_slot" required class="form-select">
                            @for($i = 1; $i <= 10; $i++)
                                <option value="{{ $i }}" {{ old('max_bookings_per_slot', '1') == $i ? 'selected' : '' }}>
                                    {{ $i }} {{ $i == 1 ? 'patient' : 'patients' }}
                                </option>
                            @endfor
                        </select>
                        @error('max_bookings_per_slot')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Effective From -->
                    <div class="col-md-6">
                        <label class="form-label">Effective From (optional)</label>
                        <input type="date" name="effective_from" value="{{ old('effective_from') }}"
                               min="{{ date('Y-m-d') }}" class="form-control">
                        <small class="form-text text-muted">Leave blank to start immediately</small>
                        @error('effective_from')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Effective Until -->
                    <div class="col-md-6">
                        <label class="form-label">Effective Until (optional)</label>
                        <input type="date" name="effective_until" value="{{ old('effective_until') }}"
                               min="{{ date('Y-m-d') }}" class="form-control">
                        <small class="form-text text-muted">Leave blank for no end date</small>
                        @error('effective_until')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Preview -->
                    <div class="col-12">
                        <div class="alert alert-info">
                            <h6 class="alert-heading">Preview</h6>
                            <div id="preview">Select day and time to see preview</div>
                        </div>
                    </div>

                    <!-- Submit Buttons -->
                    <div class="col-12">
                        <div class="d-flex justify-content-end gap-3">
                            <a href="{{ route('doctor.availability.index') }}" class="btn btn-secondary-custom">
                                <i class="fas fa-times me-2"></i>Cancel
                            </a>
                            <button type="submit" class="btn btn-primary-custom">
                                <i class="fas fa-save me-2"></i>Create Availability Slot
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <!-- Bulk Create Option -->
        <div class="table-card">
            <h6 class="mb-3"><i class="fas fa-calendar-plus me-2"></i>Quick Setup</h6>
            <p class="text-muted mb-4">Want to set the same hours for multiple days?</p>

            <form method="POST" action="{{ route('doctor.availability.bulk') }}">
                @csrf

                <!-- Multiple Days Selection -->
                <div class="mb-4">
                    <label class="form-label">Select Days</label>
                    <div class="row g-2">
                        @foreach($daysOfWeek as $day => $dayName)
                            <div class="col-md-3 col-6">
                                <div class="form-check">
                                    <input type="checkbox" name="days[]" value="{{ $day }}" class="form-check-input" id="bulk_{{ $day }}">
                                    <label class="form-check-label" for="bulk_{{ $day }}">{{ $dayName }}</label>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <label class="form-label">Start Time</label>
                        <input type="time" name="start_time" value="09:00" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">End Time</label>
                        <input type="time" name="end_time" value="17:00" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Duration</label>
                        <select name="slot_duration" class="form-select">
                            <option value="30">30 min</option>
                            <option value="60">60 min</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Max Bookings</label>
                        <select name="max_bookings_per_slot" class="form-select">
                            <option value="1">1 patient</option>
                            <option value="2">2 patients</option>
                        </select>
                    </div>
                </div>

                <div>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-calendar-plus me-2"></i>Create Multiple Slots
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
</div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const daySelect = document.querySelector('select[name="day_of_week"]');
    const startTime = document.querySelector('input[name="start_time"]');
    const endTime = document.querySelector('input[name="end_time"]');
    const duration = document.querySelector('select[name="slot_duration"]');
    const maxBookings = document.querySelector('select[name="max_bookings_per_slot"]');
    const preview = document.getElementById('preview');

    function updatePreview() {
        const day = daySelect.value;
        const start = startTime.value;
        const end = endTime.value;
        const dur = duration.value;
        const max = maxBookings.value;

        if (day && start && end && dur && max) {
            const dayName = daySelect.options[daySelect.selectedIndex].text;
            const startFormatted = formatTime(start);
            const endFormatted = formatTime(end);

            // Calculate number of slots
            const startMinutes = timeToMinutes(start);
            const endMinutes = timeToMinutes(end);
            const totalMinutes = endMinutes - startMinutes;
            const slots = Math.floor(totalMinutes / parseInt(dur));

            preview.innerHTML = `
                <strong>${dayName}</strong><br>
                ${startFormatted} - ${endFormatted}<br>
                ${slots} slots of ${dur} minutes each<br>
                Up to ${max} patient(s) per slot
            `;
        } else {
            preview.innerHTML = 'Select day and time to see preview';
        }
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

    function timeToMinutes(time) {
        const [hours, minutes] = time.split(':');
        return parseInt(hours) * 60 + parseInt(minutes);
    }

    // Add event listeners
    [daySelect, startTime, endTime, duration, maxBookings].forEach(element => {
        element.addEventListener('change', updatePreview);
    });

    // Initial preview update
    updatePreview();
});
</script>
@endsection
