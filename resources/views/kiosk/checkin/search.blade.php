@extends('layouts.kiosk')

@section('title', 'Find Your Appointment | MedCura AI Kiosk')

@section('content')
<div class="text-center">
    <!-- Progress Indicator -->
    <div class="kiosk-progress mb-4">
        <div class="progress-step completed">1</div>
        <div class="progress-step active">2</div>
        <div class="progress-step">3</div>
        <div class="progress-step">4</div>
    </div>

    <!-- Search Header -->
    <div class="mb-5">
        <i class="fas fa-search text-primary" style="font-size: 4rem; margin-bottom: 2rem;"></i>
        <h1 class="display-5 fw-bold mb-3">Find Your Appointment</h1>
        <p class="lead text-muted mb-4" style="font-size: 1.3rem;">
            Enter your appointment details to continue
        </p>
    </div>

    <!-- Search Form -->
    <div class="kiosk-card">
        <form method="POST" action="{{ route('kiosk.checkin.search.submit') }}" id="searchForm">
            @csrf

            <!-- Search Type Selection -->
            <div class="mb-4">
                <h3 class="h5 mb-3">How would you like to search?</h3>
                <div class="row g-3">
                    <div class="col-md-3">
                        <input type="radio" class="btn-check" name="search_type" id="appointment_number" value="appointment_number"
                               {{ request('type') === 'appointment_number' || !request('type') ? 'checked' : '' }}>
                        <label class="kiosk-btn kiosk-btn-secondary w-100" for="appointment_number">
                            <i class="fas fa-hashtag me-2"></i>
                            Appointment Number
                        </label>
                    </div>

                    <div class="col-md-3">
                        <input type="radio" class="btn-check" name="search_type" id="name" value="name"
                               {{ request('type') === 'personal' ? 'checked' : '' }}>
                        <label class="kiosk-btn kiosk-btn-secondary w-100" for="name">
                            <i class="fas fa-user me-2"></i>
                            Name
                        </label>
                    </div>

                    <div class="col-md-3">
                        <input type="radio" class="btn-check" name="search_type" id="phone" value="phone"
                               {{ request('type') === 'personal' ? 'checked' : '' }}>
                        <label class="kiosk-btn kiosk-btn-secondary w-100" for="phone">
                            <i class="fas fa-phone me-2"></i>
                            Phone
                        </label>
                    </div>

                    <div class="col-md-3">
                        <input type="radio" class="btn-check" name="search_type" id="email" value="email"
                               {{ request('type') === 'personal' ? 'checked' : '' }}>
                        <label class="kiosk-btn kiosk-btn-secondary w-100" for="email">
                            <i class="fas fa-envelope me-2"></i>
                            Email
                        </label>
                    </div>
                </div>
            </div>

            <!-- Search Input -->
            <div class="mb-4">
                <label for="search_value" class="form-label h4 mb-3">
                    <span id="searchLabel">Enter Appointment Number</span>
                </label>
                <input type="text" name="search_value" id="search_value" class="kiosk-input form-control text-center"
                       placeholder="Enter your information here" required
                       value="{{ old('search_value') }}"
                       style="font-size: 1.5rem; letter-spacing: 0.1em;">

                @error('search_value')
                    <div class="text-danger mt-2">{{ $message }}</div>
                @enderror
            </div>

            <!-- Virtual Keyboard -->
            <div class="mb-4">
                <div class="row g-2" id="virtualKeyboard">
                    <!-- Numbers 1-9 -->
                    <div class="col-4"><button type="button" class="kiosk-btn kiosk-btn-primary w-100 keyboard-key" data-key="1">1</button></div>
                    <div class="col-4"><button type="button" class="kiosk-btn kiosk-btn-primary w-100 keyboard-key" data-key="2">2</button></div>
                    <div class="col-4"><button type="button" class="kiosk-btn kiosk-btn-primary w-100 keyboard-key" data-key="3">3</button></div>
                    <div class="col-4"><button type="button" class="kiosk-btn kiosk-btn-primary w-100 keyboard-key" data-key="4">4</button></div>
                    <div class="col-4"><button type="button" class="kiosk-btn kiosk-btn-primary w-100 keyboard-key" data-key="5">5</button></div>
                    <div class="col-4"><button type="button" class="kiosk-btn kiosk-btn-primary w-100 keyboard-key" data-key="6">6</button></div>
                    <div class="col-4"><button type="button" class="kiosk-btn kiosk-btn-primary w-100 keyboard-key" data-key="7">7</button></div>
                    <div class="col-4"><button type="button" class="kiosk-btn kiosk-btn-primary w-100 keyboard-key" data-key="8">8</button></div>
                    <div class="col-4"><button type="button" class="kiosk-btn kiosk-btn-primary w-100 keyboard-key" data-key="9">9</button></div>
                    <div class="col-4"><button type="button" class="kiosk-btn kiosk-btn-primary w-100 keyboard-key" data-key="0">0</button></div>
                    <div class="col-4"><button type="button" class="kiosk-btn kiosk-btn-secondary w-100 keyboard-key" data-key="clear">Clear</button></div>
                    <div class="col-4"><button type="button" class="kiosk-btn kiosk-btn-secondary w-100 keyboard-key" data-key="backspace">⌫</button></div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="row g-3">
                <div class="col-md-6">
                    <button type="submit" class="kiosk-btn kiosk-btn-success w-100" id="searchButton">
                        <i class="fas fa-search me-2"></i>
                        Search Appointment
                    </button>
                </div>
                <div class="col-md-6">
                    <a href="{{ route('kiosk.checkin.start') }}" class="kiosk-btn kiosk-btn-secondary w-100">
                        <i class="fas fa-arrow-left me-2"></i>
                        Back
                    </a>
                </div>
            </div>
        </form>
    </div>

    <!-- Help Text -->
    <div class="mt-4">
        <p class="text-muted small">
            <i class="fas fa-info-circle me-2"></i>
            Your appointment number can be found in your confirmation email or SMS message.
            If you don't have it, try searching by your name or contact information.
        </p>
    </div>
</div>

@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show position-fixed top-50 start-50 translate-middle" style="z-index: 9999; min-width: 400px;" role="alert">
    <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('search_value');
    const searchLabel = document.getElementById('searchLabel');
    const searchTypeRadios = document.querySelectorAll('input[name="search_type"]');
    const virtualKeyboard = document.getElementById('virtualKeyboard');
    const searchButton = document.getElementById('searchButton');

    // Update search label and input based on selected type
    function updateSearchInterface() {
        const selectedType = document.querySelector('input[name="search_type"]:checked').value;

        switch(selectedType) {
            case 'appointment_number':
                searchLabel.textContent = 'Enter Appointment Number';
                searchInput.placeholder = 'e.g., APT-123456';
                searchInput.type = 'text';
                virtualKeyboard.style.display = 'block';
                break;
            case 'name':
                searchLabel.textContent = 'Enter Your Full Name';
                searchInput.placeholder = 'Enter your full name';
                searchInput.type = 'text';
                virtualKeyboard.style.display = 'none';
                break;
            case 'phone':
                searchLabel.textContent = 'Enter Phone Number';
                searchInput.placeholder = 'e.g., (555) 123-4567';
                searchInput.type = 'tel';
                virtualKeyboard.style.display = 'block';
                break;
            case 'email':
                searchLabel.textContent = 'Enter Email Address';
                searchInput.placeholder = 'Enter your email address';
                searchInput.type = 'email';
                virtualKeyboard.style.display = 'none';
                break;
        }

        speakText(`Search by ${selectedType.replace('_', ' ')}. ${searchLabel.textContent.toLowerCase()}.`);
    }

    // Handle search type changes
    searchTypeRadios.forEach(radio => {
        radio.addEventListener('change', updateSearchInterface);
    });

    // Virtual keyboard functionality
    document.querySelectorAll('.keyboard-key').forEach(key => {
        key.addEventListener('click', function() {
            const keyValue = this.dataset.key;
            const currentValue = searchInput.value;

            if (keyValue === 'clear') {
                searchInput.value = '';
            } else if (keyValue === 'backspace') {
                searchInput.value = currentValue.slice(0, -1);
            } else {
                searchInput.value += keyValue;
            }

            searchInput.focus();
            speakText(keyValue === 'clear' ? 'cleared' : keyValue === 'backspace' ? 'backspace' : keyValue);
        });
    });

    // Form submission
    document.getElementById('searchForm').addEventListener('submit', function(e) {
        searchButton.disabled = true;
        searchButton.innerHTML = '<div class="kiosk-spinner"></div> Searching...';
        speakText('Searching for your appointment. Please wait.');
    });

    // Initialize interface
    updateSearchInterface();

    // Focus on search input
    searchInput.focus();

    // Voice guidance
    setTimeout(() => {
        speakText('Find your appointment. Select how you want to search, then enter your information.');
    }, 1000);
});

function speakText(text) {
    if (typeof window.speakText === 'function') {
        window.speakText(text);
    }
}
</script>
@endsection
