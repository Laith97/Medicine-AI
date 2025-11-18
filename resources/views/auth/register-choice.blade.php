@extends('master')

@section('title', 'Register - Choose Your Account Type')

@section('content')
<div class="dashboard-header py-2 border-bottom">
    <h2 class="h1 mb-1" style="font-weight: 700;">Register</h2>
    <p>Choose your account type to get started</p>
</div>

<div class="auth-wrapper hero-section d-flex align-items-center" style="background: linear-gradient(135deg, #fbfdff00 0%, #34495e 100%); min-height: 100vh; padding: 2rem 0;">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12 col-md-8 col-lg-6">
                <div class="auth-card">
                    <!-- Header -->
                    <div class="auth-header text-center mb-5">
                        <div class="auth-logo mb-3">
                            <i class="bi bi-heart-pulse" style="font-size: 3rem; color: #DE6262;"></i>
                        </div>
                        <h2 class="auth-title">Join Our Platform</h2>
                        <p class="auth-subtitle">Select the account type that best describes you</p>
                    </div>

                    <!-- Registration Options -->
                    <div class="row g-4">
                        <!-- Doctor Registration -->
                        <div class="col-12">
                            <div class="registration-option-card doctor-card" onclick="window.location.href='/register-doctor'">
                                <div class="card-body text-center p-4">
                                    <div class="option-icon mb-3">
                                        <i class="fas fa-user-doctor" style="font-size: 3rem; color: #DE6262;"></i>
                                    </div>
                                    <h4 class="option-title mb-3">Healthcare Professional</h4>
                                    <p class="option-description mb-4">
                                        Register as a doctor, nurse, or healthcare provider to access clinical decision support tools, patient management, and professional features.
                                    </p>
                                    <div class="option-features mb-4">
                                        <div class="row text-start">
                                            <div class="col-6">
                                                <i class="fas fa-check text-success me-2"></i>
                                                <small>Clinical Decision Support</small>
                                            </div>
                                            <div class="col-6">
                                                <i class="fas fa-check text-success me-2"></i>
                                                <small>Voice Assistant</small>
                                            </div>
                                            <div class="col-6">
                                                <i class="fas fa-check text-success me-2"></i>
                                                <small>Patient Management</small>
                                            </div>
                                            <div class="col-6">
                                                <i class="fas fa-check text-success me-2"></i>
                                                <small>Professional Landing Page</small>
                                            </div>
                                        </div>
                                    </div>
                                    <button class="btn auth-btn w-100">
                                        <i class="fas fa-user-doctor me-2"></i>
                                        Register as Healthcare Professional
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Patient Registration -->
                        <div class="col-12">
                            <div class="registration-option-card patient-card" onclick="window.location.href='{{ route('patient.register') }}'">
                                <div class="card-body text-center p-4">
                                    <div class="option-icon mb-3">
                                        <i class="fas fa-user-injured" style="font-size: 3rem; color: #4A90E2;"></i>
                                    </div>
                                    <h4 class="option-title mb-3">Patient</h4>
                                    <p class="option-description mb-4">
                                        Create a patient account to easily book appointments, manage your health records, and access personalized healthcare services.
                                    </p>
                                    <div class="option-features mb-4">
                                        <div class="row text-start">
                                            <div class="col-6">
                                                <i class="fas fa-check text-success me-2"></i>
                                                <small>Easy Booking</small>
                                            </div>
                                            <div class="col-6">
                                                <i class="fas fa-check text-success me-2"></i>
                                                <small>Health Records</small>
                                            </div>
                                            <div class="col-6">
                                                <i class="fas fa-check text-success me-2"></i>
                                                <small>Appointment History</small>
                                            </div>
                                            <div class="col-6">
                                                <i class="fas fa-check text-success me-2"></i>
                                                <small>Email Reminders</small>
                                            </div>
                                        </div>
                                    </div>
                                    <button class="btn patient-btn w-100">
                                        <i class="fas fa-user-injured me-2"></i>
                                        Register as Patient
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Guest Option -->
                    <div class="text-center mt-4">
                        <div class="auth-divider">
                            <span>or</span>
                        </div>
                        <p class="text-muted mb-3">Don't want to create an account?</p>
                        <a href="{{ route('doctors.index') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-search me-2"></i>
                            Browse Doctors as Guest
                        </a>
                    </div>

                    <!-- Login Link -->
                    <div class="text-center mt-4">
                        <p class="mb-0">Already have an account?</p>
                        <a href="{{ route('login') }}" class="auth-link-primary">
                            Sign in here <i class="bi bi-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.auth-wrapper {
    position: relative;
    overflow: hidden;
}

.auth-wrapper::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 1000"><defs><radialGradient id="a" cx="50%" cy="50%"><stop offset="0%" stop-color="%23ffffff" stop-opacity="0.1"/><stop offset="100%" stop-color="%23ffffff" stop-opacity="0"/></radialGradient></defs><circle cx="200" cy="200" r="100" fill="url(%23a)"/><circle cx="800" cy="300" r="150" fill="url(%23a)"/><circle cx="400" cy="700" r="120" fill="url(%23a)"/></svg>');
    opacity: 0.3;
}

.auth-card {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(20px);
    border-radius: 20px;
    padding: 3rem;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.2);
    position: relative;
    z-index: 1;
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

.registration-option-card {
    background: white;
    border: 2px solid #e9ecef;
    border-radius: 16px;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 12px rgba(0,0,0,0.05);
}

.registration-option-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
    border-color: #DE6262;
}

.doctor-card:hover {
    border-color: #DE6262;
    background: linear-gradient(135deg, rgba(222, 98, 98, 0.02), rgba(222, 98, 98, 0.01));
}

.patient-card:hover {
    border-color: #4A90E2;
    background: linear-gradient(135deg, rgba(74, 144, 226, 0.02), rgba(74, 144, 226, 0.01));
}

.option-icon {
    width: 80px;
    height: 80px;
    background: linear-gradient(45deg, #f8f9fa, #e9ecef);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
    transition: all 0.3s ease;
}

.registration-option-card:hover .option-icon {
    transform: scale(1.1);
}

.doctor-card:hover .option-icon {
    background: linear-gradient(45deg, #DE6262, #E87A7A);
    color: white;
}

.patient-card:hover .option-icon {
    background: linear-gradient(45deg, #4A90E2, #5BA0F2);
    color: white;
}

.option-title {
    color: #2c3e50;
    font-weight: 600;
    font-size: 1.5rem;
}

.option-description {
    color: #6c757d;
    font-size: 0.95rem;
    line-height: 1.5;
}

.option-features small {
    color: #495057;
    font-weight: 500;
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
    color: white;
}

.patient-btn {
    background: linear-gradient(135deg, #4A90E2 0%, #5BA0F2 100%);
    border: none;
    border-radius: 12px;
    padding: 0.875rem 1.5rem;
    font-weight: 600;
    font-size: 1rem;
    color: white;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(74, 144, 226, 0.3);
}

.patient-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(74, 144, 226, 0.4);
    background: linear-gradient(135deg, #3A80D2 0%, #4B90E2 100%);
    color: white;
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

.btn-outline-secondary {
    border-color: #6c757d;
    color: #6c757d;
    transition: all 0.3s ease;
}

.btn-outline-secondary:hover {
    background: #6c757d;
    border-color: #6c757d;
    color: white;
    transform: translateY(-2px);
}

@media (max-width: 768px) {
    .auth-card {
        padding: 2rem;
        margin: 1rem;
    }

    .auth-title {
        font-size: 1.75rem;
    }

    .option-title {
        font-size: 1.25rem;
    }

    .registration-option-card {
        margin-bottom: 1rem;
    }
}
</style>

@endsection