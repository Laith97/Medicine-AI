@extends('master')

@section('title', 'Login - MedCura Clinical Platform')

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
    content: '🔐';
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
</style>
@endpush

@section('content')
<div class="auth-page">
    <div class="container-fluid">
        <div class="row min-vh-100">
            <!-- Left side - Information -->
            <div class="col-lg-6 auth-info-section d-none d-lg-flex">
                <div class="auth-info-content">
                    <i class="bi bi-shield-check display-1 text-primary mb-4"></i>
                    <h1 class="display-5 fw-bold text-white mb-3">Welcome Back to Your Healthcare Hub</h1>
                    <p class="lead text-white-50 mb-4">Access your personalized healthcare dashboard with AI-powered tools and secure patient management.</p>
                    <div class="auth-features">
                        <div class="feature-item mb-3">
                            <i class="bi bi-check-circle text-success me-3"></i>
                            <span class="text-white">AI Diagnosis Assistant</span>
                        </div>
                        <div class="feature-item mb-3">
                            <i class="bi bi-check-circle text-success me-3"></i>
                            <span class="text-white">Patient Management</span>
                        </div>
                        <div class="feature-item mb-3">
                            <i class="bi bi-check-circle text-success me-3"></i>
                            <span class="text-white">Secure Health Records</span>
                        </div>
                        <div class="feature-item mb-3">
                            <i class="bi bi-check-circle text-success me-3"></i>
                            <span class="text-white">Voice Assistant Access</span>
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
                            <h2 class="auth-title">Welcome Back</h2>
                            <p class="auth-subtitle">Sign in to your account</p>
                        </div>

                    <!-- Login Form -->
                    <form method="POST" action="{{ route('login') }}" class="auth-form">
                        @csrf

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
                                autofocus
                                placeholder="Enter your email"
                            >
                            @error('email')
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
                                    placeholder="Enter your password"
                                >
                                <button type="button" class="password-toggle" onclick="togglePassword('password')" aria-label="Toggle password visibility">
                                    <i class="bi bi-eye" id="password-eye"></i>
                                </button>
                            </div>
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Remember Me & Forgot Password -->
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="remember" id="remember">
                                <label class="form-check-label" for="remember">
                                    Remember me
                                </label>
                            </div>
                            @if (Route::has('password.request'))
                                <a href="{{ route('password.request') }}" class="auth-link">
                                    Forgot password?
                                </a>
                            @endif
                        </div>

                        <!-- Login Button -->
                        <button type="submit" class="btn auth-btn w-100 mb-3">
                            <i class="bi bi-box-arrow-in-right me-2"></i>
                            Sign In
                        </button>

                        <div class="auth-divider">
                            <span>or</span>
                        </div>
                        <div class="text-center">
                            <p class="mb-0">New Patient?</p>
                            <a href="{{ route('patient.register') }}" class="auth-link-primary">
                                Register Here <i class="bi bi-arrow-right ms-1"></i>
                            </a>
                        </div>

<!--
                        <div class="text-center">
                            <p class="mb-0">Don't have an account?</p>
                            <a href="{{ route('register') }}" class="auth-link-primary">
                                Create your account <i class="bi bi-arrow-right ms-1"></i>
                            </a>
                        </div>-->
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
    border-radius: 8px;
    padding: 0.75rem 1rem;
    font-size: 1rem;
    transition: all 0.3s ease;
    background: rgba(255, 255, 255, 0.8);
}

.auth-input:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    background: white;
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
</script>
@endsection
