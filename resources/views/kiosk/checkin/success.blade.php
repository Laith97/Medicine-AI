@extends('layouts.kiosk')

@section('title', 'Check-In Successful | MedCura AI Kiosk')

@section('content')
<div class="text-center">
    <!-- Success Header -->
    <div class="mb-5">
        <div class="success-checkmark mb-4">
            <i class="fas fa-check-circle text-success" style="font-size: 5rem;"></i>
        </div>
        <h1 class="display-5 fw-bold text-success mb-3">Check-In Successful!</h1>
        <p class="lead text-muted mb-4" style="font-size: 1.3rem;">
            You have been successfully checked in for your appointment
        </p>
    </div>

    <!-- Appointment Confirmation Card -->
    <div class="kiosk-card mb-4">
        <h3 class="h5 mb-4 text-center">Appointment Confirmed</h3>

        <div class="row g-4 mb-4">
            <div class="col-md-6">
                <div class="text-center">
                    <div class="mb-3">
                        @if($appointment->doctor->profile_image)
                            <img src="{{ asset('storage/' . $appointment->doctor->profile_image) }}"
                                 alt="{{ $appointment->doctor->user->name }}"
                                 class="rounded-circle"
                                 style="width: 80px; height: 80px; object-fit: cover;">
                        @else
                            <div class="rounded-circle bg-primary d-flex align-items-center justify-content-center mx-auto"
                                 style="width: 80px; height: 80px;">
                                <i class="fas fa-user-md text-white fs-2"></i>
                            </div>
                        @endif
                    </div>
                    <h4 class="h6 mb-1">Dr. {{ $appointment->doctor->user->name }}</h4>
                    <p class="text-muted small mb-0">{{ $appointment->doctor->specialty->name }}</p>
                </div>
            </div>

            <div class="col-md-6">
                <div class="text-start">
                    <div class="mb-3">
                        <strong class="text-muted">Appointment:</strong><br>
                        <span class="h6">{{ $appointment->appointment_date->format('M j, Y g:i A') }}</span>
                    </div>

                    <div class="mb-3">
                        <strong class="text-muted">Type:</strong><br>
                        <span class="h6">{{ ucfirst(str_replace('_', ' ', $appointment->appointment_type)) }}</span>
                    </div>

                    <div class="mb-3">
                        <strong class="text-muted">Duration:</strong><br>
                        <span class="h6">{{ $appointment->doctor->appointment_duration }} minutes</span>
                    </div>

                    <div class="mb-3">
                        <strong class="text-muted">Location:</strong><br>
                        <span class="h6">Consultation Room</span>
                    </div>
                </div>
            </div>
        </div>

        <hr class="my-4">

        <!-- Check-In Details -->
        <div class="text-center mb-4">
            <div class="row">
                <div class="col-md-4">
                    <div class="border-end border-md-end-0 border-bottom border-md-bottom-0 pb-3 pb-md-0">
                        <div class="text-muted small">Check-In Time</div>
                        <div class="h5 text-success">{{ now()->format('g:i A') }}</div>
                        <div class="small text-muted">{{ now()->format('M j, Y') }}</div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="border-end border-md-end-0 border-bottom border-md-bottom-0 pb-3 pb-md-0">
                        <div class="text-muted small">Appointment Number</div>
                        <div class="h5 text-primary">{{ $appointment->appointment_number }}</div>
                        <div class="small text-muted">Keep this for reference</div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="text-muted small">Status</div>
                    <div class="h5 text-success">
                        <i class="fas fa-check-circle me-2"></i>Checked In
                    </div>
                    <div class="small text-muted">Ready for your appointment</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Next Steps -->
    <div class="kiosk-card mb-4">
        <h3 class="h5 mb-4 text-center">What Happens Next?</h3>

        <div class="row g-4">
            <div class="col-md-6">
                <div class="text-center">
                    <i class="fas fa-clock text-info mb-3" style="font-size: 2.5rem;"></i>
                    <h4 class="h6 mb-2">Wait for Your Turn</h4>
                    <p class="small text-muted">
                        Please have a seat in the waiting area. You will be called when it's your turn.
                    </p>
                </div>
            </div>

            <div class="col-md-6">
                <div class="text-center">
                    <i class="fas fa-envelope text-primary mb-3" style="font-size: 2.5rem;"></i>
                    <h4 class="h6 mb-2">Confirmation Sent</h4>
                    <p class="small text-muted">
                        A confirmation email with your appointment details has been sent to your email address.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Important Information -->
    <div class="kiosk-card mb-4">
        <h4 class="h6 mb-3 text-center text-primary">Important Information</h4>

        <div class="row text-start small">
            <div class="col-md-6">
                <div class="d-flex mb-2">
                    <i class="fas fa-check-circle text-success me-2 mt-1"></i>
                    <span>Please arrive 10-15 minutes before your appointment time</span>
                </div>
                <div class="d-flex mb-2">
                    <i class="fas fa-check-circle text-success me-2 mt-1"></i>
                    <span>Bring any relevant medical records or test results</span>
                </div>
            </div>
            <div class="col-md-6">
                <div class="d-flex mb-2">
                    <i class="fas fa-check-circle text-success me-2 mt-1"></i>
                    <span>Have your insurance card ready if applicable</span>
                </div>
                <div class="d-flex mb-2">
                    <i class="fas fa-check-circle text-success me-2 mt-1"></i>
                    <span>Turn off your mobile phone or set it to silent</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="row g-3">
        <div class="col-md-6">
            <a href="{{ route('kiosk.welcome') }}" class="kiosk-btn kiosk-btn-primary w-100">
                <i class="fas fa-home me-2"></i>
                Return to Home
            </a>
        </div>
        <div class="col-md-6">
            <button class="kiosk-btn kiosk-btn-success w-100" onclick="printConfirmation()">
                <i class="fas fa-print me-2"></i>
                Print Confirmation
            </button>
        </div>
    </div>

    <!-- Help Text -->
    <div class="mt-4">
        <p class="text-muted small">
            <i class="fas fa-info-circle me-2"></i>
            Thank you for choosing MedCura AI. If you have any questions, please speak with our reception staff.
        </p>
    </div>
</div>

<style>
.success-checkmark {
    animation: checkmark 0.8s ease-in-out;
}

@keyframes checkmark {
    0% {
        transform: scale(0);
        opacity: 0;
    }
    50% {
        transform: scale(1.2);
        opacity: 0.8;
    }
    100% {
        transform: scale(1);
        opacity: 1;
    }
}

@media print {
    .kiosk-btn, .kiosk-footer, .emergency-btn {
        display: none !important;
    }

    .kiosk-container {
        background: white !important;
        color: black !important;
    }

    .kiosk-card {
        background: white !important;
        border: 1px solid #000 !important;
        box-shadow: none !important;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Success animation and voice guidance
    speakText('Check-in successful. Your appointment has been confirmed. Please wait in the waiting area and you will be called when it is your turn.');

    // Auto-return to home after 3 minutes of inactivity
    let autoReturnTimer;
    function resetAutoReturnTimer() {
        clearTimeout(autoReturnTimer);
        autoReturnTimer = setTimeout(() => {
            speakText('Returning to home screen due to inactivity.');
            window.location.href = '{{ route("kiosk.welcome") }}';
        }, 180000); // 3 minutes
    }

    // Reset timer on any interaction
    document.addEventListener('click', resetAutoReturnTimer);
    document.addEventListener('touchstart', resetAutoReturnTimer);
    document.addEventListener('keydown', resetAutoReturnTimer);

    // Start the timer
    resetAutoReturnTimer();
});

function printConfirmation() {
    speakText('Printing appointment confirmation. Please wait.');
    window.print();
}

function speakText(text) {
    if (typeof window.speakText === 'function') {
        window.speakText(text);
    }
}
</script>
@endsection
