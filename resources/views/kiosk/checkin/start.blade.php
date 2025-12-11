@extends('layouts.kiosk')

@section('title', 'Start Check-In | MedCura AI Kiosk')

@section('content')
<div class="text-center">
    <!-- Progress Indicator -->
    <div class="kiosk-progress mb-4">
        <div class="progress-step active">1</div>
        <div class="progress-step">2</div>
        <div class="progress-step">3</div>
        <div class="progress-step">4</div>
    </div>

    <!-- Check-In Header -->
    <div class="mb-5">
        <i class="fas fa-calendar-check text-success" style="font-size: 4rem; margin-bottom: 2rem;"></i>
        <h1 class="display-5 fw-bold mb-3">Appointment Check-In</h1>
        <p class="lead text-muted mb-4" style="font-size: 1.3rem;">
            Let's get you checked in for your appointment
        </p>
    </div>

    <!-- Instructions -->
    <div class="kiosk-card mb-5">
        <h3 class="h5 mb-4">How would you like to find your appointment?</h3>

        <div class="row g-4">
            <div class="col-md-6">
                <div class="text-center">
                    <i class="fas fa-id-card text-primary mb-3" style="font-size: 2.5rem;"></i>
                    <h4 class="h6 mb-2">By Appointment Number</h4>
                    <p class="small text-muted mb-3">
                        Enter the appointment number from your confirmation email or SMS.
                    </p>
                    <a href="{{ route('kiosk.checkin.search') }}?type=appointment_number" class="kiosk-btn kiosk-btn-primary w-100">
                        Use Appointment Number
                    </a>
                </div>
            </div>

            <div class="col-md-6">
                <div class="text-center">
                    <i class="fas fa-user text-info mb-3" style="font-size: 2.5rem;"></i>
                    <h4 class="h6 mb-2">By Personal Information</h4>
                    <p class="small text-muted mb-3">
                        Search using your name, phone number, or email address.
                    </p>
                    <a href="{{ route('kiosk.checkin.search') }}?type=personal" class="kiosk-btn kiosk-btn-secondary w-100">
                        Use Personal Info
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Alternative Options -->
    <div class="kiosk-card">
        <h4 class="h6 mb-4 text-center">Other Check-In Methods</h4>

        <div class="row g-3">
            <div class="col-md-4">
                <button class="kiosk-btn kiosk-btn-secondary w-100" onclick="scanQRCode()">
                    <i class="fas fa-qrcode me-2"></i>
                    Scan QR Code
                </button>
            </div>

            <div class="col-md-4">
                <button class="kiosk-btn kiosk-btn-secondary w-100" onclick="useBiometric()">
                    <i class="fas fa-fingerprint me-2"></i>
                    Biometric ID
                </button>
            </div>

            <div class="col-md-4">
                <button class="kiosk-btn kiosk-btn-secondary w-100" onclick="callStaff()">
                    <i class="fas fa-user-headset me-2"></i>
                    Call for Help
                </button>
            </div>
        </div>
    </div>

    <!-- Back Button -->
    <div class="mt-4">
        <a href="{{ route('kiosk.welcome') }}" class="kiosk-btn kiosk-btn-secondary">
            <i class="fas fa-arrow-left me-2"></i>
            Back to Main Menu
        </a>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Voice guidance
    speakText('Appointment check-in. Touch the appointment number button if you have your appointment number, or touch personal information to search by name, phone, or email.');

    // Auto-focus on first option
    const firstButton = document.querySelector('.kiosk-btn-primary');
    if (firstButton) {
        firstButton.focus();
    }
});

function scanQRCode() {
    speakText('QR code scanning is not available at this kiosk. Please use appointment number or personal information instead.');
    alert('QR code scanning is not available at this kiosk.\n\nPlease use your appointment number or personal information to check in.');
}

function useBiometric() {
    speakText('Biometric identification is not available at this kiosk. Please use appointment number or personal information instead.');
    alert('Biometric identification is not available at this kiosk.\n\nPlease use your appointment number or personal information to check in.');
}

function callStaff() {
    speakText('Calling for assistance. A staff member will be with you shortly.');
    alert('A staff member has been notified and will assist you shortly.\n\nPlease wait for assistance.');
}

function speakText(text) {
    if (typeof window.speakText === 'function') {
        window.speakText(text);
    }
}
</script>
@endsection
