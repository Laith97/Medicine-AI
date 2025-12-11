@extends('layouts.kiosk')

@section('title', 'Card Payment | MedCura AI Kiosk')

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
        <i class="fas fa-credit-card text-primary" style="font-size: 4rem; margin-bottom: 2rem;"></i>
        <h1 class="display-5 fw-bold mb-3">Card Payment</h1>
        <p class="lead text-muted mb-4" style="font-size: 1.3rem;">
            Please insert or tap your card
        </p>
    </div>

    <!-- Amount Display -->
    <div class="kiosk-card mb-4">
        <div class="text-center">
            <div class="mb-3">
                <span class="text-muted h5">Amount to Pay</span>
            </div>
            <div class="display-2 fw-bold text-success mb-3">
                ${{ number_format(($appointment->payment_amount ?? $appointment->doctor->consultation_fee) / 100, 2) }}
            </div>
        </div>
    </div>

    <!-- Card Reader Interface -->
    <div class="kiosk-card mb-4">
        <div class="text-center">
            <div class="mb-4">
                <i class="fas fa-mobile-screen-button text-primary" style="font-size: 3rem;"></i>
            </div>
            <h3 class="h5 mb-3">Card Reader Ready</h3>
            <p class="text-muted mb-4">
                Please insert your card into the reader below, or tap your contactless card
            </p>

            <!-- Card Slot Visualization -->
            <div class="card-reader-container mb-4">
                <div class="card-reader">
                    <div class="card-slot">
                        <div class="card-chip">
                            <i class="fas fa-microchip text-warning"></i>
                        </div>
                        <div class="card-stripes">
                            <div class="stripe"></div>
                            <div class="stripe"></div>
                            <div class="stripe"></div>
                        </div>
                    </div>
                    <div class="reader-lights">
                        <div class="light red"></div>
                        <div class="light yellow"></div>
                        <div class="light green active"></div>
                    </div>
                </div>
                <div class="contactless-icon">
                    <i class="fas fa-wifi text-primary fa-2x"></i>
                    <div class="small text-muted mt-1">Contactless</div>
                </div>
            </div>

            <!-- Status Message -->
            <div id="cardStatus" class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                Ready to accept card payment
            </div>
        </div>
    </div>

    <!-- Payment Form (Hidden initially) -->
    <div class="kiosk-card" id="paymentForm" style="display: none;">
        <h3 class="h5 mb-4 text-center">Enter Payment Details</h3>

        <form method="POST" action="{{ route('kiosk.payment.process', $appointment) }}" id="cardPaymentForm">
            @csrf

            <!-- Card Number -->
            <div class="mb-3">
                <label for="card_number" class="form-label h6">Card Number</label>
                <input type="text" name="card_number" id="card_number" class="kiosk-input form-control text-center"
                       placeholder="0000 0000 0000 0000" maxlength="19" required>
            </div>

            <!-- Expiry and CVV -->
            <div class="row g-3 mb-3">
                <div class="col-6">
                    <label for="expiry" class="form-label h6">Expiry Date</label>
                    <input type="text" name="expiry" id="expiry" class="kiosk-input form-control text-center"
                           placeholder="MM/YY" maxlength="5" required>
                </div>
                <div class="col-6">
                    <label for="cvv" class="form-label h6">CVV</label>
                    <input type="text" name="cvv" id="cvv" class="kiosk-input form-control text-center"
                           placeholder="123" maxlength="4" required>
                </div>
            </div>

            <!-- Cardholder Name -->
            <div class="mb-4">
                <label for="cardholder_name" class="form-label h6">Cardholder Name</label>
                <input type="text" name="cardholder_name" id="cardholder_name" class="kiosk-input form-control text-center"
                       placeholder="John Doe" required>
            </div>

            <!-- Virtual Keyboard -->
            <div class="mb-4">
                <div class="row g-2" id="cardKeyboard">
                    <div class="col-3"><button type="button" class="kiosk-btn kiosk-btn-primary w-100 keyboard-key" data-key="1">1</button></div>
                    <div class="col-3"><button type="button" class="kiosk-btn kiosk-btn-primary w-100 keyboard-key" data-key="2">2</button></div>
                    <div class="col-3"><button type="button" class="kiosk-btn kiosk-btn-primary w-100 keyboard-key" data-key="3">3</button></div>
                    <div class="col-3"><button type="button" class="kiosk-btn kiosk-btn-primary w-100 keyboard-key" data-key="4">4</button></div>
                    <div class="col-3"><button type="button" class="kiosk-btn kiosk-btn-primary w-100 keyboard-key" data-key="5">5</button></div>
                    <div class="col-3"><button type="button" class="kiosk-btn kiosk-btn-primary w-100 keyboard-key" data-key="6">6</button></div>
                    <div class="col-3"><button type="button" class="kiosk-btn kiosk-btn-primary w-100 keyboard-key" data-key="7">7</button></div>
                    <div class="col-3"><button type="button" class="kiosk-btn kiosk-btn-primary w-100 keyboard-key" data-key="8">8</button></div>
                    <div class="col-3"><button type="button" class="kiosk-btn kiosk-btn-primary w-100 keyboard-key" data-key="9">9</button></div>
                    <div class="col-3"><button type="button" class="kiosk-btn kiosk-btn-primary w-100 keyboard-key" data-key="0">0</button></div>
                    <div class="col-3"><button type="button" class="kiosk-btn kiosk-btn-secondary w-100 keyboard-key" data-key="/">/</button></div>
                    <div class="col-3"><button type="button" class="kiosk-btn kiosk-btn-secondary w-100 keyboard-key" data-key="backspace">⌫</button></div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="row g-3">
                <div class="col-md-6">
                    <button type="submit" class="kiosk-btn kiosk-btn-success w-100" id="payButton">
                        <i class="fas fa-lock me-2"></i>
                        Pay Securely
                    </button>
                </div>
                <div class="col-md-6">
                    <button type="button" class="kiosk-btn kiosk-btn-secondary w-100" onclick="cancelPayment()">
                        <i class="fas fa-times me-2"></i>
                        Cancel
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Alternative Actions -->
    <div class="row g-3 mt-4">
        <div class="col-md-6">
            <a href="{{ route('kiosk.payment.amount', $appointment) }}" class="kiosk-btn kiosk-btn-secondary w-100">
                <i class="fas fa-arrow-left me-2"></i>
                Back to Payment Options
            </a>
        </div>
        <div class="col-md-6">
            <button class="kiosk-btn kiosk-btn-warning w-100" onclick="needHelp()">
                <i class="fas fa-question-circle me-2"></i>
                Need Help?
            </button>
        </div>
    </div>
</div>

<style>
.card-reader-container {
    position: relative;
    display: inline-block;
    margin: 2rem 0;
}

.card-reader {
    background: #f8f9fa;
    border: 3px solid #dee2e6;
    border-radius: 12px;
    padding: 2rem;
    position: relative;
    width: 300px;
    margin: 0 auto;
}

.card-slot {
    background: #fff;
    border: 2px solid #dee2e6;
    border-radius: 8px;
    height: 60px;
    position: relative;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 1rem;
}

.card-chip {
    background: #ffd700;
    border-radius: 4px;
    width: 30px;
    height: 25px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.card-stripes {
    display: flex;
    gap: 2px;
}

.stripe {
    width: 3px;
    height: 40px;
    background: #000;
}

.reader-lights {
    display: flex;
    justify-content: center;
    gap: 1rem;
    margin-top: 1rem;
}

.light {
    width: 20px;
    height: 20px;
    border-radius: 50%;
    border: 2px solid #dee2e6;
}

.light.red { background: #dc3545; }
.light.yellow { background: #ffc107; }
.light.green { background: #28a745; }

.light.active {
    box-shadow: 0 0 10px currentColor;
}

.contactless-icon {
    position: absolute;
    top: -10px;
    right: -10px;
    background: white;
    border-radius: 50%;
    width: 60px;
    height: 60px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    border: 2px solid #dee2e6;
}

.keyboard-key {
    font-size: 1.2rem;
    padding: 1rem;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const cardStatus = document.getElementById('cardStatus');
    const paymentForm = document.getElementById('paymentForm');
    const cardPaymentForm = document.getElementById('cardPaymentForm');
    const payButton = document.getElementById('payButton');

    let currentField = null;
    let cardInserted = false;

    // Voice guidance
    speakText('Card payment. Please insert your card into the reader or tap your contactless card to begin payment.');

    // Simulate card detection
    setTimeout(() => {
        simulateCardDetection();
    }, 3000);

    // Handle keyboard input
    document.querySelectorAll('.keyboard-key').forEach(key => {
        key.addEventListener('click', function() {
            const keyValue = this.dataset.key;

            if (!currentField) return;

            if (keyValue === 'backspace') {
                currentField.value = currentField.value.slice(0, -1);
            } else {
                currentField.value += keyValue;
            }

            formatInput(currentField);
            speakText(keyValue === 'backspace' ? 'backspace' : keyValue);
        });
    });

    // Focus handling for inputs
    document.querySelectorAll('input').forEach(input => {
        input.addEventListener('focus', function() {
            currentField = this;
        });
    });

    // Form submission
    cardPaymentForm.addEventListener('submit', function(e) {
        e.preventDefault();

        if (!validateCardForm()) {
            return;
        }

        payButton.disabled = true;
        payButton.innerHTML = '<div class="kiosk-spinner"></div> Processing Payment...';
        speakText('Processing payment. Please wait.');

        // Simulate payment processing
        setTimeout(() => {
            window.location.href = '{{ route("kiosk.payment.receipt", $appointment) }}';
        }, 3000);
    });

    // Auto-format inputs
    document.getElementById('card_number').addEventListener('input', function() {
        this.value = this.value.replace(/\D/g, '').replace(/(\d{4})(?=\d)/g, '$1 ');
    });

    document.getElementById('expiry').addEventListener('input', function() {
        this.value = this.value.replace(/\D/g, '').replace(/(\d{2})(?=\d)/g, '$1/');
    });

    document.getElementById('cvv').addEventListener('input', function() {
        this.value = this.value.replace(/\D/g, '');
    });
});

function simulateCardDetection() {
    const cardStatus = document.getElementById('cardStatus');
    const paymentForm = document.getElementById('paymentForm');

    cardStatus.className = 'alert alert-success';
    cardStatus.innerHTML = '<i class="fas fa-check-circle me-2"></i>Card detected! Processing...';

    speakText('Card detected. Processing payment information.');

    setTimeout(() => {
        cardStatus.innerHTML = '<i class="fas fa-credit-card me-2"></i>Card accepted. Please enter any additional details if required.';
        paymentForm.style.display = 'block';
        speakText('Card accepted. Please enter your card details if prompted.');

        // Focus on first input
        document.getElementById('card_number').focus();
    }, 2000);
}

function formatInput(field) {
    const value = field.value.replace(/\D/g, '');

    switch(field.id) {
        case 'card_number':
            field.value = value.replace(/(\d{4})(?=\d)/g, '$1 ');
            break;
        case 'expiry':
            field.value = value.replace(/(\d{2})(?=\d)/g, '$1/');
            break;
    }
}

function validateCardForm() {
    const cardNumber = document.getElementById('card_number').value.replace(/\s/g, '');
    const expiry = document.getElementById('expiry').value;
    const cvv = document.getElementById('cvv').value;
    const cardholderName = document.getElementById('cardholder_name').value;

    if (cardNumber.length < 13 || cardNumber.length > 19) {
        speakText('Please enter a valid card number.');
        showKioskError('Please enter a valid card number.');
        document.getElementById('card_number').focus();
        return false;
    }

    if (!expiry.match(/^\d{2}\/\d{2}$/)) {
        speakText('Please enter a valid expiry date.');
        showKioskError('Please enter a valid expiry date (MM/YY).');
        document.getElementById('expiry').focus();
        return false;
    }

    if (cvv.length < 3 || cvv.length > 4) {
        speakText('Please enter a valid CVV.');
        showKioskError('Please enter a valid CVV.');
        document.getElementById('cvv').focus();
        return false;
    }

    if (!cardholderName.trim()) {
        speakText('Please enter the cardholder name.');
        showKioskError('Please enter the cardholder name.');
        document.getElementById('cardholder_name').focus();
        return false;
    }

    return true;
}

function cancelPayment() {
    if (confirm('Are you sure you want to cancel this payment?')) {
        speakText('Payment cancelled. Returning to payment options.');
        window.location.href = '{{ route("kiosk.payment.amount", $appointment) }}';
    }
}

function needHelp() {
    speakText('Help requested. A staff member will assist you shortly.');
    showKioskError('A staff member has been notified and will assist you with your payment. Please wait for assistance.');
}

function showKioskError(message) {
    const errorDiv = document.createElement('div');
    errorDiv.className = 'kiosk-card border-danger mt-3';
    errorDiv.innerHTML = `
        <div class="text-center text-danger">
            <i class="fas fa-exclamation-triangle fa-2x mb-3"></i>
            <h5>Error</h5>
            <p class="mb-0">${message}</p>
        </div>
    `;

    // Insert after the payment form
    const paymentForm = document.getElementById('paymentForm');
    if (paymentForm.nextSibling) {
        paymentForm.parentNode.insertBefore(errorDiv, paymentForm.nextSibling);
    } else {
        paymentForm.parentNode.appendChild(errorDiv);
    }

    // Auto-remove after 5 seconds
    setTimeout(() => {
        if (errorDiv.parentNode) {
            errorDiv.remove();
        }
    }, 5000);
}

function speakText(text) {
    if (typeof window.speakText === 'function') {
        window.speakText(text);
    }
}
</script>
@endsection
