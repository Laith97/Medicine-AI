@extends('layouts.kiosk')

@section('title', 'Verify Appointment | MedCura AI Kiosk')

@section('content')
<div class="text-center">
    <!-- Progress Indicator -->
    <div class="kiosk-progress mb-4">
        <div class="progress-step completed">1</div>
        <div class="progress-step completed">2</div>
        <div class="progress-step active">3</div>
        <div class="progress-step">4</div>
    </div>

    <!-- Verification Header -->
    <div class="mb-5">
        <i class="fas fa-shield-alt text-warning" style="font-size: 4rem; margin-bottom: 2rem;"></i>
        <h1 class="display-5 fw-bold mb-3">Verify Your Appointment</h1>
        <p class="lead text-muted mb-4" style="font-size: 1.3rem;">
            Please confirm this is your appointment
        </p>
    </div>

    <!-- Appointment Details -->
    <div class="kiosk-card mb-4">
        <h3 class="h5 mb-4 text-center">Appointment Details</h3>

        <div class="row g-4">
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
                        <strong class="text-muted">Date & Time:</strong><br>
                        <span class="h5">{{ $appointment->appointment_date->format('M j, Y') }}</span><br>
                        <span class="h6 text-muted">{{ $appointment->appointment_date->format('g:i A') }}</span>
                    </div>

                    <div class="mb-3">
                        <strong class="text-muted">Type:</strong><br>
                        <span class="h6">{{ ucfirst(str_replace('_', ' ', $appointment->appointment_type)) }}</span>
                    </div>

                    <div class="mb-3">
                        <strong class="text-muted">Duration:</strong><br>
                        <span class="h6">{{ $appointment->doctor->appointment_duration }} minutes</span>
                    </div>
                </div>
            </div>
        </div>

        <hr class="my-4">

        <div class="text-center">
            <div class="mb-3">
                <strong class="text-muted">Appointment Number:</strong><br>
                <span class="h4 text-primary">{{ $appointment->appointment_number }}</span>
            </div>

            @if($appointment->patient)
            <div class="mb-3">
                <strong class="text-muted">Patient:</strong><br>
                <span class="h5">{{ $appointment->patient->name }}</span>
            </div>
            @endif

            @if($appointment->reason)
            <div class="mb-3">
                <strong class="text-muted">Reason:</strong><br>
                <span class="small">{{ $appointment->reason }}</span>
            </div>
            @endif
        </div>
    </div>

    <!-- Verification Methods -->
    <div class="kiosk-card mb-4">
        <h3 class="h5 mb-4 text-center">How would you like to verify your identity?</h3>

        <form method="POST" action="{{ route('kiosk.checkin.confirm', $appointment) }}" id="verificationForm">
            @csrf

            <div class="row g-3">
                <div class="col-md-6">
                    <input type="radio" class="btn-check" name="verification_method" id="qr_code" value="qr_code" checked>
                    <label class="kiosk-btn kiosk-btn-secondary w-100" for="qr_code">
                        <i class="fas fa-qrcode me-2"></i>
                        <div class="text-start">
                            <div class="fw-bold">QR Code</div>
                            <small>Scan QR code from your phone</small>
                        </div>
                    </label>
                </div>

                <div class="col-md-6">
                    <input type="radio" class="btn-check" name="verification_method" id="id_card" value="id_card">
                    <label class="kiosk-btn kiosk-btn-secondary w-100" for="id_card">
                        <i class="fas fa-id-card me-2"></i>
                        <div class="text-start">
                            <div class="fw-bold">ID Card</div>
                            <small>Present government-issued ID</small>
                        </div>
                    </label>
                </div>

                <div class="col-md-6">
                    <input type="radio" class="btn-check" name="verification_method" id="biometric" value="biometric">
                    <label class="kiosk-btn kiosk-btn-secondary w-100" for="biometric">
                        <i class="fas fa-fingerprint me-2"></i>
                        <div class="text-start">
                            <div class="fw-bold">Biometric</div>
                            <small>Fingerprint or facial recognition</small>
                        </div>
                    </label>
                </div>

                <div class="col-md-6">
                    <input type="radio" class="btn-check" name="verification_method" id="manual" value="manual">
                    <label class="kiosk-btn kiosk-btn-secondary w-100" for="manual">
                        <i class="fas fa-user-check me-2"></i>
                        <div class="text-start">
                            <div class="fw-bold">Manual Check</div>
                            <small>Staff verification required</small>
                        </div>
                    </label>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="row g-3 mt-4">
                <div class="col-md-6">
                    <button type="submit" class="kiosk-btn kiosk-btn-success w-100" id="confirmButton">
                        <i class="fas fa-check me-2"></i>
                        Confirm & Check In
                    </button>
                </div>
                <div class="col-md-6">
                    <a href="{{ route('kiosk.checkin.search') }}" class="kiosk-btn kiosk-btn-secondary w-100">
                        <i class="fas fa-arrow-left me-2"></i>
                        Different Appointment
                    </a>
                </div>
            </div>
        </form>
    </div>

    <!-- Help Text -->
    <div class="alert alert-info">
        <i class="fas fa-info-circle me-2"></i>
        <strong>Need help?</strong> If this is not your appointment or you need assistance,
        please touch the "Call for Help" button or ask a staff member.
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const verificationForm = document.getElementById('verificationForm');
    const confirmButton = document.getElementById('confirmButton');
    const verificationMethods = document.querySelectorAll('input[name="verification_method"]');

    // Handle verification method selection
    verificationMethods.forEach(method => {
        method.addEventListener('change', function() {
            const methodValue = this.value;

            // Provide feedback based on selection
            switch(methodValue) {
                case 'qr_code':
                    speakText('QR code verification selected. Please scan the QR code from your appointment confirmation.');
                    showQRScanner();
                    break;
                case 'id_card':
                    speakText('ID card verification selected. Please present your government-issued ID to the scanner.');
                    hideQRScanner();
                    break;
                case 'biometric':
                    speakText('Biometric verification selected. Please place your finger on the scanner or look at the camera.');
                    hideQRScanner();
                    break;
                case 'manual':
                    speakText('Manual verification selected. A staff member will assist you shortly.');
                    hideQRScanner();
                    break;
            }
        });
    });

    // Form submission
    verificationForm.addEventListener('submit', function(e) {
        const selectedMethod = document.querySelector('input[name="verification_method"]:checked');

        if (!selectedMethod) {
            e.preventDefault();
            speakText('Please select a verification method.');
            alert('Please select a verification method.');
            return;
        }

        confirmButton.disabled = true;
        confirmButton.innerHTML = '<div class="kiosk-spinner"></div> Verifying...';
        speakText('Verifying your identity. Please wait.');
    });

    // Voice guidance
    setTimeout(() => {
        speakText('Appointment verification. Please review your appointment details and select how you would like to verify your identity.');
    }, 1000);

    // Auto-select first option
    const firstMethod = document.querySelector('input[name="verification_method"]');
    if (firstMethod) {
        firstMethod.checked = true;
        firstMethod.dispatchEvent(new Event('change'));
    }
});

function showQRScanner() {
    // In a real implementation, this would activate the QR scanner
    // For demo purposes, we'll just show a message
    if (!document.getElementById('qrScanner')) {
        const scannerDiv = document.createElement('div');
        scannerDiv.id = 'qrScanner';
        scannerDiv.className = 'kiosk-card mt-3';
        scannerDiv.innerHTML = `
            <div class="text-center">
                <i class="fas fa-qrcode fa-3x text-primary mb-3"></i>
                <h5>QR Code Scanner</h5>
                <p class="text-muted">Please scan the QR code from your appointment confirmation.</p>
                <div class="bg-dark rounded p-4 mb-3" style="height: 200px; display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-camera fa-2x text-white"></i>
                </div>
                <button class="kiosk-btn kiosk-btn-secondary" onclick="hideQRScanner()">Cancel</button>
            </div>
        `;

        document.querySelector('.kiosk-card').appendChild(scannerDiv);
    }
}

function hideQRScanner() {
    const scanner = document.getElementById('qrScanner');
    if (scanner) {
        scanner.remove();
    }
}

function speakText(text) {
    if (typeof window.speakText === 'function') {
        window.speakText(text);
    }
}
</script>
@endsection
