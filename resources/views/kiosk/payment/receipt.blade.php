@extends('layouts.kiosk')

@section('title', 'Payment Receipt | MedCura AI Kiosk')

@section('content')
<div class="text-center">
    <!-- Success Header -->
    <div class="mb-5">
        <div class="success-checkmark mb-4">
            <i class="fas fa-check-circle text-success" style="font-size: 5rem;"></i>
        </div>
        <h1 class="display-5 fw-bold text-success mb-3">Payment Successful!</h1>
        <p class="lead text-muted mb-4" style="font-size: 1.3rem;">
            Your payment has been processed successfully
        </p>
    </div>

    <!-- Receipt Card -->
    <div class="kiosk-card mb-4">
        <div class="text-center mb-4">
            <h2 class="h4 mb-3">Payment Receipt</h2>
            <div class="border-bottom pb-3 mb-3">
                <strong class="text-muted">Receipt #{{ strtoupper(substr(md5(time() . $appointment->id), 0, 8)) }}</strong>
            </div>
        </div>

        <!-- Appointment Details -->
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
                </div>
            </div>
        </div>

        <hr class="my-4">

        <!-- Payment Details -->
        <div class="mb-4">
            <h4 class="h6 mb-3 text-center">Payment Details</h4>

            <div class="row text-center">
                <div class="col-md-4">
                    <div class="border-end border-md-end-0 border-bottom border-md-bottom-0 pb-3 pb-md-0">
                        <div class="text-muted small">Payment Method</div>
                        <div class="fw-bold">Credit Card</div>
                        <div class="small text-muted">**** **** **** 1234</div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="border-end border-md-end-0 border-bottom border-md-bottom-0 pb-3 pb-md-0">
                        <div class="text-muted small">Transaction ID</div>
                        <div class="fw-bold">{{ strtoupper(substr(md5(time() . rand()), 0, 12)) }}</div>
                        <div class="small text-muted">{{ now()->format('M j, Y g:i A') }}</div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="text-muted small">Amount Paid</div>
                    <div class="h4 text-success fw-bold">
                        ${{ number_format(($appointment->payment_amount ?? $appointment->doctor->consultation_fee) / 100, 2) }}
                    </div>
                    <div class="small text-muted">Paid in full</div>
                </div>
            </div>
        </div>

        <!-- Receipt Footer -->
        <div class="border-top pt-3 mt-4">
            <div class="row text-center small text-muted">
                <div class="col-md-6">
                    <div>MedCura AI Healthcare</div>
                    <div>123 Medical Center Drive</div>
                    <div>Healthcare City, HC 12345</div>
                </div>
                <div class="col-md-6">
                    <div>Phone: (555) 123-4567</div>
                    <div>Email: info@medcura.ai</div>
                    <div>Website: www.medcura.ai</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Next Steps -->
    <div class="kiosk-card mb-4">
        <h3 class="h5 mb-4 text-center">What's Next?</h3>

        <div class="row g-4">
            <div class="col-md-6">
                <div class="text-center">
                    <i class="fas fa-calendar-check text-primary mb-3" style="font-size: 2.5rem;"></i>
                    <h4 class="h6 mb-2">Appointment Confirmed</h4>
                    <p class="small text-muted">
                        Your appointment has been confirmed and you will receive a confirmation email shortly.
                    </p>
                </div>
            </div>

            <div class="col-md-6">
                <div class="text-center">
                    <i class="fas fa-envelope text-info mb-3" style="font-size: 2.5rem;"></i>
                    <h4 class="h6 mb-2">Check Your Email</h4>
                    <p class="small text-muted">
                        A receipt and appointment details have been sent to your email address.
                    </p>
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
            <button class="kiosk-btn kiosk-btn-success w-100" onclick="printReceipt()">
                <i class="fas fa-print me-2"></i>
                Print Receipt
            </button>
        </div>
    </div>

    <!-- Help Text -->
    <div class="mt-4">
        <p class="text-muted small">
            <i class="fas fa-info-circle me-2"></i>
            Thank you for choosing MedCura AI. Please proceed to your appointment at the scheduled time.
            If you have any questions, please ask a staff member.
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
    speakText('Payment successful. Your appointment has been confirmed. Thank you for choosing MedCura AI.');

    // Auto-print after a delay (optional)
    setTimeout(() => {
        if (window.location.search.includes('auto_print=true')) {
            printReceipt();
        }
    }, 2000);

    // Add print styles
    const printStyles = `
        @media print {
            body * { visibility: hidden; }
            .kiosk-card, .kiosk-card * { visibility: visible; }
            .kiosk-card { position: absolute; left: 0; top: 0; width: 100%; }
            .kiosk-btn, .kiosk-footer, .emergency-btn { display: none !important; }
        }
    `;

    const style = document.createElement('style');
    style.textContent = printStyles;
    document.head.appendChild(style);
});

function printReceipt() {
    speakText('Printing receipt. Please wait.');
    window.print();
}

function speakText(text) {
    if (typeof window.speakText === 'function') {
        window.speakText(text);
    }
}

// Auto-return to home after 2 minutes of inactivity
let autoReturnTimer;
function resetAutoReturnTimer() {
    clearTimeout(autoReturnTimer);
    autoReturnTimer = setTimeout(() => {
        speakText('Returning to home screen due to inactivity.');
        window.location.href = '{{ route("kiosk.welcome") }}';
    }, 120000); // 2 minutes
}

// Reset timer on any interaction
document.addEventListener('click', resetAutoReturnTimer);
document.addEventListener('touchstart', resetAutoReturnTimer);
document.addEventListener('keydown', resetAutoReturnTimer);

// Start the timer
resetAutoReturnTimer();
</script>
@endsection
