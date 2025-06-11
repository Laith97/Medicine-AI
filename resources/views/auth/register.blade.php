@extends('master')

@section('title', 'Register - AI Medical Diagnosis')

@section('content')
<div class="auth-wrapper hero-section d-flex align-items-center" style="background: linear-gradient(135deg, #fbfdff00 0%, #34495e 100%); min-height: 100vh; padding: 2rem 0;">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12 col-md-6 col-lg-5">
                <div class="auth-card">
                    <!-- Header -->
                    <div class="auth-header text-center mb-4">
                        <div class="auth-logo mb-3">
                            <i class="bi bi-heart-pulse" style="font-size: 3rem; color: #DE6262;"></i>
                        </div>
                        <h2 class="auth-title">Create Account</h2>
                        <p class="auth-subtitle">Join AI Medical Diagnosis platform today</p>
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
                                class="form-control auth-input @error('name') is-invalid @enderror" 
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
