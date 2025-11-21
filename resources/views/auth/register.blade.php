@extends('master')

@section('title', 'Register - AI Medical Diagnosis')

@push('styles')
<style>
/* Professional Dashboard Header Styling */
.dashboard-header {
    background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
    border-radius: 15px;
    padding: 2rem;
    margin-bottom: 2rem;
    margin-top: 90px; /* Add space from fixed top-bar */
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
    content: '👤';
    font-size: 2rem;
}

.dashboard-header p {
    color: rgba(255, 255, 255, 0.9);
    font-size: 1.1rem;
    font-weight: 500;
    margin-bottom: 0;
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

/* Responsive adjustments for auth layout */
@media (max-width: 991px) {
    .auth-form-section {
        padding: 1rem;
    }

    .auth-card {
        padding: 1.5rem;
    }

    .auth-info-content .display-5 {
        font-size: 2rem;
    }
}

@media (max-width: 576px) {
    .auth-card {
        padding: 1rem;
        margin: 0.5rem;
    }

    .auth-form-container {
        max-width: 100%;
    }
}
</style>
@endpush

@section('content')
<div class="auth-page">
    <div class="container-fluid">
        <div class="row min-vh-100">
            <!-- Left side - Information -->
            <div class="col-lg-6 auth-info-section d-none d-lg-flex">
                <div class="auth-info-content">
                    <i class="bi bi-heart-pulse display-1 text-primary mb-4"></i>
                    <h1 class="display-5 fw-bold text-white mb-3">Join Our AI Healthcare Platform</h1>
                    <p class="lead text-white-50 mb-4">Create your account and start revolutionizing patient care with advanced AI diagnosis tools.</p>
                    <div class="auth-features">
                        <div class="feature-item mb-3">
                            <i class="bi bi-check-circle text-success me-3"></i>
                            <span class="text-white">AI-Powered Diagnosis</span>
                        </div>
                        <div class="feature-item mb-3">
                            <i class="bi bi-check-circle text-success me-3"></i>
                            <span class="text-white">Voice Assistant Technology</span>
                        </div>
                        <div class="feature-item mb-3">
                            <i class="bi bi-check-circle text-success me-3"></i>
                            <span class="text-white">Patient Management</span>
                        </div>
                        <div class="feature-item mb-3">
                            <i class="bi bi-check-circle text-success me-3"></i>
                            <span class="text-white">Professional Landing Pages</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right side - Form -->
            <div class="col-lg-6 col-12 auth-form-section">
                <div class="auth-form-container">
                    <!-- Compact header for mobile -->
                    <div class="text-center mb-4 d-lg-none">
                        <i class="bi bi-heart-pulse text-primary mb-3" style="font-size: 2.5rem;"></i>
                        <h2 class="h5 text-muted">Welcome to AI Medical Diagnosis</h2>
                    </div>

                    <!-- Main form card -->
                    <div class="auth-card">
                        <!-- Header -->
                        <div class="auth-header text-center mb-4">
                            <h2 class="auth-title">Create Account</h2>
                            <p class="auth-subtitle">Join our healthcare platform today</p>
                        </div>

                    <!-- Register Form -->
                    <form method="POST" action="{{ route('register') }}" class="auth-form">
                        @csrf

                        <!-- Name Field -->
                        <div class="form-group mb-3">
                            <label for="name" class="form-label">
                                <i class="bi bi-person me-2"></i>Full Name
                            </label>
                            <input 
                                id="name" 
                                type="text" 
                                name="name" 
                                class="form-control auth-input @if($errors ?? false) @error('name') is-invalid @enderror @endif" 
                                value="{{ old('name') }}" 
                                required 
                                autofocus
                                placeholder="Enter your full name"
                            >
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Email Field -->
                        <div class="form-group mb-3">
                            <label for="email" class="form-label">
                                <i class="bi bi-envelope me-2"></i>Email Address
                            </label>
                            <input 
                                id="email" 
                                type="email" 
                                name="email" 
                                class="form-control auth-input @error('email') is-invalid @enderror" 
                                value="{{ old('email') }}" 
                                required
                                placeholder="Enter your email"
                            >
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Phone Number Field -->
                        <div class="form-group mb-3">
                            <label for="phone" class="form-label">
                                <i class="bi bi-telephone me-2"></i>Phone Number <span class="text-danger">*</span>
                            </label>
                            <input 
                                id="phone" 
                                type="tel" 
                                name="phone" 
                                class="form-control auth-input @error('phone') is-invalid @enderror" 
                                value="{{ old('phone') }}" 
                                required
                                placeholder="Enter your phone number (e.g., +1234567890)"
                                pattern="^\+?[1-9]\d{1,14}$"
                            >
                            <div class="form-text">
                                <small class="text-muted">
                                    <i class="bi bi-info-circle me-1"></i>
                                    Required for SMS invoice reminders. Include country code (e.g., +1 for US)
                                </small>
                            </div>
                            @error('phone')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Password Field -->
                        <div class="form-group mb-3">
                            <label for="password" class="form-label">
                                <i class="bi bi-lock me-2"></i>Password
                            </label>
                            <div class="password-input-wrapper">
                                <input 
                                    id="password" 
                                    type="password" 
                                    name="password" 
                                    class="form-control auth-input @error('password') is-invalid @enderror" 
                                    required
                                    placeholder="Create a strong password"
                                >
                                <button type="button" class="password-toggle" onclick="togglePassword('password')">
                                    <i class="bi bi-eye" id="password-eye"></i>
                                </button>
                            </div>
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Confirm Password Field -->
                        <div class="form-group mb-3">
                            <label for="password_confirmation" class="form-label">
                                <i class="bi bi-shield-check me-2"></i>Confirm Password
                            </label>
                            <div class="password-input-wrapper">
                                <input 
                                    id="password_confirmation" 
                                    type="password" 
                                    name="password_confirmation" 
                                    class="form-control auth-input @error('password_confirmation') is-invalid @enderror" 
                                    required
                                    placeholder="Confirm your password"
                                >
                                <button type="button" class="password-toggle" onclick="togglePassword('password_confirmation')">
                                    <i class="bi bi-eye" id="password_confirmation-eye"></i>
                                </button>
                            </div>
                            @error('password_confirmation')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Medical Specialty Field -->
                        <div class="form-group mb-3">
                            <label for="specialty" class="form-label">
                                <i class="bi bi-heart-pulse me-2"></i>Medical Specialty <span class="text-danger">*</span>
                            </label>
                            <select class="form-control auth-input @error('specialty') is-invalid @enderror" name="specialty_select" id="specialty_select" onchange="toggleCustomSpecialty()">
                                <option value="">-- Select Your Specialty --</option>
                                
                                <optgroup label="🧠 General & Internal Medicine">
                                    <option value="General Practitioner">General Practitioner (GP) / Family Medicine</option>
                                    <option value="Internal Medicine">Internal Medicine (Internist)</option>
                                </optgroup>
                                
                                <optgroup label="🩺 Internal Medicine Subspecialties">
                                    <option value="Cardiology">Cardiology (Heart)</option>
                                    <option value="Pulmonology">Pulmonology (Lungs)</option>
                                    <option value="Gastroenterology">Gastroenterology (Digestive system)</option>
                                    <option value="Nephrology">Nephrology (Kidneys)</option>
                                    <option value="Endocrinology">Endocrinology (Hormones & glands)</option>
                                    <option value="Hematology">Hematology (Blood)</option>
                                    <option value="Hematology-Oncology">Hematology-Oncology (Blood cancers)</option>
                                    <option value="Rheumatology">Rheumatology (Joints & autoimmune diseases)</option>
                                    <option value="Infectious Disease">Infectious Disease</option>
                                    <option value="Dermatology">Dermatology (Skin, hair, nails)</option>
                                    <option value="Allergy & Immunology">Allergy & Immunology</option>
                                    <option value="Reproductive Endocrinology">Reproductive Endocrinology (Fertility hormones)</option>
                                </optgroup>
                                
                                <optgroup label="🧠 Emergency & Critical Care">
                                    <option value="Emergency Medicine">Emergency Medicine</option>
                                    <option value="Critical Care">Critical Care / Intensive Care Medicine</option>
                                </optgroup>
                                
                                <optgroup label="💉 Anesthesia & Pain Management">
                                    <option value="Anesthesiology">Anesthesiology</option>
                                    <option value="Pain Management">Pain Management / Interventional Pain Medicine</option>
                                </optgroup>
                                
                                <optgroup label="🧠 Neurology & Psychiatry">
                                    <option value="Neurology">Neurology (Brain & nerves)</option>
                                    <option value="Neurosurgery">Neurosurgery (Brain & spine surgery)</option>
                                    <option value="Psychiatry">Psychiatry (Mental health)</option>
                                    <option value="Child & Adolescent Psychiatry">Child & Adolescent Psychiatry</option>
                                    <option value="Behavioral & Developmental Pediatrics">Behavioral & Developmental Pediatrics</option>
                                </optgroup>
                                
                                <optgroup label="🦴 Surgical Specialties">
                                    <option value="General Surgery">General Surgery</option>
                                    <option value="Orthopedic Surgery">Orthopedic Surgery (Bones & joints)</option>
                                    <option value="Cardiothoracic Surgery">Cardiothoracic Surgery (Heart & lungs)</option>
                                    <option value="Vascular Surgery">Vascular Surgery (Blood vessels)</option>
                                    <option value="Pediatric Vascular Surgery">Pediatric Vascular Surgery</option>
                                    <option value="Plastic & Reconstructive Surgery">Plastic & Reconstructive Surgery</option>
                                    <option value="Oral & Maxillofacial Surgery">Oral & Maxillofacial Surgery</option>
                                    <option value="Surgical Oncology">Surgical Oncology (Cancer surgery)</option>
                                    <option value="Colorectal Surgery">Colorectal Surgery</option>
                                    <option value="Urology">Urology (Urinary & male reproductive system)</option>
                                    <option value="ENT">ENT / Otolaryngology (Ear, Nose, Throat)</option>
                                    <option value="Ophthalmic Surgery">Ophthalmic Surgery (Eye surgery)</option>
                                    <option value="Pediatric Surgery">Pediatric Surgery</option>
                                    <option value="Hand Surgery">Hand Surgery</option>
                                </optgroup>
                                
                                <optgroup label="👶 Pediatrics & Women's Health">
                                    <option value="Pediatrics">Pediatrics</option>
                                    <option value="Neonatology">Neonatology (Newborn care)</option>
                                    <option value="Pediatric Behavioral Medicine">Pediatric Behavioral Medicine</option>
                                    <option value="Obstetrics & Gynecology">Obstetrics & Gynecology (OB/GYN)</option>
                                    <option value="Gynecologic Oncology">Gynecologic Oncology</option>
                                    <option value="Reproductive Endocrinology & Infertility">Reproductive Endocrinology & Infertility</option>
                                    <option value="Maternal–Fetal Medicine">Maternal–Fetal Medicine</option>
                                </optgroup>
                                
                                <optgroup label="🧬 Diagnostic & Support Specialties">
                                    <option value="Pathology">Pathology (Laboratory medicine)</option>
                                    <option value="Radiology">Radiology (Medical imaging)</option>
                                    <option value="Interventional Radiology">Interventional Radiology</option>
                                    <option value="Nuclear Medicine">Nuclear Medicine</option>
                                    <option value="Endoscopy">Endoscopy / GI Endoscopy</option>
                                    <option value="Electrodiagnostic Medicine">Electrodiagnostic Medicine (EMG, EEG)</option>
                                </optgroup>
                                
                                <optgroup label="🏥 Other Medical Specialties">
                                    <option value="Oncology">Oncology (Medical cancer care)</option>
                                    <option value="Hepatology">Hepatology (Liver diseases)</option>
                                    <option value="Genetic Hematology">Genetic Hematology</option>
                                    <option value="Geriatrics">Geriatrics (Elderly care)</option>
                                    <option value="Physical Medicine & Rehabilitation">Physical Medicine & Rehabilitation</option>
                                    <option value="Occupational & Environmental Medicine">Occupational & Environmental Medicine</option>
                                    <option value="Sports Medicine">Sports Medicine</option>
                                </optgroup>
                                
                                <optgroup label="✏️ Custom">
                                    <option value="other">Other (Please specify)</option>
                                </optgroup>
                            </select>
                            
                            <!-- Custom Specialty Input (Hidden by default) -->
                            <div id="custom_specialty_container" style="display: none;" class="mt-2">
                                <input 
                                    type="text" 
                                    name="custom_specialty" 
                                    id="custom_specialty" 
                                    class="form-control auth-input @if($errors ?? false) @error('custom_specialty') is-invalid @enderror @endif"
                                    placeholder="Please enter your medical specialty"
                                    value="{{ old('custom_specialty') }}"
                                >
                                @if($errors ?? false)
                                    @error('custom_specialty')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                @endif
                            </div>
                            
                            <!-- Hidden field to store the final specialty value -->
                            <input type="hidden" name="specialty" id="specialty" value="{{ old('specialty') }}">
                            
                            @if($errors ?? false)
                                @error('specialty')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            @endif
                        </div>

                        <!-- Plan Selection Section (Removed - users start on free trial by default) -->
                        <input type="hidden" name="selected_plan" id="selected_plan" value="free">
                        <input type="hidden" name="selected_billing" id="selected_billing" value="monthly">>

                        <!-- Terms Agreement -->
                        <div class="form-check mb-4">
                            <input class="form-check-input" type="checkbox" id="terms" required>
                            <label class="form-check-label" for="terms">
                                I agree to the <a href="#" class="auth-link">Terms of Service</a> and <a href="#" class="auth-link">Privacy Policy</a>
                            </label>
                        </div>

                        <!-- Register Button -->
                        <button type="submit" class="btn auth-btn w-100 mb-3">
                            <i class="bi bi-person-plus me-2"></i>
                            Create Account
                        </button>

                        <!-- Divider -->
                        <div class="auth-divider">
                            <span>or</span>
                        </div>

                        <!-- Login Link -->
                        <div class="text-center">
                            <p class="mb-0">Already have an account?</p>
                            <a href="{{ route('login') }}" class="auth-link-primary">
                                Sign in here <i class="bi bi-arrow-right ms-1"></i>
                            </a>
                        </div>
                        </form>
                    </div>

                    <!-- Footer links -->
                    <div class="text-center mt-4">
                        <small class="text-muted">Need help? <a href="{{ route('contact') }}" class="text-decoration-none">Contact Support</a></small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.auth-page {
    min-height: 100vh;
    background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
    position: relative;
    overflow: hidden;
}

.auth-info-section {
    background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 3rem;
    position: relative;
}

.auth-info-section::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 1000"><defs><radialGradient id="a" cx="50%" cy="50%"><stop offset="0%" stop-color="%23ffffff" stop-opacity="0.05"/><stop offset="100%" stop-color="%23ffffff" stop-opacity="0"/></radialGradient></defs><circle cx="200" cy="200" r="100" fill="url(%23a)"/><circle cx="800" cy="300" r="150" fill="url(%23a)"/><circle cx="400" cy="700" r="120" fill="url(%23a)"/></svg>');
    opacity: 0.3;
}

.auth-info-content {
    position: relative;
    z-index: 2;
    max-width: 500px;
    text-align: center;
}

.auth-form-section {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 2rem;
    min-height: 100vh;
}

.auth-form-container {
    width: 100%;
    max-width: 450px;
}

.auth-card {
    background: rgba(255, 255, 255, 0.98);
    backdrop-filter: blur(20px);
    border-radius: 16px;
    padding: 2rem;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.3);
    position: relative;
    z-index: 2;
}

.auth-title {
    color: #2c3e50;
    font-weight: 700;
    font-size: 2rem;
    margin-bottom: 0.5rem;
}

.auth-subtitle {
    color: #6c757d;
    font-size: 1rem;
    margin-bottom: 0;
}

.form-label {
    color: #2c3e50;
    font-weight: 600;
    font-size: 0.9rem;
    margin-bottom: 0.5rem;
}

.auth-input {
    border: 2px solid #e9ecef;
    border-radius: 12px;
    padding: 0.75rem 1rem;
    font-size: 1rem;
    transition: all 0.3s ease;
    background: rgba(255, 255, 255, 0.8);
}

.auth-input:focus {
    border-color: #DE6262;
    box-shadow: 0 0 0 0.2rem rgba(222, 98, 98, 0.25);
    background: white;
}

select.form-control.auth-input {
    -webkit-appearance: menulist;
    -moz-appearance: menulist;
    appearance: menulist;
}

.password-input-wrapper {
    position: relative;
}

.password-toggle {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: #6c757d;
    cursor: pointer;
    padding: 0;
    font-size: 1.1rem;
}

.password-toggle:hover {
    color: #DE6262;
}

.auth-btn {
    background: linear-gradient(135deg, #DE6262 0%, #FFB88C 100%);
    border: none;
    border-radius: 12px;
    padding: 0.875rem 1.5rem;
    font-weight: 600;
    font-size: 1rem;
    color: white;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(222, 98, 98, 0.3);
}

.auth-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(222, 98, 98, 0.4);
    background: linear-gradient(135deg, #c44d4d 0%, #e6a373 100%);
}

.auth-divider {
    text-align: center;
    margin: 1.5rem 0;
    position: relative;
}

.auth-divider::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 0;
    right: 0;
    height: 1px;
    background: #e9ecef;
}

.auth-divider span {
    background: rgba(255, 255, 255, 0.95);
    padding: 0 1rem;
    color: #6c757d;
    font-size: 0.9rem;
}

.auth-link {
    color: #DE6262;
    text-decoration: none;
    font-size: 0.9rem;
    transition: color 0.3s ease;
}

.auth-link:hover {
    color: #c44d4d;
    text-decoration: underline;
}

.auth-link-primary {
    color: #DE6262;
    text-decoration: none;
    font-weight: 600;
    font-size: 1rem;
    transition: all 0.3s ease;
}

.auth-link-primary:hover {
    color: #c44d4d;
    transform: translateX(3px);
}

.form-check-input:checked {
    background-color: #DE6262;
    border-color: #DE6262;
}

.form-check-input:focus {
    box-shadow: 0 0 0 0.2rem rgba(222, 98, 98, 0.25);
}

/* Custom specialty input styling */
#custom_specialty_container {
    animation: slideDown 0.3s ease-out;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

#custom_specialty {
    border: 2px solid #e9ecef;
    transition: border-color 0.3s ease;
}

#custom_specialty:focus {
    border-color: #DE6262;
    box-shadow: 0 0 0 0.2rem rgba(222, 98, 98, 0.25);
}

@media (max-width: 768px) {
    .auth-card {
        padding: 2rem;
        margin: 1rem;
    }
    
    .auth-title {
        font-size: 1.75rem;
    }
}
</style>

<script>
function togglePassword(inputId) {
    const input = document.getElementById(inputId);
    const eye = document.getElementById(inputId + '-eye');
    
    if (input.type === 'password') {
        input.type = 'text';
        eye.className = 'bi bi-eye-slash';
    } else {
        input.type = 'password';
        eye.className = 'bi bi-eye';
    }
}

function toggleCustomSpecialty() {
    const select = document.getElementById('specialty_select');
    const customContainer = document.getElementById('custom_specialty_container');
    const customInput = document.getElementById('custom_specialty');
    const hiddenInput = document.getElementById('specialty');
    
    if (select.value === 'other') {
        customContainer.style.display = 'block';
        customInput.required = true;
        customInput.focus();
        hiddenInput.value = ''; // Clear hidden field when showing custom input
    } else {
        customContainer.style.display = 'none';
        customInput.required = false;
        customInput.value = '';
        hiddenInput.value = select.value; // Set hidden field to selected value
    }
}

// Update hidden field when custom specialty is typed
document.addEventListener('DOMContentLoaded', function() {
    const customInput = document.getElementById('custom_specialty');
    const hiddenInput = document.getElementById('specialty');
    const select = document.getElementById('specialty_select');
    
    // Handle custom input changes
    customInput.addEventListener('input', function() {
        if (select.value === 'other') {
            hiddenInput.value = this.value;
        }
    });
    
    // Handle form submission to ensure proper validation
    const form = document.querySelector('.auth-form');
    form.addEventListener('submit', function(e) {
        const select = document.getElementById('specialty_select');
        const customInput = document.getElementById('custom_specialty');
        const hiddenInput = document.getElementById('specialty');
        
        if (select.value === 'other') {
            if (!customInput.value.trim()) {
                e.preventDefault();
                customInput.focus();
                customInput.classList.add('is-invalid');
                return false;
            }
            hiddenInput.value = customInput.value.trim();
        } else {
            hiddenInput.value = select.value;
        }
    });
    
    // Initialize on page load (for validation errors)
    const oldSpecialty = '{{ old("specialty") }}';
    const oldCustomSpecialty = '{{ old("custom_specialty") }}';
    
    if (oldCustomSpecialty) {
        document.getElementById('specialty_select').value = 'other';
        toggleCustomSpecialty();
        document.getElementById('custom_specialty').value = oldCustomSpecialty;
        document.getElementById('specialty').value = oldCustomSpecialty;
    } else if (oldSpecialty) {
        // Check if old specialty exists in dropdown
        const selectOptions = Array.from(document.getElementById('specialty_select').options);
        const optionExists = selectOptions.some(option => option.value === oldSpecialty);
        
        if (optionExists) {
            document.getElementById('specialty_select').value = oldSpecialty;
        } else {
            // If specialty doesn't exist in dropdown, treat as custom
            document.getElementById('specialty_select').value = 'other';
            toggleCustomSpecialty();
            document.getElementById('custom_specialty').value = oldSpecialty;
        }
        document.getElementById('specialty').value = oldSpecialty;
    }
    
    // Plan Selection Functions
    const selectedBilling = '{{ $selectedBilling }}';
    
    // Initialize billing toggle based on URL parameter
    if (selectedBilling === 'yearly') {
        switchBilling('yearly');
    } else {
        switchBilling('monthly');
    }
});

// Plan Selection Functions
function selectPlan(planKey) {
    // Remove selected class from all plans
    document.querySelectorAll('.plan-card').forEach(card => {
        card.classList.remove('selected');
    });
    
    // Add selected class to clicked plan
    document.querySelector(`.plan-card[data-plan="${planKey}"]`).classList.add('selected');
    
    // Update hidden field
    document.getElementById('selected_plan').value = planKey;
}

function switchBilling(period) {
    const monthlyToggle = document.getElementById('monthly-toggle');
    const yearlyToggle = document.getElementById('yearly-toggle');
    const monthlyPrices = document.querySelectorAll('.monthly-price-display');
    const yearlyPrices = document.querySelectorAll('.yearly-price-display');
    
    document.getElementById('selected_billing').value = period;
    
    if (period === 'monthly') {
        monthlyToggle.style.background = '#DE6262';
        monthlyToggle.style.color = 'white';
        yearlyToggle.style.background = 'transparent';
        yearlyToggle.style.color = '#6C757D';
        
        monthlyPrices.forEach(price => price.style.display = 'inline');
        yearlyPrices.forEach(price => price.style.display = 'none');
    } else {
        yearlyToggle.style.background = '#DE6262';
        yearlyToggle.style.color = 'white';
        monthlyToggle.style.background = 'transparent';
        monthlyToggle.style.color = '#6C757D';
        
        monthlyPrices.forEach(price => price.style.display = 'none');
        yearlyPrices.forEach(price => price.style.display = 'inline');
    }
}
</script>

<!-- Plan Selection Styles -->
<style>
.plan-card {
    border: 2px solid #e9ecef;
    border-radius: 12px;
    padding: 1rem;
    cursor: pointer;
    transition: all 0.3s ease;
    background: white;
    height: 100%;
}

.plan-card:hover {
    border-color: #DE6262;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(222, 98, 98, 0.15);
}

.plan-card.selected {
    border-color: #DE6262;
    background: linear-gradient(135deg, rgba(222, 98, 98, 0.05), rgba(222, 98, 98, 0.02));
    box-shadow: 0 4px 12px rgba(222, 98, 98, 0.2);
}

.plan-header {
    text-align: center;
    margin-bottom: 0.75rem;
}

.plan-name {
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 0.25rem;
    font-size: 0.95rem;
}

.plan-price .price {
    font-weight: 700;
    font-size: 1.25rem;
    color: #DE6262;
}

.plan-price .period {
    font-size: 0.8rem;
    color: #6c757d;
}

.plan-features {
    list-style: none;
    padding: 0;
    margin: 0;
    font-size: 0.8rem;
}

.plan-features li {
    padding: 0.2rem 0;
    color: #6c757d;
}

.billing-toggle {
    font-weight: 500;
}

.billing-toggle:hover {
    background: #f8f9fa !important;
}
</style>

@endsection
