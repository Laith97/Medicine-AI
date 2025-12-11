@extends('layouts.kiosk')

@section('title', 'Payment Amount | MedCura AI Kiosk')

@section('content')
<div class="text-center">
    <!-- Progress Indicator -->
    <div class="kiosk-progress mb-4">
        <div class="progress-step completed">1</div>
        <div class="progress-step completed">2</div>
        <div class="progress-step completed">3</div>
        <div class="progress-step active">4</div>
    </div>

    <!-- Payment Header -->
    <div class="mb-5">
        <i class="fas fa-credit-card text-success" style="font-size: 4rem; margin-bottom: 2rem;"></i>
        <h1 class="display-5 fw-bold mb-3">Payment Required</h1>
        <p class="lead text-muted mb-4" style="font-size: 1.3rem;">
            Please review the amount and proceed with payment
        </p>
    </div>

    <!-- Payment Summary -->
    <div class="kiosk-card mb-4">
        <h3 class="h5 mb-4 text-center">Payment Summary</h3>

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

        <!-- Amount Display -->
        <div class="text-center mb-4">
            <div class="mb-3">
                <span class="text-muted">Total Amount Due</span>
            </div>
            <div class="display-1 fw-bold text-success mb-3">
                ${{ number_format(($appointment->payment_amount ?? $appointment->doctor->consultation_fee) / 100, 2) }}
            </div>
            <div class="text-muted">
                <small>Consultation Fee</small>
            </div>
        </div>

        <!-- Payment Breakdown (if applicable) -->
        @php
            $baseFee = $appointment->doctor->consultation_fee;
            $totalAmount = $appointment->payment_amount ?? $baseFee;
            $taxAmount = $totalAmount - $baseFee;
        @endphp

        @if($taxAmount > 0)
        <div class="row text-center mb-4">
            <div class="col-6">
                <div class="border-end">
                    <div class="text-muted small">Consultation</div>
                    <div class="h6">${{ number_format($baseFee / 100, 2) }}</div>
                </div>
            </div>
            <div class="col-6">
                <div class="text-muted small">Taxes & Fees</div>
                <div class="h6">${{ number_format($taxAmount / 100, 2) }}</div>
            </div>
        </div>
        @endif
    </div>

    <!-- Payment Options -->
    <div class="kiosk-card mb-4">
        <h3 class="h5 mb-4 text-center">Choose Payment Method</h3>

        <div class="row g-3">
            <div class="col-md-6">
                <a href="{{ route('kiosk.payment.card', $appointment) }}" class="kiosk-btn kiosk-btn-success w-100">
                    <i class="fas fa-credit-card me-2"></i>
                    <div class="text-start">
                        <div class="fw-bold">Card Payment</div>
                        <small>Credit, Debit, or Contactless</small>
                    </div>
                </a>
            </div>

            <div class="col-md-6">
                <button class="kiosk-btn kiosk-btn-secondary w-100" onclick="showOtherMethods()">
                    <i class="fas fa-wallet me-2"></i>
                    <div class="text-start">
                        <div class="fw-bold">Other Methods</div>
                        <small>Cash, Insurance, etc.</small>
                    </div>
                </button>
            </div>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="row g-3">
        <div class="col-md-6">
            <a href="{{ route('kiosk.checkin.success', $appointment) }}" class="kiosk-btn kiosk-btn-secondary w-100">
                <i class="fas fa-times me-2"></i>
                Skip Payment
            </a>
        </div>
        <div class="col-md-6">
            <a href="{{ route('kiosk.welcome') }}" class="kiosk-btn kiosk-btn-danger w-100">
                <i class="fas fa-arrow-left me-2"></i>
                Cancel & Return
            </a>
        </div>
    </div>

    <!-- Help Text -->
    <div class="mt-4">
        <p class="text-muted small">
            <i class="fas fa-info-circle me-2"></i>
            Payment is required to confirm your appointment. You can also pay at the front desk if preferred.
        </p>
    </div>
</div>

<!-- Other Payment Methods Modal -->
<div class="modal fade" id="otherMethodsModal" tabindex="-1" aria-labelledby="otherMethodsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="otherMethodsModalLabel">Other Payment Methods</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="kiosk-card text-center h-100">
                            <i class="fas fa-money-bill-wave text-success fa-2x mb-3"></i>
                            <h6>Cash Payment</h6>
                            <p class="small text-muted">Pay at the front desk</p>
                            <button class="kiosk-btn kiosk-btn-success w-100" onclick="selectCashPayment()">
                                Select Cash
                            </button>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="kiosk-card text-center h-100">
                            <i class="fas fa-shield-alt text-info fa-2x mb-3"></i>
                            <h6>Insurance</h6>
                            <p class="small text-muted">Use insurance coverage</p>
                            <button class="kiosk-btn kiosk-btn-secondary w-100" onclick="selectInsurance()">
                                Use Insurance
                            </button>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="kiosk-card text-center h-100">
                            <i class="fas fa-mobile-alt text-primary fa-2x mb-3"></i>
                            <h6>Mobile Payment</h6>
                            <p class="small text-muted">Apple Pay, Google Pay</p>
                            <button class="kiosk-btn kiosk-btn-primary w-100" onclick="selectMobilePayment()">
                                Mobile Pay
                            </button>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="kiosk-card text-center h-100">
                            <i class="fas fa-user-headset text-warning fa-2x mb-3"></i>
                            <h6>Front Desk</h6>
                            <p class="small text-muted">Pay at reception</p>
                            <button class="kiosk-btn kiosk-btn-warning w-100" onclick="payAtFrontDesk()">
                                Front Desk
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Back to Card Payment</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Voice guidance
    const amount = '{{ number_format(($appointment->payment_amount ?? $appointment->doctor->consultation_fee) / 100, 2) }}';
    speakText(`Payment required. Total amount due is ${amount} dollars. Touch card payment to proceed with payment, or touch other methods for alternative payment options.`);

    // Auto-focus on card payment button
    const cardPaymentBtn = document.querySelector('a[href*="payment/card"]');
    if (cardPaymentBtn) {
        cardPaymentBtn.focus();
    }
});

function showOtherMethods() {
    const modal = new bootstrap.Modal(document.getElementById('otherMethodsModal'));
    modal.show();
    speakText('Other payment methods. Select your preferred payment option.');
}

function selectCashPayment() {
    speakText('Cash payment selected. Please proceed to the front desk to complete your payment.');
    alert('Please proceed to the front desk to complete your cash payment.\n\nYour appointment has been confirmed.');
    window.location.href = '{{ route("kiosk.checkin.success", $appointment) }}';
}

function selectInsurance() {
    speakText('Insurance payment selected. Please proceed to the front desk for insurance verification.');
    alert('Please proceed to the front desk for insurance verification and payment.\n\nYour appointment has been confirmed.');
    window.location.href = '{{ route("kiosk.checkin.success", $appointment) }}';
}

function selectMobilePayment() {
    speakText('Mobile payment selected. Please follow the instructions on the screen.');
    alert('Mobile payment feature is not available at this kiosk.\n\nPlease use card payment or pay at the front desk.');
}

function payAtFrontDesk() {
    speakText('Front desk payment selected. Please proceed to the front desk to complete your payment.');
    alert('Please proceed to the front desk to complete your payment.\n\nYour appointment has been confirmed.');
    window.location.href = '{{ route("kiosk.checkin.success", $appointment) }}';
}

function speakText(text) {
    if (typeof window.speakText === 'function') {
        window.speakText(text);
    }
}
</script>
@endsection
