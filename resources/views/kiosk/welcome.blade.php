@extends('layouts.kiosk')

@section('title', 'Welcome to MedCura AI Kiosk')

@section('content')
<div class="text-center">
    <!-- Welcome Header -->
    <div class="mb-5">
        <i class="fas fa-hospital text-primary" style="font-size: 4rem; margin-bottom: 2rem;"></i>
        <h1 class="display-4 fw-bold text-primary mb-3">Welcome to MedCura AI</h1>
        <p class="lead text-muted mb-4" style="font-size: 1.5rem;">
            Your healthcare companion is ready to assist you
        </p>
    </div>

    <!-- Main Options -->
    <div class="row g-4 mb-5">
        <div class="col-md-6">
            <div class="kiosk-card text-center h-100">
                <div class="mb-4">
                    <i class="fas fa-calendar-check text-success" style="font-size: 3rem;"></i>
                </div>
                <h2 class="h4 mb-3">Check In for Appointment</h2>
                <p class="text-muted mb-4">
                    Check in for your scheduled appointment and complete any necessary payments.
                </p>
                <a href="{{ route('kiosk.checkin.start') }}" class="kiosk-btn kiosk-btn-success w-100">
                    <i class="fas fa-arrow-right me-2"></i>
                    Start Check-In
                </a>
            </div>
        </div>

        <div class="col-md-6">
            <div class="kiosk-card text-center h-100">
                <div class="mb-4">
                    <i class="fas fa-info-circle text-info" style="font-size: 3rem;"></i>
                </div>
                <h2 class="h4 mb-3">Information & Help</h2>
                <p class="text-muted mb-4">
                    Get information about our services, find your way around, or get assistance.
                </p>
                <button class="kiosk-btn kiosk-btn-secondary w-100" onclick="showHelp()">
                    <i class="fas fa-question-circle me-2"></i>
                    Get Help
                </button>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="kiosk-card">
        <h3 class="h5 mb-4 text-center">Quick Actions</h3>
        <div class="row g-3">
            <div class="col-6 col-md-3">
                <button class="kiosk-btn kiosk-btn-primary w-100" onclick="speakText('Please touch the check-in button to begin your appointment check-in process.')">
                    <i class="fas fa-volume-up me-2"></i>
                    Voice Help
                </button>
            </div>
            <div class="col-6 col-md-3">
                <button class="kiosk-btn kiosk-btn-primary w-100" onclick="toggleContrast()">
                    <i class="fas fa-adjust me-2"></i>
                    High Contrast
                </button>
            </div>
            <div class="col-6 col-md-3">
                <button class="kiosk-btn kiosk-btn-primary w-100" onclick="toggleVoice()">
                    <i class="fas fa-microphone me-2"></i>
                    Voice Control
                </button>
            </div>
            <div class="col-6 col-md-3">
                <button class="kiosk-btn kiosk-btn-danger w-100" onclick="callEmergency()">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Emergency
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Help Modal -->
<div class="modal fade" id="helpModal" tabindex="-1" aria-labelledby="helpModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="helpModalLabel">How to Use the Kiosk</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row g-4">
                    <div class="col-md-6">
                        <h6><i class="fas fa-calendar-check text-success me-2"></i>Check-In Process</h6>
                        <ol class="small">
                            <li>Touch "Start Check-In" to begin</li>
                            <li>Search for your appointment</li>
                            <li>Verify your identity</li>
                            <li>Complete payment if required</li>
                            <li>Receive confirmation</li>
                        </ol>
                    </div>
                    <div class="col-md-6">
                        <h6><i class="fas fa-universal-access text-info me-2"></i>Accessibility Features</h6>
                        <ul class="small">
                            <li><strong>Large Buttons:</strong> Easy to touch</li>
                            <li><strong>Voice Guidance:</strong> Audio assistance</li>
                            <li><strong>High Contrast:</strong> Better visibility</li>
                            <li><strong>Emergency Button:</strong> Always available</li>
                        </ul>
                    </div>
                </div>

                <hr>

                <div class="text-center">
                    <p class="mb-2"><strong>Need more help?</strong></p>
                    <p class="small text-muted">
                        Please ask a staff member for assistance, or call our help desk at extension 123.
                    </p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-focus on the main check-in button for keyboard navigation
    const checkInButton = document.querySelector('a[href*="checkin"]');
    if (checkInButton) {
        checkInButton.focus();
    }

    // Welcome message
    setTimeout(() => {
        speakText('Welcome to MedCura AI Kiosk. Touch the check-in button to begin, or touch get help for assistance.');
    }, 1000);
});

function showHelp() {
    const modal = new bootstrap.Modal(document.getElementById('helpModal'));
    modal.show();
    speakText('Help information displayed. Please read the instructions or ask a staff member for assistance.');
}

function speakText(text) {
    if (typeof window.speakText === 'function') {
        window.speakText(text);
    }
}

function toggleContrast() {
    if (typeof window.toggleContrast === 'function') {
        window.toggleContrast();
    }
}

function toggleVoice() {
    if (typeof window.toggleVoice === 'function') {
        window.toggleVoice();
    }
}

function callEmergency() {
    if (typeof window.callEmergency === 'function') {
        window.callEmergency();
    }
}
</script>
@endsection
