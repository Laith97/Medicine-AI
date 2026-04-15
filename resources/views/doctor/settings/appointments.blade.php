@extends('master')

@section('title', 'Appointment Settings')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/doctor-dashboard.css') }}">
<link rel="stylesheet" href="{{ asset('demos/medical/medical.css') }}">

<style>
/* Professional Dashboard Header Styling */
.dashboard-header {
    background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
    border-radius: 15px;
    padding: 2rem;
    margin-bottom: 2rem;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
    border: 1px solid rgba(222, 98, 98, 0.2);
    position: relative;
    overflow: hidden;
}

.dashboard-header::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(135deg, #DE6262 0%, #2c3e50 100%);
}

.dashboard-header h2 {
    color: #ffffff;
    font-weight: 700;
    font-size: 2.5rem;
    margin-bottom: 0.5rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.dashboard-header h2::before {
    content: '⚙️';
    font-size: 2rem;
}

.dashboard-header p {
    color: rgba(255, 255, 255, 0.9);
    font-size: 1.1rem;
    font-weight: 500;
    margin-bottom: 0;
}

/* Button styles within header */
.dashboard-header .btn {
    background: rgba(255, 255, 255, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.3);
    color: white;
    transition: all 0.3s ease;
}

.dashboard-header .btn:hover {
    background: rgba(255, 255, 255, 0.2);
    border-color: rgba(255, 255, 255, 0.5);
    color: white;
    transform: translateY(-1px);
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .dashboard-header {
        padding: 1.5rem;
        margin-bottom: 1.5rem;
    }

    .dashboard-header h2 {
        font-size: 2rem;
    }

    .dashboard-header p {
        font-size: 1rem;
    }
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
                <div class="d-flex align-items-center">
                    <a href="{{ route('doctor.dashboard') }}" class="btn" style="background: rgba(255,255,255,0.2); border: 1px solid rgba(255,255,255,0.3); color: white; margin-right: 1rem;">
                        <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                    </a>
                    <div>
                        <h2 class="h1 mb-1">Appointment Settings</h2>
                        <p class="text-muted mb-0">Manage your appointment type preferences</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container-fluid" style="background-color: #f8f9fa;">
    <div class="container">

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
