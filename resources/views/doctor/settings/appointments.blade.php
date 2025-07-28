@extends('master')

@section('title', 'Appointment Settings')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/doctor-dashboard.css') }}">
<link rel="stylesheet" href="{{ asset('demos/medical/medical.css') }}">
@endpush

@section('content')
<div class="dashboard-container">
    <div class="container">
        <!-- Header -->
        <div class="dashboard-header d-flex justify-content-between align-items-center mb-4">
            <div class="d-flex align-items-center">
                <a href="{{ route('doctor.dashboard') }}" class="btn btn-secondary-custom me-3">
                    <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                </a>
                <div>
                    <h2 class="h1 mb-1">Appointment Settings</h2>
                    <p class="text-muted mb-0">Manage your appointment type preferences</p>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-8">
                <!-- Appointment Types Settings -->
                <div class="table-card mb-4">
                    <div class="p-4">
                        <h5 class="mb-4">
                            <i class="fas fa-calendar-check text-primary me-2"></i>
                            Appointment Types
                        </h5>
                        <p class="text-muted mb-4">
                            Choose which appointment types you want to offer to your patients.
                            Only enabled types will appear as options when patients book appointments.
                        </p>

                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        @if (session('success'))
                            <div class="alert alert-success">
                                {{ session('success') }}
                            </div>
                        @endif

                        <form method="POST" action="{{ route('doctor.settings.appointments.update') }}">
                            @csrf
                            @method('PUT')

                            <div class="row">
                                @foreach($appointmentTypes as $type => $label)
                                    <div class="col-md-6 mb-4">
                                        <div class="card h-100 appointment-type-card {{ $doctor->isAppointmentTypeEnabled($type) ? 'enabled' : 'disabled' }}">
                                            <div class="card-body d-flex align-items-center">
                                                <div class="me-3">
                                                    <div class="appointment-type-icon">
                                                        @if($type === 'in_person')
                                                            <i class="fas fa-hospital text-primary"></i>
                                                        @elseif($type === 'video_call')
                                                            <i class="fas fa-video text-success"></i>
                                                        @else
                                                            <i class="fas fa-phone text-info"></i>
                                                        @endif
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <h6 class="mb-1">{{ $label }}</h6>
                                                    <small class="text-muted">
                                                        @if($type === 'in_person')
                                                            Face-to-face consultations at your clinic
                                                        @elseif($type === 'video_call')
                                                            Online video consultations
                                                        @else
                                                            Phone call consultations
                                                        @endif
                                                    </small>
                                                </div>
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input"
                                                           type="checkbox"
                                                           name="appointment_types[]"
                                                           value="{{ $type }}"
                                                           id="type_{{ $type }}"
                                                           {{ $doctor->isAppointmentTypeEnabled($type) ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="type_{{ $type }}">
                                                        {{ $doctor->isAppointmentTypeEnabled($type) ? 'Enabled' : 'Disabled' }}
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <div class="d-flex justify-content-between align-items-center mt-4">
                                <div class="text-muted">
                                    <small>
                                        <i class="fas fa-info-circle me-1"></i>
                                        At least one appointment type must be enabled
                                    </small>
                                </div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Save Changes
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Current Status -->
                <div class="table-card mb-4">
                    <div class="p-4">
                        <h5 class="mb-3">
                            <i class="fas fa-chart-bar text-success me-2"></i>
                            Current Status
                        </h5>

                        @php
                            $enabledTypes = $doctor->getEnabledAppointmentTypes();
                            $totalTypes = count($appointmentTypes);
                            $enabledCount = count($enabledTypes);
                        @endphp

                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-1">
                                <small class="text-muted">Enabled Types</small>
                                <small class="text-muted">{{ $enabledCount }}/{{ $totalTypes }}</small>
                            </div>
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar bg-success"
                                     style="width: {{ ($enabledCount / $totalTypes) * 100 }}%"></div>
                            </div>
                        </div>

                        <div class="enabled-types-list">
                            @if(count($enabledTypes) > 0)
                                <h6 class="mb-2">Enabled Types:</h6>
                                @foreach($enabledTypes as $type)
                                    <div class="d-flex align-items-center mb-2">
                                        <div class="me-2">
                                            @if($type === 'in_person')
                                                <i class="fas fa-hospital text-primary"></i>
                                            @elseif($type === 'video_call')
                                                <i class="fas fa-video text-success"></i>
                                            @else
                                                <i class="fas fa-phone text-info"></i>
                                            @endif
                                        </div>
                                        <small>{{ $appointmentTypes[$type] }}</small>
                                    </div>
                                @endforeach
                            @else
                                <div class="text-center text-muted">
                                    <i class="fas fa-exclamation-triangle mb-2"></i>
                                    <p class="mb-0">No appointment types enabled</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Help & Tips -->
                <div class="table-card">
                    <div class="p-4">
                        <h5 class="mb-3">
                            <i class="fas fa-lightbulb text-warning me-2"></i>
                            Tips
                        </h5>
                        <div class="tips-list">
                            <div class="tip-item mb-3">
                                <div class="d-flex">
                                    <div class="me-2">
                                        <i class="fas fa-check-circle text-success"></i>
                                    </div>
                                    <div>
                                        <small class="text-muted">
                                            Enable multiple appointment types to give patients more flexibility
                                        </small>
                                    </div>
                                </div>
                            </div>
                            <div class="tip-item mb-3">
                                <div class="d-flex">
                                    <div class="me-2">
                                        <i class="fas fa-check-circle text-success"></i>
                                    </div>
                                    <div>
                                        <small class="text-muted">
                                            Video calls can help you reach patients who can't visit in person
                                        </small>
                                    </div>
                                </div>
                            </div>
                            <div class="tip-item">
                                <div class="d-flex">
                                    <div class="me-2">
                                        <i class="fas fa-check-circle text-success"></i>
                                    </div>
                                    <div>
                                        <small class="text-muted">
                                            You can change these settings anytime based on your availability
                                        </small>
                                    </div>
                                </div>
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
    // Handle appointment type card styling
    const checkboxes = document.querySelectorAll('input[name="appointment_types[]"]');

    checkboxes.forEach(function(checkbox) {
        const card = checkbox.closest('.appointment-type-card');
        const label = checkbox.nextElementSibling;

        function updateCardState() {
            if (checkbox.checked) {
                card.classList.remove('disabled');
                card.classList.add('enabled');
                label.textContent = 'Enabled';
            } else {
                card.classList.remove('enabled');
                card.classList.add('disabled');
                label.textContent = 'Disabled';
            }
        }

        // Initial state
        updateCardState();

        // Handle changes
        checkbox.addEventListener('change', updateCardState);
    });
});
</script>
@endpush

@push('styles')
<style>
.appointment-type-card {
    border: 2px solid #e9ecef;
    transition: all 0.3s ease;
    cursor: pointer;
}

.appointment-type-card.enabled {
    border-color: #28a745;
    background-color: #f8fff9;
}

.appointment-type-card.disabled {
    border-color: #e9ecef;
    background-color: #f8f9fa;
}

.appointment-type-card:hover {
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.appointment-type-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background-color: rgba(0,123,255,0.1);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
}

.form-check-input:checked {
    background-color: #28a745;
    border-color: #28a745;
}

.tip-item {
    padding: 8px 0;
    border-bottom: 1px solid #f1f3f4;
}

.tip-item:last-child {
    border-bottom: none;
}
</style>
@endpush
